<?php

namespace App\Services\PageBuilderAi;

use App\Services\AI\AiProviderRouter;
use App\Services\AiBuilder\BuilderSchema;
use App\Services\AiBuilder\Validator;
use Illuminate\Support\Str;

/**
 * Runs the page builder as a section-by-section agent.
 *
 * The AI talks to us in JSON turns (action / narration / type / codes).
 * We execute its tool calls against a working copy of the page and feed the
 * results back until it finalizes. Every step is emitted as a streamable event
 * so the frontend chat rail can narrate the whole build live.
 */
final class PageBuilderAiAgent
{
    private array $events = [];

    /** @var callable|null */
    private $onEvent;

    private array $workspace;

    private array $touched = [];

    private array $createdIndexes = [];

    private int $tokens = 0;

    private array $plan = [];

    private string $lastPlanJson = '';

    private string $mode = 'page';

    public function __construct(
        private AiProviderRouter $router,
        private PageBuilderAiTools $tools,
        private Validator $validator,
    ) {
    }

    public function run(
        string $operation,
        string $prompt,
        array $page,
        ?array $selectedSection,
        int $sectionIndex,
        string $model,
        string $mode = 'page',
        ?callable $onEvent = null,
    ): array {
        $this->events = [];
        $this->onEvent = $onEvent;
        $this->workspace = [
            'builder' => $page['builder'] ?? 'mailpurse',
            'version' => $page['version'] ?? 1,
            'meta' => $page['meta'] ?? [],
            'sections' => array_values(array_filter($page['sections'] ?? [], 'is_array')),
        ];
        $this->touched = [];
        $this->createdIndexes = [];
        $this->tokens = 0;
        $this->plan = [];
        $this->lastPlanJson = '';
        $this->mode = $mode === 'email' ? 'email' : 'page';

        $system = $this->tools->system($mode);
        $messages = [
            ['role' => 'user', 'content' => $this->tools->prompt($operation, $prompt, $this->workspace, $selectedSection, $sectionIndex, $mode)],
        ];

        $this->emit(PageBuilderAiContract::EVENT_START, [
            'operation' => $operation,
            'model' => $model,
            'page_section_count' => count($this->workspace['sections']),
        ]);

        $rounds = 0;
        $consecutiveThink = 0;
        $finalizeNudges = 0;
        $maxRounds = PageBuilderAiContract::MAX_AGENT_ROUNDS;
        $finalResult = null;
        $wantsFullPage = $operation === PageBuilderAiContract::OPERATION_PAGE || $this->wantsFullPage($prompt);

        while ($rounds < $maxRounds) {
            $rounds++;

            $aiResponse = $this->router->generateChat($model, $messages, PageBuilderAiContract::MAX_TOKENS_PER_ROUND, $system);
            if (!($aiResponse['success'] ?? false)) {
                $message = is_string($aiResponse['message'] ?? null)
                    ? $aiResponse['message']
                    : 'The AI provider could not be reached.';
                $this->emit(PageBuilderAiContract::EVENT_ERROR, ['message' => $message]);
                throw new \RuntimeException($message);
            }

            $this->tokens += (int) ($aiResponse['tokens'] ?? 0);
            $text = is_string($aiResponse['text'] ?? null) ? trim($aiResponse['text']) : '';
            $ai = $this->extractJson($text);
            $messages[] = ['role' => 'assistant', 'content' => $text];

            if ($ai === null) {
                $consecutiveThink++;
                $this->emit(PageBuilderAiContract::EVENT_NARRATE, [
                    'narration' => 'Let me re-compose that step and continue.',
                ]);
                $messages[] = [
                    'role' => 'user',
                    'content' => 'Your previous turn was not valid JSON, so it was ignored. Continue the build and reply with exactly ONE valid JSON object using the response_contract fields (action, narration, type, section_code, ...). If the page is not complete yet, keep calling tools such as create_section. Example: {"action":"tool_call","type":"create_section","narration":"Adding the next section.","section_code":{"name":"Section","style":{},"columns":[{"spans":{"base":12,"md":12,"lg":12},"widgets":[]}]}}.',
                ];
                continue;
            }

            $action = $this->resolveAction($ai);
            $narration = is_string($ai['narration'] ?? null) ? trim($ai['narration']) : '';

            // The AI may publish the build plan (list of sections/steps) at any turn.
            $this->syncPlanFromAi($ai);

            if ($action === PageBuilderAiContract::ACTION_THINK) {
                $consecutiveThink++;
                $this->emit(PageBuilderAiContract::EVENT_THINK, [
                    'thought' => is_string($ai['reasoning'] ?? null) && trim($ai['reasoning']) !== ''
                        ? trim($ai['reasoning'])
                        : ($narration !== '' ? $narration : null),
                    'narration' => $narration,
                ]);
                if ($consecutiveThink > PageBuilderAiContract::MAX_CONSECUTIVE_THINK) {
                    $this->emit(PageBuilderAiContract::EVENT_NARRATE, [
                        'narration' => 'Time to commit the design and wrap up.',
                    ]);
                    $finalResult = $this->finalizeResult($operation, $ai);
                    break;
                }
                continue;
            }

            if ($action === PageBuilderAiContract::ACTION_CLARIFICATION) {
                $question = is_string($ai['question'] ?? null) && trim($ai['question']) !== ''
                    ? trim($ai['question'])
                    : 'Could you give me a bit more detail so I can finish this?';
                $this->emit(PageBuilderAiContract::EVENT_CLARIFICATION, ['question' => $question]);
                $finalResult = ['type' => 'clarification', 'question' => $question];
                break;
            }

            if ($action === PageBuilderAiContract::ACTION_FINALIZE) {
                $pendingPlanItems = count(array_filter($this->plan, fn ($item) => ($item['status'] ?? '') !== 'done'));
                $pageIsThin = count($this->workspace['sections']) < PageBuilderAiContract::MIN_PAGE_SECTIONS;
                if ($wantsFullPage
                    && ($pendingPlanItems > 0 || ($pageIsThin && $finalizeNudges < PageBuilderAiContract::MAX_FINALIZE_NUDGES))) {
                    $finalizeNudges++;
                    if ($this->mode === 'email') {
                        $this->emit(PageBuilderAiContract::EVENT_NARRATE, [
                            'narration' => 'That section is in — continuing to build out the rest of the email.',
                        ]);
                        $messages[] = [
                            'role' => 'user',
                            'content' => 'You are not done yet. The user asked for a complete email template, but it currently has only '
                                . count($this->workspace['sections']) . ' section(s). Keep building section by section: use create_section for the remaining sections (e.g. header/logo, hero, content/body, CTA, footer with copyright/social — whatever fits the request), keep your plan field up to date with the remaining sections, and only finalize once the email template feels complete. Reply with one JSON object.',
                        ];
                    } else {
                        $this->emit(PageBuilderAiContract::EVENT_NARRATE, [
                            'narration' => 'That section is in — continuing to build out the rest of the page.',
                        ]);
                        $messages[] = [
                            'role' => 'user',
                            'content' => 'You are not done yet. The user asked for a complete landing page, but it currently has only '
                                . count($this->workspace['sections']) . ' section(s). Keep building section by section: use create_section for the remaining sections (e.g. about, features, gallery, testimonials, pricing, contact, footer — whatever fits), keep your plan field up to date with the remaining sections, and only finalize once the page feels complete. Reply with one JSON object.',
                        ];
                    }
                    continue;
                }
                $finalResult = $this->finalizeResult($operation, $ai);
                break;
            }

            // Tool call
            $consecutiveThink = 0;
            $tool = is_string($ai['type'] ?? null) ? trim($ai['type']) : '';
            if (!in_array($tool, PageBuilderAiContract::tools(), true)) {
                $this->emit(PageBuilderAiContract::EVENT_ERROR, [
                    'message' => 'The AI requested an unknown tool: ' . ($tool !== '' ? $tool : '(empty)'),
                ]);
                $messages[] = [
                    'role' => 'user',
                    'content' => 'Tool "' . $tool . '" is not in the allowed_tools list. Choose one of: ' . implode(', ', PageBuilderAiContract::tools()) . '.',
                ];
                continue;
            }

            $arguments = $this->toolArguments($ai);
            $this->emit(PageBuilderAiContract::EVENT_TOOL_CALL, [
                'tool' => $tool,
                'arguments' => $arguments,
                'narration' => $narration,
                'targets' => $ai['targets'] ?? null,
            ]);

            if ($tool === PageBuilderAiContract::TOOL_CREATE_SECTION) {
                $this->markPlanStatus(null, 'in_progress');
            }

            $execution = $this->executeTool($tool, $ai, $operation);

            if (($execution['ok'] ?? false) && $tool === PageBuilderAiContract::TOOL_CREATE_SECTION) {
                $createdName = is_string($execution['result']['section']['name'] ?? null)
                    ? $execution['result']['section']['name']
                    : null;
                $this->markPlanStatus($createdName, 'done');
            } else if (($execution['ok'] ?? false) && $tool === PageBuilderAiContract::TOOL_CREATE_PAGE) {
                $this->finalizePlan();
            }

            $this->emit(PageBuilderAiContract::EVENT_TOOL_RESULT, [
                'tool' => $tool,
                'result' => $execution['result'] ?? null,
                'summary' => $execution['summary'] ?? '',
                'ok' => $execution['ok'] ?? false,
            ]);

            $messages[] = [
                'role' => 'user',
                'content' => 'TOOL RESULT for "' . $tool . '":' . "\n" . json_encode($this->trimForContext($execution['result'] ?? []), JSON_UNESCAPED_SLASHES)
                    . $this->nextPlanInstruction($tool),
            ];

            if (!($execution['ok'] ?? false)) {
                $this->emit(PageBuilderAiContract::EVENT_NARRATE, [
                    'narration' => 'That step did not quite work — let me adjust.',
                ]);
            }
        }

        if ($finalResult === null) {
            $finalResult = $this->finalizeResult($operation, ['section_index' => null]);
        }

        // Close out the plan checklist: anything still pending becomes done.
        $this->finalizePlan();

        // Always broadcast the final page so the preview stays in sync.
        if ($finalResult['type'] !== 'clarification') {
            $pageDoc = $this->finalPageDocument();
            if ($finalResult['type'] === 'section' && isset($finalResult['section'])) {
                $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
                    'index' => $this->indexOfSectionId($finalResult['section']['id'] ?? null) ?? 0,
                    'section' => $finalResult['section'],
                    'final' => true,
                ]);
            }
            $this->emit(PageBuilderAiContract::EVENT_PAGE_READY, [
                'page' => $pageDoc,
                'result' => $finalResult,
            ]);
        }

        $this->emit(PageBuilderAiContract::EVENT_DONE, [
            'result' => $finalResult,
            'tokens' => $this->tokens,
            'rounds' => $rounds,
        ]);

        return [
            'events' => $this->events,
            'result' => $finalResult,
            'tokens' => $this->tokens,
            'rounds' => $rounds,
            'touched' => array_values($this->touched),
            'created' => $this->createdIndexes,
        ];
    }

    // ------------------------------------------------------------------
    // Tool execution
    // ------------------------------------------------------------------

    private function executeTool(string $tool, array $ai, string $operation): array
    {
        return match ($tool) {
            PageBuilderAiContract::TOOL_FIND_ELEMENT => $this->toolFindElement($ai),
            PageBuilderAiContract::TOOL_CREATE_SECTION => $this->toolCreateSection($ai),
            PageBuilderAiContract::TOOL_CREATE_PAGE => $this->toolCreatePage($ai),
            PageBuilderAiContract::TOOL_UPDATE_SECTION => $this->toolUpdateSection($ai),
            PageBuilderAiContract::TOOL_INSERT_COLUMN => $this->toolInsertColumn($ai),
            PageBuilderAiContract::TOOL_UPDATE_COLUMN => $this->toolUpdateColumn($ai),
            PageBuilderAiContract::TOOL_REMOVE_COLUMN => $this->toolRemoveColumn($ai),
            PageBuilderAiContract::TOOL_INSERT_ELEMENT => $this->toolInsertElement($ai),
            PageBuilderAiContract::TOOL_UPDATE_ELEMENT => $this->toolUpdateElement($ai),
            PageBuilderAiContract::TOOL_REMOVE_ELEMENT => $this->toolRemoveElement($ai),
            PageBuilderAiContract::TOOL_GET_STYLE_PRESETS => $this->toolGetStylePresets(),
            PageBuilderAiContract::TOOL_FINALIZE => ['ok' => true, 'result' => [], 'summary' => 'Build finalized.'],
            default => ['ok' => false, 'result' => [], 'summary' => 'Unknown tool.'],
        };
    }

    private function toolFindElement(array $ai): array
    {
        $elements = is_array($ai['elements'] ?? null) ? $ai['elements'] : [];
        if (empty($elements) && is_array($ai['targets'] ?? null)) {
            $elements = $ai['targets']['elements'] ?? [];
        }
        if (empty($elements)) {
            return ['ok' => false, 'result' => [], 'summary' => 'No elements requested for find_element.'];
        }
        $found = PageBuilderElementCatalog::find($elements);

        return [
            'ok' => true,
            'result' => [
                'requested' => $elements,
                'elements' => $found,
            ],
            'summary' => 'Loaded ' . count($found) . ' element template' . (count($found) === 1 ? '' : 's') . ': ' . implode(', ', array_column($found, 'type')),
        ];
    }

    private function toolCreateSection(array $ai): array
    {
        $section = $this->cleanSection($ai['section_code'] ?? $ai['section'] ?? $ai);
        if (empty($section)) {
            return ['ok' => false, 'result' => [], 'summary' => 'create_section needs a section_code object.'];
        }

        $section = $this->validator->validate($section, $this->mode);
        $section = $this->assignIds($section);
        $this->workspace['sections'][] = $section;
        $index = count($this->workspace['sections']) - 1;
        $this->touched[$index] = true;
        $this->createdIndexes[] = $index;

        $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
            'index' => $index,
            'section' => $section,
            'final' => false,
        ]);

        $name = $section['name'] ?? 'Section';
        return [
            'ok' => true,
            'result' => ['index' => $index, 'section' => $section],
            'summary' => 'Created "' . $name . '" and added it to the page.',
        ];
    }

    private function toolCreatePage(array $ai): array
    {
        $page = is_array($ai['page_code'] ?? null) ? $ai['page_code'] : $ai;
        $sections = is_array($page['sections'] ?? null) ? $page['sections'] : ($ai['sections'] ?? []);
        $clean = [];
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $clean[] = $this->assignIds($this->validator->validate($section, $this->mode));
        }
        $this->workspace['sections'] = $clean;
        foreach (array_keys($clean) as $i) {
            $this->touched[$i] = true;
            $this->createdIndexes[] = $i;
        }
        $meta = is_array($page['meta'] ?? null) ? $page['meta'] : ($ai['meta'] ?? []);
        $this->workspace['meta'] = $meta;

        // Broadcast each rebuilt section
        foreach ($clean as $i => $section) {
            $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
                'index' => $i,
                'section' => $section,
                'final' => false,
            ]);
        }

        $this->emit(PageBuilderAiContract::EVENT_PAGE_READY, [
            'page' => $this->finalPageDocument(),
            'result' => ['type' => 'page', 'page' => $this->finalPageDocument()],
            'final' => true,
        ]);

        return [
            'ok' => true,
            'result' => ['page' => $this->finalPageDocument()],
            'summary' => 'Assembled the full page with ' . count($clean) . ' section' . (count($clean) === 1 ? '' : 's') . '.',
        ];
    }

    private function toolUpdateSection(array $ai): array
    {
        $index = $this->resolveSectionIndex($ai);
        if ($index === null || !isset($this->workspace['sections'][$index])) {
            return ['ok' => false, 'result' => [], 'summary' => 'update_section: section_index does not exist.'];
        }

        $section = $this->workspace['sections'][$index];

        $name = $ai['name'] ?? $ai['section_code']['name'] ?? null;
        if (is_string($name) && trim($name) !== '') {
            $section['name'] = trim($name);
        }

        $styles = $ai['styles'] ?? $ai['section_code']['styles'] ?? null;
        if (is_array($styles)) {
            $section['style'] = array_merge(is_array($section['style']) ? $section['style'] : [], $styles);
        }

        $columns = $ai['columns'] ?? $ai['section_code']['columns'] ?? null;
        if (is_array($columns)) {
            $section['columns'] = $columns;
        }

        $section = $this->validator->validate($section, $this->mode);
        $section = $this->assignIds($section);
        $this->workspace['sections'][$index] = $section;
        $this->touched[$index] = true;

        $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
            'index' => $index,
            'section' => $section,
            'final' => false,
        ]);

        return [
            'ok' => true,
            'result' => ['index' => $index, 'section' => $section],
            'summary' => 'Updated section "' . ($section['name'] ?? '') . '".',
        ];
    }

    private function toolInsertColumn(array $ai): array
    {
        $index = $this->resolveSectionIndex($ai);
        $column = is_array($ai['column'] ?? null) ? $ai['column'] : ($ai['section_code']['column'] ?? null);
        if ($index === null || !isset($this->workspace['sections'][$index])) {
            return ['ok' => false, 'result' => [], 'summary' => 'insert_column: section_index does not exist.'];
        }
        if (!is_array($column)) {
            return ['ok' => false, 'result' => [], 'summary' => 'insert_column needs a column object.'];
        }

        $section = $this->workspace['sections'][$index];
        $column = $this->validateAndAssignColumn($column);
        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
        $columns[] = $column;
        $section['columns'] = $columns;
        $section = $this->validator->validate($section, $this->mode);
        $this->workspace['sections'][$index] = $section;
        $this->touched[$index] = true;

        $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
            'index' => $index,
            'section' => $section,
            'final' => false,
        ]);

        return [
            'ok' => true,
            'result' => ['index' => $index, 'section' => $section],
            'summary' => 'Inserted a new column into "' . ($section['name'] ?? '') . '".',
        ];
    }

    private function toolUpdateColumn(array $ai): array
    {
        [$index, $columnIndex] = $this->resolveSectionColumn($ai);
        if ($index === null || $columnIndex === null || !isset($this->workspace['sections'][$index])) {
            return ['ok' => false, 'result' => [], 'summary' => 'update_column: target does not exist.'];
        }
        $section = $this->workspace['sections'][$index];
        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
        if (!isset($columns[$columnIndex])) {
            return ['ok' => false, 'result' => [], 'summary' => 'update_column: column_index does not exist.'];
        }

        $target = $columns[$columnIndex];
        $spans = $ai['spans'] ?? null;
        if (is_array($spans)) {
            $target['spans'] = array_merge(is_array($target['spans'] ?? null) ? $target['spans'] : [], $spans);
        }
        $widgets = $ai['widgets'] ?? $ai['section_code']['widgets'] ?? null;
        if (is_array($widgets)) {
            $target['widgets'] = $widgets;
        }
        $columns[$columnIndex] = $this->validateAndAssignColumn($target);
        $section['columns'] = $columns;
        $section = $this->validator->validate($section, $this->mode);
        $this->workspace['sections'][$index] = $section;
        $this->touched[$index] = true;

        $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
            'index' => $index,
            'section' => $section,
            'final' => false,
        ]);

        return [
            'ok' => true,
            'result' => ['index' => $index, 'section' => $section],
            'summary' => 'Updated a column in "' . ($section['name'] ?? '') . '".',
        ];
    }

    private function toolRemoveColumn(array $ai): array
    {
        [$index, $columnIndex] = $this->resolveSectionColumn($ai);
        if ($index === null || $columnIndex === null || !isset($this->workspace['sections'][$index])) {
            return ['ok' => false, 'result' => [], 'summary' => 'remove_column: target does not exist.'];
        }
        $section = $this->workspace['sections'][$index];
        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
        if (!isset($columns[$columnIndex])) {
            return ['ok' => false, 'result' => [], 'summary' => 'remove_column: column_index does not exist.'];
        }
        array_splice($columns, $columnIndex, 1);
        $section['columns'] = $columns;
        $section = $this->validator->validate($section, $this->mode);
        $this->workspace['sections'][$index] = $section;
        $this->touched[$index] = true;

        $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
            'index' => $index,
            'section' => $section,
            'final' => false,
        ]);

        return [
            'ok' => true,
            'result' => ['index' => $index, 'section' => $section],
            'summary' => 'Removed a column from "' . ($section['name'] ?? '') . '".',
        ];
    }

    private function toolInsertElement(array $ai): array
    {
        [$index, $columnIndex] = $this->resolveSectionColumn($ai);
        $widget = $ai['element_code'] ?? null;
        if ($index === null || $columnIndex === null || !isset($this->workspace['sections'][$index])) {
            return ['ok' => false, 'result' => [], 'summary' => 'insert_element: target section does not exist.'];
        }
        if (!is_array($widget) || !is_string($widget['type'] ?? null)) {
            return ['ok' => false, 'result' => [], 'summary' => 'insert_element needs element_code {type, props}.'];
        }

        $section = $this->workspace['sections'][$index];
        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
        if (!isset($columns[$columnIndex])) {
            return ['ok' => false, 'result' => [], 'summary' => 'insert_element: column_index does not exist.'];
        }
        $columns[$columnIndex]['widgets'][] = $this->normalizeWidget($widget);
        $section['columns'] = $columns;
        $section = $this->validator->validate($section, $this->mode);
        $this->workspace['sections'][$index] = $section;
        $this->touched[$index] = true;

        $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
            'index' => $index,
            'section' => $section,
            'final' => false,
        ]);

        return [
            'ok' => true,
            'result' => ['index' => $index, 'section' => $section],
            'summary' => 'Added a ' . ($widget['type'] ?? 'widget') . ' element to "' . ($section['name'] ?? '') . '".',
        ];
    }

    private function toolUpdateElement(array $ai): array
    {
        [$index, $columnIndex, $widgetIndex] = $this->resolveSectionColumnWidget($ai);
        if ($index === null || $columnIndex === null || $widgetIndex === null || !isset($this->workspace['sections'][$index])) {
            return ['ok' => false, 'result' => [], 'summary' => 'update_element: target does not exist.'];
        }
        $section = $this->workspace['sections'][$index];
        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
        if (!isset($columns[$columnIndex]['widgets'][$widgetIndex])) {
            return ['ok' => false, 'result' => [], 'summary' => 'update_element: widget_index does not exist.'];
        }

        $widget = $ai['element_code'] ?? null;
        if (is_array($widget)) {
            $columns[$columnIndex]['widgets'][$widgetIndex] = $this->normalizeWidget($widget);
        }
        $section['columns'] = $columns;
        $section = $this->validator->validate($section, $this->mode);
        $this->workspace['sections'][$index] = $section;
        $this->touched[$index] = true;

        $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
            'index' => $index,
            'section' => $section,
            'final' => false,
        ]);

        return [
            'ok' => true,
            'result' => ['index' => $index, 'section' => $section],
            'summary' => 'Updated an element in "' . ($section['name'] ?? '') . '".',
        ];
    }

    private function toolRemoveElement(array $ai): array
    {
        [$index, $columnIndex, $widgetIndex] = $this->resolveSectionColumnWidget($ai);
        if ($index === null || $columnIndex === null || $widgetIndex === null || !isset($this->workspace['sections'][$index])) {
            return ['ok' => false, 'result' => [], 'summary' => 'remove_element: target does not exist.'];
        }
        $section = $this->workspace['sections'][$index];
        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
        if (!isset($columns[$columnIndex]['widgets'][$widgetIndex])) {
            return ['ok' => false, 'result' => [], 'summary' => 'remove_element: widget_index does not exist.'];
        }
        array_splice($columns[$columnIndex]['widgets'], $widgetIndex, 1);
        $section['columns'] = $columns;
        $section = $this->validator->validate($section, $this->mode);
        $this->workspace['sections'][$index] = $section;
        $this->touched[$index] = true;

        $this->emit(PageBuilderAiContract::EVENT_SECTION_READY, [
            'index' => $index,
            'section' => $section,
            'final' => false,
        ]);

        return [
            'ok' => true,
            'result' => ['index' => $index, 'section' => $section],
            'summary' => 'Removed an element from "' . ($section['name'] ?? '') . '".',
        ];
    }

    private function toolGetStylePresets(): array
    {
        $presets = [
            ['key' => 'css-none', 'label' => 'None', 'category' => 'css'],
            ['key' => 'css-mesh-soft', 'label' => 'Soft Mesh', 'category' => 'css'],
            ['key' => 'css-animated-gradient', 'label' => 'Animated Gradient', 'category' => 'css'],
            ['key' => 'css-blobs', 'label' => 'Blurry Blobs', 'category' => 'css'],
            ['key' => 'css-dots', 'label' => 'Polka Dots', 'category' => 'css'],
            ['key' => 'css-stripes', 'label' => 'Stripes', 'category' => 'css'],
            ['key' => 'svg-blob-1', 'label' => 'Organic Blob', 'category' => 'svg'],
            ['key' => 'svg-blob-2', 'label' => 'Double Blob', 'category' => 'svg'],
            ['key' => 'svg-waves', 'label' => 'Waves', 'category' => 'svg'],
            ['key' => 'gradflow-aurora', 'label' => 'Aurora', 'category' => 'webgl'],
            ['key' => 'gradflow-silk', 'label' => 'Silk', 'category' => 'webgl'],
            ['key' => 'gradflow-cosmic', 'label' => 'Cosmic', 'category' => 'webgl'],
            ['key' => 'gradflow-plasma', 'label' => 'Plasma', 'category' => 'webgl'],
            ['key' => 'gradflow-neon', 'label' => 'Neon', 'category' => 'webgl'],
        ];

        return [
            'ok' => true,
            'result' => [
                'background_presets' => $presets,
                'usage' => 'Set section.style.backgroundPreset = {"key": "<preset key>", "category": "<preset category>", "params": {"color1": "#...", "color2": "#...", "opacity": 1}} to apply one. Use "css-none" or omit it for a plain background. Dark presets (gradflow-*) should match a deliberately dark section.',
                'style_keys' => BuilderSchema::sectionStyleKeys(),
            ],
            'summary' => 'Loaded style presets.',
        ];
    }

    // ------------------------------------------------------------------
    // Result handling
    // ------------------------------------------------------------------

    private function finalizeResult(string $operation, array $ai): array
    {
        // The AI may hand us a full page explicitly.
        $pageCode = is_array($ai['page_code'] ?? null) ? $ai['page_code'] : null;
        if (is_array($pageCode) && is_array($pageCode['sections'] ?? null)) {
            $clean = [];
            foreach ($pageCode['sections'] as $section) {
                if (!is_array($section)) {
                    continue;
                }
                $section = $this->assignIds($this->validator->validate($section, $this->mode));
                $clean[] = $section;
            }
            $this->workspace['sections'] = $clean;
            if (is_array($pageCode['meta'] ?? null)) {
                $this->workspace['meta'] = $pageCode['meta'];
            }
            foreach (array_keys($clean) as $i) {
                $this->touched[$i] = true;
            }
        }

        if ($operation === PageBuilderAiContract::OPERATION_SECTION) {
            // Prefer the specific section the AI pointed at; otherwise the created/touched ones.
            $sectionIndex = $ai['section_index'] ?? null;
            if (is_int($sectionIndex) && isset($this->workspace['sections'][$sectionIndex])) {
                $section = $this->workspace['sections'][$sectionIndex];
                return ['type' => 'section', 'section' => $section];
            }

            $lastCreated = end($this->createdIndexes);
            if ($lastCreated !== false && isset($this->workspace['sections'][$lastCreated])) {
                return ['type' => 'section', 'section' => $this->workspace['sections'][$lastCreated]];
            }

            if (count($this->workspace['sections']) > 0) {
                return ['type' => 'pageset', 'page' => $this->finalPageDocument(), 'sections' => $this->workspace['sections']];
            }

            return ['type' => 'clarification', 'question' => 'I could not build anything from that request — could you rephrase it?'];
        }

        return ['type' => 'page', 'page' => $this->finalPageDocument()];
    }

    private function finalPageDocument(): array
    {
        return [
            'builder' => $this->workspace['builder'],
            'version' => $this->workspace['version'],
            'meta' => $this->workspace['meta'],
            'sections' => array_values($this->workspace['sections']),
        ];
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function wantsFullPage(string $prompt): bool
    {
        $lower = mb_strtolower(is_string($prompt) ? $prompt : '');

        if (preg_match('/\b(landing page|landing-page|home ?page|homepage|web ?page|webpage|web ?site|website|full page|whole page|entire page|one-page site|marketing page)\b/', $lower)) {
            return true;
        }

        if ((bool) preg_match('/(create|build|design|make|generate|build me|i want|i need).{0,40}\b(site|webpage|website|homepage|home ?page|web ?page)\b/', $lower)) {
            return true;
        }

        // Email mode: requests for a full email template must produce every section.
        if (preg_match('/\b(full|whole|complete|entire|long)\b.{0,20}\b(email|newsletter|template|campaign)\b/', $lower)
            || preg_match('/\b(email|newsletter|template|campaign)\b.{0,20}\b(full|whole|complete|entire)\b/', $lower)) {
            return true;
        }

        return (bool) preg_match('/(create|build|design|make|generate|draft|write|compose).{0,60}\b(email|newsletter|email template|email campaign|promo email|promotional email)\b/', $lower);
    }

    private function resolveAction(array $ai): string
    {
        if (!empty($ai['final'])) {
            return PageBuilderAiContract::ACTION_FINALIZE;
        }
        $action = is_string($ai['action'] ?? null) ? trim($ai['action']) : '';
        if (in_array($action, PageBuilderAiContract::actions(), true)) {
            return $action;
        }
        // A tool_call with no action value
        if (is_string($ai['type'] ?? null) && trim($ai['type']) !== '') {
            return PageBuilderAiContract::ACTION_TOOL_CALL;
        }

        return PageBuilderAiContract::ACTION_THINK;
    }

    private function toolArguments(array $ai): array
    {
        $keys = ['elements', 'targets', 'section_code', 'element_code', 'columns', 'styles', 'page_code', 'question', 'section', 'column', 'widgets', 'spans', 'name', 'summary'];
        $out = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $ai) && $ai[$key] !== null) {
                $out[$key] = $ai[$key];
            }
        }

        return $out;
    }

    private function resolveSectionIndex(array $ai): ?int
    {
        $index = $ai['targets']['section_index'] ?? $ai['section_index'] ?? null;
        if (is_string($index) && is_numeric($index)) {
            $index = (int) $index;
        }

        return is_int($index) ? $index : null;
    }

    private function resolveSectionColumn(array $ai): array
    {
        $index = $this->resolveSectionIndex($ai);
        $columnIndex = $ai['targets']['column_index'] ?? $ai['column_index'] ?? null;
        if (is_string($columnIndex) && is_numeric($columnIndex)) {
            $columnIndex = (int) $columnIndex;
        }

        return [$index, is_int($columnIndex) ? $columnIndex : null];
    }

    private function resolveSectionColumnWidget(array $ai): array
    {
        [$index, $columnIndex] = $this->resolveSectionColumn($ai);
        $widgetIndex = $ai['targets']['widget_index'] ?? $ai['widget_index'] ?? null;
        if (is_string($widgetIndex) && is_numeric($widgetIndex)) {
            $widgetIndex = (int) $widgetIndex;
        }

        return [$index, $columnIndex, is_int($widgetIndex) ? $widgetIndex : null];
    }

    private function indexOfSectionId(?string $id): ?int
    {
        if ($id === null) {
            return null;
        }
        foreach ($this->workspace['sections'] as $i => $section) {
            if (($section['id'] ?? null) === $id) {
                return $i;
            }
        }

        return null;
    }

    private function cleanSection(mixed $section): array
    {
        if (!is_array($section)) {
            return [];
        }

        return isset($section['name']) || isset($section['columns']) ? $section : [];
    }

    private function normalizeWidget(array $widget): array
    {
        $type = is_string($widget['type'] ?? null) ? $widget['type'] : 'rich_text';
        if (!BuilderSchema::isValidWidgetType($type, 'page')) {
            $type = BuilderSchema::mapUnknownWidgetType($type);
        }
        $props = is_array($widget['props'] ?? null) ? $widget['props'] : [];
        $widget['type'] = $type;
        $widget['props'] = BuilderSchema::sanitizeProps($props, $type);
        if (empty($widget['id'])) {
            $widget['id'] = 'w_' . Str::random(10);
        }

        return $widget;
    }

    private function validateAndAssignColumn(array $column): array
    {
        $spans = is_array($column['spans'] ?? null)
            ? $column['spans']
            : ['base' => 12, 'md' => 12, 'lg' => 12];
        $widgets = is_array($column['widgets'] ?? null) ? $column['widgets'] : [];
        $widgets = array_values(array_map(fn ($w) => $this->normalizeWidget(is_array($w) ? $w : ['type' => 'rich_text']), $widgets));

        $column = [
            'spans' => $spans,
            'widgets' => $widgets,
        ];
        if (empty($column['id'])) {
            $column['id'] = 'col_' . Str::random(10);
        }

        return $column;
    }

    private function assignIds(array $section): array
    {
        if (empty($section['id'])) {
            $section['id'] = 'sec_' . Str::random(10);
        }
        foreach ($section['columns'] ?? [] as $colIndex => &$column) {
            if (empty($column['id'])) {
                $column['id'] = 'col_' . Str::random(10);
            }
            foreach ($column['widgets'] ?? [] as $widgetIndex => &$widget) {
                if (is_array($widget) && empty($widget['id'])) {
                    $widget['id'] = 'w_' . Str::random(10);
                }
            }
            unset($widget);
        }
        unset($column);

        return $section;
    }

    private function trimForContext(array $data, int $maxBytes = 6000): array
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
        if (is_string($json) && strlen($json) > $maxBytes) {
            return ['truncated' => true, 'payload' => mb_substr($json, 0, $maxBytes)];
        }

        return $data;
    }

    /**
     * Merge the plan list the AI published into the running checklist.
     * New entries are added as pending; existing entries keep their status.
     */
    private function syncPlanFromAi(array $ai): void
    {
        if (!isset($ai['plan']) || !is_array($ai['plan'])) {
            return;
        }

        $items = [];
        foreach ($ai['plan'] as $entry) {
            if (is_string($entry)) {
                $title = trim($entry);
                if ($title !== '') {
                    $items[] = ['id' => 'plan-' . Str::slug($title), 'title' => $title, 'status' => 'pending'];
                }
                continue;
            }
            if (!is_array($entry)) {
                continue;
            }
            $title = trim((string) ($entry['title'] ?? $entry['label'] ?? ''));
            if ($title === '') {
                continue;
            }
            $id = (is_string($entry['id'] ?? null) && trim($entry['id']) !== '')
                ? (string) $entry['id']
                : 'plan-' . Str::slug($title);
            $items[] = ['id' => $id, 'title' => $title, 'status' => 'pending'];
        }

        if (empty($items)) {
            return;
        }

        if (!empty($this->plan)) {
            $existing = [];
            foreach ($this->plan as $p) {
                $existing[$p['id']] = $p;
            }
            $merged = [];
            foreach ($items as $item) {
                if (isset($existing[$item['id']])) {
                    $item['status'] = $existing[$item['id']]['status'];
                    unset($existing[$item['id']]);
                }
                $merged[] = $item;
            }
            // Keep earlier entries the AI stopped repeating (e.g. already-built ones).
            foreach ($existing as $p) {
                $merged[] = $p;
            }
            $this->plan = $merged;
        } else {
            $this->plan = $items;
        }

        $this->emitPlanState();
    }

    private function markPlanStatus(?string $title, string $status): void
    {
        if (empty($this->plan)) {
            return;
        }
        $matched = false;
        foreach ($this->plan as &$item) {
            if ($item['status'] !== 'done' && ($title === null || strcasecmp($item['title'], $title) === 0)) {
                $item['status'] = $status;
                $matched = true;
                break;
            }
        }
        unset($item);
        if ($matched && $status === 'done') {
            foreach ($this->plan as &$item) {
                if ($item['status'] === 'pending') {
                    $item['status'] = 'in_progress';
                    break;
                }
            }
            unset($item);
        }
        if ($matched) {
            $this->emitPlanState();
        }
    }

    private function nextPlanInstruction(string $tool): string
    {
        if ($tool !== PageBuilderAiContract::TOOL_CREATE_SECTION || empty($this->plan)) {
            return '';
        }

        foreach ($this->plan as $item) {
            if (($item['status'] ?? '') !== 'done') {
                return "\nNEXT REQUIRED PLAN STEP: Create the section titled \"" . ($item['title'] ?? 'the next planned section') . "\" now. Do not skip ahead. Continue using create_section before finalizing.";
            }
        }

        return "\nAll planned sections are complete. Finalize the build.";
    }

    private function finalizePlan(): void
    {
        if (empty($this->plan)) {
            return;
        }
        $changed = false;
        foreach ($this->plan as &$item) {
            if ($item['status'] !== 'done') {
                $item['status'] = 'done';
                $changed = true;
            }
        }
        unset($item);
        if ($changed) {
            $this->emitPlanState();
        }
    }

    private function emitPlanState(): void
    {
        if (empty($this->plan)) {
            return;
        }
        $json = json_encode($this->plan, JSON_UNESCAPED_SLASHES);
        if ($json === $this->lastPlanJson) {
            return;
        }
        $this->lastPlanJson = $json;

        $activeId = null;
        foreach ($this->plan as $p) {
            if ($p['status'] === 'in_progress') {
                $activeId = $p['id'];
                break;
            }
        }

        $this->emit(PageBuilderAiContract::EVENT_PLAN, [
            'items' => $this->plan,
            'active_id' => $activeId,
        ]);
    }

    private function extractJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        $text = preg_replace('/<\/?think(?:ing)?>/i', '', $text) ?? $text;

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $text, $matches)) {
            $decoded = json_decode(trim($matches[1]), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/(\{[\s\S]*\})/', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            $fixed = preg_replace('/,\s*([}\]])/', '$1', $matches[1]);
            $decoded = json_decode($fixed ?? '', true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function emit(string $event, array $data): void
    {
        $payload = ['event' => $event, 'data' => $data];
        $this->events[] = $payload;
        if (is_callable($this->onEvent)) {
            ($this->onEvent)($payload);
        }
    }
}
