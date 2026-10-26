@extends('layouts.customer')

@section('title', __('Create Pipeline'))

@section('hide-page-header', '1')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('customer.crm.pipelines.index') }}" class="text-sm text-gray-500 hover:text-[#1E5FEA] transition-colors">&larr; {{ __('Back to pipelines') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ __('Create Pipeline') }}</h1>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
        <form method="POST" action="{{ route('customer.crm.pipelines.store') }}" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Description') }}</label>
                <textarea name="description" rows="2" class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Stages') }} <span class="text-red-500">*</span></label>
                <div id="stages-container" class="space-y-3">
                    @for($i = 0; $i < 3; $i++)
                    <div class="flex items-center gap-3 stage-row">
                        <input type="text" name="stages[{{ $i }}][name]" value="{{ old('stages.'.$i.'.name', $i === 0 ? 'Lead' : ($i === 1 ? 'Qualified' : 'Won')) }}" placeholder="{{ __('Stage name') }}" required class="flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <input type="color" name="stages[{{ $i }}][color]" value="{{ old('stages.'.$i.'.color', ['#3b82f6', '#10b981', '#22c55e'][$i]) }}" class="h-9 w-9 rounded-lg border border-gray-200 cursor-pointer dark:border-gray-600">
                        <select name="stages[{{ $i }}][type]" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="open" {{ old('stages.'.$i.'.type', 'open') === 'open' ? 'selected' : '' }}>{{ __('Open') }}</option>
                            @if($i === 2)
                            <option value="won" selected>{{ __('Won') }}</option>
                            @endif
                            <option value="lost" {{ old('stages.'.$i.'.type') === 'lost' ? 'selected' : '' }}>{{ __('Lost') }}</option>
                        </select>
                    </div>
                    @endfor
                </div>
                <button type="button" onclick="addStage()" class="mt-3 text-sm text-[#1E5FEA] hover:underline">{{ __('+ Add another stage') }}</button>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('customer.crm.pipelines.index') }}" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">{{ __('Cancel') }}</a>
                <button type="submit" class="rounded-lg bg-[#1E5FEA] px-4 py-2 text-sm font-medium text-white hover:bg-[#174bc0] transition-colors">{{ __('Create Pipeline') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
let stageIndex = 3;
function addStage() {
    const container = document.getElementById('stages-container');
    const div = document.createElement('div');
    div.className = 'flex items-center gap-3 stage-row';
    div.innerHTML = `
        <input type="text" name="stages[${stageIndex}][name]" placeholder="{{ __('Stage name') }}" required class="flex-1 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-[#1E5FEA] focus:outline-none focus:ring-1 focus:ring-[#1E5FEA] dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        <input type="color" name="stages[${stageIndex}][color]" value="#3b82f6" class="h-9 w-9 rounded-lg border border-gray-200 cursor-pointer dark:border-gray-600">
        <select name="stages[${stageIndex}][type]" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            <option value="open">{{ __('Open') }}</option>
            <option value="won">{{ __('Won') }}</option>
            <option value="lost">{{ __('Lost') }}</option>
        </select>
        <button type="button" onclick="this.closest('.stage-row').remove()" class="text-gray-400 hover:text-red-500 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
        </button>
    `;
    container.appendChild(div);
    stageIndex++;
}
</script>
@endsection
