import React from 'react';
import { createRoot } from 'react-dom/client';

export function createPageBuilderRuntime({ PageBuilderApp }) {
    function mount() {
        const el = document.getElementById('page-builder-react');
        if (!el) return;

        if (window.__mailpursePageBuilderMounting) return;
        window.__mailpursePageBuilderMounting = true;

        const uploadUrl = el.getAttribute('data-upload-url') || '';
        const aiUrl = el.getAttribute('data-ai-url') || '';
        const aiMessageUrl = el.getAttribute('data-ai-message-url') || '';
        const aiModelsUrl = el.getAttribute('data-ai-models-url') || '';
        const aiMessagesUrl = el.getAttribute('data-ai-messages-url') || '';
        const pageTitle = el.getAttribute('data-page-title') || '';
        const settingsUrl = el.getAttribute('data-settings-url') || '';
        const previewUrl = el.getAttribute('data-preview-url') || '';
        const renderHtmlUrl = el.getAttribute('data-render-html-url') || '';
        const testEmailUrl = el.getAttribute('data-test-email-url') || '';
        const templateName = el.getAttribute('data-template-name') || '';
        const embedMode = (el.getAttribute('data-embed') || '') === '1';
        const requestedMode = el.getAttribute('data-mode') || '';
        const mode = ['form', 'email'].includes(requestedMode) ? requestedMode : 'page';
        const layoutType = el.getAttribute('data-layout-type') || '';
        const megaMenuBuilderEnabled = (el.getAttribute('data-mega-menu-builder') || '0') === '1';
        const aiEnabled = (el.getAttribute('data-ai-enabled') || '1') === '1';
        const aiDefaultModel = el.getAttribute('data-ai-default-model') || '';
        let aiEnabledModels = [];
        try {
            aiEnabledModels = JSON.parse(el.getAttribute('data-ai-enabled-models') || '[]');
        } catch (e) {
            aiEnabledModels = [];
        }
        let deliveryServers = [];
        try {
            deliveryServers = JSON.parse(el.getAttribute('data-delivery-servers') || '[]');
        } catch (e) {
            deliveryServers = [];
        }

        const templatesApi = {
            listUrl: el.getAttribute('data-templates-list-url') || '',
            fetchUrl: el.getAttribute('data-templates-fetch-url') || '',
            productId: parseInt(el.getAttribute('data-templates-product-id') || '7', 10) || 7,
        };

        let megaMenus = [];
        try {
            megaMenus = JSON.parse(el.getAttribute('data-mega-menus') || '[]');
        } catch (e) {
            megaMenus = [];
        }

        let navMenus = [];
        try {
            navMenus = JSON.parse(el.getAttribute('data-nav-menus') || '[]');
        } catch (e) {
            navMenus = [];
        }

        let lists = [];
        try {
            lists = JSON.parse(el.getAttribute('data-lists') || '[]');
        } catch (e) {
            lists = [];
        }

        let initial = null;
        try {
            initial = JSON.parse(el.getAttribute('data-initial-builder') || 'null');
        } catch (e) {
            initial = null;
        }

        const builderInputId = el.getAttribute('data-builder-input-id') || 'builder_data';
        const builderInput = document.getElementById(builderInputId);

        const setHidden = (page) => {
            if (!builderInput) return;
            try {
                builderInput.value = JSON.stringify(page);
            } catch (e) {
                builderInput.value = '';
            }
        };

        window.mailpursePageBuilder = {
            serialize: () => {
                if (!builderInput) return null;
                try {
                    return JSON.parse(builderInput.value || 'null');
                } catch (e) {
                    return null;
                }
            },
        };

        try {
            const root = el.__mailpurseReactRoot || createRoot(el);
            el.__mailpurseReactRoot = root;
            root.render(
                React.createElement(PageBuilderApp, {
                    initialData: initial,
                    uploadUrl,
                    aiUrl,
                    aiMessageUrl,
                    aiModelsUrl,
                    aiMessagesUrl,
                    pageTitle,
                    settingsUrl,
                    previewUrl,
                    renderHtmlUrl,
                    testEmailUrl,
                    templateName,
                    deliveryServers,
                    aiEnabled,
                    aiDefaultModel,
                    aiEnabledModels,
                    embedMode,
                    mode,
                    layoutType,
                    megaMenuBuilderEnabled,
                    megaMenus,
                    navMenus,
                    lists,
                    templatesApi,
                    onChange: (page) => {
                        setHidden(page);
                    },
                })
            );
            window.__mailpursePageBuilderLastMountError = null;
        } catch (e) {
            window.__mailpursePageBuilderLastMountError = e;
        } finally {
            window.__mailpursePageBuilderMounting = false;
        }
    }

    function unmount() {
        const el = document.getElementById('page-builder-react');
        if (!el) return;
        try {
            if (el.__mailpurseReactRoot) {
                el.__mailpurseReactRoot.unmount();
                delete el.__mailpurseReactRoot;
            }
        } catch (e) {
        }
    }

    let initialized = false;

    function __initPageBuilder() {
        if (initialized) return;
        initialized = true;

        if (window.Turbo) {
            document.addEventListener('turbo:load', mount);
            document.addEventListener('turbo:before-cache', unmount);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', mount);
        } else {
            mount();
        }
    }

    return { mount, unmount, __initPageBuilder };
}
