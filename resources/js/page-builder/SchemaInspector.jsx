import React, { useState, useMemo } from 'react';
import { ControlsRenderer } from './controls/renderer.jsx';
import { getElement } from './elementRegistry.js';
import { sectionSchema } from './schemas/section.js';
import { columnSchema, gridColumnSchema } from './schemas/column.js';
import { pageSchema } from './schemas/page.js';
import { Input } from './ui/primitives.jsx';
import { ImageUrlUploadControl } from './reusable/imageUploadControl.jsx';

export function SchemaInspector({ kind, model, page, onPageChange, onSectionChange, onColumnChange, onWidgetChange, uploadUrl, megaMenus, globals, editTab, layoutType }) {
    const [errors] = useState({});

    const renderPageMeta = () => {
        return (
        <div className="space-y-4">
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Page Title</label>
                <Input value={page.meta?.title || ''} onChange={(v) => onPageChange({ meta: { ...page.meta, title: v } })} placeholder="Enter page title" />
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Description</label>
                <textarea
                    value={page.meta?.description || ''}
                    onChange={(e) => onPageChange({ meta: { ...page.meta, description: e.target.value } })}
                    placeholder="Enter meta description"
                    rows={3}
                    className="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm placeholder-gray-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-400"
                />
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Meta Keywords</label>
                <Input value={page.meta?.keywords || ''} onChange={(v) => onPageChange({ meta: { ...page.meta, keywords: v } })} placeholder="keyword1, keyword2, keyword3" />
            </div>
            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Featured Image</label>
                <ImageUrlUploadControl
                    value={page.meta?.featuredImage || ''}
                    onChange={(v) => onPageChange({ meta: { ...page.meta, featuredImage: v } })}
                    uploadUrl={uploadUrl}
                    onUploaded={(url) => onPageChange({ meta: { ...page.meta, featuredImage: url } })}
                    placeholder="Enter image URL"
                />
            </div>
        </div>
        );
    };

    const renderPage = () => {
        if (editTab === 'content') return renderPageMeta();
        const schema = pageSchema[editTab] || [];
        return <ControlsRenderer schema={schema} value={page} onChange={onPageChange} errors={errors} globals={globals} megaMenus={megaMenus} uploadUrl={uploadUrl} />;
    };

    const renderSection = () => {
        const schema = sectionSchema[editTab] || [];
        return <ControlsRenderer schema={schema} value={model} onChange={onSectionChange} errors={errors} globals={globals} megaMenus={megaMenus} uploadUrl={uploadUrl} />;
    };

    const renderColumn = () => {
        const schema = (model.kind === 'grid_column' ? gridColumnSchema : columnSchema)[editTab] || [];
        return <ControlsRenderer schema={schema} value={model.column} onChange={onColumnChange} errors={errors} globals={globals} megaMenus={megaMenus} uploadUrl={uploadUrl} />;
    };

    const renderWidget = () => {
        const element = getElement(model.widget.type);
        const schema = element?.schema?.[editTab] || [];
        return <ControlsRenderer schema={schema} value={model.widget} onChange={onWidgetChange} errors={errors} globals={globals} megaMenus={megaMenus} uploadUrl={uploadUrl} />;
    };

    if (kind === 'page') return renderPage();
    if (kind === 'section') return renderSection();
    if (kind === 'column' || kind === 'grid_column') return renderColumn();
    return renderWidget();
}
