<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiBuilder\BackgroundPresetStore;
use Illuminate\Http\Request;

class AiBuilderBackgroundPresetController extends Controller
{
    public function index()
    {
        $presets = BackgroundPresetStore::all();
        return view('admin.ai-builder-background-presets.index', compact('presets'));
    }

    public function create()
    {
        return view('admin.ai-builder-background-presets.index', [
            'presets' => BackgroundPresetStore::all(),
            'editing' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePreset($request);
        BackgroundPresetStore::update($validated);

        return redirect()->route('admin.ai-builder-background-presets.index')
            ->with('success', __('Background preset saved.'));
    }

    public function edit(string $presetId)
    {
        $editing = BackgroundPresetStore::find($presetId);
        $presets = BackgroundPresetStore::all();

        if ($editing === null) {
            return redirect()->route('admin.ai-builder-background-presets.index')
                ->with('error', __('Preset not found.'));
        }

        return view('admin.ai-builder-background-presets.index', compact('editing', 'presets'));
    }

    public function update(Request $request, string $presetId)
    {
        $validated = $this->validatePreset($request);

        if ($validated['preset_id'] !== $presetId) {
            BackgroundPresetStore::delete($presetId);
        }

        BackgroundPresetStore::update($validated);

        return redirect()->route('admin.ai-builder-background-presets.index')
            ->with('success', __('Background preset updated.'));
    }

    public function destroy(string $presetId)
    {
        BackgroundPresetStore::delete($presetId);

        return redirect()->route('admin.ai-builder-background-presets.index')
            ->with('success', __('Background preset deleted.'));
    }

    private function validatePreset(Request $request): array
    {
        $rules = [
            'preset_id' => 'required|string|max:120|regex:/^[a-z0-9-]+$/',
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:grid,grid-fade,dots,mesh,blobs,image,gradflow',
            'description' => 'nullable|string',
            'config_json' => 'required|json',
            'preview_image' => 'nullable|string|max:500',
            'sort_order' => 'required|integer|min:-9999|max:9999',
            'is_active' => 'boolean',
        ];

        $validated = $request->validate($rules);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $config = json_decode($validated['config_json'], true);

        if (! is_array($config) || ! isset($config['type'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'config_json' => 'Config JSON must contain a "type" key.',
            ]);
        }

        $validated['config'] = $config;
        unset($validated['config_json']);

        return $validated;
    }
}
