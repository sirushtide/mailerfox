import React from 'react';
import { Input, Select, Range } from '../ui/primitives.jsx';
import { ColorInput } from '../ui/color.jsx';
import { TypographyPopover } from '../reusable/typography.jsx';
import { SpacingControls } from '../reusable/spacingControls.jsx';
import { BorderControls, FourSideLinkedControl } from '../reusable/boxControls.jsx';
import { StyleAccordionItem } from '../controls/accordion.jsx';
import { HOVER_EFFECT_OPTIONS } from '../lib/hoverEffects.js';

const isMegaMenuBuilderEnabled = () =>
    typeof window !== 'undefined' && window.__MP_MEGA_MENU_BUILDER_ENABLED === true;

export function NavMenuContentInspector({ widget, onChange, megaMenus = [], navMenus = [] }) {
    const props = widget.props || {};
    const items = Array.isArray(props.items) ? props.items : [];
    const selectedMenuId = props.nav_menu_id || '';

    function selectMenu(menuId) {
        if (menuId === '') {
            onChange({ props: { ...props, nav_menu_id: null, items: [] } });
        } else {
            const menu = navMenus.find((m) => String(m.id) === String(menuId));
            if (menu) {
                onChange({
                    props: {
                        ...props,
                        nav_menu_id: Number(menuId),
                        items: menu.items || [],
                    },
                });
            }
        }
    }

    return (
        <div className="space-y-3 px-4">
            <div className="grid grid-cols-2 gap-3">
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Alignment</label>
                    <Select
                        value={props.align || 'left'}
                        onChange={(v) => onChange({ props: { ...props, align: v } })}
                        options={[
                            { value: 'left', label: 'Left' },
                            { value: 'center', label: 'Center' },
                            { value: 'right', label: 'Right' },
                        ]}
                    />
                </div>
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Spacing (px)</label>
                    <Input
                        type="number"
                        value={String(props.spacing ?? 24)}
                        onChange={(v) => onChange({ props: { ...props, spacing: Number(v) } })}
                    />
                </div>
            </div>

            <div>
                <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Navigation Menu</label>
                <Select
                    value={String(selectedMenuId)}
                    onChange={selectMenu}
                    options={[
                        { value: '', label: '— Select a menu —' },
                        ...navMenus.map((m) => ({ value: String(m.id), label: m.name || m.key })),
                    ]}
                />
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {navMenus.length === 0
                        ? 'No menus available. Create menus from Site Settings → Menus.'
                        : 'Items are managed from Site Settings → Menus.'}
                </p>
            </div>

            {items.length > 0 && (
                <div className="space-y-1">
                    <label className="block text-xs font-medium text-gray-500 mb-1">Preview Items ({items.length})</label>
                    {items.map((item, idx) => (
                        <div key={idx} className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300 px-2 py-1 rounded border border-gray-200 dark:border-gray-700">
                            <span className="flex-1 truncate">{item.label || 'Unnamed'}</span>
                            <span className="text-gray-400">{item.type || 'link'}</span>
                        </div>
                    ))}
                </div>
            )}

            {isMegaMenuBuilderEnabled() && megaMenus.length > 0 && items.length > 0 && (
                <div className="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-300">Per-item Mega Menu override</label>
                    {items.map((item, idx) => {
                        if (item.type === 'megamenu') {
                            return (
                                <div key={idx} className="flex items-center gap-2">
                                    <span className="text-xs text-gray-500 flex-1 truncate">{item.label}</span>
                                    <div className="w-32">
                                        <Select
                                            className="!mt-0 w-full"
                                            value={String(item.mega_menu_id || '')}
                                            onChange={(v) => {
                                                const next = items.map((it, i) => (i === idx ? { ...it, mega_menu_id: v === '' ? null : Number(v) } : it));
                                                onChange({ props: { ...props, items: next } });
                                            }}
                                            options={[
                                                { value: '', label: 'Select...' },
                                                ...megaMenus.map((mm) => ({ value: String(mm.id), label: mm.name })),
                                            ]}
                                        />
                                    </div>
                                </div>
                            );
                        }
                        return null;
                    })}
                </div>
            )}
        </div>
    );
}

