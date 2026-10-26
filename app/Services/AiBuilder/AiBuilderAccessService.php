<?php

namespace App\Services\AiBuilder;

use App\Models\Customer;
use App\Services\AI\AiProviderRegistry;
use Illuminate\Support\Facades\Auth;

class AiBuilderAccessService
{
    public function settings(Customer $customer): array
    {
        return (array) ($customer->groupSetting('ai_builder') ?? []);
    }

    public function enabledModels(Customer $customer): array
    {
        $settings = $this->settings($customer);

        return array_values((array) data_get($settings, 'models.enabled', []));
    }

    public function defaultModelForCustomer(Customer $customer): ?string
    {
        $enabled = $this->enabledModels($customer);
        if (! empty($enabled)) {
            return $enabled[0];
        }

        return null;
    }

    public function canUseModel(Customer $customer, ?string $model): bool
    {
        if ($model === null || $model === '' || strtolower($model) === 'auto') {
            return true;
        }

        if (str_ends_with(strtolower(trim($model)), ':free')) {
            return true;
        }

        $enabled = $this->enabledModels($customer);

        if (empty($enabled)) {
            return true;
        }

        return in_array($model, $enabled, true);
    }

    public function permission(Customer $customer, string $key): bool
    {
        $settings = $this->settings($customer);

        // If the plan has no ai_builder permissions block yet, default to allowing access.
        if (! array_key_exists('permissions', $settings)) {
            return true;
        }

        return (bool) data_get($settings, "permissions.{$key}", false);
    }

    public function canDiscuss(Customer $customer): bool
    {
        return $this->permission($customer, 'can_discuss');
    }

    public function canShareProject(Customer $customer): bool
    {
        return $this->permission($customer, 'can_share_project');
    }

    public function canCodePreview(Customer $customer): bool
    {
        return $this->permission($customer, 'can_code_preview');
    }

    public function canCodeEdit(Customer $customer): bool
    {
        return $this->permission($customer, 'can_code_edit');
    }

    public function canLivePreviewEdit(Customer $customer): bool
    {
        return $this->permission($customer, 'can_live_preview_edit');
    }

    public function canCustomDomain(Customer $customer): bool
    {
        return $this->permission($customer, 'can_custom_domain');
    }

    public function canByos(Customer $customer): bool
    {
        return $this->permission($customer, 'can_byos');
    }

    public function platformBadge(Customer $customer): array
    {
        $settings = $this->settings($customer);

        return [
            'enabled' => (bool) data_get($settings, 'platform_badge.enabled', false),
            'text' => (string) data_get($settings, 'platform_badge.text', 'Built with MailerFox'),
        ];
    }

    public function capabilities(Customer $customer): array
    {
        return [
            'enabledModels' => $this->enabledModels($customer),
            'permissions' => [
                'canDiscuss' => $this->canDiscuss($customer),
                'canShareProject' => $this->canShareProject($customer),
                'canCodePreview' => $this->canCodePreview($customer),
                'canCodeEdit' => $this->canCodeEdit($customer),
                'canLivePreviewEdit' => $this->canLivePreviewEdit($customer),
                'canCustomDomain' => $this->canCustomDomain($customer),
                'canByos' => $this->canByos($customer),
            ],
            'platformBadge' => $this->platformBadge($customer),
        ];
    }

    public function currentCustomerCapabilities(): array
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof Customer) {
            return [
                'enabledModels' => [],
                'permissions' => [
                    'canDiscuss' => false,
                    'canShareProject' => false,
                    'canCodePreview' => false,
                    'canCodeEdit' => false,
                    'canLivePreviewEdit' => false,
                    'canCustomDomain' => false,
                    'canByos' => false,
                ],
                'platformBadge' => [
                    'enabled' => false,
                    'text' => 'Built with MailerFox',
                ],
            ];
        }

        return $this->capabilities($customer);
    }

    public function allModelsWithAccess(Customer $customer): array
    {
        $enabled = $this->enabledModels($customer);
        $all = [];

        foreach (AiProviderRegistry::providerKeys() as $key) {
            foreach (AiProviderRegistry::models($key) as $model) {
                $all[$model] = [
                    'provider' => $key,
                    'accessible' => empty($enabled) || in_array($model, $enabled, true),
                ];
            }
        }

        return $all;
    }
}
