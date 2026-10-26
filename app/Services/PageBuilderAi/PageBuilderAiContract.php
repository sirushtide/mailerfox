<?php

namespace App\Services\PageBuilderAi;

final class PageBuilderAiContract
{
    public const OPERATION_PAGE = 'page';
    public const OPERATION_SECTION = 'section';
    public const OPERATION_ITERATION = 'iteration';

    // AI actions (the value of the "action" field returned by the model each turn)
    public const ACTION_THINK = 'think';
    public const ACTION_TOOL_CALL = 'tool_call';
    public const ACTION_FINALIZE = 'finalize';
    public const ACTION_CLARIFICATION = 'ask_clarification';

    // Tools the AI can call
    public const TOOL_FIND_ELEMENT = 'find_element';
    public const TOOL_CREATE_SECTION = 'create_section';
    public const TOOL_CREATE_PAGE = 'create_page';
    public const TOOL_UPDATE_SECTION = 'update_section';
    public const TOOL_INSERT_COLUMN = 'insert_column';
    public const TOOL_UPDATE_COLUMN = 'update_column';
    public const TOOL_REMOVE_COLUMN = 'remove_column';
    public const TOOL_INSERT_ELEMENT = 'insert_element';
    public const TOOL_UPDATE_ELEMENT = 'update_element';
    public const TOOL_REMOVE_ELEMENT = 'remove_element';
    public const TOOL_GET_STYLE_PRESETS = 'get_style_presets';
    public const TOOL_ASK_CLARIFICATION = 'ask_clarification';
    public const TOOL_FINALIZE = 'finalize';

    // Legacy aliases kept for backward compatibility
    public const TOOL_TRACKING = '_tracking';
    public const TOOL_CREATE_PAGE_LEGACY = 'create_page';

    // Events streamed to the frontend
    public const EVENT_START = 'agent.start';
    public const EVENT_THINK = 'think';
    public const EVENT_NARRATE = 'narrate';
    public const EVENT_PLAN = 'plan';
    public const EVENT_TOOL_CALL = 'tool_call';
    public const EVENT_TOOL_RESULT = 'tool_result';
    public const EVENT_SECTION_READY = 'section_ready';
    public const EVENT_PAGE_READY = 'page_ready';
    public const EVENT_CLARIFICATION = 'clarification';
    public const EVENT_DONE = 'done';
    public const EVENT_ERROR = 'error';

    // Agent guardrails
    public const MAX_AGENT_ROUNDS = 30;
    public const MAX_CONSECUTIVE_THINK = 8;
    public const MAX_TOKENS_PER_ROUND = 6000;

    // A "page" operation must not be finalized far too thin; nudge the AI to keep
    // building section by section until the page has at least this many sections.
    public const MIN_PAGE_SECTIONS = 3;
    public const MAX_FINALIZE_NUDGES = 2;
    public const PROTOCOL_VERSION = 2;

    public static function operations(): array
    {
        return [self::OPERATION_PAGE, self::OPERATION_SECTION, self::OPERATION_ITERATION];
    }

    public static function actions(): array
    {
        return [self::ACTION_THINK, self::ACTION_TOOL_CALL, self::ACTION_FINALIZE, self::ACTION_CLARIFICATION];
    }

    public static function tools(): array
    {
        return [
            self::TOOL_FIND_ELEMENT,
            self::TOOL_CREATE_SECTION,
            self::TOOL_CREATE_PAGE,
            self::TOOL_UPDATE_SECTION,
            self::TOOL_INSERT_COLUMN,
            self::TOOL_UPDATE_COLUMN,
            self::TOOL_REMOVE_COLUMN,
            self::TOOL_INSERT_ELEMENT,
            self::TOOL_UPDATE_ELEMENT,
            self::TOOL_REMOVE_ELEMENT,
            self::TOOL_GET_STYLE_PRESETS,
            self::TOOL_ASK_CLARIFICATION,
            self::TOOL_FINALIZE,
        ];
    }

    public static function mutationTools(): array
    {
        return [
            self::TOOL_CREATE_SECTION,
            self::TOOL_CREATE_PAGE,
            self::TOOL_UPDATE_SECTION,
            self::TOOL_INSERT_COLUMN,
            self::TOOL_UPDATE_COLUMN,
            self::TOOL_REMOVE_COLUMN,
            self::TOOL_INSERT_ELEMENT,
            self::TOOL_UPDATE_ELEMENT,
            self::TOOL_REMOVE_ELEMENT,
        ];
    }

    public static function events(): array
    {
        return [
            self::EVENT_START,
            self::EVENT_THINK,
            self::EVENT_NARRATE,
            self::EVENT_TOOL_CALL,
            self::EVENT_TOOL_RESULT,
            self::EVENT_SECTION_READY,
            self::EVENT_PAGE_READY,
            self::EVENT_CLARIFICATION,
            self::EVENT_DONE,
            self::EVENT_ERROR,
        ];
    }
}