export function NavMenuStyleInspector({ widget, onChange }) {
    const props = widget.props || {};

    const paddingKeys = {
        linked: 'paddingLinked',
        t: 'paddingTop',
        r: 'paddingRight',
        b: 'paddingBottom',
        l: 'paddingLeft',
        legacyX: 'paddingX',
        legacyY: 'paddingY',
    };

    const marginKeys = {
        linked: 'marginLinked',
        t: 'marginTop',
        r: 'marginRight',
        b: 'marginBottom',
        l: 'marginLeft',
    };

    const gapValue = Number.isFinite(Number(props.gap)) ? Number(props.gap) : 24;

    return (
        <div>
            <StyleAccordionItem title="Typography & Colors" defaultOpen={true}>
                <div className="space-y-3 px-4">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={props.color || ''}
                                onChange={(v) => onChange({ props: { ...props, color: v } })}
                            />
                        </div>
                    </div>

                    <TypographyPopover
                        value={{
                            fontFamily: props.fontFamily || '',
                            fontSize: props.fontSize,
                            fontWeight: props.fontWeight || '',
                            align: 'left',
                            lineHeight: props.lineHeight,
                            letterSpacing: props.letterSpacing,
                            wordSpacing: props.wordSpacing,
                        }}
                        onChange={(next) =>
                            onChange({
                                props: {
                                    ...props,
                                    fontFamily: next.fontFamily,
                                    fontSize: next.fontSize === '' ? undefined : next.fontSize,
                                    fontWeight: next.fontWeight,
                                    lineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                    letterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                                    wordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                                },
                            })
                        }
                    />

                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Item Spacing</label>
                        <div className="flex items-center justify-between mb-1">
                            <span className="text-xs text-gray-500">Gap</span>
                            <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{gapValue}px</span>
                        </div>
                        <Range
                            min={0}
                            max={80}
                            step={1}
                            value={String(gapValue)}
                            onChange={(e) => onChange({ props: { ...props, gap: Number(e.target.value) } })}
                        />
                    </div>

                    <SpacingControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        paddingKeys={paddingKeys}
                        paddingFallbackValue={props.paddingTop ?? props.paddingY ?? props.paddingX}
                        marginKeys={marginKeys}
                        marginFallbackValue={props.marginTop ?? props.marginRight ?? props.marginBottom ?? props.marginLeft}
                    />

                    <FourSideLinkedControl
                        label="Border Radius"
                        value={props}
                        keys={{
                            linked: 'borderRadiusLinked',
                            t: 'borderRadiusTop',
                            r: 'borderRadiusRight',
                            b: 'borderRadiusBottom',
                            l: 'borderRadiusLeft',
                            legacy: 'borderRadius',
                        }}
                        fallbackValue={props.borderRadius ?? 0}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                    />

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={props.backgroundColor || ''}
                                onChange={(v) => onChange({ props: { ...props, backgroundColor: v } })}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Hover Effect</label>
                        <Select
                            className="!mt-0"
                            value={props.hoverEffect || ''}
                            onChange={(v) => onChange({ props: { ...props, hoverEffect: v } })}
                            options={HOVER_EFFECT_OPTIONS}
                        />
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Dropdown Container" defaultOpen={false}>
                <div className="space-y-3 px-4">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Container Width</label>
                        <div className="flex items-center justify-between mb-1">
                            <span className="text-xs text-gray-500">Width</span>
                            <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{Number(props.dropdownWidth ?? 200)}px</span>
                        </div>
                        <Range
                            min={200}
                            max={800}
                            step={1}
                            value={String(props.dropdownWidth ?? 200)}
                            onChange={(e) => onChange({ props: { ...props, dropdownWidth: Number(e.target.value) } })}
                        />
                    </div>

                    <SpacingControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        paddingKeys={{
                            linked: 'dropdownPaddingLinked',
                            t: 'dropdownPaddingTop',
                            r: 'dropdownPaddingRight',
                            b: 'dropdownPaddingBottom',
                            l: 'dropdownPaddingLeft',
                        }}
                        paddingFallbackValue={props.dropdownPaddingTop ?? 8}
                        marginKeys={{
                            linked: 'dropdownMarginLinked',
                            t: 'dropdownMarginTop',
                            r: 'dropdownMarginRight',
                            b: 'dropdownMarginBottom',
                            l: 'dropdownMarginLeft',
                        }}
                        marginFallbackValue={props.dropdownMarginTop ?? 0}
                    />

                    <BorderControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        styleKey="dropdownBorderStyle"
                        colorKey="dropdownBorderColor"
                        widthKeys={{
                            linked: 'dropdownBorderWidthLinked',
                            t: 'dropdownBorderWidthTop',
                            r: 'dropdownBorderWidthRight',
                            b: 'dropdownBorderWidthBottom',
                            l: 'dropdownBorderWidthLeft',
                        }}
                        widthFallbackValue={props.dropdownBorderWidthTop ?? 1}
                    />

                    <FourSideLinkedControl
                        label="Border Radius"
                        value={props}
                        keys={{
                            linked: 'dropdownBorderRadiusLinked',
                            t: 'dropdownBorderRadiusTop',
                            r: 'dropdownBorderRadiusRight',
                            b: 'dropdownBorderRadiusBottom',
                            l: 'dropdownBorderRadiusLeft',
                            legacy: 'dropdownBorderRadius',
                        }}
                        fallbackValue={props.dropdownBorderRadius ?? 6}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                    />

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={props.dropdownBackgroundColor || ''}
                                onChange={(v) => onChange({ props: { ...props, dropdownBackgroundColor: v } })}
                            />
                        </div>
                    </div>
                </div>
            </StyleAccordionItem>

            <StyleAccordionItem title="Dropdown Items" defaultOpen={false}>
                <div className="space-y-3 px-4">
                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Text Color</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={props.dropdownItemColor || ''}
                                onChange={(v) => onChange({ props: { ...props, dropdownItemColor: v } })}
                            />
                        </div>
                    </div>

                    <TypographyPopover
                        value={{
                            fontFamily: props.dropdownItemFontFamily || '',
                            fontSize: props.dropdownItemFontSize,
                            fontWeight: props.dropdownItemFontWeight || '',
                            align: 'left',
                            lineHeight: props.dropdownItemLineHeight,
                            letterSpacing: props.dropdownItemLetterSpacing,
                            wordSpacing: props.dropdownItemWordSpacing,
                        }}
                        onChange={(next) =>
                            onChange({
                                props: {
                                    ...props,
                                    dropdownItemFontFamily: next.fontFamily,
                                    dropdownItemFontSize: next.fontSize === '' ? undefined : next.fontSize,
                                    dropdownItemFontWeight: next.fontWeight,
                                    dropdownItemLineHeight: next.lineHeight === '' ? undefined : next.lineHeight,
                                    dropdownItemLetterSpacing: next.letterSpacing === '' ? undefined : next.letterSpacing,
                                    dropdownItemWordSpacing: next.wordSpacing === '' ? undefined : next.wordSpacing,
                                },
                            })
                        }
                    />

                    <SpacingControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        paddingKeys={{
                            linked: 'dropdownItemPaddingLinked',
                            t: 'dropdownItemPaddingTop',
                            r: 'dropdownItemPaddingRight',
                            b: 'dropdownItemPaddingBottom',
                            l: 'dropdownItemPaddingLeft',
                        }}
                        paddingFallbackValue={props.dropdownItemPaddingTop ?? 6}
                        marginKeys={{
                            linked: 'dropdownItemMarginLinked',
                            t: 'dropdownItemMarginTop',
                            r: 'dropdownItemMarginRight',
                            b: 'dropdownItemMarginBottom',
                            l: 'dropdownItemMarginLeft',
                        }}
                        marginFallbackValue={props.dropdownItemMarginTop ?? 0}
                    />

                    <BorderControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        styleKey="dropdownItemBorderStyle"
                        colorKey="dropdownItemBorderColor"
                        widthKeys={{
                            linked: 'dropdownItemBorderWidthLinked',
                            t: 'dropdownItemBorderWidthTop',
                            r: 'dropdownItemBorderWidthRight',
                            b: 'dropdownItemBorderWidthBottom',
                            l: 'dropdownItemBorderWidthLeft',
                        }}
                        widthFallbackValue={props.dropdownItemBorderWidthTop ?? 0}
                    />

                    <FourSideLinkedControl
                        label="Border Radius"
                        value={props}
                        keys={{
                            linked: 'dropdownItemBorderRadiusLinked',
                            t: 'dropdownItemBorderRadiusTop',
                            r: 'dropdownItemBorderRadiusRight',
                            b: 'dropdownItemBorderRadiusBottom',
                            l: 'dropdownItemBorderRadiusLeft',
                            legacy: 'dropdownItemBorderRadius',
                        }}
                        fallbackValue={props.dropdownItemBorderRadius ?? 0}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                    />
                </div>
            </StyleAccordionItem>

            {isMegaMenuBuilderEnabled() ? (
            <StyleAccordionItem title="Mega Menu" defaultOpen={false}>
                <div className="space-y-3 px-4">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Panel Width</label>
                        <Select
                            value={props.megaMenuWidthMode || 'full'}
                            onChange={(v) => onChange({ props: { ...props, megaMenuWidthMode: v } })}
                            options={[
                                { value: 'full', label: 'Full Width' },
                                { value: 'fixed', label: 'Fixed Width' },
                            ]}
                        />
                    </div>

                    {props.megaMenuWidthMode === 'fixed' && (
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Fixed Width</label>
                            <div className="flex items-center justify-between mb-1">
                                <span className="text-xs text-gray-500">Width</span>
                                <span className="text-xs font-medium text-gray-700 dark:text-gray-300">{Number(props.megaMenuFixedWidth ?? 800)}px</span>
                            </div>
                            <Range
                                min={400}
                                max={1400}
                                step={1}
                                value={String(props.megaMenuFixedWidth ?? 800)}
                                onChange={(e) => onChange({ props: { ...props, megaMenuFixedWidth: Number(e.target.value) } })}
                            />
                        </div>
                    )}

                    <SpacingControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        paddingKeys={{
                            linked: 'megaMenuPaddingLinked',
                            t: 'megaMenuPaddingTop',
                            r: 'megaMenuPaddingRight',
                            b: 'megaMenuPaddingBottom',
                            l: 'megaMenuPaddingLeft',
                        }}
                        paddingFallbackValue={props.megaMenuPaddingTop ?? 24}
                        marginKeys={{
                            linked: 'megaMenuMarginLinked',
                            t: 'megaMenuMarginTop',
                            r: 'megaMenuMarginRight',
                            b: 'megaMenuMarginBottom',
                            l: 'megaMenuMarginLeft',
                        }}
                        marginFallbackValue={props.megaMenuMarginTop ?? 0}
                    />

                    <BorderControls
                        value={props}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                        styleKey="megaMenuBorderStyle"
                        colorKey="megaMenuBorderColor"
                        widthKeys={{
                            linked: 'megaMenuBorderWidthLinked',
                            t: 'megaMenuBorderWidthTop',
                            r: 'megaMenuBorderWidthRight',
                            b: 'megaMenuBorderWidthBottom',
                            l: 'megaMenuBorderWidthLeft',
                        }}
                        widthFallbackValue={props.megaMenuBorderWidthTop ?? 1}
                    />

                    <FourSideLinkedControl
                        label="Border Radius"
                        value={props}
                        keys={{
                            linked: 'megaMenuBorderRadiusLinked',
                            t: 'megaMenuBorderRadiusTop',
                            r: 'megaMenuBorderRadiusRight',
                            b: 'megaMenuBorderRadiusBottom',
                            l: 'megaMenuBorderRadiusLeft',
                            legacy: 'megaMenuBorderRadius',
                        }}
                        fallbackValue={props.megaMenuBorderRadius ?? 12}
                        onChange={(nextProps) => onChange({ props: nextProps })}
                    />

                    <div className="grid grid-cols-2 gap-3 items-center">
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Background</label>
                        <div className="flex justify-end">
                            <ColorInput
                                value={props.megaMenuBackgroundColor || ''}
                                onChange={(v) => onChange({ props: { ...props, megaMenuBackgroundColor: v } })}
                            />
                        </div>
                    </div>
                </div>
            </StyleAccordionItem>
            ) : null}
        </div>
    );
}
