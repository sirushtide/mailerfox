<?php

namespace App\Services;

use App\Models\CampaignRecipient;
use App\Models\ListSubscriber;

class PersonalizationService
{
    private const STANDARD_TAG_KEYS = ['first_name', 'last_name', 'email', 'full_name', 'name', 'unsubscribe_url'];

    public function personalizeForSubscriber(string $content, ListSubscriber $subscriber, bool $replaceBareStandardTags = true): string
    {
        $fullName = trim((string) (($subscriber->first_name ?? '') . ' ' . ($subscriber->last_name ?? '')));
        $standardValues = [
            'first_name' => (string) ($subscriber->first_name ?? ''),
            'last_name' => (string) ($subscriber->last_name ?? ''),
            'email' => (string) $subscriber->email,
            'full_name' => $fullName,
            'name' => $fullName,
            'unsubscribe_url' => '',
        ];

        $replacements = [
            // Handle @ prefixed tags used in the UI (longest first)
            '@{{first_name}}' => $standardValues['first_name'],
            '@{{last_name}}' => $standardValues['last_name'],
            '@{{email}}' => $standardValues['email'],
            '@{{full_name}}' => $standardValues['full_name'],
            '@{{name}}' => $standardValues['name'],
            '@{{unsubscribe_url}}' => $standardValues['unsubscribe_url'],
            // Double brace patterns (longer)
            '{{first_name}}' => $standardValues['first_name'],
            '{{last_name}}' => $standardValues['last_name'],
            '{{email}}' => $standardValues['email'],
            '{{full_name}}' => $standardValues['full_name'],
            '{{name}}' => $standardValues['name'],
            '{{unsubscribe_url}}' => $standardValues['unsubscribe_url'],
            // Single brace patterns (shorter - must be last)
            '{first_name}' => $standardValues['first_name'],
            '{last_name}' => $standardValues['last_name'],
            '{email}' => $standardValues['email'],
            '{full_name}' => $standardValues['full_name'],
            '{name}' => $standardValues['name'],
            '{unsubscribe_url}' => $standardValues['unsubscribe_url'],
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        if ($replaceBareStandardTags) {
            $content = $this->replaceBareStandardTags($content, $standardValues);
        }

        $custom = is_array($subscriber->custom_fields) ? $subscriber->custom_fields : [];
        return $this->replaceCustomFieldTags($content, $custom);
    }

    public function personalizeForCampaignRecipient(string $content, CampaignRecipient $recipient, bool $replaceBareStandardTags = true): string
    {
        $fullName = trim((string) (($recipient->first_name ?? '') . ' ' . ($recipient->last_name ?? '')));
        $standardValues = [
            'first_name' => (string) ($recipient->first_name ?? ''),
            'last_name' => (string) ($recipient->last_name ?? ''),
            'email' => (string) $recipient->email,
            'full_name' => $fullName,
            'name' => $fullName,
            'unsubscribe_url' => '',
        ];

        $replacements = [
            // Handle @ prefixed tags used in the UI (longest first)
            '@{{first_name}}' => $standardValues['first_name'],
            '@{{last_name}}' => $standardValues['last_name'],
            '@{{email}}' => $standardValues['email'],
            '@{{full_name}}' => $standardValues['full_name'],
            '@{{name}}' => $standardValues['name'],
            '@{{unsubscribe_url}}' => $standardValues['unsubscribe_url'],
            // Double brace patterns (longer)
            '{{first_name}}' => $standardValues['first_name'],
            '{{last_name}}' => $standardValues['last_name'],
            '{{email}}' => $standardValues['email'],
            '{{full_name}}' => $standardValues['full_name'],
            '{{name}}' => $standardValues['name'],
            '{{unsubscribe_url}}' => $standardValues['unsubscribe_url'],
            // Single brace patterns (shorter - must be last)
            '{first_name}' => $standardValues['first_name'],
            '{last_name}' => $standardValues['last_name'],
            '{email}' => $standardValues['email'],
            '{full_name}' => $standardValues['full_name'],
            '{name}' => $standardValues['name'],
            '{unsubscribe_url}' => $standardValues['unsubscribe_url'],
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        if ($replaceBareStandardTags) {
            $content = $this->replaceBareStandardTags($content, $standardValues);
        }

        $meta = is_array($recipient->meta) ? $recipient->meta : [];
        $custom = (isset($meta['custom_fields']) && is_array($meta['custom_fields'])) ? $meta['custom_fields'] : [];

        return $this->replaceCustomFieldTags($content, $custom);
    }

    public function replaceCustomFieldTags(string $content, array $customFields): string
    {
        if ($content === '') {
            return '';
        }

        return (string) preg_replace_callback('/(?:@?\{\{cf[:_]?([a-zA-Z][a-zA-Z0-9_]*)\}\})|(?:@?\{cf[:_]?([a-zA-Z][a-zA-Z0-9_]*)\})/', function ($m) use ($customFields) {
            $key = (string) (($m[1] ?? '') !== '' ? ($m[1] ?? '') : ($m[2] ?? ''));
            $value = $customFields[$key] ?? '';

            if ($value === null) {
                return '';
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            return '';
        }, $content);
    }

    public function convertPlaceholdersToZeptoMergeTags(string $content, bool $replaceBareStandardTags = true): string
    {
        $content = str_replace(
            ['{first_name}', '{last_name}', '{email}', '{full_name}', '{name}', '{unsubscribe_url}', '@{{first_name}}', '@{{last_name}}', '@{{email}}', '@{{full_name}}', '@{{name}}', '@{{unsubscribe_url}}'],
            ['{{first_name}}', '{{last_name}}', '{{email}}', '{{full_name}}', '{{full_name}}', '{{unsubscribe_url}}', '{{first_name}}', '{{last_name}}', '{{email}}', '{{full_name}}', '{{full_name}}', '{{unsubscribe_url}}'],
            $content
        );

        if ($replaceBareStandardTags) {
            $content = $this->replaceBareStandardTags($content, [
                'first_name' => '{{first_name}}',
                'last_name' => '{{last_name}}',
                'email' => '{{email}}',
                'full_name' => '{{full_name}}',
                'name' => '{{full_name}}',
                'unsubscribe_url' => '{{unsubscribe_url}}',
            ]);
        }

        return (string) preg_replace_callback('/(?:@?\{\{cf[:_]?([a-zA-Z][a-zA-Z0-9_]*)\}\})|(?:@?\{cf[:_]?([a-zA-Z][a-zA-Z0-9_]*)\})/', function ($m) {
            $key = (string) (($m[1] ?? '') !== '' ? ($m[1] ?? '') : ($m[2] ?? ''));
            if ($key === '') {
                return '';
            }
            return '{{cf_' . $key . '}}';
        }, $content);
    }

    private function replaceBareStandardTags(string $content, array $values): string
    {
        if ($content === '' || empty($values)) {
            return $content;
        }

        $availableKeys = array_values(array_intersect(self::STANDARD_TAG_KEYS, array_keys($values)));
        if (empty($availableKeys)) {
            return $content;
        }

        $pattern = '/(?<![a-zA-Z0-9_])(' . implode('|', array_map('preg_quote', $availableKeys)) . ')(?![a-zA-Z0-9_])/i';

        return (string) preg_replace_callback($pattern, function ($match) use ($values) {
            $key = strtolower((string) ($match[1] ?? ''));
            return array_key_exists($key, $values) ? (string) $values[$key] : (string) ($match[0] ?? '');
        }, $content);
    }
}
