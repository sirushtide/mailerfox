@extends('layouts.admin')

@section('title', __('Edit Menu'))
@section('page-title', __('Edit Menu'))

@section('breadcrumbs')
<ol class="flex flex-wrap items-center gap-1.5 text-sm text-admin-text-secondary">
    <li>
        <a href="{{ route('admin.dashboard') }}" class="font-medium transition hover:text-admin-text-primary">
            {{ __('Dashboard') }}
        </a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li>
        <a href="{{ route('admin.menus.index') }}" class="font-medium transition hover:text-admin-text-primary">
            {{ __('Menus') }}
        </a>
    </li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li class="font-medium text-admin-text-primary truncate">{{ $menu->name ?: $menu->key }}</li>
    <li aria-hidden="true" class="text-admin-text-secondary/50">/</li>
    <li class="font-medium text-admin-text-primary">{{ __('Edit') }}</li>
</ol>
@endsection

@section('content')
<div class="space-y-6" x-data>
    @if(session('success'))
        <div class="rounded-lg border border-green-300 bg-green-50 p-4 dark:bg-green-900/20 dark:border-green-700">
            <div class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-300 bg-red-50 p-4 dark:bg-red-900/20 dark:border-red-700">
            <ul class="text-sm text-red-800 dark:text-red-300 list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-card>
        <form method="POST" action="{{ route('admin.menus.update', $menu) }}" id="menu-form" class="space-y-6" data-turbo="false">
            @csrf
            @method('PUT')

            <div class="flex flex-col gap-3 border-b border-admin-border pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-admin-text-primary">{{ $menu->name ?: $menu->key }}</h3>
                    <p class="text-xs text-gray-500 dark:text-admin-text-secondary font-mono">{{ $menu->key }}</p>
                </div>
                <div class="w-full sm:w-64">
                    <label class="block text-xs font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Menu name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $menu->name) }}" placeholder="{{ __('Menu name') }}" class="mt-1 block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <div class="space-y-5 lg:col-span-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Add Page') }}</label>
                        <div class="mt-1 flex gap-2">
                            <select id="add-page-id" class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="">{{ __('Select a page') }}</option>
                                @foreach($pages as $p)
                                    <option value="{{ $p->id }}">{{ $p->title }}</option>
                                @endforeach
                            </select>
                            <button type="button" id="btn-add-page" class="shrink-0 px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700">{{ __('Add') }}</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Add Built-in Link') }}</label>
                        <div class="mt-1 flex gap-2">
                            <select id="add-route" class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="">{{ __('Select a route') }}</option>
                                <option value="home">home</option>
                                <option value="features">features</option>
                                <option value="pricing">pricing</option>
                                <option value="blog.index">blog.index</option>
                                <option value="roadmap">roadmap</option>
                                <option value="docs">docs</option>
                                <option value="api.docs.public">api.docs.public</option>
                            </select>
                            <button type="button" id="btn-add-route" class="shrink-0 px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700">{{ __('Add') }}</button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Add Custom Link') }}</label>
                        <div class="mt-1 space-y-2">
                            <input type="text" id="add-custom-label" placeholder="{{ __('Link label') }}" class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <div class="flex gap-2">
                                <input type="text" id="add-custom-url" placeholder="{{ __('https://example.com') }}" class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <button type="button" id="btn-add-custom" class="shrink-0 px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700">{{ __('Add') }}</button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Add Dropdown Group') }}</label>
                        <div class="mt-1 flex gap-2">
                            <input type="text" id="add-dropdown-label" placeholder="{{ __('Dropdown label') }}" class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                            <button type="button" id="btn-add-dropdown" class="shrink-0 px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700">{{ __('Add') }}</button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('Click "+ Link" inside the group to add child links via the inline selector.') }}</p>
                    </div>

                    @if(config('mailpurse.features.mega_menu_builder') && $megaMenus->isNotEmpty())
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-admin-text-primary">{{ __('Add Mega Menu') }}</label>
                            <div class="mt-1 flex gap-2">
                                <input type="text" id="add-megamenu-label" placeholder="{{ __('Mega menu label') }}" class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <button type="button" id="btn-add-megamenu" class="shrink-0 px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700">{{ __('Add') }}</button>
                            </div>
                            <div class="mt-2">
                                <select id="add-megamenu-id" class="block w-full rounded-md border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                    <option value="">{{ __('Select a mega menu') }}</option>
                                    @foreach($megaMenus as $mm)
                                        <option value="{{ $mm->id }}">{{ $mm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('Select a mega menu layout to display on hover.') }}</p>
                        </div>
                    @endif

                    <div class="pt-2">
                        <x-button type="submit" variant="primary">{{ __('Save Menu') }}</x-button>
                    </div>

                    <input type="hidden" name="items" id="items" value="">
                </div>

                <div class="lg:col-span-8">
                    <div class="mx-auto w-full max-w-2xl">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-900 dark:text-admin-text-primary">{{ __('Menu Items') }}</div>
                            <div class="text-xs text-gray-500 dark:text-admin-text-secondary">{{ __('Drag to reorder') }}</div>
                        </div>

                        <div class="mt-3 rounded-lg border border-admin-border bg-white/5">
                            <ul id="menu-items" class="divide-y divide-admin-border"></ul>
                        </div>

                        <p id="menu-items-empty" class="mt-3 hidden text-center text-sm text-gray-500 dark:text-admin-text-secondary">{{ __('No items yet. Add pages, links, or dropdowns from the left.') }}</p>
                    </div>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const initialItems = @json($menu->items ?? []);
    const pagesById = @json($pages->keyBy('id'));
    const megaMenusById = @json($megaMenus->keyBy('id'));

    let items = Array.isArray(initialItems) ? initialItems : [];

    const listEl = document.getElementById('menu-items');
    const itemsInput = document.getElementById('items');
    const emptyEl = document.getElementById('menu-items-empty');

    function sync() {
        itemsInput.value = JSON.stringify(items);
    }

    function labelFor(item) {
        if (item.type === 'page') {
            const p = pagesById[String(item.page_id)] || null;
            return item.label || (p ? p.title : 'Page');
        }
        if (item.type === 'dropdown') {
            return item.label || 'Dropdown';
        }
        if (item.type === 'megamenu') {
            const mm = megaMenusById[String(item.mega_menu_id)] || null;
            return item.label || (mm ? mm.name : 'Mega Menu');
        }
        if (item.type === 'custom') {
            return item.label || item.url || 'Custom Link';
        }
        return item.label || (item.route || 'Route');
    }

    function renderChildItem(child, parentItem, childIndex) {
        const li = document.createElement('li');
        li.className = 'flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-800/50';

        const left = document.createElement('div');
        left.className = 'flex items-center gap-2 min-w-0';

        const indent = document.createElement('div');
        indent.className = 'text-gray-400 select-none text-xs';
        indent.textContent = '└';

        const childLabelText = document.createElement('div');
        childLabelText.className = 'text-xs text-gray-600 dark:text-admin-text-secondary truncate';

        let childDisplayLabel = child.label || child.url || child.route || ('page #' + child.page_id);
        childLabelText.textContent = childDisplayLabel;

        left.appendChild(indent);
        left.appendChild(childLabelText);

        const right = document.createElement('div');
        right.className = 'flex items-center gap-2';

        const childLabelInput = document.createElement('input');
        childLabelInput.type = 'text';
        childLabelInput.className = 'w-36 rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-xs';
        childLabelInput.placeholder = childDisplayLabel;
        childLabelInput.value = typeof child.label === 'string' ? child.label : '';
        childLabelInput.addEventListener('input', function () {
            const v = String(childLabelInput.value || '').trim();
            child.label = v || undefined;
            sync();
        });

        const removeChildBtn = document.createElement('button');
        removeChildBtn.type = 'button';
        removeChildBtn.className = 'text-xs text-red-600 hover:text-red-700';
        removeChildBtn.textContent = '✕';
        removeChildBtn.addEventListener('click', function () {
            parentItem.children.splice(childIndex, 1);
            sync();
            render();
        });

        right.appendChild(childLabelInput);
        right.appendChild(removeChildBtn);

        li.appendChild(left);
        li.appendChild(right);

        return li;
    }

    function renderItem(item, index) {
        if (item.type === 'dropdown') {
            const wrapper = document.createElement('li');
            wrapper.className = 'border-b border-admin-border last:border-0';
            wrapper.draggable = true;
            wrapper.dataset.index = String(index);

            const header = document.createElement('div');
            header.className = 'flex items-center justify-between px-4 py-3';

            const left = document.createElement('div');
            left.className = 'flex items-center gap-3 min-w-0';

            const handle = document.createElement('div');
            handle.className = 'text-admin-text-secondary cursor-move select-none';
            handle.textContent = '≡';

            const chevron = document.createElement('span');
            chevron.className = 'text-gray-400 text-xs select-none';
            chevron.textContent = '▾';

            const text = document.createElement('div');
            text.className = 'min-w-0';

            const title = document.createElement('div');
            title.className = 'text-sm font-medium text-gray-900 dark:text-admin-text-primary truncate flex items-center gap-1';
            title.textContent = labelFor(item);

            const badge = document.createElement('span');
            badge.className = 'ml-1 text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 rounded px-1.5 py-0.5';
            badge.textContent = 'dropdown';
            title.appendChild(badge);

            const meta = document.createElement('div');
            meta.className = 'text-xs text-gray-500 dark:text-admin-text-secondary truncate';
            meta.textContent = (Array.isArray(item.children) ? item.children.length : 0) + ' child links';

            text.appendChild(title);
            text.appendChild(meta);

            left.appendChild(handle);
            left.appendChild(chevron);
            left.appendChild(text);

            const right = document.createElement('div');
            right.className = 'flex items-center gap-2';

            const labelInput = document.createElement('input');
            labelInput.type = 'text';
            labelInput.className = 'w-36 rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm';
            labelInput.placeholder = 'Dropdown label';
            labelInput.value = typeof item.label === 'string' ? item.label : '';
            labelInput.addEventListener('input', function () {
                item.label = String(labelInput.value || '').trim() || 'Dropdown';
                sync();
                title.firstChild.textContent = item.label;
            });

            const addChildRow = document.createElement('div');
            addChildRow.className = 'items-center gap-1 mt-1 flex-wrap';
            addChildRow.style.display = 'none';
            addChildRow.id = 'child-row-' + index;

            const childRouteSelect = document.createElement('select');
            childRouteSelect.className = 'text-xs rounded border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500';
            ['','home','features','pricing','blog.index','roadmap','docs','api.docs.public'].forEach(function(r) {
                const opt = document.createElement('option');
                opt.value = r;
                opt.textContent = r || '— route —';
                childRouteSelect.appendChild(opt);
            });

            const childPageSelect = document.createElement('select');
            childPageSelect.className = 'text-xs rounded border-admin-border bg-white/5 text-admin-text-primary shadow-sm focus:border-primary-500 focus:ring-primary-500';
            const emptyPageOpt = document.createElement('option');
            emptyPageOpt.value = '';
            emptyPageOpt.textContent = '— page —';
            childPageSelect.appendChild(emptyPageOpt);
            Object.values(pagesById).forEach(function(p) {
                const opt = document.createElement('option');
                opt.value = String(p.id);
                opt.textContent = p.title;
                childPageSelect.appendChild(opt);
            });

            const childCustomLabel = document.createElement('input');
            childCustomLabel.type = 'text';
            childCustomLabel.placeholder = 'Custom label';
            childCustomLabel.className = 'text-xs w-28 rounded border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500';

            const childCustomUrl = document.createElement('input');
            childCustomUrl.type = 'text';
            childCustomUrl.placeholder = 'https://…';
            childCustomUrl.className = 'text-xs w-32 rounded border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500';

            const addChildConfirmBtn = document.createElement('button');
            addChildConfirmBtn.type = 'button';
            addChildConfirmBtn.className = 'text-xs px-2 py-0.5 bg-primary-600 text-white rounded hover:bg-primary-700';
            addChildConfirmBtn.textContent = 'Add';
            addChildConfirmBtn.addEventListener('click', function () {
                const route = childRouteSelect.value;
                const pageId = childPageSelect.value;
                const customUrl = String(childCustomUrl.value || '').trim();
                const customLabel = String(childCustomLabel.value || '').trim();
                if (!Array.isArray(item.children)) { item.children = []; }
                if (customUrl) {
                    item.children.push({ type: 'custom', label: customLabel || customUrl, url: customUrl });
                    childCustomUrl.value = '';
                    childCustomLabel.value = '';
                } else if (route) {
                    item.children.push({ type: 'route', route: route, label: route });
                    childRouteSelect.value = '';
                } else if (pageId) {
                    const p = pagesById[pageId] || null;
                    item.children.push({ type: 'page', page_id: Number(pageId), label: p ? p.title : '' });
                    childPageSelect.value = '';
                } else {
                    return;
                }
                sync();
                render();
            });

            addChildRow.appendChild(childRouteSelect);
            addChildRow.appendChild(childPageSelect);
            addChildRow.appendChild(childCustomLabel);
            addChildRow.appendChild(childCustomUrl);
            addChildRow.appendChild(addChildConfirmBtn);

            const toggleChildRowBtn = document.createElement('button');
            toggleChildRowBtn.type = 'button';
            toggleChildRowBtn.className = 'text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded hover:bg-gray-200 dark:hover:bg-gray-600';
            toggleChildRowBtn.textContent = '+ Link';
            toggleChildRowBtn.addEventListener('click', function () {
                addChildRow.style.display = addChildRow.style.display === 'none' ? 'flex' : 'none';
            });

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'text-sm text-red-600 hover:text-red-700';
            removeBtn.textContent = 'Remove';
            removeBtn.addEventListener('click', function () {
                items.splice(index, 1);
                sync();
                render();
            });

            right.appendChild(labelInput);
            right.appendChild(toggleChildRowBtn);
            right.appendChild(removeBtn);

            header.appendChild(left);
            header.appendChild(right);
            wrapper.appendChild(header);

            if (Array.isArray(item.children) && item.children.length > 0) {
                const childList = document.createElement('ul');
                childList.className = 'divide-y divide-admin-border border-t border-admin-border';
                item.children.forEach(function (child, ci) {
                    childList.appendChild(renderChildItem(child, item, ci));
                });
                wrapper.appendChild(childList);
            }

            const addChildSection = document.createElement('div');
            addChildSection.className = 'px-4 pb-2';
            addChildSection.appendChild(addChildRow);
            wrapper.appendChild(addChildSection);

            wrapper.addEventListener('dragstart', function (e) {
                e.dataTransfer.setData('text/plain', String(index));
            });
            wrapper.addEventListener('dragover', function (e) { e.preventDefault(); });
            wrapper.addEventListener('drop', function (e) {
                e.preventDefault();
                const fromIndex = Number(e.dataTransfer.getData('text/plain'));
                const toIndex = Number(wrapper.dataset.index);
                if (!Number.isFinite(fromIndex) || !Number.isFinite(toIndex) || fromIndex === toIndex) { return; }
                const moved = items.splice(fromIndex, 1)[0];
                items.splice(toIndex, 0, moved);
                sync();
                render();
            });

            return wrapper;
        }

        const li = document.createElement('li');
        li.className = 'flex items-center justify-between px-4 py-3';
        li.draggable = true;
        li.dataset.index = String(index);

        const left = document.createElement('div');
        left.className = 'flex items-center gap-3 min-w-0';

        const handle = document.createElement('div');
        handle.className = 'text-admin-text-secondary cursor-move select-none';
        handle.textContent = '≡';

        const text = document.createElement('div');
        text.className = 'min-w-0';

        const title = document.createElement('div');
        title.className = 'text-sm font-medium text-gray-900 dark:text-admin-text-primary truncate';
        title.textContent = labelFor(item);

        const meta = document.createElement('div');
        meta.className = 'text-xs text-gray-500 dark:text-admin-text-secondary truncate';
        if (item.type === 'page') {
            meta.textContent = 'page #' + item.page_id;
        } else if (item.type === 'route') {
            meta.textContent = 'route: ' + item.route;
        } else if (item.type === 'megamenu') {
            const mm = megaMenusById[String(item.mega_menu_id)] || null;
            meta.textContent = 'mega menu: ' + (mm ? mm.name : '#' + item.mega_menu_id);
        } else if (item.type === 'custom') {
            meta.textContent = 'custom: ' + (item.url || '');
        }

        text.appendChild(title);
        text.appendChild(meta);

        left.appendChild(handle);
        left.appendChild(text);

        const right = document.createElement('div');
        right.className = 'flex items-center gap-2';

        const labelInput = document.createElement('input');
        labelInput.type = 'text';
        labelInput.className = 'w-44 rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm';
        labelInput.placeholder = String(labelFor(item) || '');
        labelInput.value = typeof item.label === 'string' ? item.label : '';
        labelInput.addEventListener('input', function () {
            const v = String(labelInput.value || '').trim();
            if (!v) {
                delete item.label;
            } else {
                item.label = v;
            }
            sync();
            title.textContent = labelFor(item);
        });

        right.appendChild(labelInput);

        if (item.type === 'custom') {
            const urlInput = document.createElement('input');
            urlInput.type = 'text';
            urlInput.className = 'w-44 rounded-md border-admin-border bg-white/5 text-admin-text-primary placeholder:text-admin-text-secondary/70 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm';
            urlInput.placeholder = 'https://example.com';
            urlInput.value = typeof item.url === 'string' ? item.url : '';
            urlInput.addEventListener('input', function () {
                item.url = String(urlInput.value || '').trim();
                sync();
                meta.textContent = 'custom: ' + item.url;
                title.textContent = labelFor(item);
            });
            right.appendChild(urlInput);
        }

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'text-sm text-red-600 hover:text-red-700';
        removeBtn.textContent = 'Remove';
        removeBtn.addEventListener('click', function () {
            items.splice(index, 1);
            sync();
            render();
        });

        right.appendChild(removeBtn);

        li.appendChild(left);
        li.appendChild(right);

        li.addEventListener('dragstart', function (e) {
            e.dataTransfer.setData('text/plain', li.dataset.index);
        });

        li.addEventListener('dragover', function (e) {
            e.preventDefault();
        });

        li.addEventListener('drop', function (e) {
            e.preventDefault();
            const fromIndex = Number(e.dataTransfer.getData('text/plain'));
            const toIndex = Number(li.dataset.index);

            if (!Number.isFinite(fromIndex) || !Number.isFinite(toIndex) || fromIndex === toIndex) {
                return;
            }

            const moved = items.splice(fromIndex, 1)[0];
            items.splice(toIndex, 0, moved);
            sync();
            render();
        });

        return li;
    }

    function render() {
        listEl.innerHTML = '';
        items.forEach(function (item, index) {
            listEl.appendChild(renderItem(item, index));
        });
        if (emptyEl) {
            emptyEl.classList.toggle('hidden', items.length > 0);
        }
        sync();
    }

    document.getElementById('btn-add-page').addEventListener('click', function () {
        const pageId = Number(document.getElementById('add-page-id').value);
        if (!Number.isFinite(pageId) || !pageId) {
            return;
        }
        items.push({ type: 'page', page_id: pageId });
        document.getElementById('add-page-id').value = '';
        sync();
        render();
    });

    document.getElementById('btn-add-route').addEventListener('click', function () {
        const route = String(document.getElementById('add-route').value || '').trim();
        if (!route) {
            return;
        }
        items.push({ type: 'route', route: route, label: route });
        document.getElementById('add-route').value = '';
        sync();
        render();
    });

    document.getElementById('btn-add-custom').addEventListener('click', function () {
        const label = String(document.getElementById('add-custom-label').value || '').trim();
        const url = String(document.getElementById('add-custom-url').value || '').trim();
        if (!label && !url) {
            return;
        }
        items.push({ type: 'custom', label: label || url, url: url });
        document.getElementById('add-custom-label').value = '';
        document.getElementById('add-custom-url').value = '';
        sync();
        render();
    });

    document.getElementById('btn-add-dropdown').addEventListener('click', function () {
        const label = String(document.getElementById('add-dropdown-label').value || '').trim();
        if (!label) {
            return;
        }
        items.push({ type: 'dropdown', label: label, children: [] });
        document.getElementById('add-dropdown-label').value = '';
        sync();
        render();
    });

    var addMegaMenuBtn = document.getElementById('btn-add-megamenu');
    if (addMegaMenuBtn) {
        addMegaMenuBtn.addEventListener('click', function () {
            var label = String(document.getElementById('add-megamenu-label').value || '').trim();
            var megaMenuId = Number(document.getElementById('add-megamenu-id').value);
            if (!label || !megaMenuId) {
                return;
            }
            items.push({ type: 'megamenu', label: label, mega_menu_id: megaMenuId });
            document.getElementById('add-megamenu-label').value = '';
            document.getElementById('add-megamenu-id').value = '';
            sync();
            render();
        });
    }

    document.getElementById('menu-form').addEventListener('submit', function (e) {
        e.preventDefault();
        sync();
        this.submit();
    });

    render();
})();
</script>
@endpush
