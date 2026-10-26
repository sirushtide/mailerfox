<?php

namespace App\Services\PageBuilder;

use App\Models\SiteLayout;
use App\Models\NavigationMenu;
use App\Models\Page;

class PageBuilderRenderer
{
    private bool $webglPresetsUsed = false;
    private bool $gradflowPresetsUsed = false;
    private array $globalColors = [];
    private bool $inPricingScope = false;
    private int $responsiveWidthSeq = 0;

    public function isBuilderData(array $data): bool
    {
        $builder = $data['builder'] ?? null;
        $version = $data['version'] ?? null;

        return is_string($builder)
            && $builder === 'mailpurse'
            && (is_int($version) || (is_numeric($version) && (int) $version === 1));
    }

    private function sectionContainsPricingTable(array $section, bool $intoGrids = true): bool
    {
        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
        foreach ($columns as $col) {
            if ($this->columnContainsPricingTable($col, $intoGrids)) {
                return true;
            }
        }
        return false;
    }

    private function columnContainsPricingTable(mixed $col, bool $intoGrids = true): bool
    {
        if (!is_array($col)) {
            return false;
        }
        $widgets = is_array($col['widgets'] ?? null) ? $col['widgets'] : [];
        foreach ($widgets as $w) {
            if ($this->widgetContainsPricingTable($w, $intoGrids)) {
                return true;
            }
        }
        return false;
    }

    private function widgetContainsPricingTable(mixed $widget, bool $intoGrids = true): bool
    {
        if (!is_array($widget)) {
            return false;
        }
        $type = is_string($widget['type'] ?? null) ? trim((string) $widget['type']) : '';
        if ($type === 'pricing_table') {
            return true;
        }
        if ($type === 'grid' && $intoGrids) {
            $props = is_array($widget['props'] ?? null) ? $widget['props'] : [];
            $columns = is_array($props['columns'] ?? null) ? $props['columns'] : [];
            foreach ($columns as $col) {
                if ($this->columnContainsPricingTable($col, $intoGrids)) {
                    return true;
                }
            }
        }
        if ($type === 'tabs') {
            $props = is_array($widget['props'] ?? null) ? $widget['props'] : [];
            $items = is_array($props['items'] ?? null) ? $props['items'] : [];
            foreach ($items as $it) {
                if (!is_array($it)) {
                    continue;
                }
                $nested = is_array($it['widgets'] ?? null) ? $it['widgets'] : [];
                foreach ($nested as $nw) {
                    if ($this->widgetContainsPricingTable($nw)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private function buildPricingLabelTypography(array $style): string
    {
        $css = '';
        $fontFamily = $this->sanitizeFontFamily($style['labelFontFamily'] ?? null);
        if ($fontFamily !== null) {
            $css .= 'font-family:' . $fontFamily . ';';
        }
        $fontSize = $this->sanitizeIntPx($style['labelFontSize'] ?? null, 8, 200);
        if ($fontSize !== null) {
            $css .= 'font-size:' . (string) $fontSize . 'px;';
        }
        $fontWeight = $this->sanitizeFontWeight($style['labelFontWeight'] ?? null);
        if ($fontWeight !== null) {
            $css .= 'font-weight:' . $fontWeight . ';';
        }
        $lineHeight = $this->cssLineHeight($style['labelLineHeight'] ?? null, '');
        if ($lineHeight !== '') {
            $css .= 'line-height:' . e($lineHeight) . ';';
        }
        $letterSpacing = $this->sanitizeFloatPx($style['labelLetterSpacing'] ?? null, -50, 200);
        if ($letterSpacing !== null) {
            $css .= 'letter-spacing:' . $letterSpacing . 'px;';
        }
        $wordSpacing = $this->sanitizeFloatPx($style['labelWordSpacing'] ?? null, -50, 400);
        if ($wordSpacing !== null) {
            $css .= 'word-spacing:' . $wordSpacing . 'px;';
        }
        return $css;
    }

    private function renderPricingToggle(array $style = []): string
    {
        $trackColor = $this->sanitizeColor($style['switcherTrackColor'] ?? null) ?? '#2563eb';
        $dotColor = $this->sanitizeColor($style['switcherDotColor'] ?? null) ?? '#ffffff';
        $labelColor = $this->sanitizeColor($style['switcherLabelColor'] ?? null) ?? '#4b5563';
        $labelActiveColor = $this->sanitizeColor($style['switcherActiveLabelColor'] ?? null) ?? '#2563eb';
        $typo = $this->buildPricingLabelTypography($style);

        $leftLabel = is_string($style['switcherLeftLabel'] ?? null) && trim((string) $style['switcherLeftLabel']) !== '' ? trim((string) $style['switcherLeftLabel']) : 'Monthly';
        $rightLabel = is_string($style['switcherRightLabel'] ?? null) && trim((string) $style['switcherRightLabel']) !== '' ? trim((string) $style['switcherRightLabel']) : 'Yearly';
        $leftSub = is_string($style['switcherLeftSubLabel'] ?? null) ? trim((string) $style['switcherLeftSubLabel']) : '';
        $rightSub = is_string($style['switcherRightSubLabel'] ?? null) ? trim((string) $style['switcherRightSubLabel']) : '';
        $subStyle = 'font-size:11px;font-weight:400;opacity:0.8;display:block;';

        $toggleStyle = 'display:flex;align-items:center;justify-content:center;gap:16px;margin-bottom:24px;';
        $toggleBtnBase = 'font-weight:600;cursor:pointer;background:transparent;border:none;padding:0;display:flex;flex-direction:column;align-items:center;gap:2px;text-align:center;' . $typo;
        $toggleTrackBase = 'position:relative;width:56px;height:28px;border-radius:9999px;background:' . $trackColor . ';border:none;padding:0;cursor:pointer;';
        $toggleDotBase = 'position:absolute;top:2px;left:2px;width:24px;height:24px;background:' . $dotColor . ';border-radius:9999px;transition:transform 0.2s;';

        $leftHtml = '<span>' . e($leftLabel) . '</span>';
        if ($leftSub !== '') {
            $leftHtml .= '<span style="' . e($subStyle) . '">' . e($leftSub) . '</span>';
        }
        $rightHtml = '<span>' . e($rightLabel) . '</span>';
        if ($rightSub !== '') {
            $rightHtml .= '<span style="' . e($subStyle) . '">' . e($rightSub) . '</span>';
        }

        return '<div class="mp-pricing__toggle" style="' . e($toggleStyle) . '">'
            . '<button type="button" @click="annual = false" :style="{ color: annual ? \'' . $labelColor . '\' : \'' . $labelActiveColor . '\' }" style="' . e($toggleBtnBase) . '">' . $leftHtml . '</button>'
            . '<button type="button" @click="annual = !annual" aria-label="Toggle yearly billing" style="' . e($toggleTrackBase) . '">'
            . '<span style="' . e($toggleDotBase) . '" :style="{ transform: annual ? \'translateX(28px)\' : \'translateX(0)\' }"></span>'
            . '</button>'
            . '<button type="button" @click="annual = true" :style="{ color: annual ? \'' . $labelActiveColor . '\' : \'' . $labelColor . '\' }" style="' . e($toggleBtnBase) . '">' . $rightHtml . '</button>'
            . '</div>';
    }

    private function renderPricingTabs(array $tabs, array $style = []): string
    {
        if (count($tabs) === 0) {
            return $this->renderPricingToggle($style);
        }

        $pillBg = $this->sanitizeColor($style['tabBackgroundColor'] ?? null) ?? '#f3f4f6';
        $pillActiveBg = $this->sanitizeColor($style['tabActiveBackgroundColor'] ?? null) ?? '#2563eb';
        $pillText = $this->sanitizeColor($style['tabTextColor'] ?? null) ?? '#374151';
        $pillActiveText = $this->sanitizeColor($style['tabActiveTextColor'] ?? null) ?? '#ffffff';
        $badgeBg = $this->sanitizeColor($style['tabBadgeBackgroundColor'] ?? null) ?? '#dbeafe';
        $badgeText = $this->sanitizeColor($style['tabBadgeTextColor'] ?? null) ?? '#1d4ed8';
        $badgeActiveBg = $this->sanitizeColor($style['tabBadgeActiveBackgroundColor'] ?? null) ?? '#ffffff33';
        $badgeActiveText = $this->sanitizeColor($style['tabBadgeActiveTextColor'] ?? null) ?? '#ffffff';
        $typo = $this->buildPricingLabelTypography($style);

        $out = '<div class="mp-pricing__tabs" style="display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:8px;margin-bottom:24px;">';
        foreach ($tabs as $idx => $tab) {
            if (!is_array($tab)) {
                continue;
            }
            $text = is_string($tab['text'] ?? null) ? trim((string) $tab['text']) : ('Tab ' . ($idx + 1));
            $badge = is_string($tab['badge'] ?? null) ? trim((string) $tab['badge']) : '';
            $setAnnual = $idx === 0 ? 'false' : 'true';
            $isActiveExpr = 'annual === ' . ($idx === 0 ? 'false' : 'true');

            $out .= '<button type="button" @click="annual = ' . $setAnnual . '" :style="{ backgroundColor: ' . $isActiveExpr . ' ? \'' . $pillActiveBg . '\' : \'' . $pillBg . '\', color: ' . $isActiveExpr . ' ? \'' . $pillActiveText . '\' : \'' . $pillText . '\' }" style="position:relative;padding:8px 16px;border-radius:9999px;font-weight:500;cursor:pointer;border:none;background:transparent;' . e($typo) . '">';
            $out .= e($text);
            if ($badge !== '') {
                $out .= '<span :style="{ backgroundColor: ' . $isActiveExpr . ' ? \'' . $badgeActiveBg . '\' : \'' . $badgeBg . '\', color: ' . $isActiveExpr . ' ? \'' . $badgeActiveText . '\' : \'' . $badgeText . '\' }" style="margin-left:6px;display:inline-flex;align-items:center;padding:2px 6px;border-radius:9999px;font-size:10px;font-weight:600;">' . e($badge) . '</span>';
            }
            $out .= '</button>';
        }
        $out .= '</div>';

        return $out;
    }

    private function renderSectionPricingHeader(array $section): string
    {
        $tabs = is_array($section['pricingTabs'] ?? null) ? $section['pricingTabs'] : [];
        $style = is_array($section['pricingStyle'] ?? null) ? $section['pricingStyle'] : [];
        $mode = is_string($section['pricingMode'] ?? null) ? trim((string) $section['pricingMode']) : '';
        if (!in_array($mode, ['switcher', 'tabs'], true)) {
            $mode = count($tabs) > 0 ? 'tabs' : 'switcher';
        }
        if ($mode === 'tabs') {
            return $this->renderPricingTabs($tabs, $style);
        }
        return $this->renderPricingToggle($style);
    }

    public function render(array $data): string
    {
        $this->webglPresetsUsed = false;
        $this->gradflowPresetsUsed = false;

        $sections = $data['sections'] ?? [];
        if (!is_array($sections)) {
            $sections = [];
        }

        $pageStyle = is_array($data['style'] ?? null) ? $data['style'] : [];

        $globals = is_array($data['globals'] ?? null) ? $data['globals'] : [];
        $globalColorsList = is_array($globals['colors'] ?? null) ? $globals['colors'] : [];
        $this->globalColors = [];
        foreach ($globalColorsList as $gc) {
            if (is_array($gc) && isset($gc['id'], $gc['value'])) {
                $this->globalColors[$gc['id']] = $gc['value'];
            }
        }

        $out = '<style>'
             . '.mp-grid{display:flex;flex-wrap:wrap;width:100%;}'
             . '.mp-richtext{font-size:1rem;line-height:1.75;}'
             . '.mp-richtext p{margin:0;}'
             . '.mp-richtext h1{color:#111827;font-weight:800;font-size:2.25em;margin-top:0;margin-bottom:0.8888889em;line-height:1.1111111;}'
             . '.mp-richtext h2{color:#111827;font-weight:700;font-size:1.5em;margin-top:2em;margin-bottom:1em;line-height:1.3333333;}'
             . '.mp-richtext h3{color:#111827;font-weight:600;font-size:1.25em;margin-top:1.6em;margin-bottom:0.6em;line-height:1.6;}'
             . '.mp-richtext h4{color:#111827;font-weight:600;margin-top:1.5em;margin-bottom:0.5em;line-height:1.5;}'
             . '.mp-richtext ul,.mp-richtext ol{margin:0 0 12px 20px;padding:0;}'
             . '.mp-richtext ul{list-style-type:disc;}.mp-richtext ol{list-style-type:decimal;}'
             . '.mp-richtext li{margin:6px 0;}'
             . '.mp-richtext a{text-decoration:underline;}'
             . '.mp-richtext strong{color:#111827;font-weight:600;}'
             . '.mp-richtext blockquote{margin:14px 0;padding-left:14px;border-left:3px solid rgba(156,163,175,0.6);color:rgba(75,85,99,1);}'
            . '.mp-col{flex:12 1 0;min-width:0;}'
            . ".mp-col[data-span-base='1']{flex:1 1 0;}"
            . ".mp-col[data-span-base='2']{flex:2 1 0;}"
            . ".mp-col[data-span-base='3']{flex:3 1 0;}"
            . ".mp-col[data-span-base='4']{flex:4 1 0;}"
            . ".mp-col[data-span-base='5']{flex:5 1 0;}"
            . ".mp-col[data-span-base='6']{flex:6 1 0;}"
            . ".mp-col[data-span-base='7']{flex:7 1 0;}"
            . ".mp-col[data-span-base='8']{flex:8 1 0;}"
            . ".mp-col[data-span-base='9']{flex:9 1 0;}"
            . ".mp-col[data-span-base='10']{flex:10 1 0;}"
            . ".mp-col[data-span-base='11']{flex:11 1 0;}"
            . ".mp-col[data-span-base='12']{flex:12 1 0;}"
            . '@media (min-width:768px){'
            . ".mp-col[data-span-md='1']{flex:1 1 0;}"
            . ".mp-col[data-span-md='2']{flex:2 1 0;}"
            . ".mp-col[data-span-md='3']{flex:3 1 0;}"
            . ".mp-col[data-span-md='4']{flex:4 1 0;}"
            . ".mp-col[data-span-md='5']{flex:5 1 0;}"
            . ".mp-col[data-span-md='6']{flex:6 1 0;}"
            . ".mp-col[data-span-md='7']{flex:7 1 0;}"
            . ".mp-col[data-span-md='8']{flex:8 1 0;}"
            . ".mp-col[data-span-md='9']{flex:9 1 0;}"
            . ".mp-col[data-span-md='10']{flex:10 1 0;}"
            . ".mp-col[data-span-md='11']{flex:11 1 0;}"
            . ".mp-col[data-span-md='12']{flex:12 1 0;}";

        $out .= '}'
            . '@media (min-width:1024px){'
            . ".mp-col[data-span-lg='1']{flex:1 1 0;}"
            . ".mp-col[data-span-lg='2']{flex:2 1 0;}"
            . ".mp-col[data-span-lg='3']{flex:3 1 0;}"
            . ".mp-col[data-span-lg='4']{flex:4 1 0;}"
            . ".mp-col[data-span-lg='5']{flex:5 1 0;}"
            . ".mp-col[data-span-lg='6']{flex:6 1 0;}"
            . ".mp-col[data-span-lg='7']{flex:7 1 0;}"
            . ".mp-col[data-span-lg='8']{flex:8 1 0;}"
            . ".mp-col[data-span-lg='9']{flex:9 1 0;}"
            . ".mp-col[data-span-lg='10']{flex:10 1 0;}"
            . ".mp-col[data-span-lg='11']{flex:11 1 0;}"
            . ".mp-col[data-span-lg='12']{flex:12 1 0;}"
            . '}'
            . '.mp-marquee{overflow:hidden;width:100%;white-space:nowrap;position:relative;--mp-marquee-fade-width:48px;--mp-marquee-fade-color:transparent;}'
            . '.mp-marquee--mask{-webkit-mask-image:linear-gradient(to right, rgba(0,0,0,0) 0%, rgba(0,0,0,1) var(--mp-marquee-fade-width), rgba(0,0,0,1) calc(100% - var(--mp-marquee-fade-width)), rgba(0,0,0,0) 100%);mask-image:linear-gradient(to right, rgba(0,0,0,0) 0%, rgba(0,0,0,1) var(--mp-marquee-fade-width), rgba(0,0,0,1) calc(100% - var(--mp-marquee-fade-width)), rgba(0,0,0,0) 100%);-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-size:100% 100%;mask-size:100% 100%;}'
            . '.mp-marquee--overlay::before,.mp-marquee--overlay::after{content:"";position:absolute;top:0;bottom:0;width:var(--mp-marquee-fade-width);z-index:1;pointer-events:none;}'
            . '.mp-marquee--overlay::before{left:0;background:linear-gradient(to right, var(--mp-marquee-fade-color) 0%, rgba(0,0,0,0) 100%);}'
            . '.mp-marquee--overlay::after{right:0;background:linear-gradient(to left, var(--mp-marquee-fade-color) 0%, rgba(0,0,0,0) 100%);}'
            . '.mp-marquee__track{display:flex;align-items:center;width:max-content;flex-wrap:nowrap;animation-name:mp-marquee;animation-timing-function:linear;animation-iteration-count:infinite;will-change:transform;}'
            . '@keyframes mp-marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}'
            . '.mp-bg-preset{position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;}'
            . '.mp-gradient-animated{background-size:400% 400% !important;animation:mp-gradient-shift 8s ease infinite;}'
            . '@keyframes mp-gradient-shift{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}'
            . '.mp-blobs-animated{background-size:200% 200%;animation:mp-blobs-float 12s ease infinite;}'
            . '@keyframes mp-blobs-float{0%{background-position:0% 0%;}50%{background-position:100% 100%;}100%{background-position:0% 0%;}}'
            . '.mp-mobile-menu{position:relative;}'
            . '.mp-mobile-menu__toggle{cursor:pointer;color:inherit;}'
            . '.mp-mobile-menu__overlay{position:fixed;inset:0;opacity:0;visibility:hidden;transition:opacity .3s ease,visibility .3s ease;z-index:9998;}'
            . '.mp-mobile-menu__overlay.is-open{opacity:1;visibility:visible;}'
            . '.mp-mobile-menu__drawer{position:fixed;top:0;bottom:0;width:min(85vw,360px);max-width:100%;z-index:9999;overflow-y:auto;visibility:hidden;transition:transform .3s ease,opacity .3s ease;}'
            . '.mp-mobile-menu__drawer[data-direction="right"]{right:0;transform:translateX(100%);}'
            . '.mp-mobile-menu__drawer[data-direction="left"]{left:0;transform:translateX(-100%);}'
            . '.mp-mobile-menu__drawer.is-open{visibility:visible;transform:translateX(0);}'
            . '.mp-mobile-menu__drawer[data-animation="fade"]{opacity:0;transform:none;}'
            . '.mp-mobile-menu__drawer[data-animation="fade"].is-open{opacity:1;}'
            . '.mp-mobile-menu__drawer[data-animation="scale"]{opacity:0;transform:scale(.96);}'
            . '.mp-mobile-menu__drawer[data-animation="scale"].is-open{opacity:1;transform:scale(1);}'
            . '.mp-mobile-menu__drawer[data-animation="none"]{transition:none;}'
            . 'body.mp-mobile-menu-open{overflow:hidden;}'
            . $this->buildCustomMotionKeyframesCss($sections)
            . '</style>';

        // Build page-level styles for background wrapper
        $mainStyle = $this->buildPageStyle($pageStyle);
        $stickyScript = '';

        $stickyConfig = null;
        if (($pageStyle['headerSticky'] ?? null) === true) {
            $stickyHeight = $this->intOrNull($pageStyle['stickyHeight'] ?? null);
            $stickyWidth = $this->intOrNull($pageStyle['stickyWidth'] ?? null);
            $stickyMarginTop = $this->intOrNull($pageStyle['stickyMarginTop'] ?? null);
            $stickyMarginRight = $this->intOrNull($pageStyle['stickyMarginRight'] ?? null);
            $stickyMarginBottom = $this->intOrNull($pageStyle['stickyMarginBottom'] ?? null);
            $stickyMarginLeft = $this->intOrNull($pageStyle['stickyMarginLeft'] ?? null);

            // Build a unique class for this header instance
            $stickyClass = 'mp-header-sticky-' . substr(md5(uniqid('', true)), 0, 8);
            $stickyWidthValue = $stickyWidth !== null && $stickyWidth > 0 ? $stickyWidth . 'px' : '100%';
            $stickyMinHeightValue = $stickyHeight !== null && $stickyHeight > 0 ? $stickyHeight . 'px' : '';

            $stickyBgType = is_string($pageStyle['stickyBackgroundType'] ?? null) ? trim((string) $pageStyle['stickyBackgroundType']) : '';
            $stickyBackgroundColor = '';
            $stickyBackgroundImage = '';
            if ($stickyBgType === 'gradient') {
                $from = is_string($pageStyle['stickyGradientFrom'] ?? null) ? trim((string) $pageStyle['stickyGradientFrom']) : '#111827';
                $to = is_string($pageStyle['stickyGradientTo'] ?? null) ? trim((string) $pageStyle['stickyGradientTo']) : '#3b82f6';
                $angle = $this->intOrNull($pageStyle['stickyGradientAngle'] ?? null) ?? 135;
                $gType = is_string($pageStyle['stickyGradientType'] ?? null) ? trim((string) $pageStyle['stickyGradientType']) : 'linear';
                $gType = in_array($gType, ['linear', 'radial'], true) ? $gType : 'linear';
                if ($gType === 'radial') {
                    $pos = is_string($pageStyle['stickyGradientPosition'] ?? null) ? trim((string) $pageStyle['stickyGradientPosition']) : 'top center';
                    $stickyBackgroundImage = 'radial-gradient(at ' . $pos . ', ' . $from . ' 0%, ' . $to . ' 100%)';
                } else {
                    $stickyBackgroundImage = 'linear-gradient(' . $angle . 'deg, ' . $from . ' 0%, ' . $to . ' 100%)';
                }
            }

            $stickyBgColor = is_string($pageStyle['stickyBackgroundColor'] ?? null) ? trim((string) $pageStyle['stickyBackgroundColor']) : '';
            if ($stickyBgColor !== '') {
                $stickyBackgroundColor = $stickyBgColor;
            }

            $stickyBackdropBlur = $this->intOrNull($pageStyle['stickyBackdropBlur'] ?? null);
            $stickyBackdropBlurCss = ($stickyBackdropBlur !== null && $stickyBackdropBlur >= 0) ? min(40, $stickyBackdropBlur) . 'px' : '';

            $stickyBorderStyle = is_string($pageStyle['stickyBorderStyle'] ?? null) ? trim((string) $pageStyle['stickyBorderStyle']) : '';
            $stickyBorderColor = is_string($pageStyle['stickyBorderColor'] ?? null) ? trim((string) $pageStyle['stickyBorderColor']) : '';
            $stickyBorderWidth = $this->intOrNull($pageStyle['stickyBorderWidthBottom'] ?? null) ?? $this->intOrNull($pageStyle['stickyBorderWidthTop'] ?? null) ?? 0;
            $stickyBorderBottom = ($stickyBorderStyle !== '' && $stickyBorderColor !== '' && $stickyBorderWidth > 0)
                ? $stickyBorderWidth . 'px ' . $stickyBorderStyle . ' ' . $stickyBorderColor
                : '';

            // Full sticky border (all sides)
            $stickyBorderTopWidth = $this->intOrNull($pageStyle['stickyBorderWidthTop'] ?? null) ?? 0;
            $stickyBorderRightWidth = $this->intOrNull($pageStyle['stickyBorderWidthRight'] ?? null) ?? 0;
            $stickyBorderBottomWidth = $this->intOrNull($pageStyle['stickyBorderWidthBottom'] ?? null) ?? 0;
            $stickyBorderLeftWidth = $this->intOrNull($pageStyle['stickyBorderWidthLeft'] ?? null) ?? 0;
            $stickyBorderTop = ($stickyBorderStyle !== '' && $stickyBorderColor !== '' && $stickyBorderTopWidth > 0)
                ? $stickyBorderTopWidth . 'px ' . $stickyBorderStyle . ' ' . $stickyBorderColor : '';
            $stickyBorderRight = ($stickyBorderStyle !== '' && $stickyBorderColor !== '' && $stickyBorderRightWidth > 0)
                ? $stickyBorderRightWidth . 'px ' . $stickyBorderStyle . ' ' . $stickyBorderColor : '';
            $stickyBorderLeft = ($stickyBorderStyle !== '' && $stickyBorderColor !== '' && $stickyBorderLeftWidth > 0)
                ? $stickyBorderLeftWidth . 'px ' . $stickyBorderStyle . ' ' . $stickyBorderColor : '';

            // Sticky border radius
            $stickyBorderRadiusTop = $this->intOrNull($pageStyle['stickyBorderRadiusTop'] ?? null);
            $stickyBorderRadiusRight = $this->intOrNull($pageStyle['stickyBorderRadiusRight'] ?? null);
            $stickyBorderRadiusBottom = $this->intOrNull($pageStyle['stickyBorderRadiusBottom'] ?? null);
            $stickyBorderRadiusLeft = $this->intOrNull($pageStyle['stickyBorderRadiusLeft'] ?? null);
            $stickyBorderRadius = '';
            if ($stickyBorderRadiusTop !== null || $stickyBorderRadiusRight !== null || $stickyBorderRadiusBottom !== null || $stickyBorderRadiusLeft !== null) {
                $t = $stickyBorderRadiusTop ?? 0;
                $r = $stickyBorderRadiusRight ?? 0;
                $b = $stickyBorderRadiusBottom ?? 0;
                $l = $stickyBorderRadiusLeft ?? 0;
                $stickyBorderRadius = $t . 'px ' . $r . 'px ' . $b . 'px ' . $l . 'px';
            }

            // Sticky text and link colors
            $stickyTextColor = is_string($pageStyle['stickyTextColor'] ?? null) ? trim((string) $pageStyle['stickyTextColor']) : '';
            $stickyLinkColor = is_string($pageStyle['stickyLinkColor'] ?? null) ? trim((string) $pageStyle['stickyLinkColor']) : '';

            // The sticky styles must be applied to the same element that carries
            // the section background (mp-section__inner for boxed, <section> for
            // full-width). Find the header section, preferring a section that
            // already declares itself sticky, otherwise the first section.
            $stickySectionIndex = null;
            foreach (array_values($sections) as $idx => $section) {
                if (is_array($section) && (($section['style']['headerSticky'] ?? null) === true)) {
                    $stickySectionIndex = $idx;
                    break;
                }
            }
            if ($stickySectionIndex === null && !empty($sections)) {
                $stickySectionIndex = 0;
            }

            if ($stickySectionIndex !== null) {
                $stickyConfig = [
                    'sectionIndex' => $stickySectionIndex,
                    'class' => $stickyClass,
                    'width' => $stickyWidthValue,
                    'minHeight' => $stickyMinHeightValue,
                    'backgroundColor' => $stickyBackgroundColor,
                    'backgroundImage' => $stickyBackgroundImage,
                    'backdropBlur' => $stickyBackdropBlurCss,
                    'borderBottom' => $stickyBorderBottom,
                    'borderTop' => $stickyBorderTop,
                    'borderRight' => $stickyBorderRight,
                    'borderLeft' => $stickyBorderLeft,
                    'borderRadius' => $stickyBorderRadius,
                    'textColor' => $stickyTextColor,
                    'linkColor' => $stickyLinkColor,
                    'marginTop' => $stickyMarginTop !== null ? $stickyMarginTop . 'px' : '',
                    'marginRight' => $stickyMarginRight !== null ? $stickyMarginRight . 'px' : '',
                    'marginBottom' => $stickyMarginBottom !== null ? $stickyMarginBottom . 'px' : '',
                    'marginLeft' => $stickyMarginLeft !== null ? $stickyMarginLeft . 'px' : '',
                ];

                $stickyScript = '<style>'
                    . '.' . $stickyClass . '{display:flex !important;flex-direction:column !important;justify-content:center !important;transition:height 0.4s ease,max-width 0.4s ease,background-color 0.4s ease,background-image 0.4s ease,border 0.4s ease,border-radius 0.4s ease,color 0.4s ease;}'
                    . '.' . $stickyClass . ' > *{flex:0 0 auto !important;}'
                    . '.' . $stickyClass . ' a{color:var(--mp-sticky-link-color,inherit);}'
                    . '</style>'
                    . '<script>'
                    . '(function(){'
                    . 'var el=document.querySelector(".' . $stickyClass . '");'
                    . 'if(!el)return;'
                    . 'var stickyWidth=' . json_encode($stickyWidthValue) . ';'
                    . 'var stickyHeight=' . json_encode($stickyMinHeightValue) . ';'
                    . 'var stickyBackgroundColor=' . json_encode($stickyBackgroundColor) . ';'
                    . 'var stickyBackgroundImage=' . json_encode($stickyBackgroundImage) . ';'
                    . 'var stickyBackdropBlur=' . json_encode($stickyBackdropBlurCss) . ';'
                    . 'var stickyBorderBottom=' . json_encode($stickyBorderBottom) . ';'
                    . 'var stickyBorderTop=' . json_encode($stickyBorderTop) . ';'
                    . 'var stickyBorderRight=' . json_encode($stickyBorderRight) . ';'
                    . 'var stickyBorderLeft=' . json_encode($stickyBorderLeft) . ';'
                    . 'var stickyBorderRadius=' . json_encode($stickyBorderRadius) . ';'
                    . 'var stickyTextColor=' . json_encode($stickyTextColor) . ';'
                    . 'var stickyLinkColor=' . json_encode($stickyLinkColor) . ';'
                    . 'var stickyMarginTop=' . json_encode($stickyConfig['marginTop']) . ';'
                    . 'var stickyMarginRight=' . json_encode($stickyConfig['marginRight']) . ';'
                    . 'var stickyMarginBottom=' . json_encode($stickyConfig['marginBottom']) . ';'
                    . 'var stickyMarginLeft=' . json_encode($stickyConfig['marginLeft']) . ';'
                    . 'var baseMaxWidth=el.style.maxWidth||"";'
                    . 'var baseHeight=el.style.height||"";'
                    . 'var baseBackgroundColor=el.style.backgroundColor||"";'
                    . 'var baseBackgroundImage=el.style.backgroundImage||"";'
                    . 'var baseBackdropFilter=el.style.backdropFilter||"";'
                    . 'var baseWebkitBackdropFilter=el.style.webkitBackdropFilter||"";'
                    . 'var baseBorderBottom=el.style.borderBottom||"";'
                    . 'var baseBorderTop=el.style.borderTop||"";'
                    . 'var baseBorderRight=el.style.borderRight||"";'
                    . 'var baseBorderLeft=el.style.borderLeft||"";'
                    . 'var baseBorderRadius=el.style.borderRadius||"";'
                    . 'var baseColor=el.style.color||"";'
                    . 'var baseMarginTop=el.style.marginTop||"";'
                    . 'var baseMarginRight=el.style.marginRight||"";'
                    . 'var baseMarginBottom=el.style.marginBottom||"";'
                    . 'var baseMarginLeft=el.style.marginLeft||"";'
                    . 'var baseTop=el.style.top||"";'
                    . 'var baseWidth=el.style.width||"";'
                    . 'function applySticky(should){'
                    . 'if(should){'
                    . 'el.style.maxWidth=stickyWidth;'
                    . 'el.style.height=stickyHeight;'
                    . 'if(stickyBackgroundColor){el.style.backgroundColor=stickyBackgroundColor;el.style.backgroundImage="none";}'
                    . 'else if(stickyBackgroundImage){el.style.backgroundImage=stickyBackgroundImage;el.style.backgroundColor="";}'
                    . 'if(stickyBackdropBlur){el.style.backdropFilter="blur("+stickyBackdropBlur+")";el.style.webkitBackdropFilter="blur("+stickyBackdropBlur+")";}'
                    . 'el.style.borderBottom=stickyBorderBottom;'
                    . 'el.style.borderTop=stickyBorderTop;'
                    . 'el.style.borderRight=stickyBorderRight;'
                    . 'el.style.borderLeft=stickyBorderLeft;'
                    . 'el.style.borderRadius=stickyBorderRadius;'
                    . 'if(stickyTextColor){el.style.color=stickyTextColor;}'
                    . 'if(stickyLinkColor){el.style.setProperty("--mp-sticky-link-color",stickyLinkColor);}'
                    . 'if(stickyMarginTop){el.style.top=stickyMarginTop;}'
                    . 'if(stickyMarginRight){el.style.marginRight=stickyMarginRight;}'
                    . 'if(stickyMarginBottom){el.style.marginBottom=stickyMarginBottom;}'
                    . 'if(stickyMarginLeft){el.style.marginLeft=stickyMarginLeft;}'
                    . 'if(stickyMarginLeft||stickyMarginRight){el.style.width="auto";}'
                    . '}else{'
                    . 'el.style.maxWidth=baseMaxWidth;'
                    . 'el.style.height=baseHeight;'
                    . 'el.style.backgroundColor=baseBackgroundColor;'
                    . 'el.style.backgroundImage=baseBackgroundImage;'
                    . 'el.style.backdropFilter=baseBackdropFilter;'
                    . 'el.style.webkitBackdropFilter=baseWebkitBackdropFilter;'
                    . 'el.style.borderBottom=baseBorderBottom;'
                    . 'el.style.borderTop=baseBorderTop;'
                    . 'el.style.borderRight=baseBorderRight;'
                    . 'el.style.borderLeft=baseBorderLeft;'
                    . 'el.style.borderRadius=baseBorderRadius;'
                    . 'el.style.color=baseColor;'
                    . 'el.style.marginTop=baseMarginTop;'
                    . 'el.style.marginRight=baseMarginRight;'
                    . 'el.style.marginBottom=baseMarginBottom;'
                    . 'el.style.marginLeft=baseMarginLeft;'
                    . 'el.style.top=baseTop;'
                    . 'el.style.width=baseWidth;'
                    . 'el.style.removeProperty("--mp-sticky-link-color");'
                    . '}'
                    . '}'
                    . 'function onScroll(){'
                    . 'applySticky(window.scrollY>10);'
                    . '}'
                    . 'window.addEventListener("scroll",onScroll,{passive:true});'
                    . 'onScroll();'
                    . '})();'
                    . '</script>';
            }
        }

        $bgWrapper = '<div' . ($mainStyle !== '' ? ' style="' . e($mainStyle) . '"' : '') . '>';

        $out .= $bgWrapper;

        $contentWrapperStyle = $this->buildPageContentStyle($pageStyle);
        $out .= '<div' . ($contentWrapperStyle !== '' ? ' style="' . e($contentWrapperStyle) . '"' : '') . '>';

        foreach (array_values($sections) as $sectionIndex => $section) {
            if (!is_array($section)) {
                continue;
            }

            $out .= $this->renderSection($section, $sectionIndex, $stickyConfig);
        }

        $out .= '</div>';
        $out .= '</div>';

        if ($stickyScript !== '') {
            $out .= $stickyScript;
        }

        if ($this->webglPresetsUsed) {
            $out .= $this->webglRuntimeScript();
        }

        if ($this->gradflowPresetsUsed) {
            $out .= '<script src="' . e(asset('gradflow-runtime.js?v=2')) . '"></script>';
        }

        return $out;
    }

    private function buildPageStyle(array $style): string
    {
        $out = 'width:100%;';
        
        $marginTop = $this->intOrNull($style['marginTop'] ?? null);
        $marginRight = $this->intOrNull($style['marginRight'] ?? null);
        $marginBottom = $this->intOrNull($style['marginBottom'] ?? null);
        $marginLeft = $this->intOrNull($style['marginLeft'] ?? null);
        
        $paddingTop = $this->intOrNull($style['paddingTop'] ?? null);
        $paddingRight = $this->intOrNull($style['paddingRight'] ?? null);
        $paddingBottom = $this->intOrNull($style['paddingBottom'] ?? null);
        $paddingLeft = $this->intOrNull($style['paddingLeft'] ?? null);
        
        if ($marginTop !== null) {
            $out .= 'margin-top:' . $marginTop . 'px;';
        }
        if ($marginRight !== null) {
            $out .= 'margin-right:' . $marginRight . 'px;';
        }
        if ($marginBottom !== null) {
            $out .= 'margin-bottom:' . $marginBottom . 'px;';
        }
        if ($marginLeft !== null) {
            $out .= 'margin-left:' . $marginLeft . 'px;';
        }
        
        if ($paddingTop !== null) {
            $out .= 'padding-top:' . $paddingTop . 'px;';
        }
        if ($paddingRight !== null) {
            $out .= 'padding-right:' . $paddingRight . 'px;';
        }
        if ($paddingBottom !== null) {
            $out .= 'padding-bottom:' . $paddingBottom . 'px;';
        }
        if ($paddingLeft !== null) {
            $out .= 'padding-left:' . $paddingLeft . 'px;';
        }
        
        // Background styles
        $bgType = is_string($style['backgroundType'] ?? null) ? trim((string) $style['backgroundType']) : '';
        if ($bgType !== '' && !in_array($bgType, ['color', 'gradient', 'image'], true)) {
            $bgType = '';
        }
        
        if ($bgType === 'gradient') {
            $from = is_string($style['gradientFrom'] ?? null) && trim((string) $style['gradientFrom']) !== '' 
                ? (string) $style['gradientFrom'] 
                : '#111827';
            $to = is_string($style['gradientTo'] ?? null) && trim((string) $style['gradientTo']) !== '' 
                ? (string) $style['gradientTo'] 
                : '#3b82f6';
            $angle = $this->intOrNull($style['gradientAngle'] ?? null) ?? 135;
            if ($angle < 0) {
                $angle = 0;
            }
            if ($angle > 360) {
                $angle = 360;
            }

            $gType = is_string($style['gradientType'] ?? null) ? trim((string) $style['gradientType']) : 'linear';
            $gType = in_array($gType, ['linear', 'radial'], true) ? $gType : 'linear';
            if ($gType === 'radial') {
                $position = is_string($style['gradientPosition'] ?? null) ? trim((string) $style['gradientPosition']) : '';
                $allowedPositions = [
                    'top left',
                    'top center',
                    'top right',
                    'center left',
                    'center center',
                    'center right',
                    'bottom left',
                    'bottom center',
                    'bottom right',
                ];
                if (!in_array($position, $allowedPositions, true)) {
                    $position = 'top center';
                }
                $out .= 'background-image:radial-gradient(at ' . $position . ', ' . $from . ' 0%, ' . $to . ' 100%);';
            } else {
                $out .= 'background-image:linear-gradient(' . $angle . 'deg, ' . $from . ' 0%, ' . $to . ' 100%);';
            }
        } elseif ($bgType === 'image') {
            $bgImage = is_array($style['backgroundImage'] ?? null) ? $style['backgroundImage'] : [];
            $url = is_string($bgImage['url'] ?? null) ? trim((string) $bgImage['url']) : '';
            if ($url !== '') {
                $out .= 'background-image:url(' . $url . ');';
                $size = is_string($style['backgroundSize'] ?? null) ? trim((string) $style['backgroundSize']) : 'cover';
                if ($size === '' || !preg_match('/^(cover|contain|auto|\d+(?:\.\d+)?(px|%)(?:\s+\d+(?:\.\d+)?(px|%))?)$/', $size)) {
                    $size = 'cover';
                }
                $position = is_string($style['backgroundPosition'] ?? null) ? trim((string) $style['backgroundPosition']) : 'center';
                $allowedPositions = [
                    'center',
                    'top',
                    'bottom',
                    'left',
                    'right',
                    'top left',
                    'top right',
                    'bottom left',
                    'bottom right',
                    'top center',
                    'center left',
                    'center center',
                    'center right',
                    'bottom center',
                ];
                if (!in_array($position, $allowedPositions, true)) {
                    $position = 'center';
                }
                $repeat = is_string($style['backgroundRepeat'] ?? null) ? trim((string) $style['backgroundRepeat']) : 'no-repeat';
                if (!in_array($repeat, ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], true)) {
                    $repeat = 'no-repeat';
                }

                $out .= 'background-size:' . $size . ';';
                $out .= 'background-position:' . $position . ';';
                $out .= 'background-repeat:' . $repeat . ';';
            }
        } elseif ($bgType === 'color') {
            $color = is_string($style['backgroundColor'] ?? null) ? trim((string) $style['backgroundColor']) : '';
            if ($color !== '') {
                $out .= 'background-color:' . $color . ';';
            }
        }

        $headerHeight = $this->intOrNull($style['headerHeight'] ?? null);
        if ($headerHeight !== null && $headerHeight > 0) {
            $out .= 'height:' . $headerHeight . 'px;';
            $out .= 'display:flex;align-items:center;overflow:visible;';
        }

        $headerSticky = ($style['headerSticky'] ?? null) === true;
        if ($headerSticky) {
            $out .= 'position:sticky;top:0;z-index:50;';
            $out .= 'margin-left:auto;margin-right:auto;';
            $out .= 'max-width:100%;';
            $out .= 'transition:height 0.4s ease, width 0.4s ease, max-width 0.4s ease, background-color 0.4s ease, background-image 0.4s ease, border-bottom 0.4s ease, color 0.4s ease;';
        }

        return $out;
    }

    private function buildPageContentStyle(array $style): string
    {
        $out = 'position:relative;';

        $maxWidthType = is_string($style['pageMaxWidthType'] ?? null) ? trim((string) $style['pageMaxWidthType']) : 'full';
        if ($maxWidthType === 'boxed') {
            $boxedWidth = is_numeric($style['pageBoxedWidth'] ?? null) ? (float) $style['pageBoxedWidth'] : 1152;
            $out .= 'max-width:' . (string) $boxedWidth . 'px;';
            $out .= 'margin-left:auto;margin-right:auto;';
        }

        $headerHeight = $this->intOrNull($style['headerHeight'] ?? null);
        if ($headerHeight !== null && $headerHeight > 0) {
            $out .= 'width:100%;';
        }

        return $out;
    }

    private function renderSection(array $section, int $sectionIndex = 0, ?array $stickyHeaderConfig = null): string
    {
        $style = is_array($section['style'] ?? null) ? $section['style'] : [];
        $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];

        $hasPricingTable = $this->sectionContainsPricingTable($section);
        $hasStandalonePricingTable = $this->sectionContainsPricingTable($section, false);
        $this->inPricingScope = $hasPricingTable;

        $adv = $this->normalizeAdvanced(is_array($section['advanced'] ?? null) ? $section['advanced'] : []);

        $bgVideoHtml = $this->renderBackgroundVideo($style);
        $gridOverlayHtml = $this->renderGridOverlay($style);
        $presetOverlayHtml = $this->renderSectionBackgroundPreset($style, $section['id'] ?? '');
        $hasPresetOverlay = $presetOverlayHtml !== '';

        $stackedSticky = ($style['stackedSticky'] ?? null) === true;
        $stackedZIndex = is_numeric($style['stackedZIndex'] ?? null) ? max(0, (int) round((float) $style['stackedZIndex'])) : 0;
        if ($stackedSticky && $stackedZIndex === 0) {
            $stackedZIndex = $sectionIndex + 1;
        }

        $paddingX = $this->intOrNull($style['paddingX'] ?? null) ?? 0;
        $paddingY = $this->intOrNull($style['paddingY'] ?? null) ?? 48;
        $gap = $this->intOrNull($style['gap'] ?? null);
        $gapX = $this->intOrNull($style['gapX'] ?? null);
        $gapY = $this->intOrNull($style['gapY'] ?? null);

        $direction = is_string($style['itemsDirection'] ?? null) ? trim((string) $style['itemsDirection']) : 'row';
        if (!in_array($direction, ['row', 'column', 'row-reverse', 'column-reverse'], true)) {
            $direction = 'row';
        }

        $justify = is_string($style['itemsJustify'] ?? null) ? trim((string) $style['itemsJustify']) : 'flex-start';
        if (!in_array($justify, ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], true)) {
            $justify = 'flex-start';
        }

        $align = is_string($style['itemsAlign'] ?? null) ? trim((string) $style['itemsAlign']) : 'stretch';
        if (!in_array($align, ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'], true)) {
            $align = 'stretch';
        }
        $maxWidthType = is_string($style['maxWidthType'] ?? null) ? trim((string) $style['maxWidthType']) : '';
        if ($maxWidthType === '') {
            $maxWidthType = (is_string($style['maxWidth'] ?? null) && trim((string) $style['maxWidth']) === 'full') ? 'full' : 'boxed';
        }
        $maxWidthType = in_array($maxWidthType, ['full', 'boxed'], true) ? $maxWidthType : 'boxed';

        $isStickyTarget = $stickyHeaderConfig !== null && ($stickyHeaderConfig['sectionIndex'] ?? null) === $sectionIndex;
        $stickyTargetClass = $isStickyTarget ? ($stickyHeaderConfig['class'] ?? '') : '';
        $stickyOnFullWidth = $isStickyTarget && $maxWidthType === 'full';
        $stickyOnInner = $isStickyTarget && $maxWidthType !== 'full';

        $shellClasses = trim('mp-section ' . ($adv['class'] ?? '') . ($stackedSticky ? ' mp-section-stacked' : '') . ($stickyOnFullWidth && $stickyTargetClass !== '' ? ' ' . $stickyTargetClass : ''));

        $gsapEnabled = ($style['gsapEnabled'] ?? null) === true;
        $gsapStagger = is_numeric($style['gsapStagger'] ?? null) ? (float) $style['gsapStagger'] : 0;
        if ($gsapStagger < 0) {
            $gsapStagger = 0;
        }
        if ($gsapStagger > 2) {
            $gsapStagger = 2;
        }
        if ($gsapEnabled) {
            $preset = is_string($style['gsapPreset'] ?? null) ? trim((string) $style['gsapPreset']) : '';
            $allowed = ['fade-up', 'fade-in', 'scale-in', 'slide-left', 'slide-right', 'blurred-in', 'from-x', 'from-y'];
            $preset = in_array($preset, $allowed, true) ? $preset : 'fade-up';
            $shellClasses = trim($shellClasses . ' gsap-' . $preset);
            if ($gsapStagger > 0) {
                $shellClasses = trim($shellClasses . ' gsap-stagger');
            }
        }
        $innerClasses = 'mp-section__inner' . ($stickyOnInner && $stickyTargetClass !== '' ? ' ' . $stickyTargetClass : '');

        $sectionStyle = '';
        if (($adv['position'] ?? '') !== '') {
            $sectionStyle .= 'position:' . $adv['position'] . ';';
        }
        if (($adv['display'] ?? '') !== '') {
            $sectionStyle .= 'display:' . $adv['display'] . ';';
        }
        if (($adv['textAlign'] ?? '') !== '') {
            $sectionStyle .= 'text-align:' . $adv['textAlign'] . ';';
        }
        if (($adv['overflowX'] ?? '') !== '') {
            $sectionStyle .= 'overflow-x:' . $adv['overflowX'] . ';';
        }
        if (($adv['overflowY'] ?? '') !== '') {
            $sectionStyle .= 'overflow-y:' . $adv['overflowY'] . ';';
        }
        if (($adv['alignItems'] ?? '') !== '') {
            $sectionStyle .= 'align-items:' . $adv['alignItems'] . ';';
        }
        if (($adv['justifyContent'] ?? '') !== '') {
            $sectionStyle .= 'justify-content:' . $adv['justifyContent'] . ';';
        }
        if (($adv['zIndex'] ?? null) !== null) {
            $sectionStyle .= 'z-index:' . (string) $adv['zIndex'] . ';';
        }
        if (($adv['position'] ?? '') === 'absolute') {
            if (($adv['absXOffset'] ?? null) !== null) {
                $sectionStyle .= (($adv['absXSide'] ?? 'left') === 'right' ? 'right:' : 'left:') . (string) $adv['absXOffset'] . 'px;';
            }
            if (($adv['absYOffset'] ?? null) !== null) {
                $sectionStyle .= (($adv['absYSide'] ?? 'top') === 'bottom' ? 'bottom:' : 'top:') . (string) $adv['absYOffset'] . 'px;';
            }
        }
        $paddingTop = $this->intOrNull($style['paddingTop'] ?? null);
        $paddingRight = $this->intOrNull($style['paddingRight'] ?? null);
        $paddingBottom = $this->intOrNull($style['paddingBottom'] ?? null);
        $paddingLeft = $this->intOrNull($style['paddingLeft'] ?? null);

        $marginTop = $this->intOrNull($style['marginTop'] ?? null);
        $marginRight = $this->intOrNull($style['marginRight'] ?? null);
        $marginBottom = $this->intOrNull($style['marginBottom'] ?? null);
        $marginLeft = $this->intOrNull($style['marginLeft'] ?? null);
        if ($marginTop !== null) {
            $sectionStyle .= 'margin-top:' . $marginTop . 'px;';
        }
        if ($marginRight !== null) {
            $sectionStyle .= 'margin-right:' . $marginRight . 'px;';
        }
        if ($marginBottom !== null) {
            $sectionStyle .= 'margin-bottom:' . $marginBottom . 'px;';
        }
        if ($marginLeft !== null) {
            $sectionStyle .= 'margin-left:' . $marginLeft . 'px;';
        }

        if ($maxWidthType === 'boxed') {
            $boxedWidth = $this->cssLength($style['boxedWidth'] ?? null, (
                (is_string($style['maxWidth'] ?? null) && trim((string) $style['maxWidth']) === '7xl') ? '1280px' : '1152px'
            ));
            $sectionStyle .= 'width:100%;max-width:' . $boxedWidth . ';';
            if (($marginLeft === null || $marginLeft === 0) && ($marginRight === null || $marginRight === 0)) {
                $sectionStyle .= 'margin-left:auto;margin-right:auto;';
            }
            if ($hasPresetOverlay) {
                $sectionStyle .= $this->buildSectionStyle($style);
            }
        } elseif ($maxWidthType === 'full') {
            $sectionStyle .= 'width:100%;';
            // Apply background to outer section for full-width so it spans full viewport.
            $sectionStyle .= $this->buildSectionStyle($style);
        }

        $overflowX = is_string($style['overflowX'] ?? null) ? trim((string) $style['overflowX']) : '';
        $overflowY = is_string($style['overflowY'] ?? null) ? trim((string) $style['overflowY']) : '';
        $allowedOverflow = ['visible', 'hidden', 'scroll', 'auto', 'clip'];
        if ($overflowX !== '' && in_array($overflowX, $allowedOverflow, true)) {
            $sectionStyle .= 'overflow-x:' . $overflowX . ';';
        }
        if ($overflowY !== '' && in_array($overflowY, $allowedOverflow, true)) {
            $sectionStyle .= 'overflow-y:' . $overflowY . ';';
        }

        $height = $this->cssLength($style['height'] ?? null, '');
        $isZeroHeight = $height !== '' && preg_match('/^0(?:\.0+)?(px|em|rem|%|vw|vh)?$/i', $height) === 1;
        if ($height !== '' && $height !== 'auto' && !$isZeroHeight) {
            $sectionStyle .= 'height:' . $height . ';display:flex;flex-direction:column;';
        }

        $minHeight = $this->intOrNull($style['minHeight'] ?? null);
        if ($minHeight !== null && $minHeight > 0) {
            $sectionStyle .= 'min-height:' . $minHeight . 'px;';
        }

        if ($stackedSticky) {
            $sectionStyle .= 'position:sticky;top:0;min-height:' . (($height !== '' && $height !== 'auto') ? $height : '100vh') . ';z-index:' . (string) $stackedZIndex . ';overflow:visible;margin-top:0;margin-bottom:0;';
        }

        $borderStyle = is_string($style['borderStyle'] ?? null) ? trim((string) $style['borderStyle']) : '';
        if ($borderStyle !== '') {
            $sectionStyle .= 'border-style:' . $borderStyle . ';';
            $borderColor = is_string($style['borderColor'] ?? null) ? trim((string) $style['borderColor']) : '';
            if ($borderColor !== '') {
                $sectionStyle .= 'border-color:' . $borderColor . ';';
            }
            $borderWidths = [
                'top' => $this->intOrNull($style['borderWidthTop'] ?? null),
                'right' => $this->intOrNull($style['borderWidthRight'] ?? null),
                'bottom' => $this->intOrNull($style['borderWidthBottom'] ?? null),
                'left' => $this->intOrNull($style['borderWidthLeft'] ?? null),
            ];
            foreach ($borderWidths as $side => $bw) {
                if ($bw !== null && $bw >= 0) {
                    $sectionStyle .= 'border-' . $side . '-width:' . $bw . 'px;';
                }
            }
        }
        $borderRadius = $this->intOrNull($style['borderRadius'] ?? null);
        $borderRadiusTop = $this->intOrNull($style['borderRadiusTop'] ?? null) ?? $borderRadius;
        $borderRadiusRight = $this->intOrNull($style['borderRadiusRight'] ?? null) ?? $borderRadius;
        $borderRadiusBottom = $this->intOrNull($style['borderRadiusBottom'] ?? null) ?? $borderRadius;
        $borderRadiusLeft = $this->intOrNull($style['borderRadiusLeft'] ?? null) ?? $borderRadius;
        if ($borderRadiusTop !== null && $borderRadiusTop >= 0) {
            $sectionStyle .= 'border-top-left-radius:' . $borderRadiusTop . 'px;';
        }
        if ($borderRadiusRight !== null && $borderRadiusRight >= 0) {
            $sectionStyle .= 'border-top-right-radius:' . $borderRadiusRight . 'px;';
        }
        if ($borderRadiusBottom !== null && $borderRadiusBottom >= 0) {
            $sectionStyle .= 'border-bottom-right-radius:' . $borderRadiusBottom . 'px;';
        }
        if ($borderRadiusLeft !== null && $borderRadiusLeft >= 0) {
            $sectionStyle .= 'border-bottom-left-radius:' . $borderRadiusLeft . 'px;';
        }

        // Transform controls
        $tx = $this->intOrNull($style['transformTranslateX'] ?? null) ?? 0;
        $ty = $this->intOrNull($style['transformTranslateY'] ?? null) ?? 0;
        $rot = $this->intOrNull($style['transformRotate'] ?? null) ?? 0;

        $scale = null;
        if (($style['transformScale'] ?? null) !== null && ($style['transformScale'] ?? null) !== '') {
            $scaleRaw = $style['transformScale'];
            $scaleNum = is_numeric($scaleRaw) ? (float) $scaleRaw : null;
            if ($scaleNum !== null && is_finite($scaleNum)) {
                if ($scaleNum < 0.1) {
                    $scaleNum = 0.1;
                }
                if ($scaleNum > 3) {
                    $scaleNum = 3;
                }
                $scale = $scaleNum;
            }
        }

        $origin = is_string($style['transformOrigin'] ?? null) ? trim((string) $style['transformOrigin']) : '';
        $allowedOrigins = [
            'center center',
            'top left', 'top center', 'top right',
            'center left', 'center right',
            'bottom left', 'bottom center', 'bottom right',
        ];
        $origin = in_array($origin, $allowedOrigins, true) ? $origin : 'center center';

        if ($tx !== 0 || $ty !== 0 || $rot !== 0 || ($scale !== null && abs($scale - 1.0) > 0.0001)) {
            $s = $scale !== null ? $scale : 1.0;
            $sectionStyle .= 'transform:translate(' . $tx . 'px,' . $ty . 'px) rotate(' . $rot . 'deg) scale(' . rtrim(rtrim(sprintf('%.2f', $s), '0'), '.') . ');';
            $sectionStyle .= 'transform-origin:' . $origin . ';';
        }

        // CSS animation controls
        $preset = is_string($style['animationPreset'] ?? null) ? trim((string) $style['animationPreset']) : '';
        $allowedPresets = ['mp-fade-in', 'mp-fade-up', 'mp-scale-in', 'mp-slide-left', 'mp-slide-right'];
        if ($preset === 'custom') {
            $name = 'mp-custom-' . $this->safeKeyframeId($section['id'] ?? '');
            if ($name !== 'mp-custom-') {
                $dur = is_numeric($style['animationDuration'] ?? null) ? (float) $style['animationDuration'] : 0.8;
                if ($dur < 0) {
                    $dur = 0;
                }
                if ($dur > 30) {
                    $dur = 30;
                }

                $delay = is_numeric($style['animationDelay'] ?? null) ? (float) $style['animationDelay'] : 0;
                if ($delay < 0) {
                    $delay = 0;
                }
                if ($delay > 30) {
                    $delay = 30;
                }

                $easing = is_string($style['animationEasing'] ?? null) ? trim((string) $style['animationEasing']) : 'ease';
                if ($easing === '') {
                    $easing = 'ease';
                }
                if (strlen($easing) > 64) {
                    $easing = substr($easing, 0, 64);
                }

                $iterRaw = $style['animationIterationCount'] ?? 1;
                $iter = '1';
                if (is_string($iterRaw) && trim($iterRaw) === 'infinite') {
                    $iter = 'infinite';
                } elseif (is_numeric($iterRaw)) {
                    $n = (int) round((float) $iterRaw);
                    if ($n < 1) {
                        $n = 1;
                    }
                    if ($n > 99) {
                        $n = 99;
                    }
                    $iter = (string) $n;
                }

                $pingPong = ($style['animationPingPong'] ?? null) === true;
                if ($pingPong && $iter === '1') {
                    $iter = '2';
                }

                $fill = is_string($style['animationFillMode'] ?? null) ? trim((string) $style['animationFillMode']) : 'both';
                if (!in_array($fill, ['none', 'both', 'forwards', 'backwards'], true)) {
                    $fill = 'both';
                }

                $sectionStyle .= 'animation-name:' . $name . ';';
                $sectionStyle .= 'animation-duration:' . rtrim(rtrim(sprintf('%.2f', $dur), '0'), '.') . 's;';
                $sectionStyle .= 'animation-delay:' . rtrim(rtrim(sprintf('%.2f', $delay), '0'), '.') . 's;';
                $sectionStyle .= 'animation-timing-function:' . $easing . ';';
                $sectionStyle .= 'animation-iteration-count:' . $iter . ';';
                $sectionStyle .= 'animation-fill-mode:' . $fill . ';';
                if ($pingPong) {
                    $sectionStyle .= 'animation-direction:alternate;';
                }
            }
        } elseif (in_array($preset, $allowedPresets, true)) {
            $dur = is_numeric($style['animationDuration'] ?? null) ? (float) $style['animationDuration'] : 0.8;
            if ($dur < 0) {
                $dur = 0;
            }
            if ($dur > 30) {
                $dur = 30;
            }

            $delay = is_numeric($style['animationDelay'] ?? null) ? (float) $style['animationDelay'] : 0;
            if ($delay < 0) {
                $delay = 0;
            }
            if ($delay > 30) {
                $delay = 30;
            }

            $easing = is_string($style['animationEasing'] ?? null) ? trim((string) $style['animationEasing']) : 'ease';
            if ($easing === '') {
                $easing = 'ease';
            }
            if (strlen($easing) > 64) {
                $easing = substr($easing, 0, 64);
            }

            $iterRaw = $style['animationIterationCount'] ?? 1;
            $iter = '1';
            if (is_string($iterRaw) && trim($iterRaw) === 'infinite') {
                $iter = 'infinite';
            } elseif (is_numeric($iterRaw)) {
                $n = (int) round((float) $iterRaw);
                if ($n < 1) {
                    $n = 1;
                }
                if ($n > 99) {
                    $n = 99;
                }
                $iter = (string) $n;
            }

            $fill = is_string($style['animationFillMode'] ?? null) ? trim((string) $style['animationFillMode']) : 'both';
            if (!in_array($fill, ['none', 'both', 'forwards', 'backwards'], true)) {
                $fill = 'both';
            }

            $sectionStyle .= 'animation-name:' . $preset . ';';
            $sectionStyle .= 'animation-duration:' . rtrim(rtrim(sprintf('%.2f', $dur), '0'), '.') . 's;';
            $sectionStyle .= 'animation-delay:' . rtrim(rtrim(sprintf('%.2f', $delay), '0'), '.') . 's;';
            $sectionStyle .= 'animation-timing-function:' . $easing . ';';
            $sectionStyle .= 'animation-iteration-count:' . $iter . ';';
            $sectionStyle .= 'animation-fill-mode:' . $fill . ';';
        }

        $innerStyle = '';
        if ($maxWidthType !== 'full' && !$hasPresetOverlay) {
            $innerStyle .= $this->buildSectionStyle($style);
        }
        if ($paddingY !== null) {
            $innerStyle .= 'padding-top:' . $paddingY . 'px;padding-bottom:' . $paddingY . 'px;';
        }
        if ($paddingX !== null) {
            $innerStyle .= 'padding-left:' . $paddingX . 'px;padding-right:' . $paddingX . 'px;';
        }
        if ($paddingTop !== null) {
            $innerStyle .= 'padding-top:' . $paddingTop . 'px;';
        }
        if ($paddingRight !== null) {
            $innerStyle .= 'padding-right:' . $paddingRight . 'px;';
        }
        if ($paddingBottom !== null) {
            $innerStyle .= 'padding-bottom:' . $paddingBottom . 'px;';
        }
        if ($paddingLeft !== null) {
            $innerStyle .= 'padding-left:' . $paddingLeft . 'px;';
        }
        if ($maxWidthType === 'boxed') {
            $boxedWidth = $this->cssLength($style['boxedWidth'] ?? null, (
                (is_string($style['maxWidth'] ?? null) && trim((string) $style['maxWidth']) === '7xl') ? '1280px' : '1152px'
            ));
            $innerStyle .= 'max-width:' . $boxedWidth . ';';
            $innerStyle .= 'margin-left:auto;margin-right:auto;';
        } elseif ($maxWidthType === 'full') {
            $innerMaxWidth = $this->cssLength($style['innerMaxWidth'] ?? null, '');
            if ($innerMaxWidth !== '') {
                $innerStyle .= 'width:100%;max-width:' . $innerMaxWidth . ';margin-left:auto;margin-right:auto;';
            } else {
                $innerStyle .= 'width:100%;max-width:100%;';
            }
        }

        // When a section height is set, let the inner content stretch to fill it
        // (mirrors the canvas, where the section inner wrapper uses flex:1 1 auto).
        $sectionHeightTablet = $this->cssLength($style['heightTablet'] ?? null, '');
        $sectionHeightMobile = $this->cssLength($style['heightMobile'] ?? null, '');
        $sectionHasHeight = ($height !== '' && $height !== 'auto')
            || ($sectionHeightTablet !== '' && $sectionHeightTablet !== 'auto')
            || ($sectionHeightMobile !== '' && $sectionHeightMobile !== 'auto');
        if ($sectionHasHeight) {
            $innerStyle .= 'flex:1 1 auto;display:flex;flex-direction:column;';
        }

        $sectionWidthScope = $this->responsiveWidthScope(is_string($section['id'] ?? null) ? $section['id'] : '');
        $sectionWidthCss = $this->responsiveContainerWidthCss($style, $sectionWidthScope, $maxWidthType);
        if ($sectionWidthCss !== '') {
            $innerClasses = trim($innerClasses . ' ' . $sectionWidthScope);
        }

        $sectionDisplayScope = $this->responsiveWidthScope('disp-' . (is_string($section['id'] ?? null) ? $section['id'] : ''));
        $sectionDisplayCss = $this->responsiveDisplayCss($adv, $sectionDisplayScope);
        if ($sectionDisplayCss !== '') {
            $shellClasses = trim($shellClasses . ' ' . $sectionDisplayScope);
        }

        $sectionHeightScope = $this->responsiveWidthScope('h-' . (is_string($section['id'] ?? null) ? $section['id'] : ''));
        $sectionHeightCss = $this->responsiveHeightCss($style, $sectionHeightScope);
        if ($sectionHeightCss !== '') {
            $shellClasses = trim($shellClasses . ' ' . $sectionHeightScope);
        }

        $sectionItemsScope = $this->responsiveWidthScope('items-' . (is_string($section['id'] ?? null) ? $section['id'] : ''));
        $sectionItemsCss = $this->responsiveSectionItemsCss($style, $sectionItemsScope);

        $gridStyle = '';
        $gridClasses = ['mp-grid'];
        if ($sectionItemsCss !== '') {
            $gridClasses[] = $sectionItemsScope;
        }
        $isColumn = in_array($direction, ['column', 'column-reverse'], true);
        if ($isColumn) {
            $gridClasses[] = 'mp-grid--column';
        }
        $gridStyle .= 'display:flex;flex-wrap:wrap;flex-direction:' . $direction . ';';
        $gridStyle .= 'justify-content:' . $justify . ';align-items:' . $align . ';';
        $gridStyle .= 'width:100%;';
        if ($sectionHasHeight) {
            $gridStyle .= 'flex:1 1 auto;';
        }

        $fallbackGap = $gap !== null ? $gap : 24;
        $gx = $gapX !== null ? $gapX : $fallbackGap;
        $gy = $gapY !== null ? $gapY : $fallbackGap;
        $gridStyle .= 'column-gap:' . $gx . 'px;row-gap:' . $gy . 'px;';
        $gridStyle .= '--mp-gap-x:' . $gx . 'px;';

        $sectionCprScope = $this->responsiveWidthScope('cpr-' . (is_string($section['id'] ?? null) ? $section['id'] : ''));
        $sectionCprCss = $this->responsiveColumnsPerRowCss(
            $style,
            '.mp-grid.' . $sectionCprScope . '.' . $sectionCprScope,
            '> .mp-col',
            $gx,
            max(1, count($columns)),
            $gy
        );
        if ($sectionCprCss !== '') {
            $gridClasses[] = $sectionCprScope;
        }

        $grid = ($sectionItemsCss !== '' ? '<style>' . $sectionItemsCss . '</style>' : '')
            . ($sectionCprCss !== '' ? '<style>' . $sectionCprCss . '</style>' : '')
            . '<div class="' . e(implode(' ', $gridClasses)) . '"' . ($gridStyle !== '' ? (' style="' . e($gridStyle) . '"') : '') . '>';
        $colCtx = ['mode' => 'section', 'direction' => $direction, 'justify' => $justify, 'align' => $align, 'gapX' => $gx];
        foreach ($columns as $col) {
            if (!is_array($col)) {
                continue;
            }
            $grid .= $this->renderColumn($col, $colCtx);
        }
        $grid .= '</div>';

        $this->inPricingScope = false;

        if (($gridOverlayHtml !== '' || $presetOverlayHtml !== '') && !str_contains($sectionStyle, 'position:')) {
            $sectionStyle .= 'position:relative;';
        }

        $attrs = '';
        if (($adv['id'] ?? '') !== '') {
            $attrs .= ' id="' . e($adv['id']) . '"';
        }
        if (($adv['cache'] ?? '') === 'no_cache') {
            $attrs .= ' data-mp-cache="no_cache"';
        }

        if ($gsapEnabled) {
            $preset = is_string($style['gsapPreset'] ?? null) ? trim((string) $style['gsapPreset']) : '';
            $allowed = ['fade-up', 'fade-in', 'scale-in', 'slide-left', 'slide-right', 'blurred-in', 'from-x', 'from-y'];
            $preset = in_array($preset, $allowed, true) ? $preset : 'fade-up';

            $trigger = is_string($style['gsapTrigger'] ?? null) ? trim((string) $style['gsapTrigger']) : 'scroll';
            $trigger = in_array($trigger, ['scroll', 'load'], true) ? $trigger : 'scroll';

            $dur = is_numeric($style['gsapDuration'] ?? null) ? (float) $style['gsapDuration'] : 0.8;
            if ($dur < 0) {
                $dur = 0;
            }
            if ($dur > 30) {
                $dur = 30;
            }

            $delay = is_numeric($style['gsapDelay'] ?? null) ? (float) $style['gsapDelay'] : 0;
            if ($delay < 0) {
                $delay = 0;
            }
            if ($delay > 30) {
                $delay = 30;
            }

            $ease = is_string($style['gsapEase'] ?? null) ? trim((string) $style['gsapEase']) : 'power2.out';
            if ($ease === '') {
                $ease = 'power2.out';
            }
            if (strlen($ease) > 64) {
                $ease = substr($ease, 0, 64);
            }

            $start = $this->intOrNull($style['gsapStart'] ?? null) ?? 80;
            if ($start < 0) {
                $start = 0;
            }
            if ($start > 100) {
                $start = 100;
            }

            $once = ($style['gsapOnce'] ?? null) !== false;

            $cfg = [
                'enabled' => true,
                'preset' => $preset,
                'trigger' => $trigger,
                'duration' => $dur,
                'delay' => $delay,
                'ease' => $ease,
                'start' => $start,
                'once' => $once,
                'stagger' => $gsapStagger,
            ];

            $attrs .= ' data-mp-gsap="' . e(json_encode($cfg)) . '"';
        }

        // Page-level header sticky styles must be applied to the element that
        // actually carries the section background so the sticky background is
        // visible and width/height animate on the correct container.
        if ($isStickyTarget) {
            $transition = 'transition:height 0.4s ease,max-width 0.4s ease,background-color 0.4s ease,background-image 0.4s ease,border 0.4s ease,border-radius 0.4s ease;';
            if ($maxWidthType === 'full') {
                $sectionStyle .= 'position:sticky;top:0;z-index:50;margin-left:auto;margin-right:auto;max-width:100%;' . $transition;
            } else {
                $innerStyle .= 'position:sticky;top:0;z-index:50;margin-left:auto;margin-right:auto;' . $transition;
            }
        }

        $stickyBehavior = is_string($style['stickyBehavior'] ?? null) ? trim((string) $style['stickyBehavior']) : 'none';
        $stickyScript = '';
        if ($stickyBehavior !== 'none' && $stickyBehavior !== '') {
            $stickyOffset = $this->intOrNull($style['stickyOffset'] ?? null) ?? 0;
            $stickyBg = is_string($style['stickyBackgroundColor'] ?? null) ? trim((string) $style['stickyBackgroundColor']) : '';
            $stickyBorder = is_string($style['stickyBorderColor'] ?? null) ? trim((string) $style['stickyBorderColor']) : '';
            $stickyText = is_string($style['stickyTextColor'] ?? null) ? trim((string) $style['stickyTextColor']) : '';

            $attrs .= ' data-mp-sticky-behavior="' . e($stickyBehavior) . '"';
            $attrs .= ' data-mp-sticky-offset="' . e((string) $stickyOffset) . '"';
            if ($stickyBg !== '') {
                $attrs .= ' data-mp-sticky-bg="' . e($stickyBg) . '"';
            }
            if ($stickyBorder !== '') {
                $attrs .= ' data-mp-sticky-border="' . e($stickyBorder) . '"';
            }
            if ($stickyText !== '') {
                $attrs .= ' data-mp-sticky-text="' . e($stickyText) . '"';
            }

            $jsBehavior = $stickyBehavior === 'up-scroll' ? 'up-scroll' : 'scroll';
            $stickyScript = '<script>'
                . '(function(){'
                . 'var s=document.currentScript.previousElementSibling;'
                . 'if(!s||s.tagName!=="SECTION")return;'
                . 'var b="' . $jsBehavior . '",o=parseInt(s.dataset.mpStickyOffset||"0",10)||0,'
                . 'bg=s.dataset.mpStickyBg||"",bc=s.dataset.mpStickyBorder||"",tc=s.dataset.mpStickyText||"",'
                . 'last=0,applied=false,clone=null,t=300,rmTimer=null,tick=false;'
                . 'function apply(){'
                . 'if(applied||!s)return;'
                . 'if(rmTimer){clearTimeout(rmTimer);rmTimer=null;}'
                . 'applied=true;'
                . 'clone=s.cloneNode(true);'
                . 'clone.removeAttribute("id");'
                . 'clone.classList.add("mp-sticky-clone");'
                . 'clone.style.position="fixed";clone.style.top="0";clone.style.left="0";clone.style.right="0";clone.style.zIndex="9999";'
                . 'clone.style.margin="0";clone.style.willChange="transform";'
                . 'clone.style.transition="transform "+t+"ms cubic-bezier(0.25,0.46,0.45,0.94),background-color "+t+"ms ease,border-color "+t+"ms ease";'
                . 'clone.style.transform="translateY(-100%)";'
                . 'if(bg)clone.style.backgroundColor=bg;'
                . 'if(bc){clone.style.borderBottomWidth="1px";clone.style.borderBottomStyle="solid";clone.style.borderBottomColor=bc;}'
                . 'if(tc){clone.querySelectorAll("a,button,.mp-nav-link,.mp-mega-trigger").forEach(function(el){el.style.color=tc;});}'
                . 'document.body.appendChild(clone);'
                . 'requestAnimationFrame(function(){requestAnimationFrame(function(){if(clone)clone.style.transform="translateY(0)";});});'
                . '}'
                . 'function remove(){'
                . 'if(!applied||!clone)return;'
                . 'applied=false;'
                . 'clone.style.transform="translateY(-100%)";'
                . 'rmTimer=setTimeout(function(){'
                . 'rmTimer=null;'
                . 'if(clone&&clone.parentNode){clone.parentNode.removeChild(clone);}'
                . 'clone=null;'
                . '},t);'
                . '}'
                . 'function onScroll(){'
                . 'var y=window.scrollY||window.pageYOffset;'
                . 'if(b==="scroll"){if(y>o){apply();}else{remove();}}'
                . 'else if(b==="up-scroll"){if(y<last&&y>o){apply();}else if(y<=o||y>last){remove();}}'
                . 'last=y;'
                . '}'
                . 'window.addEventListener("scroll",function(){'
                . 'if(tick)return;'
                . 'tick=true;'
                . 'requestAnimationFrame(function(){onScroll();tick=false;});'
                . '},{passive:true});'
                . 'onScroll();'
                . '})();'
                . '</script>';
        }

        $pricingScopeAttr = $hasPricingTable ? ' x-data="{ annual: false }"' : '';
        $pricingHeader = $hasStandalonePricingTable ? $this->renderSectionPricingHeader($section) : '';

        return '<section class="' . e($shellClasses) . '"' . $attrs . $pricingScopeAttr . ($sectionStyle !== '' ? (' style="' . e($sectionStyle) . '"') : '') . '>'
            . ($sectionWidthCss !== '' ? ('<style>' . $sectionWidthCss . '</style>') : '')
            . ($sectionDisplayCss !== '' ? ('<style>' . $sectionDisplayCss . '</style>') : '')
            . ($sectionHeightCss !== '' ? ('<style>' . $sectionHeightCss . '</style>') : '')
            . $bgVideoHtml
            . $gridOverlayHtml
            . $presetOverlayHtml
            . '<div class="mp-section__content"' . ($sectionHasHeight ? ' style="flex:1 1 auto;display:flex;flex-direction:column;"' : '') . '>'
            . '<div class="' . e(trim($innerClasses)) . '"' . ($innerStyle !== '' ? (' style="' . e($innerStyle) . '"') : '') . '>'
            . $pricingHeader
            . $grid
            . '</div>'
            . '</div>'
            . '</section>'
            . $stickyScript;
    }

    private function renderColumn(array $col, array $ctx = []): string
    {
        $spans = is_array($col['spans'] ?? null) ? $col['spans'] : [];
        $base = $this->span($spans['base'] ?? 12) ?? 12;
        $md = $this->span($spans['md'] ?? null);
        $lg = $this->span($spans['lg'] ?? null);

        $adv = $this->normalizeAdvanced(is_array($col['advanced'] ?? null) ? $col['advanced'] : []);
        $columnStyle = is_array($col['style'] ?? null) ? $col['style'] : [];

        $classes = trim('mp-col ' . ($adv['class'] ?? ''));
        $attrs = ' data-span-base="' . e((string) $base) . '"';
        if ($md !== null) {
            $attrs .= ' data-span-md="' . e((string) $md) . '"';
        }
        if ($lg !== null) {
            $attrs .= ' data-span-lg="' . e((string) $lg) . '"';
        }

        if (($adv['id'] ?? '') !== '') {
            $attrs .= ' id="' . e($adv['id']) . '"';
        }

        if (($adv['cache'] ?? '') === 'no_cache') {
            $attrs .= ' data-mp-cache="no_cache"';
        }

        $style = '';
        $paddingY = $this->intOrNull($columnStyle['paddingY'] ?? null);
        $paddingX = $this->intOrNull($columnStyle['paddingX'] ?? null);
        $paddingTop = $this->intOrNull($columnStyle['paddingTop'] ?? null) ?? $paddingY ?? 0;
        $paddingRight = $this->intOrNull($columnStyle['paddingRight'] ?? null) ?? $paddingX ?? 0;
        $paddingBottom = $this->intOrNull($columnStyle['paddingBottom'] ?? null) ?? $paddingY ?? 0;
        $paddingLeft = $this->intOrNull($columnStyle['paddingLeft'] ?? null) ?? $paddingX ?? 0;
        $style .= 'box-sizing:border-box;padding:' . $paddingTop . 'px ' . $paddingRight . 'px ' . $paddingBottom . 'px ' . $paddingLeft . 'px;';
        foreach (['Top' => 'top', 'Right' => 'right', 'Bottom' => 'bottom', 'Left' => 'left'] as $key => $side) {
            $margin = $this->intOrNull($columnStyle['margin' . $key] ?? null);
            if ($margin !== null) {
                $style .= 'margin-' . $side . ':' . $margin . 'px;';
            }
        }
        if (($adv['position'] ?? '') !== '') {
            $style .= 'position:' . $adv['position'] . ';';
        }
        $flexDir = is_string($columnStyle['itemsDirection'] ?? null) && in_array($columnStyle['itemsDirection'], ['row', 'column', 'row-reverse', 'column-reverse'], true)
            ? $columnStyle['itemsDirection'] : 'column';
        $justify = is_string($columnStyle['itemsJustify'] ?? null) && in_array($columnStyle['itemsJustify'], ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], true)
            ? $columnStyle['itemsJustify'] : 'flex-start';
        $alignItems = is_string($columnStyle['itemsAlign'] ?? null) && in_array($columnStyle['itemsAlign'], ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'], true)
            ? $columnStyle['itemsAlign'] : 'stretch';
        $style .= 'display:flex;flex-direction:' . $flexDir . ';justify-content:' . $justify . ';align-items:' . $alignItems . ';';
        if (($adv['display'] ?? '') !== '') {
            $style .= 'display:' . $adv['display'] . ';';
        }
        if (($adv['textAlign'] ?? '') !== '') {
            $style .= 'text-align:' . $adv['textAlign'] . ';';
        }
        if (($adv['overflowX'] ?? '') !== '') {
            $style .= 'overflow-x:' . $adv['overflowX'] . ';';
        }
        if (($adv['overflowY'] ?? '') !== '') {
            $style .= 'overflow-y:' . $adv['overflowY'] . ';';
        }
        if (($adv['alignItems'] ?? '') !== '') {
            $style .= 'align-items:' . $adv['alignItems'] . ';';
        }
        if (($adv['justifyContent'] ?? '') !== '') {
            $style .= 'justify-content:' . $adv['justifyContent'] . ';';
        }
        if (($adv['zIndex'] ?? null) !== null) {
            $style .= 'z-index:' . (string) $adv['zIndex'] . ';';
        }
        if (($adv['position'] ?? '') === 'absolute') {
            if (($adv['absXOffset'] ?? null) !== null) {
                $style .= (($adv['absXSide'] ?? 'left') === 'right' ? 'right:' : 'left:') . (string) $adv['absXOffset'] . 'px;';
            }
            if (($adv['absYOffset'] ?? null) !== null) {
                $style .= (($adv['absYSide'] ?? 'top') === 'bottom' ? 'bottom:' : 'top:') . (string) $adv['absYOffset'] . 'px;';
            }
        }
        if (is_numeric($columnStyle['gapX'] ?? null)) {
            $style .= 'column-gap:' . (string) $columnStyle['gapX'] . 'px;';
        }
        if (is_numeric($columnStyle['gapY'] ?? null)) {
            $style .= 'row-gap:' . (string) $columnStyle['gapY'] . 'px;';
        }

        $sDirection = is_string($ctx['direction'] ?? null) ? $ctx['direction'] : 'row';
        $sJustify = is_string($ctx['justify'] ?? null) ? $ctx['justify'] : 'flex-start';
        $sAlign = is_string($ctx['align'] ?? null) ? $ctx['align'] : 'stretch';
        $sGapX = is_numeric($ctx['gapX'] ?? null) ? (float) $ctx['gapX'] : 24;
        $isGridCtx = ($ctx['mode'] ?? 'section') === 'grid';
        $span = max(1, min(12, (int) ($lg ?? $md ?? $base ?? 12)));

        $colMaxWidthType = is_string($columnStyle['maxWidthType'] ?? null) && $columnStyle['maxWidthType'] !== ''
            ? $columnStyle['maxWidthType']
            : ((is_string($columnStyle['maxWidth'] ?? null) && trim((string) $columnStyle['maxWidth']) === 'full') ? 'full' : 'boxed');
        $colWidthConstraint = $colMaxWidthType === 'full'
            ? ($columnStyle['innerMaxWidth'] ?? null)
            : ($columnStyle['boxedWidth'] ?? null);
        $colWidthCss = ($colWidthConstraint !== null && $colWidthConstraint !== '')
            ? $this->cssLength($colWidthConstraint, '')
            : '';

        if ($colWidthCss !== '') {
            $style .= 'flex:0 0 auto;width:100%;max-width:' . $colWidthCss . ';min-width:0;';
            $colMLeft = $this->intOrNull($columnStyle['marginLeft'] ?? null);
            $colMRight = $this->intOrNull($columnStyle['marginRight'] ?? null);
            if (($colMLeft === null || $colMLeft === 0) && ($colMRight === null || $colMRight === 0)) {
                $style .= 'margin-left:auto;margin-right:auto;';
            }
        } elseif ($isGridCtx) {
            if ($sDirection === 'row' || $sDirection === 'row-reverse') {
                $basisPct = round(($span / 12) * 100, 4);
                $gapAdj = round((1 - $span / 12) * $sGapX, 4);
                $style .= 'flex-basis:calc(' . $basisPct . '% - ' . $gapAdj . 'px);flex-grow:0;flex-shrink:1;min-width:0;';
            } else {
                $style .= 'flex-basis:0;flex-grow:' . $span . ';flex-shrink:1;min-width:0;';
            }
        } elseif ($sDirection === 'column' || $sDirection === 'column-reverse') {
            $style .= 'flex:0 0 auto;min-width:0;';
        } elseif ($sJustify !== 'flex-start') {
            $basisPct = round(($span / 12) * 100, 4);
            $gapAdj = round((1 - $span / 12) * $sGapX, 4);
            $style .= 'flex-basis:calc(' . $basisPct . '% - ' . $gapAdj . 'px);flex-grow:0;flex-shrink:0;min-width:0;';
        } else {
            $style .= 'flex-basis:0;flex-grow:' . $span . ';flex-shrink:1;min-width:0;';
        }

        $colWidthScope = $this->responsiveWidthScope(is_string($col['id'] ?? null) ? $col['id'] : '');
        $colWidthMediaCss = $this->responsiveContainerWidthCss($columnStyle, $colWidthScope, $colMaxWidthType);
        $colDisplayCss = $this->responsiveDisplayCss($adv, $colWidthScope);
        $colResponsiveCss = $colWidthMediaCss . $colDisplayCss;
        if ($colResponsiveCss !== '') {
            $classes = trim($classes . ' ' . $colWidthScope);
        }

        $selfAlign = is_string($columnStyle['align'] ?? null) && trim($columnStyle['align']) !== '' ? trim($columnStyle['align']) : $sAlign;
        if (in_array($selfAlign, ['stretch', 'flex-start', 'center', 'flex-end', 'baseline', 'start', 'end', 'self-start', 'self-end'], true)) {
            $style .= 'align-self:' . $selfAlign . ';';
        }

        // Column-level container look (mirrors the canvas containerInnerStyle on .mp-col):
        // background, border, radius, min-height.
        $style .= $this->buildSectionStyle($columnStyle);
        if (is_string($columnStyle['borderStyle'] ?? null) && trim((string) $columnStyle['borderStyle']) !== '' && ($columnStyle['borderStyle'] ?? '') !== 'default'
            && in_array((string) $columnStyle['borderStyle'], ['solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset'], true)) {
            $style .= 'border-style:' . $columnStyle['borderStyle'] . ';';
        }
        $colBorderColor = $this->sanitizeColor($columnStyle['borderColor'] ?? null);
        if ($colBorderColor !== null) {
            $style .= 'border-color:' . $colBorderColor . ';';
        }
        foreach (['Top' => 'top', 'Right' => 'right', 'Bottom' => 'bottom', 'Left' => 'left'] as $bKey => $bSide) {
            $bw = $this->intOrNull($columnStyle['borderWidth' . $bKey] ?? null);
            if ($bw !== null && $bw >= 0) {
                $style .= 'border-' . $bSide . '-width:' . $bw . 'px;';
            }
        }
        foreach (['Top' => 'top-left', 'Right' => 'top-right', 'Bottom' => 'bottom-right', 'Left' => 'bottom-left'] as $rKey => $rCorner) {
            $br = $this->intOrNull($columnStyle['borderRadius' . $rKey] ?? null) ?? $this->intOrNull($columnStyle['borderRadius'] ?? null);
            if ($br !== null && $br >= 0) {
                $style .= 'border-' . $rCorner . '-radius:' . $br . 'px;';
            }
        }
        $colHeight = $this->intOrNull($columnStyle['height'] ?? null);
        if ($colHeight !== null && $colHeight >= 0) {
            $style .= 'min-height:' . $colHeight . 'px;';
        }

        if ($style !== '') {
            $attrs .= ' style="' . e($style) . '"';
        }

        $widgets = is_array($col['widgets'] ?? null) ? $col['widgets'] : [];

        // Columns per row: wrap this column's widgets into rows without removing any.
        $colWidgetTotal = 0;
        foreach ($widgets as $w) {
            if (is_array($w)) {
                $colWidgetTotal++;
            }
        }
        $colGapX = max(0, $this->intOrNull($columnStyle['gapX'] ?? null) ?? $this->intOrNull($columnStyle['gap'] ?? null) ?? 0);
        $colGapY = max(0, $this->intOrNull($columnStyle['gapY'] ?? null) ?? $this->intOrNull($columnStyle['gap'] ?? null) ?? 0);
        $colCprScope = $this->responsiveWidthScope('cpr-' . (is_string($col['id'] ?? null) ? $col['id'] : ''));
        $colCprCss = $this->responsiveColumnsPerRowCss($columnStyle, '.' . $colCprScope . '.' . $colCprScope, '> div', $colGapX, $colWidgetTotal, $colGapY);
        $colWraps = $colCprCss !== '';

        $widgetsHtml = '';
        $widgetIndex = 0;
        foreach ($widgets as $w) {
            if (!is_array($w)) {
                continue;
            }
            $widgetHtml = $this->renderWidget($w);
            if ($widgetHtml !== '') {
                $widgetIndex++;
                $widgetStyle = 'width:100%;max-width:100%;min-width:0;box-sizing:border-box;';
                if ($widgetIndex > 1 && !$colWraps) {
                    // Grid columns use a tighter 12px stack spacing (canvas space-y-3);
                    // section columns keep 16px (canvas space-y-4). When wrapping, row-gap
                    // handles vertical spacing instead.
                    $widgetStyle .= 'margin-top:' . ($isGridCtx ? '12' : '16') . 'px;';
                }
                $wid = is_string($w['id'] ?? null) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $w['id']) : '';
                $scope = is_string($wid) && $wid !== '' ? 'mp-typo-' . $wid : '';
                $typoCss = $scope !== '' ? $this->responsiveTypographyCss(is_array($w['props'] ?? null) ? $w['props'] : [], $scope) : '';
                $styleTag = $typoCss !== '' ? '<style>' . $typoCss . '</style>' : '';
                $classAttr = $scope !== '' ? ' class="' . e($scope) . '"' : '';
                $widgetsHtml .= '<div' . $classAttr . ' style="' . $widgetStyle . '">' . $styleTag . $widgetHtml . '</div>';
            }
        }

        $innerWrapperClass = $colWraps ? ' class="' . e($colCprScope) . '"' : '';
        $outerColumnMargin = $isGridCtx ? '' : 'margin-top:12px;margin-bottom:12px;';
        $html = '<div style="flex:1 1 auto;display:flex;flex-direction:column;' . $outerColumnMargin . '">'
            . '<div' . $innerWrapperClass . ' style="flex:1 1 auto;width:100%;min-width:0;position:relative;">' . $widgetsHtml . '</div>'
            . '</div>';

        return ($colResponsiveCss !== '' ? '<style>' . $colResponsiveCss . '</style>' : '')
            . ($colCprCss !== '' ? '<style>' . $colCprCss . '</style>' : '')
            . '<div class="' . e($classes) . '"' . $attrs . '>' . $html . '</div>';
    }

    private function renderWidget(array $widget): string
    {
        $type = is_string($widget['type'] ?? null) ? trim((string) $widget['type']) : '';
        $props = is_array($widget['props'] ?? null) ? $widget['props'] : [];
        $adv = $this->normalizeAdvanced(is_array($widget['advanced'] ?? null) ? $widget['advanced'] : []);

        $html = match ($type) {
            'heading' => $this->renderHeading($props),
            'rich_text' => $this->renderRichText($props),
            'image' => $this->renderImage($props, $adv),
            'button' => $this->renderButton($props),
            'button_group' => $this->renderButtonGroup($props),
            'badge' => $this->renderBadge($props),
            'icon' => $this->renderIcon($props),
            'pricing_table' => $this->renderPricingTable($props),
            'faqs' => $this->renderFaqs($props),
            'video' => $this->renderVideo($props),
            'slider' => $this->renderSlider($props),
            'marquee' => $this->renderMarquee($props),
            'grid' => $this->renderGrid($props, is_array($widget['style'] ?? null) ? $widget['style'] : [], is_string($widget['id'] ?? null) ? (string) $widget['id'] : ''),
            'tabs' => $this->renderTabs($props),
            'nav_menu' => $this->renderNavMenu($props),
            'mega_menu' => $this->renderMegaMenu($props),
            'social_icons' => $this->renderSocialIcons($props, $adv),
            'social_proof' => $this->renderSocialProof($props),
            'newsletter_form' => $this->renderNewsletterForm($props),
            'copyright' => $this->renderCopyright($props),
            'divider' => $this->renderEmailDivider($props),
            'spacer' => $this->renderEmailSpacer($props),
            'logo' => $this->renderEmailLogo($props),
            'rating' => $this->renderEmailRating($props),
            'link' => $this->renderEmailLinks($props, $adv),
            'ai_prompt_input' => $this->renderAiPromptInput($props),
            'login_form' => $this->renderLoginForm($props),
            'register_form' => $this->renderRegisterForm($props),
            'form_text' => $this->renderFormText($props),
            'form_email' => $this->renderFormEmail($props),
            'form_textarea' => $this->renderFormTextarea($props),
            'form_select' => $this->renderFormSelect($props),
            'form_checkbox' => $this->renderFormCheckbox($props),
            'form_radio' => $this->renderFormRadio($props),
            'form_submit' => $this->renderFormSubmit($props),
            'text_timeline' => $this->renderTextTimeline($props),
            'testimonials' => $this->renderTestimonials($props),
            'testimonial' => $this->renderTestimonial($props),
            default => '',
        };

        if ($html === '') {
            return '';
        }

        $gsapEnabled = ($props['gsapEnabled'] ?? null) === true;
        $gsapStagger = is_numeric($props['gsapStagger'] ?? null) ? (float) $props['gsapStagger'] : 0;
        if ($gsapStagger < 0) {
            $gsapStagger = 0;
        }
        if ($gsapStagger > 2) {
            $gsapStagger = 2;
        }

        $hasTransform = ($props['transformTranslateX'] ?? null) !== null
            || ($props['transformTranslateY'] ?? null) !== null
            || ($props['transformRotate'] ?? null) !== null
            || ($props['transformScale'] ?? null) !== null
            || ($props['transformOrigin'] ?? null) !== null;
        $hasCssAnimation = is_string($props['animationPreset'] ?? null) && trim((string) $props['animationPreset']) !== '';

        $needsWrapper = (($adv['id'] ?? '') !== '')
            || (($adv['class'] ?? '') !== '')
            || (($adv['position'] ?? '') !== '')
            || (($adv['display'] ?? '') !== '')
            || (($adv['displayTablet'] ?? '') !== '')
            || (($adv['displayMobile'] ?? '') !== '')
            || (($adv['textAlign'] ?? '') !== '')
            || (($adv['overflowX'] ?? '') !== '')
            || (($adv['overflowY'] ?? '') !== '')
            || (($adv['alignItems'] ?? '') !== '')
            || (($adv['justifyContent'] ?? '') !== '')
            || ((($adv['position'] ?? '') === 'absolute') && ((($adv['absXOffset'] ?? null) !== null) || (($adv['absYOffset'] ?? null) !== null)))
            || (($adv['zIndex'] ?? null) !== null)
            || (($adv['width'] ?? null) !== null)
            || (($adv['height'] ?? null) !== null)
            || (($adv['cache'] ?? '') === 'no_cache')
            || $gsapEnabled
            || $hasTransform
            || $hasCssAnimation;

        if (!$needsWrapper) {
            return $html;
        }

        $attrs = '';
        if (($adv['id'] ?? '') !== '') {
            $attrs .= ' id="' . e($adv['id']) . '"';
        }

        $classes = trim((string) ($adv['class'] ?? ''));
        $widgetDisplayScope = '';
        $widgetDisplayCss = '';
        if (($adv['displayTablet'] ?? '') !== '' || ($adv['displayMobile'] ?? '') !== '') {
            $widgetDisplayScope = $this->responsiveWidthScope('disp-' . (is_string($widget['id'] ?? null) ? $widget['id'] : ''));
            $widgetDisplayCss = $this->responsiveDisplayCss($adv, $widgetDisplayScope);
            if ($widgetDisplayScope !== '') {
                $classes = trim($classes . ' ' . $widgetDisplayScope);
            }
        }
        if ($gsapEnabled) {
            $preset = is_string($props['gsapPreset'] ?? null) ? trim((string) $props['gsapPreset']) : '';
            $allowed = ['fade-up', 'fade-in', 'scale-in', 'slide-left', 'slide-right', 'blurred-in', 'from-x', 'from-y'];
            $preset = in_array($preset, $allowed, true) ? $preset : 'fade-up';
            $classes = trim($classes . ' gsap-' . $preset);
            if ($gsapStagger > 0) {
                $classes = trim($classes . ' gsap-stagger');
            }
        }

        if ($classes !== '') {
            $attrs .= ' class="' . e($classes) . '"';
        }
        if (($adv['cache'] ?? '') === 'no_cache') {
            $attrs .= ' data-mp-cache="no_cache"';
        }

        if ($gsapEnabled) {
            $preset = is_string($props['gsapPreset'] ?? null) ? trim((string) $props['gsapPreset']) : '';
            $allowed = ['fade-up', 'fade-in', 'scale-in', 'slide-left', 'slide-right', 'blurred-in', 'from-x', 'from-y'];
            $preset = in_array($preset, $allowed, true) ? $preset : 'fade-up';

            $trigger = is_string($props['gsapTrigger'] ?? null) ? trim((string) $props['gsapTrigger']) : 'scroll';
            $trigger = in_array($trigger, ['scroll', 'load'], true) ? $trigger : 'scroll';

            $dur = is_numeric($props['gsapDuration'] ?? null) ? (float) $props['gsapDuration'] : 0.8;
            if ($dur < 0) {
                $dur = 0;
            }
            if ($dur > 30) {
                $dur = 30;
            }

            $delay = is_numeric($props['gsapDelay'] ?? null) ? (float) $props['gsapDelay'] : 0;
            if ($delay < 0) {
                $delay = 0;
            }
            if ($delay > 30) {
                $delay = 30;
            }

            $ease = is_string($props['gsapEase'] ?? null) ? trim((string) $props['gsapEase']) : 'power2.out';
            if ($ease === '') {
                $ease = 'power2.out';
            }
            if (strlen($ease) > 64) {
                $ease = substr($ease, 0, 64);
            }

            $start = $this->intOrNull($props['gsapStart'] ?? null) ?? 80;
            if ($start < 0) {
                $start = 0;
            }
            if ($start > 100) {
                $start = 100;
            }

            $once = ($props['gsapOnce'] ?? null) !== false;

            $cfg = [
                'enabled' => true,
                'preset' => $preset,
                'trigger' => $trigger,
                'duration' => $dur,
                'delay' => $delay,
                'split' => is_string($props['gsapSplit'] ?? null) ? trim((string) $props['gsapSplit']) : '',
                'splitStagger' => is_numeric($props['gsapSplitStagger'] ?? null) ? (float) $props['gsapSplitStagger'] : 0.04,
                'ease' => $ease,
                'start' => $start,
                'once' => $once,
                'stagger' => $gsapStagger,
            ];

            $attrs .= ' data-mp-gsap="' . e(json_encode($cfg)) . '"';
        }

        $style = '';
        if (($adv['position'] ?? '') !== '') {
            $style .= 'position:' . $adv['position'] . ';';
        }
        if (($adv['display'] ?? '') !== '') {
            $style .= 'display:' . $adv['display'] . ';';
            if ($adv['display'] === 'flex' || $adv['display'] === 'inline-flex') {
                $style .= 'min-height:100%;';
            }
        }
        if (($adv['textAlign'] ?? '') !== '') {
            $style .= 'text-align:' . $adv['textAlign'] . ';';
        }
        if (($adv['overflowX'] ?? '') !== '') {
            $style .= 'overflow-x:' . $adv['overflowX'] . ';';
        }
        if (($adv['overflowY'] ?? '') !== '') {
            $style .= 'overflow-y:' . $adv['overflowY'] . ';';
        }
        if (($adv['alignItems'] ?? '') !== '') {
            $style .= 'align-items:' . $adv['alignItems'] . ';';
        }
        if (($adv['justifyContent'] ?? '') !== '') {
            $style .= 'justify-content:' . $adv['justifyContent'] . ';';
        }
        if (($adv['zIndex'] ?? null) !== null) {
            $style .= 'z-index:' . (string) $adv['zIndex'] . ';';
        }
        if (($adv['width'] ?? null) !== null) {
            $style .= 'width:' . (string) $adv['width'] . 'px;';
        }
        if (($adv['height'] ?? null) !== null) {
            $style .= 'height:' . (string) $adv['height'] . 'px;';
        }
        if (($adv['position'] ?? '') === 'absolute') {
            if (($adv['absXOffset'] ?? null) !== null) {
                $style .= (($adv['absXSide'] ?? 'left') === 'right' ? 'right:' : 'left:') . (string) $adv['absXOffset'] . 'px;';
            }
            if (($adv['absYOffset'] ?? null) !== null) {
                $style .= (($adv['absYSide'] ?? 'top') === 'bottom' ? 'bottom:' : 'top:') . (string) $adv['absYOffset'] . 'px;';
            }
        }

        $tx = $this->intOrNull($props['transformTranslateX'] ?? null) ?? 0;
        $ty = $this->intOrNull($props['transformTranslateY'] ?? null) ?? 0;
        $rot = $this->intOrNull($props['transformRotate'] ?? null) ?? 0;

        $scale = null;
        if (($props['transformScale'] ?? null) !== null && ($props['transformScale'] ?? null) !== '') {
            $scaleRaw = $props['transformScale'];
            $scaleNum = is_numeric($scaleRaw) ? (float) $scaleRaw : null;
            if ($scaleNum !== null && is_finite($scaleNum)) {
                if ($scaleNum < 0.1) {
                    $scaleNum = 0.1;
                }
                if ($scaleNum > 3) {
                    $scaleNum = 3;
                }
                $scale = $scaleNum;
            }
        }

        $origin = is_string($props['transformOrigin'] ?? null) ? trim((string) $props['transformOrigin']) : '';
        $allowedOrigins = [
            'center center',
            'top left', 'top center', 'top right',
            'center left', 'center right',
            'bottom left', 'bottom center', 'bottom right',
        ];
        $origin = in_array($origin, $allowedOrigins, true) ? $origin : 'center center';

        if ($tx !== 0 || $ty !== 0 || $rot !== 0 || ($scale !== null && abs($scale - 1.0) > 0.0001)) {
            $s = $scale !== null ? $scale : 1.0;
            $style .= 'transform:translate(' . $tx . 'px,' . $ty . 'px) rotate(' . $rot . 'deg) scale(' . rtrim(rtrim(sprintf('%.2f', $s), '0'), '.') . ');';
            $style .= 'transform-origin:' . $origin . ';';
        }

        $preset = is_string($props['animationPreset'] ?? null) ? trim((string) $props['animationPreset']) : '';
        $allowedPresets = ['mp-fade-in', 'mp-fade-up', 'mp-scale-in', 'mp-slide-left', 'mp-slide-right'];
        if ($preset === 'custom') {
            $name = 'mp-custom-' . $this->safeKeyframeId($widget['id'] ?? '');
            if ($name !== 'mp-custom-') {
                $dur = is_numeric($props['animationDuration'] ?? null) ? (float) $props['animationDuration'] : 0.8;
                if ($dur < 0) {
                    $dur = 0;
                }
                if ($dur > 30) {
                    $dur = 30;
                }

                $delay = is_numeric($props['animationDelay'] ?? null) ? (float) $props['animationDelay'] : 0;
                if ($delay < 0) {
                    $delay = 0;
                }
                if ($delay > 30) {
                    $delay = 30;
                }

                $easing = is_string($props['animationEasing'] ?? null) ? trim((string) $props['animationEasing']) : 'ease';
                if ($easing === '') {
                    $easing = 'ease';
                }
                if (strlen($easing) > 64) {
                    $easing = substr($easing, 0, 64);
                }

                $iterRaw = $props['animationIterationCount'] ?? 1;
                $iter = '1';
                if (is_string($iterRaw) && trim($iterRaw) === 'infinite') {
                    $iter = 'infinite';
                } elseif (is_numeric($iterRaw)) {
                    $n = (int) round((float) $iterRaw);
                    if ($n < 1) {
                        $n = 1;
                    }
                    if ($n > 99) {
                        $n = 99;
                    }
                    $iter = (string) $n;
                }

                $pingPong = ($props['animationPingPong'] ?? null) === true;
                if ($pingPong && $iter === '1') {
                    $iter = '2';
                }

                $fill = is_string($props['animationFillMode'] ?? null) ? trim((string) $props['animationFillMode']) : 'both';
                if (!in_array($fill, ['none', 'both', 'forwards', 'backwards'], true)) {
                    $fill = 'both';
                }

                $style .= 'animation-name:' . $name . ';';
                $style .= 'animation-duration:' . rtrim(rtrim(sprintf('%.2f', $dur), '0'), '.') . 's;';
                $style .= 'animation-delay:' . rtrim(rtrim(sprintf('%.2f', $delay), '0'), '.') . 's;';
                $style .= 'animation-timing-function:' . $easing . ';';
                $style .= 'animation-iteration-count:' . $iter . ';';
                $style .= 'animation-fill-mode:' . $fill . ';';
                if ($pingPong) {
                    $style .= 'animation-direction:alternate;';
                }
            }
        } elseif (in_array($preset, $allowedPresets, true)) {
            $dur = is_numeric($props['animationDuration'] ?? null) ? (float) $props['animationDuration'] : 0.8;
            if ($dur < 0) {
                $dur = 0;
            }
            if ($dur > 30) {
                $dur = 30;
            }

            $delay = is_numeric($props['animationDelay'] ?? null) ? (float) $props['animationDelay'] : 0;
            if ($delay < 0) {
                $delay = 0;
            }
            if ($delay > 30) {
                $delay = 30;
            }

            $easing = is_string($props['animationEasing'] ?? null) ? trim((string) $props['animationEasing']) : 'ease';
            if ($easing === '') {
                $easing = 'ease';
            }
            if (strlen($easing) > 64) {
                $easing = substr($easing, 0, 64);
            }

            $iterRaw = $props['animationIterationCount'] ?? 1;
            $iter = '1';
            if (is_string($iterRaw) && trim($iterRaw) === 'infinite') {
                $iter = 'infinite';
            } elseif (is_numeric($iterRaw)) {
                $n = (int) round((float) $iterRaw);
                if ($n < 1) {
                    $n = 1;
                }
                if ($n > 99) {
                    $n = 99;
                }
                $iter = (string) $n;
            }

            $fill = is_string($props['animationFillMode'] ?? null) ? trim((string) $props['animationFillMode']) : 'both';
            if (!in_array($fill, ['none', 'both', 'forwards', 'backwards'], true)) {
                $fill = 'both';
            }

            $style .= 'animation-name:' . $preset . ';';
            $style .= 'animation-duration:' . rtrim(rtrim(sprintf('%.2f', $dur), '0'), '.') . 's;';
            $style .= 'animation-delay:' . rtrim(rtrim(sprintf('%.2f', $delay), '0'), '.') . 's;';
            $style .= 'animation-timing-function:' . $easing . ';';
            $style .= 'animation-iteration-count:' . $iter . ';';
            $style .= 'animation-fill-mode:' . $fill . ';';
        }
        if ($style !== '') {
            $attrs .= ' style="' . e($style) . '"';
        }

        return ($widgetDisplayCss !== '' ? '<style>' . $widgetDisplayCss . '</style>' : '') . '<div' . $attrs . '>' . $html . '</div>';
    }

    private function safeKeyframeId($id): string
    {
        $s = is_string($id) ? $id : (is_numeric($id) ? (string) $id : '');
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $s) ?? '';
    }

    private function clampScale($v): float
    {
        $n = is_numeric($v) ? (float) $v : 1.0;
        if (!is_finite($n)) {
            $n = 1.0;
        }
        if ($n < 0.1) {
            $n = 0.1;
        }
        if ($n > 3) {
            $n = 3;
        }
        return $n;
    }

    private function formatFloat(float $n): string
    {
        return rtrim(rtrim(sprintf('%.2f', $n), '0'), '.');
    }

    private function transformString(int $tx, int $ty, int $rot, float $scale): string
    {
        return 'translate(' . $tx . 'px,' . $ty . 'px) rotate(' . $rot . 'deg) scale(' . $this->formatFloat($scale) . ')';
    }

    private function buildCustomMotionKeyframesCss(array $sections): string
    {
        $rules = '';
        $seen = [];

        $add = function ($id, $motionLike) use (&$rules, &$seen) {
            $m = is_array($motionLike) ? $motionLike : [];
            $preset = is_string($m['animationPreset'] ?? null) ? trim((string) $m['animationPreset']) : '';
            if ($preset !== 'custom') {
                return;
            }

            $safeId = $this->safeKeyframeId($id);
            if ($safeId === '') {
                return;
            }

            $name = 'mp-custom-' . $safeId;
            if (isset($seen[$name])) {
                return;
            }
            $seen[$name] = true;

            $fromTx = $this->intOrNull($m['animationFromTranslateX'] ?? null) ?? 0;
            $fromTy = $this->intOrNull($m['animationFromTranslateY'] ?? null) ?? 0;
            $fromRot = $this->intOrNull($m['animationFromRotate'] ?? null) ?? 0;
            $fromScale = $this->clampScale($m['animationFromScale'] ?? null);

            $fallbackToTx = $this->intOrNull($m['transformTranslateX'] ?? null) ?? 0;
            $fallbackToTy = $this->intOrNull($m['transformTranslateY'] ?? null) ?? 0;
            $fallbackToRot = $this->intOrNull($m['transformRotate'] ?? null) ?? 0;
            $fallbackToScale = $this->clampScale($m['transformScale'] ?? null);

            $toTx = $this->intOrNull($m['animationToTranslateX'] ?? null) ?? $fallbackToTx;
            $toTy = $this->intOrNull($m['animationToTranslateY'] ?? null) ?? $fallbackToTy;
            $toRot = $this->intOrNull($m['animationToRotate'] ?? null) ?? $fallbackToRot;

            $toScale = $fallbackToScale;
            if (($m['animationToScale'] ?? null) !== null && ($m['animationToScale'] ?? null) !== '') {
                $toScale = $this->clampScale($m['animationToScale'] ?? null);
            }

            $rules .= '@keyframes ' . $name . '{from{transform:' . $this->transformString($fromTx, $fromTy, $fromRot, $fromScale) . ';}to{transform:' . $this->transformString($toTx, $toTy, $toRot, $toScale) . ';}}';
        };

        $walkWidgets = function ($widgets) use (&$walkWidgets, $add) {
            if (!is_array($widgets)) {
                return;
            }
            foreach ($widgets as $w) {
                if (!is_array($w)) {
                    continue;
                }
                $add($w['id'] ?? '', is_array($w['props'] ?? null) ? $w['props'] : []);

                $type = is_string($w['type'] ?? null) ? trim((string) $w['type']) : '';
                if ($type === 'grid') {
                    $props = is_array($w['props'] ?? null) ? $w['props'] : [];
                    $cols = is_array($props['columns'] ?? null) ? $props['columns'] : [];
                    foreach ($cols as $gc) {
                        if (!is_array($gc)) {
                            continue;
                        }
                        $walkWidgets($gc['widgets'] ?? []);
                    }
                }
            }
        };

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }
            $add($section['id'] ?? '', is_array($section['style'] ?? null) ? $section['style'] : []);

            $columns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
            foreach ($columns as $col) {
                if (!is_array($col)) {
                    continue;
                }
                $walkWidgets($col['widgets'] ?? []);
            }
        }

        return $rules;
    }

    private function normalizeAdvanced(array $advanced): array
    {
        $id = $this->sanitizeCssId($advanced['cssId'] ?? null);
        $class = $this->sanitizeCssClasses($advanced['cssClasses'] ?? null);
        $position = $this->sanitizePosition($advanced['position'] ?? null);
        $display = $this->sanitizeDisplay($advanced['display'] ?? null);
        $displayTablet = $this->sanitizeDisplay($advanced['displayTablet'] ?? null);
        $displayMobile = $this->sanitizeDisplay($advanced['displayMobile'] ?? null);
        $textAlign = $this->sanitizeTextAlign($advanced['textAlign'] ?? null);
        $overflowAllowed = ['visible', 'hidden', 'scroll', 'auto', 'clip'];
        $overflowX = is_string($advanced['overflowX'] ?? null) && in_array($advanced['overflowX'], $overflowAllowed, true) ? $advanced['overflowX'] : '';
        $overflowY = is_string($advanced['overflowY'] ?? null) && in_array($advanced['overflowY'], $overflowAllowed, true) ? $advanced['overflowY'] : '';
        $alignItems = $this->sanitizeAlignItems($advanced['alignItems'] ?? null);
        $justifyContent = $this->sanitizeJustifyContent($advanced['justifyContent'] ?? null);
        $zIndex = $this->sanitizeZIndex($advanced['zIndex'] ?? null);

        $absXSide = $this->sanitizeAbsXSide($advanced['absXSide'] ?? null) ?? 'left';
        $absYSide = $this->sanitizeAbsYSide($advanced['absYSide'] ?? null) ?? 'top';
        $absXOffset = $this->sanitizeOffset($advanced['absXOffset'] ?? null);
        $absYOffset = $this->sanitizeOffset($advanced['absYOffset'] ?? null);

        $cache = is_string($advanced['cache'] ?? null) ? trim((string) $advanced['cache']) : '';
        $cache = $cache === 'no_cache' ? 'no_cache' : '';

        $width = null;
        if (($advanced['width'] ?? null) !== null && ($advanced['width'] ?? null) !== '') {
            $wn = is_numeric($advanced['width']) ? (int) $advanced['width'] : null;
            if ($wn !== null && $wn >= 0 && $wn <= 9999) {
                $width = $wn;
            }
        }

        $height = null;
        if (($advanced['height'] ?? null) !== null && ($advanced['height'] ?? null) !== '') {
            $hn = is_numeric($advanced['height']) ? (int) $advanced['height'] : null;
            if ($hn !== null && $hn >= 0 && $hn <= 9999) {
                $height = $hn;
            }
        }

        return [
            'id' => $id ?? '',
            'class' => $class ?? '',
            'position' => $position ?? '',
            'display' => $display ?? '',
            'displayTablet' => $displayTablet ?? '',
            'displayMobile' => $displayMobile ?? '',
            'textAlign' => $textAlign ?? '',
            'overflowX' => $overflowX,
            'overflowY' => $overflowY,
            'alignItems' => $alignItems ?? '',
            'justifyContent' => $justifyContent ?? '',
            'absXSide' => $absXSide,
            'absXOffset' => $absXOffset,
            'absYSide' => $absYSide,
            'absYOffset' => $absYOffset,
            'zIndex' => $zIndex,
            'width' => $width,
            'height' => $height,
            'cache' => $cache,
        ];
    }

    private function sanitizeCssId(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        if (strlen($v) > 120) {
            return null;
        }
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_\-:\.]*$/', $v)) {
            return null;
        }

        return $v;
    }

    private function sanitizeCssClasses(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $v) ?: [];
        $keep = [];
        foreach ($parts as $p) {
            $p = trim((string) $p);
            if ($p === '') {
                continue;
            }
            if (strlen($p) > 64) {
                continue;
            }
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_\-]*$/', $p)) {
                continue;
            }
            $keep[] = $p;
            if (count($keep) >= 20) {
                break;
            }
        }

        if (!$keep) {
            return null;
        }

        $joined = implode(' ', $keep);
        if (strlen($joined) > 240) {
            $joined = substr($joined, 0, 240);
        }
        return $joined;
    }

    private function sanitizePosition(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        return in_array($v, ['static', 'relative', 'absolute', 'fixed', 'sticky'], true) ? $v : null;
    }

    private function sanitizeDisplay(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        return in_array($v, ['block', 'inline', 'inline-block', 'flex', 'inline-flex', 'grid', 'inline-grid', 'none'], true) ? $v : null;
    }

    private function sanitizeTextAlign(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        return in_array($v, ['left', 'center', 'right', 'justify', 'start', 'end'], true) ? $v : null;
    }

    private function sanitizeAlignItems(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        return in_array($v, ['stretch', 'flex-start', 'center', 'flex-end', 'baseline', 'start', 'end', 'self-start', 'self-end'], true) ? $v : null;
    }

    private function sanitizeJustifyContent(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        return in_array($v, ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly', 'start', 'end', 'left', 'right'], true) ? $v : null;
    }

    private function sanitizeZIndex(mixed $value): ?int
    {
        $n = $this->intOrNull($value);
        if ($n === null) {
            return null;
        }

        if ($n < -9999) {
            $n = -9999;
        }
        if ($n > 9999) {
            $n = 9999;
        }

        return $n;
    }

    private function sanitizeOffset(mixed $value): ?int
    {
        $n = $this->intOrNull($value);
        if ($n === null) {
            return null;
        }

        if ($n < -9999) {
            $n = -9999;
        }
        if ($n > 9999) {
            $n = 9999;
        }

        return $n;
    }

    private function sanitizeAbsXSide(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $v = trim((string) $value);
        return in_array($v, ['left', 'right'], true) ? $v : null;
    }

    private function sanitizeAbsYSide(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $v = trim((string) $value);
        return in_array($v, ['top', 'bottom'], true) ? $v : null;
    }

    private function renderGrid(array $props, array $style = [], string $widgetId = ''): string
    {
        $count = $this->intOrNull($props['count'] ?? null) ?? 2;
        if ($count < 1) {
            $count = 1;
        }
        if ($count > 4) {
            $count = 4;
        }

        $gap = $this->intOrNull($style['gap'] ?? null) ?? 0;
        if ($gap < 0) {
            $gap = 0;
        }

        $direction = is_string($style['itemsDirection'] ?? null) && in_array($style['itemsDirection'], ['row', 'column', 'row-reverse', 'column-reverse'], true)
            ? $style['itemsDirection'] : 'row';
        $justify = is_string($style['itemsJustify'] ?? null) && in_array($style['itemsJustify'], ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], true)
            ? $style['itemsJustify'] : 'flex-start';
        $align = is_string($style['itemsAlign'] ?? null) && in_array($style['itemsAlign'], ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'], true)
            ? $style['itemsAlign'] : 'stretch';
        $gapX = is_numeric($style['gapX'] ?? null) ? max(0, (float) $style['gapX']) : $gap;
        $gapY = is_numeric($style['gapY'] ?? null) ? max(0, (float) $style['gapY']) : $gap;

        $columns = $props['columns'] ?? [];
        if (!is_array($columns)) {
            $columns = [];
        }

        $gridStyle = 'display:flex;flex-wrap:wrap;flex-direction:' . $direction . ';justify-content:' . $justify . ';align-items:' . $align . ';width:100%;';
        if ($direction === 'row' || $direction === 'row-reverse') {
            $gridStyle .= 'column-gap:' . $gapX . 'px;';
        }
        $gridStyle .= 'row-gap:' . $gapY . 'px;';
        $gridStyle .= '--mp-gap-x:' . (string) $gapX . 'px;';
        $gridClasses = in_array($direction, ['column', 'column-reverse'], true) ? 'mp-grid mp-grid--column' : 'mp-grid';

        $gridItemsScope = $this->responsiveWidthScope();
        $gridItemsCss = $this->responsiveSectionItemsCss($style, $gridItemsScope);
        if ($gridItemsCss !== '') {
            $gridClasses .= ' ' . $gridItemsScope;
        }

        $gridColumnsScope = $this->responsiveWidthScope('cols-' . $widgetId);
        $gridColumnsCss = '';
        if ($gridColumnsScope !== '') {
            $gridColumnsCss = $this->responsiveColumnsPerRowCss(
                $style,
                '.mp-grid.' . $gridColumnsScope . '.' . $gridColumnsScope,
                '> .mp-col',
                $gapX,
                $count,
                $gapY
            );
            if ($gridColumnsCss !== '') {
                $gridClasses .= ' ' . $gridColumnsScope;
            }
        }

        // Widget-level container look (mirrors the canvas containerInnerStyle):
        // background, width constraint, padding, margin, border, radius, height.
        $gridStyle .= $this->buildSectionStyle($style);

        $gPadTop = $this->intOrNull($style['paddingTop'] ?? null) ?? $this->intOrNull($style['paddingY'] ?? null) ?? 0;
        $gPadRight = $this->intOrNull($style['paddingRight'] ?? null) ?? $this->intOrNull($style['paddingX'] ?? null) ?? 0;
        $gPadBottom = $this->intOrNull($style['paddingBottom'] ?? null) ?? $this->intOrNull($style['paddingY'] ?? null) ?? 0;
        $gPadLeft = $this->intOrNull($style['paddingLeft'] ?? null) ?? $this->intOrNull($style['paddingX'] ?? null) ?? 0;
        if ($gPadTop !== 0 || $gPadRight !== 0 || $gPadBottom !== 0 || $gPadLeft !== 0) {
            $gridStyle .= 'padding:' . $gPadTop . 'px ' . $gPadRight . 'px ' . $gPadBottom . 'px ' . $gPadLeft . 'px;';
        }
        $gridStyle .= 'box-sizing:border-box;';
        foreach (['Top' => 'top', 'Right' => 'right', 'Bottom' => 'bottom', 'Left' => 'left'] as $mKey => $mSide) {
            $mFallback = ($mKey === 'Top' || $mKey === 'Bottom') ? ($style['marginY'] ?? null) : ($style['marginX'] ?? null);
            $m = $this->intOrNull($style['margin' . $mKey] ?? null) ?? $this->intOrNull($mFallback);
            if ($m !== null) {
                $gridStyle .= 'margin-' . $mSide . ':' . $m . 'px;';
            }
        }
        if (is_string($style['borderStyle'] ?? null) && trim((string) $style['borderStyle']) !== '' && ($style['borderStyle'] ?? '') !== 'default'
            && in_array((string) $style['borderStyle'], ['solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset'], true)) {
            $gridStyle .= 'border-style:' . $style['borderStyle'] . ';';
        }
        $gridBorderColor = $this->sanitizeColor($style['borderColor'] ?? null);
        if ($gridBorderColor !== null) {
            $gridStyle .= 'border-color:' . $gridBorderColor . ';';
        }
        foreach (['Top' => 'top', 'Right' => 'right', 'Bottom' => 'bottom', 'Left' => 'left'] as $bKey => $bSide) {
            $bw = $this->intOrNull($style['borderWidth' . $bKey] ?? null);
            if ($bw !== null && $bw >= 0) {
                $gridStyle .= 'border-' . $bSide . '-width:' . $bw . 'px;';
            }
        }
        foreach (['Top' => 'top-left', 'Right' => 'top-right', 'Bottom' => 'bottom-right', 'Left' => 'bottom-left'] as $rKey => $rCorner) {
            $br = $this->intOrNull($style['borderRadius' . $rKey] ?? null) ?? $this->intOrNull($style['borderRadius'] ?? null);
            if ($br !== null && $br >= 0) {
                $gridStyle .= 'border-' . $rCorner . '-radius:' . $br . 'px;';
            }
        }
        $gridHeight = $this->intOrNull($style['height'] ?? null);
        if ($gridHeight !== null && $gridHeight > 0) {
            $gridStyle .= 'height:' . $gridHeight . 'px;';
        }

        // Container width constraint (mirrors the canvas containerInnerStyle logic).
        $gMaxWidthTypeRaw = is_string($style['maxWidthType'] ?? null) ? trim((string) $style['maxWidthType']) : '';
        $gMaxWidthType = $gMaxWidthTypeRaw !== ''
            ? $gMaxWidthTypeRaw
            : ((is_string($style['maxWidth'] ?? null) && trim((string) $style['maxWidth']) === 'full') ? 'full' : 'boxed');
        $gWidthCss = '';
        if ($gMaxWidthType === 'full') {
            $gWidthCss = $this->cssLength($style['innerMaxWidth'] ?? null, '');
            if ($gWidthCss === '' && $gMaxWidthTypeRaw === 'full') {
                $gWidthCss = '1152px';
            }
        } else {
            $gWidthCss = $this->cssLength($style['boxedWidth'] ?? null, '');
            if ($gWidthCss === '' && $gMaxWidthTypeRaw === 'boxed') {
                $gWidthCss = '600px';
            }
        }
        $gridWidthScope = $this->responsiveWidthScope();
        $gridWidthCss = $this->responsiveContainerWidthCss($style, $gridWidthScope, $gMaxWidthType);
        if ($gridWidthCss !== '') {
            $gridClasses .= ' ' . $gridWidthScope;
        }

        if ($gWidthCss !== '') {
            $gridStyle .= 'max-width:' . $gWidthCss . ';';
            $gmLeft = $this->intOrNull($style['marginLeft'] ?? null) ?? $this->intOrNull($style['marginX'] ?? null);
            $gmRight = $this->intOrNull($style['marginRight'] ?? null) ?? $this->intOrNull($style['marginX'] ?? null);
            if (($gmLeft === null || $gmLeft === 0) && ($gmRight === null || $gmRight === 0)) {
                $gridStyle .= 'margin-left:auto;margin-right:auto;';
            }
        }

        $gridHeader = '';
        $gridScopeAttr = '';
        $gridHasPricing = false;
        foreach ($columns as $col) {
            if ($this->columnContainsPricingTable($col, false)) {
                $gridHasPricing = true;
                break;
            }
        }
        if ($gridHasPricing) {
            $gTabs = is_array($props['pricingTabs'] ?? null) ? $props['pricingTabs'] : [];
            $gStyle = is_array($props['pricingStyle'] ?? null) ? $props['pricingStyle'] : [];
            $gMode = is_string($props['pricingMode'] ?? null) ? trim((string) $props['pricingMode']) : '';
            if (!in_array($gMode, ['switcher', 'tabs'], true)) {
                $gMode = count($gTabs) > 0 ? 'tabs' : 'switcher';
            }
            $gridHeader = $gMode === 'tabs'
                ? $this->renderPricingTabs($gTabs, $gStyle)
                : $this->renderPricingToggle($gStyle);
            $gridScopeAttr = ' x-data="{ annual: false }"';
            $out = '<div' . $gridScopeAttr . '>';
            $out .= $gridHeader;
            $prevPricingScope = $this->inPricingScope;
            $this->inPricingScope = true;
        } else {
            $out = '';
            $prevPricingScope = $this->inPricingScope;
        }

        $out .= ($gridWidthCss !== '' ? '<style>' . $gridWidthCss . '</style>' : '')
            . ($gridItemsCss !== '' ? '<style>' . $gridItemsCss . '</style>' : '')
            . ($gridColumnsCss !== '' ? '<style>' . $gridColumnsCss . '</style>' : '')
            . '<div class="' . e($gridClasses) . '"' . ($gridStyle !== '' ? (' style="' . e($gridStyle) . '"') : '') . '>';

        $colCtx = ['mode' => 'grid', 'direction' => $direction, 'justify' => $justify, 'align' => $align, 'gapX' => $gapX];
        $rendered = 0;
        foreach ($columns as $col) {
            if ($rendered >= $count) {
                break;
            }
            if (!is_array($col)) {
                continue;
            }
            $out .= $this->renderColumn($col, $colCtx);
            $rendered++;
        }

        $this->inPricingScope = $prevPricingScope;

        $out .= '</div>';
        if ($gridHasPricing) {
            $out .= '</div>';
        }

        if (($props['mobileMenu'] ?? null) === true) {
            $out = $this->wrapMobileMenu($out, $props);
        }

        return $out;
    }

    private function mobileMenuToggleIcon(array $p, int $size): string
    {
        $url = is_string($p['mobileMenuToggleIconUrl'] ?? null) ? trim((string) $p['mobileMenuToggleIconUrl']) : '';
        $svg = is_string($p['mobileMenuToggleIconSvg'] ?? null) ? trim((string) $p['mobileMenuToggleIconSvg']) : '';
        $source = $p['mobileMenuToggleIconSource'] ?? '';
        $source = $source === 'svg' || $source === 'url' ? $source : '';

        if ($svg !== '' && ($source === 'svg' || $url === '')) {
            $safe = preg_replace('/<script[\s\S]*?<\/script>/i', '', $svg);
            $safe = is_string($safe) ? $safe : '';
            if ($safe !== '' && stripos($safe, '<svg') !== false) {
                if (preg_match('/<svg\b[^>]*\bwidth\s*=/i', $safe)) {
                    return $safe;
                }
                return preg_replace('/<svg\b/i', '<svg width="' . $size . '" height="' . $size . '"', $safe, 1) ?? $safe;
            }
        }

        if ($url !== '') {
            return '<img src="' . e($url) . '" alt="" style="width:' . $size . 'px;height:' . $size . 'px;object-fit:contain;display:block;" />';
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>';
    }

    private function mobileMenuDrawerStyle(array $p): string
    {
        $out = '';

        $bgType = (($p['mobileMenuBackgroundType'] ?? 'color') === 'gradient') ? 'gradient' : 'color';
        if ($bgType === 'gradient') {
            $from = $this->sanitizeColor($p['mobileMenuGradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($p['mobileMenuGradientTo'] ?? null) ?? '#3b82f6';
            $type = (($p['mobileMenuGradientType'] ?? 'linear') === 'radial') ? 'radial' : 'linear';
            if ($type === 'radial') {
                $pos = is_string($p['mobileMenuGradientPosition'] ?? null) ? trim((string) $p['mobileMenuGradientPosition']) : 'top center';
                $out .= 'background-image:radial-gradient(at ' . $pos . ', ' . $from . ', ' . $to . ');';
            } else {
                $angle = $this->intOrNull($p['mobileMenuGradientAngle'] ?? null) ?? 135;
                $out .= 'background-image:linear-gradient(' . $angle . 'deg, ' . $from . ', ' . $to . ');';
            }
        } else {
            $color = $this->sanitizeColor($p['mobileMenuBackgroundColor'] ?? null) ?? '#ffffff';
            $out .= 'background-color:' . $color . ';';
        }

        $bw = [
            'top' => $this->intOrNull($p['mobileMenuBorderWidthTop'] ?? null) ?? 0,
            'right' => $this->intOrNull($p['mobileMenuBorderWidthRight'] ?? null) ?? 0,
            'bottom' => $this->intOrNull($p['mobileMenuBorderWidthBottom'] ?? null) ?? 0,
            'left' => $this->intOrNull($p['mobileMenuBorderWidthLeft'] ?? null) ?? 0,
        ];
        if ($bw['top'] > 0 || $bw['right'] > 0 || $bw['bottom'] > 0 || $bw['left'] > 0) {
            $borderStyle = is_string($p['mobileMenuBorderStyle'] ?? null) && in_array($p['mobileMenuBorderStyle'], ['solid', 'dashed', 'dotted'], true)
                ? $p['mobileMenuBorderStyle'] : 'solid';
            $borderColor = $this->sanitizeColor($p['mobileMenuBorderColor'] ?? null) ?? '#e5e7eb';
            $out .= 'border-style:' . $borderStyle . ';border-color:' . $borderColor . ';';
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                if ($bw[$side] > 0) {
                    $out .= 'border-' . $side . '-width:' . $bw[$side] . 'px;';
                }
            }
        }

        $radiusMap = [
            'top-left' => $this->intOrNull($p['mobileMenuBorderRadiusTop'] ?? null),
            'top-right' => $this->intOrNull($p['mobileMenuBorderRadiusRight'] ?? null),
            'bottom-right' => $this->intOrNull($p['mobileMenuBorderRadiusBottom'] ?? null),
            'bottom-left' => $this->intOrNull($p['mobileMenuBorderRadiusLeft'] ?? null),
        ];
        foreach ($radiusMap as $corner => $val) {
            if ($val !== null) {
                $out .= 'border-' . $corner . '-radius:' . $val . 'px;';
            }
        }

        $padMap = [
            'top' => $this->intOrNull($p['mobileMenuPaddingTop'] ?? null),
            'right' => $this->intOrNull($p['mobileMenuPaddingRight'] ?? null),
            'bottom' => $this->intOrNull($p['mobileMenuPaddingBottom'] ?? null),
            'left' => $this->intOrNull($p['mobileMenuPaddingLeft'] ?? null),
        ];
        foreach ($padMap as $side => $val) {
            if ($val !== null) {
                $out .= 'padding-' . $side . ':' . $val . 'px;';
            }
        }

        return $out;
    }

    private function wrapMobileMenu(string $inner, array $props): string
    {
        $direction = (($props['mobileMenuDirection'] ?? 'right') === 'left') ? 'left' : 'right';
        $animation = in_array(($props['mobileMenuAnimation'] ?? 'slide'), ['slide', 'fade', 'scale', 'none'], true)
            ? $props['mobileMenuAnimation'] : 'slide';

        $iconSize = $this->intOrNull($props['mobileMenuToggleIconSize'] ?? null) ?? 28;
        $iconSize = max(12, min(96, $iconSize));

        $icon = $this->mobileMenuToggleIcon($props, $iconSize);

        $closeColor = $this->sanitizeColor($props['mobileMenuCloseColor'] ?? null) ?? '';
        $closeStyle = 'position:absolute;top:12px;right:12px;background:transparent;border:0;font-size:24px;line-height:1;cursor:pointer;padding:4px;'
            . ($closeColor !== '' ? 'color:' . $closeColor . ';' : '');

        $overlay = is_string($props['mobileMenuOverlayColor'] ?? null) ? trim((string) $props['mobileMenuOverlayColor']) : '';
        if ($overlay === '') {
            $overlay = 'rgba(17,24,39,0.5)';
        }
        $overlay = $this->sanitizeColor($overlay) ?? 'rgba(17,24,39,0.5)';

        $drawerStyle = $this->mobileMenuDrawerStyle($props);

        return '<div class="mp-mobile-menu" data-direction="' . e($direction) . '" data-animation="' . e($animation) . '">'
            . '<button type="button" class="mp-mobile-menu__toggle" aria-label="Open menu" aria-expanded="false" style="display:inline-flex;align-items:center;justify-content:center;background:transparent;border:0;padding:8px;cursor:pointer;">' . $icon . '</button>'
            . '<div class="mp-mobile-menu__overlay" style="background:' . e($overlay) . ';"></div>'
            . '<div class="mp-mobile-menu__drawer" data-direction="' . e($direction) . '" data-animation="' . e($animation) . '" style="' . e($drawerStyle) . '">'
            . '<button type="button" class="mp-mobile-menu__close" aria-label="Close menu" style="' . e($closeStyle) . '">&times;</button>'
            . '<div class="mp-mobile-menu__content">' . $inner . '</div>'
            . '</div>'
            . '</div>';
    }

    private function renderTabs(array $props): string
    {
        $items = is_array($props['items'] ?? null) ? $props['items'] : [];
        if (count($items) === 0) {
            return '';
        }

        $orientation = is_string($props['orientation'] ?? null) ? trim((string) $props['orientation']) : 'horizontal';
        $orientation = in_array($orientation, ['horizontal', 'vertical', 'timeline'], true) ? $orientation : 'horizontal';
        $isHorizontal = $orientation === 'horizontal';
        $isVertical = $orientation === 'vertical';

        $activeIndex = $this->intOrNull($props['activeIndex'] ?? null) ?? 0;
        if ($activeIndex < 0 || $activeIndex >= count($items)) {
            $activeIndex = 0;
        }

        $tabGap = $this->intOrNull($props['tabGap'] ?? null) ?? 0;
        if ($tabGap < 0) {
            $tabGap = 0;
        }
        if ($tabGap > 120) {
            $tabGap = 120;
        }

        $tabPaddingX = $this->intOrNull($props['tabPaddingX'] ?? null) ?? 16;
        $tabPaddingY = $this->intOrNull($props['tabPaddingY'] ?? null) ?? 10;
        $contentPadding = $this->intOrNull($props['contentPadding'] ?? null) ?? 16;

        $tabTextColor = $this->sanitizeColor($props['tabTextColor'] ?? null) ?? '#6b7280';
        $tabBackgroundColor = $this->sanitizeColor($props['tabBackgroundColor'] ?? null) ?? 'transparent';
        $activeTabTextColor = $this->sanitizeColor($props['activeTabTextColor'] ?? null) ?? '#111827';
        $activeTabBackgroundColor = $this->sanitizeColor($props['activeTabBackgroundColor'] ?? null) ?? 'transparent';
        $activeTabBorderColor = $this->sanitizeColor($props['activeTabBorderColor'] ?? null) ?? '#2563eb';
        $contentBackgroundColor = $this->sanitizeColor($props['contentBackgroundColor'] ?? null) ?? 'transparent';
        $contentTextColor = $this->sanitizeColor($props['contentTextColor'] ?? null) ?? '#374151';
        $tabListBorderColor = $this->sanitizeColor($props['tabListBorderColor'] ?? null) ?? '#e5e7eb';

        $wrapperStyle = '';
        if (isset($props['marginTop']) && is_numeric($props['marginTop'])) {
            $wrapperStyle .= 'margin-top:' . (int) $props['marginTop'] . 'px;';
        }
        if (isset($props['marginRight']) && is_numeric($props['marginRight'])) {
            $wrapperStyle .= 'margin-right:' . (int) $props['marginRight'] . 'px;';
        }
        if (isset($props['marginBottom']) && is_numeric($props['marginBottom'])) {
            $wrapperStyle .= 'margin-bottom:' . (int) $props['marginBottom'] . 'px;';
        }
        if (isset($props['marginLeft']) && is_numeric($props['marginLeft'])) {
            $wrapperStyle .= 'margin-left:' . (int) $props['marginLeft'] . 'px;';
        }
        if (isset($props['paddingTop']) && is_numeric($props['paddingTop'])) {
            $wrapperStyle .= 'padding-top:' . (int) $props['paddingTop'] . 'px;';
        }
        if (isset($props['paddingRight']) && is_numeric($props['paddingRight'])) {
            $wrapperStyle .= 'padding-right:' . (int) $props['paddingRight'] . 'px;';
        }
        if (isset($props['paddingBottom']) && is_numeric($props['paddingBottom'])) {
            $wrapperStyle .= 'padding-bottom:' . (int) $props['paddingBottom'] . 'px;';
        }
        if (isset($props['paddingLeft']) && is_numeric($props['paddingLeft'])) {
            $wrapperStyle .= 'padding-left:' . (int) $props['paddingLeft'] . 'px;';
        }

        $bgType = is_string($props['backgroundType'] ?? null) ? trim((string) $props['backgroundType']) : 'classic';
        if ($bgType === 'gradient') {
            $from = $this->sanitizeColor($props['gradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($props['gradientTo'] ?? null) ?? '#3b82f6';
            $fromLoc = $this->intOrNull($props['gradientFromLocation'] ?? null) ?? 0;
            $toLoc = $this->intOrNull($props['gradientToLocation'] ?? null) ?? 100;
            $gradientType = is_string($props['gradientType'] ?? null) ? trim((string) $props['gradientType']) : 'linear';
            $gradientType = in_array($gradientType, ['linear', 'radial'], true) ? $gradientType : 'linear';
            $position = is_string($props['gradientPosition'] ?? null) && trim($props['gradientPosition']) !== '' ? trim($props['gradientPosition']) : 'top center';
            if ($gradientType === 'radial') {
                $wrapperStyle .= 'background-image:radial-gradient(at ' . $position . ', ' . $from . ' ' . $fromLoc . '%, ' . $to . ' ' . $toLoc . '%);';
            } else {
                $wrapperStyle .= 'background-image:linear-gradient(135deg, ' . $from . ' ' . $fromLoc . '%, ' . $to . ' ' . $toLoc . '%);';
            }
        } else {
            $bgColor = $this->sanitizeColor($props['backgroundColor'] ?? null);
            if ($bgColor !== null) {
                $wrapperStyle .= 'background-color:' . $bgColor . ';';
            }
        }

        if (isset($props['borderColor']) && is_string($props['borderColor']) && $props['borderColor'] !== '') {
            $wrapperStyle .= 'border-color:' . $this->sanitizeColor($props['borderColor']) . ';';
        }
        $borderStyle = is_string($props['borderStyle'] ?? null) ? trim((string) $props['borderStyle']) : 'default';
        if (in_array($borderStyle, ['solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset'], true)) {
            $wrapperStyle .= 'border-style:' . $borderStyle . ';';
        }
        if (isset($props['borderWidthTop']) && is_numeric($props['borderWidthTop'])) {
            $wrapperStyle .= 'border-top-width:' . (int) $props['borderWidthTop'] . 'px;';
        }
        if (isset($props['borderWidthRight']) && is_numeric($props['borderWidthRight'])) {
            $wrapperStyle .= 'border-right-width:' . (int) $props['borderWidthRight'] . 'px;';
        }
        if (isset($props['borderWidthBottom']) && is_numeric($props['borderWidthBottom'])) {
            $wrapperStyle .= 'border-bottom-width:' . (int) $props['borderWidthBottom'] . 'px;';
        }
        if (isset($props['borderWidthLeft']) && is_numeric($props['borderWidthLeft'])) {
            $wrapperStyle .= 'border-left-width:' . (int) $props['borderWidthLeft'] . 'px;';
        }
        if (isset($props['borderRadiusTop']) && is_numeric($props['borderRadiusTop'])) {
            $wrapperStyle .= 'border-top-left-radius:' . (int) $props['borderRadiusTop'] . 'px;';
        }
        if (isset($props['borderRadiusRight']) && is_numeric($props['borderRadiusRight'])) {
            $wrapperStyle .= 'border-top-right-radius:' . (int) $props['borderRadiusRight'] . 'px;';
        }
        if (isset($props['borderRadiusBottom']) && is_numeric($props['borderRadiusBottom'])) {
            $wrapperStyle .= 'border-bottom-right-radius:' . (int) $props['borderRadiusBottom'] . 'px;';
        }
        if (isset($props['borderRadiusLeft']) && is_numeric($props['borderRadiusLeft'])) {
            $wrapperStyle .= 'border-bottom-left-radius:' . (int) $props['borderRadiusLeft'] . 'px;';
        }

        $containerStyle = 'display:flex;';
        if ($isVertical) {
            $containerStyle .= 'flex-direction:row;gap:16px;';
        } else {
            $containerStyle .= 'flex-direction:column;';
        }

        $listStyle = 'display:flex;gap:' . $tabGap . 'px;list-style:none;margin:0;padding:0;';
        if ($isHorizontal) {
            $listStyle .= 'flex-direction:row;border-bottom:1px solid ' . $tabListBorderColor . ';';
        } elseif ($isVertical) {
            $listStyle .= 'flex-direction:column;border-right:1px solid ' . $tabListBorderColor . ';min-width:120px;flex-shrink:0;padding-right:12px;';
        } else {
            $listStyle .= 'flex-direction:row;border-bottom:1px solid ' . $tabListBorderColor . ';';
        }

        $baseBtnStyle = 'padding:' . $tabPaddingY . 'px ' . $tabPaddingX . 'px;border:none;cursor:pointer;text-align:left;transition:color 0.2s,background-color 0.2s;white-space:nowrap;display:flex;align-items:center;gap:8px;background:transparent;';
        if ($isHorizontal) {
            $baseBtnStyle .= 'border-bottom:2px solid transparent;';
        } else {
            $baseBtnStyle .= 'border-left:3px solid transparent;width:100%;';
        }

        $inactiveStyle = $baseBtnStyle . 'color:' . $tabTextColor . ';background-color:' . $tabBackgroundColor . ';';
        $activeStyle = $baseBtnStyle . 'color:' . $activeTabTextColor . ';background-color:' . $activeTabBackgroundColor . ';';
        if ($isHorizontal) {
            $activeStyle .= 'border-bottom-color:' . $activeTabBorderColor . ';';
        } else {
            $activeStyle .= 'border-left-color:' . $activeTabBorderColor . ';';
        }

        $out = '<div class="mp-tabs" x-data="{ active: ' . $activeIndex . ' }" style="' . e($wrapperStyle) . '">';
        $out .= '<div class="mp-tabs__container" style="' . e($containerStyle) . '">';
        $out .= '<div class="mp-tabs__list" role="tablist" style="' . e($listStyle) . '">';

        foreach ($items as $idx => $it) {
            if (!is_array($it)) {
                continue;
            }
            $title = is_string($it['title'] ?? null) ? trim((string) $it['title']) : ('Tab ' . ($idx + 1));
            $out .= '<button type="button" role="tab" @click="active = ' . $idx . '" :aria-selected="active === ' . $idx . '"';
            $out .= ' :style="active === ' . $idx . ' ? \'' . $activeStyle . '\' : \'' . $inactiveStyle . '\'"';
            $out .= '><span>' . e($title) . '</span></button>';
        }

        $out .= '</div>';
        $out .= '<div class="mp-tabs__panels" style="flex:1;">';

        foreach ($items as $idx => $it) {
            if (!is_array($it)) {
                continue;
            }
            $panelStyle = 'padding:' . $contentPadding . 'px;background-color:' . $contentBackgroundColor . ';color:' . $contentTextColor . ';';

            $widgets = is_array($it['widgets'] ?? null) ? $it['widgets'] : [];
            $contentHtml = '';
            foreach ($widgets as $w) {
                if (!is_array($w)) {
                    continue;
                }
                $contentHtml .= $this->renderWidget($w);
            }

            $textContent = is_string($it['content'] ?? null) ? (string) $it['content'] : '';
            if ($contentHtml === '' && $textContent !== '') {
                $contentHtml = nl2br(e($textContent), false);
            }

            $out .= '<div class="mp-tabs__panel" role="tabpanel" x-show="active === ' . $idx . '" style="' . e($panelStyle) . '">';
            $out .= $contentHtml;
            $out .= '</div>';
        }

        $out .= '</div>';
        $out .= '</div>';
        $out .= '</div>';

        return $out;
    }

    private function renderHeading(array $props): string
    {
        $html = is_string($props['html'] ?? null) ? (string) $props['html'] : '';
        $text = is_string($props['text'] ?? null) ? (string) $props['text'] : '';
        $level = $this->intOrNull($props['level'] ?? null) ?? 2;
        if ($level < 1 || $level > 6) {
            $level = 2;
        }

        $align = $this->sanitizeAlign($props['align'] ?? null) ?? 'left';
        $color = $this->sanitizeColor($props['color'] ?? null);
        $fontFamily = $this->sanitizeFontFamily($props['fontFamily'] ?? null);
        $fontWeight = $this->sanitizeFontWeight($props['fontWeight'] ?? null);
        $fontSize = $this->cssLength($props['fontSize'] ?? null, '');
        $lineHeight = $this->cssLineHeight($props['lineHeight'] ?? null, '');
        $letterSpacing = $this->cssLength($props['letterSpacing'] ?? null, '');
        $wordSpacing = $this->cssLength($props['wordSpacing'] ?? null, '');

        $tag = 'h' . $level;
        $defaultFontSize = match ($level) {
            1 => 44,
            2 => 36,
            3 => 30,
            4 => 24,
            5 => 20,
            default => 18,
        };

        $fontSize = $fontSize !== '' ? $fontSize : $defaultFontSize . 'px';

        $finalWeight = $fontWeight !== null ? $fontWeight : 600;

        $inline = 'text-align:var(--mp-typo-textAlign,' . $align . ');font-weight:' . $finalWeight . ';font-size:var(--mp-typo-fontSize,' . e($fontSize) . ');';

        if ($fontFamily !== null) {
            $inline .= 'font-family:' . $fontFamily . ';';
        }
        $inline .= 'line-height:var(--mp-typo-lineHeight,' . ($lineHeight !== '' ? e($lineHeight) : '1.15') . ');';
        if ($letterSpacing !== '') {
            $inline .= 'letter-spacing:' . e($letterSpacing) . ';';
        }
        if ($wordSpacing !== '') {
            $inline .= 'word-spacing:' . e($wordSpacing) . ';';
        }
        if ($color !== null) {
            $inline .= 'color:' . $color . ';';
        }

        if (isset($props['marginTop']) && is_numeric($props['marginTop'])) {
            $inline .= 'margin-top:' . (int) $props['marginTop'] . 'px;';
        }
        if (isset($props['marginRight']) && is_numeric($props['marginRight'])) {
            $inline .= 'margin-right:' . (int) $props['marginRight'] . 'px;';
        }
        if (isset($props['marginBottom']) && is_numeric($props['marginBottom'])) {
            $inline .= 'margin-bottom:' . (int) $props['marginBottom'] . 'px;';
        }
        if (isset($props['marginLeft']) && is_numeric($props['marginLeft'])) {
            $inline .= 'margin-left:' . (int) $props['marginLeft'] . 'px;';
        }

        if (isset($props['paddingTop']) && is_numeric($props['paddingTop'])) {
            $inline .= 'padding-top:' . (int) $props['paddingTop'] . 'px;';
        }
        if (isset($props['paddingRight']) && is_numeric($props['paddingRight'])) {
            $inline .= 'padding-right:' . (int) $props['paddingRight'] . 'px;';
        }
        if (isset($props['paddingBottom']) && is_numeric($props['paddingBottom'])) {
            $inline .= 'padding-bottom:' . (int) $props['paddingBottom'] . 'px;';
        }
        if (isset($props['paddingLeft']) && is_numeric($props['paddingLeft'])) {
            $inline .= 'padding-left:' . (int) $props['paddingLeft'] . 'px;';
        }

        $bgType = is_string($props['backgroundType'] ?? null) ? trim((string) $props['backgroundType']) : 'classic';
        $bgType = in_array($bgType, ['classic', 'gradient'], true) ? $bgType : 'classic';
        if ($bgType === 'gradient') {
            $from = $this->sanitizeColor($props['gradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($props['gradientTo'] ?? null) ?? '#3b82f6';
            $fromLoc = $this->intOrNull($props['gradientFromLocation'] ?? null) ?? 0;
            $toLoc = $this->intOrNull($props['gradientToLocation'] ?? null) ?? 100;
            if ($fromLoc < 0) {
                $fromLoc = 0;
            }
            if ($fromLoc > 100) {
                $fromLoc = 100;
            }
            if ($toLoc < 0) {
                $toLoc = 0;
            }
            if ($toLoc > 100) {
                $toLoc = 100;
            }

            $gType = is_string($props['gradientType'] ?? null) ? trim((string) $props['gradientType']) : 'linear';
            $gType = in_array($gType, ['linear', 'radial'], true) ? $gType : 'linear';
            if ($gType === 'radial') {
                $position = is_string($props['gradientPosition'] ?? null) ? trim((string) $props['gradientPosition']) : '';
                $allowedPositions = [
                    'top left',
                    'top center',
                    'top right',
                    'center left',
                    'center center',
                    'center right',
                    'bottom left',
                    'bottom center',
                    'bottom right',
                ];
                if (!in_array($position, $allowedPositions, true)) {
                    $position = 'top center';
                }
                $inline .= 'background-image:radial-gradient(at ' . $position . ', ' . $from . ' ' . (string) $fromLoc . '%, ' . $to . ' ' . (string) $toLoc . '%);';
            } else {
                $inline .= 'background-image:linear-gradient(135deg, ' . $from . ' ' . (string) $fromLoc . '%, ' . $to . ' ' . (string) $toLoc . '%);';
            }
        } else {
            $bg = $this->sanitizeColor($props['backgroundColor'] ?? null);
            if ($bg !== null) {
                $inline .= 'background-color:' . $bg . ';';
            }
        }

        // Border
        if (isset($props['borderStyle']) && is_string($props['borderStyle']) && $props['borderStyle'] !== '' && $props['borderStyle'] !== 'default') {
            $inline .= 'border-style:' . e($props['borderStyle']) . ';';
        }
        if (isset($props['borderColor']) && is_string($props['borderColor']) && $props['borderColor'] !== '') {
            $inline .= 'border-color:' . e($props['borderColor']) . ';';
        }
        if (isset($props['borderWidthTop']) && is_numeric($props['borderWidthTop'])) {
            $inline .= 'border-top-width:' . (int)$props['borderWidthTop'] . 'px;';
        }
        if (isset($props['borderWidthRight']) && is_numeric($props['borderWidthRight'])) {
            $inline .= 'border-right-width:' . (int)$props['borderWidthRight'] . 'px;';
        }
        if (isset($props['borderWidthBottom']) && is_numeric($props['borderWidthBottom'])) {
            $inline .= 'border-bottom-width:' . (int)$props['borderWidthBottom'] . 'px;';
        }
        if (isset($props['borderWidthLeft']) && is_numeric($props['borderWidthLeft'])) {
            $inline .= 'border-left-width:' . (int)$props['borderWidthLeft'] . 'px;';
        }
        if (isset($props['borderRadiusTop']) && is_numeric($props['borderRadiusTop'])) {
            $inline .= 'border-top-left-radius:' . (int)$props['borderRadiusTop'] . 'px;';
        }
        if (isset($props['borderRadiusRight']) && is_numeric($props['borderRadiusRight'])) {
            $inline .= 'border-top-right-radius:' . (int)$props['borderRadiusRight'] . 'px;';
        }
        if (isset($props['borderRadiusBottom']) && is_numeric($props['borderRadiusBottom'])) {
            $inline .= 'border-bottom-right-radius:' . (int)$props['borderRadiusBottom'] . 'px;';
        }
        if (isset($props['borderRadiusLeft']) && is_numeric($props['borderRadiusLeft'])) {
            $inline .= 'border-bottom-left-radius:' . (int)$props['borderRadiusLeft'] . 'px;';
        }

        $safeHtml = trim($html) !== '' ? $this->sanitizeHeadingHtml($html) : '';

        $highlight = $this->buildHighlightSpec($props);
        $content = $safeHtml !== '' ? $safeHtml : e($text);
        $content = $this->applyHighlightTokensToHtml($content, $highlight);

        return '<' . $tag . ' class="mp-heading" style="' . e($inline) . '">' . $content . '</' . $tag . '>';
    }

    private function renderRichText(array $props): string
    {
        $html = is_string($props['html'] ?? null) ? (string) $props['html'] : '';

        $align = $this->sanitizeAlign($props['align'] ?? null) ?? 'left';
        $color = $this->sanitizeColor($props['color'] ?? null);
        $fontFamily = $this->sanitizeFontFamily($props['fontFamily'] ?? null);
        $fontWeight = $this->sanitizeFontWeight($props['fontWeight'] ?? null);
        $fontSize = $this->cssLength($props['fontSize'] ?? null, '');
        $lineHeight = $this->cssLineHeight($props['lineHeight'] ?? null, '');
        $letterSpacing = $this->cssLength($props['letterSpacing'] ?? null, '');
        $wordSpacing = $this->cssLength($props['wordSpacing'] ?? null, '');

        $safe = $this->sanitizeRichText($html);
        $highlight = $this->buildHighlightSpec($props);
        $safe = $this->applyHighlightTokensToHtml($safe, $highlight);

        $linkColor = $this->sanitizeColor($props['linkColor'] ?? null);
        if ($linkColor !== null) {
            $safe = $this->applyLinkColorToHtml($safe, $linkColor);
        }

        $inline = 'text-align:var(--mp-typo-textAlign,' . $align . ');';
        if ($color !== null) {
            $inline .= 'color:' . $color . ';';
        }
        if ($fontFamily !== null) {
            $inline .= 'font-family:' . $fontFamily . ';';
        }
        if ($fontWeight !== null) {
            $inline .= 'font-weight:' . $fontWeight . ';';
        }
        $inline .= 'font-size:var(--mp-typo-fontSize,' . ($fontSize !== '' ? e($fontSize) : 'inherit') . ');';
        $inline .= 'line-height:var(--mp-typo-lineHeight,' . ($lineHeight !== '' ? e($lineHeight) : 'inherit') . ');';
        if ($letterSpacing !== '') {
            $inline .= 'letter-spacing:' . e($letterSpacing) . ';';
        }
        if ($wordSpacing !== '') {
            $inline .= 'word-spacing:' . e($wordSpacing) . ';';
        }

        // Padding
        if (isset($props['paddingTop']) && is_numeric($props['paddingTop'])) {
            $inline .= 'padding-top:' . (int)$props['paddingTop'] . 'px;';
        }
        if (isset($props['paddingRight']) && is_numeric($props['paddingRight'])) {
            $inline .= 'padding-right:' . (int)$props['paddingRight'] . 'px;';
        }
        if (isset($props['paddingBottom']) && is_numeric($props['paddingBottom'])) {
            $inline .= 'padding-bottom:' . (int)$props['paddingBottom'] . 'px;';
        }
        if (isset($props['paddingLeft']) && is_numeric($props['paddingLeft'])) {
            $inline .= 'padding-left:' . (int)$props['paddingLeft'] . 'px;';
        }

        // Margin
        if (isset($props['marginTop']) && is_numeric($props['marginTop'])) {
            $inline .= 'margin-top:' . (int)$props['marginTop'] . 'px;';
        }
        if (isset($props['marginRight']) && is_numeric($props['marginRight'])) {
            $inline .= 'margin-right:' . (int)$props['marginRight'] . 'px;';
        }
        if (isset($props['marginBottom']) && is_numeric($props['marginBottom'])) {
            $inline .= 'margin-bottom:' . (int)$props['marginBottom'] . 'px;';
        }
        if (isset($props['marginLeft']) && is_numeric($props['marginLeft'])) {
            $inline .= 'margin-left:' . (int)$props['marginLeft'] . 'px;';
        }

        // Background
        $bgType = is_string($props['backgroundType'] ?? null) ? trim((string) $props['backgroundType']) : 'classic';
        if ($bgType === 'gradient') {
            $from = is_string($props['gradientFrom'] ?? null) && trim((string) $props['gradientFrom']) !== ''
                ? (string) $props['gradientFrom']
                : '#111827';
            $to = is_string($props['gradientTo'] ?? null) && trim((string) $props['gradientTo']) !== ''
                ? (string) $props['gradientTo']
                : '#3b82f6';
            $fromLoc = is_numeric($props['gradientFromLocation'] ?? null) ? (int)$props['gradientFromLocation'] : 0;
            $toLoc = is_numeric($props['gradientToLocation'] ?? null) ? (int)$props['gradientToLocation'] : 100;
            $gType = is_string($props['gradientType'] ?? null) ? trim((string) $props['gradientType']) : 'linear';
            $position = is_string($props['gradientPosition'] ?? null) && trim((string) $props['gradientPosition']) !== ''
                ? trim((string) $props['gradientPosition'])
                : 'top center';

            if ($gType === 'radial') {
                $inline .= 'background-image:radial-gradient(at ' . e($position) . ', ' . e($from) . ' ' . $fromLoc . '%, ' . e($to) . ' ' . $toLoc . '%);';
            } else {
                $inline .= 'background-image:linear-gradient(135deg, ' . e($from) . ' ' . $fromLoc . '%, ' . e($to) . ' ' . $toLoc . '%);';
            }
        } else {
            if (isset($props['backgroundColor']) && is_string($props['backgroundColor']) && $props['backgroundColor'] !== '') {
                $inline .= 'background-color:' . e($props['backgroundColor']) . ';';
            }
        }

        // Border
        if (isset($props['borderStyle']) && is_string($props['borderStyle']) && $props['borderStyle'] !== '' && $props['borderStyle'] !== 'default') {
            $inline .= 'border-style:' . e($props['borderStyle']) . ';';
        }
        if (isset($props['borderColor']) && is_string($props['borderColor']) && $props['borderColor'] !== '') {
            $inline .= 'border-color:' . e($props['borderColor']) . ';';
        }
        if (isset($props['borderWidthTop']) && is_numeric($props['borderWidthTop'])) {
            $inline .= 'border-top-width:' . (int)$props['borderWidthTop'] . 'px;';
        }
        if (isset($props['borderWidthRight']) && is_numeric($props['borderWidthRight'])) {
            $inline .= 'border-right-width:' . (int)$props['borderWidthRight'] . 'px;';
        }
        if (isset($props['borderWidthBottom']) && is_numeric($props['borderWidthBottom'])) {
            $inline .= 'border-bottom-width:' . (int)$props['borderWidthBottom'] . 'px;';
        }
        if (isset($props['borderWidthLeft']) && is_numeric($props['borderWidthLeft'])) {
            $inline .= 'border-left-width:' . (int)$props['borderWidthLeft'] . 'px;';
        }
        if (isset($props['borderRadiusTop']) && is_numeric($props['borderRadiusTop'])) {
            $inline .= 'border-top-left-radius:' . (int)$props['borderRadiusTop'] . 'px;';
        }
        if (isset($props['borderRadiusRight']) && is_numeric($props['borderRadiusRight'])) {
            $inline .= 'border-top-right-radius:' . (int)$props['borderRadiusRight'] . 'px;';
        }
        if (isset($props['borderRadiusBottom']) && is_numeric($props['borderRadiusBottom'])) {
            $inline .= 'border-bottom-right-radius:' . (int)$props['borderRadiusBottom'] . 'px;';
        }
        if (isset($props['borderRadiusLeft']) && is_numeric($props['borderRadiusLeft'])) {
            $inline .= 'border-bottom-left-radius:' . (int)$props['borderRadiusLeft'] . 'px;';
        }

        // Text shadow
        if (isset($props['textShadowColor']) && is_string($props['textShadowColor']) && $props['textShadowColor'] !== '') {
            $tsx = isset($props['textShadowX']) && is_numeric($props['textShadowX']) ? (int)$props['textShadowX'] : 0;
            $tsy = isset($props['textShadowY']) && is_numeric($props['textShadowY']) ? (int)$props['textShadowY'] : 0;
            $tsb = isset($props['textShadowBlur']) && is_numeric($props['textShadowBlur']) ? (int)$props['textShadowBlur'] : 0;
            $inline .= 'text-shadow:' . $tsx . 'px ' . $tsy . 'px ' . $tsb . 'px ' . e($props['textShadowColor']) . ';';
        }

        return '<div class="mp-richtext" style="' . e($inline) . '">' . $safe . '</div>';
    }

    private function buildHighlightSpec(array $props): array
    {
        $mode = is_string($props['highlightMode'] ?? null) ? trim((string) $props['highlightMode']) : 'none';
        $mode = in_array($mode, ['none', 'color', 'gradient'], true) ? $mode : 'none';

        $out = [
            'mode' => $mode,
            'class' => 'mp-text-highlight',
            'style' => '',
        ];

        if ($mode === 'color') {
            $c = $this->sanitizeColor($props['highlightColor'] ?? null);

            $style = '';
            if ($c !== null) {
                $style .= 'color:' . $c . ';';
            }

            $hFontFamily = $this->sanitizeFontFamily($props['highlightFontFamily'] ?? null);
            $hFontWeight = $this->sanitizeFontWeight($props['highlightFontWeight'] ?? null);
            $hFontSize = $this->sanitizeIntPx($props['highlightFontSize'] ?? null, 6, 240);
            $hLineHeight = $this->cssLineHeight($props['highlightLineHeight'] ?? null, '');
            $hLetterSpacing = $this->sanitizeFloatPx($props['highlightLetterSpacing'] ?? null, -50, 200);
            $hWordSpacing = $this->sanitizeFloatPx($props['highlightWordSpacing'] ?? null, -50, 400);

            if ($hFontFamily !== null) {
                $style .= 'font-family:' . $hFontFamily . ';';
            }
            if ($hFontWeight !== null) {
                $style .= 'font-weight:' . $hFontWeight . ';';
            }
            if ($hFontSize !== null) {
                $style .= 'font-size:' . $hFontSize . 'px;';
            }
            if ($hLineHeight !== '') {
                $style .= 'line-height:' . e($hLineHeight) . ';';
            }
            if ($hLetterSpacing !== null) {
                $style .= 'letter-spacing:' . $hLetterSpacing . 'px;';
            }
            if ($hWordSpacing !== null) {
                $style .= 'word-spacing:' . $hWordSpacing . 'px;';
            }

            $out['style'] = $style;
            return $out;
        }

        if ($mode === 'gradient') {
            $from = $this->sanitizeColor($props['highlightGradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($props['highlightGradientTo'] ?? null) ?? '#3b82f6';
            $animate = isset($props['highlightAnimate']) && $props['highlightAnimate'] === true;
            $dur = $this->intOrNull($props['highlightAnimationDuration'] ?? null);
            if ($dur === null) {
                $dur = 6;
            }
            if ($dur < 1) {
                $dur = 1;
            }
            if ($dur > 60) {
                $dur = 60;
            }

            $style = '';
            $style .= 'background-image:linear-gradient(90deg, ' . $from . ', ' . $to . ');';
            $style .= '-webkit-background-clip:text;';
            $style .= 'background-clip:text;';
            $style .= 'color:transparent;';
            $style .= '-webkit-text-fill-color:transparent;';

            $hFontFamily = $this->sanitizeFontFamily($props['highlightFontFamily'] ?? null);
            $hFontWeight = $this->sanitizeFontWeight($props['highlightFontWeight'] ?? null);
            $hFontSize = $this->sanitizeIntPx($props['highlightFontSize'] ?? null, 6, 240);
            $hLineHeight = $this->cssLineHeight($props['highlightLineHeight'] ?? null, '');
            $hLetterSpacing = $this->sanitizeFloatPx($props['highlightLetterSpacing'] ?? null, -50, 200);
            $hWordSpacing = $this->sanitizeFloatPx($props['highlightWordSpacing'] ?? null, -50, 400);

            if ($hFontFamily !== null) {
                $style .= 'font-family:' . $hFontFamily . ';';
            }
            if ($hFontWeight !== null) {
                $style .= 'font-weight:' . $hFontWeight . ';';
            }
            if ($hFontSize !== null) {
                $style .= 'font-size:' . $hFontSize . 'px;';
            }
            if ($hLineHeight !== '') {
                $style .= 'line-height:' . e($hLineHeight) . ';';
            }
            if ($hLetterSpacing !== null) {
                $style .= 'letter-spacing:' . $hLetterSpacing . 'px;';
            }
            if ($hWordSpacing !== null) {
                $style .= 'word-spacing:' . $hWordSpacing . 'px;';
            }

            if ($animate) {
                $out['class'] .= ' mp-text-highlight--animate';
                $style .= 'background-size:200% 200%;';
                $style .= 'animation:mp-highlight-gradient ' . (string) $dur . 's linear infinite;';
            }

            $out['style'] = $style;
            return $out;
        }

        return $out;
    }

    private function splitHighlightTokens(string $input): array
    {
        $s = (string) $input;
        $out = [];
        $i = 0;
        $len = strlen($s);
        while ($i < $len) {
            $start = strpos($s, '{{', $i);
            if ($start === false) {
                $out[] = ['type' => 'text', 'value' => substr($s, $i)];
                break;
            }
            if ($start > $i) {
                $out[] = ['type' => 'text', 'value' => substr($s, $i, $start - $i)];
            }
            $end = strpos($s, '}}', $start + 2);
            if ($end === false) {
                $out[] = ['type' => 'text', 'value' => substr($s, $start)];
                break;
            }
            $out[] = ['type' => 'highlight', 'value' => substr($s, $start + 2, $end - ($start + 2))];
            $i = $end + 2;
        }
        return $out;
    }

    private function innerHtml(\DOMNode $node): string
    {
        $out = '';
        $doc = $node->ownerDocument;
        if (!$doc) {
            return '';
        }
        foreach ($node->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return is_string($out) ? $out : '';
    }

    private function applyHighlightTokensToHtml(string $html, array $highlight): string
    {
        if (trim($html) === '' || !str_contains($html, '{{')) {
            return $html;
        }

        $mode = is_string($highlight['mode'] ?? null) ? (string) $highlight['mode'] : 'none';
        $class = is_string($highlight['class'] ?? null) ? (string) $highlight['class'] : 'mp-text-highlight';
        $style = is_string($highlight['style'] ?? null) ? (string) $highlight['style'] : '';

        if ($mode === 'none') {
            $style = 'color:#2563eb;';
        }

        $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';
        $styleAttr = $style !== '' ? ' style="' . e($style) . '"' : '';
        $open = '<span' . $classAttr . $styleAttr . '>';
        $close = '</span>';

        $out = preg_replace_callback('/\{\{([\s\S]*?)\}\}/', function (array $m) use ($open, $close): string {
            $inner = isset($m[1]) ? (string) $m[1] : '';
            if ($inner === '') {
                return '';
            }
            return $open . $inner . $close;
        }, $html);

        return is_string($out) ? $out : $html;
    }

    private function applyLinkColorToHtml(string $html, string $color): string
    {
        $color = trim($color);
        if ($color === '' || trim($html) === '') {
            return $html;
        }

        $out = preg_replace_callback('/<a\b([^>]*)>/i', function (array $m) use ($color): string {
            $attrs = isset($m[1]) ? (string) $m[1] : '';
            if (preg_match('/\bstyle\s*=\s*"([^"]*)"/i', $attrs, $sm)) {
                $existing = (string) $sm[1];
                if (preg_match('/(^|;|\s)color\s*:/i', $existing)) {
                    return $m[0];
                }
                $merged = ($existing === '' || preg_match('/;\s*$/', $existing))
                    ? $existing . 'color:' . $color . ';'
                    : $existing . ';color:' . $color . ';';
                $newAttrs = str_replace($sm[0], 'style="' . e($merged) . '"', $attrs);
                return '<a' . $newAttrs . '>';
            }
            return '<a' . $attrs . ' style="color:' . e($color) . ';">';
        }, $html);

        return is_string($out) ? $out : $html;
    }

    private function renderImage(array $props, array $adv = []): string
    {
        $url = is_string($props['url'] ?? null) ? trim((string) $props['url']) : '';
        $alt = is_string($props['alt'] ?? null) ? (string) $props['alt'] : '';
        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'left';
        $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';

        $widthCss = $this->cssLength($props['width'] ?? null, '');
        $heightCss = $this->cssLength($props['height'] ?? null, '');

        $svgRaw = is_string($props['svg'] ?? null) ? trim((string) $props['svg']) : '';
        $source = ((string) ($props['source'] ?? '')) === 'svg' ? 'svg' : 'url';

        if ($svgRaw !== '' && ($source === 'svg' || $url === '')) {
            $svg = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is', '', $svgRaw) ?? $svgRaw;
            $svg = preg_replace('/<\s*script\b[^>]*>/i', '', $svg) ?? $svg;
            $svg = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg) ?? $svg;
            $svg = preg_replace('/javascript\s*:/i', '', $svg) ?? $svg;

            $jc = match ($align) {
                'center' => 'center',
                'right' => 'flex-end',
                default => 'flex-start',
            };
            $svgStyle = 'display:inline-block;max-width:100%;width:' . e($widthCss !== '' ? $widthCss : '100%') . ';height:auto;';
            if (preg_match('/<svg\b[^>]*\bstyle\s*=\s*(["\'])/i', $svg)) {
                $svg = preg_replace('/(<svg\b[^>]*\bstyle\s*=\s*)(["\'])(.*?)\2/i', '$1$2$3;' . $svgStyle . '$2', $svg, 1) ?? $svg;
            } else {
                $svg = preg_replace('/<svg\b/i', '<svg style="' . $svgStyle . '"', $svg, 1) ?? $svg;
            }

            return '<div style="display:flex;width:100%;justify-content:' . $jc . ';">' . $svg . '</div>';
        }

        if ($url === '') {
            return '';
        }

        $wrapperStyle = 'display:flex;width:100%;';
        $jc = is_string($adv['justifyContent'] ?? null) && trim((string) $adv['justifyContent']) !== ''
            ? trim((string) $adv['justifyContent'])
            : match ($align) {
                'center' => 'center',
                'right' => 'flex-end',
                default => 'flex-start',
            };
        $wrapperStyle .= 'justify-content:' . $jc . ';';

        $imgStyle = 'max-width:100%;display:block;';
        if ($widthCss !== '') {
            $imgStyle .= 'width:' . $widthCss . ';';
        }
        if ($heightCss !== '') {
            $imgStyle .= 'height:' . $heightCss . ';';
        } else {
            $imgStyle .= 'height:auto;';
        }
        $objectFit = is_string($props['objectFit'] ?? null) ? trim((string) $props['objectFit']) : '';
        if (in_array($objectFit, ['cover', 'contain', 'fill', 'none', 'scale-down'], true)) {
            $imgStyle .= 'object-fit:' . $objectFit . ';';
        }

        if (isset($props['marginTop']) && is_numeric($props['marginTop'])) {
            $imgStyle .= 'margin-top:' . (int)$props['marginTop'] . 'px;';
        }
        if (isset($props['marginRight']) && is_numeric($props['marginRight'])) {
            $imgStyle .= 'margin-right:' . (int)$props['marginRight'] . 'px;';
        }
        if (isset($props['marginBottom']) && is_numeric($props['marginBottom'])) {
            $imgStyle .= 'margin-bottom:' . (int)$props['marginBottom'] . 'px;';
        }
        if (isset($props['marginLeft']) && is_numeric($props['marginLeft'])) {
            $imgStyle .= 'margin-left:' . (int)$props['marginLeft'] . 'px;';
        }

        if (isset($props['paddingTop']) && is_numeric($props['paddingTop'])) {
            $imgStyle .= 'padding-top:' . (int)$props['paddingTop'] . 'px;';
        }
        if (isset($props['paddingRight']) && is_numeric($props['paddingRight'])) {
            $imgStyle .= 'padding-right:' . (int)$props['paddingRight'] . 'px;';
        }
        if (isset($props['paddingBottom']) && is_numeric($props['paddingBottom'])) {
            $imgStyle .= 'padding-bottom:' . (int)$props['paddingBottom'] . 'px;';
        }
        if (isset($props['paddingLeft']) && is_numeric($props['paddingLeft'])) {
            $imgStyle .= 'padding-left:' . (int)$props['paddingLeft'] . 'px;';
        }

        $imgBorderWidth = $this->cssLength($props['borderWidth'] ?? null, '');
        if ($imgBorderWidth !== '') {
            $imgStyle .= 'border-width:' . e($imgBorderWidth) . ';';
            $imgStyle .= 'border-style:solid;';
        }
        $imgBorderWidthTop = $this->cssLength($props['borderWidthTop'] ?? null, '');
        if ($imgBorderWidthTop !== '') {
            $imgStyle .= 'border-top-width:' . e($imgBorderWidthTop) . ';';
            $imgStyle .= 'border-style:solid;';
        }
        $imgBorderWidthRight = $this->cssLength($props['borderWidthRight'] ?? null, '');
        if ($imgBorderWidthRight !== '') {
            $imgStyle .= 'border-right-width:' . e($imgBorderWidthRight) . ';';
            $imgStyle .= 'border-style:solid;';
        }
        $imgBorderWidthBottom = $this->cssLength($props['borderWidthBottom'] ?? null, '');
        if ($imgBorderWidthBottom !== '') {
            $imgStyle .= 'border-bottom-width:' . e($imgBorderWidthBottom) . ';';
            $imgStyle .= 'border-style:solid;';
        }
        $imgBorderWidthLeft = $this->cssLength($props['borderWidthLeft'] ?? null, '');
        if ($imgBorderWidthLeft !== '') {
            $imgStyle .= 'border-left-width:' . e($imgBorderWidthLeft) . ';';
            $imgStyle .= 'border-style:solid;';
        }
        if (isset($props['borderStyle']) && is_string($props['borderStyle']) && $props['borderStyle'] !== '' && $props['borderStyle'] !== 'default'
            && in_array($props['borderStyle'], ['solid', 'dashed', 'dotted', 'double', 'none', 'hidden', 'groove', 'ridge', 'inset', 'outset'], true)) {
            $imgStyle .= 'border-style:' . e($props['borderStyle']) . ';';
        }
        if (isset($props['borderColor']) && is_string($props['borderColor']) && $props['borderColor'] !== '') {
            $imgStyle .= 'border-color:' . e($props['borderColor']) . ';';
        }

        $imgBorderRadius = $this->cssLength($props['borderRadius'] ?? null, '');
        if ($imgBorderRadius !== '') {
            $imgStyle .= 'border-radius:' . e($imgBorderRadius) . ';';
        }
        $imgBorderRadiusTop = $this->cssLength($props['borderRadiusTop'] ?? null, '');
        if ($imgBorderRadiusTop !== '') {
            $imgStyle .= 'border-top-left-radius:' . e($imgBorderRadiusTop) . ';';
        }
        $imgBorderRadiusRight = $this->cssLength($props['borderRadiusRight'] ?? null, '');
        if ($imgBorderRadiusRight !== '') {
            $imgStyle .= 'border-top-right-radius:' . e($imgBorderRadiusRight) . ';';
        }
        $imgBorderRadiusBottom = $this->cssLength($props['borderRadiusBottom'] ?? null, '');
        if ($imgBorderRadiusBottom !== '') {
            $imgStyle .= 'border-bottom-right-radius:' . e($imgBorderRadiusBottom) . ';';
        }
        $imgBorderRadiusLeft = $this->cssLength($props['borderRadiusLeft'] ?? null, '');
        if ($imgBorderRadiusLeft !== '') {
            $imgStyle .= 'border-bottom-left-radius:' . e($imgBorderRadiusLeft) . ';';
        }

        $boxShadowColor = is_string($props['boxShadowColor'] ?? null) ? trim((string) $props['boxShadowColor']) : '';
        if ($boxShadowColor !== '') {
            $bsX = is_numeric($props['boxShadowX'] ?? null) ? (int) $props['boxShadowX'] : 0;
            $bsY = is_numeric($props['boxShadowY'] ?? null) ? (int) $props['boxShadowY'] : 0;
            $bsBlur = is_numeric($props['boxShadowBlur'] ?? null) ? (int) $props['boxShadowBlur'] : 0;
            $bsSpread = is_numeric($props['boxShadowSpread'] ?? null) ? (int) $props['boxShadowSpread'] : 0;
            $imgStyle .= 'box-shadow:' . $bsX . 'px ' . $bsY . 'px ' . $bsBlur . 'px ' . $bsSpread . 'px ' . e($boxShadowColor) . ';';
        }

        return '<div class="mp-image" style="' . e($wrapperStyle) . '">'
            . '<img src="' . e($url) . '" alt="' . e($alt) . '" class="mp-image__img" style="' . e($imgStyle) . '" />'
            . '</div>';
    }

    private function renderButton(array $props): string
    {
        $firstColor = function (array $props, string ...$keys): string {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $props)) {
                    continue;
                }

                $value = $props[$key];
                if (is_string($value)) {
                    $value = trim($value);
                    if ($value !== '') {
                        $resolved = $this->sanitizeColor($value);
                        return is_string($resolved) && $resolved !== '' ? $resolved : $value;
                    }
                }

                $resolved = $this->sanitizeColor($value);
                if (is_string($resolved) && $resolved !== '') {
                    return $resolved;
                }
            }

            return '';
        };

        $text = is_string($props['text'] ?? null) ? (string) $props['text'] : 'Click Me';
        $url = is_string($props['url'] ?? null) ? trim((string) $props['url']) : '';
        $target = is_string($props['target'] ?? null) ? trim((string) $props['target']) : '_self';
        $target = in_array($target, ['_self', '_blank'], true) ? $target : '_self';
        
        $style = is_string($props['style'] ?? null) ? trim((string) $props['style']) : 'primary';
        $style = in_array($style, ['primary', 'secondary', 'outline', 'ghost'], true) ? $style : 'primary';
        
        $size = is_string($props['size'] ?? null) ? trim((string) $props['size']) : 'md';
        $size = in_array($size, ['xs', 'sm', 'md', 'lg', 'xl'], true) ? $size : 'md';
        
        $fullWidth = isset($props['fullWidth']) && $props['fullWidth'] === true;
        
        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'left';
        $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';

        $iconType = is_string($props['iconType'] ?? null) ? trim((string) $props['iconType']) : '';
        $iconType = in_array($iconType, ['none', 'preset', 'custom'], true)
            ? $iconType
            : (is_string($props['iconUrl'] ?? null) && trim((string) $props['iconUrl']) !== '' ? 'custom' : (is_string($props['icon'] ?? null) && trim((string) $props['icon']) !== '' ? 'preset' : 'none'));

        $icon = is_string($props['icon'] ?? null) ? trim((string) $props['icon']) : '';
        $iconUrl = is_string($props['iconUrl'] ?? null) ? trim((string) $props['iconUrl']) : '';
        $iconPosition = is_string($props['iconPosition'] ?? null) ? trim((string) $props['iconPosition']) : 'left';
        $iconPosition = in_array($iconPosition, ['left', 'right'], true) ? $iconPosition : 'left';

        // Base size styles
        $sizeStyles = match ($size) {
            'xs' => 'padding:0.375rem 0.625rem;font-size:0.75rem;gap:0.25rem;',
            'sm' => 'padding:0.5rem 0.75rem;font-size:0.875rem;gap:0.375rem;',
            'md' => 'padding:0.5rem 1rem;font-size:1rem;gap:0.5rem;',
            'lg' => 'padding:0.75rem 1.25rem;font-size:1.125rem;gap:0.625rem;',
            'xl' => 'padding:0.875rem 1.5rem;font-size:1.25rem;gap:0.75rem;',
            default => 'padding:0.5rem 1rem;font-size:1rem;gap:0.5rem;',
        };

        // Base style colors
        $styleClasses = match ($style) {
            'primary' => 'background-color:#2563eb;color:#ffffff;border:1px solid transparent;',
            'secondary' => 'background-color:#4b5563;color:#ffffff;border:1px solid transparent;',
            'outline' => 'background-color:transparent;color:#2563eb;border:1px solid #2563eb;',
            'ghost' => 'background-color:transparent;color:#374151;border:1px solid transparent;',
            default => 'background-color:#2563eb;color:#ffffff;border:1px solid transparent;',
        };

        $alignStyle = match ($align) {
            'center' => 'text-align:center;',
            'right' => 'text-align:right;',
            default => 'text-align:left;',
        };

        // Build button style with custom overrides
        $buttonStyle = $sizeStyles . $styleClasses . 'display:inline-flex;align-items:center;justify-content:center;font-weight:600;border-radius:0.5rem;text-decoration:none;transition:all 0.2s;';
        
        // Custom styling overrides
        $bgType = is_string($props['backgroundType'] ?? null) ? trim((string) $props['backgroundType']) : (is_string($props['buttonBackgroundType'] ?? null) ? trim((string) $props['buttonBackgroundType']) : 'classic');
        if ($bgType === 'gradient') {
            $from = $firstColor($props, 'gradientFrom', 'buttonGradientFrom');
            if ($from === '') { $from = '#111827'; }
            $to = $firstColor($props, 'gradientTo', 'buttonGradientTo');
            if ($to === '') { $to = '#3b82f6'; }
            $fromLoc = is_numeric($props['gradientFromLocation'] ?? null) ? (int)$props['gradientFromLocation'] : 0;
            $toLoc = is_numeric($props['gradientToLocation'] ?? null) ? (int)$props['gradientToLocation'] : 100;
            $gType = is_string($props['gradientType'] ?? null) ? trim((string) $props['gradientType']) : (is_string($props['buttonGradientType'] ?? null) ? trim((string) $props['buttonGradientType']) : 'linear');
            $gType = in_array($gType, ['linear', 'radial'], true) ? $gType : 'linear';

            $buttonStyle .= 'background-color:transparent;';
            if ($gType === 'radial') {
                $position = is_string($props['gradientPosition'] ?? null) && trim((string) $props['gradientPosition']) !== ''
                    ? trim((string) $props['gradientPosition'])
                    : (is_string($props['buttonGradientPosition'] ?? null) && trim((string) $props['buttonGradientPosition']) !== '' ? trim((string) $props['buttonGradientPosition']) : 'top center');
                $allowedPositions = [
                    'top left', 'top center', 'top right',
                    'center left', 'center center', 'center right',
                    'bottom left', 'bottom center', 'bottom right',
                ];
                if (!in_array($position, $allowedPositions, true)) {
                    $position = 'top center';
                }
                $buttonStyle .= 'background-image:radial-gradient(at ' . $position . ', ' . $from . ' ' . $fromLoc . '%, ' . $to . ' ' . $toLoc . '%);';
            } else {
                $buttonStyle .= 'background-image:linear-gradient(135deg, ' . $from . ' ' . $fromLoc . '%, ' . $to . ' ' . $toLoc . '%);';
            }
        } else {
            $backgroundColor = $firstColor($props, 'backgroundColor', 'buttonBackgroundColor');
            if ($backgroundColor !== '') {
                $buttonStyle .= 'background-color:' . e($backgroundColor) . ';';
            }
        }
        $textColor = $firstColor($props, 'textColor', 'buttonTextColor');
        if ($textColor !== '') {
            $buttonStyle .= 'color:' . e($textColor) . ';';
        }
        if (isset($props['borderStyle']) && is_string($props['borderStyle']) && $props['borderStyle'] !== '' && $props['borderStyle'] !== 'default'
            && in_array($props['borderStyle'], ['solid', 'dashed', 'dotted', 'double', 'none', 'hidden', 'groove', 'ridge', 'inset', 'outset'], true)) {
            $buttonStyle .= 'border-style:' . e($props['borderStyle']) . ';';
        }
        $borderWidthCss = $this->cssLength($props['borderWidth'] ?? null, '');
        if ($borderWidthCss !== '') {
            $buttonStyle .= 'border-width:' . e($borderWidthCss) . ';';
        }
        // Per-side border widths (override legacy borderWidth when provided)
        $borderWidthTopCss = $this->cssLength($props['borderWidthTop'] ?? null, '');
        if ($borderWidthTopCss !== '') {
            $buttonStyle .= 'border-top-width:' . e($borderWidthTopCss) . ';';
        }
        $borderWidthRightCss = $this->cssLength($props['borderWidthRight'] ?? null, '');
        if ($borderWidthRightCss !== '') {
            $buttonStyle .= 'border-right-width:' . e($borderWidthRightCss) . ';';
        }
        $borderWidthBottomCss = $this->cssLength($props['borderWidthBottom'] ?? null, '');
        if ($borderWidthBottomCss !== '') {
            $buttonStyle .= 'border-bottom-width:' . e($borderWidthBottomCss) . ';';
        }
        $borderWidthLeftCss = $this->cssLength($props['borderWidthLeft'] ?? null, '');
        if ($borderWidthLeftCss !== '') {
            $buttonStyle .= 'border-left-width:' . e($borderWidthLeftCss) . ';';
        }
        if (isset($props['borderColor']) && is_string($props['borderColor']) && $props['borderColor'] !== '') {
            $buttonStyle .= 'border-color:' . e($props['borderColor']) . ';';
        }
        $borderRadiusCss = $this->cssLength($props['borderRadius'] ?? null, '');
        if ($borderRadiusCss !== '') {
            $buttonStyle .= 'border-radius:' . e($borderRadiusCss) . ';';
        }
        // Per-corner border radii (override legacy borderRadius when provided)
        $borderRadiusTopCss = $this->cssLength($props['borderRadiusTop'] ?? null, '');
        if ($borderRadiusTopCss !== '') {
            $buttonStyle .= 'border-top-left-radius:' . e($borderRadiusTopCss) . ';';
        }
        $borderRadiusRightCss = $this->cssLength($props['borderRadiusRight'] ?? null, '');
        if ($borderRadiusRightCss !== '') {
            $buttonStyle .= 'border-top-right-radius:' . e($borderRadiusRightCss) . ';';
        }
        $borderRadiusBottomCss = $this->cssLength($props['borderRadiusBottom'] ?? null, '');
        if ($borderRadiusBottomCss !== '') {
            $buttonStyle .= 'border-bottom-right-radius:' . e($borderRadiusBottomCss) . ';';
        }
        $borderRadiusLeftCss = $this->cssLength($props['borderRadiusLeft'] ?? null, '');
        if ($borderRadiusLeftCss !== '') {
            $buttonStyle .= 'border-bottom-left-radius:' . e($borderRadiusLeftCss) . ';';
        }
        if (isset($props['paddingX']) && is_numeric($props['paddingX'])) {
            $buttonStyle .= 'padding-left:' . (int)$props['paddingX'] . 'px;padding-right:' . (int)$props['paddingX'] . 'px;';
        }
        if (isset($props['paddingY']) && is_numeric($props['paddingY'])) {
            $buttonStyle .= 'padding-top:' . (int)$props['paddingY'] . 'px;padding-bottom:' . (int)$props['paddingY'] . 'px;';
        }
        // Per-side padding (override legacy paddingX/paddingY when provided)
        if (isset($props['paddingTop']) && is_numeric($props['paddingTop'])) {
            $buttonStyle .= 'padding-top:' . (int)$props['paddingTop'] . 'px;';
        }
        if (isset($props['paddingRight']) && is_numeric($props['paddingRight'])) {
            $buttonStyle .= 'padding-right:' . (int)$props['paddingRight'] . 'px;';
        }
        if (isset($props['paddingBottom']) && is_numeric($props['paddingBottom'])) {
            $buttonStyle .= 'padding-bottom:' . (int)$props['paddingBottom'] . 'px;';
        }
        if (isset($props['paddingLeft']) && is_numeric($props['paddingLeft'])) {
            $buttonStyle .= 'padding-left:' . (int)$props['paddingLeft'] . 'px;';
        }
        $buttonFontFamily = $this->sanitizeFontFamily($props['fontFamily'] ?? null);
        if ($buttonFontFamily !== null) {
            $buttonStyle .= 'font-family:' . e($buttonFontFamily) . ';';
        }
        $buttonFontSize = $this->cssLength($props['fontSize'] ?? null, '');
        if ($buttonFontSize !== '') {
            $buttonStyle .= 'font-size:' . e($buttonFontSize) . ';';
        }
        $buttonFontWeight = $props['fontWeight'] ?? null;
        if ((is_string($buttonFontWeight) && trim($buttonFontWeight) !== '') || (is_numeric($buttonFontWeight) && !is_bool($buttonFontWeight))) {
            $fw = trim((string) $buttonFontWeight);
            if ($fw === '' || !preg_match('/^(bold|bolder|lighter|normal|[1-9]00)$/', $fw)) {
                $fw = '';
            }
            if ($fw !== '') {
                $buttonStyle .= 'font-weight:' . $fw . ';';
            }
        }
        $buttonLineHeight = $this->cssLineHeight($props['lineHeight'] ?? null, '');
        if ($buttonLineHeight !== '') {
            $buttonStyle .= 'line-height:' . e($buttonLineHeight) . ';';
        }
        $buttonLetterSpacing = $this->cssLength($props['letterSpacing'] ?? null, '');
        if ($buttonLetterSpacing !== '') {
            $buttonStyle .= 'letter-spacing:' . e($buttonLetterSpacing) . ';';
        }
        $buttonWordSpacing = $this->cssLength($props['wordSpacing'] ?? null, '');
        if ($buttonWordSpacing !== '') {
            $buttonStyle .= 'word-spacing:' . e($buttonWordSpacing) . ';';
        }

        // Box shadow
        $boxShadowColor = is_string($props['boxShadowColor'] ?? null) ? trim((string) $props['boxShadowColor']) : '';
        if ($boxShadowColor !== '') {
            $bsX = is_numeric($props['boxShadowX'] ?? null) ? (int)$props['boxShadowX'] : 0;
            $bsY = is_numeric($props['boxShadowY'] ?? null) ? (int)$props['boxShadowY'] : 0;
            $bsBlur = is_numeric($props['boxShadowBlur'] ?? null) ? (int)$props['boxShadowBlur'] : 0;
            $bsSpread = is_numeric($props['boxShadowSpread'] ?? null) ? (int)$props['boxShadowSpread'] : 0;
            $buttonStyle .= 'box-shadow:' . $bsX . 'px ' . $bsY . 'px ' . $bsBlur . 'px ' . $bsSpread . 'px ' . e($boxShadowColor) . ';';
        }

        $buttonWidth = $this->cssLength($props['width'] ?? null, '');
        if (!$fullWidth && $buttonWidth !== '') {
            $buttonStyle .= 'width:' . e($buttonWidth) . ';';
        }
        $buttonHeight = $this->cssLength($props['height'] ?? null, '');
        if ($buttonHeight !== '') {
            $buttonStyle .= 'height:' . e($buttonHeight) . ';';
        }

        if ($fullWidth) {
            $buttonStyle .= 'width:100%;';
        }

        // Container style for margins
        $containerStyle = $alignStyle;
        if (isset($props['marginTop']) && is_numeric($props['marginTop'])) {
            $containerStyle .= 'margin-top:' . (int)$props['marginTop'] . 'px;';
        }
        if (isset($props['marginRight']) && is_numeric($props['marginRight'])) {
            $containerStyle .= 'margin-right:' . (int)$props['marginRight'] . 'px;';
        }
        if (isset($props['marginBottom']) && is_numeric($props['marginBottom'])) {
            $containerStyle .= 'margin-bottom:' . (int)$props['marginBottom'] . 'px;';
        }
        if (isset($props['marginLeft']) && is_numeric($props['marginLeft'])) {
            $containerStyle .= 'margin-left:' . (int)$props['marginLeft'] . 'px;';
        }

        // Icon
        $iconHtml = '';
        if ($iconType === 'custom' && $iconUrl !== '' && (bool) preg_match('/^https?:\/\//i', $iconUrl)) {
            $iconHtml = '<img src="' . e($iconUrl) . '" alt="" style="width:1.25rem;height:1.25rem;flex-shrink:0;object-fit:contain;" />';
        }

        if ($iconHtml === '' && $iconType === 'preset' && $icon !== '') {
            $iconPath = match ($icon) {
                'arrow-right' => 'M13 7l5 5m0 0l-5 5m5-5H6',
                'arrow-left' => 'M11 17l-5-5m0 0l5-5m-5 5h12',
                'download' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
                'external' => 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14',
                'check' => 'M5 13l4 4L19 7',
                'plus' => 'M12 4v16m8-8H4',
                'heart' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                'star' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
                default => '',
            };

            if ($iconPath !== '') {
                $iconHtml = '<svg style="width:1.25rem;height:1.25rem;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . e($iconPath) . '"/></svg>';
            }
        }

        $tag = $url !== '' ? 'a' : 'button';
        $attrs = $url !== '' ? ' href="' . e($url) . '" target="' . e($target) . '"' : ' type="button"';

        $buttonContent = '';

        // Unique ID for hover/focus CSS scoping
        $btnId = 'mp-btn-' . uniqid();
        $attrs .= ' id="' . $btnId . '"';

        $hoverCss = '';
        $hasHover = false;

        $hoverTextColor = $firstColor($props, 'hoverTextColor', 'buttonHoverTextColor');
        if ($hoverTextColor !== '') {
            $hoverCss .= 'color:' . e($hoverTextColor) . ' !important;';

            $hasHover = true;
        }

        $hoverBgType = is_string($props['hoverBackgroundType'] ?? null) ? trim((string) $props['hoverBackgroundType']) : (is_string($props['buttonHoverBackgroundType'] ?? null) ? trim((string) $props['buttonHoverBackgroundType']) : 'classic');
        if ($hoverBgType === 'gradient') {
            $from = $firstColor($props, 'hoverGradientFrom', 'buttonHoverGradientFrom') !== '' ? $firstColor($props, 'hoverGradientFrom', 'buttonHoverGradientFrom') : '#111827';
            $to = $firstColor($props, 'hoverGradientTo', 'buttonHoverGradientTo') !== '' ? $firstColor($props, 'hoverGradientTo', 'buttonHoverGradientTo') : '#3b82f6';
            $fromLoc = is_numeric($props['hoverGradientFromLocation'] ?? null) ? (int)$props['hoverGradientFromLocation'] : 0;
            $toLoc = is_numeric($props['hoverGradientToLocation'] ?? null) ? (int)$props['hoverGradientToLocation'] : 100;
            $gType = is_string($props['hoverGradientType'] ?? null) ? trim((string) $props['hoverGradientType']) : (is_string($props['buttonHoverGradientType'] ?? null) ? trim((string) $props['buttonHoverGradientType']) : 'linear');
            $gType = in_array($gType, ['linear', 'radial'], true) ? $gType : 'linear';

            $hoverCss .= 'background-color:transparent !important;';

            if ($gType === 'radial') {
                $position = is_string($props['hoverGradientPosition'] ?? null) && trim((string) $props['hoverGradientPosition']) !== ''
                    ? trim((string) $props['hoverGradientPosition'])
                    : (is_string($props['buttonHoverGradientPosition'] ?? null) && trim((string) $props['buttonHoverGradientPosition']) !== '' ? trim((string) $props['buttonHoverGradientPosition']) : 'top center');
                $allowedPositions = [
                    'top left', 'top center', 'top right',
                    'center left', 'center center', 'center right',
                    'bottom left', 'bottom center', 'bottom right',
                ];
                if (!in_array($position, $allowedPositions, true)) {
                    $position = 'top center';
                }
                $hoverCss .= 'background-image:radial-gradient(at ' . $position . ', ' . $from . ' ' . $fromLoc . '%, ' . $to . ' ' . $toLoc . '%) !important;';

            } else {
                $hoverCss .= 'background-image:linear-gradient(135deg, ' . $from . ' ' . $fromLoc . '%, ' . $to . ' ' . $toLoc . '%) !important;';

            }
            $hasHover = true;
        } else {
            $hoverBgColor = $firstColor($props, 'hoverBackgroundColor', 'buttonHoverBackgroundColor');
            if ($hoverBgColor !== '') {
                $hoverCss .= 'background-image:none !important;';
                $hoverCss .= 'background-color:' . e($hoverBgColor) . ' !important;';

                $hasHover = true;
            }
        }

        $focusCss = '';
        $hasFocus = false;

        $focusTextColor = $firstColor($props, 'focusTextColor', 'buttonFocusTextColor');
        if ($focusTextColor !== '') {
            $focusCss .= 'color:' . e($focusTextColor) . ' !important;';

            $hasFocus = true;
        }

        $focusBgType = is_string($props['focusBackgroundType'] ?? null) ? trim((string) $props['focusBackgroundType']) : (is_string($props['buttonFocusBackgroundType'] ?? null) ? trim((string) $props['buttonFocusBackgroundType']) : 'classic');
        if ($focusBgType === 'gradient') {
            $from = $firstColor($props, 'focusGradientFrom', 'buttonFocusGradientFrom') !== '' ? $firstColor($props, 'focusGradientFrom', 'buttonFocusGradientFrom') : '#111827';
            $to = $firstColor($props, 'focusGradientTo', 'buttonFocusGradientTo') !== '' ? $firstColor($props, 'focusGradientTo', 'buttonFocusGradientTo') : '#3b82f6';
            $fromLoc = is_numeric($props['focusGradientFromLocation'] ?? null) ? (int)$props['focusGradientFromLocation'] : 0;
            $toLoc = is_numeric($props['focusGradientToLocation'] ?? null) ? (int)$props['focusGradientToLocation'] : 100;
            $gType = is_string($props['focusGradientType'] ?? null) ? trim((string) $props['focusGradientType']) : (is_string($props['buttonFocusGradientType'] ?? null) ? trim((string) $props['buttonFocusGradientType']) : 'linear');
            $gType = in_array($gType, ['linear', 'radial'], true) ? $gType : 'linear';

            $focusCss .= 'background-color:transparent !important;';

            if ($gType === 'radial') {
                $position = is_string($props['focusGradientPosition'] ?? null) && trim((string) $props['focusGradientPosition']) !== ''
                    ? trim((string) $props['focusGradientPosition'])
                    : (is_string($props['buttonFocusGradientPosition'] ?? null) && trim((string) $props['buttonFocusGradientPosition']) !== '' ? trim((string) $props['buttonFocusGradientPosition']) : 'top center');
                $allowedPositions = [
                    'top left', 'top center', 'top right',
                    'center left', 'center center', 'center right',
                    'bottom left', 'bottom center', 'bottom right',
                ];
                if (!in_array($position, $allowedPositions, true)) {
                    $position = 'top center';
                }
                $focusCss .= 'background-image:radial-gradient(at ' . $position . ', ' . $from . ' ' . $fromLoc . '%, ' . $to . ' ' . $toLoc . '%) !important;';

            } else {
                $focusCss .= 'background-image:linear-gradient(135deg, ' . $from . ' ' . $fromLoc . '%, ' . $to . ' ' . $toLoc . '%) !important;';

            }
            $hasFocus = true;
        } else {
            $focusBgColor = $firstColor($props, 'focusBackgroundColor', 'buttonFocusBackgroundColor');
            if ($focusBgColor !== '') {
                $focusCss .= 'background-image:none !important;';
                $focusCss .= 'background-color:' . e($focusBgColor) . ' !important;';

                $hasFocus = true;
            }
        }

        $hoverTextEffect = is_string($props['hoverTextEffect'] ?? null) ? trim((string) $props['hoverTextEffect']) : 'none';
        $hasTextEffect = $hoverTextEffect !== 'none';

        $pseudoStyleTag = '';
        if ($hasHover || $hasFocus || $hasTextEffect) {
            $css = '';
            if ($hasHover) {
                $css .= '#' . $btnId . ':hover{' . $hoverCss . '}';
            }
            if ($hasFocus) {
                $css .= '#' . $btnId . ':focus{' . $focusCss . '}';
            }
            if ($hoverTextEffect === 'swap-up') {
                $css .= '#' . $btnId . ':hover .mp-btn-text-normal{transform:translateY(-100%) !important;opacity:0 !important;}';
                $css .= '#' . $btnId . ':hover .mp-btn-text-hover{transform:translateY(0) !important;opacity:1 !important;}';
            }
            if ($hoverTextEffect === 'swap-icon') {
                $css .= '#' . $btnId . ':hover .mp-btn-icon-normal{transform:translateX(100%) !important;opacity:0 !important;}';
                $css .= '#' . $btnId . ':hover .mp-btn-icon-hover{transform:translateX(0) !important;opacity:1 !important;}';
            }
            if ($hoverTextEffect === 'slide-icon-right') {
                $css .= '#' . $btnId . ':hover .mp-btn-icon-slide{transform:translateX(4px) !important;}';
            }
            $pseudoStyleTag = '<style>' . $css . '</style>';
        }

        $textHtml = e($text);
        if ($hoverTextEffect === 'swap-up') {
            $textHtml = '<span class="mp-btn-text-wrap" style="position:relative;display:inline-block;overflow:hidden;vertical-align:middle;">'
                . '<span class="mp-btn-text-normal" style="display:block;transition:transform 0.3s ease,opacity 0.3s ease;">' . e($text) . '</span>'
                . '<span class="mp-btn-text-hover" style="position:absolute;top:0;left:0;width:100%;display:block;transition:transform 0.3s ease,opacity 0.3s ease;transform:translateY(100%);opacity:0;">' . e($text) . '</span>'
                . '</span>';
        }

        $iconSwapHtml = '';
        if ($hoverTextEffect === 'swap-icon' && $iconHtml !== '') {
            $iconSwapHtml = '<span style="position:relative;display:inline-block;overflow:hidden;vertical-align:middle;">'
                . '<span class="mp-btn-icon-normal" style="display:block;transition:transform 0.3s ease,opacity 0.3s ease;">' . $iconHtml . '</span>'
                . '<span class="mp-btn-icon-hover" style="position:absolute;top:0;left:0;width:100%;display:block;transition:transform 0.3s ease,opacity 0.3s ease;transform:translateX(-100%);opacity:0;">' . $iconHtml . '</span>'
                . '</span>';
        }

        $iconSlideHtml = '';
        if ($hoverTextEffect === 'slide-icon-right' && $iconHtml !== '') {
            $iconSlideHtml = '<span class="mp-btn-icon-slide" style="display:inline-block;transition:transform 0.3s ease;">' . $iconHtml . '</span>';
        }

        if ($iconHtml !== '' && $iconPosition === 'left') {
            $buttonContent .= ($hoverTextEffect === 'swap-icon' && $iconSwapHtml !== '') ? $iconSwapHtml : (($hoverTextEffect === 'slide-icon-right' && $iconSlideHtml !== '') ? $iconSlideHtml : $iconHtml);
        }
        $buttonContent .= $textHtml;
        if ($iconHtml !== '' && $iconPosition === 'right') {
            $buttonContent .= ($hoverTextEffect === 'swap-icon' && $iconSwapHtml !== '') ? $iconSwapHtml : (($hoverTextEffect === 'slide-icon-right' && $iconSlideHtml !== '') ? $iconSlideHtml : $iconHtml);
        }

        $bfxClass = $this->borderEffectClassName($props);
        $bfxStyle = $this->borderEffectStyleVars($props);
        if ($bfxClass !== '') {
            $buttonStyle .= $bfxStyle;
        }

        return $pseudoStyleTag
            . '<div class="mp-button" style="' . $containerStyle . '">'
            . '<' . $tag . $attrs . ' class="mp-button__btn' . ($bfxClass !== '' ? ' ' . $bfxClass : '') . '" style="' . $buttonStyle . '">'
            . $buttonContent
            . '</' . $tag . '>'
            . '</div>';
    }

    private function renderButtonFromPrefix(array $allProps, string $prefix): string
    {
        $props = [];
        foreach ($allProps as $k => $v) {
            if (str_starts_with($k, $prefix)) {
                $props[substr($k, strlen($prefix))] = $v;
            }
        }
        return $this->renderButton($props);
    }

    private function renderButtonGroup(array $props): string
    {
        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'left';
        $gap = is_numeric($props['gap'] ?? null) ? (int)$props['gap'] : 16;
        $direction = is_string($props['direction'] ?? null) ? trim((string) $props['direction']) : 'horizontal';

        $justify = match ($align) {
            'center' => 'center',
            'right' => 'flex-end',
            default => 'flex-start',
        };
        $flexDir = $direction === 'vertical' ? 'column' : 'row';
        $alignItems = $direction === 'vertical' ? 'stretch' : 'center';

        $btn1 = $this->renderButtonFromPrefix($props, 'btn1_');
        $btn2 = $this->renderButtonFromPrefix($props, 'btn2_');

        return '<div style="display:flex;gap:' . $gap . 'px;flex-direction:' . $flexDir . ';justify-content:' . $justify . ';align-items:' . $alignItems . ';">'
            . $btn1 . $btn2
            . '</div>';
    }

    private function renderBadge(array $props): string
    {
        $text = is_string($props['text'] ?? null) ? (string) $props['text'] : 'Announcing ShadcnSpace 1.0';
        $leading = is_string($props['leading'] ?? null) ? trim((string) $props['leading']) : 'label';
        $leading = in_array($leading, ['none', 'label', 'dot', 'icon'], true) ? $leading : 'label';
        $labelText = is_string($props['labelText'] ?? null) ? (string) $props['labelText'] : 'New';
        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'left';
        $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
        $gap = is_numeric($props['gap'] ?? null) ? (int) $props['gap'] : 8;

        $textColor = $this->sanitizeColor($props['textColor'] ?? null) ?? '#111827';
        $backgroundType = is_string($props['backgroundType'] ?? null) ? trim((string) $props['backgroundType']) : 'classic';
        $backgroundType = in_array($backgroundType, ['classic', 'gradient'], true) ? $backgroundType : 'classic';
        $backgroundColor = $this->sanitizeColor($props['backgroundColor'] ?? null) ?? '#ffffff';

        $borderStyle = is_string($props['borderStyle'] ?? null) ? trim((string) $props['borderStyle']) : 'solid';
        $hasExplicitBorder = $borderStyle !== '' && $borderStyle !== 'default' && $borderStyle !== 'none';
        $borderColor = $this->sanitizeColor($props['borderColor'] ?? null) ?? '#e5e7eb';
        $borderWidthTop = $this->intOrNull($props['borderWidthTop'] ?? null) ?? 1;
        $borderWidthRight = $this->intOrNull($props['borderWidthRight'] ?? null) ?? 1;
        $borderWidthBottom = $this->intOrNull($props['borderWidthBottom'] ?? null) ?? 1;
        $borderWidthLeft = $this->intOrNull($props['borderWidthLeft'] ?? null) ?? 1;
        $borderRadiusTop = $this->intOrNull($props['borderRadiusTop'] ?? null) ?? 9999;
        $borderRadiusRight = $this->intOrNull($props['borderRadiusRight'] ?? null) ?? 9999;
        $borderRadiusBottom = $this->intOrNull($props['borderRadiusBottom'] ?? null) ?? 9999;
        $borderRadiusLeft = $this->intOrNull($props['borderRadiusLeft'] ?? null) ?? 9999;

        $badgeBfxClass = $this->borderEffectClassName($props);
        $badgeBfxStyle = $this->borderEffectStyleVars($props);

        $useGradientBorder = ($props['useGradientBorder'] ?? null) === true && $badgeBfxClass === '';
        $borderGradientFrom = $this->sanitizeColor($props['borderGradientFrom'] ?? null) ?? '#3b82f6';
        $borderGradientTo = $this->sanitizeColor($props['borderGradientTo'] ?? null) ?? '#a855f7';

        $labelBg = $this->sanitizeColor($props['labelBg'] ?? null) ?? '#2563eb';
        $labelColor = $this->sanitizeColor($props['labelColor'] ?? null) ?? '#ffffff';
        $labelUseGradient = ($props['labelUseGradient'] ?? null) === true;
        $labelGradientFrom = $this->sanitizeColor($props['labelGradientFrom'] ?? null) ?? '#3b82f6';
        $labelGradientTo = $this->sanitizeColor($props['labelGradientTo'] ?? null) ?? '#1d4ed8';
        $labelFontSize = $this->sanitizeIntPx($props['labelFontSize'] ?? null, 8, 72) ?? max(11, ($this->intOrNull($props['fontSize'] ?? null) ?? 14) - 1);
        $labelFontWeight = is_string($props['labelFontWeight'] ?? null) ? trim((string) $props['labelFontWeight']) : '600';

        $dotColor = $this->sanitizeColor($props['dotColor'] ?? null) ?? '#22c55e';
        $dotSize = $this->intOrNull($props['dotSize'] ?? null) ?? 8;

        $iconUrl = is_string($props['iconUrl'] ?? null) ? (string) $props['iconUrl'] : '';
        $iconSvg = is_string($props['iconSvg'] ?? null) ? (string) $props['iconSvg'] : '';
        $iconColor = $this->sanitizeColor($props['iconColor'] ?? null) ?? $textColor;
        $iconSize = $this->intOrNull($props['iconSize'] ?? null) ?? 16;

        $paddingTop = $this->intOrNull($props['paddingTop'] ?? null) ?? 6;
        $paddingRight = $this->intOrNull($props['paddingRight'] ?? null) ?? 14;
        $paddingBottom = $this->intOrNull($props['paddingBottom'] ?? null) ?? 6;
        $paddingLeft = $this->intOrNull($props['paddingLeft'] ?? null) ?? ($leading === 'label' ? 6 : 10);

        $fontFamily = $this->sanitizeFontFamily($props['fontFamily'] ?? null);
        $fontSize = $this->sanitizeIntPx($props['fontSize'] ?? null, 8, 72) ?? 14;
        $fontWeight = is_string($props['fontWeight'] ?? null) ? trim((string) $props['fontWeight']) : '500';
        $lineHeight = $this->sanitizeFloatPx($props['lineHeight'] ?? null, 0, 300);
        $letterSpacing = $this->sanitizeFloatPx($props['letterSpacing'] ?? null, -50, 50);
        $wordSpacing = $this->sanitizeFloatPx($props['wordSpacing'] ?? null, -50, 50);

        $marginTop = $this->intOrNull($props['marginTop'] ?? null);
        $marginRight = $this->intOrNull($props['marginRight'] ?? null);
        $marginBottom = $this->intOrNull($props['marginBottom'] ?? null);
        $marginLeft = $this->intOrNull($props['marginLeft'] ?? null);

        $badgeId = 'mp-badge-' . uniqid();

        $justify = match ($align) {
            'center' => 'center',
            'right' => 'flex-end',
            default => 'flex-start',
        };

        $containerStyle = 'display:flex;justify-content:' . $justify . ';';
        if ($marginTop !== null) {
            $containerStyle .= 'margin-top:' . $marginTop . 'px;';
        }
        if ($marginRight !== null) {
            $containerStyle .= 'margin-right:' . $marginRight . 'px;';
        }
        if ($marginBottom !== null) {
            $containerStyle .= 'margin-bottom:' . $marginBottom . 'px;';
        }
        if ($marginLeft !== null) {
            $containerStyle .= 'margin-left:' . $marginLeft . 'px;';
        }

        $badgeStyle = 'display:inline-flex;align-items:center;gap:' . $gap . 'px;box-sizing:border-box;';
        $badgeStyle .= 'padding:' . $paddingTop . 'px ' . $paddingRight . 'px ' . $paddingBottom . 'px ' . $paddingLeft . 'px;';
        $badgeStyle .= 'border-top-left-radius:' . $borderRadiusTop . 'px;';
        $badgeStyle .= 'border-top-right-radius:' . $borderRadiusRight . 'px;';
        $badgeStyle .= 'border-bottom-right-radius:' . $borderRadiusBottom . 'px;';
        $badgeStyle .= 'border-bottom-left-radius:' . $borderRadiusLeft . 'px;';
        $badgeStyle .= 'color:' . $textColor . ';';
        $badgeStyle .= 'font-size:' . $fontSize . 'px;';
        $badgeStyle .= 'font-weight:' . e($fontWeight) . ';';
        if ($fontFamily !== null) {
            $badgeStyle .= 'font-family:' . e($fontFamily) . ';';
        }
        if ($lineHeight !== null) {
            $badgeStyle .= 'line-height:' . $lineHeight . 'px;';
        }
        if ($letterSpacing !== null) {
            $badgeStyle .= 'letter-spacing:' . $letterSpacing . 'px;';
        }
        if ($wordSpacing !== null) {
            $badgeStyle .= 'word-spacing:' . $wordSpacing . 'px;';
        }

        if ($badgeBfxClass !== '') {
            $badgeStyle .= $badgeBfxStyle;
        }

        if ($backgroundType === 'gradient') {
            $bgFrom = $this->sanitizeColor($props['gradientFrom'] ?? null) ?? '#111827';
            $bgTo = $this->sanitizeColor($props['gradientTo'] ?? null) ?? '#3b82f6';
            $bgFromLoc = is_numeric($props['gradientFromLocation'] ?? null) ? (int) $props['gradientFromLocation'] : 0;
            $bgToLoc = is_numeric($props['gradientToLocation'] ?? null) ? (int) $props['gradientToLocation'] : 100;
            $gType = is_string($props['gradientType'] ?? null) ? trim((string) $props['gradientType']) : 'linear';
            $gType = in_array($gType, ['linear', 'radial'], true) ? $gType : 'linear';
            if ($gType === 'radial') {
                $position = is_string($props['gradientPosition'] ?? null) ? trim((string) $props['gradientPosition']) : 'top center';
                $allowedPositions = ['top left', 'top center', 'top right', 'center left', 'center center', 'center right', 'bottom left', 'bottom center', 'bottom right'];
                if (!in_array($position, $allowedPositions, true)) {
                    $position = 'top center';
                }
                $badgeStyle .= 'background-image:radial-gradient(at ' . $position . ', ' . $bgFrom . ' ' . $bgFromLoc . '%, ' . $bgTo . ' ' . $bgToLoc . '%);';
            } else {
                $badgeStyle .= 'background-image:linear-gradient(135deg, ' . $bgFrom . ' ' . $bgFromLoc . '%, ' . $bgTo . ' ' . $bgToLoc . '%);';
            }
        } else {
            $badgeStyle .= 'background-color:' . $backgroundColor . ';';
        }

        if ($useGradientBorder) {
            $badgeStyle .= 'border-style:solid;border-color:transparent;';
        } elseif ($hasExplicitBorder) {
            $badgeStyle .= 'border-style:' . $borderStyle . ';';
            $badgeStyle .= 'border-top-width:' . $borderWidthTop . 'px;';
            $badgeStyle .= 'border-right-width:' . $borderWidthRight . 'px;';
            $badgeStyle .= 'border-bottom-width:' . $borderWidthBottom . 'px;';
            $badgeStyle .= 'border-left-width:' . $borderWidthLeft . 'px;';
            $badgeStyle .= 'border-color:' . $borderColor . ';';
        }

        $pseudoStyle = '';
        if ($useGradientBorder) {
            $pseudoStyle = '#' . $badgeId . '{position:relative;background-clip:padding-box;}#' . $badgeId . '::before{content:"";position:absolute;inset:0;z-index:-1;margin:-' . $borderWidthTop . 'px -' . $borderWidthRight . 'px -' . $borderWidthBottom . 'px -' . $borderWidthLeft . 'px;border-radius:' . $borderRadiusTop . 'px ' . $borderRadiusRight . 'px ' . $borderRadiusBottom . 'px ' . $borderRadiusLeft . 'px;background:linear-gradient(135deg, ' . $borderGradientFrom . ', ' . $borderGradientTo . ');pointer-events:none;}';
        }

        $labelStyle = 'display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;';
        if ($labelUseGradient) {
            $labelStyle .= 'background-image:linear-gradient(135deg, ' . $labelGradientFrom . ', ' . $labelGradientTo . ');';
        } else {
            $labelStyle .= 'background-color:' . $labelBg . ';';
        }
        $labelStyle .= 'color:' . $labelColor . ';';
        $labelStyle .= 'padding:2px 10px;border-radius:9999px;font-size:' . $labelFontSize . 'px;font-weight:' . e($labelFontWeight) . ';line-height:1.2;white-space:nowrap;';

        $leadingHtml = '';
        if ($leading === 'label') {
            $leadingHtml = '<span style="' . $labelStyle . '">' . e($labelText) . '</span>';
        } elseif ($leading === 'dot') {
            $leadingHtml = '<span style="display:inline-block;width:' . $dotSize . 'px;height:' . $dotSize . 'px;border-radius:50%;background-color:' . $dotColor . ';flex-shrink:0;"></span>';
        } elseif ($leading === 'icon') {
            $iconStyle = 'display:inline-flex;align-items:center;justify-content:center;width:' . $iconSize . 'px;height:' . $iconSize . 'px;flex-shrink:0;color:' . $iconColor . ';';
            if ($iconUrl !== '') {
                $leadingHtml = '<span class="mp-badge__icon" style="' . $iconStyle . '"><img src="' . e($iconUrl) . '" alt="" style="width:100%;height:100%;object-fit:contain;" /></span>';
            } elseif ($iconSvg !== '') {
                $processedSvg = $this->injectSvgColor($iconSvg, $iconColor);
                $leadingHtml = '<span class="mp-badge__icon" style="' . $iconStyle . '">' . $processedSvg . '</span>';
            } else {
                $leadingHtml = '<span class="mp-badge__icon" style="' . $iconStyle . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:100%;height:100%;"><circle cx="12" cy="12" r="10" /><path d="M12 8v8" /><path d="M8 12h8" /></svg></span>';
            }
        }

        $styleTag = $pseudoStyle !== '' ? '<style>' . $pseudoStyle . '</style>' : '';

        return $styleTag
            . '<div class="mp-badge" style="' . $containerStyle . '">'
            . '<span id="' . $badgeId . '" class="mp-badge__inner' . ($badgeBfxClass !== '' ? ' ' . $badgeBfxClass : '') . '" style="' . $badgeStyle . '">'
            . $leadingHtml
            . '<span class="mp-badge__text">' . e($text) . '</span>'
            . '</span>'
            . '</div>';
    }

    private function getSinglePlan(array $props): ?array
    {
        $plan = $props['plan'] ?? null;
        if (is_array($plan)) {
            return $plan;
        }

        $plans = $props['plans'] ?? null;
        if (is_array($plans)) {
            $legacy = array_values(array_filter($plans, function ($p) {
                return is_array($p);
            }));
            $first = $legacy[0] ?? null;
            $second = $legacy[1] ?? null;
            if ($first) {
                return [
                    'title' => is_string($first['title'] ?? null) ? (string) $first['title'] : 'Plan',
                    'description' => is_string($first['description'] ?? null) ? (string) $first['description'] : '',
                    'monthlyPrice' => is_string($first['price'] ?? null) ? (string) $first['price'] : '$0',
                    'monthlyPeriod' => is_string($first['period'] ?? null) ? (string) $first['period'] : '/mo',
                    'yearlyPrice' => ($second && is_string($second['price'] ?? null)) ? (string) $second['price'] : (is_string($first['price'] ?? null) ? (string) $first['price'] : '$0'),
                    'yearlyPeriod' => ($second && is_string($second['period'] ?? null)) ? (string) $second['period'] : '/yr',
                    'features' => is_array($first['features'] ?? null) ? $first['features'] : [],
                    'buttonText' => is_string($first['buttonText'] ?? null) ? (string) $first['buttonText'] : 'Get started',
                    'buttonUrl' => is_string($first['buttonUrl'] ?? null) ? (string) $first['buttonUrl'] : '',
                    'buttonTarget' => is_string($first['buttonTarget'] ?? null) ? (string) $first['buttonTarget'] : '_self',
                    'featured' => isset($first['featured']) && $first['featured'] === true,
                ];
            }
        }

        return null;
    }

    private function parsePricingFeature(string $text): array
    {
        $t = trim($text);
        if ($t === '') {
            return ['text' => '', 'included' => true];
        }
        if (str_starts_with($t, '!') || str_starts_with($t, '-')) {
            return ['text' => trim(substr($t, 1)), 'included' => false];
        }
        return ['text' => $t, 'included' => true];
    }

    private function renderPricingTable(array $props): string
    {
        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'center';
        $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'center';
        $style = is_string($props['style'] ?? null) ? trim((string) $props['style']) : 'default';
        $style = in_array($style, ['default', 'classic', 'dark', 'header', 'minimal', 'split', 'gradient'], true) ? $style : 'default';

        $featureIconType = is_string($props['featureIconType'] ?? null) ? trim((string) $props['featureIconType']) : '';
        $featureIconType = in_array($featureIconType, ['check', 'custom'], true) ? $featureIconType : '';
        $featureIconUrl = is_string($props['featureIconUrl'] ?? null) ? trim((string) $props['featureIconUrl']) : '';

        if ($featureIconType === '' && $featureIconUrl !== '') {
            $featureIconType = 'custom';
        }
        if ($featureIconType === '') {
            $featureIconType = 'check';
        }

        $iconColor = $this->sanitizeColor($props['iconColor'] ?? null) ?? '#111827';
        $titleColor = $this->sanitizeColor($props['titleColor'] ?? null);
        $priceColor = $this->sanitizeColor($props['priceColor'] ?? null);
        $descriptionColor = $this->sanitizeColor($props['descriptionColor'] ?? null);
        $featureTextColor = $this->sanitizeColor($props['featureTextColor'] ?? null) ?? '#374151';
        $excludedFeatureTextColor = $this->sanitizeColor($props['excludedFeatureTextColor'] ?? null) ?? '#6b7280';
        $featureIconColor = $this->sanitizeColor($props['featureIconColor'] ?? null) ?? '#10b981';
        $excludedFeatureIconColor = $this->sanitizeColor($props['excludedFeatureIconColor'] ?? null) ?? '#ef4444';

        $titleTypo = $this->pricingTypographyStyle($props, 'title');
        $priceTypo = $this->pricingTypographyStyle($props, 'price');
        $descriptionTypo = $this->pricingTypographyStyle($props, 'description');
        $featureTypo = $this->pricingTypographyStyle($props, 'feature');
        $excludedFeatureTypo = $this->pricingTypographyStyle($props, 'excludedFeature');
        $buttonTypo = $this->pricingTypographyStyle($props, 'button');

        $includedIcon = '<span aria-hidden="true" style="color:' . $featureIconColor . ';margin-top:1px;">✓</span>';
        $excludedIcon = '<span aria-hidden="true" style="color:' . $excludedFeatureIconColor . ';margin-top:1px;">✕</span>';
        $customIcon = $includedIcon;
        if ($featureIconType === 'custom' && $featureIconUrl !== '' && (bool) preg_match('/^https?:\/\//i', $featureIconUrl)) {
            $customIcon = '<img src="' . e($featureIconUrl) . '" alt="" aria-hidden="true" style="width:16px;height:16px;flex-shrink:0;object-fit:contain;margin-top:1px;" />';
        }

        $cardBg = $this->sanitizeColor($props['cardBackgroundColor'] ?? null) ?? '#ffffff';
        $cardBorder = $this->sanitizeColor($props['cardBorderColor'] ?? null) ?? '#e5e7eb';
        $cardBorderWidth = $this->intOrNull($props['cardBorderWidth'] ?? null);
        if ($cardBorderWidth === null || $cardBorderWidth < 0) {
            $cardBorderWidth = 1;
        }
        if ($cardBorderWidth > 20) {
            $cardBorderWidth = 20;
        }
        $cardRadius = $this->intOrNull($props['cardBorderRadius'] ?? null);
        if ($cardRadius === null || $cardRadius < 0) {
            $cardRadius = 14;
        }
        if ($cardRadius > 80) {
            $cardRadius = 80;
        }
        $cardPadding = $this->intOrNull($props['cardPadding'] ?? null);
        if ($cardPadding === null || $cardPadding < 0) {
            $cardPadding = 20;
        }
        if ($cardPadding > 120) {
            $cardPadding = 120;
        }

        $featuredBg = $this->sanitizeColor($props['featuredBackgroundColor'] ?? null);
        $featuredBorder = $this->sanitizeColor($props['featuredBorderColor'] ?? null);

        $btnBg = $this->sanitizeColor($props['buttonBackgroundColor'] ?? null) ?? '#2563eb';
        $btnText = $this->sanitizeColor($props['buttonTextColor'] ?? null) ?? '#ffffff';
        $btnRadius = $this->intOrNull($props['buttonBorderRadius'] ?? null);
        if ($btnRadius === null || $btnRadius < 0) {
            $btnRadius = 10;
        }
        if ($btnRadius > 80) {
            $btnRadius = 80;
        }

        $headerBg = $this->sanitizeColor($props['headerBackgroundColor'] ?? null) ?? '#3b82f6';
        $headerText = $this->sanitizeColor($props['headerTextColor'] ?? null) ?? '#ffffff';
        $badgeBg = $this->sanitizeColor($props['badgeBackgroundColor'] ?? null) ?? '#111827';
        $badgeText = $this->sanitizeColor($props['badgeTextColor'] ?? null) ?? '#ffffff';
        $darkBg = $this->sanitizeColor($props['darkBackgroundColor'] ?? null) ?? '#0f172a';
        $darkText = $this->sanitizeColor($props['darkTextColor'] ?? null) ?? '#f8fafc';
        $gradientFrom = $this->sanitizeColor($props['gradientFrom'] ?? null) ?? '#8b5cf6';
        $gradientTo = $this->sanitizeColor($props['gradientTo'] ?? null) ?? '#f59e0b';

        $plan = $this->getSinglePlan($props);
        if ($plan === null) {
            return '';
        }

        $plan['badge'] = is_string($plan['badge'] ?? null) ? trim((string) $plan['badge']) : '';
        $plan['icon'] = is_string($plan['icon'] ?? null) ? trim((string) $plan['icon']) : '';
        $plan['featuresTitle'] = is_string($plan['featuresTitle'] ?? null) ? trim((string) $plan['featuresTitle']) : '';
        $plan['discount'] = is_string($plan['discount'] ?? null) ? trim((string) $plan['discount']) : '';

        $cardBgType = is_string($props['cardBackgroundType'] ?? null) ? trim((string) $props['cardBackgroundType']) : 'color';
        $cardBgType = in_array($cardBgType, ['color', 'gradient', 'image'], true) ? $cardBgType : 'color';
        $cardBackgroundStyle = '';
        if ($plan['featured'] && $featuredBg !== null) {
            $cardBackgroundStyle = 'background-color:' . $featuredBg . ';';
        } elseif ($cardBgType === 'image') {
            $url = is_string($props['cardBackgroundImageUrl'] ?? null) ? trim((string) $props['cardBackgroundImageUrl']) : '';
            if ($url !== '') {
                $cardBackgroundStyle = 'background-color:#ffffff;background-image:url("' . e($url) . '");background-size:cover;background-position:center;background-repeat:no-repeat;';
            } else {
                $cardBackgroundStyle = 'background-color:#ffffff;';
            }
        } elseif ($cardBgType === 'gradient') {
            $from = $this->sanitizeColor($props['cardBackgroundGradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($props['cardBackgroundGradientTo'] ?? null) ?? '#3b82f6';
            $cardGradientType = is_string($props['cardBackgroundGradientType'] ?? null) ? trim((string) $props['cardBackgroundGradientType']) : 'linear';
            $cardGradientType = in_array($cardGradientType, ['linear', 'radial'], true) ? $cardGradientType : 'linear';
            if ($cardGradientType === 'radial') {
                $position = is_string($props['cardBackgroundGradientPosition'] ?? null) ? trim((string) $props['cardBackgroundGradientPosition']) : 'top center';
                $cardBackgroundStyle = 'background-image:radial-gradient(at ' . $position . ', ' . $from . ', ' . $to . ');';
            } else {
                $cardBackgroundStyle = 'background-image:linear-gradient(135deg, ' . $from . ', ' . $to . ');';
            }
        } else {
            $cardBackgroundStyle = 'background-color:' . $cardBg . ';';
        }

        $wrapperStyle = '';
        if (isset($props['marginTop']) && is_numeric($props['marginTop'])) {
            $wrapperStyle .= 'margin-top:' . (int) $props['marginTop'] . 'px;';
        }
        if (isset($props['marginRight']) && is_numeric($props['marginRight'])) {
            $wrapperStyle .= 'margin-right:' . (int) $props['marginRight'] . 'px;';
        }
        if (isset($props['marginBottom']) && is_numeric($props['marginBottom'])) {
            $wrapperStyle .= 'margin-bottom:' . (int) $props['marginBottom'] . 'px;';
        }
        if (isset($props['marginLeft']) && is_numeric($props['marginLeft'])) {
            $wrapperStyle .= 'margin-left:' . (int) $props['marginLeft'] . 'px;';
        }
        if (isset($props['paddingTop']) && is_numeric($props['paddingTop'])) {
            $wrapperStyle .= 'padding-top:' . (int) $props['paddingTop'] . 'px;';
        }
        if (isset($props['paddingRight']) && is_numeric($props['paddingRight'])) {
            $wrapperStyle .= 'padding-right:' . (int) $props['paddingRight'] . 'px;';
        }
        if (isset($props['paddingBottom']) && is_numeric($props['paddingBottom'])) {
            $wrapperStyle .= 'padding-bottom:' . (int) $props['paddingBottom'] . 'px;';
        }
        if (isset($props['paddingLeft']) && is_numeric($props['paddingLeft'])) {
            $wrapperStyle .= 'padding-left:' . (int) $props['paddingLeft'] . 'px;';
        }

        $textAlign = 'text-align:' . $align . ';';
        $justifyContent = $align === 'left' ? 'flex-start' : ($align === 'right' ? 'flex-end' : 'center');

        $btnStyle = 'display:block;width:100%;text-decoration:none;text-align:center;font-weight:600;padding:10px 14px;';
        $btnStyle .= 'background-color:' . $btnBg . ';color:' . $btnText . ';border-radius:' . (string) $btnRadius . 'px;';
        $btnStyle .= $buttonTypo;

        $btnTag = 'button';
        $btnAttrs = ' type="button"';
        if (is_string($plan['buttonUrl'] ?? null) && $plan['buttonUrl'] !== '' && $this->isSafeHref($plan['buttonUrl'])) {
            $btnTag = 'a';
            $btnAttrs = ' href="' . e($plan['buttonUrl']) . '" target="' . e($plan['buttonTarget'] ?? '_self') . '" rel="noopener noreferrer"';
        }

        $features = [];
        if (is_array($plan['features'] ?? null)) {
            foreach ($plan['features'] as $f) {
                if (!is_string($f)) {
                    continue;
                }
                $parsed = $this->parsePricingFeature($f);
                if ($parsed['text'] === '') {
                    continue;
                }
                $features[] = $parsed;
                if (count($features) >= 10) {
                    break;
                }
            }
        }

        $featuresHtml = '';
        foreach ($features as $f) {
            $icon = $featureIconType === 'custom' ? $customIcon : ($f['included'] ? $includedIcon : $excludedIcon);
            $liStyle = 'display:flex;gap:8px;align-items:flex-start;font-size:14px;';
            $liStyle .= 'color:' . ($f['included'] ? $featureTextColor : $excludedFeatureTextColor) . ';';
            $liStyle .= $f['included'] ? $featureTypo : $excludedFeatureTypo;
            $featuresHtml .= '<li style="' . e($liStyle) . '">'
                . $icon
                . '<span>' . e($f['text']) . '</span>'
                . '</li>';
        }
        if ($featuresHtml !== '') {
            $featuresHtml = '<ul style="list-style:none;padding:0;margin:16px 0 0 0;display:flex;flex-direction:column;gap:8px;">' . $featuresHtml . '</ul>';
        }

        $cardBfxClass = $this->borderEffectClassName($props, 'cardBorder');
        $cardBfxStyle = $this->borderEffectStyleVars($props, 'cardBorder');

        $cardBaseStyle = $cardBackgroundStyle;
        $cardBaseStyle .= 'border-style:solid;';
        $cardBaseStyle .= 'border-color:' . ($plan['featured'] && $featuredBorder !== null ? $featuredBorder : $cardBorder) . ';';
        $cardBaseStyle .= 'border-width:' . (string) $cardBorderWidth . 'px;';
        $cardBaseStyle .= 'border-radius:' . (string) $cardRadius . 'px;';
        $cardBaseStyle .= 'padding:' . (string) $cardPadding . 'px;';

        $cardStyle = $cardBaseStyle;
        if ($cardBfxClass !== '') {
            $cardStyle .= $cardBfxStyle;
        }

        $toggleHtml = $this->renderPricingToggle();

        $priceColorCss = $priceColor ?? '#111827';
        $priceNumStyle = 'font-weight:700;font-size:34px;line-height:1;color:' . $priceColorCss . ';' . $priceTypo;
        $pricePeriodStyle = 'font-size:14px;color:' . $priceColorCss . ';margin-left:6px;';

        $priceMonthly = '<span style="' . e($priceNumStyle) . '" x-show="!annual">' . e($plan['monthlyPrice']) . '</span>'
            . '<span style="' . e($pricePeriodStyle) . '" x-show="!annual">' . e($plan['monthlyPeriod']) . '</span>';
        $priceYearly = '<span style="' . e($priceNumStyle) . '" x-show="annual">' . e($plan['yearlyPrice']) . '</span>'
            . '<span style="' . e($pricePeriodStyle) . '" x-show="annual">' . e($plan['yearlyPeriod']) . '</span>';

        $cardHtml = '';

        if ($style === 'classic') {
            $cardHtml = '<div class="mp-pricing__card' . ($cardBfxClass !== '' ? ' ' . $cardBfxClass : '') . '" style="' . e($cardStyle . 'max-width:420px;margin:0 auto;text-align:' . $align . ';') . '">'
                . ($plan['icon'] !== '' ? ('<div style="font-size:36px;margin-bottom:16px;color:' . $iconColor . ';">' . e($plan['icon']) . '</div>') : '')
                . '<div style="' . e('font-weight:700;font-size:20px;margin:0;color:' . ($titleColor ?? '#111827') . ';' . $titleTypo) . '">' . e($plan['title']) . '</div>'
                . ($plan['description'] !== '' ? ('<div style="' . e('margin-top:8px;font-size:14px;color:' . ($descriptionColor ?? '#4b5563') . ';' . $descriptionTypo) . '">' . e($plan['description']) . '</div>') : '')
                . '<div style="margin-top:16px;display:flex;align-items:flex-end;justify-content:' . $justifyContent . ';gap:4px;">' . $priceMonthly . $priceYearly . '</div>'
                . $featuresHtml
                . '<' . $btnTag . $btnAttrs . ' style="' . e($btnStyle . 'margin-top:18px;') . '">' . e($plan['buttonText']) . '</' . $btnTag . '>'
                . '</div>';
        } elseif ($style === 'dark') {
            $cardStyle = 'color:' . $darkText . ';border-style:solid;border-color:' . $cardBorder . ';border-width:' . (string) $cardBorderWidth . 'px;border-radius:' . (string) $cardRadius . 'px;padding:' . (string) $cardPadding . 'px;';
            if ($cardBgType === 'color') {
                $cardStyle = 'background-color:' . $darkBg . ';' . $cardStyle;
            } else {
                $cardStyle = $cardBackgroundStyle . $cardStyle;
            }
            if ($cardBfxClass !== '') {
                $cardStyle .= $cardBfxStyle;
            }
            $darkTitleStyle = 'font-weight:700;font-size:20px;color:' . ($titleColor ?? $darkText) . ';' . $titleTypo;
            $darkDescStyle = 'margin-top:8px;font-size:14px;opacity:0.8;' . ($descriptionColor !== null ? 'color:' . $descriptionColor . ';' : '') . $descriptionTypo;
            $darkPriceStyle = 'font-weight:700;font-size:34px;line-height:1;color:' . ($priceColor ?? $darkText) . ';' . $priceTypo;
            $darkPeriodStyle = 'font-size:14px;opacity:0.7;margin-left:6px;' . ($priceColor !== null ? 'color:' . $priceColor . ';' : '');
            $cardHtml = '<div class="mp-pricing__card' . ($cardBfxClass !== '' ? ' ' . $cardBfxClass : '') . '" style="' . e($cardStyle . 'max-width:420px;margin:0 auto;text-align:' . $align . ';') . '">'
                . '<div style="display:flex;align-items:center;justify-content:' . $justifyContent . ';gap:8px;">'
                . '<div style="' . e($darkTitleStyle) . '">' . e($plan['title']) . '</div>'
                . ($plan['badge'] !== '' ? ('<span style="font-size:12px;padding:4px 10px;border-radius:9999px;background-color:' . $badgeBg . ';color:' . $badgeText . ';">' . e($plan['badge']) . '</span>') : '')
                . '</div>'
                . ($plan['description'] !== '' ? ('<div style="' . e($darkDescStyle) . '">' . e($plan['description']) . '</div>') : '')
                . '<div style="margin-top:16px;display:flex;align-items:flex-end;justify-content:' . $justifyContent . ';gap:4px;">'
                . '<span style="' . e($darkPriceStyle) . '" x-show="!annual">' . e($plan['monthlyPrice']) . '</span>'
                . '<span style="' . e($darkPriceStyle) . '" x-show="annual">' . e($plan['yearlyPrice']) . '</span>'
                . '<span style="' . e($darkPeriodStyle) . '" x-show="!annual">' . e($plan['monthlyPeriod']) . '</span>'
                . '<span style="' . e($darkPeriodStyle) . '" x-show="annual">' . e($plan['yearlyPeriod']) . '</span>'
                . '</div>'
                . $featuresHtml
                . '<' . $btnTag . $btnAttrs . ' style="' . e($btnStyle . 'margin-top:18px;') . '">' . e($plan['buttonText']) . '</' . $btnTag . '>'
                . '</div>';
        } elseif ($style === 'header') {
            $cardStyle = $cardBaseStyle . 'padding:0;overflow:hidden;';
            if ($cardBfxClass !== '') {
                $cardStyle .= $cardBfxStyle;
            }
            $cardHtml = '<div class="mp-pricing__card' . ($cardBfxClass !== '' ? ' ' . $cardBfxClass : '') . '" style="' . e($cardStyle . 'max-width:420px;margin:0 auto;text-align:' . $align . ';') . '">'
                . ($plan['badge'] !== '' ? ('<div style="padding:8px 16px;font-size:14px;font-weight:600;background-color:' . $headerBg . ';color:' . $headerText . ';">' . e($plan['badge']) . '</div>') : '')
                . '<div style="padding:24px;">'
                . '<div style="display:flex;align-items:center;justify-content:' . $justifyContent . ';gap:8px;">'
                . '<div style="' . e('font-weight:700;font-size:20px;color:' . ($titleColor ?? '#111827') . ';' . $titleTypo) . '">' . e($plan['title']) . '</div>'
                . ($plan['discount'] !== '' ? ('<span style="font-size:12px;padding:4px 10px;border-radius:9999px;background-color:' . $badgeBg . ';color:' . $badgeText . ';">' . e($plan['discount']) . '</span>') : '')
                . '</div>'
                . ($plan['description'] !== '' ? ('<div style="' . e('margin-top:8px;font-size:14px;color:' . ($descriptionColor ?? '#4b5563') . ';' . $descriptionTypo) . '">' . e($plan['description']) . '</div>') : '')
                . '<div style="margin-top:16px;display:flex;align-items:flex-end;justify-content:' . $justifyContent . ';gap:4px;">' . $priceMonthly . $priceYearly . '</div>'
                . $featuresHtml
                . '<' . $btnTag . $btnAttrs . ' style="' . e($btnStyle . 'margin-top:18px;') . '">' . e($plan['buttonText']) . '</' . $btnTag . '>'
                . '</div></div>';
        } elseif ($style === 'minimal') {
            $cardStyle = $cardBaseStyle . 'padding:0;overflow:hidden;';
            if ($cardBfxClass !== '') {
                $cardStyle .= $cardBfxStyle;
            }
            $rowsHtml = '';
            foreach ($features as $f) {
                $icon = $featureIconType === 'custom' ? $customIcon : ($f['included'] ? $includedIcon : $excludedIcon);
                $rowStyle = 'display:flex;align-items:center;justify-content:center;gap:12px;padding:12px 16px;border-bottom:1px solid #f3f4f6;font-size:14px;';
                $rowStyle .= 'color:' . ($f['included'] ? $featureTextColor : $excludedFeatureTextColor) . ';';
                $rowStyle .= $f['included'] ? $featureTypo : $excludedFeatureTypo;
                $rowsHtml .= '<div style="' . e($rowStyle) . '">'
                    . $icon
                    . '<span>' . e($f['text']) . '</span></div>';
            }
            $minimalTitleStyle = 'font-size:14px;font-weight:600;opacity:0.8;color:' . ($titleColor ?? $headerText) . ';' . $titleTypo;
            $minimalPriceStyle = 'font-weight:700;font-size:34px;line-height:1;color:' . ($priceColor ?? $headerText) . ';' . $priceTypo;
            $minimalPeriodStyle = 'font-size:14px;opacity:0.8;margin-left:6px;' . ($priceColor !== null ? 'color:' . $priceColor . ';' : '');
            $cardHtml = '<div class="mp-pricing__card' . ($cardBfxClass !== '' ? ' ' . $cardBfxClass : '') . '" style="' . e($cardStyle . 'max-width:420px;margin:0 auto;text-align:' . $align . ';') . '">'
                . '<div style="padding:24px;background-color:' . $headerBg . ';color:' . $headerText . ';">'
                . '<div style="' . e($minimalTitleStyle) . '">' . e($plan['badge'] !== '' ? $plan['badge'] : $plan['title']) . '</div>'
                . '<div style="margin-top:8px;display:flex;align-items:flex-end;justify-content:center;gap:4px;">'
                . '<span style="' . e($minimalPriceStyle) . '" x-show="!annual">' . e($plan['monthlyPrice']) . '</span>'
                . '<span style="' . e($minimalPriceStyle) . '" x-show="annual">' . e($plan['yearlyPrice']) . '</span>'
                . '<span style="' . e($minimalPeriodStyle) . '" x-show="!annual">' . e($plan['monthlyPeriod']) . '</span>'
                . '<span style="' . e($minimalPeriodStyle) . '" x-show="annual">' . e($plan['yearlyPeriod']) . '</span>'
                . '</div></div>'
                . ($rowsHtml !== '' ? ('<div>' . $rowsHtml . '</div>') : '')
                . '<div style="padding:16px;"><' . $btnTag . $btnAttrs . ' style="' . e($btnStyle) . '">' . e($plan['buttonText']) . '</' . $btnTag . '></div>'
                . '</div>';
        } elseif ($style === 'split') {
            $cardStyle = $cardBaseStyle . 'padding:0;overflow:hidden;';
            if ($cardBfxClass !== '') {
                $cardStyle .= $cardBfxStyle;
            }
            $leftBg = $featuredBg ?? '#ccfbf1';
            $featuresTitle = $plan['featuresTitle'] !== '' ? ('<div style="font-weight:700;font-size:18px;color:#111827;margin-bottom:16px;">' . e($plan['featuresTitle']) . '</div>') : '';
            $cardHtml = '<div class="mp-pricing__card' . ($cardBfxClass !== '' ? ' ' . $cardBfxClass : '') . '" style="' . e($cardStyle . 'max-width:672px;margin:0 auto;') . '">'
                . '<div style="display:grid;grid-template-columns:1fr 1fr;">'
                . '<div style="padding:32px;background-color:' . $leftBg . ';">'
                . ($plan['badge'] !== '' ? ('<span style="display:inline-block;font-size:12px;padding:4px 10px;border-radius:9999px;background-color:' . $badgeBg . ';color:' . $badgeText . ';margin-bottom:16px;">' . e($plan['badge']) . '</span>') : '')
                . '<div style="' . e('font-weight:700;font-size:20px;color:' . ($titleColor ?? '#111827') . ';' . $titleTypo) . '">' . e($plan['title']) . '</div>'
                . ($plan['description'] !== '' ? ('<div style="' . e('margin-top:8px;font-size:14px;color:' . ($descriptionColor ?? '#4b5563') . ';' . $descriptionTypo) . '">' . e($plan['description']) . '</div>') : '')
                . '<div style="margin-top:16px;display:flex;align-items:flex-end;gap:4px;">'
                . '<span style="' . e('font-weight:700;font-size:34px;line-height:1;color:' . ($priceColor ?? '#111827') . ';' . $priceTypo) . '" x-show="!annual">' . e($plan['monthlyPrice']) . '</span>'
                . '<span style="' . e('font-weight:700;font-size:34px;line-height:1;color:' . ($priceColor ?? '#111827') . ';' . $priceTypo) . '" x-show="annual">' . e($plan['yearlyPrice']) . '</span>'
                . '<span style="' . e('font-size:14px;color:' . ($priceColor ?? '#4b5563') . ';margin-left:6px;') . '" x-show="!annual">' . e($plan['monthlyPeriod']) . '</span>'
                . '<span style="' . e('font-size:14px;color:' . ($priceColor ?? '#4b5563') . ';margin-left:6px;') . '" x-show="annual">' . e($plan['yearlyPeriod']) . '</span>'
                . '</div>'
                . '<' . $btnTag . $btnAttrs . ' style="' . e($btnStyle . 'margin-top:24px;') . '">' . e($plan['buttonText']) . '</' . $btnTag . '>'
                . '</div>'
                . '<div style="padding:32px;background-color:#ffffff;">'
                . $featuresTitle
                . $featuresHtml
                . '</div></div></div>';
        } elseif ($style === 'gradient') {
            $innerRadius = max(0, $cardRadius - 2);
            $gradientWrapper = 'padding:2px;border-radius:' . (string) $cardRadius . 'px;background:linear-gradient(135deg, ' . $gradientFrom . ', ' . $gradientTo . ');';
            $innerStyle = $cardBackgroundStyle . 'border-radius:' . (string) $innerRadius . 'px;padding:' . (string) $cardPadding . 'px;text-align:' . $align . ';';
            $cardHtml = '<div class="mp-pricing__card" style="' . e($gradientWrapper . 'max-width:420px;margin:0 auto;') . '">'
                . '<div style="' . e($innerStyle) . '">'
                . '<div style="display:flex;align-items:center;justify-content:' . $justifyContent . ';gap:8px;">'
                . '<div style="' . e('font-weight:700;font-size:20px;color:' . ($titleColor ?? '#111827') . ';' . $titleTypo) . '">' . e($plan['title']) . '</div>'
                . ($plan['badge'] !== '' ? ('<span style="font-size:12px;padding:4px 10px;border-radius:9999px;background-color:' . $badgeBg . ';color:' . $badgeText . ';">' . e($plan['badge']) . '</span>') : '')
                . '</div>'
                . ($plan['description'] !== '' ? ('<div style="' . e('margin-top:8px;font-size:14px;color:' . ($descriptionColor ?? '#4b5563') . ';' . $descriptionTypo) . '">' . e($plan['description']) . '</div>') : '')
                . '<div style="margin-top:16px;display:flex;align-items:flex-end;justify-content:' . $justifyContent . ';gap:4px;">' . $priceMonthly . $priceYearly . '</div>'
                . $featuresHtml
                . '<' . $btnTag . $btnAttrs . ' style="' . e($btnStyle . 'margin-top:18px;') . '">' . e($plan['buttonText']) . '</' . $btnTag . '>'
                . '</div></div>';
        } else {
            $cardHtml = '<div class="mp-pricing__card' . ($cardBfxClass !== '' ? ' ' . $cardBfxClass : '') . '" style="' . e($cardStyle . 'max-width:420px;margin:0 auto;text-align:' . $align . ';') . '">'
                . '<div style="' . e('font-weight:600;font-size:14px;margin:0;color:' . ($titleColor ?? '#111827') . ';' . $titleTypo) . '">' . e($plan['title']) . '</div>'
                . '<div style="margin-top:8px;display:flex;align-items:flex-end;justify-content:' . $justifyContent . ';gap:4px;">' . $priceMonthly . $priceYearly . '</div>'
                . ($plan['description'] !== '' ? ('<div style="' . e('margin-top:8px;font-size:14px;color:' . ($descriptionColor ?? '#4b5563') . ';' . $descriptionTypo) . '">' . e($plan['description']) . '</div>') : '')
                . $featuresHtml
                . '<' . $btnTag . $btnAttrs . ' style="' . e($btnStyle . 'margin-top:18px;') . '">' . e($plan['buttonText']) . '</' . $btnTag . '>'
                . '</div>';
        }

        if ($this->inPricingScope) {
            return '<div class="mp-pricing"' . ($wrapperStyle !== '' ? ' style="' . e($wrapperStyle) . '"' : '') . '>'
                . $cardHtml
                . '</div>';
        }

        return '<div class="mp-pricing" x-data="{ annual: false }"' . ($wrapperStyle !== '' ? ' style="' . e($wrapperStyle) . '"' : '') . '>'
            . $toggleHtml
            . $cardHtml
            . '</div>';
    }

    private function renderFaqs(array $props): string
    {
        $items = $props['items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        $openFirst = isset($props['openFirst']) && $props['openFirst'] === true;

        $carouselEnabled = isset($props['carouselEnabled']) && $props['carouselEnabled'] === true;
        $carouselSpeed = is_numeric($props['carouselSpeed'] ?? null) ? (float) $props['carouselSpeed'] : 4.0;
        if ($carouselSpeed < 1) {
            $carouselSpeed = 1;
        }
        if ($carouselSpeed > 60) {
            $carouselSpeed = 60;
        }
        $carouselArrows = !isset($props['carouselArrows']) || $props['carouselArrows'] !== false;
        $carouselArrowPosition = is_string($props['carouselArrowPosition'] ?? null) ? trim((string) $props['carouselArrowPosition']) : 'inside';
        $carouselArrowPosition = in_array($carouselArrowPosition, ['inside', 'outside'], true) ? $carouselArrowPosition : 'inside';
        $carouselEase = is_string($props['carouselEase'] ?? null) ? trim((string) $props['carouselEase']) : 'ease';
        $carouselEase = in_array($carouselEase, ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out'], true) ? $carouselEase : 'ease';

        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'left';
        $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';

        $gap = $this->intOrNull($props['gap'] ?? null) ?? 10;
        if ($gap < 0) {
            $gap = 0;
        }
        if ($gap > 120) {
            $gap = 120;
        }

        $wrapperStyle = '';

        $wrapperMarginLinked = $this->intOrNull($props['marginLinked'] ?? null);
        if ($wrapperMarginLinked !== null) {
            $wrapperStyle .= 'margin:' . (string) $wrapperMarginLinked . 'px;';
        } else {
            if (isset($props['marginTop']) && is_numeric($props['marginTop'])) {
                $wrapperStyle .= 'margin-top:' . (int) $props['marginTop'] . 'px;';
            }
            if (isset($props['marginRight']) && is_numeric($props['marginRight'])) {
                $wrapperStyle .= 'margin-right:' . (int) $props['marginRight'] . 'px;';
            }
            if (isset($props['marginBottom']) && is_numeric($props['marginBottom'])) {
                $wrapperStyle .= 'margin-bottom:' . (int) $props['marginBottom'] . 'px;';
            }
            if (isset($props['marginLeft']) && is_numeric($props['marginLeft'])) {
                $wrapperStyle .= 'margin-left:' . (int) $props['marginLeft'] . 'px;';
            }
        }

        $wrapperPaddingLinked = $this->intOrNull($props['paddingLinked'] ?? null);
        if ($wrapperPaddingLinked !== null) {
            $wrapperStyle .= 'padding:' . (string) $wrapperPaddingLinked . 'px;';
        } else {
            if (isset($props['paddingTop']) && is_numeric($props['paddingTop'])) {
                $wrapperStyle .= 'padding-top:' . (int) $props['paddingTop'] . 'px;';
            }
            if (isset($props['paddingRight']) && is_numeric($props['paddingRight'])) {
                $wrapperStyle .= 'padding-right:' . (int) $props['paddingRight'] . 'px;';
            }
            if (isset($props['paddingBottom']) && is_numeric($props['paddingBottom'])) {
                $wrapperStyle .= 'padding-bottom:' . (int) $props['paddingBottom'] . 'px;';
            }
            if (isset($props['paddingLeft']) && is_numeric($props['paddingLeft'])) {
                $wrapperStyle .= 'padding-left:' . (int) $props['paddingLeft'] . 'px;';
            }
        }

        $bgType = is_string($props['backgroundType'] ?? null) ? trim((string) $props['backgroundType']) : 'classic';
        $bgType = in_array($bgType, ['classic', 'gradient'], true) ? $bgType : 'classic';
        if ($bgType === 'gradient') {
            $from = $this->sanitizeColor($props['gradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($props['gradientTo'] ?? null) ?? '#3b82f6';
            $fromLoc = $this->intOrNull($props['gradientFromLocation'] ?? null) ?? 0;
            $toLoc = $this->intOrNull($props['gradientToLocation'] ?? null) ?? 100;
            if ($fromLoc < 0) {
                $fromLoc = 0;
            }
            if ($fromLoc > 100) {
                $fromLoc = 100;
            }
            if ($toLoc < 0) {
                $toLoc = 0;
            }
            if ($toLoc > 100) {
                $toLoc = 100;
            }

            $gType = is_string($props['gradientType'] ?? null) ? trim((string) $props['gradientType']) : 'linear';
            $gType = in_array($gType, ['linear', 'radial'], true) ? $gType : 'linear';

            if ($gType === 'radial') {
                $position = is_string($props['gradientPosition'] ?? null) ? trim((string) $props['gradientPosition']) : '';
                $allowedPositions = [
                    'top left',
                    'top center',
                    'top right',
                    'center left',
                    'center center',
                    'center right',
                    'bottom left',
                    'bottom center',
                    'bottom right',
                ];
                if (!in_array($position, $allowedPositions, true)) {
                    $position = 'top center';
                }
                $wrapperStyle .= 'background-image:radial-gradient(at ' . $position . ', ' . $from . ' ' . (string) $fromLoc . '%, ' . $to . ' ' . (string) $toLoc . '%);';
            } else {
                $wrapperStyle .= 'background-image:linear-gradient(135deg, ' . $from . ' ' . (string) $fromLoc . '%, ' . $to . ' ' . (string) $toLoc . '%);';
            }
        } else {
            $bg = $this->sanitizeColor($props['backgroundColor'] ?? null);
            if ($bg !== null) {
                $wrapperStyle .= 'background-color:' . $bg . ';';
            }
        }

        $wrapperBackdropBlur = $this->intOrNull($props['backdropBlur'] ?? null);
        if ($wrapperBackdropBlur !== null && $wrapperBackdropBlur > 0) {
            $wrapperBackdropBlur = min(40, $wrapperBackdropBlur);
            $wrapperStyle .= '-webkit-backdrop-filter:blur(' . (string) $wrapperBackdropBlur . 'px);backdrop-filter:blur(' . (string) $wrapperBackdropBlur . 'px);';
        }

        $itemBgType = is_string($props['itemBackgroundType'] ?? null) ? trim((string) $props['itemBackgroundType']) : 'color';
        $itemBgType = $itemBgType === 'gradient' ? 'gradient' : 'color';
        $itemBg = $this->sanitizeColor($props['itemBackgroundColor'] ?? null) ?? '#ffffff';
        $itemGradientFrom = $this->sanitizeColor($props['itemGradientFrom'] ?? null) ?? '#111827';
        $itemGradientTo = $this->sanitizeColor($props['itemGradientTo'] ?? null) ?? '#3b82f6';
        $itemGradientFromLocation = $this->intOrNull($props['itemGradientFromLocation'] ?? null) ?? 0;
        $itemGradientToLocation = $this->intOrNull($props['itemGradientToLocation'] ?? null) ?? 100;
        $itemGradientFromLocation = max(0, min(100, $itemGradientFromLocation));
        $itemGradientToLocation = max(0, min(100, $itemGradientToLocation));
        $itemGradientType = is_string($props['itemGradientType'] ?? null) ? trim((string) $props['itemGradientType']) : 'linear';
        $itemGradientType = $itemGradientType === 'radial' ? 'radial' : 'linear';
        $itemGradientPosition = is_string($props['itemGradientPosition'] ?? null) ? trim((string) $props['itemGradientPosition']) : 'top center';
        if (!in_array($itemGradientPosition, [
            'top left', 'top center', 'top right',
            'center left', 'center center', 'center right',
            'bottom left', 'bottom center', 'bottom right',
        ], true)) {
            $itemGradientPosition = 'top center';
        }
        $itemGradientAngle = $this->intOrNull($props['itemGradientAngle'] ?? null) ?? 135;
        $itemBackdropBlur = $this->intOrNull($props['itemBackdropBlur'] ?? null);

        $itemBorderStyle = is_string($props['itemBorderStyle'] ?? null) ? trim((string) $props['itemBorderStyle']) : '';
        $itemBorderStyle = in_array($itemBorderStyle, ['solid', 'dashed', 'dotted'], true) ? $itemBorderStyle : 'solid';
        $itemBorder = $this->sanitizeColor($props['itemBorderColor'] ?? null) ?? '#e5e7eb';
        $itemBorderWidth = $this->intOrNull($props['itemBorderWidth'] ?? null);
        if ($itemBorderWidth === null || $itemBorderWidth < 0) {
            $itemBorderWidth = 1;
        }
        if ($itemBorderWidth > 20) {
            $itemBorderWidth = 20;
        }
        $itemBorderWidthLinked = $this->intOrNull($props['itemBorderWidthLinked'] ?? null);
        $itemBorderWidthTop = $this->intOrNull($props['itemBorderWidthTop'] ?? null);
        $itemBorderWidthRight = $this->intOrNull($props['itemBorderWidthRight'] ?? null);
        $itemBorderWidthBottom = $this->intOrNull($props['itemBorderWidthBottom'] ?? null);
        $itemBorderWidthLeft = $this->intOrNull($props['itemBorderWidthLeft'] ?? null);

        $itemRadius = $this->intOrNull($props['itemBorderRadius'] ?? null);
        if ($itemRadius === null || $itemRadius < 0) {
            $itemRadius = 10;
        }
        if ($itemRadius > 80) {
            $itemRadius = 80;
        }

        $itemPaddingX = $this->intOrNull($props['itemPaddingX'] ?? null);
        if ($itemPaddingX === null || $itemPaddingX < 0) {
            $itemPaddingX = 14;
        }
        if ($itemPaddingX > 160) {
            $itemPaddingX = 160;
        }
        $itemPaddingY = $this->intOrNull($props['itemPaddingY'] ?? null);
        if ($itemPaddingY === null || $itemPaddingY < 0) {
            $itemPaddingY = 12;
        }
        if ($itemPaddingY > 160) {
            $itemPaddingY = 160;
        }
        $itemPaddingLinked = $this->intOrNull($props['itemPaddingLinked'] ?? null);
        $itemPaddingTop = $this->intOrNull($props['itemPaddingTop'] ?? null);
        $itemPaddingRight = $this->intOrNull($props['itemPaddingRight'] ?? null);
        $itemPaddingBottom = $this->intOrNull($props['itemPaddingBottom'] ?? null);
        $itemPaddingLeft = $this->intOrNull($props['itemPaddingLeft'] ?? null);

        $itemMarginLinked = $this->intOrNull($props['itemMarginLinked'] ?? null);
        $itemMarginTop = $this->intOrNull($props['itemMarginTop'] ?? null);
        $itemMarginRight = $this->intOrNull($props['itemMarginRight'] ?? null);
        $itemMarginBottom = $this->intOrNull($props['itemMarginBottom'] ?? null);
        $itemMarginLeft = $this->intOrNull($props['itemMarginLeft'] ?? null);

        $iconType = is_string($props['iconType'] ?? null) ? trim((string) $props['iconType']) : 'none';
        $iconType = in_array($iconType, ['chevron', 'plus', 'arrow'], true) ? $iconType : 'none';
        $iconPosition = (($props['iconPosition'] ?? '') === 'left') ? 'left' : 'right';
        $iconSvgs = [
            'chevron' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
            'plus' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>',
            'arrow' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>',
        ];
        $iconSvg = $iconType !== 'none' ? ($iconSvgs[$iconType] ?? '') : '';

        $questionColor = $this->sanitizeColor($props['questionColor'] ?? null);
        $questionFontFamily = $this->sanitizeFontFamily($props['questionFontFamily'] ?? null);
        $questionFontSize = $this->sanitizeIntPx($props['questionFontSize'] ?? null, 8, 80);
        $questionFontWeight = $this->sanitizeFontWeight($props['questionFontWeight'] ?? null);
        $questionLineHeight = $this->sanitizeIntPx($props['questionLineHeight'] ?? null, 8, 120);
        $questionLetterSpacing = $this->sanitizeIntPx($props['questionLetterSpacing'] ?? null, -10, 40);
        $questionWordSpacing = $this->sanitizeIntPx($props['questionWordSpacing'] ?? null, -10, 40);

        $questionBorderStyle = is_string($props['questionBorderStyle'] ?? null) ? trim((string) $props['questionBorderStyle']) : '';
        $questionBorderStyle = in_array($questionBorderStyle, ['solid', 'dashed', 'dotted'], true) ? $questionBorderStyle : '';
        $questionBorderColor = $this->sanitizeColor($props['questionBorderColor'] ?? null);
        $questionBorderWidthLinked = $this->intOrNull($props['questionBorderWidthLinked'] ?? null);
        $questionBorderWidthTop = $this->intOrNull($props['questionBorderWidthTop'] ?? null);
        $questionBorderWidthRight = $this->intOrNull($props['questionBorderWidthRight'] ?? null);
        $questionBorderWidthBottom = $this->intOrNull($props['questionBorderWidthBottom'] ?? null);
        $questionBorderWidthLeft = $this->intOrNull($props['questionBorderWidthLeft'] ?? null);

        $answerColor = $this->sanitizeColor($props['answerColor'] ?? null);
        $answerFontFamily = $this->sanitizeFontFamily($props['answerFontFamily'] ?? null);
        $answerFontSize = $this->sanitizeIntPx($props['answerFontSize'] ?? null, 8, 80);
        $answerFontWeight = $this->sanitizeFontWeight($props['answerFontWeight'] ?? null);
        $answerLineHeight = $this->sanitizeIntPx($props['answerLineHeight'] ?? null, 8, 140);
        $answerLetterSpacing = $this->sanitizeIntPx($props['answerLetterSpacing'] ?? null, -10, 40);
        $answerWordSpacing = $this->sanitizeIntPx($props['answerWordSpacing'] ?? null, -10, 40);

        $listStyle = 'display:flex;flex-direction:column;gap:' . (string) $gap . 'px;';

        $itemsHtml = '';
        $i = 0;
        foreach ($items as $it) {
            if ($i >= 50) {
                break;
            }
            if (!is_array($it)) {
                continue;
            }
            $q = is_string($it['question'] ?? null) ? (string) $it['question'] : '';
            $a = is_string($it['answer'] ?? null) ? (string) $it['answer'] : '';

            $q = trim($q);
            $a = trim($a);
            if ($q === '' && $a === '') {
                $i++;
                continue;
            }

            $detailsStyle = 'border-style:' . $itemBorderStyle . ';border-color:' . $itemBorder . ';';
            if ($itemBorderWidthLinked !== null) {
                $detailsStyle .= 'border-width:' . (string) $itemBorderWidthLinked . 'px;';
            } elseif ($itemBorderWidthTop !== null || $itemBorderWidthRight !== null || $itemBorderWidthBottom !== null || $itemBorderWidthLeft !== null) {
                $detailsStyle .= 'border-width:' . (string) ($itemBorderWidthTop ?? 0) . 'px ' . (string) ($itemBorderWidthRight ?? 0) . 'px ' . (string) ($itemBorderWidthBottom ?? 0) . 'px ' . (string) ($itemBorderWidthLeft ?? 0) . 'px;';
            } else {
                $detailsStyle .= 'border-width:' . (string) $itemBorderWidth . 'px;';
            }
            $detailsStyle .= 'border-radius:' . (string) $itemRadius . 'px;';

            if ($itemBgType === 'gradient') {
                if ($itemGradientType === 'radial') {
                    $detailsStyle .= 'background-image:radial-gradient(at ' . $itemGradientPosition . ', ' . $itemGradientFrom . ' ' . (string) $itemGradientFromLocation . '%, ' . $itemGradientTo . ' ' . (string) $itemGradientToLocation . '%);';
                } else {
                    $detailsStyle .= 'background-image:linear-gradient(' . (string) $itemGradientAngle . 'deg, ' . $itemGradientFrom . ' ' . (string) $itemGradientFromLocation . '%, ' . $itemGradientTo . ' ' . (string) $itemGradientToLocation . '%);';
                }
            } else {
                $detailsStyle .= 'background-color:' . $itemBg . ';';
            }

            if ($itemBackdropBlur !== null && $itemBackdropBlur > 0) {
                $itemBackdropBlur = min(40, $itemBackdropBlur);
                $detailsStyle .= '-webkit-backdrop-filter:blur(' . (string) $itemBackdropBlur . 'px);backdrop-filter:blur(' . (string) $itemBackdropBlur . 'px);';
            }

            if ($itemPaddingLinked !== null) {
                $detailsStyle .= 'padding:' . (string) $itemPaddingLinked . 'px;';
            } elseif ($itemPaddingTop !== null || $itemPaddingRight !== null || $itemPaddingBottom !== null || $itemPaddingLeft !== null) {
                $detailsStyle .= 'padding:' . (string) ($itemPaddingTop ?? $itemPaddingY) . 'px ' . (string) ($itemPaddingRight ?? $itemPaddingX) . 'px ' . (string) ($itemPaddingBottom ?? $itemPaddingY) . 'px ' . (string) ($itemPaddingLeft ?? $itemPaddingX) . 'px;';
            } else {
                $detailsStyle .= 'padding:' . (string) $itemPaddingY . 'px ' . (string) $itemPaddingX . 'px;';
            }

            if ($itemMarginLinked !== null) {
                $detailsStyle .= 'margin:' . (string) $itemMarginLinked . 'px;';
            } elseif ($itemMarginTop !== null || $itemMarginRight !== null || $itemMarginBottom !== null || $itemMarginLeft !== null) {
                $detailsStyle .= 'margin:' . (string) ($itemMarginTop ?? 0) . 'px ' . (string) ($itemMarginRight ?? 0) . 'px ' . (string) ($itemMarginBottom ?? 0) . 'px ' . (string) ($itemMarginLeft ?? 0) . 'px;';
            }

            if ($carouselEnabled) {
                $detailsStyle .= 'flex:0 0 100%;box-sizing:border-box;';
            }

            $summaryStyle = 'cursor:pointer;list-style:none;';
            $summaryStyle .= 'font-weight:' . (string) ($questionFontWeight ?? 600) . ';';
            if ($questionBorderStyle !== '') {
                $summaryStyle .= 'border-style:' . $questionBorderStyle . ';';
            }
            if ($questionBorderColor !== null) {
                $summaryStyle .= 'border-color:' . $questionBorderColor . ';';
            }
            if ($questionBorderWidthLinked !== null) {
                $summaryStyle .= 'border-width:' . (string) $questionBorderWidthLinked . 'px;';
            } elseif ($questionBorderWidthTop !== null || $questionBorderWidthRight !== null || $questionBorderWidthBottom !== null || $questionBorderWidthLeft !== null) {
                $summaryStyle .= 'border-width:' . (string) ($questionBorderWidthTop ?? 0) . 'px ' . (string) ($questionBorderWidthRight ?? 0) . 'px ' . (string) ($questionBorderWidthBottom ?? 0) . 'px ' . (string) ($questionBorderWidthLeft ?? 0) . 'px;';
            }
            if ($questionColor !== null) {
                $summaryStyle .= 'color:' . $questionColor . ';';
            }
            if ($questionFontFamily !== null) {
                $summaryStyle .= 'font-family:' . $questionFontFamily . ';';
            }
            if ($questionFontSize !== null) {
                $summaryStyle .= 'font-size:' . (string) $questionFontSize . 'px;';
            }
            if ($questionLineHeight !== null) {
                $summaryStyle .= 'line-height:' . (string) $questionLineHeight . 'px;';
            }
            if ($questionLetterSpacing !== null) {
                $summaryStyle .= 'letter-spacing:' . (string) $questionLetterSpacing . 'px;';
            }
            if ($questionWordSpacing !== null) {
                $summaryStyle .= 'word-spacing:' . (string) $questionWordSpacing . 'px;';
            }
            if ($iconSvg !== '') {
                $summaryStyle .= 'display:flex;align-items:center;gap:8px;';
            }

            $answerStyle = 'margin-top:8px;';
            if ($answerFontWeight !== null) {
                $answerStyle .= 'font-weight:' . (string) $answerFontWeight . ';';
            }
            if ($answerColor !== null) {
                $answerStyle .= 'color:' . $answerColor . ';';
            }
            if ($answerFontFamily !== null) {
                $answerStyle .= 'font-family:' . $answerFontFamily . ';';
            }
            if ($answerFontSize !== null) {
                $answerStyle .= 'font-size:' . (string) $answerFontSize . 'px;';
            }
            if ($answerLineHeight !== null) {
                $answerStyle .= 'line-height:' . (string) $answerLineHeight . 'px;';
            }
            if ($answerLetterSpacing !== null) {
                $answerStyle .= 'letter-spacing:' . (string) $answerLetterSpacing . 'px;';
            }
            if ($answerWordSpacing !== null) {
                $answerStyle .= 'word-spacing:' . (string) $answerWordSpacing . 'px;';
            }

            $answerHtml = $this->sanitizeRichText($a);
            if ($answerHtml === '') {
                $answerHtml = e($a !== '' ? $a : 'Answer');
            }

            $iconHtml = '';
            if ($iconSvg !== '') {
                $iconSpanStyle = 'display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;';
                if ($questionColor !== null) {
                    $iconSpanStyle .= 'color:' . $questionColor . ';';
                }
                $iconClass = 'mp-faqs__icon' . ($iconType === 'plus' ? ' mp-faqs__icon--plus' : '');
                $iconHtml = '<span class="' . e($iconClass) . '" style="' . e($iconSpanStyle) . '">' . $iconSvg . '</span>';
            }

            $questionSpan = '<span' . ($iconSvg !== '' ? ' style="flex:1;"' : '') . '>' . e($q !== '' ? $q : 'Question') . '</span>';
            $summaryInner = ($iconSvg !== '' && $iconPosition === 'left')
                ? $iconHtml . $questionSpan
                : $questionSpan . $iconHtml;

            $itemsHtml .= '<details class="mp-faqs__item"' . (($openFirst && $i === 0) ? ' open' : '') . ' style="' . e($detailsStyle) . '">' 
                . '<summary class="mp-faqs__q" style="' . e($summaryStyle) . '">' . $summaryInner . '</summary>'
                . '<div class="mp-faqs__a" style="' . e($answerStyle) . '">' . $answerHtml . '</div>'
                . '</details>';
            $i++;
        }

        if ($itemsHtml === '') {
            return '';
        }

        $alignStyle = 'text-align:' . $align . ';';

        $iconStyleTag = '';
        if ($iconSvg !== '') {
            $iconStyleTag = '<style>.mp-faqs__icon{transition:transform .3s ease;}.mp-faqs__item[open] .mp-faqs__icon{transform:rotate(180deg);}.mp-faqs__item[open] .mp-faqs__icon--plus{transform:rotate(45deg);}.mp-faqs__item--closing .mp-faqs__icon,.mp-faqs__item--closing .mp-faqs__icon--plus{transform:rotate(0deg);}</style>';
        }

        $carouselAttrs = '';
        if ($carouselEnabled) {
            $carouselAttrs = ' data-mp-faqs-carousel="1"'
                . ' data-mp-faqs-carousel-speed="' . e((string) $carouselSpeed) . '"'
                . ' data-mp-faqs-carousel-ease="' . e($carouselEase) . '"'
                . ' data-mp-faqs-carousel-arrows="' . e($carouselArrows ? '1' : '0') . '"'
                . ' data-mp-faqs-carousel-arrow-position="' . e($carouselArrowPosition) . '"';
        }

        if ($carouselEnabled) {
            $carouselContainerStyle = 'position:relative;';
            $viewportStyle = 'overflow:hidden;';
            $trackStyle = 'display:flex;flex-direction:row;gap:0;will-change:transform;';

            $btnBase = 'position:absolute;top:50%;transform:translateY(-50%);z-index:5;';
            $btnBase .= 'display:inline-flex;align-items:center;justify-content:center;';
            $btnBase .= 'width:36px;height:36px;border-radius:9999px;';
            $btnBase .= 'border:1px solid rgba(0,0,0,0.12);background:rgba(255,255,255,0.92);';
            $btnBase .= 'cursor:pointer;';

            $leftPos = $carouselArrowPosition === 'outside' ? 'left:-44px;' : 'left:12px;';
            $rightPos = $carouselArrowPosition === 'outside' ? 'right:-44px;' : 'right:12px;';

            $controlsHtml = '';
            if ($carouselArrows) {
                $controlsHtml = '<button type="button" data-mp-faqs-prev aria-label="Previous" style="' . e($btnBase . $leftPos) . '">'
                    . '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>'
                    . '</button>'
                    . '<button type="button" data-mp-faqs-next aria-label="Next" style="' . e($btnBase . $rightPos) . '">'
                    . '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>'
                    . '</button>';
            }

            $carouselHtml = '<div class="mp-faqs__carousel" style="' . e($carouselContainerStyle) . '">'
                . $controlsHtml
                . '<div class="mp-faqs__viewport" style="' . e($viewportStyle) . '">'
                . '<div class="mp-faqs__track" style="' . e($trackStyle) . '">'
                . $itemsHtml
                . '</div>'
                . '</div>'
                . '</div>';

            return $iconStyleTag . '<div class="mp-faqs"' . $carouselAttrs . ($wrapperStyle !== '' ? ' style="' . e($alignStyle . $wrapperStyle) . '"' : ' style="' . e($alignStyle) . '"') . '>'
                . $carouselHtml
                . '</div>';
        }

        return $iconStyleTag . '<div class="mp-faqs"' . ($wrapperStyle !== '' ? ' style="' . e($alignStyle . $wrapperStyle) . '"' : ' style="' . e($alignStyle) . '"') . '>'
            . '<div class="mp-faqs__list" style="' . e($listStyle) . '">'
            . $itemsHtml
            . '</div>'
            . '</div>';
    }

    private function renderIcon(array $props): string
    {
        $iconUrl = is_string($props['iconUrl'] ?? null) ? trim((string) $props['iconUrl']) : '';
        $iconSvg = is_string($props['iconSvg'] ?? null) ? trim((string) $props['iconSvg']) : '';
        $heading = is_string($props['heading'] ?? null) ? (string) $props['heading'] : 'Heading';
        $subtext = is_string($props['subtext'] ?? null) ? (string) $props['subtext'] : 'Subtext';
        $subtextPlain = trim(preg_replace('/<[^>]*>/', '', str_replace('&nbsp;', ' ', $subtext)) ?? $subtext);
        $hasSubtext = $subtextPlain !== '' || preg_match('/<(img|br|hr|video|iframe|svg)\b/i', $subtext) === 1;
        
        $iconPosition = is_string($props['iconPosition'] ?? null) ? trim((string) $props['iconPosition']) : 'top';
        $iconPosition = in_array($iconPosition, ['top', 'left', 'right'], true) ? $iconPosition : 'top';
        
        $iconSize = $this->intOrNull($props['iconSize'] ?? null) ?? 64;
        if ($iconSize < 16) {
            $iconSize = 16;
        }
        if ($iconSize > 256) {
            $iconSize = 256;
        }
        
        $iconGap = $this->intOrNull($props['iconGap'] ?? null) ?? 16;
        if ($iconGap < 0) {
            $iconGap = 0;
        }
        
        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'center';
        $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'center';
        
        // Container style
        $containerStyle = 'text-align:' . $align . ';';
        
        // Padding
        if (isset($props['paddingTop']) && is_numeric($props['paddingTop'])) {
            $containerStyle .= 'padding-top:' . (int)$props['paddingTop'] . 'px;';
        }
        if (isset($props['paddingRight']) && is_numeric($props['paddingRight'])) {
            $containerStyle .= 'padding-right:' . (int)$props['paddingRight'] . 'px;';
        }
        if (isset($props['paddingBottom']) && is_numeric($props['paddingBottom'])) {
            $containerStyle .= 'padding-bottom:' . (int)$props['paddingBottom'] . 'px;';
        }
        if (isset($props['paddingLeft']) && is_numeric($props['paddingLeft'])) {
            $containerStyle .= 'padding-left:' . (int)$props['paddingLeft'] . 'px;';
        }
        
        // Margin
        if (isset($props['marginTop']) && is_numeric($props['marginTop'])) {
            $containerStyle .= 'margin-top:' . (int)$props['marginTop'] . 'px;';
        }
        if (isset($props['marginRight']) && is_numeric($props['marginRight'])) {
            $containerStyle .= 'margin-right:' . (int)$props['marginRight'] . 'px;';
        }
        if (isset($props['marginBottom']) && is_numeric($props['marginBottom'])) {
            $containerStyle .= 'margin-bottom:' . (int)$props['marginBottom'] . 'px;';
        }
        if (isset($props['marginLeft']) && is_numeric($props['marginLeft'])) {
            $containerStyle .= 'margin-left:' . (int)$props['marginLeft'] . 'px;';
        }
        
        // Background color
        if (isset($props['backgroundColor']) && is_string($props['backgroundColor']) && $props['backgroundColor'] !== '') {
            $containerStyle .= 'background-color:' . e($props['backgroundColor']) . ';';
        }

        // Border
        $hasBorderWidth = false;
        if (isset($props['borderWidthTop']) && is_numeric($props['borderWidthTop'])) {
            $containerStyle .= 'border-top-width:' . (int)$props['borderWidthTop'] . 'px;';
            $hasBorderWidth = true;
        }
        if (isset($props['borderWidthRight']) && is_numeric($props['borderWidthRight'])) {
            $containerStyle .= 'border-right-width:' . (int)$props['borderWidthRight'] . 'px;';
            $hasBorderWidth = true;
        }
        if (isset($props['borderWidthBottom']) && is_numeric($props['borderWidthBottom'])) {
            $containerStyle .= 'border-bottom-width:' . (int)$props['borderWidthBottom'] . 'px;';
            $hasBorderWidth = true;
        }
        if (isset($props['borderWidthLeft']) && is_numeric($props['borderWidthLeft'])) {
            $containerStyle .= 'border-left-width:' . (int)$props['borderWidthLeft'] . 'px;';
            $hasBorderWidth = true;
        }

        $borderStyle = is_string($props['borderStyle'] ?? null) ? trim((string) $props['borderStyle']) : '';
        if ($borderStyle === 'default') {
            $borderStyle = '';
        }
        if ($borderStyle !== '') {
            $containerStyle .= 'border-style:' . e($borderStyle) . ';';
        } elseif ($hasBorderWidth) {
            $containerStyle .= 'border-style:solid;';
        }

        if (isset($props['borderColor']) && is_string($props['borderColor']) && $props['borderColor'] !== '') {
            $containerStyle .= 'border-color:' . e($props['borderColor']) . ';';
        } elseif ($hasBorderWidth) {
            $containerStyle .= 'border-color:#e5e7eb;';
        }
        
        // Border radius
        if (isset($props['borderRadius']) && is_numeric($props['borderRadius'])) {
            $containerStyle .= 'border-radius:' . (int)$props['borderRadius'] . 'px;';
        }
        if (isset($props['borderRadiusTop']) && is_numeric($props['borderRadiusTop'])) {
            $containerStyle .= 'border-top-left-radius:' . (int)$props['borderRadiusTop'] . 'px;';
        }
        if (isset($props['borderRadiusRight']) && is_numeric($props['borderRadiusRight'])) {
            $containerStyle .= 'border-top-right-radius:' . (int)$props['borderRadiusRight'] . 'px;';
        }
        if (isset($props['borderRadiusBottom']) && is_numeric($props['borderRadiusBottom'])) {
            $containerStyle .= 'border-bottom-right-radius:' . (int)$props['borderRadiusBottom'] . 'px;';
        }
        if (isset($props['borderRadiusLeft']) && is_numeric($props['borderRadiusLeft'])) {
            $containerStyle .= 'border-bottom-left-radius:' . (int)$props['borderRadiusLeft'] . 'px;';
        }
        
        // Flex container style
        $flexDirection = $iconPosition === 'top' ? 'column' : 'row';
        if ($iconPosition === 'right') {
            $flexDirection = 'row-reverse';
        }
        
        $alignItems = match ($align) {
            'left' => 'flex-start',
            'right' => 'flex-end',
            default => 'center',
        };
        
        $justifyContent = match ($align) {
            'left' => 'flex-start',
            'right' => 'flex-end',
            default => 'center',
        };
        
        $flexStyle = 'display:flex;flex-direction:' . $flexDirection . ';align-items:' . $alignItems . ';gap:' . $iconGap . 'px;';
        if ($iconPosition !== 'top') {
            $flexStyle .= 'justify-content:' . $justifyContent . ';';
        }
        
        // Icon element
        $iconHtml = '';
        $iconContainerStyle = 'display:inline-block;';
        
        // Icon background
        $iconBgType = isset($props['iconBackgroundType']) && is_string($props['iconBackgroundType']) ? $props['iconBackgroundType'] : 'color';
        if ($iconBgType === 'gradient') {
            $from = isset($props['iconGradientFrom']) && is_string($props['iconGradientFrom']) && $props['iconGradientFrom'] !== '' ? e($props['iconGradientFrom']) : '#111827';
            $to = isset($props['iconGradientTo']) && is_string($props['iconGradientTo']) && $props['iconGradientTo'] !== '' ? e($props['iconGradientTo']) : '#3b82f6';
            $gradientType = isset($props['iconGradientType']) && $props['iconGradientType'] === 'radial' ? 'radial' : 'linear';
            if ($gradientType === 'radial') {
                $position = isset($props['iconGradientPosition']) && is_string($props['iconGradientPosition']) && $props['iconGradientPosition'] !== '' ? e($props['iconGradientPosition']) : 'top center';
                $iconContainerStyle .= 'background:radial-gradient(at ' . $position . ', ' . $from . ', ' . $to . ');';
            } else {
                $angle = isset($props['iconGradientAngle']) && is_numeric($props['iconGradientAngle']) ? (int)$props['iconGradientAngle'] : 135;
                $iconContainerStyle .= 'background:linear-gradient(' . $angle . 'deg, ' . $from . ', ' . $to . ');';
            }
        } elseif (isset($props['iconBackgroundColor']) && is_string($props['iconBackgroundColor']) && $props['iconBackgroundColor'] !== '') {
            $iconContainerStyle .= 'background-color:' . e($props['iconBackgroundColor']) . ';';
        }
        
        // Icon padding
        if (isset($props['iconPaddingTop']) && is_numeric($props['iconPaddingTop'])) {
            $iconContainerStyle .= 'padding-top:' . (int)$props['iconPaddingTop'] . 'px;';
        }
        if (isset($props['iconPaddingRight']) && is_numeric($props['iconPaddingRight'])) {
            $iconContainerStyle .= 'padding-right:' . (int)$props['iconPaddingRight'] . 'px;';
        }
        if (isset($props['iconPaddingBottom']) && is_numeric($props['iconPaddingBottom'])) {
            $iconContainerStyle .= 'padding-bottom:' . (int)$props['iconPaddingBottom'] . 'px;';
        }
        if (isset($props['iconPaddingLeft']) && is_numeric($props['iconPaddingLeft'])) {
            $iconContainerStyle .= 'padding-left:' . (int)$props['iconPaddingLeft'] . 'px;';
        }
        
        // Icon border radius
        if (isset($props['iconBorderRadius']) && is_numeric($props['iconBorderRadius'])) {
            $iconContainerStyle .= 'border-radius:' . (int)$props['iconBorderRadius'] . 'px;';
        }
        if (isset($props['iconBorderRadiusTop']) && is_numeric($props['iconBorderRadiusTop'])) {
            $iconContainerStyle .= 'border-top-left-radius:' . (int)$props['iconBorderRadiusTop'] . 'px;';
        }
        if (isset($props['iconBorderRadiusRight']) && is_numeric($props['iconBorderRadiusRight'])) {
            $iconContainerStyle .= 'border-top-right-radius:' . (int)$props['iconBorderRadiusRight'] . 'px;';
        }
        if (isset($props['iconBorderRadiusBottom']) && is_numeric($props['iconBorderRadiusBottom'])) {
            $iconContainerStyle .= 'border-bottom-right-radius:' . (int)$props['iconBorderRadiusBottom'] . 'px;';
        }
        if (isset($props['iconBorderRadiusLeft']) && is_numeric($props['iconBorderRadiusLeft'])) {
            $iconContainerStyle .= 'border-bottom-left-radius:' . (int)$props['iconBorderRadiusLeft'] . 'px;';
        }
        
        // Icon border
        $hasBorder = false;
        if (isset($props['iconBorderWidthTop']) && is_numeric($props['iconBorderWidthTop'])) {
            $iconContainerStyle .= 'border-top-width:' . (int)$props['iconBorderWidthTop'] . 'px;';
            $hasBorder = true;
        }
        if (isset($props['iconBorderWidthRight']) && is_numeric($props['iconBorderWidthRight'])) {
            $iconContainerStyle .= 'border-right-width:' . (int)$props['iconBorderWidthRight'] . 'px;';
            $hasBorder = true;
        }
        if (isset($props['iconBorderWidthBottom']) && is_numeric($props['iconBorderWidthBottom'])) {
            $iconContainerStyle .= 'border-bottom-width:' . (int)$props['iconBorderWidthBottom'] . 'px;';
            $hasBorder = true;
        }
        if (isset($props['iconBorderWidthLeft']) && is_numeric($props['iconBorderWidthLeft'])) {
            $iconContainerStyle .= 'border-left-width:' . (int)$props['iconBorderWidthLeft'] . 'px;';
            $hasBorder = true;
        }
        
        if ($hasBorder) {
            $iconContainerStyle .= 'border-style:solid;';
            if (isset($props['iconBorderColor']) && is_string($props['iconBorderColor']) && $props['iconBorderColor'] !== '') {
                $iconContainerStyle .= 'border-color:' . e($props['iconBorderColor']) . ';';
            } else {
                $iconContainerStyle .= 'border-color:#e5e7eb;';
            }
        }
        
        // Shadow
        if (isset($props['shadowColor']) && is_string($props['shadowColor']) && $props['shadowColor'] !== '') {
            $shadowX = isset($props['shadowX']) && is_numeric($props['shadowX']) ? (int)$props['shadowX'] : 0;
            $shadowY = isset($props['shadowY']) && is_numeric($props['shadowY']) ? (int)$props['shadowY'] : 0;
            $shadowBlur = isset($props['shadowBlur']) && is_numeric($props['shadowBlur']) ? (int)$props['shadowBlur'] : 0;
            $iconContainerStyle .= 'filter:drop-shadow(' . $shadowX . 'px ' . $shadowY . 'px ' . $shadowBlur . 'px ' . e($props['shadowColor']) . ');';
        }
        
        // Icon color applied to container so it affects inline SVGs and placeholder
        if (isset($props['iconColor']) && is_string($props['iconColor']) && $props['iconColor'] !== '') {
            $iconContainerStyle .= 'color:' . e($props['iconColor']) . ';';
        }

        $iconSource = is_string($props['iconSource'] ?? null) ? trim((string) $props['iconSource']) : '';
        $iconSource = in_array($iconSource, ['svg', 'url'], true) ? $iconSource : '';
        $useSvg = $iconSvg !== '' && ($iconSource === 'svg' || $iconUrl === '');

        if ($useSvg) {
            $svgSizeStyle = 'display:inline-block;width:' . $iconSize . 'px;height:' . $iconSize . 'px;';
            if (preg_match('/<svg\b[^>]*\bstyle\s*=/i', $iconSvg)) {
                $iconSvgSized = preg_replace('/(<svg\b[^>]*\bstyle\s*=\s*)(["\'])([\s\S]*?)\2/i', '$1$2$3;' . $svgSizeStyle . '$2', $iconSvg, 1) ?? $iconSvg;
            } else {
                $iconSvgSized = preg_replace('/<svg\b/i', '<svg style="' . $svgSizeStyle . '"', $iconSvg, 1) ?? $iconSvg;
            }
            $iconHtml = '<div style="' . $iconContainerStyle . '">' . $iconSvgSized . '</div>';
        } elseif ($iconUrl !== '') {
            $iconStyle = 'width:' . $iconSize . 'px;height:' . $iconSize . 'px;object-fit:contain;display:block;';
            $iconHtml = '<div style="' . $iconContainerStyle . '"><img src="' . e($iconUrl) . '" alt="" style="' . $iconStyle . '" /></div>';
        } else {
            // Placeholder
            $fallbackColor = (isset($props['iconColor']) && is_string($props['iconColor']) && $props['iconColor'] !== '') ? e($props['iconColor']) : '#9ca3af';
            $iconHtml = '<div style="' . $iconContainerStyle . 'width:' . $iconSize . 'px;height:' . $iconSize . 'px;background-color:#e5e7eb;border-radius:8px;display:flex;align-items:center;justify-content:center;color:' . $fallbackColor . ';font-size:12px;">Icon</div>';
        }
        
        // Heading style
        $headingStyle = 'font-weight:600;font-size:18px;margin:0;';
        if (isset($props['headingColor']) && is_string($props['headingColor']) && $props['headingColor'] !== '') {
            $headingStyle .= 'color:' . e($props['headingColor']) . ';';
        }
        if (isset($props['headingFontFamily']) && is_string($props['headingFontFamily']) && $props['headingFontFamily'] !== '') {
            $headingStyle .= 'font-family:' . e($props['headingFontFamily']) . ';';
        }
        if (isset($props['headingFontSize']) && is_numeric($props['headingFontSize'])) {
            $headingStyle .= 'font-size:' . (int)$props['headingFontSize'] . 'px;';
        }
        if (isset($props['headingFontWeight']) && is_string($props['headingFontWeight']) && $props['headingFontWeight'] !== '') {
            $headingStyle .= 'font-weight:' . e($props['headingFontWeight']) . ';';
        }
        if (isset($props['headingLineHeight']) && is_numeric($props['headingLineHeight'])) {
            $headingStyle .= 'line-height:' . (float)$props['headingLineHeight'] . ';';
        }
        if (isset($props['headingLetterSpacing']) && is_numeric($props['headingLetterSpacing'])) {
            $headingStyle .= 'letter-spacing:' . (int)$props['headingLetterSpacing'] . 'px;';
        }
        if (isset($props['headingWordSpacing']) && is_numeric($props['headingWordSpacing'])) {
            $headingStyle .= 'word-spacing:' . (int)$props['headingWordSpacing'] . 'px;';
        }
        if (isset($props['headingMarginTop']) && is_numeric($props['headingMarginTop'])) {
            $headingStyle .= 'margin-top:' . (int)$props['headingMarginTop'] . 'px;';
        }
        if (isset($props['headingMarginRight']) && is_numeric($props['headingMarginRight'])) {
            $headingStyle .= 'margin-right:' . (int)$props['headingMarginRight'] . 'px;';
        }
        if (isset($props['headingMarginBottom']) && is_numeric($props['headingMarginBottom'])) {
            $headingStyle .= 'margin-bottom:' . (int)$props['headingMarginBottom'] . 'px;';
        }
        if (isset($props['headingMarginLeft']) && is_numeric($props['headingMarginLeft'])) {
            $headingStyle .= 'margin-left:' . (int)$props['headingMarginLeft'] . 'px;';
        }

        // Subtext style
        $subtextStyle = 'font-size:14px;color:#6b7280;margin:0;';
        if (isset($props['subtextColor']) && is_string($props['subtextColor']) && $props['subtextColor'] !== '') {
            $subtextStyle .= 'color:' . e($props['subtextColor']) . ';';
        }
        if (isset($props['subtextFontFamily']) && is_string($props['subtextFontFamily']) && $props['subtextFontFamily'] !== '') {
            $subtextStyle .= 'font-family:' . e($props['subtextFontFamily']) . ';';
        }
        if (isset($props['subtextFontSize']) && is_numeric($props['subtextFontSize'])) {
            $subtextStyle .= 'font-size:' . (int)$props['subtextFontSize'] . 'px;';
        }
        if (isset($props['subtextFontWeight']) && is_string($props['subtextFontWeight']) && $props['subtextFontWeight'] !== '') {
            $subtextStyle .= 'font-weight:' . e($props['subtextFontWeight']) . ';';
        }
        if (isset($props['subtextLineHeight']) && is_numeric($props['subtextLineHeight'])) {
            $subtextStyle .= 'line-height:' . (float)$props['subtextLineHeight'] . ';';
        }
        if (isset($props['subtextLetterSpacing']) && is_numeric($props['subtextLetterSpacing'])) {
            $subtextStyle .= 'letter-spacing:' . (int)$props['subtextLetterSpacing'] . 'px;';
        }
        if (isset($props['subtextWordSpacing']) && is_numeric($props['subtextWordSpacing'])) {
            $subtextStyle .= 'word-spacing:' . (int)$props['subtextWordSpacing'] . 'px;';
        }
        if (isset($props['subtextMarginTop']) && is_numeric($props['subtextMarginTop'])) {
            $subtextStyle .= 'margin-top:' . (int)$props['subtextMarginTop'] . 'px;';
        }
        if (isset($props['subtextMarginRight']) && is_numeric($props['subtextMarginRight'])) {
            $subtextStyle .= 'margin-right:' . (int)$props['subtextMarginRight'] . 'px;';
        }
        if (isset($props['subtextMarginBottom']) && is_numeric($props['subtextMarginBottom'])) {
            $subtextStyle .= 'margin-bottom:' . (int)$props['subtextMarginBottom'] . 'px;';
        }
        if (isset($props['subtextMarginLeft']) && is_numeric($props['subtextMarginLeft'])) {
            $subtextStyle .= 'margin-left:' . (int)$props['subtextMarginLeft'] . 'px;';
        }

        $textContent = '<div>'
            . '<div style="' . $headingStyle . '">' . $this->sanitizeRichText($heading) . '</div>'
            . ($hasSubtext ? '<div style="' . $subtextStyle . '">' . $this->sanitizeRichText($subtext) . '</div>' : '')
            . '</div>';
        
        $hoverScope = 'mp-icon-hover-' . bin2hex(random_bytes(3));
        $hoverCss = $this->elementHoverCss($props, $hoverScope);
        $hoverStyleTag = $hoverCss !== '' ? '<style>' . $hoverCss . '</style>' : '';

        return $hoverStyleTag . '<div class="mp-icon ' . e($hoverScope) . '" style="' . $containerStyle . '">'
            . '<div style="' . $flexStyle . '">'
            . $iconHtml
            . $textContent
            . '</div>'
            . '</div>';
    }

    private function renderVideo(array $props): string
    {
        $videoType = is_string($props['videoType'] ?? null) ? trim((string) $props['videoType']) : 'youtube';
        $videoType = in_array($videoType, ['youtube', 'vimeo', 'mp4'], true) ? $videoType : 'youtube';
        
        $youtubeId = is_string($props['youtubeId'] ?? null) ? trim((string) $props['youtubeId']) : '';
        $vimeoId = is_string($props['vimeoId'] ?? null) ? trim((string) $props['vimeoId']) : '';
        $mp4Url = is_string($props['mp4Url'] ?? null) ? trim((string) $props['mp4Url']) : '';
        
        $aspectRatio = is_string($props['aspectRatio'] ?? null) ? trim((string) $props['aspectRatio']) : '16/9';
        $aspectRatio = in_array($aspectRatio, ['16/9', '4/3', '1/1', '21/9'], true) ? $aspectRatio : '16/9';
        
        $autoplay = isset($props['autoplay']) && $props['autoplay'] === true;
        $loop = isset($props['loop']) && $props['loop'] === true;
        $muted = isset($props['muted']) && $props['muted'] === true;
        $controls = !isset($props['controls']) || $props['controls'] === true;

        $paddingBottom = match ($aspectRatio) {
            '16/9' => '56.25%',
            '4/3' => '75%',
            '1/1' => '100%',
            '21/9' => '42.857%',
            default => '56.25%',
        };

        $content = '';
        
        if ($videoType === 'youtube' && $youtubeId !== '') {
            $params = [];
            if ($autoplay) $params[] = 'autoplay=1';
            if ($loop) $params[] = 'loop=1&playlist=' . e($youtubeId);
            if ($muted) $params[] = 'mute=1';
            if (!$controls) $params[] = 'controls=0';
            
            $queryString = !empty($params) ? '?' . implode('&', $params) : '';
            $src = 'https://www.youtube.com/embed/' . e($youtubeId) . $queryString;
            
            $content = '<iframe src="' . $src . '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        } elseif ($videoType === 'vimeo' && $vimeoId !== '') {
            $params = [];
            if ($autoplay) $params[] = 'autoplay=1';
            if ($loop) $params[] = 'loop=1';
            if ($muted) $params[] = 'muted=1';
            
            $queryString = !empty($params) ? '?' . implode('&', $params) : '';
            $src = 'https://player.vimeo.com/video/' . e($vimeoId) . $queryString;
            
            $content = '<iframe src="' . $src . '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
        } elseif ($videoType === 'mp4' && $mp4Url !== '') {
            $videoAttrs = '';
            if ($autoplay) $videoAttrs .= ' autoplay';
            if ($loop) $videoAttrs .= ' loop';
            if ($muted) $videoAttrs .= ' muted';
            if ($controls) $videoAttrs .= ' controls';
            
            $content = '<video src="' . e($mp4Url) . '"' . $videoAttrs . ' style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;"></video>';
        }

        if ($content === '') {
            return '';
        }

        return '<div class="mp-video" style="position:relative;width:100%;padding-bottom:' . $paddingBottom . ';overflow:hidden;background-color:#000;">'
            . $content
            . '</div>';
    }

    private function renderMarquee(array $props): string
    {
        $images = $props['images'] ?? [];
        $items = $props['items'] ?? [];
        $hasImages = is_array($images) && count($images) > 0;
        $hasItems = is_array($items) && count($items) > 0;
        if (!$hasImages && !$hasItems) {
            return '';
        }

        $speed = $this->intOrNull($props['speed'] ?? null) ?? 30;
        if ($speed < 5) {
            $speed = 5;
        }
        if ($speed > 200) {
            $speed = 200;
        }

        $gap = $this->intOrNull($props['gap'] ?? null) ?? 24;
        if ($gap < 0) {
            $gap = 0;
        }

        $direction = is_string($props['direction'] ?? null) ? trim((string) $props['direction']) : 'left';
        $direction = in_array($direction, ['left', 'right'], true) ? $direction : 'left';

        $fadeEnabled = is_bool($props['fadeEnabled'] ?? null) ? (bool) $props['fadeEnabled'] : true;
        $fadeMask = is_bool($props['fadeMask'] ?? null) ? (bool) $props['fadeMask'] : true;
        $fadeColor = is_string($props['fadeColor'] ?? null) ? trim((string) $props['fadeColor']) : '';
        $fadeWidth = $this->intOrNull($props['fadeWidth'] ?? null);
        if ($fadeWidth !== null) {
            if ($fadeWidth < 0) {
                $fadeWidth = 0;
            }
            if ($fadeWidth > 200) {
                $fadeWidth = 200;
            }
        }

        $imageWidth = $this->intOrNull($props['imageWidth'] ?? null);
        if ($imageWidth !== null) {
            if ($imageWidth < 0) {
                $imageWidth = 0;
            }
            if ($imageWidth > 600) {
                $imageWidth = 600;
            }
            if ($imageWidth === 0) {
                $imageWidth = null;
            }
        }

        $imageHeight = $this->intOrNull($props['imageHeight'] ?? null);
        if ($imageHeight === null) {
            $imageHeight = ($imageWidth !== null) ? null : 40;
        }
        if ($imageHeight !== null) {
            if ($imageHeight < 10) {
                $imageHeight = 10;
            }
            if ($imageHeight > 200) {
                $imageHeight = 200;
            }
        }

        $inner = '';
        $separatorIcon = is_string($props['separatorIcon'] ?? null) ? trim((string) $props['separatorIcon']) : '';
        $halfGap = max(0, (int) round($gap / 2));

        // Text styling (typography + color) for text mode, mirroring the builder canvas.
        $textStyle = 'white-space:nowrap;font-weight:600;';
        $mqFontFamily = $this->sanitizeFontFamily($props['fontFamily'] ?? null);
        if ($mqFontFamily !== null) {
            $textStyle .= 'font-family:' . e($mqFontFamily) . ';';
        }
        $mqFontSize = $this->cssLength($props['fontSize'] ?? null, '');
        $textStyle .= 'font-size:' . e($mqFontSize !== '' ? $mqFontSize : '14px') . ';';
        $mqFontWeight = $props['fontWeight'] ?? null;
        if ((is_string($mqFontWeight) && trim($mqFontWeight) !== '') || (is_numeric($mqFontWeight) && !is_bool($mqFontWeight))) {
            $textStyle .= 'font-weight:' . e(trim((string) $mqFontWeight)) . ';';
        }
        $mqLineHeight = $this->cssLineHeight($props['lineHeight'] ?? null);
        if ($mqLineHeight !== '') {
            $textStyle .= 'line-height:' . e($mqLineHeight) . ';';
        }
        $mqLetterSpacing = $this->cssLength($props['letterSpacing'] ?? null, '');
        if ($mqLetterSpacing !== '') {
            $textStyle .= 'letter-spacing:' . e($mqLetterSpacing) . ';';
        }
        $mqWordSpacing = $this->cssLength($props['wordSpacing'] ?? null, '');
        if ($mqWordSpacing !== '') {
            $textStyle .= 'word-spacing:' . e($mqWordSpacing) . ';';
        }
        $mqTextColor = $this->sanitizeColor($props['textColor'] ?? null);
        if ($mqTextColor !== null) {
            $textStyle .= 'color:' . e($mqTextColor) . ';';
        }
        $sepStyle = 'display:inline-flex;align-items:center;justify-content:center;padding-left:' . e((string) $halfGap) . 'px;padding-right:' . e((string) $halfGap) . 'px;flex:0 0 auto;';
        if ($mqTextColor !== null) {
            $sepStyle .= 'color:' . e($mqTextColor) . ';';
        }

        if ($hasImages) {
            $renderItem = function ($it) use ($imageWidth, $imageHeight) {
                if (is_array($it)) {
                    $type = is_string($it['type'] ?? null) ? trim((string) $it['type']) : '';
                    $url = is_string($it['url'] ?? null) ? trim((string) $it['url']) : '';
                    if ($url !== '' && ($type === '' || $type === 'image')) {
                        $style = 'height:' . ($imageHeight !== null ? e((string) $imageHeight) . 'px' : 'auto') . ';';
                        $style .= 'width:' . ($imageWidth !== null ? e((string) $imageWidth) . 'px' : 'auto') . ';';
                        $style .= 'display:block;';
                        return '<img src="' . e($url) . '" alt="" style="' . $style . '" />';
                    }
                }
                return '';
            };

            foreach ($images as $it) {
                $piece = $renderItem($it);
                if ($piece === '') {
                    continue;
                }
                $inner .= '<div class="mp-marquee__item" style="flex:0 0 auto;padding-right:' . e((string) $gap) . 'px">' . $piece . '</div>';
            }
        } else {
            foreach ($items as $it) {
                $text = is_string($it) ? $it : (is_array($it) && is_string($it['text'] ?? null) ? (string) $it['text'] : '');
                $text = trim($text);
                if ($text === '') {
                    continue;
                }
                $inner .= '<div class="mp-marquee__item" style="flex:0 0 auto;padding-left:' . e((string) $halfGap) . 'px;padding-right:' . e((string) $halfGap) . 'px;display:flex;align-items:center;">'
                    . '<span class="mp-marquee__text" style="' . $textStyle . '">' . e($text) . '</span>'
                    . '</div>';
                if ($separatorIcon !== '') {
                    $inner .= '<span class="mp-marquee__separator-icon" style="' . $sepStyle . '">' . $separatorIcon . '</span>';
                }
            }
        }

        if ($inner === '') {
            return '';
        }

        $duration = (string) $speed . 's';
        $animDirection = $direction === 'right' ? 'reverse' : 'normal';

        $classes = 'mp-marquee';
        if ($fadeEnabled && $fadeMask) {
            $classes .= ' mp-marquee--mask';
        }
        if ($fadeEnabled && $fadeColor !== '') {
            $classes .= ' mp-marquee--overlay';
        }

        $style = 'overflow:hidden;width:100%;white-space:nowrap;';
        $style .= $this->buildSectionStyle($props);
        if ($fadeEnabled && $fadeWidth !== null) {
            $style .= '--mp-marquee-fade-width:' . e((string) $fadeWidth) . 'px;';
        }
        if ($fadeEnabled && $fadeColor !== '') {
            $style .= '--mp-marquee-fade-color:' . e($fadeColor) . ';';
        }

        return '<div class="' . e($classes) . '" style="' . e($style) . '">'
            . '<div class="mp-marquee__track" style="display:flex;align-items:center;width:max-content;flex-wrap:nowrap;animation-name:mp-marquee;animation-duration:' . e($duration) . ';animation-timing-function:linear;animation-iteration-count:infinite;animation-direction:' . e($animDirection) . ';animation-play-state:running;will-change:transform;">'
            . $inner
            . $inner
            . '</div>'
            . '</div>';
    }

    private function carouselConfig(array $props): array
    {
        $raw = [
            'perPage' => $props['carouselPerPage'] ?? null,
            'perPageTablet' => $props['carouselPerPageTablet'] ?? null,
            'perPageMobile' => $props['carouselPerPageMobile'] ?? null,
            'gap' => $props['carouselGap'] ?? null,
            'loop' => $props['carouselLoop'] ?? null,
            'autoplay' => $props['carouselAutoplay'] ?? null,
            'interval' => $props['carouselInterval'] ?? null,
            'speed' => $props['carouselSpeed'] ?? null,
            'effect' => $props['carouselEffect'] ?? null,
            'direction' => $props['carouselDirection'] ?? null,
            'pauseOnHover' => $props['carouselPauseOnHover'] ?? null,
            'arrowsShow' => $props['carouselArrowsShow'] ?? null,
            'arrowsPosition' => $props['carouselArrowsPosition'] ?? null,
            'dotsShow' => $props['carouselDotsShow'] ?? null,
            'dotsPosition' => $props['carouselDotsPosition'] ?? null,
        ];
        $int = static function ($v, int $default, int $min, int $max): int {
            if (!is_numeric($v)) {
                return $default;
            }
            return max($min, min($max, (int) round((float) $v)));
        };
        $bool = static function ($v, bool $default): bool {
            if ($v === null) {
                return $default;
            }
            return !($v === false || $v === 'false' || $v === 0 || $v === '0');
        };

        $arrowPositions = ['sides', 'top-left', 'top-right', 'bottom-center'];
        $dotPositions = ['bottom-center', 'bottom-left', 'bottom-right', 'top-right'];
        $arrowsPosition = is_string($raw['arrowsPosition'] ?? null) && in_array($raw['arrowsPosition'], $arrowPositions, true)
            ? $raw['arrowsPosition']
            : 'sides';
        $dotsPosition = is_string($raw['dotsPosition'] ?? null) && in_array($raw['dotsPosition'], $dotPositions, true)
            ? $raw['dotsPosition']
            : 'bottom-center';

        return [
            'perPage' => $int($raw['perPage'] ?? null, 1, 1, 6),
            'perPageTablet' => $int($raw['perPageTablet'] ?? null, (int) ($int($raw['perPage'] ?? null, 1, 1, 6)), 1, 6),
            'perPageMobile' => $int($raw['perPageMobile'] ?? null, (int) ($int($raw['perPage'] ?? null, 1, 1, 6)), 1, 6),
            'gap' => $int($raw['gap'] ?? null, 16, 0, 120),
            'loop' => $bool($raw['loop'] ?? null, true),
            'autoplay' => $bool($raw['autoplay'] ?? null, false),
            'interval' => $int($raw['interval'] ?? null, 5, 1, 60),
            'speed' => $int($raw['speed'] ?? null, 500, 100, 4000),
            'effect' => (($raw['effect'] ?? null) === 'fade') ? 'fade' : 'slide',
            'direction' => (($raw['direction'] ?? null) === 'vertical') ? 'vertical' : 'horizontal',
            'pauseOnHover' => $bool($raw['pauseOnHover'] ?? null, true),
            'arrowsShow' => $bool($raw['arrowsShow'] ?? null, true),
            'arrowsPosition' => $arrowsPosition,
            'dotsShow' => $bool($raw['dotsShow'] ?? null, true),
            'dotsPosition' => $dotsPosition,
        ];
    }

    private function carouselBorderCss(array $p, string $prefix): array
    {
        $out = [];
        $style = is_string($p[$prefix . 'BorderStyle'] ?? null) ? trim((string) $p[$prefix . 'BorderStyle']) : '';
        if ($style !== '' && $style !== 'default') {
            $out[] = 'border-style:' . $style;
        }
        $color = $this->sanitizeColor($p[$prefix . 'BorderColor'] ?? null);
        if ($color !== null) {
            $out[] = 'border-color:' . $color;
        }
        foreach (['Top', 'Right', 'Bottom', 'Left'] as $side) {
            $w = $p[$prefix . 'BorderWidth' . $side] ?? null;
            if (is_numeric($w)) {
                $out[] = 'border-' . strtolower($side) . '-width:' . (int) round((float) $w) . 'px';
            }
        }
        return $out;
    }

    private function carouselBackgroundCss(array $p, string $prefix): array
    {
        $out = [];
        if (($p[$prefix . 'BackgroundType'] ?? null) === 'gradient') {
            $from = $this->sanitizeColor($p[$prefix . 'GradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($p[$prefix . 'GradientTo'] ?? null) ?? '#3b82f6';
            $out[] = 'background:linear-gradient(135deg,' . $from . ',' . $to . ')';
        } else {
            $color = $this->sanitizeColor($p[$prefix . 'BackgroundColor'] ?? null);
            if ($color !== null) {
                $out[] = 'background:' . $color;
            }
        }
        return $out;
    }

    private function carouselShadowCss(array $p, string $prefix): array
    {
        $color = $this->sanitizeColor($p[$prefix . 'ShadowColor'] ?? null);
        if ($color === null) {
            return [];
        }
        $x = is_numeric($p[$prefix . 'ShadowX'] ?? null) ? (int) $p[$prefix . 'ShadowX'] : 0;
        $y = is_numeric($p[$prefix . 'ShadowY'] ?? null) ? (int) $p[$prefix . 'ShadowY'] : 0;
        $blur = is_numeric($p[$prefix . 'ShadowBlur'] ?? null) ? (int) $p[$prefix . 'ShadowBlur'] : 0;
        $spread = is_numeric($p[$prefix . 'ShadowSpread'] ?? null) ? (int) $p[$prefix . 'ShadowSpread'] : 0;
        return ['box-shadow:' . $x . 'px ' . $y . 'px ' . $blur . 'px ' . $spread . 'px ' . $color];
    }

    private function carouselSizeCss(array $p, string $prefix): array
    {
        $out = [];
        if (is_numeric($p[$prefix . 'Width'] ?? null)) {
            $out[] = 'width:' . (int) round((float) $p[$prefix . 'Width']) . 'px';
        }
        if (is_numeric($p[$prefix . 'Height'] ?? null)) {
            $out[] = 'height:' . (int) round((float) $p[$prefix . 'Height']) . 'px';
        }
        return $out;
    }

    private function carouselSpacingCss(array $p, string $prefix): array
    {
        $out = [];
        foreach (['Top', 'Right', 'Bottom', 'Left'] as $side) {
            $lower = strtolower($side);
            if (is_numeric($p[$prefix . 'Padding' . $side] ?? null)) {
                $out[] = 'padding-' . $lower . ':' . (int) round((float) $p[$prefix . 'Padding' . $side]) . 'px';
            }
            if (is_numeric($p[$prefix . 'Margin' . $side] ?? null)) {
                $out[] = 'margin-' . $lower . ':' . (int) round((float) $p[$prefix . 'Margin' . $side]) . 'px';
            }
        }
        return $out;
    }

    private function carouselPaddingCss(array $p, string $prefix): array
    {
        $out = [];
        foreach (['Top', 'Right', 'Bottom', 'Left'] as $side) {
            if (is_numeric($p[$prefix . 'Padding' . $side] ?? null)) {
                $out[] = 'padding-' . strtolower($side) . ':' . (int) round((float) $p[$prefix . 'Padding' . $side]) . 'px';
            }
        }
        return $out;
    }

    private function carouselMarginCss(array $p, string $prefix): array
    {
        $out = [];
        foreach (['Top', 'Right', 'Bottom', 'Left'] as $side) {
            if (is_numeric($p[$prefix . 'Margin' . $side] ?? null)) {
                $out[] = 'margin-' . strtolower($side) . ':' . (int) round((float) $p[$prefix . 'Margin' . $side]) . 'px';
            }
        }
        return $out;
    }

    private function carouselRadiusCss(array $p, string $prefix): array
    {
        $out = [];
        $map = [
            'Top' => 'border-top-left-radius',
            'Right' => 'border-top-right-radius',
            'Bottom' => 'border-bottom-right-radius',
            'Left' => 'border-bottom-left-radius',
        ];
        foreach ($map as $side => $prop) {
            $v = $p[$prefix . 'BorderRadius' . $side] ?? null;
            if (is_numeric($v)) {
                $out[] = $prop . ':' . (int) round((float) $v) . 'px';
            }
        }
        if (empty($out) && is_numeric($p[$prefix . 'BorderRadius'] ?? null)) {
            $out[] = 'border-radius:' . (int) round((float) $p[$prefix . 'BorderRadius']) . 'px';
        }
        return $out;
    }

    private function carouselCss(array $p, string $scope, string $dotsPosition = 'bottom-center'): string
    {
        $sel = '.' . $scope;
        $css = '';

        $arrowNormal = array_merge(
            $this->carouselBorderCss($p, 'arrow'),
            $this->carouselRadiusCss($p, 'arrow'),
            $this->carouselBackgroundCss($p, 'arrow'),
            $this->carouselShadowCss($p, 'arrow'),
            $this->carouselSizeCss($p, 'arrow'),
            $this->carouselPaddingCss($p, 'arrow')
        );
        $iconColor = $this->sanitizeColor($p['arrowIconColor'] ?? null);
        if ($iconColor !== null) {
            $arrowNormal[] = 'color:' . $iconColor;
        }
        if (!empty($arrowNormal)) {
            $css .= $sel . ' .mp-carousel__arrow{' . implode(';', $arrowNormal) . ';}';
        }

        $arrowHover = array_merge(
            $this->carouselBorderCss($p, 'arrowHover'),
            $this->carouselRadiusCss($p, 'arrowHover'),
            $this->carouselBackgroundCss($p, 'arrowHover'),
            $this->carouselShadowCss($p, 'arrowHover'),
            $this->carouselSizeCss($p, 'arrowHover'),
            $this->carouselSpacingCss($p, 'arrowHover')
        );
        $hoverIconColor = $this->sanitizeColor($p['arrowHoverIconColor'] ?? null);
        if ($hoverIconColor !== null) {
            $arrowHover[] = 'color:' . $hoverIconColor;
        }
        if (!empty($arrowHover)) {
            $css .= $sel . ' .mp-carousel__arrow:hover{' . implode(';', $arrowHover) . ';}';
        }

        // Arrow margin applies to the whole arrow group (the pair); gap spaces
        // the two arrows.
        $arrowMargin = $this->carouselMarginCss($p, 'arrow');
        if (!empty($arrowMargin)) {
            $css .= $sel . ' .mp-carousel__arrows{' . implode(';', $arrowMargin) . ';}';
        }
        if (is_numeric($p['arrowGap'] ?? null)) {
            $css .= $sel . ' .mp-carousel__arrows{gap:' . (int) round((float) $p['arrowGap']) . 'px;}';
        }

        // dotMargin* offsets the whole dots cluster from its anchor (emitted below
        // as container inset); per-dot margin would be inert on the anchored row.
        $dotNormal = array_merge(
            $this->carouselBackgroundCss($p, 'dot'),
            $this->carouselShadowCss($p, 'dot'),
            $this->carouselSizeCss($p, 'dot')
        );
        if (!empty($dotNormal)) {
            $css .= $sel . ' .mp-carousel__dot{' . implode(';', $dotNormal) . ';}';
        }
        if (is_numeric($p['dotGap'] ?? null)) {
            $css .= $sel . ' .mp-carousel__dots{gap:' . (int) round((float) $p['dotGap']) . 'px;}';
        }

        // Cluster offset from the anchor (mirrors the canvas dotsLayout math).
        $dn = (static function ($v) {
            return is_numeric($v) ? (int) round((float) $v) : 0;
        });
        $dDx = $dn($p['dotMarginLeft'] ?? null) - $dn($p['dotMarginRight'] ?? null);
        $dDy = $dn($p['dotMarginTop'] ?? null) - $dn($p['dotMarginBottom'] ?? null);
        if ($dDx !== 0 || $dDy !== 0) {
            if ($dotsPosition === 'top-right') {
                $css .= $sel . ' .mp-carousel__dots{top:' . (8 + $dDy) . 'px;right:' . (8 - $dDx) . 'px;}';
            } elseif ($dotsPosition === 'bottom-left') {
                $css .= $sel . ' .mp-carousel__dots{bottom:' . (8 - $dDy) . 'px;left:' . (8 + $dDx) . 'px;}';
            } elseif ($dotsPosition === 'bottom-right') {
                $css .= $sel . ' .mp-carousel__dots{bottom:' . (8 - $dDy) . 'px;right:' . (8 - $dDx) . 'px;}';
            } else {
                $css .= $sel . ' .mp-carousel__dots{bottom:' . (8 - $dDy) . 'px;' . ($dDx !== 0 ? 'margin-left:' . $dDx . 'px;' : '') . '}';
            }
        }

        $dotActive = array_merge(
            $this->carouselBackgroundCss($p, 'dotActive'),
            $this->carouselShadowCss($p, 'dotActive'),
            $this->carouselSizeCss($p, 'dotActive'),
            $this->carouselSpacingCss($p, 'dotActive')
        );
        if (!empty($dotActive)) {
            $css .= $sel . ' .mp-carousel__dot.is-active{' . implode(';', $dotActive) . ';}';
        }
        if (is_numeric($p['dotActiveGap'] ?? null)) {
            $g = (int) round((float) $p['dotActiveGap']);
            $css .= $sel . ' .mp-carousel__dot.is-active{margin-left:' . $g . 'px;margin-right:' . $g . 'px;}';
        }

        return $css;
    }

    private function carouselArrowIcon(array $props, bool $prev): string
    {
        $url = is_string($props['arrowIconUrl'] ?? null) ? trim((string) $props['arrowIconUrl']) : '';
        $svg = is_string($props['arrowIconSvg'] ?? null) ? trim((string) $props['arrowIconSvg']) : '';
        $source = ($props['arrowIconSource'] ?? null) === 'svg' ? 'svg' : 'url';
        $useSvg = $svg !== '' && ($source === 'svg' || $url === '');
        $flip = $prev ? 'transform:scaleX(-1);' : '';

        if ($useSvg) {
            $sizeStyle = 'display:inline-block;width:18px;height:18px;';
            if (preg_match('/<svg\b[^>]*\bstyle\s*=/i', $svg)) {
                $sized = preg_replace('/(<svg\b[^>]*\bstyle\s*=\s*)(["\'])([\s\S]*?)\2/i', '$1$2$3;' . $sizeStyle . '$2', $svg, 1) ?? $svg;
            } else {
                $sized = preg_replace('/<svg\b/i', '<svg style="' . $sizeStyle . '"', $svg, 1) ?? $svg;
            }
            return '<span style="' . $flip . '">' . $sized . '</span>';
        }
        if ($url !== '') {
            return '<img src="' . e($url) . '" alt="" style="width:18px;height:18px;object-fit:contain;display:block;' . $flip . '" />';
        }
        return $prev
            ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';
    }

    private function renderCarousel(string $pagesHtml, int $pageCount, array $config, array $props = []): string
    {
        $json = json_encode([
            'perPage' => $config['perPage'],
            'perPageTablet' => $config['perPageTablet'] ?? $config['perPage'],
            'perPageMobile' => $config['perPageMobile'] ?? $config['perPage'],
            'gap' => $config['gap'],
            'loop' => $config['loop'],
            'autoplay' => $config['autoplay'],
            'interval' => $config['interval'],
            'speed' => $config['speed'],
            'effect' => $config['effect'],
            'direction' => $config['direction'],
            'pauseOnHover' => $config['pauseOnHover'],
            'arrowsShow' => $config['arrowsShow'],
            'dotsShow' => $config['dotsShow'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            $json = '{}';
        }

        $scope = 'mpc-' . bin2hex(random_bytes(4));
        $css = $this->carouselCss($props, $scope, (string) ($config['dotsPosition'] ?? 'bottom-center'));

        $arrows = '';
        if ($config['arrowsShow']) {
            $prevIcon = $this->carouselArrowIcon($props, true);
            $nextIcon = $this->carouselArrowIcon($props, false);
            $prev = '<button type="button" class="mp-carousel__arrow" data-mp-carousel-prev data-prev="1" aria-label="Previous">' . $prevIcon . '</button>';
            $next = '<button type="button" class="mp-carousel__arrow" data-mp-carousel-next data-next="1" aria-label="Next">' . $nextIcon . '</button>';
            if ($config['arrowsPosition'] === 'sides') {
                $arrows = $prev . $next;
            } else {
                $arrows = '<div class="mp-carousel__arrows" data-pos="' . e($config['arrowsPosition']) . '">' . $prev . $next . '</div>';
            }
        }

        $dots = '';
        if ($config['dotsShow']) {
            $dotButtons = '';
            for ($i = 0; $i < max(1, $pageCount); $i++) {
                $dotButtons .= '<button type="button" class="mp-carousel__dot' . ($i === 0 ? ' is-active' : '') . '" data-mp-carousel-dot aria-label="Go to slide ' . ($i + 1) . '"></button>';
            }
            $dots = '<div class="mp-carousel__dots" data-pos="' . e($config['dotsPosition']) . '">' . $dotButtons . '</div>';
        }

        $styleTag = $css !== '' ? '<style>' . $css . '</style>' : '';

        return $styleTag . '<div class="mp-carousel ' . e($scope) . '" data-mp-carousel=\'' . e($json) . '\' data-direction="' . e($config['direction']) . '">'
            . '<div class="mp-carousel__viewport"><div class="mp-carousel__track">' . $pagesHtml . '</div></div>'
            . ($config['arrowsShow'] ? $arrows : '')
            . $dots
            . '</div>';
    }

    private function carouselStars(float $rating, string $color, int $size): string
    {
        $out = '<div style="display:flex;gap:2px;">';
        for ($i = 0; $i < 5; $i++) {
            $filled = $i < (int) round($rating);
            $out .= '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="' . ($filled ? e($color) : 'none') . '" stroke="' . e($color) . '" stroke-width="1.5" style="flex-shrink:0;"><path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.46L12 17.3l-5.8 3.06 1.11-6.46-4.7-4.58 6.49-.94z"/></svg>';
        }
        return $out . '</div>';
    }

    private function responsiveTypographyCss(array $p, string $scope): string
    {
        if ($scope === '') {
            return '';
        }

        $rule = function (string $suffix) use ($p): array {
            $out = [];
            $fs = $this->cssLength($p['fontSize' . $suffix] ?? null, '');
            if ($fs !== '') {
                $out[] = '--mp-typo-fontSize:' . $fs;
            }
            $lh = $this->cssLineHeight($p['lineHeight' . $suffix] ?? null, '');
            if ($lh !== '') {
                $out[] = '--mp-typo-lineHeight:' . $lh;
            }
            $align = $this->sanitizeAlign($p['align' . $suffix] ?? null);
            if ($align !== null && $align !== '') {
                $out[] = '--mp-typo-textAlign:' . $align;
            }
            return $out;
        };

        $css = '';
        $base = $rule('');
        if (!empty($base)) {
            $css .= '.' . $scope . '{' . implode(';', $base) . ';}';
        }
        $tablet = $rule('Tablet');
        if (!empty($tablet)) {
            $css .= '@media (max-width:1023px){.' . $scope . '{' . implode(';', $tablet) . ';}}';
        }
        $mobile = $rule('Mobile');
        if (!empty($mobile)) {
            $css .= '@media (max-width:639px){.' . $scope . '{' . implode(';', $mobile) . ';}}';
        }
        return $css;
    }

    /**
     * Build a unique scope class for responsive container width overrides.
     */
    private function responsiveWidthScope(string $seed = ''): string
    {
        $clean = $seed !== '' ? preg_replace('/[^a-zA-Z0-9_-]/', '', $seed) : '';
        if ($clean === '' || $clean === null) {
            $this->responsiveWidthSeq++;
            $clean = 'n' . $this->responsiveWidthSeq;
        }
        return 'mp-w-' . $clean;
    }

    /**
     * Emit tablet/mobile max-width overrides for a container width, mirroring
     * the builder's responsive innerMaxWidth/boxedWidth behavior. Inline styles
     * win over stylesheet rules, so overrides use !important.
     */
    private function responsiveContainerWidthCss(array $style, string $scope, string $mode): string
    {
        if ($scope === '') {
            return '';
        }

        $isFull = $mode === 'full';
        $pick = function (string $suffix) use ($style, $isFull): string {
            $key = ($isFull ? 'innerMaxWidth' : 'boxedWidth') . $suffix;
            $value = $this->cssLength($style[$key] ?? null, '');
            if ($value !== '' && !preg_match('/^[-+]?\d+(?:\.\d+)?(px|em|rem|%|vw|vh)$/i', $value) && $value !== 'auto') {
                return '';
            }
            return $value;
        };

        $css = '';
        $tablet = $pick('Tablet');
        if ($tablet !== '') {
            $css .= '@media (max-width:1023px){.' . $scope . '{max-width:' . $tablet . ' !important;}}';
        }
        $mobile = $pick('Mobile');
        if ($mobile !== '') {
            $css .= '@media (max-width:639px){.' . $scope . '{max-width:' . $mobile . ' !important;}}';
        }
        return $css;
    }

    /**
     * Emit tablet/mobile display overrides for an advanced-display scope.
     */
    private function responsiveDisplayCss(array $adv, string $scope): string
    {
        if ($scope === '') {
            return '';
        }

        $css = '';
        $tablet = $this->sanitizeDisplay($adv['displayTablet'] ?? null);
        if ($tablet !== null && $tablet !== '') {
            $css .= '@media (max-width:1023px){.' . $scope . '{display:' . $tablet . ' !important;}}';
        }
        $mobile = $this->sanitizeDisplay($adv['displayMobile'] ?? null);
        if ($mobile !== null && $mobile !== '') {
            $css .= '@media (max-width:639px){.' . $scope . '{display:' . $mobile . ' !important;}}';
        }

        return $css;
    }

    /**
     * Emit tablet/mobile height overrides for a section height scope.
     */
    private function responsiveHeightCss(array $style, string $scope): string
    {
        if ($scope === '') {
            return '';
        }

        $css = '';
        $tablet = $this->cssLength($style['heightTablet'] ?? null, '');
        if ($tablet !== '' && $tablet !== 'auto') {
            $css .= '@media (max-width:1023px){.' . $scope . '{height:' . $tablet . ' !important;}}';
        }
        $mobile = $this->cssLength($style['heightMobile'] ?? null, '');
        if ($mobile !== '' && $mobile !== 'auto') {
            $css .= '@media (max-width:639px){.' . $scope . '{height:' . $mobile . ' !important;}}';
        }

        return $css;
    }

    /**
     * Emit tablet/mobile overrides for a section/grid flex layout
     * (direction, justify-content, align-items).
     */
    private function responsiveSectionItemsCss(array $style, string $scope): string
    {
        if ($scope === '') {
            return '';
        }

        $direction = function ($v): string {
            $v = is_string($v) ? trim($v) : '';
            return in_array($v, ['row', 'column', 'row-reverse', 'column-reverse'], true) ? $v : '';
        };
        $justify = function ($v): string {
            $v = is_string($v) ? trim($v) : '';
            return in_array($v, ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], true) ? $v : '';
        };
        $align = function ($v): string {
            $v = is_string($v) ? trim($v) : '';
            return in_array($v, ['stretch', 'flex-start', 'center', 'flex-end', 'baseline'], true) ? $v : '';
        };

        $build = function (string $suffix) use ($style, $direction, $justify, $align): string {
            $out = '';
            $d = $direction($style['itemsDirection' . $suffix] ?? null);
            if ($d !== '') {
                $out .= 'flex-direction:' . $d . ' !important;';
            }
            $j = $justify($style['itemsJustify' . $suffix] ?? null);
            if ($j !== '') {
                $out .= 'justify-content:' . $j . ' !important;';
            }
            $a = $align($style['itemsAlign' . $suffix] ?? null);
            if ($a !== '') {
                $out .= 'align-items:' . $a . ' !important;';
            }
            return $out;
        };

        $css = '';
        $tablet = $build('Tablet');
        if ($tablet !== '') {
            $css .= '@media (max-width:1023px){.' . $scope . '{' . $tablet . '}}';
        }
        $mobile = $build('Mobile');
        if ($mobile !== '') {
            $css .= '@media (max-width:639px){.' . $scope . '{' . $mobile . '}}';
        }

        // The published stylesheet clamps columns to full width on small screens.
        // When a breakpoint explicitly uses a row direction, override that clamp
        // so the chosen columns stay side by side.
        $mobileDir = $direction($style['itemsDirectionMobile'] ?? null);
        if ($mobileDir === 'row' || $mobileDir === 'row-reverse') {
            $css .= '@media (max-width:767px){.mp-grid.' . $scope . '>.mp-col{flex:1 1 0 !important;}}';
        }
        $tabletDir = $direction($style['itemsDirectionTablet'] ?? null);
        if ($tabletDir === 'row' || $tabletDir === 'row-reverse') {
            $css .= '@media (min-width:768px) and (max-width:1023px){.mp-grid.' . $scope . '>.mp-col{flex:1 1 0 !important;}}';
        }

        return $css;
    }

    /**
     * Wrap flex children (grid columns or a column's widget wrappers) into N
     * items per row, with responsive per-breakpoint counts.
     *
     * $containerSel / $childSel are complete selector fragments; the scope class
     * is doubled in them for specificity over the published stylesheet.
     */
    private function responsiveColumnsPerRowCss(array $style, string $containerSel, string $childSel, float $gapX, int $count, float $gapY = 0): string
    {
        if ($count < 2) {
            return '';
        }

        $perRow = function ($v): ?int {
            return is_numeric($v) ? max(1, min(6, (int) round((float) $v))) : null;
        };
        $base = $perRow($style['columnsPerRow'] ?? null);
        $tablet = $perRow($style['columnsPerRowTablet'] ?? null);
        $mobile = $perRow($style['columnsPerRowMobile'] ?? null);

        if ($base === null && $tablet === null && $mobile === null) {
            return '';
        }

        $colRule = function (int $n) use ($gapX, $count): string {
            $n = min($n, max(1, $count));
            if ($n < 2) {
                return 'flex:0 0 100% !important;max-width:100% !important;';
            }
            $w = 'calc((100% - ' . (($n - 1) * $gapX) . 'px) / ' . $n . ')';
            return 'flex:0 0 ' . $w . ' !important;max-width:' . $w . ' !important;';
        };

        $rowRule = 'display:flex !important;flex-wrap:wrap !important;flex-direction:row !important;column-gap:' . $gapX . 'px;row-gap:' . $gapY . 'px;';

        // Base falls back to "all in one row" (count) so a breakpoint-only value
        // doesn't stack the desktop layout.
        $css = $containerSel . '{' . $rowRule . '}'
            . $containerSel . ' ' . $childSel . '{' . $colRule($base ?? $count) . '}';
        if ($tablet !== null) {
            $css .= '@media (max-width:1023px){' . $containerSel . '{' . $rowRule . '}' . $containerSel . ' ' . $childSel . '{' . $colRule($tablet) . '}}';
        }
        if ($mobile !== null) {
            $css .= '@media (max-width:639px){' . $containerSel . '{' . $rowRule . '}' . $containerSel . ' ' . $childSel . '{' . $colRule($mobile) . '}}';
        }

        return $css;
    }

    private function elementHoverCss(array $p, string $scope): string
    {
        $out = [];
        if (($p['hoverBackgroundType'] ?? null) === 'gradient') {
            $from = $this->sanitizeColor($p['hoverGradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($p['hoverGradientTo'] ?? null) ?? '#3b82f6';
            $out[] = 'background:linear-gradient(135deg,' . $from . ',' . $to . ')';
        } elseif ($this->sanitizeColor($p['hoverBackgroundColor'] ?? null) !== null) {
            $out[] = 'background:' . $this->sanitizeColor($p['hoverBackgroundColor']);
        }

        $borderStyle = is_string($p['hoverBorderStyle'] ?? null) ? trim((string) $p['hoverBorderStyle']) : '';
        if ($borderStyle !== '' && $borderStyle !== 'default') {
            $out[] = 'border-style:' . $borderStyle;
        }
        $borderColor = $this->sanitizeColor($p['hoverBorderColor'] ?? null);
        if ($borderColor !== null) {
            $out[] = 'border-color:' . $borderColor;
        }
        foreach (['Top', 'Right', 'Bottom', 'Left'] as $side) {
            $w = $p['hoverBorderWidth' . $side] ?? null;
            if (is_numeric($w)) {
                $out[] = 'border-' . strtolower($side) . '-width:' . (int) round((float) $w) . 'px';
            }
        }
        foreach (['Top' => 'border-top-left-radius', 'Right' => 'border-top-right-radius', 'Bottom' => 'border-bottom-right-radius', 'Left' => 'border-bottom-left-radius'] as $side => $prop) {
            $r = $p['hoverBorderRadius' . $side] ?? null;
            if (is_numeric($r)) {
                $out[] = $prop . ':' . (int) round((float) $r) . 'px';
            }
        }
        $shadowColor = $this->sanitizeColor($p['hoverShadowColor'] ?? null);
        if ($shadowColor !== null) {
            $x = is_numeric($p['hoverShadowX'] ?? null) ? (int) $p['hoverShadowX'] : 0;
            $y = is_numeric($p['hoverShadowY'] ?? null) ? (int) $p['hoverShadowY'] : 0;
            $blur = is_numeric($p['hoverShadowBlur'] ?? null) ? (int) $p['hoverShadowBlur'] : 0;
            $spread = is_numeric($p['hoverShadowSpread'] ?? null) ? (int) $p['hoverShadowSpread'] : 0;
            $out[] = 'box-shadow:' . $x . 'px ' . $y . 'px ' . $blur . 'px ' . $spread . 'px ' . $shadowColor;
        }

        if (empty($out)) {
            return '';
        }
        $important = array_map(static fn (string $decl): string => $decl . ' !important', $out);
        $transition = '.' . $scope . '{transition:background-color .25s ease, background .25s ease, border-color .25s ease, border-width .25s ease, border-radius .25s ease, box-shadow .25s ease;}';
        return $transition . '.' . $scope . ':hover{' . implode(';', $important) . ';}';
    }

    private function testimonialCard(array $props, array $item, string $scope = ''): string
    {
        $rating = is_numeric($item['rating'] ?? null) ? max(0.0, min(5.0, (float) $item['rating'])) : 5.0;
        $starColor = $this->sanitizeColor($props['starColor'] ?? null) ?? '#F59E0B';
        $starSize = is_numeric($props['starSize'] ?? null) ? (int) $props['starSize'] : 18;
        $quote = is_string($item['quote'] ?? null) ? $item['quote'] : '';
        $quoteColor = $this->sanitizeColor($props['quoteColor'] ?? null) ?? '#111827';
        $name = is_string($item['name'] ?? null) ? $item['name'] : '';
        $role = is_string($item['role'] ?? null) ? $item['role'] : '';
        $nameColor = $this->sanitizeColor($props['nameColor'] ?? null) ?? '#111827';
        $roleColor = $this->sanitizeColor($props['roleColor'] ?? null) ?? '#6b7280';
        $avatarSize = is_numeric($props['avatarSize'] ?? null) ? (int) $props['avatarSize'] : 44;

        $avatarUrl = is_string($item['avatarUrl'] ?? null) ? $item['avatarUrl'] : '';
        $avatarSvg = is_string($item['avatarSvg'] ?? null) ? $item['avatarSvg'] : '';
        $avatarSource = ($item['avatarSource'] ?? null) === 'svg' ? 'svg' : 'url';
        $useSvg = $avatarSvg !== '' && ($avatarSource === 'svg' || $avatarUrl === '');

        $cardGap = is_numeric($props['cardGap'] ?? null) ? (int) round((float) $props['cardGap']) : 12;
        $containerStyle = 'display:flex;flex-direction:column;gap:' . $cardGap . 'px;box-sizing:border-box;height:100%;';
        $bgType = ($props['backgroundType'] ?? null) === 'gradient' ? 'gradient' : 'color';
        if ($bgType === 'gradient') {
            $from = $this->sanitizeColor($props['gradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($props['gradientTo'] ?? null) ?? '#3b82f6';
            $containerStyle .= 'background:linear-gradient(135deg,' . e($from) . ',' . e($to) . ');';
        } elseif (is_string($props['backgroundColor'] ?? null) && trim($props['backgroundColor']) !== '') {
            $bg = $this->sanitizeColor($props['backgroundColor']);
            if ($bg !== null) {
                $containerStyle .= 'background:' . $bg . ';';
            }
        }

        $containerStyle .= 'margin-top:' . e($this->cssLength($props['marginTop'] ?? null, '0px')) . ';';
        $containerStyle .= 'margin-right:' . e($this->cssLength($props['marginRight'] ?? null, '0px')) . ';';
        $containerStyle .= 'margin-bottom:' . e($this->cssLength($props['marginBottom'] ?? null, '0px')) . ';';
        $containerStyle .= 'margin-left:' . e($this->cssLength($props['marginLeft'] ?? null, '0px')) . ';';
        $containerStyle .= 'padding-top:' . e($this->cssLength($props['paddingTop'] ?? null, '24px')) . ';';
        $containerStyle .= 'padding-right:' . e($this->cssLength($props['paddingRight'] ?? null, '24px')) . ';';
        $containerStyle .= 'padding-bottom:' . e($this->cssLength($props['paddingBottom'] ?? null, '24px')) . ';';
        $containerStyle .= 'padding-left:' . e($this->cssLength($props['paddingLeft'] ?? null, '24px')) . ';';

        $borderStyle = is_string($props['borderStyle'] ?? null) ? trim((string) $props['borderStyle']) : '';
        if ($borderStyle !== '' && $borderStyle !== 'default') {
            $containerStyle .= 'border-style:' . e($borderStyle) . ';';
        }
        if (is_string($props['borderColor'] ?? null) && trim($props['borderColor']) !== '') {
            $bc = $this->sanitizeColor($props['borderColor']);
            if ($bc !== null) {
                $containerStyle .= 'border-color:' . $bc . ';';
            }
        }
        $containerStyle .= 'border-top-width:' . e($this->cssLength($props['borderWidthTop'] ?? null, '0px')) . ';';
        $containerStyle .= 'border-right-width:' . e($this->cssLength($props['borderWidthRight'] ?? null, '0px')) . ';';
        $containerStyle .= 'border-bottom-width:' . e($this->cssLength($props['borderWidthBottom'] ?? null, '0px')) . ';';
        $containerStyle .= 'border-left-width:' . e($this->cssLength($props['borderWidthLeft'] ?? null, '0px')) . ';';
        $containerStyle .= 'border-top-left-radius:' . e($this->cssLength($props['borderRadiusTop'] ?? null, '12px')) . ';';
        $containerStyle .= 'border-top-right-radius:' . e($this->cssLength($props['borderRadiusRight'] ?? null, '12px')) . ';';
        $containerStyle .= 'border-bottom-right-radius:' . e($this->cssLength($props['borderRadiusBottom'] ?? null, '12px')) . ';';
        $containerStyle .= 'border-bottom-left-radius:' . e($this->cssLength($props['borderRadiusLeft'] ?? null, '12px')) . ';';

        $quoteStyle = 'margin:0;color:' . e($quoteColor) . ';font-size:' . e($this->cssLength($props['quoteFontSize'] ?? null, '16px')) . ';line-height:1.6;';
        $quoteFontFamily = $this->sanitizeFontFamily($props['quoteFontFamily'] ?? null);
        if ($quoteFontFamily !== null) {
            $quoteStyle .= 'font-family:' . $quoteFontFamily . ';';
        }
        $quoteFontWeight = $this->sanitizeFontWeight($props['quoteFontWeight'] ?? null);
        if ($quoteFontWeight !== null) {
            $quoteStyle .= 'font-weight:' . $quoteFontWeight . ';';
        }
        if (is_numeric($props['quoteLineHeight'] ?? null)) {
            $quoteStyle .= 'line-height:' . (float) $props['quoteLineHeight'] . ';';
        }
        $quoteLetterSpacing = $this->cssLength($props['quoteLetterSpacing'] ?? null, '');
        if ($quoteLetterSpacing !== '') {
            $quoteStyle .= 'letter-spacing:' . e($quoteLetterSpacing) . ';';
        }
        $quoteWordSpacing = $this->cssLength($props['quoteWordSpacing'] ?? null, '');
        if ($quoteWordSpacing !== '') {
            $quoteStyle .= 'word-spacing:' . e($quoteWordSpacing) . ';';
        }

        $nameStyle = 'color:' . e($nameColor) . ';font-size:' . e($this->cssLength($props['nameFontSize'] ?? null, '15px')) . ';';
        $nameFontWeight = $this->sanitizeFontWeight($props['nameFontWeight'] ?? null);
        $nameStyle .= 'font-weight:' . ($nameFontWeight ?? '600') . ';';
        $nameFontFamily = $this->sanitizeFontFamily($props['nameFontFamily'] ?? null);
        if ($nameFontFamily !== null) {
            $nameStyle .= 'font-family:' . $nameFontFamily . ';';
        }
        $nameLetterSpacing = $this->cssLength($props['nameLetterSpacing'] ?? null, '');
        if ($nameLetterSpacing !== '') {
            $nameStyle .= 'letter-spacing:' . e($nameLetterSpacing) . ';';
        }

        $avatarHtml = '';
        if ($useSvg) {
            $svgSizeStyle = 'display:inline-block;width:' . $avatarSize . 'px;height:' . $avatarSize . 'px;';
            if (preg_match('/<svg\b[^>]*\bstyle\s*=/i', $avatarSvg)) {
                $avatarSvgSized = preg_replace('/(<svg\b[^>]*\bstyle\s*=\s*)(["\'])([\s\S]*?)\2/i', '$1$2$3;' . $svgSizeStyle . '$2', $avatarSvg, 1) ?? $avatarSvg;
            } else {
                $avatarSvgSized = preg_replace('/<svg\b/i', '<svg style="' . $svgSizeStyle . '"', $avatarSvg, 1) ?? $avatarSvg;
            }
            $avatarHtml = '<div style="width:' . $avatarSize . 'px;height:' . $avatarSize . 'px;flex-shrink:0;">' . $avatarSvgSized . '</div>';
        } elseif ($avatarUrl !== '') {
            $avatarHtml = '<img src="' . e($avatarUrl) . '" alt="' . e($name) . '" style="width:' . $avatarSize . 'px;height:' . $avatarSize . 'px;border-radius:999px;object-fit:cover;flex-shrink:0;" />';
        } else {
            $initial = $name !== '' ? mb_strtoupper(mb_substr(trim($name), 0, 1)) : '?';
            $avatarHtml = '<div style="width:' . $avatarSize . 'px;height:' . $avatarSize . 'px;border-radius:999px;background:#e2e8f0;color:#64748b;display:flex;align-items:center;justify-content:center;font-weight:600;flex-shrink:0;">' . e($initial) . '</div>';
        }

        $nameBlock = '';
        if ($name !== '') {
            $nameBlock .= '<div style="' . $nameStyle . '">' . e($name) . '</div>';
        }
        if ($role !== '') {
            $nameBlock .= '<div style="color:' . e($roleColor) . ';font-size:13px;">' . e($role) . '</div>';
        }

        $inner = $this->carouselStars($rating, $starColor, $starSize);
        if ($quote !== '') {
            $inner .= '<div style="' . $quoteStyle . '">' . $this->sanitizeRichText($quote) . '</div>';
        }
        $inner .= '<div style="display:flex;align-items:center;gap:10px;margin-top:auto;">' . $avatarHtml . '<div style="min-width:0;">' . $nameBlock . '</div></div>';

        $hoverCss = $scope !== '' ? $this->elementHoverCss($props, $scope) : '';
        $styleTag = '<style>.mp-testimonial p,.mp-testimonial ul,.mp-testimonial ol{margin:0;}' . $hoverCss . '</style>';
        $class = 'mp-testimonial' . ($scope !== '' ? ' ' . $scope : '');

        return $styleTag . '<div class="' . e($class) . '" style="' . e($containerStyle) . '">' . $inner . '</div>';
    }

    private function renderTestimonial(array $props): string
    {
        $scope = 'mp-testimonial-hover-' . bin2hex(random_bytes(3));
        return $this->testimonialCard($props, [
            'avatarUrl' => $props['avatarUrl'] ?? null,
            'avatarSvg' => $props['avatarSvg'] ?? null,
            'avatarSource' => $props['avatarSource'] ?? null,
            'rating' => $props['rating'] ?? null,
            'quote' => $props['quote'] ?? null,
            'name' => $props['name'] ?? null,
            'role' => $props['role'] ?? null,
        ], $scope);
    }

    private function renderTestimonials(array $props): string
    {
        $config = $this->carouselConfig($props);
        $items = array_values(array_filter(is_array($props['items'] ?? null) ? $props['items'] : [], 'is_array'));
        if (empty($items)) {
            return '';
        }

        $scope = 'mp-testimonial-hover-' . bin2hex(random_bytes(3));
        $chunks = array_chunk($items, max(1, $config['perPage']));
        $pages = '';
        foreach ($chunks as $group) {
            $slides = '';
            foreach ($group as $item) {
                $slides .= '<div class="mp-carousel__slide">' . $this->testimonialCard($props, $item, $scope) . '</div>';
            }
            $pages .= '<div class="mp-carousel__page" style="gap:' . $config['gap'] . 'px;">' . $slides . '</div>';
        }

        return $this->renderCarousel($pages, count($chunks), $config, $props);
    }

    private function renderSlider(array $props): string
    {
        $images = $props['images'] ?? [];
        if (!is_array($images) || count($images) === 0) {
            return '';
        }

        $width = $this->intOrNull($props['width'] ?? null);
        if ($width !== null) {
            if ($width < 0) {
                $width = 0;
            }
            if ($width > 2400) {
                $width = 2400;
            }
            if ($width === 0) {
                $width = null;
            }
        }

        $height = $this->intOrNull($props['height'] ?? null) ?? 360;
        if ($height < 120) {
            $height = 120;
        }
        if ($height > 900) {
            $height = 900;
        }

        $items = '';
        foreach ($images as $img) {
            if (!is_array($img)) {
                continue;
            }
            $url = is_string($img['url'] ?? null) ? trim((string) $img['url']) : '';
            $alt = is_string($img['alt'] ?? null) ? (string) $img['alt'] : '';
            if ($url === '') {
                continue;
            }
            $items .= '<div>'
                . '<img src="' . e($url) . '" alt="' . e($alt) . '" style="height:' . e((string) $height) . 'px;object-fit:cover;" />'
                . '</div>';
        }

        if ($items === '') {
            return '';
        }

        $perPage = $this->intOrNull($props['carouselPerPage'] ?? null);
        if ($perPage === null) {
            $perPage = 1;
        }
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($perPage > 6) {
            $perPage = 6;
        }

        $gap = $this->intOrNull($props['carouselGap'] ?? null);
        if ($gap === null) {
            $gap = 16;
        }
        if ($gap < 0) {
            $gap = 0;
        }
        if ($gap > 120) {
            $gap = 120;
        }

        $autoplay = ($props['carouselAutoplay'] ?? null) === false ? false : true;
        $arrows = ($props['carouselArrows'] ?? null) === false ? false : true;

        $interval = $props['carouselInterval'] ?? null;
        $interval = is_numeric($interval) ? (float) $interval : 4.0;
        if (!is_finite($interval) || $interval < 1) {
            $interval = 1;
        }
        if ($interval > 60) {
            $interval = 60;
        }

        $ease = is_string($props['carouselEase'] ?? null) ? trim((string) $props['carouselEase']) : '';
        if (!in_array($ease, ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out'], true)) {
            $ease = 'ease';
        }

        $arrowSize = $this->intOrNull($props['arrowSize'] ?? null);
        if ($arrowSize !== null) {
            if ($arrowSize < 24) {
                $arrowSize = 24;
            }
            if ($arrowSize > 72) {
                $arrowSize = 72;
            }
        }

        $arrowRadius = $this->intOrNull($props['arrowRadius'] ?? null);
        if ($arrowRadius !== null) {
            if ($arrowRadius < 0) {
                $arrowRadius = 0;
            }
            if ($arrowRadius > 999) {
                $arrowRadius = 999;
            }
        }

        $arrowBg = $this->sanitizeColor($props['arrowBgColor'] ?? null);
        $arrowColor = $this->sanitizeColor($props['arrowColor'] ?? null);
        $arrowBorder = $this->sanitizeColor($props['arrowBorderColor'] ?? null);

        $style = '--mp-slider-gap:' . (string) $gap . 'px;'
            . '--mp-slider-per-page:' . (string) $perPage . ';';

        if ($arrowSize !== null) {
            $style .= '--mp-slider-arrow-size:' . (string) $arrowSize . 'px;';
        }
        if ($arrowRadius !== null) {
            $style .= '--mp-slider-arrow-radius:' . (string) $arrowRadius . 'px;';
        }
        if ($arrowBg !== null) {
            $style .= '--mp-slider-arrow-bg:' . $arrowBg . ';';
        }
        if ($arrowColor !== null) {
            $style .= '--mp-slider-arrow-color:' . $arrowColor . ';';
        }
        if ($arrowBorder !== null) {
            $style .= '--mp-slider-arrow-border:' . $arrowBorder . ';';
        }
        if ($width !== null) {
            $style .= 'width:' . (string) $width . 'px;';
        }

        $attrs = ' data-height="' . e((string) $height) . '"'
            . ' data-mp-slider-per-page="' . e((string) $perPage) . '"'
            . ' data-mp-slider-interval="' . e((string) $interval) . '"'
            . ' data-mp-slider-ease="' . e($ease) . '"'
            . ($autoplay ? '' : ' data-mp-slider-autoplay="0"')
            . ($arrows ? '' : ' data-mp-slider-arrows="0"');

        return '<div class="mp-slider"' . $attrs . ' style="' . e($style) . '">' 
            . '<div class="mp-slider__track">'
            . $items
            . '</div>'
            . '</div>';
    }

    private function applyBackdropBlur(array $style, string $styles): string
    {
        $backdropBlur = $this->intOrNull($style['backdropBlur'] ?? null);
        if ($backdropBlur !== null && $backdropBlur > 0) {
            $styles .= 'backdrop-filter:blur(' . $backdropBlur . 'px);-webkit-backdrop-filter:blur(' . $backdropBlur . 'px);';
        }
        return $styles;
    }

    private function buildSectionStyle(array $style): string
    {
        $bgType = is_string($style['backgroundType'] ?? null) ? trim((string) $style['backgroundType']) : 'color';
        $bgType = in_array($bgType, ['color', 'gradient', 'image'], true) ? $bgType : 'color';

        if ($bgType === 'gradient') {
            $from = $this->sanitizeColor($style['gradientFrom'] ?? null);
            $to = $this->sanitizeColor($style['gradientTo'] ?? null);
            $angle = $this->intOrNull($style['gradientAngle'] ?? null) ?? 135;
            if ($angle < 0) {
                $angle = 0;
            }
            if ($angle > 360) {
                $angle = 360;
            }
            if ($from !== null && $to !== null) {
                $gType = is_string($style['gradientType'] ?? null) ? trim((string) $style['gradientType']) : 'linear';
                $gType = in_array($gType, ['linear', 'radial'], true) ? $gType : 'linear';
                if ($gType === 'radial') {
                    $position = is_string($style['gradientPosition'] ?? null) ? trim((string) $style['gradientPosition']) : '';
                    $allowedPositions = [
                        'top left',
                        'top center',
                        'top right',
                        'center left',
                        'center center',
                        'center right',
                        'bottom left',
                        'bottom center',
                        'bottom right',
                    ];
                    if (!in_array($position, $allowedPositions, true)) {
                        $position = 'top center';
                    }
                    return $this->applyBackdropBlur($style, 'background-image:radial-gradient(at ' . $position . ', ' . $from . ' 0%, ' . $to . ' 100%);');
                }

                return $this->applyBackdropBlur($style, 'background-image:linear-gradient(' . $angle . 'deg,' . $from . ' 0%,' . $to . ' 100%);');
            }
        }

        if ($bgType === 'image') {
            $img = is_array($style['backgroundImage'] ?? null) ? $style['backgroundImage'] : [];
            $url = is_string($img['url'] ?? null) ? trim((string) $img['url']) : '';
            $img2 = is_array($style['backgroundImage2'] ?? null) ? $style['backgroundImage2'] : [];
            $url2 = is_string($img2['url'] ?? null) ? trim((string) $img2['url']) : '';

            $urls = [];
            if ($url !== '') {
                $urls[] = $this->cssUrl($url);
            }
            if ($url2 !== '') {
                $urls[] = $this->cssUrl($url2);
            }

            if (count($urls) > 0) {
                $size = is_string($style['backgroundSize'] ?? null) ? trim((string) $style['backgroundSize']) : 'cover';
                if ($size === '' || !preg_match('/^(cover|contain|auto|\d+(?:\.\d+)?(px|%)(?:\s+\d+(?:\.\d+)?(px|%))?)$/', $size)) {
                    $size = 'cover';
                }
                $position = is_string($style['backgroundPosition'] ?? null) ? trim((string) $style['backgroundPosition']) : 'center';
                $allowedPositions = [
                    'center',
                    'top',
                    'bottom',
                    'left',
                    'right',
                    'top left',
                    'top right',
                    'bottom left',
                    'bottom right',
                    'top center',
                    'center left',
                    'center center',
                    'center right',
                    'bottom center',
                ];
                if (!in_array($position, $allowedPositions, true)) {
                    $position = 'center';
                }
                $repeat = is_string($style['backgroundRepeat'] ?? null) ? trim((string) $style['backgroundRepeat']) : 'no-repeat';
                if (!in_array($repeat, ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], true)) {
                    $repeat = 'no-repeat';
                }

                $bgImages = 'background-image:' . implode(',', array_map(fn ($u) => 'url(' . $u . ')', $urls)) . ';';
                $sizes = 'background-size:' . implode(',', array_fill(0, count($urls), $size)) . ';';
                $positions = 'background-position:' . implode(',', array_fill(0, count($urls), $position)) . ';';
                $repeats = 'background-repeat:' . implode(',', array_fill(0, count($urls), $repeat)) . ';';
                return $this->applyBackdropBlur($style, $bgImages . $sizes . $positions . $repeats);
            }
        }

        $color = $this->sanitizeColor($style['backgroundColor'] ?? null);
        $styleOut = '';
        if ($color !== null) {
            $styleOut .= 'background-color:' . $color . ';';
        }

        $backdropBlur = $this->intOrNull($style['backdropBlur'] ?? null);
        if ($backdropBlur !== null && $backdropBlur > 0) {
            $styleOut .= 'backdrop-filter:blur(' . $backdropBlur . 'px);-webkit-backdrop-filter:blur(' . $backdropBlur . 'px);';
        }

        return $styleOut;
    }

    private function renderBackgroundVideo(array $style): string
    {
        $bgVideoType = is_string($style['backgroundVideoType'] ?? null) ? trim((string) $style['backgroundVideoType']) : '';
        $bgVideoType = in_array($bgVideoType, ['youtube', 'mp4'], true) ? $bgVideoType : '';

        if ($bgVideoType === 'youtube') {
            $youtubeId = is_string($style['youtubeId'] ?? null) ? trim((string) $style['youtubeId']) : '';
            if ($youtubeId === '') {
                return '';
            }

            $src = 'https://www.youtube.com/embed/' . rawurlencode($youtubeId) . '?autoplay=1&mute=1&loop=1&playlist=' . rawurlencode($youtubeId) . '&controls=0&showinfo=0&modestbranding=1&playsinline=1';

            return '<div class="mp-bg-video">'
                . '<iframe class="mp-bg-video__frame" src="' . e($src) . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>'
                . '<div class="mp-bg-video__overlay"></div>'
                . '</div>';
        }

        if ($bgVideoType === 'mp4') {
            $url = is_string($style['mp4Url'] ?? null) ? trim((string) $style['mp4Url']) : '';
            if ($url === '') {
                return '';
            }

            return '<div class="mp-bg-video">'
                . '<video class="mp-bg-video__video" autoplay muted loop playsinline src="' . e($url) . '"></video>'
                . '<div class="mp-bg-video__overlay"></div>'
                . '</div>';
        }

        return '';
    }

    private function renderGridOverlay(array $style): string
    {
        if (($style['gridOverlay'] ?? null) !== true) {
            return '';
        }

        $size = is_numeric($style['gridSize'] ?? null) ? (int) $style['gridSize'] : 40;
        if ($size < 8) {
            $size = 8;
        }
        if ($size > 200) {
            $size = 200;
        }

        $color = is_string($style['gridColor'] ?? null) ? trim((string) $style['gridColor']) : 'rgba(0,0,0,0.04)';
        if ($color === '') {
            $color = 'rgba(0,0,0,0.04)';
        }

        $opacity = is_numeric($style['gridOpacity'] ?? null) ? (float) $style['gridOpacity'] : 0.3;
        if ($opacity < 0) {
            $opacity = 0;
        }
        if ($opacity > 1) {
            $opacity = 1;
        }

        $overlayColor = $color;
        if ($opacity < 1) {
            // If the color is already rgba, keep it; otherwise apply opacity via inline style on the element
            $overlayColor = $color;
        }

        $css = 'position:absolute;inset:0;pointer-events:none;z-index:1;'
            . 'background-image:linear-gradient(' . e($overlayColor) . ' 1px, transparent 1px),'
            . 'linear-gradient(90deg,' . e($overlayColor) . ' 1px, transparent 1px);'
            . 'background-size:' . $size . 'px ' . $size . 'px;';

        return '<div class="mp-section__grid-overlay" style="' . $css . '"></div>';
    }

    private function renderSectionBackgroundPreset(array $style, string $sectionId): string
    {
        $preset = is_array($style['backgroundPreset'] ?? null) ? $style['backgroundPreset'] : null;
        if (!is_array($preset)) {
            return '';
        }
        $key = is_string($preset['key'] ?? null) ? $preset['key'] : 'css-none';
        if ($key === 'css-none' || $key === '') {
            return '';
        }
        $category = is_string($preset['category'] ?? null) ? $preset['category'] : 'css';
        $params = is_array($preset['params'] ?? null) ? $preset['params'] : [];

        if ($category === 'css') {
            return $this->renderCssPresetOverlay($key, $params, $sectionId);
        }
        if ($category === 'svg') {
            return $this->renderSvgPresetOverlay($key, $params, $sectionId);
        }
        if ($category === 'webgl') {
            if (str_starts_with($key, 'gradflow-')) {
                $this->gradflowPresetsUsed = true;
            } else {
                $this->webglPresetsUsed = true;
            }
            return $this->renderWebglPresetOverlay($key, $params, $sectionId);
        }
        return '';
    }

    private function renderCssPresetOverlay(string $key, array $params, string $sectionId): string
    {
        $c1 = $this->sanitizeColor($params['color1'] ?? '#6366f1') ?? '#6366f1';
        $c2 = $this->sanitizeColor($params['color2'] ?? '#ec4899') ?? '#ec4899';
        $c3 = $this->sanitizeColor($params['color3'] ?? '#8b5cf6') ?? '#8b5cf6';
        $opacity = is_numeric($params['opacity'] ?? null) ? (float) $params['opacity'] : 1;
        $opacity = max(0, min(1, $opacity));
        $scale = is_numeric($params['scale'] ?? null) ? (float) $params['scale'] : 1;
        $scale = max(0.1, min(5, $scale));

        $classes = 'mp-bg-preset';
        $style = 'position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;opacity:' . $opacity . ';';

        switch ($key) {
            case 'css-mesh-soft':
                $style .= 'background-image:radial-gradient(at 0% 0%, ' . e($c1) . ' 0px, transparent 50%), radial-gradient(at 100% 0%, ' . e($c2) . ' 0px, transparent 50%), radial-gradient(at 100% 100%, ' . e($c3) . ' 0px, transparent 50%), radial-gradient(at 0% 100%, ' . e($this->interpolateColor($c1, $c2, 0.5)) . ' 0px, transparent 50%);';
                $style .= 'background-color:' . e($this->interpolateColor($c1, '#ffffff', 0.92)) . ';';
                break;
            case 'css-mesh-vivid':
                $style .= 'background-image:radial-gradient(at 0% 0%, ' . e($c1) . ' 0px, transparent 50%), radial-gradient(at 100% 0%, ' . e($c2) . ' 0px, transparent 50%), radial-gradient(at 100% 100%, ' . e($c3) . ' 0px, transparent 50%), radial-gradient(at 50% 50%, ' . e($c2) . ' 0px, transparent 70%);';
                $style .= 'background-color:' . e($c3) . ';';
                break;
            case 'css-animated-gradient':
                $style .= 'background-image:linear-gradient(135deg, ' . e($c1) . ', ' . e($c2) . ', ' . e($c3) . ', ' . e($c1) . ');';
                $style .= 'background-size:' . (400 / $scale) . '% ' . (400 / $scale) . '%;';
                $classes .= ' mp-gradient-animated';
                break;
            case 'css-blobs':
                $style .= 'background-image:radial-gradient(circle at 20% 30%, ' . e($c1) . ' 0%, transparent 40%), radial-gradient(circle at 80% 70%, ' . e($c2) . ' 0%, transparent 40%), radial-gradient(circle at 50% 50%, ' . e($c3) . ' 0%, transparent 50%);';
                $style .= 'background-color:' . e($this->interpolateColor($c2, '#ffffff', 0.9)) . ';';
                $style .= 'filter:blur(' . (8 * $scale) . 'px);';
                $classes .= ' mp-blobs-animated';
                break;
            case 'css-stripes':
                $size = 40 * $scale;
                $style .= 'background-image:repeating-linear-gradient(45deg, ' . e($c1) . ' 0px, ' . e($c1) . ' ' . ($size / 2) . 'px, ' . e($c2) . ' ' . ($size / 2) . 'px, ' . e($c2) . ' ' . $size . 'px);';
                break;
            case 'css-dots':
                $size = 24 * $scale;
                $style .= 'background-image:radial-gradient(' . e($c1) . ' ' . (2 * $scale) . 'px, transparent ' . (2 * $scale) . 'px);';
                $style .= 'background-size:' . $size . 'px ' . $size . 'px;';
                $style .= 'background-color:' . e($c2) . ';';
                break;
            default:
                return '';
        }

        return '<div class="' . e($classes) . '" style="' . $style . '"></div>';
    }

    private function interpolateColor(string $a, string $b, float $amount): string
    {
        $ah = hexdec(ltrim($a, '#'));
        $bh = hexdec(ltrim($b, '#'));
        $ar = ($ah >> 16) & 0xff;
        $ag = ($ah >> 8) & 0xff;
        $ab = $ah & 0xff;
        $br = ($bh >> 16) & 0xff;
        $bg = ($bh >> 8) & 0xff;
        $bb = $bh & 0xff;
        $rr = (int) round($ar + ($br - $ar) * $amount);
        $rg = (int) round($ag + ($bg - $ag) * $amount);
        $rb = (int) round($ab + ($bb - $ab) * $amount);
        return 'rgb(' . $rr . ', ' . $rg . ', ' . $rb . ')';
    }

    private function renderSvgPresetOverlay(string $key, array $params, string $sectionId): string
    {
        $c1 = $this->sanitizeColor($params['color1'] ?? '#6366f1') ?? '#6366f1';
        $c2 = $this->sanitizeColor($params['color2'] ?? '#ec4899') ?? '#ec4899';
        $c3 = $this->sanitizeColor($params['color3'] ?? '#8b5cf6') ?? '#8b5cf6';
        $opacity = is_numeric($params['opacity'] ?? null) ? (float) $params['opacity'] : 1;
        $opacity = max(0, min(1, $opacity));
        $scale = is_numeric($params['scale'] ?? null) ? (float) $params['scale'] : 1;
        $scale = max(0.1, min(5, $scale));
        $safeId = preg_replace('/[^a-z0-9_-]/i', '', $sectionId);

        if ($key === 'svg-blob-1') {
            return '<svg class="mp-bg-preset" viewBox="0 0 100 100" preserveAspectRatio="none" style="opacity:' . $opacity . ';">'
                . '<defs><linearGradient id="g-' . e($safeId) . '" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="' . e($c1) . '"/><stop offset="100%" stop-color="' . e($c2) . '"/></linearGradient></defs>'
                . '<path d="M20,50 Q30,10 50,30 T80,50 T60,80 T20,50" fill="url(#g-' . e($safeId) . ')" opacity="0.8" transform="scale(' . $scale . ')"/></svg>';
        }
        if ($key === 'svg-blob-2') {
            return '<svg class="mp-bg-preset" viewBox="0 0 100 100" preserveAspectRatio="none" style="opacity:' . $opacity . ';">'
                . '<circle cx="25" cy="30" r="' . (18 * $scale) . '" fill="' . e($c1) . '" opacity="0.6"/>'
                . '<circle cx="75" cy="60" r="' . (24 * $scale) . '" fill="' . e($c2) . '" opacity="0.6"/>'
                . '<circle cx="50" cy="80" r="' . (14 * $scale) . '" fill="' . e($c3) . '" opacity="0.5"/></svg>';
        }
        if ($key === 'svg-waves') {
            return '<svg class="mp-bg-preset" viewBox="0 0 1200 120" preserveAspectRatio="none" style="opacity:' . $opacity . ';">'
                . '<defs><linearGradient id="wg-' . e($safeId) . '" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="' . e($c1) . '" stop-opacity="0.4"/><stop offset="100%" stop-color="' . e($c2) . '" stop-opacity="0.4"/></linearGradient></defs>'
                . '<path d="M0,60 C300,10 600,110 900,60 C1050,35 1150,85 1200,60 V120 H0 Z" fill="url(#wg-' . e($safeId) . ')"/>'
                . '<path d="M0,80 C200,40 500,120 800,80 C1000,60 1100,100 1200,80 V120 H0 Z" fill="' . e($c2) . '" opacity="0.2" transform="scale(' . $scale . ')"/></svg>';
        }
        return '';
    }

    private function renderWebglPresetOverlay(string $key, array $params, string $sectionId): string
    {
        $c1 = $this->sanitizeColor($params['color1'] ?? '#111827') ?? '#111827';
        $c2 = $this->sanitizeColor($params['color2'] ?? '#3b82f6') ?? '#3b82f6';
        $c3 = $this->sanitizeColor($params['color3'] ?? '#8b5cf6') ?? '#8b5cf6';
        $opacity = is_numeric($params['opacity'] ?? null) ? (float) $params['opacity'] : 1;
        $opacity = max(0, min(1, $opacity));
        $speed = is_numeric($params['speed'] ?? null) ? (float) $params['speed'] : 10;
        $scale = is_numeric($params['scale'] ?? null) ? (float) $params['scale'] : 1;
        $scale = max(0.1, min(5, $scale));
        $safeId = preg_replace('/[^a-z0-9_-]/i', '', $sectionId);

        if (str_starts_with($key, 'gradflow-')) {
            $gfType = is_string($params['type'] ?? null) ? $params['type'] : 'aurora';
            $gfNoise = is_numeric($params['noise'] ?? null) ? (float) $params['noise'] : 0.3;
            $gfSpeed = is_numeric($params['speed'] ?? null) ? (float) $params['speed'] : 0.5;
            return '<canvas class="mp-bg-preset mp-webgl-canvas mp-gradflow-canvas" data-mp-preset="gradflow" data-mp-gf-type="' . e($gfType) . '" data-mp-colors="' . e(implode(',', [$c1, $c2, $c3])) . '" data-mp-speed="' . $gfSpeed . '" data-mp-scale="' . $scale . '" data-mp-noise="' . $gfNoise . '" id="mp-webgl-' . e($safeId) . '" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;opacity:' . $opacity . ';"></canvas>';
        }

        $c4 = $this->sanitizeColor($params['color4'] ?? '#ec4899') ?? '#ec4899';
        $c5 = $this->sanitizeColor($params['color5'] ?? '#f59e0b') ?? '#f59e0b';
        $shaderMap = ['webgl-noise' => 'noise', 'webgl-gradient-flow' => 'gradientFlow', 'webgl-vibrant-waves' => 'vibrantWaves', 'webgl-radiant-rings' => 'radiantRings', 'webgl-aurora' => 'aurora'];
        $shaderName = $shaderMap[$key] ?? 'gradientFlow';

        return '<canvas class="mp-bg-preset mp-webgl-canvas" data-mp-preset="' . e($shaderName) . '" data-mp-colors="' . e(implode(',', [$c1, $c2, $c3, $c4, $c5])) . '" data-mp-speed="' . $speed . '" data-mp-scale="' . $scale . '" id="mp-webgl-' . e($safeId) . '" style="position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;opacity:' . $opacity . ';"></canvas>';
    }

    private function sanitizeRichText(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $allowedTags = [
            'p', 'br',
            'strong', 'b',
            'em', 'i',
            'u',
            'a',
            'ul', 'ol', 'li',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'blockquote',
        ];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $this->sanitizeNode($dom, $allowedTags);

        $out = $dom->saveHTML();
        $out = is_string($out) ? $out : '';
        return preg_replace('/<\?xml[^>]*\?>/i', '', $out) ?? $out;
    }

    private function sanitizeHeadingHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $allowedTags = [
            'br',
            'strong', 'b',
            'em', 'i',
            'u',
            'a',
        ];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $this->sanitizeNode($dom, $allowedTags);

        $out = $dom->saveHTML();
        $out = is_string($out) ? $out : '';
        return preg_replace('/<\?xml[^>]*\?>/i', '', $out) ?? $out;
    }

    private function sanitizeNode(\DOMNode $node, array $allowedTags): void
    {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tag = strtolower((string) $node->nodeName);
            if (!in_array($tag, $allowedTags, true)) {
                $this->unwrapNode($node);
                return;
            }

            if ($node->hasAttributes()) {
                $href = null;
                if ($tag === 'a' && $node->attributes?->getNamedItem('href')) {
                    $href = (string) $node->attributes->getNamedItem('href')->nodeValue;
                }

                $remove = [];
                foreach ($node->attributes as $attr) {
                    $remove[] = (string) $attr->nodeName;
                }
                foreach ($remove as $name) {
                    $node->removeAttribute($name);
                }

                if ($tag === 'a' && is_string($href) && $this->isSafeHref($href)) {
                    /** @var \DOMElement $node */
                    $node->setAttribute('href', $href);
                    $node->setAttribute('rel', 'noopener noreferrer');
                    $node->setAttribute('target', '_blank');
                }
            }
        }

        if ($node->hasChildNodes()) {
            for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
                $child = $node->childNodes->item($i);
                if ($child) {
                    $this->sanitizeNode($child, $allowedTags);
                }
            }
        }
    }

    private function unwrapNode(\DOMNode $node): void
    {
        $parent = $node->parentNode;
        if (!$parent) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }

    private function isSafeHref(string $href): bool
    {
        $href = trim($href);
        if ($href === '') {
            return false;
        }

        if (str_starts_with($href, '#')) {
            return true;
        }

        return (bool) preg_match('/^(https?:\/\/|mailto:)/i', $href);
    }

    private function sanitizeColor(mixed $color): ?string
    {
        if (is_array($color) && isset($color['global']) && is_string($color['global'])) {
            $resolved = $this->globalColors[$color['global']] ?? null;
            if (is_string($resolved) && $resolved !== '') {
                $color = $resolved;
            } else {
                return null;
            }
        }

        if (is_array($color) && isset($color['r'], $color['g'], $color['b'])) {
            $r = (int) $color['r'];
            $g = (int) $color['g'];
            $b = (int) $color['b'];
            if ($r >= 0 && $r <= 255 && $g >= 0 && $g <= 255 && $b >= 0 && $b <= 255) {
                return sprintf('#%02x%02x%02x', $r, $g, $b);
            }
            return null;
        }

        if (!is_string($color)) {
            return null;
        }
        $color = trim($color);
        if ($color === '') {
            return null;
        }

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $color)) {
            return $color;
        }

        if (preg_match('/^rgba?\(\s*([0-9.]+)\s*,\s*([0-9.]+)\s*,\s*([0-9.]+)(?:\s*,\s*([0-9.]+)\s*)?\)$/i', $color, $m)) {
            $r = (float) $m[1];
            $g = (float) $m[2];
            $b = (float) $m[3];
            $a = isset($m[4]) ? (float) $m[4] : 1.0;

            if (!is_finite($r) || !is_finite($g) || !is_finite($b) || !is_finite($a)) {
                return null;
            }

            if ($r < 0 || $r > 255 || $g < 0 || $g > 255 || $b < 0 || $b > 255) {
                return null;
            }

            if ($a < 0 || $a > 1) {
                return null;
            }

            return $color;
        }

        return null;
    }

    private function injectSvgColor(string $svg, string $color): string
    {
        if ($svg === '' || $color === '') {
            return $svg;
        }
        return preg_replace_callback(
            '/<svg\b([^>]*)>/i',
            function ($matches) use ($color) {
                $attrs = $matches[1];
                $hasFillNone = preg_match('/fill=["\']none["\']/i', $attrs);
                $hasStrokeNone = preg_match('/stroke=["\']none["\']/i', $attrs);
                $styleInject = 'color:' . $color . ';';
                if (!$hasFillNone) {
                    $styleInject .= 'fill:currentColor;';
                }
                if (!$hasStrokeNone) {
                    $styleInject .= 'stroke:currentColor;';
                }
                $existingStyle = [];
                if (preg_match('/style=["\']([^"\']*)["\']/i', $attrs, $styleMatch)) {
                    $existingStyle = $styleMatch;
                }
                if (!empty($existingStyle)) {
                    $newStyle = $existingStyle[1] . $styleInject;
                    $newAttrs = str_replace($existingStyle[0], 'style="' . $newStyle . '"', $attrs);
                    return '<svg' . $newAttrs . '>';
                }
                return '<svg' . $attrs . ' style="' . $styleInject . '">';
            },
            $svg
        ) ?? $svg;
    }

    private function sanitizeAlign(mixed $align): ?string
    {
        if (!is_string($align)) {
            return null;
        }

        $align = trim((string) $align);
        if ($align === '') {
            return null;
        }

        return in_array($align, ['left', 'center', 'right', 'justify'], true) ? $align : null;
    }

    private function sanitizeFontWeight(mixed $weight): ?int
    {
        $n = $this->intOrNull($weight);
        if ($n === null) {
            return null;
        }
        if (!in_array($n, [100, 200, 300, 400, 500, 600, 700, 800, 900], true)) {
            return null;
        }
        return $n;
    }

    private function sanitizeFontFamily(mixed $family): ?string
    {
        if (!is_string($family)) {
            return null;
        }
        $family = trim((string) $family);
        if ($family === '') {
            return null;
        }
        if (strlen($family) > 180) {
            return null;
        }

        // allow common font-family characters only
        if (!preg_match('/^[A-Za-z0-9\s\-,\(\)\'"\"]+$/', $family)) {
            return null;
        }

        return $family;
    }

    private function sanitizeIntPx(mixed $value, int $min, int $max): ?int
    {
        $n = $this->intOrNull($value);
        if ($n === null) {
            return null;
        }
        if ($n < $min) {
            $n = $min;
        }
        if ($n > $max) {
            $n = $max;
        }
        return $n;
    }

    private function sanitizeFloatPx(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            $n = (float) $value;
            if (!is_finite($n)) {
                return null;
            }
            if ($n < $min) {
                $n = $min;
            }
            if ($n > $max) {
                $n = $max;
            }
            return $n;
        }
        return null;
    }

    private function intOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Normalize a builder length (numeric px, CSS string, or {value, unit})
     * into a valid CSS length string.
     */
    private function cssLength(mixed $value, string $fallback): string
    {
        if (is_int($value) || is_float($value)) {
            return $value . 'px';
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        if (
            is_array($value)
            && isset($value['value'], $value['unit'])
            && is_string($value['unit'])
            && $value['unit'] !== ''
            && $value['value'] !== ''
            && $value['value'] !== null
        ) {
            return $value['value'] . $value['unit'];
        }

        return $fallback;
    }

    /**
     * Mirror the builder's cssLengthToCss(value, { lineHeight: true }):
     * numbers between 0 and 6 stay unitless multipliers; otherwise px/CSS units.
     */
    private function cssLineHeight(mixed $value, string $fallback = ''): string
    {
        if (is_int($value) || is_float($value)) {
            if ($value > 0 && $value < 6) {
                return (string) $value;
            }
            return $value . 'px';
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return $fallback;
            }
            if (preg_match('/^([-+]?\d+(?:\.\d+)?)$/', $trimmed, $m)) {
                $num = (float) $m[1];
                if ($num > 0 && $num < 6) {
                    return $m[1];
                }
            }
            return $trimmed;
        }

        return $this->cssLength($value, $fallback);
    }

    /**
     * Build a CSS typography fragment (family, size, weight, line-height,
     * letter/word spacing) from prefix-paired widget props.
     */
    private function pricingTypographyStyle(array $props, string $prefix): string
    {
        $style = '';

        $fontFamily = $this->sanitizeFontFamily($props[$prefix . 'FontFamily'] ?? null);
        if ($fontFamily !== null) {
            $style .= 'font-family:' . $fontFamily . ';';
        }

        $fontSize = $this->cssLength($props[$prefix . 'FontSize'] ?? null, '');
        if ($fontSize !== '') {
            $style .= 'font-size:' . $fontSize . ';';
        }

        $fontWeight = $props[$prefix . 'FontWeight'] ?? null;
        if (is_string($fontWeight)) {
            $fontWeight = trim($fontWeight);
        } elseif (is_int($fontWeight) || is_float($fontWeight)) {
            $fontWeight = (string) $fontWeight;
        } else {
            $fontWeight = '';
        }
        if ($fontWeight !== '' && preg_match('/^(100|200|300|400|500|600|700|800|900|normal|bold|bolder|lighter)$/i', $fontWeight)) {
            $style .= 'font-weight:' . $fontWeight . ';';
        }

        $lineHeight = $this->cssLineHeight($props[$prefix . 'LineHeight'] ?? null, '');
        if ($lineHeight !== '') {
            $style .= 'line-height:' . $lineHeight . ';';
        }

        $letterSpacing = $this->cssLength($props[$prefix . 'LetterSpacing'] ?? null, '');
        if ($letterSpacing !== '') {
            $style .= 'letter-spacing:' . $letterSpacing . ';';
        }

        $wordSpacing = $this->cssLength($props[$prefix . 'WordSpacing'] ?? null, '');
        if ($wordSpacing !== '') {
            $style .= 'word-spacing:' . $wordSpacing . ';';
        }

        return $style;
    }

    private function borderEffectClassName(array $props, string $prefix = 'border'): string
    {
        $effectKey = $prefix . 'Effect';
        $effect = is_string($props[$effectKey] ?? null) ? trim((string) $props[$effectKey]) : '';
        $allowed = ['glow', 'gradient', 'sweep', 'running', 'trace', 'neon', 'shimmer'];
        if ($effect === '' || !in_array($effect, $allowed, true)) {
            return '';
        }
        return 'mp-bfx mp-bfx--' . $effect;
    }

    private function borderEffectStyleVars(array $props, string $prefix = 'border'): string
    {
        $effectKey = $prefix . 'Effect';
        $effect = is_string($props[$effectKey] ?? null) ? trim((string) $props[$effectKey]) : '';
        $allowed = ['glow', 'gradient', 'sweep', 'running', 'trace', 'neon', 'shimmer'];
        if ($effect === '' || !in_array($effect, $allowed, true)) {
            return '';
        }

        $colorKey = $prefix . 'EffectColor';
        $color2Key = $prefix . 'EffectColor2';
        $speedKey = $prefix . 'EffectSpeed';
        $thicknessKey = $prefix . 'EffectThickness';

        $color = is_string($props[$colorKey] ?? null) && trim((string) $props[$colorKey]) !== ''
            ? (string) $props[$colorKey]
            : '#3b82f6';
        $color2 = is_string($props[$color2Key] ?? null) && trim((string) $props[$color2Key]) !== ''
            ? (string) $props[$color2Key]
            : '#8b5cf6';
        $speed = is_numeric($props[$speedKey] ?? null) ? (float) $props[$speedKey] : 3;
        $speed = max(0.5, min(30, $speed));
        $thickness = is_numeric($props[$thicknessKey] ?? null) ? (int) $props[$thicknessKey] : 2;
        $thickness = max(1, min(12, $thickness));

        return '--mp-bfx-color:' . $color . ';--mp-bfx-color2:' . $color2 . ';--mp-bfx-speed:' . $speed . 's;--mp-bfx-size:' . $thickness . 'px;';
    }

    private function renderMegaMenuLayout(mixed $id): string
    {
        $layoutId = is_numeric($id) ? (int) $id : null;
        if ($layoutId === null || $layoutId <= 0) {
            return '';
        }

        try {
            $layout = SiteLayout::published()->where('type', 'megamenu')->where('id', $layoutId)->first();
            if (!$layout) {
                return '';
            }
            $html = is_string($layout->html_content) && trim($layout->html_content) !== ''
                ? $layout->html_content
                : null;
            if ($html === null) {
                $builder = is_array($layout->builder_data) ? $layout->builder_data : null;
                if ($builder !== null && $this->isBuilderData($builder)) {
                    $html = $this->render($builder);
                }
            }
            return is_string($html) ? $html : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function span(mixed $v): ?int
    {
        $n = $this->intOrNull($v);
        if ($n === null) {
            return null;
        }
        if ($n < 1) {
            return 1;
        }
        if ($n > 12) {
            return 12;
        }
        return $n;
    }

    private function cssUrl(string $url): string
    {
        $url = str_replace(['"', "'", ')', '('], '', $url);
        return '"' . $url . '"';
    }

    private function resolveNavMenuUrls(array $items): array
    {
        $allowedRoutes = [
            'home' => 'home',
            'features' => 'features',
            'pricing' => 'pricing',
            'blog.index' => 'blog.index',
            'roadmap' => 'roadmap',
            'docs' => 'docs',
            'api.docs.public' => 'api.docs.public',
        ];

        $pageCache = [];

        $resolve = function (array $item) use ($allowedRoutes, &$pageCache, &$resolve): array {
            $type = $item['type'] ?? 'link';

            if ($type === 'route') {
                $route = $item['route'] ?? '';
                $item['url'] = $allowedRoutes[$route] ?? route($route, absolute: false);
                $item['type'] = 'link';
                return $item;
            }

            if ($type === 'page') {
                $pageId = (int) ($item['page_id'] ?? 0);
                if ($pageId > 0) {
                    if (!isset($pageCache[$pageId])) {
                        $pageCache[$pageId] = Page::find($pageId);
                    }
                    $page = $pageCache[$pageId];
                    if ($page) {
                        $item['url'] = '/' . ltrim($page->slug, '/');
                        if (!isset($item['label']) || trim($item['label']) === '') {
                            $item['label'] = $page->title;
                        }
                    }
                }
                $item['type'] = 'link';
                return $item;
            }

            if ($type === 'custom') {
                $item['url'] = is_string($item['url'] ?? null) && trim((string) $item['url']) !== '' ? trim((string) $item['url']) : '#';
                $item['type'] = 'link';
                return $item;
            }

            if ($type === 'dropdown' && isset($item['children']) && is_array($item['children'])) {
                $item['children'] = array_map($resolve, $item['children']);
                return $item;
            }

            return $item;
        };

        return array_map($resolve, $items);
    }

    private function renderNavMenu(array $props): string
    {
        $items = is_array($props['items'] ?? null) ? $props['items'] : [];

        // If nav_menu_id is set, load items from the NavigationMenu model
        $navMenuId = $props['nav_menu_id'] ?? null;
        if ($navMenuId !== null && $navMenuId !== '') {
            try {
                $menu = NavigationMenu::find((int) $navMenuId);
                if ($menu && is_array($menu->items)) {
                    $items = $menu->items;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Resolve page_id / route items to URLs
        $items = $this->resolveNavMenuUrls($items);

        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'left';
        $spacing = is_string($props['spacing'] ?? null) ? trim((string) $props['spacing']) : '24';
        $mobileBreakpoint = is_string($props['mobileBreakpoint'] ?? null) ? trim((string) $props['mobileBreakpoint']) : 'md';

        $allowedAligns = ['left', 'center', 'right'];
        $align = in_array($align, $allowedAligns, true) ? $align : 'left';

        $justify = match ($align) {
            'center' => 'center',
            'right' => 'flex-end',
            default => 'flex-start',
        };

        $gap = $this->intOrNull($props['gap'] ?? null);
        $gapPx = $gap !== null && $gap >= 0 ? $gap : (is_numeric($spacing) ? (int) $spacing : 24);

        $ulStyle = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:' . $justify . ';gap:' . e((string) $gapPx) . 'px;list-style:none;';

        $marginTop = $this->intOrNull($props['marginTop'] ?? null);
        $marginRight = $this->intOrNull($props['marginRight'] ?? null);
        $marginBottom = $this->intOrNull($props['marginBottom'] ?? null);
        $marginLeft = $this->intOrNull($props['marginLeft'] ?? null);
        if ($marginTop !== null) {
            $ulStyle .= 'margin-top:' . $marginTop . 'px;';
        }
        if ($marginRight !== null) {
            $ulStyle .= 'margin-right:' . $marginRight . 'px;';
        }
        if ($marginBottom !== null) {
            $ulStyle .= 'margin-bottom:' . $marginBottom . 'px;';
        }
        if ($marginLeft !== null) {
            $ulStyle .= 'margin-left:' . $marginLeft . 'px;';
        }
        if ($marginTop === null && $marginRight === null && $marginBottom === null && $marginLeft === null) {
            $ulStyle .= 'margin:0;';
        }

        $paddingTop = $this->intOrNull($props['paddingTop'] ?? null);
        $paddingRight = $this->intOrNull($props['paddingRight'] ?? null);
        $paddingBottom = $this->intOrNull($props['paddingBottom'] ?? null);
        $paddingLeft = $this->intOrNull($props['paddingLeft'] ?? null);
        if ($paddingTop !== null) {
            $ulStyle .= 'padding-top:' . $paddingTop . 'px;';
        }
        if ($paddingRight !== null) {
            $ulStyle .= 'padding-right:' . $paddingRight . 'px;';
        }
        if ($paddingBottom !== null) {
            $ulStyle .= 'padding-bottom:' . $paddingBottom . 'px;';
        }
        if ($paddingLeft !== null) {
            $ulStyle .= 'padding-left:' . $paddingLeft . 'px;';
        }
        if ($paddingTop === null && $paddingRight === null && $paddingBottom === null && $paddingLeft === null) {
            $ulStyle .= 'padding:0;';
        }

        $borderRadius = $this->intOrNull($props['borderRadius'] ?? null);
        $borderRadiusTop = $this->intOrNull($props['borderRadiusTop'] ?? null) ?? $borderRadius ?? null;
        $borderRadiusRight = $this->intOrNull($props['borderRadiusRight'] ?? null) ?? $borderRadius ?? null;
        $borderRadiusBottom = $this->intOrNull($props['borderRadiusBottom'] ?? null) ?? $borderRadius ?? null;
        $borderRadiusLeft = $this->intOrNull($props['borderRadiusLeft'] ?? null) ?? $borderRadius ?? null;
        if ($borderRadiusTop !== null && $borderRadiusTop >= 0) {
            $ulStyle .= 'border-top-left-radius:' . $borderRadiusTop . 'px;';
        }
        if ($borderRadiusRight !== null && $borderRadiusRight >= 0) {
            $ulStyle .= 'border-top-right-radius:' . $borderRadiusRight . 'px;';
        }
        if ($borderRadiusBottom !== null && $borderRadiusBottom >= 0) {
            $ulStyle .= 'border-bottom-right-radius:' . $borderRadiusBottom . 'px;';
        }
        if ($borderRadiusLeft !== null && $borderRadiusLeft >= 0) {
            $ulStyle .= 'border-bottom-left-radius:' . $borderRadiusLeft . 'px;';
        }

        $backgroundColor = is_string($props['backgroundColor'] ?? null) ? trim((string) $props['backgroundColor']) : '';
        if ($backgroundColor !== '') {
            $ulStyle .= 'background-color:' . $backgroundColor . ';';
        }
        $backdropBlur = $this->intOrNull($props['backdropBlur'] ?? null);
        if ($backdropBlur !== null && $backdropBlur > 0) {
            $ulStyle .= 'backdrop-filter:blur(' . $backdropBlur . 'px);-webkit-backdrop-filter:blur(' . $backdropBlur . 'px);';
        }

        $linkStyle = 'text-decoration:none;';
        $color = is_string($props['color'] ?? null) ? trim((string) $props['color']) : '';
        if ($color !== '') {
            $linkStyle .= 'color:' . $color . ';';
        }
        $fontFamily = is_string($props['fontFamily'] ?? null) ? trim((string) $props['fontFamily']) : '';
        if ($fontFamily !== '') {
            $linkStyle .= 'font-family:' . $fontFamily . ';';
        }
        $fontSize = $this->intOrNull($props['fontSize'] ?? null);
        if ($fontSize !== null && $fontSize > 0) {
            $linkStyle .= 'font-size:' . $fontSize . 'px;';
        }
        $fontWeight = is_string($props['fontWeight'] ?? null) ? trim((string) $props['fontWeight']) : '';
        if ($fontWeight !== '') {
            $linkStyle .= 'font-weight:' . $fontWeight . ';';
        }
        $lineHeight = $this->intOrNull($props['lineHeight'] ?? null);
        if ($lineHeight !== null && $lineHeight > 0) {
            $linkStyle .= 'line-height:' . $lineHeight . ';';
        }
        $letterSpacing = $this->intOrNull($props['letterSpacing'] ?? null);
        if ($letterSpacing !== null) {
            $linkStyle .= 'letter-spacing:' . $letterSpacing . 'px;';
        }
        $wordSpacing = $this->intOrNull($props['wordSpacing'] ?? null);
        if ($wordSpacing !== null) {
            $linkStyle .= 'word-spacing:' . $wordSpacing . 'px;';
        }

        $dropdownWidth = $this->intOrNull($props['dropdownWidth'] ?? null);
        $dropdownMinWidth = ($dropdownWidth !== null && $dropdownWidth > 0) ? $dropdownWidth : 200;
        $dropdownUlStyle = 'position:absolute;top:calc(100% + 12px);left:0;min-width:' . $dropdownMinWidth . 'px;list-style:none;z-index:50;';
        $dmTop = $this->intOrNull($props['dropdownMarginTop'] ?? null);
        $dmRight = $this->intOrNull($props['dropdownMarginRight'] ?? null);
        $dmBottom = $this->intOrNull($props['dropdownMarginBottom'] ?? null);
        $dmLeft = $this->intOrNull($props['dropdownMarginLeft'] ?? null);
        if ($dmTop !== null) {
            $dropdownUlStyle .= 'margin-top:' . $dmTop . 'px;';
        }
        if ($dmRight !== null) {
            $dropdownUlStyle .= 'margin-right:' . $dmRight . 'px;';
        }
        if ($dmBottom !== null) {
            $dropdownUlStyle .= 'margin-bottom:' . $dmBottom . 'px;';
        }
        if ($dmLeft !== null) {
            $dropdownUlStyle .= 'margin-left:' . $dmLeft . 'px;';
        }
        if ($dmTop === null && $dmRight === null && $dmBottom === null && $dmLeft === null) {
            $dropdownUlStyle .= 'margin:0;';
        }
        $dpTop = $this->intOrNull($props['dropdownPaddingTop'] ?? null);
        $dpRight = $this->intOrNull($props['dropdownPaddingRight'] ?? null);
        $dpBottom = $this->intOrNull($props['dropdownPaddingBottom'] ?? null);
        $dpLeft = $this->intOrNull($props['dropdownPaddingLeft'] ?? null);
        if ($dpTop !== null) {
            $dropdownUlStyle .= 'padding-top:' . $dpTop . 'px;';
        }
        if ($dpRight !== null) {
            $dropdownUlStyle .= 'padding-right:' . $dpRight . 'px;';
        }
        if ($dpBottom !== null) {
            $dropdownUlStyle .= 'padding-bottom:' . $dpBottom . 'px;';
        }
        if ($dpLeft !== null) {
            $dropdownUlStyle .= 'padding-left:' . $dpLeft . 'px;';
        }
        if ($dpTop === null && $dpRight === null && $dpBottom === null && $dpLeft === null) {
            $dropdownUlStyle .= 'padding:8px 0;';
        }
        $dropdownBg = is_string($props['dropdownBackgroundColor'] ?? null) ? trim((string) $props['dropdownBackgroundColor']) : '';
        $dropdownUlStyle .= 'background-color:' . ($dropdownBg !== '' ? $dropdownBg : '#fff') . ';';
        $dropdownBorderStyle = is_string($props['dropdownBorderStyle'] ?? null) ? trim((string) $props['dropdownBorderStyle']) : '';
        if ($dropdownBorderStyle !== '') {
            $dropdownUlStyle .= 'border-style:' . $dropdownBorderStyle . ';';
            $dropdownBorderColor = is_string($props['dropdownBorderColor'] ?? null) ? trim((string) $props['dropdownBorderColor']) : '';
            if ($dropdownBorderColor !== '') {
                $dropdownUlStyle .= 'border-color:' . $dropdownBorderColor . ';';
            }
            $dropdownBorderWidths = [
                'top' => $this->intOrNull($props['dropdownBorderWidthTop'] ?? null),
                'right' => $this->intOrNull($props['dropdownBorderWidthRight'] ?? null),
                'bottom' => $this->intOrNull($props['dropdownBorderWidthBottom'] ?? null),
                'left' => $this->intOrNull($props['dropdownBorderWidthLeft'] ?? null),
            ];
            foreach ($dropdownBorderWidths as $side => $bw) {
                if ($bw !== null && $bw >= 0) {
                    $dropdownUlStyle .= 'border-' . $side . '-width:' . $bw . 'px;';
                }
            }
        } else {
            $dropdownUlStyle .= 'border:1px solid #e5e7eb;';
        }
        $dropdownBorderRadius = $this->intOrNull($props['dropdownBorderRadius'] ?? null);
        $dropdownBorderRadiusTop = $this->intOrNull($props['dropdownBorderRadiusTop'] ?? null) ?? $dropdownBorderRadius ?? 6;
        $dropdownBorderRadiusRight = $this->intOrNull($props['dropdownBorderRadiusRight'] ?? null) ?? $dropdownBorderRadius ?? 6;
        $dropdownBorderRadiusBottom = $this->intOrNull($props['dropdownBorderRadiusBottom'] ?? null) ?? $dropdownBorderRadius ?? 6;
        $dropdownBorderRadiusLeft = $this->intOrNull($props['dropdownBorderRadiusLeft'] ?? null) ?? $dropdownBorderRadius ?? 6;
        $dropdownUlStyle .= 'border-top-left-radius:' . $dropdownBorderRadiusTop . 'px;';
        $dropdownUlStyle .= 'border-top-right-radius:' . $dropdownBorderRadiusRight . 'px;';
        $dropdownUlStyle .= 'border-bottom-right-radius:' . $dropdownBorderRadiusBottom . 'px;';
        $dropdownUlStyle .= 'border-bottom-left-radius:' . $dropdownBorderRadiusLeft . 'px;';
        $dropdownUlStyle .= 'box-shadow:0 4px 12px rgba(0,0,0,0.08);';

        $dropdownLinkStyle = 'display:block;text-decoration:none;';
        $dic = is_string($props['dropdownItemColor'] ?? null) ? trim((string) $props['dropdownItemColor']) : '';
        if ($dic !== '') {
            $dropdownLinkStyle .= 'color:' . $dic . ';';
        } else {
            $dropdownLinkStyle .= 'color:#374151;';
        }
        $dif = is_string($props['dropdownItemFontFamily'] ?? null) ? trim((string) $props['dropdownItemFontFamily']) : '';
        if ($dif !== '') {
            $dropdownLinkStyle .= 'font-family:' . $dif . ';';
        }
        $dis = $this->intOrNull($props['dropdownItemFontSize'] ?? null);
        if ($dis !== null && $dis > 0) {
            $dropdownLinkStyle .= 'font-size:' . $dis . 'px;';
        } else {
            $dropdownLinkStyle .= 'font-size:14px;';
        }
        $diw = is_string($props['dropdownItemFontWeight'] ?? null) ? trim((string) $props['dropdownItemFontWeight']) : '';
        if ($diw !== '') {
            $dropdownLinkStyle .= 'font-weight:' . $diw . ';';
        }
        $dilh = $this->intOrNull($props['dropdownItemLineHeight'] ?? null);
        if ($dilh !== null && $dilh > 0) {
            $dropdownLinkStyle .= 'line-height:' . $dilh . ';';
        }
        $dils = $this->intOrNull($props['dropdownItemLetterSpacing'] ?? null);
        if ($dils !== null) {
            $dropdownLinkStyle .= 'letter-spacing:' . $dils . 'px;';
        }
        $diws = $this->intOrNull($props['dropdownItemWordSpacing'] ?? null);
        if ($diws !== null) {
            $dropdownLinkStyle .= 'word-spacing:' . $diws . 'px;';
        }
        $dimt = $this->intOrNull($props['dropdownItemMarginTop'] ?? null);
        $dimr = $this->intOrNull($props['dropdownItemMarginRight'] ?? null);
        $dimb = $this->intOrNull($props['dropdownItemMarginBottom'] ?? null);
        $diml = $this->intOrNull($props['dropdownItemMarginLeft'] ?? null);
        if ($dimt !== null) {
            $dropdownLinkStyle .= 'margin-top:' . $dimt . 'px;';
        }
        if ($dimr !== null) {
            $dropdownLinkStyle .= 'margin-right:' . $dimr . 'px;';
        }
        if ($dimb !== null) {
            $dropdownLinkStyle .= 'margin-bottom:' . $dimb . 'px;';
        }
        if ($diml !== null) {
            $dropdownLinkStyle .= 'margin-left:' . $diml . 'px;';
        }
        $dipt = $this->intOrNull($props['dropdownItemPaddingTop'] ?? null);
        $dipr = $this->intOrNull($props['dropdownItemPaddingRight'] ?? null);
        $dipb = $this->intOrNull($props['dropdownItemPaddingBottom'] ?? null);
        $dipl = $this->intOrNull($props['dropdownItemPaddingLeft'] ?? null);
        if ($dipt !== null) {
            $dropdownLinkStyle .= 'padding-top:' . $dipt . 'px;';
        }
        if ($dipr !== null) {
            $dropdownLinkStyle .= 'padding-right:' . $dipr . 'px;';
        }
        if ($dipb !== null) {
            $dropdownLinkStyle .= 'padding-bottom:' . $dipb . 'px;';
        }
        if ($dipl !== null) {
            $dropdownLinkStyle .= 'padding-left:' . $dipl . 'px;';
        }
        if ($dipt === null && $dipr === null && $dipb === null && $dipl === null) {
            $dropdownLinkStyle .= 'padding:6px 16px;';
        }
        $dibStyle = is_string($props['dropdownItemBorderStyle'] ?? null) ? trim((string) $props['dropdownItemBorderStyle']) : '';
        if ($dibStyle !== '') {
            $dropdownLinkStyle .= 'border-style:' . $dibStyle . ';';
            $dibColor = is_string($props['dropdownItemBorderColor'] ?? null) ? trim((string) $props['dropdownItemBorderColor']) : '';
            if ($dibColor !== '') {
                $dropdownLinkStyle .= 'border-color:' . $dibColor . ';';
            }
            $dibWidths = [
                'top' => $this->intOrNull($props['dropdownItemBorderWidthTop'] ?? null),
                'right' => $this->intOrNull($props['dropdownItemBorderWidthRight'] ?? null),
                'bottom' => $this->intOrNull($props['dropdownItemBorderWidthBottom'] ?? null),
                'left' => $this->intOrNull($props['dropdownItemBorderWidthLeft'] ?? null),
            ];
            foreach ($dibWidths as $side => $bw) {
                if ($bw !== null && $bw >= 0) {
                    $dropdownLinkStyle .= 'border-' . $side . '-width:' . $bw . 'px;';
                }
            }
        }
        $dibRadius = $this->intOrNull($props['dropdownItemBorderRadius'] ?? null);
        if ($dibRadius !== null && $dibRadius >= 0) {
            $dropdownLinkStyle .= 'border-radius:' . $dibRadius . 'px;';
        }

        $styleBlock = '<style>.mp-nav-dropdown,.mp-nav-megamenu{opacity:0;visibility:hidden;transform:translateY(20px) scale(0.98);transform-origin:top center;transition:opacity 400ms cubic-bezier(0.16,1,0.3,1),transform 400ms cubic-bezier(0.16,1,0.3,1),visibility 400ms;will-change:transform,opacity;pointer-events:none;}.mp-nav-dropdown.is-visible,.mp-nav-megamenu.is-visible{opacity:1;visibility:visible;transform:translateY(0) scale(1);pointer-events:auto;}</style>';

        $navScope = 'mp-nav-menu-' . substr(md5(json_encode($props)), 0, 8);
        $navHoverEffect = $this->sanitizeHoverEffect($props['hoverEffect'] ?? null);
        if ($navHoverEffect !== '') {
            $navEffectCss = $this->hoverEffectCss($navHoverEffect, '.' . $navScope, '.mp-nav-link');
            if ($navEffectCss !== '') {
                $styleBlock .= '<style>' . $navEffectCss . '</style>';
            }
            if ($navHoverEffect === 'magnetic') {
                $styleBlock .= $this->hoverMagneticScript($navScope);
            }
        }

        $html = $styleBlock . '<ul class="mp-nav-menu ' . $navScope . '" style="' . $ulStyle . '">';
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = is_string($item['label'] ?? null) ? trim((string) $item['label']) : '';
            $url = is_string($item['url'] ?? null) ? trim((string) $item['url']) : '#';
            $itemType = is_string($item['type'] ?? null) ? trim((string) $item['type']) : 'link';
            $children = is_array($item['children'] ?? null) ? $item['children'] : [];

            if ($label === '') {
                continue;
            }

            $hasDropdown = $itemType === 'dropdown' && count($children) > 0;
            $hasMegaMenu = $itemType === 'megamenu';

            $liAttrs = 'class="mp-nav-item" style="position:relative;"';
            if ($hasDropdown) {
                $liAttrs .= ' onmouseenter="var d=this.querySelector(\'.mp-nav-dropdown\');if(d){clearTimeout(this._t);d.classList.add(\'is-visible\');}" onmouseleave="var d=this.querySelector(\'.mp-nav-dropdown\');if(d){var s=this;s._t=setTimeout(function(){d.classList.remove(\'is-visible\');},250);}"';
            }
            if ($hasMegaMenu) {
                $liAttrs .= ' onmouseenter="var d=this.querySelector(\'.mp-nav-megamenu\');if(d){clearTimeout(this._t);d.classList.add(\'is-visible\');var r=this.getBoundingClientRect();if(d.style.width.indexOf(\'100vw\')!==-1||d.dataset.fullwidth===\'1\'){d.style.left=(-r.left)+\'px\';}}" onmouseleave="var d=this.querySelector(\'.mp-nav-megamenu\');if(d){var s=this;s._t=setTimeout(function(){d.classList.remove(\'is-visible\');},250);}"';
            }
            $html .= '<li ' . $liAttrs . '>';
            $html .= '<a href="' . e($url) . '" class="mp-nav-link" data-text="' . e($label) . '" style="' . $linkStyle . 'display:inline-flex;align-items:center;gap:4px;"><span class="mp-link-text">' . e($label) . '</span>';
            if ($hasDropdown || $hasMegaMenu) {
                $html .= '<svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>';
            }
            $html .= '</a>';

            if ($hasDropdown) {
                $html .= '<ul class="mp-nav-dropdown" style="' . $dropdownUlStyle . '">';
                foreach ($children as $child) {
                    if (!is_array($child)) {
                        continue;
                    }
                    $cLabel = is_string($child['label'] ?? null) ? trim((string) $child['label']) : '';
                    $cUrl = is_string($child['url'] ?? null) ? trim((string) $child['url']) : '#';
                    if ($cLabel === '') {
                        continue;
                    }
                    $html .= '<li><a href="' . e($cUrl) . '" class="mp-nav-dropdown-link" style="' . $dropdownLinkStyle . '">' . e($cLabel) . '</a></li>';
                }
                $html .= '</ul>';
            }

            if ($hasMegaMenu) {
                $megaMenuHtml = $this->renderMegaMenuLayout($item['mega_menu_id'] ?? null);
                if ($megaMenuHtml !== '') {
                    $mmWidthMode = is_string($props['megaMenuWidthMode'] ?? null) ? trim((string) $props['megaMenuWidthMode']) : 'full';
                    if ($mmWidthMode === 'fixed') {
                        $mmFixedWidth = $this->intOrNull($props['megaMenuFixedWidth'] ?? null);
                        $mmWidthPx = ($mmFixedWidth !== null && $mmFixedWidth > 0) ? $mmFixedWidth : 800;
                        $mmStyle = 'position:absolute;top:100%;left:0;width:' . $mmWidthPx . 'px;max-width:90vw;';
                    } else {
                        $mmStyle = 'position:absolute;top:100%;left:0;width:100vw;';
                    }

                    $mmBg = is_string($props['megaMenuBackgroundColor'] ?? null) ? trim((string) $props['megaMenuBackgroundColor']) : '#fff';
                    $mmStyle .= 'background:' . e($mmBg) . ';';

                    $mmBStyle = is_string($props['megaMenuBorderStyle'] ?? null) ? trim((string) $props['megaMenuBorderStyle']) : '';
                    $mmBColor = is_string($props['megaMenuBorderColor'] ?? null) ? trim((string) $props['megaMenuBorderColor']) : '#e5e7eb';
                    if ($mmBStyle !== '') {
                        $mmStyle .= 'border-style:' . e($mmBStyle) . ';border-color:' . e($mmBColor) . ';';
                        $mmBwTop = $this->intOrNull($props['megaMenuBorderWidthTop'] ?? null);
                        $mmBwRight = $this->intOrNull($props['megaMenuBorderWidthRight'] ?? null);
                        $mmBwBottom = $this->intOrNull($props['megaMenuBorderWidthBottom'] ?? null);
                        $mmBwLeft = $this->intOrNull($props['megaMenuBorderWidthLeft'] ?? null);
                        if ($mmBwTop !== null && $mmBwTop >= 0) $mmStyle .= 'border-top-width:' . $mmBwTop . 'px;';
                        if ($mmBwRight !== null && $mmBwRight >= 0) $mmStyle .= 'border-right-width:' . $mmBwRight . 'px;';
                        if ($mmBwBottom !== null && $mmBwBottom >= 0) $mmStyle .= 'border-bottom-width:' . $mmBwBottom . 'px;';
                        if ($mmBwLeft !== null && $mmBwLeft >= 0) $mmStyle .= 'border-left-width:' . $mmBwLeft . 'px;';
                    } else {
                        $mmStyle .= 'border:1px solid #e5e7eb;';
                    }

                    $mmBR = $this->intOrNull($props['megaMenuBorderRadius'] ?? null);
                    $mmBRTop = $this->intOrNull($props['megaMenuBorderRadiusTop'] ?? null) ?? $mmBR;
                    $mmBRRight = $this->intOrNull($props['megaMenuBorderRadiusRight'] ?? null) ?? $mmBR;
                    $mmBRBottom = $this->intOrNull($props['megaMenuBorderRadiusBottom'] ?? null) ?? $mmBR;
                    $mmBRLeft = $this->intOrNull($props['megaMenuBorderRadiusLeft'] ?? null) ?? $mmBR;
                    $mmStyle .= 'border-top-left-radius:' . ($mmBRTop !== null && $mmBRTop >= 0 ? $mmBRTop : 12) . 'px;';
                    $mmStyle .= 'border-top-right-radius:' . ($mmBRRight !== null && $mmBRRight >= 0 ? $mmBRRight : 12) . 'px;';
                    $mmStyle .= 'border-bottom-right-radius:' . ($mmBRBottom !== null && $mmBRBottom >= 0 ? $mmBRBottom : 12) . 'px;';
                    $mmStyle .= 'border-bottom-left-radius:' . ($mmBRLeft !== null && $mmBRLeft >= 0 ? $mmBRLeft : 12) . 'px;';

                    $mmPadTop = $this->intOrNull($props['megaMenuPaddingTop'] ?? null);
                    $mmPadRight = $this->intOrNull($props['megaMenuPaddingRight'] ?? null);
                    $mmPadBottom = $this->intOrNull($props['megaMenuPaddingBottom'] ?? null);
                    $mmPadLeft = $this->intOrNull($props['megaMenuPaddingLeft'] ?? null);
                    if ($mmPadTop !== null) $mmStyle .= 'padding-top:' . $mmPadTop . 'px;';
                    if ($mmPadRight !== null) $mmStyle .= 'padding-right:' . $mmPadRight . 'px;';
                    if ($mmPadBottom !== null) $mmStyle .= 'padding-bottom:' . $mmPadBottom . 'px;';
                    if ($mmPadLeft !== null) $mmStyle .= 'padding-left:' . $mmPadLeft . 'px;';
                    if ($mmPadTop === null && $mmPadRight === null && $mmPadBottom === null && $mmPadLeft === null) {
                        $mmStyle .= 'padding:24px;';
                    }

                    $mmMarTop = $this->intOrNull($props['megaMenuMarginTop'] ?? null);
                    $mmMarRight = $this->intOrNull($props['megaMenuMarginRight'] ?? null);
                    $mmMarBottom = $this->intOrNull($props['megaMenuMarginBottom'] ?? null);
                    $mmMarLeft = $this->intOrNull($props['megaMenuMarginLeft'] ?? null);
                    if ($mmMarTop !== null) $mmStyle .= 'margin-top:' . $mmMarTop . 'px;';
                    if ($mmMarRight !== null) $mmStyle .= 'margin-right:' . $mmMarRight . 'px;';
                    if ($mmMarBottom !== null) $mmStyle .= 'margin-bottom:' . $mmMarBottom . 'px;';
                    if ($mmMarLeft !== null) $mmStyle .= 'margin-left:' . $mmMarLeft . 'px;';

                    $mmStyle .= 'box-shadow:0 8px 24px rgba(0,0,0,0.1);z-index:50;';

                    $mmDataAttr = $mmWidthMode === 'full' ? ' data-fullwidth="1"' : '';
                    $html .= '<div class="mp-nav-megamenu"' . $mmDataAttr . ' style="' . $mmStyle . '">' . $megaMenuHtml . '</div>';
                }
            }

            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    private function renderMegaMenu(array $props): string
    {
        $triggerLabel = is_string($props['trigger_label'] ?? null) ? trim((string) $props['trigger_label']) : 'Menu';
        $triggerUrl = is_string($props['trigger_url'] ?? null) ? trim((string) $props['trigger_url']) : '#';
        $columns = is_array($props['columns'] ?? null) ? $props['columns'] : [];
        $panelWidth = is_string($props['panel_width'] ?? null) ? trim((string) $props['panel_width']) : 'boxed';

        $widthStyle = match ($panelWidth) {
            'full' => 'width:100vw;left:50%;transform:translateX(-50%);',
            'boxed' => 'width:800px;left:0;',
            default => 'width:auto;left:0;',
        };

        $html = '<div class="mp-mega-menu" style="position:relative;" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">';
        $html .= '<a href="' . e($triggerUrl) . '" class="mp-mega-trigger" style="text-decoration:none;">' . e($triggerLabel) . ' <svg style="display:inline-block;width:12px;height:12px;vertical-align:middle;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg></a>';
        $html .= '<div class="mp-mega-panel" x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-5" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-5" style="position:absolute;top:100%;' . $widthStyle . 'background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.1);z-index:50;padding:24px;display:none;" @click.away="open = false">';
        $html .= '<div style="display:grid;grid-template-columns:repeat(' . min(count($columns), 4) . ', 1fr);gap:24px;">';

        foreach ($columns as $col) {
            if (!is_array($col)) {
                continue;
            }
            $category = is_string($col['category'] ?? null) ? trim((string) $col['category']) : '';
            $colItems = is_array($col['items'] ?? null) ? $col['items'] : [];

            $html .= '<div class="mp-mega-column">';
            if ($category !== '') {
                $html .= '<div class="mp-mega-category" style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:#6b7280;margin-bottom:12px;">' . e($category) . '</div>';
            }
            $html .= '<div style="display:flex;flex-direction:column;gap:8px;">';
            foreach ($colItems as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $label = is_string($item['label'] ?? null) ? trim((string) $item['label']) : '';
                $url = is_string($item['url'] ?? null) ? trim((string) $item['url']) : '#';
                $desc = is_string($item['description'] ?? null) ? trim((string) $item['description']) : '';
                $icon = is_string($item['icon'] ?? null) ? trim((string) $item['icon']) : '';
                $image = is_string($item['image'] ?? null) ? trim((string) $item['image']) : '';

                if ($label === '') {
                    continue;
                }

                $html .= '<a href="' . e($url) . '" class="mp-mega-item" style="display:flex;align-items:flex-start;gap:12px;padding:10px;border-radius:8px;text-decoration:none;color:#111827;transition:background 0.15s;">';
                if ($image !== '') {
                    $html .= '<img src="' . e($image) . '" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px;flex-shrink:0;">';
                } elseif ($icon !== '') {
                    $html .= '<div style="width:40px;height:40px;border-radius:6px;background:rgba(59,130,246,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px;">' . $icon . '</div>';
                }
                $html .= '<div>';
                $html .= '<div style="font-weight:600;font-size:13px;line-height:1.3;">' . e($label) . '</div>';
                if ($desc !== '') {
                    $html .= '<div style="font-size:12px;color:#6b7280;margin-top:2px;line-height:1.3;">' . e($desc) . '</div>';
                }
                $html .= '</div>';
                $html .= '</a>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    private function renderSocialIcons(array $props, array $advanced = []): string
    {
        $icons = is_array($props['icons'] ?? null) ? $props['icons'] : [];
        $size = is_string($props['size'] ?? null) ? trim((string) $props['size']) : '20';
        $sizeInt = is_numeric($size) ? (int) $size : 20;
        $gap = is_string($props['gap'] ?? null) ? trim((string) $props['gap']) : '16';
        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'left';

        $advancedJustify = is_string($advanced['justifyContent'] ?? null) ? trim((string) $advanced['justifyContent']) : '';
        $justify = in_array($advancedJustify, ['flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'], true)
            ? $advancedJustify
            : match ($align) {
            'center' => 'center',
            'right' => 'flex-end',
            default => 'flex-start',
        };

        $scopeClass = 'mp-sci-' . substr(md5(json_encode($props)), 0, 8);
        $normalCss = $this->buildIconStyleCss($props, 'normal');
        $hoverCss = $this->buildIconStyleCss($props, 'hover');
        $containerSize = $sizeInt + 8;

        $html = '<div class="mp-social-icons ' . $scopeClass . '" style="display:flex;width:100%;max-width:100%;box-sizing:border-box;flex-wrap:wrap;align-items:center;justify-content:' . $justify . ';gap:' . e((string) $gap) . 'px;">';
        $html .= '<style>'
            . '.' . $scopeClass . ' .mp-social-icon-link{display:inline-flex;text-decoration:none;}'
            . '.' . $scopeClass . ' .mp-social-icon{display:inline-flex;align-items:center;justify-content:center;width:' . $containerSize . 'px;height:' . $containerSize . 'px;padding:4px;background-color:#f3f4f6;color:#4b5563;border-radius:9999px;}'
            . ($normalCss ? '.' . $scopeClass . ' .mp-social-icon{' . $normalCss . '}' : '')
            . ($hoverCss ? '.' . $scopeClass . ' .mp-social-icon:hover{' . $hoverCss . '}' : '')
            . '.' . $scopeClass . ' .mp-social-icon svg{width:' . e((string) $size) . 'px;height:' . e((string) $size) . 'px;display:block;fill:currentColor;}'
            . '</style>';

        foreach ($icons as $icon) {
            if (!is_array($icon)) {
                continue;
            }
            $svg = is_string($icon['svg'] ?? null) ? trim((string) $icon['svg']) : '';
            $url = is_string($icon['url'] ?? null) ? trim((string) $icon['url']) : '';
            $target = is_string($icon['target'] ?? null) ? trim((string) $icon['target']) : '_blank';
            if (!in_array($target, ['_self', '_parent', '_top'], true)) {
                $target = '_blank';
            }

            if ($svg === '' && $url === '') {
                continue;
            }

            $iconHtml = $svg !== '' ? $svg : '<svg width="' . e((string) $size) . '" height="' . e((string) $size) . '" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" /></svg>';
            $innerSpan = '<span class="mp-social-icon" aria-label="Social icon">' . $iconHtml . '</span>';

            if ($url !== '') {
                $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
                $html .= '<a class="mp-social-icon-link" href="' . e($url) . '" target="' . e($target) . '"' . $rel . ' style="text-decoration:none;">' . $innerSpan . '</a>';
            } else {
                $html .= $innerSpan;
            }
        }
        $html .= '</div>';

        return $html;
    }

    private function buildIconStyleCss(array $props, string $state): string
    {
        $prefix = $state === 'hover' ? 'iconHover' : 'icon';
        $css = [];

        $bgType = is_string($props[$prefix . 'BgType'] ?? null) ? trim((string) $props[$prefix . 'BgType']) : 'color';
        if ($bgType === 'gradient') {
            $from = $this->resolveGlobalColor($props[$prefix . 'BgGradientFrom'] ?? '');
            $to = $this->resolveGlobalColor($props[$prefix . 'BgGradientTo'] ?? '');
            if ($from !== '' && $to !== '') {
                $gradientType = is_string($props[$prefix . 'BgGradientType'] ?? null) ? trim((string) $props[$prefix . 'BgGradientType']) : 'linear';
                if ($gradientType === 'radial') {
                    $position = is_string($props[$prefix . 'BgGradientPosition'] ?? null) && trim((string) $props[$prefix . 'BgGradientPosition']) !== '' ? trim((string) $props[$prefix . 'BgGradientPosition']) : 'center';
                    $css[] = 'background-image:radial-gradient(circle at ' . e($position) . ', ' . e($from) . ', ' . e($to) . ')';
                } else {
                    $angle = is_numeric($props[$prefix . 'BgGradientAngle'] ?? null) ? (float) $props[$prefix . 'BgGradientAngle'] : 135;
                    $css[] = 'background-image:linear-gradient(' . $angle . 'deg, ' . e($from) . ', ' . e($to) . ')';
                }
            }
        } else {
            $bgColor = $this->resolveGlobalColor($props[$prefix . 'BgColor'] ?? '');
            if ($bgColor !== '') {
                $css[] = 'background-color:' . e($bgColor);
            }
        }

        $borderStyle = is_string($props[$prefix . 'BorderStyle'] ?? null) ? trim((string) $props[$prefix . 'BorderStyle']) : '';
        $borderColor = $this->resolveGlobalColor($props[$prefix . 'BorderColor'] ?? '');
        $borderWidth = is_numeric($props[$prefix . 'BorderWidth'] ?? null) ? (int) $props[$prefix . 'BorderWidth'] : 0;
        if ($borderStyle !== '' && $borderStyle !== 'none' && $borderColor !== '' && $borderWidth > 0) {
            $css[] = 'border-style:' . e($borderStyle);
            $css[] = 'border-color:' . e($borderColor);
            $css[] = 'border-width:' . $borderWidth . 'px';
        }

        $borderRadius = is_numeric($props[$prefix . 'BorderRadius'] ?? null) ? (int) $props[$prefix . 'BorderRadius'] : null;
        if ($borderRadius !== null && $borderRadius >= 0) {
            $css[] = 'border-radius:' . $borderRadius . 'px';
        }

        $iconColor = $this->resolveGlobalColor($props[$prefix . 'Color'] ?? '');
        if ($iconColor !== '') {
            $css[] = 'color:' . e($iconColor);
        }

        return implode(';', $css);
    }

    private function resolveGlobalColor($value): string
    {
        $v = is_string($value) ? trim((string) $value) : '';
        if ($v === '') {
            return '';
        }
        if (isset($this->globalColors[$v])) {
            return (string) $this->globalColors[$v];
        }
        return $v;
    }

    private function renderSocialProof(array $props): string
    {
        $avatars = is_array($props['avatars'] ?? null) ? $props['avatars'] : [];
        $rating = is_numeric($props['rating'] ?? null) ? (float) $props['rating'] : 5;
        $rating = max(0, min(5, $rating));
        $text = is_string($props['text'] ?? null) ? trim((string) $props['text']) : 'Trusted by 1000+ clients';
        $showAvatars = ($props['showAvatars'] ?? true) !== false;
        $showStars = ($props['showStars'] ?? true) !== false;
        $layout = ($props['layout'] ?? 'inline') === 'stacked' ? 'stacked' : 'inline';
        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'left';
        $align = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
        $avatarSize = is_numeric($props['avatarSize'] ?? null) ? (int) $props['avatarSize'] : 48;
        $avatarSize = max(20, min(96, $avatarSize));
        $starSize = is_numeric($props['starSize'] ?? null) ? (int) $props['starSize'] : 22;
        $starSize = max(10, min(48, $starSize));
        $textSize = is_numeric($props['textSize'] ?? null) ? (int) $props['textSize'] : 18;
        $textSize = max(10, min(48, $textSize));
        $starColor = is_string($props['starColor'] ?? null) && trim((string) $props['starColor']) !== '' ? trim((string) $props['starColor']) : '#F59E0B';
        $textColor = is_string($props['textColor'] ?? null) && trim((string) $props['textColor']) !== '' ? trim((string) $props['textColor']) : '#334155';

        $justify = match ($align) {
            'center' => 'center',
            'right' => 'flex-end',
            default => 'flex-start',
        };
        $crossAlign = match ($align) {
            'center' => 'center',
            'right' => 'flex-end',
            default => 'flex-start',
        };
        $overlap = (int) round($avatarSize * 0.32);

        // Avatar stack
        $avatarHtml = '';
        if ($showAvatars && count($avatars) > 0) {
            $avatarHtml = '<div style="display:flex;align-items:center;">';
            $idx = 0;
            $total = count($avatars);
            foreach ($avatars as $av) {
                $src = '';
                if (is_string($av)) {
                    $src = trim($av);
                } elseif (is_array($av)) {
                    $src = is_string($av['image'] ?? null) ? trim((string) $av['image']) : '';
                }
                $ml = $idx === 0 ? '0' : ('-' . $overlap . 'px');
                $z = $total - $idx;
                $bgImage = $src !== '' ? 'background-image:url(\'' . e($src) . '\');' : '';
                $label = $src === '' ? (string) ($idx + 1) : '';
                $avatarHtml .= '<div style="width:' . $avatarSize . 'px;height:' . $avatarSize . 'px;border-radius:9999px;border:2px solid #ffffff;box-shadow:0 1px 2px rgba(0,0,0,0.08);margin-left:' . $ml . ';background-color:#e2e8f0;' . $bgImage . 'background-size:cover;background-position:center;flex-shrink:0;z-index:' . $z . ';display:flex;align-items:center;justify-content:center;color:#64748b;font-size:' . (int) round($avatarSize * 0.4) . 'px;font-weight:600;">' . e($label) . '</div>';
                $idx++;
            }
            $avatarHtml .= '</div>';
        }

        // Stars
        $starsHtml = '';
        if ($showStars) {
            $starsHtml = '<div style="display:flex;align-items:center;gap:2px;">';
            $rounded = (int) round($rating);
            for ($i = 0; $i < 5; $i++) {
                $fill = $i < $rounded ? e($starColor) : 'none';
                $starsHtml .= '<svg width="' . $starSize . '" height="' . $starSize . '" viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . e($starColor) . '" stroke-width="1.5" style="flex-shrink:0;"><path d="M12 2.5l2.9 5.88 6.49.94-4.7 4.58 1.11 6.46L12 17.3l-5.8 3.06 1.11-6.46-4.7-4.58 6.49-.94z"/></svg>';
            }
            $starsHtml .= '</div>';
        }

        $textHtml = $text !== '' ? '<span style="color:' . e($textColor) . ';font-size:' . $textSize . 'px;font-weight:600;">' . e($text) . '</span>' : '';

        $ratingBlock = '<div style="display:flex;flex-direction:column;gap:4px;align-items:' . $crossAlign . ';">' . $starsHtml . $textHtml . '</div>';

        $flexDir = $layout === 'stacked' ? 'column' : 'row';
        $itemsAlign = $layout === 'stacked' ? $crossAlign : 'center';

        $gap = $layout === 'stacked' ? '8px' : '16px';
        $wrap = $layout === 'stacked' ? 'nowrap' : 'wrap';

        return '<div class="mp-social-proof" style="display:flex;flex-direction:' . $flexDir . ';align-items:' . $itemsAlign . ';justify-content:' . $justify . ';gap:' . $gap . ';flex-wrap:' . $wrap . ';">' . $avatarHtml . $ratingBlock . '</div>';
    }

    private function renderNewsletterForm(array $props): string
    {
        $buttonText = is_string($props['button_text'] ?? null) ? trim((string) $props['button_text'])
            : (is_string($props['buttonText'] ?? null) ? trim((string) $props['buttonText']) : 'Subscribe');
        $placeholder = is_string($props['placeholder'] ?? null) ? trim((string) $props['placeholder']) : 'Enter your email';
        $successMessage = is_string($props['success_message'] ?? null) ? trim((string) $props['success_message'])
            : (is_string($props['successMessage'] ?? null) ? trim((string) $props['successMessage']) : 'Thanks for subscribing!');
        $listId = is_string($props['list_id'] ?? null) ? trim((string) $props['list_id'])
            : (is_string($props['listId'] ?? null) ? trim((string) $props['listId']) : '');

        $action = '#';
        try {
            if (\Illuminate\Support\Facades\Route::has('public.subscribe.store')) {
                $action = route('public.subscribe.store', ['slug' => 'default']);
                if ($listId !== '' && is_numeric($listId)) {
                    $action .= (str_contains($action, '?') ? '&' : '?') . 'list_id=' . (int) $listId;
                }
            }
        } catch (\Throwable $e) {
            $action = '#';
        }

        $html = '<form class="mp-newsletter-form" action="' . e($action) . '" method="POST" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">';
        $html .= csrf_field();
        $html .= '<input type="email" name="email" required placeholder="' . e($placeholder) . '" class="mp-newsletter-input" style="flex:1 1 200px;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;outline:none;min-width:0;">';
        $html .= '<button type="submit" class="mp-newsletter-button" style="padding:10px 18px;background:#1E5FEA;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;white-space:nowrap;">' . e($buttonText) . '</button>';
        $html .= '</form>';

        return $html;
    }

    private function renderEmailDivider(array $props): string
    {
        $color = is_string($props['backgroundColor'] ?? null) && trim((string) $props['backgroundColor']) !== ''
            ? trim((string) $props['backgroundColor'])
            : (is_string($props['borderColor'] ?? null) && trim((string) $props['borderColor']) !== '' ? trim((string) $props['borderColor']) : '#e5e7eb');
        $height = $this->cssLength($props['height'] ?? null, '1px');
        $width = $this->cssLength($props['width'] ?? null, '100%');

        $barStyle = 'display:inline-block;vertical-align:middle;height:' . e($height) . ';width:' . e($width) . ';max-width:100%;background-color:' . e($color) . ';';

        return '<div role="separator" style="display:block;width:100%;max-width:100%;text-align:center;line-height:0;"><div style="' . $barStyle . '"></div></div>';
    }

    private function renderEmailSpacer(array $props): string
    {
        $height = is_string($props['height'] ?? null) ? trim((string) $props['height']) : '24px';

        return '<div aria-hidden="true" style="height:' . e($height) . ';line-height:' . e($height) . ';font-size:1px;">&nbsp;</div>';
    }

    private function renderEmailLinks(array $props, array $advanced = []): string
    {
        $items = is_array($props['items'] ?? null) ? $props['items'] : [];
        if (empty($items)) {
            return '';
        }

        $gap = $this->intOrNull($props['gap'] ?? null);
        $gap = ($gap !== null && $gap >= 0) ? $gap : 12;
        $separator = is_string($props['separator'] ?? null) ? $props['separator'] : '·';
        $align = is_string($props['align'] ?? null) && in_array($props['align'], ['left', 'center', 'right'], true) ? $props['align'] : 'left';
        $advancedJustify = is_string($advanced['justifyContent'] ?? null) ? trim((string) $advanced['justifyContent']) : '';
        $align = match ($advancedJustify) {
            'center' => 'center',
            'flex-end', 'end', 'right' => 'right',
            default => in_array($advancedJustify, ['flex-start', 'start', 'left'], true) ? 'left' : $align,
        };
        $color = is_string($props['color'] ?? null) && trim((string) $props['color']) !== '' ? trim((string) $props['color']) : '#2563eb';
        $hoverColor = is_string($props['hoverColor'] ?? null) ? trim((string) $props['hoverColor']) : '';

        $scopeClass = 'mp-links-' . substr(md5(json_encode($props)), 0, 8);

        $justify = match ($align) {
            'center' => 'center',
            'right' => 'flex-end',
            default => 'flex-start',
        };

        $linkStyle = 'text-decoration:none;color:' . e($color) . ';line-height:1.4;';
        if (is_string($props['fontFamily'] ?? null) && trim((string) $props['fontFamily']) !== '') {
            $linkStyle .= 'font-family:' . e(trim((string) $props['fontFamily'])) . ';';
        }
        if (($props['fontSize'] ?? null) !== null && $props['fontSize'] !== '') {
            $linkStyle .= 'font-size:' . e((string) $props['fontSize']) . ';';
        }
        if (is_string($props['fontWeight'] ?? null) && trim((string) $props['fontWeight']) !== '') {
            $linkStyle .= 'font-weight:' . e(trim((string) $props['fontWeight'])) . ';';
        }
        if (($props['lineHeight'] ?? null) !== null && $props['lineHeight'] !== '') {
            $linkStyle .= 'line-height:' . e((string) $props['lineHeight']) . ';';
        }
        if (($props['letterSpacing'] ?? null) !== null && $props['letterSpacing'] !== '') {
            $linkStyle .= 'letter-spacing:' . e((string) $props['letterSpacing']) . ';';
        }

        $sepStyle = 'opacity:0.7;color:' . e($color) . ';';

        $links = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = is_string($item['label'] ?? null) ? trim((string) $item['label']) : '';
            $url = is_string($item['url'] ?? null) ? trim((string) $item['url']) : '';
            if ($label === '' && $url === '') {
                continue;
            }
            $target = ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self';
            $rel = $target === '_blank' ? ' rel="noopener noreferrer"' : '';
            $linkLabel = $label !== '' ? $label : $url;
            $links[] = '<a class="mp-link" href="' . e($url !== '' ? $url : '#') . '" target="' . e($target) . '"' . $rel . ' data-text="' . e($linkLabel) . '" style="' . $linkStyle . '"><span class="mp-link-text">' . e($linkLabel) . '</span></a>';
        }

        if (empty($links)) {
            return '';
        }

        $isVertical = is_string($props['direction'] ?? null) && $props['direction'] === 'vertical';
        $inner = (!$isVertical && $separator !== '')
            ? implode('<span style="' . $sepStyle . '">' . e($separator) . '</span>', $links)
            : implode('', $links);

        if ($isVertical) {
            $crossAlign = match ($align) {
                'center' => 'center',
                'right' => 'flex-end',
                default => 'flex-start',
            };
            $flexStyle = 'flex-direction:column;flex-wrap:nowrap;align-items:' . e($crossAlign) . ';justify-content:flex-start;';
        } else {
            $flexStyle = 'flex-wrap:wrap;align-items:center;justify-content:' . e($justify) . ';';
        }

        $html = '<div class="mp-links ' . $scopeClass . '" style="display:flex;' . $flexStyle . 'gap:' . e((string) $gap) . 'px;width:100%;max-width:100%;box-sizing:border-box;">';
        if ($hoverColor !== '') {
            $html .= '<style>.' . $scopeClass . ' .mp-link:hover{color:' . e($hoverColor) . ' !important;}</style>';
        }
        $hoverEffect = $this->sanitizeHoverEffect($props['hoverEffect'] ?? null);
        if ($hoverEffect !== '') {
            $effectCss = $this->hoverEffectCss($hoverEffect, '.' . $scopeClass, '.mp-link');
            if ($effectCss !== '') {
                $html .= '<style>' . $effectCss . '</style>';
            }
            if ($hoverEffect === 'magnetic') {
                $html .= $this->hoverMagneticScript($scopeClass);
            }
        }
        $html .= $inner . '</div>';

        return $html;
    }

    private function sanitizeHoverEffect(mixed $value): string
    {
        $allowed = ['magnetic', 'slide', 'text-lift', 'arrow-shift', 'blur-focus', 'elastic', 'gradient-shift', 'text-flip'];

        return is_string($value) && in_array($value, $allowed, true) ? $value : '';
    }

    private function hoverEffectCss(string $effect, string $containerSel, string $linkClass): string
    {
        $underline = 'content:"";position:absolute;left:0;bottom:-3px;height:2px;width:100%;background:currentColor;border-radius:2px;';
        $linkSel = $containerSel . ' ' . $linkClass;

        return match ($effect) {
            'magnetic' => $linkSel . '{position:relative;}' . $linkSel . '::after{' . $underline . 'transform:scaleX(0);transform-origin:var(--mp-mag-origin,50%);transition:transform .35s cubic-bezier(.22,1,.36,1);}' . $linkSel . ':hover::after{transform:scaleX(1);}',
            'slide' => $linkSel . '{position:relative;}' . $linkSel . '::after{' . $underline . 'transform:scaleX(0);transform-origin:right center;transition:transform .3s ease;}' . $linkSel . ':hover::after{transform:scaleX(1);transform-origin:left center;}',
            'text-lift' => $linkSel . '{transition:transform .3s cubic-bezier(.22,1,.36,1);}' . $linkSel . ':hover{transform:translateY(-3px);}',
            'arrow-shift' => $linkSel . '::after{content:"\2192";display:inline-block;vertical-align:middle;align-self:center;line-height:1;max-width:0;margin-left:0;overflow:hidden;opacity:0;transform:translateX(-4px);transition:max-width .3s cubic-bezier(.22,1,.36,1),margin-left .3s cubic-bezier(.22,1,.36,1),opacity .3s ease,transform .3s cubic-bezier(.22,1,.36,1);}' . $linkSel . ':hover::after{max-width:2em;margin-left:6px;opacity:1;transform:translateX(0);}',
            'blur-focus' => $linkSel . '{transition:filter .3s ease,opacity .3s ease;}' . $containerSel . ':hover ' . $linkClass . '{filter:blur(1.5px);opacity:.55;}' . $linkSel . ':hover{filter:none;opacity:1;}',
            'elastic' => $linkSel . '{position:relative;}' . $linkSel . '::after{' . $underline . 'transform:scaleX(0);transform-origin:left center;transition:transform .6s cubic-bezier(.34,1.56,.64,1);}' . $linkSel . ':hover::after{transform:scaleX(1);}',
            'gradient-shift' => $linkSel . '{background-image:linear-gradient(90deg,currentColor 0%,currentColor 45%,#6366f1 70%,#a855f7 100%);background-size:200% 100%;background-position:0% 0;-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;transition:background-position .5s ease;}' . $linkSel . ':hover{background-position:100% 0;}',
            'text-flip' => $linkSel . '{position:relative;overflow:hidden;}' . $linkSel . ' .mp-link-text{display:block;transition:transform .4s cubic-bezier(.22,1,.36,1);}' . $linkSel . '::after{content:attr(data-text);position:absolute;left:0;top:0;width:100%;transform:translateY(-100%);transition:transform .4s cubic-bezier(.22,1,.36,1);}' . $linkSel . ':hover .mp-link-text{transform:translateY(100%);}' . $linkSel . ':hover::after{transform:translateY(0);}',
            default => '',
        };
    }

    private function hoverMagneticScript(string $scopeClass): string
    {
        $sel = '.' . $scopeClass . ' .mp-link,.' . $scopeClass . ' .mp-nav-link';

        return '<script>(function(){var els=document.querySelectorAll("' . $sel . '");if(!els.length)return;els.forEach(function(a){a.addEventListener("mousemove",function(e){var b=a.getBoundingClientRect();if(!b.width)return;var x=((e.clientX-b.left)/b.width)*100;a.style.setProperty("--mp-mag-origin",Math.max(0,Math.min(100,x))+"%");});});})();</script>';
    }

    private function renderEmailLogo(array $props): string
    {
        $width = $this->cssLength($props['width'] ?? null, '180px');
        $align = is_string($props['align'] ?? null) && in_array($props['align'], ['left', 'center', 'right'], true) ? $props['align'] : 'left';
        $alt = is_string($props['alt'] ?? null) ? (string) $props['alt'] : '';

        $svg = is_string($props['svg'] ?? null) ? trim((string) $props['svg']) : '';
        $url = is_string($props['url'] ?? null) ? trim((string) $props['url']) : '';
        $source = ((string) ($props['source'] ?? '')) === 'svg' ? 'svg' : 'url';

        if ($svg !== '' && ($source === 'svg' || $url === '')) {
            $svg = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is', '', $svg) ?? $svg;
            $svg = preg_replace('/<\s*script\b[^>]*>/i', '', $svg) ?? $svg;
            $svg = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg) ?? $svg;
            $svg = preg_replace('/javascript\s*:/i', '', $svg) ?? $svg;

            $logoStyle = 'display:inline-block;max-width:100%;width:' . e($width) . ';height:auto;';
            if (preg_match('/<svg\b[^>]*\bstyle\s*=\s*(["\'])/i', $svg)) {
                $svg = preg_replace('/(<svg\b[^>]*\bstyle\s*=\s*)(["\'])(.*?)\2/i', '$1$2$3;' . $logoStyle . '$2', $svg, 1) ?? $svg;
            } else {
                $svg = preg_replace('/<svg\b/i', '<svg style="' . $logoStyle . '"', $svg, 1) ?? $svg;
            }

            return '<div style="text-align:' . e($align) . ';">' . $svg . '</div>';
        }

        if ($url === '') {
            return '';
        }

        return '<div style="text-align:' . e($align) . ';"><img src="' . e($url) . '" alt="' . e($alt) . '" width="' . e($width) . '" style="display:inline-block;width:' . e($width) . ';max-width:100%;height:auto;border:0;"></div>';
    }

    private function renderEmailRating(array $props): string
    {
        $max = $this->intOrNull($props['max'] ?? null) ?? 5;
        $max = max(1, min(10, $max));

        $rating = is_numeric($props['rating'] ?? null) ? (float) $props['rating'] : 4.5;
        $rating = max(0, min((float) $max, $rating));

        $size = $this->intOrNull($props['starSize'] ?? null) ?? 20;
        $size = max(8, min(64, $size));
        $gap = $this->intOrNull($props['gap'] ?? null) ?? 4;
        $gap = max(0, min(24, $gap));

        $starColor = is_string($props['starColor'] ?? null) && trim((string) $props['starColor']) !== '' ? trim((string) $props['starColor']) : '#F59E0B';
        $emptyColor = is_string($props['emptyColor'] ?? null) && trim((string) $props['emptyColor']) !== '' ? trim((string) $props['emptyColor']) : '#E5E7EB';
        $align = is_string($props['align'] ?? null) && in_array($props['align'], ['left', 'center', 'right'], true) ? $props['align'] : 'left';
        $showValue = ($props['showValue'] ?? null) === true;
        $textSize = $this->intOrNull($props['textSize'] ?? null) ?? 14;
        $textSize = max(10, min(48, $textSize));
        $textColor = is_string($props['textColor'] ?? null) && trim((string) $props['textColor']) !== '' ? trim((string) $props['textColor']) : '#334155';

        $path = 'M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z';
        $starSvg = static function (string $color) use ($size, $path): string {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="' . e($color) . '" style="display:block;"><path d="' . $path . '"/></svg>';
        };

        $stars = '';
        for ($i = 0; $i < $max; $i++) {
            $fill = max(0, min(1, $rating - $i));
            $star = '<span style="position:relative;display:inline-block;width:' . $size . 'px;height:' . $size . 'px;line-height:0;margin-right:' . ($i === $max - 1 ? 0 : $gap) . 'px;">';
            $star .= $starSvg($emptyColor);
            if ($fill > 0) {
                $star .= '<span style="position:absolute;left:0;top:0;height:' . $size . 'px;width:' . round($fill * 100, 2) . '%;overflow:hidden;display:block;line-height:0;">';
                $star .= $starSvg($starColor);
                $star .= '</span>';
            }
            $star .= '</span>';
            $stars .= $star;
        }

        $valueHtml = '';
        if ($showValue) {
            $display = fmod($rating, 1.0) === 0.0 ? number_format($rating, 0) : number_format($rating, 1);
            $valueHtml = '<span style="font-size:' . $textSize . 'px;line-height:1.2;color:' . e($textColor) . ';font-weight:600;display:inline-block;">'
                . e($display) . '/' . $max . '</span>';
        }

        $justify = $align === 'right' ? 'flex-end' : ($align === 'center' ? 'center' : 'flex-start');

        return '<div class="mp-rating" style="display:flex;align-items:center;justify-content:' . $justify . ';gap:' . $gap . 'px;flex-wrap:wrap;line-height:0;">'
            . '<span style="display:inline-flex;align-items:center;">' . $stars . '</span>'
            . $valueHtml
            . '</div>';
    }

    private function renderCopyright(array $props): string
    {
        $text = is_string($props['text'] ?? null) ? trim((string) $props['text']) : '';
        if ($text === '') {
            $text = '&copy; {{year}} {{site_name}}';
        }

        $year = (string) date('Y');
        $siteName = config('app.name', 'MailPurse');

        $text = str_replace(['{{year}}', '{{site_name}}'], [$year, e($siteName)], $text);

        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'center';
        $allowedAligns = ['left', 'center', 'right'];
        $align = in_array($align, $allowedAligns, true) ? $align : 'center';

        $color = $this->sanitizeColor($props['color'] ?? null) ?? '#6b7280';
        $fontSize = $this->sanitizeIntPx($props['fontSize'] ?? null, 8, 80);
        $fontFamily = $this->sanitizeFontFamily($props['fontFamily'] ?? null);
        $fontWeight = $this->sanitizeFontWeight($props['fontWeight'] ?? null);
        $lineHeight = $this->sanitizeIntPx($props['lineHeight'] ?? null, 8, 140);
        $letterSpacing = $this->sanitizeIntPx($props['letterSpacing'] ?? null, -10, 40);
        $wordSpacing = $this->sanitizeIntPx($props['wordSpacing'] ?? null, -10, 40);

        $style = 'text-align:' . $align . ';margin:0;color:' . $color . ';';
        $style .= 'font-size:' . (string) ($fontSize ?? 13) . 'px;';
        if ($fontFamily !== null) {
            $style .= 'font-family:' . $fontFamily . ';';
        }
        if ($fontWeight !== null) {
            $style .= 'font-weight:' . $fontWeight . ';';
        }
        if ($lineHeight !== null) {
            $style .= 'line-height:' . (string) $lineHeight . 'px;';
        }
        if ($letterSpacing !== null) {
            $style .= 'letter-spacing:' . (string) $letterSpacing . 'px;';
        }
        if ($wordSpacing !== null) {
            $style .= 'word-spacing:' . (string) $wordSpacing . 'px;';
        }

        $padTop = $this->intOrNull($props['paddingTop'] ?? null) ?? $this->intOrNull($props['paddingY'] ?? null) ?? 0;
        $padRight = $this->intOrNull($props['paddingRight'] ?? null) ?? $this->intOrNull($props['paddingX'] ?? null) ?? 0;
        $padBottom = $this->intOrNull($props['paddingBottom'] ?? null) ?? $this->intOrNull($props['paddingY'] ?? null) ?? 0;
        $padLeft = $this->intOrNull($props['paddingLeft'] ?? null) ?? $this->intOrNull($props['paddingX'] ?? null) ?? 0;
        if ($padTop !== 0 || $padRight !== 0 || $padBottom !== 0 || $padLeft !== 0) {
            $style .= 'padding:' . $padTop . 'px ' . $padRight . 'px ' . $padBottom . 'px ' . $padLeft . 'px;';
        }

        foreach (['Top' => 'top', 'Right' => 'right', 'Bottom' => 'bottom', 'Left' => 'left'] as $mKey => $mSide) {
            $m = $this->intOrNull($props['margin' . $mKey] ?? null);
            if ($m !== null) {
                $style .= 'margin-' . $mSide . ':' . $m . 'px;';
            }
        }

        $bgType = ($props['backgroundType'] ?? null) === 'gradient' ? 'gradient' : 'color';
        if ($bgType === 'gradient') {
            $from = $this->sanitizeColor($props['gradientFrom'] ?? null) ?? '#111827';
            $to = $this->sanitizeColor($props['gradientTo'] ?? null) ?? '#3b82f6';
            $angle = $this->intOrNull($props['gradientAngle'] ?? null) ?? 135;
            $style .= 'background-image:linear-gradient(' . $angle . 'deg,' . $from . ',' . $to . ');';
        } elseif (is_string($props['backgroundColor'] ?? null) && trim((string) $props['backgroundColor']) !== '') {
            $bg = $this->sanitizeColor($props['backgroundColor']);
            if ($bg !== null) {
                $style .= 'background-color:' . $bg . ';';
            }
        }

        return '<p class="mp-copyright" style="' . e($style) . '">' . $text . '</p>';
    }

    private function sanitizeCustomSvg(string $svg): string
    {
        $svg = preg_replace('#<script\b[^<]*(?:(?!</script>)<[^<]*)*</script>#mi', '', $svg);
        $svg = preg_replace('#\s+on\w+\s*=\s*"[^"]*"#i', '', $svg);
        $svg = preg_replace("#\s+on\w+\s*=\s*'[^']*'#i", '', $svg);
        return trim($svg);
    }

    private function renderPasswordInputHtml(string $name, string $style, string $placeholder, bool $required = true): string
    {
        $reqAttr = $required ? ' required' : '';
        $eyeOpen = '<svg class="mp-eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        $eyeClosed = '<svg class="mp-eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
        return '<div class="mp-password-wrap" style="position:relative;width:100%;">'
            . '<input type="password" name="' . e($name) . '"' . $reqAttr . ' placeholder="' . e($placeholder) . '" style="' . e($style) . '">'
            . '<button type="button" class="mp-password-toggle" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:transparent;border:none;padding:4px;cursor:pointer;color:#6b7280;display:flex;align-items:center;justify-content:center;" aria-label="Toggle password visibility" onclick="const i=this.parentNode.querySelector(\'input\'); i.type=i.type===\'password\'?\'text\':\'password\'; const o=this.querySelector(\'.mp-eye-open\'); const c=this.querySelector(\'.mp-eye-closed\'); if(o) o.style.display=i.type===\'password\'?\'block\':\'none\'; if(c) c.style.display=i.type===\'text\'?\'block\':\'none\';">'
            . $eyeOpen . $eyeClosed . '</button></div>';
    }

    private function renderAuthButtonIcon(array $props): string
    {
        $type = is_string($props['buttonIconType'] ?? null) ? trim((string) $props['buttonIconType']) : 'none';
        if ($type === 'none' || $type === '') {
            return '';
        }

        if ($type === 'custom') {
            $svgRaw = is_string($props['buttonIconSvg'] ?? null) ? trim((string) $props['buttonIconSvg']) : '';
            if ($svgRaw === '') {
                return '';
            }
            $svg = $this->sanitizeCustomSvg($svgRaw);
            if ($svg === '') {
                return '';
            }
            if (str_starts_with($svg, '<svg')) {
                return '<span style="display:inline-flex;flex-shrink:0;">' . $svg . '</span>';
            }
            return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="' . e($svg) . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>';
        }

        $name = is_string($props['buttonIcon'] ?? null) ? trim((string) $props['buttonIcon']) : '';
        $path = match ($name) {
            'arrow-right' => 'M13 7l5 5m0 0l-5 5m5-5H6',
            'login' => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
            'user' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            'check' => 'M5 13l4 4L19 7',
            'plus' => 'M12 4v16m8-8H4',
            'register' => 'M12 5v14m-7-7h14',
            default => '',
        };
        if ($path === '') {
            return '';
        }
        return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="' . e($path) . '" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path></svg>';
    }

    private function renderAiPromptInput(array $props): string
    {
        $placeholder = is_string($props['placeholder'] ?? null) && trim((string) $props['placeholder']) !== ''
            ? trim((string) $props['placeholder']) : __('What would you like to build?');
        $showFileUpload = ! array_key_exists('showFileUpload', $props) || (bool) $props['showFileUpload'];
        $showDiscuss = ! array_key_exists('showDiscuss', $props) || (bool) $props['showDiscuss'];
        $discussText = is_string($props['discussText'] ?? null) && trim((string) $props['discussText']) !== ''
            ? trim((string) $props['discussText']) : __('Discuss');
        $showVoice = ! array_key_exists('showVoice', $props) || (bool) $props['showVoice'];
        $maxWidth = is_numeric($props['maxWidth'] ?? null) ? (int) $props['maxWidth'] : 640;
        $borderRadius = is_numeric($props['borderRadius'] ?? null) ? (int) $props['borderRadius'] : 16;

        $backgroundColor = $this->sanitizeColor($props['backgroundColor'] ?? null) ?? '#ffffff';
        $borderColor = $this->sanitizeColor($props['borderColor'] ?? null) ?? '#e5e7eb';
        $textColor = $this->sanitizeColor($props['textColor'] ?? null) ?? '#1f2937';
        $placeholderColor = $this->sanitizeColor($props['placeholderColor'] ?? null) ?? '#9ca3af';

        $isCustomer = false;
        $isAdmin = false;
        try {
            $isCustomer = \Illuminate\Support\Facades\Auth::guard('customer')->check();
            $isAdmin = \Illuminate\Support\Facades\Auth::guard('admin')->check()
                || \Illuminate\Support\Facades\Auth::guard('web')->check();
        } catch (\Throwable $e) {
            $isCustomer = false;
            $isAdmin = false;
        }
        $loggedIn = $isCustomer || $isAdmin;

        $loginUrl = is_string($props['loginUrl'] ?? null) && trim((string) $props['loginUrl']) !== ''
            ? trim((string) $props['loginUrl']) : '';
        if ($loginUrl === '') {
            try {
                $loginUrl = \Illuminate\Support\Facades\Route::has('login') ? route('login') : '#';
            } catch (\Throwable $e) {
                $loginUrl = '#';
            }
        }

        $registerUrl = '#';
        try {
            $registerUrl = \Illuminate\Support\Facades\Route::has('register') ? route('register') : '#';
        } catch (\Throwable $e) {
            $registerUrl = '#';
        }

        $submitUrl = is_string($props['submitUrl'] ?? null) && trim((string) $props['submitUrl']) !== ''
            ? trim((string) $props['submitUrl']) : '';
        if ($submitUrl === '') {
            try {
                if ($isAdmin && \Illuminate\Support\Facades\Route::has('admin.ai-projects.page-ai-builder')) {
                    $submitUrl = route('admin.ai-projects.page-ai-builder');
                } elseif (\Illuminate\Support\Facades\Route::has('customer.ai-projects.builder')) {
                    $submitUrl = route('customer.ai-projects.builder');
                } else {
                    $submitUrl = '';
                }
            } catch (\Throwable $e) {
                $submitUrl = '';
            }
        }

        $id = 'mp-ai-prompt-' . \Illuminate\Support\Str::random(8);

        $autoIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 16h5v5"/><circle cx="12" cy="12" r="1"/></svg>';
        $autoIconJs = addcslashes($autoIcon, '"\\');
        $backIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>';
        $chevronIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>';
        $searchIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);color:#9ca3af;pointer-events:none;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>';
        $backIconJs = addcslashes($backIcon, '"\\');
        $chevronIconJs = addcslashes($chevronIcon, '"\\');

        $modelDropdownHtml = '';
        $modelJs = '';
        if ($loggedIn) {
            $modelsJson = $this->getAvailableModelsJson();
            $modelDropdownHtml = '<div class="mp-ai-prompt-input__model-wrap" style="position:relative;">'
                . '<button type="button" class="mp-ai-prompt-input__model-btn" style="height:28px;padding:0 8px;display:inline-flex;align-items:center;gap:6px;border-radius:8px;border:0;background:transparent;font-size:12px;font-weight:500;color:#6b7280;cursor:pointer;">'
                . $autoIcon
                . '<span class="mp-ai-prompt-input__model-label">' . e(__('Auto')) . '</span>'
                . '</button>'
                . '<div class="mp-ai-prompt-input__model-dropdown" style="display:none;position:absolute;top:100%;left:0;margin-top:8px;width:224px;max-height:360px;overflow:hidden;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.12);padding:8px;z-index:30;">'
                . '<div style="font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.05em;padding:4px 8px 6px;">' . e(__('Model')) . '</div>'
                . '<div style="position:relative;margin-bottom:8px;">'
                . $searchIcon
                . '<input type="text" class="mp-ai-prompt-input__model-search" placeholder="' . e(__('Search models...')) . '" style="width:100%;border:1px solid #e5e7eb;background:#f9fafb;border-radius:8px;padding:6px 8px 6px 26px;font-size:12px;color:#374151;outline:none;" />'
                . '</div>'
                . '<div class="mp-ai-prompt-input__model-list" style="max-height:280px;overflow:auto;"></div>'
                . '</div>'
                . '</div>';
            $modelJs = 'var modelWrap=root.querySelector(".mp-ai-prompt-input__model-wrap");'
                . 'var modelBtn=root.querySelector(".mp-ai-prompt-input__model-btn");'
                . 'var modelLabel=root.querySelector(".mp-ai-prompt-input__model-label");'
                . 'var modelDropdown=root.querySelector(".mp-ai-prompt-input__model-dropdown");'
                . 'var modelList=root.querySelector(".mp-ai-prompt-input__model-list");'
                . 'var modelSearchInput=root.querySelector(".mp-ai-prompt-input__model-search");'
                . 'var selectedModel="auto";'
                . 'var modelSearch="";'
                . 'var orView=false;'
                . 'var modelsData=' . $modelsJson . ';'
                . 'function getModelLogo(m){var v=String(m).toLowerCase();var p=v.split("/")[0];var n=v.includes("/")?v.split("/").slice(1).join("/"):v;var fm=[{m:/gpt-/,f:"openai"},{m:/^o\d/,f:"openai"},{m:/chatgpt/,f:"openai"},{m:/claude/,f:"anthropic"},{m:/gemini/,f:"google"},{m:/gemma/,f:"google"},{m:/grok/,f:"xai"},{m:/llama/,f:"meta"},{m:/deepseek/,f:"deepseek"},{m:/kimi/,f:"kimi"},{m:/moonshot/,f:"kimi"},{m:/moonlight/,f:"kimi"},{m:/glm/,f:"zai"},{m:/mimo/,f:"xiaomi"},{m:/mistral/,f:"mistral"},{m:/codestral/,f:"mistral"},{m:/minimax/,f:"minimax"},{m:/qwen/,f:"qwen"},{m:/qwq/,f:"qwen"},{m:/nemotron/,f:"nvidia"},{m:/phi-/,f:"microsoft"},{m:/command-r/,f:"cohere"},{m:/sonar/,f:"perplexity"},{m:/hermes/,f:"nousresearch"},{m:/yi-large/,f:"01-ai"},{m:/ling-/,f:"inclusionai"},{m:/^auto/,f:"openrouter"},{m:/^free/,f:"openrouter"}];var fam=fm.find(function(x){return x.m.test(n);});var pm={anthropic:"anthropic",google:"google",deepseek:"deepseek","meta-llama":"meta","x-ai":"xai",openai:"openai",chatgpt:"openai",moonshot:"kimi",moonshotai:"kimi",zhipu:"zai",zhipuai:"zai",glm:"zai",xiaomi:"xiaomi",minimax:"minimax",mistralai:"mistral",qwen:"qwen",nvidia:"nvidia",microsoft:"microsoft",cohere:"cohere",perplexity:"perplexity",nousresearch:"nousresearch","01-ai":"01-ai",inclusionai:"inclusionai",openrouter:"openrouter"};var f=fam?fam.f:(pm[p]||p);return "/images/model-logos/"+f+".png";}'
                . 'function modelItemHtml(m){var isSel=selectedModel===m.value;return "<button type=\\"button\\" data-model=\\""+m.value+"\\" style=\\"width:100%;text-align:left;padding:6px 8px;border:0;border-radius:8px;font-size:12px;display:flex;align-items:center;gap:8px;cursor:pointer;background:"+(isSel?"#f3f4f6":"transparent")+";color:"+(isSel?"#111827":"#6b7280")+";font-weight:"+(isSel?"500":"normal")+"\\"><img src=\\""+getModelLogo(m.value)+"\\" alt=\\"\\" style=\\"width:16px;height:16px;border-radius:4px;object-fit:contain;flex-shrink:0;\\" onerror=\\"this.style.display=\\x27none\\x27\\"/><span style=\\"overflow:hidden;text-overflow:ellipsis;white-space:nowrap;\\">"+m.label+"</span></button>";}'
                . 'function modelMatches(m){return modelSearch===""||m.label.toLowerCase().indexOf(modelSearch.toLowerCase())>-1;}'
                . 'function renderModelList(){var h="";'
                . 'h+="<button type=\\"button\\" data-model=\\"auto\\" style=\\"width:100%;text-align:left;padding:6px 8px;border:0;border-radius:8px;font-size:12px;display:flex;align-items:center;gap:8px;cursor:pointer;background:"+(selectedModel==="auto"?"#f3f4f6":"transparent")+";color:"+(selectedModel==="auto"?"#111827":"#6b7280")+";font-weight:"+(selectedModel==="auto"?"500":"normal")+"\\">' . $autoIconJs . ' ' . e(__('Auto (recommended)')) . '</button>";'
                . 'if(orView){'
                . 'h+="<button type=\\"button\\" data-or-back style=\\"width:100%;text-align:left;padding:6px 8px;border:0;border-radius:8px;font-size:12px;font-weight:500;color:#6b7280;display:flex;align-items:center;gap:6px;cursor:pointer;background:transparent;margin-bottom:4px;\\">' . $backIconJs . ' ' . e(__('Back')) . '</button>";'
                . 'h+="<div style=\\"display:flex;align-items:center;gap:6px;padding:4px 8px 6px;\\"><img src=\\"/images/model-logos/openrouter.png\\" alt=\\"\\" style=\\"width:16px;height:16px;border-radius:4px;object-fit:contain;\\" onerror=\\"this.style.display=\\x27none\\x27\\"/><span style=\\"font-size:11px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.05em;\\">' . e(__('OpenRouter')) . '</span></div>";'
                . 'modelsData.forEach(function(g){'
                . 'if(g.provider!=="openrouter")return;'
                . 'g.models.forEach(function(m){'
                . 'if(!modelMatches(m))return;'
                . 'h+=modelItemHtml(m);'
                . '});'
                . '});'
                . '}else{'
                . 'var hasOR=false;'
                . 'modelsData.forEach(function(g){if(g.provider==="openrouter"&&g.models.length>0)hasOR=true;});'
                . 'modelsData.forEach(function(g){'
                . 'if(g.provider==="openrouter")return;'
                . 'g.models.forEach(function(m){'
                . 'if(!modelMatches(m))return;'
                . 'h+=modelItemHtml(m);'
                . '});'
                . '});'
                . 'if(hasOR){'
                . 'h+="<button type=\\"button\\" data-or-open style=\\"width:100%;text-align:left;padding:6px 8px;border:0;border-radius:8px;font-size:12px;font-weight:600;color:#6b7280;display:flex;align-items:center;gap:8px;cursor:pointer;background:transparent;\\"><img src=\\"/images/model-logos/openrouter.png\\" alt=\\"\\" style=\\"width:16px;height:16px;border-radius:4px;object-fit:contain;flex-shrink:0;\\" onerror=\\"this.style.display=\\x27none\\x27\\"/>' . e(__('OpenRouter')) . ' ' . $chevronIconJs . '</button>";'
                . '}'
                . '}'
                . 'modelList.innerHTML=h;'
                . 'modelList.querySelectorAll("button[data-model]").forEach(function(b){b.addEventListener("click",function(e){e.stopPropagation();selectedModel=b.getAttribute("data-model");if(modelLabel)modelLabel.textContent=selectedModel==="auto"?"' . e(__('Auto')) . '":selectedModel.split("/").pop();hideModelDropdown();});});'
                . 'var backBtn=modelList.querySelector("button[data-or-back]");'
                . 'if(backBtn)backBtn.addEventListener("click",function(e){e.stopPropagation();orView=false;renderModelList();});'
                . 'var orBtn=modelList.querySelector("button[data-or-open]");'
                . 'if(orBtn)orBtn.addEventListener("click",function(e){e.stopPropagation();orView=true;renderModelList();});'
                . '}'
                . 'function showModelDropdown(){if(!modelDropdown)return;modelSearch="";orView=false;if(modelSearchInput)modelSearchInput.value="";renderModelList();modelDropdown.style.display="block";document.addEventListener("click",onModelDocClick);}'
                . 'function hideModelDropdown(){if(!modelDropdown)return;modelDropdown.style.display="none";document.removeEventListener("click",onModelDocClick);}'
                . 'function onModelDocClick(e){if(modelWrap&&!modelWrap.contains(e.target))hideModelDropdown();}'
                . 'if(modelSearchInput){modelSearchInput.addEventListener("input",function(){modelSearch=modelSearchInput.value;renderModelList();});modelSearchInput.addEventListener("click",function(e){e.stopPropagation();});}'
                . 'if(modelBtn){modelBtn.addEventListener("click",function(e){e.stopPropagation();if(modelDropdown&&modelDropdown.style.display==="block"){hideModelDropdown();}else{showModelDropdown();}});}'
                . 'document.addEventListener("keydown",function(e){if(e.key==="Escape")hideModelDropdown();});'
                . 'renderModelList();';
        } elseif ($showFileUpload) {
            $modelDropdownHtml = '<span style="height:28px;padding:0 8px;display:inline-flex;align-items:center;gap:6px;border-radius:8px;font-size:12px;font-weight:500;color:#6b7280;">'
                . $autoIcon
                . e(__('Auto')) . '</span>';
        }

        $html = '<div class="mp-ai-prompt-input" id="' . e($id) . '" style="max-width:' . $maxWidth . 'px;width:100%;margin:0 auto;position:relative;">';
        $html .= '<style>#' . e($id) . ' textarea::placeholder{color:' . e($placeholderColor) . ';opacity:1;}</style>';
        $html .= '<div style="background-color:' . e($backgroundColor) . ';border:1px solid ' . e($borderColor) . ';border-radius:' . $borderRadius . 'px;padding:12px;">';
        $html .= '<textarea class="mp-ai-prompt-input__textarea" rows="2" placeholder="' . e($placeholder) . '" style="display:block;width:100%;resize:none;border:0;background:transparent;padding:0;font-size:14px;line-height:1.4;outline:none;color:' . e($textColor) . ';font-family:inherit;"></textarea>';
        $html .= '<div style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">';
        $html .= '<div style="display:flex;align-items:center;gap:4px;">';
        $html .= '<button type="button" data-mp-action="plus" title="' . e(__('Add')) . '" style="height:28px;width:28px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:0;background:transparent;color:#9ca3af;cursor:pointer;">'
            . '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg></button>';
        $html .= $modelDropdownHtml;
        if ($showDiscuss) {
            $html .= '<button type="button" data-mp-action="discuss" style="height:28px;padding:0 8px;display:inline-flex;align-items:center;gap:6px;border-radius:8px;border:0;background:transparent;color:#6b7280;font-size:12px;font-weight:500;cursor:pointer;">'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'
                . e($discussText) . '</button>';
        }
        $html .= '</div>';
        $html .= '<div style="display:flex;align-items:center;gap:8px;">';
        if ($showVoice) {
            $html .= '<button type="button" data-mp-action="voice" title="' . e(__('Voice input')) . '" style="height:32px;width:32px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:0;background:transparent;color:#9ca3af;cursor:pointer;">'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" x2="12" y1="19" y2="22"/></svg></button>';
        }
        $html .= '<button type="button" data-mp-action="submit" title="' . e(__('Submit')) . '" style="height:32px;width:32px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px;border:0;background:#111827;color:#ffffff;cursor:pointer;">'
            . '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg></button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="mp-ai-prompt-input__popover" style="display:none;position:absolute;top:100%;left:0;margin-top:10px;width:260px;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,0.12);padding:14px;z-index:40;">';
        $html .= '<p style="margin:0 0 4px;font-size:13px;font-weight:600;color:#111827;">' . e(__('Sign in to continue')) . '</p>';
        $html .= '<p style="margin:0 0 12px;font-size:12px;color:#6b7280;line-height:1.4;">' . e(__('Log in or create a free account to start building.')) . '</p>';
        $html .= '<div style="display:flex;gap:8px;">';
        $html .= '<a href="' . e($loginUrl) . '" style="flex:1;text-align:center;padding:8px 10px;border-radius:8px;background:#111827;color:#ffffff;font-size:12px;font-weight:600;text-decoration:none;">' . e(__('Log In')) . '</a>';
        $html .= '<a href="' . e($registerUrl) . '" style="flex:1;text-align:center;padding:8px 10px;border-radius:8px;background:#f3f4f6;color:#111827;font-size:12px;font-weight:600;text-decoration:none;">' . e(__('Sign Up')) . '</a>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        $loggedInJson = $loggedIn ? 'true' : 'false';
        $submitUrlJson = json_encode($submitUrl, JSON_UNESCAPED_SLASHES);

        $html .= '<script>(function(){'
            . 'var root=document.getElementById(' . json_encode($id) . ');if(!root)return;'
            . 'var loggedIn=' . $loggedInJson . ';'
            . 'var submitUrl=' . $submitUrlJson . ';'
            . 'var textarea=root.querySelector(".mp-ai-prompt-input__textarea");'
            . 'var popover=root.querySelector(".mp-ai-prompt-input__popover");'
            . 'var fileInput=root.querySelector(".mp-ai-prompt-input__file");'
            . 'function onDocClick(e){if(!root.contains(e.target)){hidePopover();}}'
            . 'function showPopover(){if(!popover)return;popover.style.display="block";document.addEventListener("click",onDocClick);}'
            . 'function hidePopover(){if(!popover)return;popover.style.display="none";document.removeEventListener("click",onDocClick);}'
            . 'function requireAuth(e){if(loggedIn)return true;if(e&&e.preventDefault)e.preventDefault();showPopover();return false;}'
            . 'function goSubmit(){var val=textarea?textarea.value.trim():"";if(!submitUrl)return;var sep=submitUrl.indexOf("?")>-1?"&":"?";var url=submitUrl+sep+"prompt="+encodeURIComponent(val)+"&auto=1";if(discussActive){url+="&mode=discuss";}if(typeof selectedModel!=="undefined"&&selectedModel&&selectedModel!=="auto"){url+="&model="+encodeURIComponent(selectedModel);}window.location.href=url;}'
            . 'var discussActive=false;'
            . $modelJs
            . 'root.querySelectorAll("[data-mp-action]").forEach(function(btn){'
            . 'btn.addEventListener("click",function(e){'
            . 'var action=btn.getAttribute("data-mp-action");'
            . 'if(action==="file"){if(!requireAuth(e))return;if(fileInput)fileInput.click();return;}'
            . 'if(action==="submit"){if(!requireAuth(e))return;goSubmit();return;}'
            . 'if(action==="discuss"){discussActive=!discussActive;btn.style.backgroundColor=discussActive?"#f3f4f6":"transparent";btn.style.color=discussActive?"#111827":"#6b7280";btn.style.fontWeight=discussActive?"600":"500";return;}'
            . 'requireAuth(e);'
            . '});'
            . '});'
            . 'if(textarea){'
            . 'textarea.addEventListener("keydown",function(e){if(e.key==="Enter"&&!e.shiftKey){e.preventDefault();if(!requireAuth(e))return;goSubmit();}});'
            . '}'
            . '})();</script>';

        return $html;
    }

    private function getAvailableModelsJson(): string
    {
        try {
            $router = app(\App\Services\AI\AiProviderRouter::class);
            $groups = [];
            foreach (\App\Services\AI\AiProviderRegistry::providerKeys() as $providerKey) {
                if (! $router->isProviderEnabled($providerKey)) {
                    continue;
                }
                $models = \App\Services\AI\AiProviderRegistry::models($providerKey);
                $modelItems = [];
                foreach ($models as $model) {
                    if (! $router->isModelEnabled($providerKey, $model)) {
                        continue;
                    }
                    $label = $this->modelLabel($model);
                    $modelItems[] = ['value' => $model, 'label' => $label];
                }
                if (count($modelItems) > 0) {
                    $groups[] = ['provider' => $providerKey, 'models' => $modelItems];
                }
            }
            return json_encode($groups, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return '[]';
        }
    }

    private function modelLabel(string $model): string
    {
        $labelMap = [
            'openai/gpt-4o' => 'GPT-4o',
            'openai/gpt-4o-mini' => 'GPT-4o Mini',
            'openai/gpt-4.1' => 'GPT-4.1',
            'openai/gpt-4.1-mini' => 'GPT-4.1 Mini',
            'openai/gpt-4.1-nano' => 'GPT-4.1 Nano',
            'openai/o3-mini' => 'o3-mini',
            'openai/o4-mini' => 'o4-mini',
            'openai/gpt-4-turbo' => 'GPT-4 Turbo',
            'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'openai/chatgpt-4o-latest' => 'ChatGPT-4o Latest',
            'anthropic/claude-fable-5' => 'Claude Fable 5',
            'anthropic/claude-opus-5' => 'Claude Opus 5',
            'anthropic/claude-sonnet-5' => 'Claude Sonnet 5',
            'anthropic/claude-opus-4-8-fast' => 'Claude Opus 4.8 Fast',
            'anthropic/claude-opus-4-8' => 'Claude Opus 4.8',
            'anthropic/claude-opus-4-7' => 'Claude Opus 4.7',
            'anthropic/claude-opus-4-6' => 'Claude Opus 4.6',
            'anthropic/claude-opus-4-5' => 'Claude Opus 4.5',
            'anthropic/claude-haiku-4-5' => 'Claude Haiku 4.5',
            'google/gemini-3.6-flash' => 'Gemini 3.6 Flash',
            'google/gemini-3.5-flash-lite' => 'Gemini 3.5 Flash Lite',
            'google/gemini-3.5-flash' => 'Gemini 3.5 Flash',
            'google/gemini-3.1-flash-lite' => 'Gemini 3.1 Flash Lite',
            'meta-llama/llama-3.3-70b-instruct' => 'Llama 3.3 70B',
            'meta-llama/llama-3.1-405b-instruct' => 'Llama 3.1 405B',
            'meta-llama/llama-3.1-70b-instruct' => 'Llama 3.1 70B',
            'meta-llama/llama-3.1-8b-instruct' => 'Llama 3.1 8B',
            'mistralai/mistral-large' => 'Mistral Large',
            'mistralai/codestral' => 'Codestral',
            'mistralai/mistral-medium' => 'Mistral Medium',
            'mistralai/mistral-small' => 'Mistral Small',
            'qwen/qwen3.7-max' => 'Qwen3.7 Max',
            'qwen/qwen3.7-plus' => 'Qwen3.7 Plus',
            'qwen/qwen3.6-plus' => 'Qwen3.6 Plus',
            'deepseek/deepseek-v4-flash' => 'DeepSeek V4 Flash',
            'deepseek/deepseek-v4-pro' => 'DeepSeek V4 Pro',
            'deepseek/deepseek-v3.2' => 'DeepSeek V3.2',
            'moonshotai/kimi-k3' => 'Kimi K3',
            'moonshotai/kimi-k2.7-code' => 'Kimi K2.7 Code',
            'moonshotai/kimi-k2.6' => 'Kimi K2.6',
            'x-ai/grok-4.5' => 'Grok 4.5',
            'x-ai/grok-4.3' => 'Grok 4.3',
            'zhipuai/glm-5.2' => 'GLM 5.2',
            'zhipuai/glm-5.1' => 'GLM 5.1',
            'minimax/minimax-m3' => 'MiniMax M3',
            'minimax/minimax-m2.7' => 'MiniMax M2.7',
            'xiaomi/mimo-v2.5' => 'MiMo-V2.5',
            'xiaomi/mimo-v2.5-pro' => 'MiMo-V2.5-Pro',
        ];

        return $labelMap[$model] ?? $model;
    }

    private function renderLoginForm(array $props): string
    {
        $emailLabel = is_string($props['emailLabel'] ?? null) ? trim((string) $props['emailLabel']) : __('Email');
        $emailPlaceholder = is_string($props['emailPlaceholder'] ?? null) ? trim((string) $props['emailPlaceholder']) : __('you@example.com');
        $passwordLabel = is_string($props['passwordLabel'] ?? null) ? trim((string) $props['passwordLabel']) : __('Password');
        $passwordPlaceholder = is_string($props['passwordPlaceholder'] ?? null) ? trim((string) $props['passwordPlaceholder']) : __('Enter your password');
        $buttonText = is_string($props['buttonText'] ?? null) ? trim((string) $props['buttonText']) : 'Sign In';
        $showRemember = isset($props['showRemember']) ? (bool) $props['showRemember'] : true;
        $showForgot = isset($props['showForgot']) ? (bool) $props['showForgot'] : true;
        $showRegisterLink = isset($props['showRegisterLink']) ? (bool) $props['showRegisterLink'] : true;
        $rememberText = is_string($props['rememberText'] ?? null) ? trim((string) $props['rememberText']) : __('Remember me');
        $forgotText = is_string($props['forgotText'] ?? null) ? trim((string) $props['forgotText']) : __('Forgot password?');
        $registerPrefix = is_string($props['registerPrefix'] ?? null) ? trim((string) $props['registerPrefix']) : __("Don't have an account?");
        $registerText = is_string($props['registerText'] ?? null) ? trim((string) $props['registerText']) : __('Create account');

        $action = '#';
        try {
            if (\Illuminate\Support\Facades\Route::has('login')) {
                $action = route('login');
            }
        } catch (\Throwable $e) {
            $action = '#';
        }

        $maxWidth = is_numeric($props['maxWidth'] ?? null) ? (int) $props['maxWidth'] : 400;
        $formWrapperStyle = 'display:flex;flex-direction:column;gap:12px;max-width:' . $maxWidth . 'px;width:100%;margin:0 auto;';
        if (is_string($props['formBackgroundColor'] ?? null) && $props['formBackgroundColor'] !== '') {
            $formWrapperStyle .= 'background-color:' . e($props['formBackgroundColor']) . ';';
        }
        if (is_numeric($props['formPadding'] ?? null)) {
            $formWrapperStyle .= 'padding:' . (int) $props['formPadding'] . 'px;';
        }
        if (is_numeric($props['formBorderRadius'] ?? null)) {
            $formWrapperStyle .= 'border-radius:' . (int) $props['formBorderRadius'] . 'px;';
        }

        $html = '<form class="mp-login-form" action="' . e($action) . '" method="POST" style="' . e($formWrapperStyle) . '">';
        $html .= csrf_field();
        $placeholderCss = $this->buildFormPlaceholderCss('.mp-login-form input', $props['placeholderColor'] ?? null);
        $hoverBorderColor = $this->sanitizeColor($props['inputBorderHoverColor'] ?? null);
        $focusBorderColor = $this->sanitizeColor($props['inputBorderFocusColor'] ?? null);
        $stateCss = '';
        if ($hoverBorderColor) {
            $stateCss .= '.mp-login-form input:hover{border-color:' . e($hoverBorderColor) . ' !important;}';
        }
        if ($focusBorderColor) {
            $stateCss .= '.mp-login-form input:focus{border-color:' . e($focusBorderColor) . ' !important;outline:none;}';
        }
        if ($placeholderCss !== '' || $stateCss !== '') {
            $html .= '<style>' . $placeholderCss . $stateCss . '</style>';
        }

        $labelColor = $this->sanitizeColor($props['labelColor'] ?? null) ?? '#374151';
        $inputStyle = $this->buildFormFieldStyle($props, 'inputTextColor');
        $passwordStyle = rtrim($inputStyle, ';') . ';padding-right:40px;';

        $html .= '<div style="display:flex;flex-direction:column;gap:4px;">';
        $html .= '<label style="font-size:13px;font-weight:500;color:' . e($labelColor) . ';">' . e($emailLabel) . '</label>';
        $html .= '<input type="email" name="email" required placeholder="' . e($emailPlaceholder) . '" style="' . e($inputStyle) . '">';
        $html .= '</div>';

        $html .= '<div style="display:flex;flex-direction:column;gap:4px;">';
        $passwordRow = '<div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:4px;">';
        $passwordRow .= '<label style="font-size:13px;font-weight:500;color:' . e($labelColor) . ';">' . e($passwordLabel) . '</label>';
        if ($showForgot) {
            $forgotUrl = '#';
            try {
                if (\Illuminate\Support\Facades\Route::has('password.request')) {
                    $forgotUrl = route('password.request');
                }
            } catch (\Throwable $e) {
                $forgotUrl = '#';
            }
            $passwordRow .= '<a href="' . e($forgotUrl) . '" style="font-size:12px;font-weight:500;color:#1E5FEA;text-decoration:none;white-space:nowrap;">' . e($forgotText) . '</a>';
        }
        $passwordRow .= '</div>';
        $html .= $passwordRow;
        $html .= $this->renderPasswordInputHtml('password', $passwordStyle, $passwordPlaceholder);
        $html .= '</div>';

        if ($showRemember) {
            $html .= '<div style="display:flex;align-items:center;gap:8px;">';
            $html .= '<input type="checkbox" name="remember" id="mp-login-remember" value="1" style="width:16px;height:16px;border-radius:3px;border:1px solid #d1d5db;">';
            $html .= '<label for="mp-login-remember" style="font-size:13px;color:#374151;">' . e($rememberText) . '</label>';
            $html .= '</div>';
        }

        $iconHtml = $this->renderAuthButtonIcon($props);
        $iconPosition = in_array($props['buttonIconPosition'] ?? '', ['left', 'right'], true) ? $props['buttonIconPosition'] : 'right';
        $buttonContent = e($buttonText);
        if ($iconHtml !== '') {
            if ($iconPosition === 'left') {
                $buttonContent = $iconHtml . ' ' . $buttonContent;
            } else {
                $buttonContent = $buttonContent . ' ' . $iconHtml;
            }
        }
        $html .= '<button type="submit" class="mp-login-button" style="padding:10px 18px;background:#1E5FEA;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;width:100%;display:flex;align-items:center;justify-content:center;gap:8px;">' . $buttonContent . '</button>';

        if ($showRegisterLink) {
            $registerUrl = '#';
            try {
                if (\Illuminate\Support\Facades\Route::has('register')) {
                    $registerUrl = route('register');
                }
            } catch (\Throwable $e) {
                $registerUrl = '#';
            }
            $html .= '<div style="text-align:center;font-size:13px;color:#6b7280;">' . e($registerPrefix) . ' <a href="' . e($registerUrl) . '" style="color:#1E5FEA;text-decoration:none;">' . e($registerText) . '</a></div>';
        }

        $html .= '</form>';

        return $html;
    }

    private function renderRegisterForm(array $props): string
    {
        $emailLabel = is_string($props['emailLabel'] ?? null) ? trim((string) $props['emailLabel']) : __('Email');
        $emailPlaceholder = is_string($props['emailPlaceholder'] ?? null) ? trim((string) $props['emailPlaceholder']) : __('you@example.com');
        $passwordLabel = is_string($props['passwordLabel'] ?? null) ? trim((string) $props['passwordLabel']) : __('Password');
        $passwordPlaceholder = is_string($props['passwordPlaceholder'] ?? null) ? trim((string) $props['passwordPlaceholder']) : __('Create a password');
        $confirmPasswordLabel = is_string($props['confirmPasswordLabel'] ?? null) ? trim((string) $props['confirmPasswordLabel']) : __('Confirm Password');
        $confirmPasswordPlaceholder = is_string($props['confirmPasswordPlaceholder'] ?? null) ? trim((string) $props['confirmPasswordPlaceholder']) : __('Confirm your password');
        $buttonText = is_string($props['buttonText'] ?? null) ? trim((string) $props['buttonText']) : 'Create Account';
        $showLoginLink = isset($props['showLoginLink']) ? (bool) $props['showLoginLink'] : true;
        $loginPrefix = is_string($props['loginPrefix'] ?? null) ? trim((string) $props['loginPrefix']) : __('Already have an account?');
        $loginText = is_string($props['loginText'] ?? null) ? trim((string) $props['loginText']) : __('Sign in');

        $action = '#';
        try {
            if (\Illuminate\Support\Facades\Route::has('register')) {
                $action = route('register');
            }
        } catch (\Throwable $e) {
            $action = '#';
        }

        $maxWidth = is_numeric($props['maxWidth'] ?? null) ? (int) $props['maxWidth'] : 400;
        $formWrapperStyle = 'display:flex;flex-direction:column;gap:12px;max-width:' . $maxWidth . 'px;width:100%;margin:0 auto;';
        if (is_string($props['formBackgroundColor'] ?? null) && $props['formBackgroundColor'] !== '') {
            $formWrapperStyle .= 'background-color:' . e($props['formBackgroundColor']) . ';';
        }
        if (is_numeric($props['formPadding'] ?? null)) {
            $formWrapperStyle .= 'padding:' . (int) $props['formPadding'] . 'px;';
        }
        if (is_numeric($props['formBorderRadius'] ?? null)) {
            $formWrapperStyle .= 'border-radius:' . (int) $props['formBorderRadius'] . 'px;';
        }

        $html = '<form class="mp-register-form" action="' . e($action) . '" method="POST" style="' . e($formWrapperStyle) . '">';
        $html .= csrf_field();
        $placeholderCss = $this->buildFormPlaceholderCss('.mp-register-form input', $props['placeholderColor'] ?? null);
        $hoverBorderColor = $this->sanitizeColor($props['inputBorderHoverColor'] ?? null);
        $focusBorderColor = $this->sanitizeColor($props['inputBorderFocusColor'] ?? null);
        $stateCss = '';
        if ($hoverBorderColor) {
            $stateCss .= '.mp-register-form input:hover{border-color:' . e($hoverBorderColor) . ' !important;}';
        }
        if ($focusBorderColor) {
            $stateCss .= '.mp-register-form input:focus{border-color:' . e($focusBorderColor) . ' !important;outline:none;}';
        }
        if ($placeholderCss !== '' || $stateCss !== '') {
            $html .= '<style>' . $placeholderCss . $stateCss . '</style>';
        }

        $labelColor = $this->sanitizeColor($props['labelColor'] ?? null) ?? '#374151';
        $inputStyle = $this->buildFormFieldStyle($props, 'inputTextColor');
        $passwordStyle = rtrim($inputStyle, ';') . ';padding-right:40px;';

        $html .= '<div style="display:flex;flex-direction:column;gap:4px;">';
        $html .= '<label style="font-size:13px;font-weight:500;color:' . e($labelColor) . ';">' . e($emailLabel) . '</label>';
        $html .= '<input type="email" name="email" required placeholder="' . e($emailPlaceholder) . '" style="' . e($inputStyle) . '">';
        $html .= '</div>';

        $html .= '<div style="display:flex;flex-direction:column;gap:4px;">';
        $html .= '<label style="font-size:13px;font-weight:500;color:' . e($labelColor) . ';">' . e($passwordLabel) . '</label>';
        $html .= $this->renderPasswordInputHtml('password', $passwordStyle, $passwordPlaceholder);
        $html .= '</div>';

        $html .= '<div style="display:flex;flex-direction:column;gap:4px;">';
        $html .= '<label style="font-size:13px;font-weight:500;color:' . e($labelColor) . ';">' . e($confirmPasswordLabel) . '</label>';
        $html .= $this->renderPasswordInputHtml('password_confirmation', $passwordStyle, $confirmPasswordPlaceholder);
        $html .= '</div>';

        $iconHtml = $this->renderAuthButtonIcon($props);
        $iconPosition = in_array($props['buttonIconPosition'] ?? '', ['left', 'right'], true) ? $props['buttonIconPosition'] : 'right';
        $buttonContent = e($buttonText);
        if ($iconHtml !== '') {
            if ($iconPosition === 'left') {
                $buttonContent = $iconHtml . ' ' . $buttonContent;
            } else {
                $buttonContent = $buttonContent . ' ' . $iconHtml;
            }
        }
        $html .= '<button type="submit" class="mp-register-button" style="padding:10px 18px;background:#1E5FEA;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:500;cursor:pointer;width:100%;display:flex;align-items:center;justify-content:center;gap:8px;">' . $buttonContent . '</button>';

        if ($showLoginLink) {
            $loginUrl = '#';
            try {
                if (\Illuminate\Support\Facades\Route::has('login')) {
                    $loginUrl = route('login');
                }
            } catch (\Throwable $e) {
                $loginUrl = '#';
            }
            $html .= '<div style="text-align:center;font-size:13px;color:#6b7280;">' . e($loginPrefix) . ' <a href="' . e($loginUrl) . '" style="color:#1E5FEA;text-decoration:none;">' . e($loginText) . '</a></div>';
        }

        $html .= '</form>';

        return $html;
    }

    private function buildFormFieldStyle(array $props, string $textColorKey = 'textColor'): string
    {
        $style = 'width:100%;box-sizing:border-box;font-size:16px;outline:none;transition:border-color 0.2s,box-shadow 0.2s;';

        $bg = $this->sanitizeColor($props['backgroundColor'] ?? null) ?? '#ffffff';
        if ($bg !== '') {
            $style .= 'background-color:' . e($bg) . ';';
        }

        $color = $this->sanitizeColor($props[$textColorKey] ?? null) ?? '#111827';
        if ($color !== '') {
            $style .= 'color:' . e($color) . ';';
        }

        $borderColor = $this->sanitizeColor($props['borderColor'] ?? null) ?? '#d1d5db';
        $borderWidth = is_numeric($props['borderWidth'] ?? null) ? (int) $props['borderWidth'] : 1;
        $borderStyle = is_string($props['borderStyle'] ?? null) ? trim((string) $props['borderStyle']) : 'solid';
        if ($borderStyle !== '' && $borderStyle !== 'none') {
            $style .= 'border:' . (int) $borderWidth . 'px ' . e($borderStyle) . ' ' . e($borderColor) . ';';
        } elseif ($borderStyle === 'none') {
            $style .= 'border:none;';
        }

        $radius = is_numeric($props['borderRadius'] ?? null) ? (int) $props['borderRadius'] : 8;
        $style .= 'border-radius:' . $radius . 'px;';

        $pt = is_numeric($props['paddingTop'] ?? null) ? (int) $props['paddingTop'] : 10;
        $pr = is_numeric($props['paddingRight'] ?? null) ? (int) $props['paddingRight'] : 14;
        $pb = is_numeric($props['paddingBottom'] ?? null) ? (int) $props['paddingBottom'] : 10;
        $pl = is_numeric($props['paddingLeft'] ?? null) ? (int) $props['paddingLeft'] : 14;
        $style .= 'padding:' . $pt . 'px ' . $pr . 'px ' . $pb . 'px ' . $pl . 'px;';

        $family = is_string($props['fontFamily'] ?? null) ? trim((string) $props['fontFamily']) : '';
        if ($family !== '') {
            $style .= 'font-family:' . e($family) . ';';
        }

        $fsize = is_numeric($props['fontSize'] ?? null) ? (int) $props['fontSize'] : 0;
        if ($fsize > 0) {
            $style .= 'font-size:' . $fsize . 'px;';
        }

        $mt = is_numeric($props['marginTop'] ?? null) ? (int) $props['marginTop'] : 0;
        if ($mt > 0) {
            $style .= 'margin-top:' . $mt . 'px;';
        }
        $mb = is_numeric($props['marginBottom'] ?? null) ? (int) $props['marginBottom'] : 8;
        if ($mb > 0) {
            $style .= 'margin-bottom:' . $mb . 'px;';
        }

        return $style;
    }

    private function buildFormPlaceholderCss(string $selector, $color): string
    {
        $placeholderColor = $this->sanitizeColor($color);
        if ($placeholderColor === null || $placeholderColor === '') {
            return '';
        }

        $selectors = [
            $selector . '::placeholder',
            $selector . '::-webkit-input-placeholder',
            $selector . '::-moz-placeholder',
            $selector . ':-ms-input-placeholder',
        ];

        return implode(',', $selectors) . '{color:' . $placeholderColor . ';opacity:1;}';
    }

    private function buildFormFieldLabel(array $props): string
    {
        $label = is_string($props['label'] ?? null) ? trim((string) $props['label']) : '';
        if ($label === '') {
            return '';
        }

        $style = 'display:block;font-size:14px;font-weight:500;color:#374151;margin-bottom:6px;';
        $color = $this->sanitizeColor($props['labelColor'] ?? null);
        if ($color !== null && $color !== '') {
            $style = str_replace('color:#374151;', 'color:' . e($color) . ';', $style);
        }
        $family = is_string($props['fontFamily'] ?? null) ? trim((string) $props['fontFamily']) : '';
        if ($family !== '') {
            $style .= 'font-family:' . e($family) . ';';
        }

        $required = isset($props['required']) && $props['required'] === true;
        $reqMark = $required ? ' <span style="color:#ef4444;">*</span>' : '';

        $name = is_string($props['name'] ?? null) ? trim((string) $props['name']) : '';
        $forAttr = $name !== '' ? ' for="' . e($name) . '"' : '';

        return '<label' . $forAttr . ' style="' . $style . '">' . e($label) . $reqMark . '</label>';
    }

    private function renderFormText(array $props): string
    {
        $name = is_string($props['name'] ?? null) ? trim((string) $props['name']) : 'name';
        if ($name === '') {
            $name = 'name';
        }
        $placeholder = is_string($props['placeholder'] ?? null) ? trim((string) $props['placeholder']) : '';
        $required = isset($props['required']) && $props['required'] === true;

        $labelHtml = $this->buildFormFieldLabel($props);
        $inputStyle = $this->buildFormFieldStyle($props);

        $html = $labelHtml;
        $html .= '<input type="text" name="' . e($name) . '"';
        if ($placeholder !== '') {
            $html .= ' placeholder="' . e($placeholder) . '"';
        }
        if ($required) {
            $html .= ' required';
        }
        $html .= ' style="' . $inputStyle . '" />';

        return $html;
    }

    private function renderFormEmail(array $props): string
    {
        $name = is_string($props['name'] ?? null) ? trim((string) $props['name']) : 'email';
        if ($name === '') {
            $name = 'email';
        }
        $placeholder = is_string($props['placeholder'] ?? null) ? trim((string) $props['placeholder']) : '';
        $required = isset($props['required']) && $props['required'] === true;

        $labelHtml = $this->buildFormFieldLabel($props);
        $inputStyle = $this->buildFormFieldStyle($props);

        $html = $labelHtml;
        $html .= '<input type="email" name="' . e($name) . '"';
        if ($placeholder !== '') {
            $html .= ' placeholder="' . e($placeholder) . '"';
        }
        if ($required) {
            $html .= ' required';
        }
        $html .= ' style="' . $inputStyle . '" />';

        return $html;
    }

    private function renderFormTextarea(array $props): string
    {
        $name = is_string($props['name'] ?? null) ? trim((string) $props['name']) : 'message';
        if ($name === '') {
            $name = 'message';
        }
        $placeholder = is_string($props['placeholder'] ?? null) ? trim((string) $props['placeholder']) : '';
        $required = isset($props['required']) && $props['required'] === true;
        $rows = is_numeric($props['rows'] ?? null) ? (int) $props['rows'] : 4;

        $labelHtml = $this->buildFormFieldLabel($props);
        $inputStyle = $this->buildFormFieldStyle($props);
        $inputStyle .= 'resize:vertical;';

        $html = $labelHtml;
        $html .= '<textarea name="' . e($name) . '" rows="' . $rows . '"';
        if ($placeholder !== '') {
            $html .= ' placeholder="' . e($placeholder) . '"';
        }
        if ($required) {
            $html .= ' required';
        }
        $html .= ' style="' . $inputStyle . '"></textarea>';

        return $html;
    }

    private function renderFormSelect(array $props): string
    {
        $name = is_string($props['name'] ?? null) ? trim((string) $props['name']) : 'field';
        if ($name === '') {
            $name = 'field';
        }
        $required = isset($props['required']) && $props['required'] === true;
        $optionsRaw = is_array($props['options'] ?? null) ? $props['options'] : [];

        $labelHtml = $this->buildFormFieldLabel($props);
        $inputStyle = $this->buildFormFieldStyle($props);

        $html = $labelHtml;
        $html .= '<select name="' . e($name) . '"';
        if ($required) {
            $html .= ' required';
        }
        $html .= ' style="' . $inputStyle . '">';

        foreach ($optionsRaw as $opt) {
            if (!is_array($opt)) {
                continue;
            }
            $val = is_string($opt['value'] ?? null) ? trim((string) $opt['value']) : '';
            $lab = is_string($opt['label'] ?? null) ? trim((string) $opt['label']) : $val;
            if ($val === '') {
                continue;
            }
            $html .= '<option value="' . e($val) . '">' . e($lab) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    private function renderFormCheckbox(array $props): string
    {
        $name = is_string($props['name'] ?? null) ? trim((string) $props['name']) : 'agree';
        if ($name === '') {
            $name = 'agree';
        }
        $required = isset($props['required']) && $props['required'] === true;
        $optionsRaw = is_array($props['options'] ?? null) ? $props['options'] : [];

        $label = is_string($props['label'] ?? null) ? trim((string) $props['label']) : '';
        $labelColor = is_string($props['labelColor'] ?? null) ? trim((string) $props['labelColor']) : '#374151';
        $family = is_string($props['fontFamily'] ?? null) ? trim((string) $props['fontFamily']) : '';

        $html = '';
        if ($label !== '') {
            $labelStyle = 'display:block;font-size:14px;font-weight:500;color:' . e($labelColor) . ';margin-bottom:8px;';
            if ($family !== '') {
                $labelStyle .= 'font-family:' . e($family) . ';';
            }
            $html .= '<span style="' . $labelStyle . '">' . e($label) . '</span>';
        }

        $wrapperStyle = 'display:flex;flex-direction:column;gap:6px;';
        $mt = is_numeric($props['marginTop'] ?? null) ? (int) $props['marginTop'] : 0;
        $mb = is_numeric($props['marginBottom'] ?? null) ? (int) $props['marginBottom'] : 8;
        if ($mt > 0) {
            $wrapperStyle .= 'margin-top:' . $mt . 'px;';
        }
        if ($mb > 0) {
            $wrapperStyle .= 'margin-bottom:' . $mb . 'px;';
        }

        $html .= '<div style="' . $wrapperStyle . '">';
        foreach ($optionsRaw as $opt) {
            if (!is_array($opt)) {
                continue;
            }
            $val = is_string($opt['value'] ?? null) ? trim((string) $opt['value']) : '';
            $lab = is_string($opt['label'] ?? null) ? trim((string) $opt['label']) : $val;
            if ($val === '') {
                continue;
            }
            $html .= '<label style="display:flex;align-items:center;gap:6px;font-size:14px;color:' . e($labelColor) . ';cursor:pointer;' . ($family !== '' ? 'font-family:' . e($family) . ';' : '') . '">';
            $html .= '<input type="checkbox" name="' . e($name) . '[]" value="' . e($val) . '"' . ($required ? ' required' : '') . ' style="width:16px;height:16px;accent-color:#1E5FEA;cursor:pointer;" />';
            $html .= e($lab);
            $html .= '</label>';
        }
        $html .= '</div>';

        return $html;
    }

    private function renderFormRadio(array $props): string
    {
        $name = is_string($props['name'] ?? null) ? trim((string) $props['name']) : 'choice';
        if ($name === '') {
            $name = 'choice';
        }
        $required = isset($props['required']) && $props['required'] === true;
        $optionsRaw = is_array($props['options'] ?? null) ? $props['options'] : [];

        $label = is_string($props['label'] ?? null) ? trim((string) $props['label']) : '';
        $labelColor = is_string($props['labelColor'] ?? null) ? trim((string) $props['labelColor']) : '#374151';
        $family = is_string($props['fontFamily'] ?? null) ? trim((string) $props['fontFamily']) : '';

        $html = '';
        if ($label !== '') {
            $labelStyle = 'display:block;font-size:14px;font-weight:500;color:' . e($labelColor) . ';margin-bottom:8px;';
            if ($family !== '') {
                $labelStyle .= 'font-family:' . e($family) . ';';
            }
            $html .= '<span style="' . $labelStyle . '">' . e($label) . '</span>';
        }

        $wrapperStyle = 'display:flex;flex-direction:column;gap:6px;';
        $mt = is_numeric($props['marginTop'] ?? null) ? (int) $props['marginTop'] : 0;
        $mb = is_numeric($props['marginBottom'] ?? null) ? (int) $props['marginBottom'] : 8;
        if ($mt > 0) {
            $wrapperStyle .= 'margin-top:' . $mt . 'px;';
        }
        if ($mb > 0) {
            $wrapperStyle .= 'margin-bottom:' . $mb . 'px;';
        }

        $html .= '<div style="' . $wrapperStyle . '">';
        foreach ($optionsRaw as $opt) {
            if (!is_array($opt)) {
                continue;
            }
            $val = is_string($opt['value'] ?? null) ? trim((string) $opt['value']) : '';
            $lab = is_string($opt['label'] ?? null) ? trim((string) $opt['label']) : $val;
            if ($val === '') {
                continue;
            }
            $html .= '<label style="display:flex;align-items:center;gap:6px;font-size:14px;color:' . e($labelColor) . ';cursor:pointer;' . ($family !== '' ? 'font-family:' . e($family) . ';' : '') . '">';
            $html .= '<input type="radio" name="' . e($name) . '" value="' . e($val) . '"' . ($required ? ' required' : '') . ' style="width:16px;height:16px;accent-color:#1E5FEA;cursor:pointer;" />';
            $html .= e($lab);
            $html .= '</label>';
        }
        $html .= '</div>';

        return $html;
    }

    private function renderFormSubmit(array $props): string
    {
        $text = is_string($props['text'] ?? null) ? trim((string) $props['text']) : 'Submit';
        if ($text === '') {
            $text = 'Submit';
        }
        $fullWidth = isset($props['fullWidth']) && $props['fullWidth'] === true;

        $buttonStyle = 'display:inline-flex;align-items:center;justify-content:center;font-weight:600;border:none;text-decoration:none;cursor:pointer;transition:all 0.2s;box-sizing:border-box;';

        $bg = is_string($props['backgroundColor'] ?? null) ? trim((string) $props['backgroundColor']) : '#1E5FEA';
        if ($bg !== '') {
            $buttonStyle .= 'background-color:' . e($bg) . ';';
        }
        $color = is_string($props['textColor'] ?? null) ? trim((string) $props['textColor']) : '#ffffff';
        if ($color !== '') {
            $buttonStyle .= 'color:' . e($color) . ';';
        }

        $pt = is_numeric($props['paddingTop'] ?? null) ? (int) $props['paddingTop'] : 12;
        $pr = is_numeric($props['paddingRight'] ?? null) ? (int) $props['paddingRight'] : 24;
        $pb = is_numeric($props['paddingBottom'] ?? null) ? (int) $props['paddingBottom'] : 12;
        $pl = is_numeric($props['paddingLeft'] ?? null) ? (int) $props['paddingLeft'] : 24;
        $buttonStyle .= 'padding:' . $pt . 'px ' . $pr . 'px ' . $pb . 'px ' . $pl . 'px;';

        $radius = is_numeric($props['borderRadius'] ?? null) ? (int) $props['borderRadius'] : 8;
        $buttonStyle .= 'border-radius:' . $radius . 'px;';

        $family = is_string($props['fontFamily'] ?? null) ? trim((string) $props['fontFamily']) : '';
        if ($family !== '') {
            $buttonStyle .= 'font-family:' . e($family) . ';';
        }
        $fsize = is_numeric($props['fontSize'] ?? null) ? (int) $props['fontSize'] : 0;
        if ($fsize > 0) {
            $buttonStyle .= 'font-size:' . $fsize . 'px;';
        }
        $fweight = is_string($props['fontWeight'] ?? null) ? trim((string) $props['fontWeight']) : '';
        if ($fweight !== '') {
            $buttonStyle .= 'font-weight:' . e($fweight) . ';';
        }

        $borderColor = is_string($props['borderColor'] ?? null) ? trim((string) $props['borderColor']) : '';
        $borderWidth = is_numeric($props['borderWidth'] ?? null) ? (int) $props['borderWidth'] : 0;
        $borderStyle = is_string($props['borderStyle'] ?? null) ? trim((string) $props['borderStyle']) : 'solid';
        if ($borderColor !== '' && $borderWidth > 0) {
            $buttonStyle .= 'border:' . (int) $borderWidth . 'px ' . e($borderStyle) . ' ' . e($borderColor) . ';';
        }

        $mt = is_numeric($props['marginTop'] ?? null) ? (int) $props['marginTop'] : 0;
        $mb = is_numeric($props['marginBottom'] ?? null) ? (int) $props['marginBottom'] : 0;
        if ($mt > 0) {
            $buttonStyle .= 'margin-top:' . $mt . 'px;';
        }
        if ($mb > 0) {
            $buttonStyle .= 'margin-bottom:' . $mb . 'px;';
        }

        $boxShadowColor = is_string($props['boxShadowColor'] ?? null) ? trim((string) $props['boxShadowColor']) : '';
        if ($boxShadowColor !== '') {
            $bsX = is_numeric($props['boxShadowX'] ?? null) ? (int) $props['boxShadowX'] : 0;
            $bsY = is_numeric($props['boxShadowY'] ?? null) ? (int) $props['boxShadowY'] : 0;
            $bsBlur = is_numeric($props['boxShadowBlur'] ?? null) ? (int) $props['boxShadowBlur'] : 0;
            $bsSpread = is_numeric($props['boxShadowSpread'] ?? null) ? (int) $props['boxShadowSpread'] : 0;
            $buttonStyle .= 'box-shadow:' . $bsX . 'px ' . $bsY . 'px ' . $bsBlur . 'px ' . $bsSpread . 'px ' . e($boxShadowColor) . ';';
        }

        if ($fullWidth) {
            $buttonStyle .= 'width:100%;';
        } elseif (is_numeric($props['width'] ?? null)) {
            $buttonStyle .= 'width:' . (int) $props['width'] . 'px;';
        }
        if (is_numeric($props['height'] ?? null)) {
            $buttonStyle .= 'height:' . (int) $props['height'] . 'px;';
        }

        $containerStyle = '';
        $align = is_string($props['align'] ?? null) ? trim((string) $props['align']) : 'left';
        $allowedAligns = ['left', 'center', 'right'];
        $align = in_array($align, $allowedAligns, true) ? $align : 'left';
        $containerStyle .= 'text-align:' . $align . ';';

        return '<div style="' . $containerStyle . '"><button type="submit" style="' . $buttonStyle . '">' . e($text) . '</button></div>';
    }

    public function renderForm(array $data, string $slug, ?string $listId = null): string
    {
        $action = route('public.subscribe.store', ['slug' => $slug]);
        if ($listId !== null && $listId !== '' && is_numeric($listId)) {
            $action .= (str_contains($action, '?') ? '&' : '?') . 'list_id=' . (int) $listId;
        }

        $pageHtml = $this->render($data);

        $formOpen = '<form action="' . e($action) . '" method="POST" style="margin:0;">';
        $formClose = '</form>';

        // Wrap the <main> content inside the form by replacing <main> and </main>
        $pageHtml = str_replace('<main', '<main', $pageHtml);

        // Insert form after the opening <main...> tag and close before </main>
        // Find the first <main ...> and </main>
        if (preg_match('/^(<main[^>]*>)/', $pageHtml, $mainOpenMatch)) {
            $mainOpen = $mainOpenMatch[1];
            $pageHtml = preg_replace('/^(<main[^>]*>)/', $mainOpen . $formOpen, $pageHtml, 1);
        } else {
            $pageHtml = $formOpen . $pageHtml;
        }

        $pageHtml = str_replace('</main>', $formClose . '</main>', $pageHtml);

        return $pageHtml;
    }

    private function webglRuntimeScript(): string
    {
        return <<<'JS'
<script>
(function(){
    var shaders = {
        noise: {
            vs: "attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }",
            fs: "precision mediump float; uniform vec2 u_resolution; uniform float u_time; uniform vec3 u_color1; uniform vec3 u_color2; uniform vec3 u_color3; uniform float u_scale; float rand(vec2 n) { return fract(sin(dot(n, vec2(12.9898, 78.233))) * 43758.5453); } float noise(vec2 p) { vec2 i = floor(p); vec2 f = fract(p); f = f * f * (3.0 - 2.0 * f); float a = rand(i); float b = rand(i + vec2(1.0, 0.0)); float c = rand(i + vec2(0.0, 1.0)); float d = rand(i + vec2(1.0, 1.0)); return mix(mix(a, b, f.x), mix(c, d, f.x), f.y); } float fbm(vec2 p) { float v = 0.0; float a = 0.5; for (int i = 0; i < 5; i++) { v += a * noise(p); p *= 2.0; a *= 0.5; } return v; } void main() { vec2 uv = gl_FragCoord.xy / u_resolution.xy; float n = fbm(uv * 3.0 * u_scale + u_time * 0.3); vec3 col = mix(u_color1, u_color2, n); col = mix(col, u_color3, smoothstep(0.4, 0.6, n)); gl_FragColor = vec4(col, 1.0); }"
        },
        gradientFlow: {
            vs: "attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }",
            fs: "precision mediump float; uniform vec2 u_resolution; uniform float u_time; uniform vec3 u_color1; uniform vec3 u_color2; uniform vec3 u_color3; uniform float u_scale; void main() { vec2 uv = gl_FragCoord.xy / u_resolution.xy; float t = u_time * 0.2; float v = sin(uv.x * 3.0 * u_scale + t) * 0.5 + 0.5; v = mix(v, sin(uv.y * 2.0 * u_scale - t * 0.7) * 0.5 + 0.5, 0.5); vec3 col = mix(u_color1, u_color2, v); col = mix(col, u_color3, sin((uv.x + uv.y) * 2.0 * u_scale + t) * 0.5 + 0.5); gl_FragColor = vec4(col, 1.0); }"
        },
        vibrantWaves: {
            vs: "attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }",
            fs: "precision mediump float; uniform vec2 u_resolution; uniform float u_time; uniform vec3 u_color1; uniform vec3 u_color2; uniform vec3 u_color3; uniform float u_scale; float hash(vec2 p){ return fract(sin(dot(p, vec2(127.1,311.7)))*43758.5453); } float vnoise(vec2 p){ vec2 i=floor(p); vec2 f=fract(p); f=f*f*f*(f*(f*6.0-15.0)+10.0); float a=hash(i); float b=hash(i+vec2(1.0,0.0)); float c=hash(i+vec2(0.0,1.0)); float d=hash(i+vec2(1.0,1.0)); return mix(mix(a,b,f.x),mix(c,d,f.x),f.y); } float fbm(vec2 p){ float v=0.0; float a=0.5; mat2 m=mat2(1.6,1.2,-1.2,1.6); for(int i=0;i<5;i++){ v+=a*vnoise(p); p=m*p; a*=0.5; } return v; } void main(){ vec2 uv=gl_FragCoord.xy/u_resolution.xy; float aspect=u_resolution.x/u_resolution.y; vec2 p=vec2(uv.x*aspect, uv.y); float t=u_time*0.12; float s=u_scale; float warp=fbm(p*0.7*s+vec2(t*0.3,-t*0.2)); float axis=dot(p, normalize(vec2(1.0,0.85))); float bandCenter=1.0+0.4*sin(t*0.6); float arc=sin(p.x*1.4+t*0.5)*0.22; float a=(axis-bandCenter-arc-(warp-0.5)*0.8)/max(0.3,s); vec3 col=mix(u_color2,u_color3,smoothstep(-0.45,0.55,a)); col+=u_color2*exp(-a*a*6.0)*0.18; float core=exp(-a*a*14.0); col=mix(col,u_color1,clamp(core,0.0,1.0)); col+=u_color1*exp(-a*a*45.0)*0.5; gl_FragColor=vec4(col,1.0); }"
        },
        radiantRings: {
            vs: "attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }",
            fs: "precision mediump float; uniform vec2 u_resolution; uniform float u_time; uniform vec3 u_color1; uniform vec3 u_color2; uniform vec3 u_color3; uniform float u_scale; void main(){ vec2 uv=gl_FragCoord.xy/u_resolution.xy; float aspect=u_resolution.x/u_resolution.y; vec2 p=vec2(uv.x*aspect, uv.y); vec2 center=vec2(0.5*aspect, -0.08); float t=u_time*0.12; float s=u_scale; float d=distance(p, center); float level=clamp(1.0-d*0.6, 0.0, 1.0); float ri=d*6.0*s - t; float band=fract(ri); vec3 col=mix(u_color3, u_color2, level*0.85); col*=0.8+0.2*smoothstep(0.0,0.2,band); float rim=smoothstep(0.85,1.0,band); col=mix(col, u_color1, rim*level*0.7); col+=u_color2*pow(level,2.2)*0.45; gl_FragColor=vec4(col,1.0); }"
        },
        aurora: {
            vs: "attribute vec2 a_position; void main() { gl_Position = vec4(a_position, 0.0, 1.0); }",
            fs: "precision mediump float; uniform vec2 u_resolution; uniform float u_time; uniform vec3 u_color1; uniform vec3 u_color2; uniform vec3 u_color3; uniform vec3 u_color4; uniform vec3 u_color5; uniform float u_scale; float hash(vec2 p){ return fract(sin(dot(p, vec2(127.1,311.7)))*43758.5453); } float vnoise(vec2 p){ vec2 i=floor(p); vec2 f=fract(p); f=f*f*f*(f*(f*6.0-15.0)+10.0); float a=hash(i); float b=hash(i+vec2(1.0,0.0)); float c=hash(i+vec2(0.0,1.0)); float d=hash(i+vec2(1.0,1.0)); return mix(mix(a,b,f.x),mix(c,d,f.x),f.y); } float fbm(vec2 p){ float v=0.0; float a=0.5; mat2 m=mat2(1.6,1.2,-1.2,1.6); for(int i=0;i<5;i++){ v+=a*vnoise(p); p=m*p; a*=0.5; } return v; } float wave(vec2 p, float t, float freq, float amp, float phase){ return sin(p.x*freq+t*0.6+phase)*amp+sin(p.x*freq*1.7+t*0.4+phase*1.3)*amp*0.5+sin(p.x*freq*0.6+t*0.8+phase*0.7)*amp*0.3; } void main(){ vec2 uv=gl_FragCoord.xy/u_resolution.xy; float aspect=u_resolution.x/u_resolution.y; vec2 p=vec2(uv.x*aspect, uv.y); float t=u_time*0.15; float s=u_scale; vec2 q; q.x=fbm(p*1.2*s+vec2(0.0, t*0.25)); q.y=fbm(p*1.2*s+vec2(5.2, t*0.18)); vec2 r; r.x=fbm(p*1.5*s+3.0*q+vec2(t*0.1, 0.0)); r.y=fbm(p*1.5*s+3.0*q+vec2(0.0, t*0.12)); float warp=(r.x-0.5)*0.35+(r.y-0.5)*0.25; float y=uv.y+warp; float band1Y=0.78+wave(p, t, 2.0*s, 0.06, 0.0); float band2Y=0.62+wave(p, t, 1.7*s, 0.07, 1.5); float band3Y=0.45+wave(p, t, 2.3*s, 0.05, 3.0); float band4Y=0.28+wave(p, t, 1.9*s, 0.06, 4.5); float band5Y=0.12+wave(p, t, 2.1*s, 0.04, 2.0); float b1=smoothstep(0.12, 0.0, abs(y-band1Y)); float b2=smoothstep(0.14, 0.0, abs(y-band2Y)); float b3=smoothstep(0.13, 0.0, abs(y-band3Y)); float b4=smoothstep(0.15, 0.0, abs(y-band4Y)); float b5=smoothstep(0.12, 0.0, abs(y-band5Y)); float glow1=smoothstep(0.3, 0.0, abs(y-band1Y)); float glow2=smoothstep(0.32, 0.0, abs(y-band2Y)); float glow3=smoothstep(0.35, 0.0, abs(y-band3Y)); float glow4=smoothstep(0.33, 0.0, abs(y-band4Y)); float glow5=smoothstep(0.3, 0.0, abs(y-band5Y)); float centerGlow=smoothstep(0.25, 0.0, abs(y-0.5))*smoothstep(0.4, 0.0, abs(uv.x-0.5)); vec3 col=vec3(0.0); col+=u_color2*glow1*0.35; col+=u_color3*glow2*0.32; col+=u_color4*glow3*0.38; col+=u_color4*glow4*0.25; col+=u_color5*glow5*0.30; col+=u_color1*centerGlow*0.45; col+=u_color2*b1*0.5; col+=u_color3*b2*0.5; col+=u_color4*b3*0.55; col+=u_color4*b4*0.4; col+=u_color5*b5*0.45; float n=fbm(p*0.8*s+4.0*r); col=mix(col, u_color3, smoothstep(0.4, 0.6, n)*0.08); col=mix(col, u_color4, smoothstep(0.3, 0.5, r.x)*0.06); col*=0.85+0.15*smoothstep(0.0, 0.5, n); float vig=1.0-smoothstep(0.45, 1.0, distance(uv, vec2(0.5, 0.5))); col*=0.75+0.25*vig; gl_FragColor=vec4(col, 1.0); }"
        }
    };
    function compile(gl, source, type) {
        var s = gl.createShader(type);
        gl.shaderSource(s, source);
        gl.compileShader(s);
        if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) { gl.deleteShader(s); return null; }
        return s;
    }
    function createProgram(gl, name) {
        var src = shaders[name];
        if (!src) return null;
        var vs = compile(gl, src.vs, gl.VERTEX_SHADER);
        var fs = compile(gl, src.fs, gl.FRAGMENT_SHADER);
        if (!vs || !fs) return null;
        var p = gl.createProgram();
        gl.attachShader(p, vs);
        gl.attachShader(p, fs);
        gl.linkProgram(p);
        if (!gl.getProgramParameter(p, gl.LINK_STATUS)) return null;
        return p;
    }
    function hexToRgb(hex) {
        var h = parseInt(hex.replace('#', ''), 16);
        return [((h >> 16) & 255) / 255, ((h >> 8) & 255) / 255, (h & 255) / 255];
    }
    function init(canvas) {
        var gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
        if (!gl) return;
        var preset = canvas.dataset.mpPreset;
        var program = createProgram(gl, preset);
        if (!program) return;
        var posBuf = gl.createBuffer();
        gl.bindBuffer(gl.ARRAY_BUFFER, posBuf);
        gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1,1,-1,-1,1,-1,1,1,-1,1,1]), gl.STATIC_DRAW);
        var posLoc = gl.getAttribLocation(program, 'a_position');
        var resLoc = gl.getUniformLocation(program, 'u_resolution');
        var timeLoc = gl.getUniformLocation(program, 'u_time');
        var c1Loc = gl.getUniformLocation(program, 'u_color1');
        var c2Loc = gl.getUniformLocation(program, 'u_color2');
        var c3Loc = gl.getUniformLocation(program, 'u_color3');
        var c4Loc = gl.getUniformLocation(program, 'u_color4');
        var c5Loc = gl.getUniformLocation(program, 'u_color5');
        var scaleLoc = gl.getUniformLocation(program, 'u_scale');
        var colors = (canvas.dataset.mpColors || '#000000,#000000,#000000').split(',');
        var speed = parseFloat(canvas.dataset.mpSpeed) || 10;
        var scale = parseFloat(canvas.dataset.mpScale) || 1;
        var start = performance.now();
        function resize() {
            var rect = canvas.getBoundingClientRect();
            var dpr = Math.min(window.devicePixelRatio || 1, 2);
            canvas.width = Math.max(1, Math.floor(rect.width * dpr));
            canvas.height = Math.max(1, Math.floor(rect.height * dpr));
            gl.viewport(0, 0, canvas.width, canvas.height);
        }
        var ro = new ResizeObserver(resize);
        ro.observe(canvas);
        resize();
        function render() {
            var rect = canvas.getBoundingClientRect();
            if (rect.width && rect.height) {
                gl.viewport(0, 0, canvas.width, canvas.height);
                gl.useProgram(program);
                gl.enableVertexAttribArray(posLoc);
                gl.bindBuffer(gl.ARRAY_BUFFER, posBuf);
                gl.vertexAttribPointer(posLoc, 2, gl.FLOAT, false, 0, 0);
                gl.uniform2f(resLoc, canvas.width, canvas.height);
                gl.uniform1f(timeLoc, (performance.now() - start) / 1000 * (speed / 10));
                gl.uniform3fv(c1Loc, hexToRgb(colors[0]));
                gl.uniform3fv(c2Loc, hexToRgb(colors[1]));
                gl.uniform3fv(c3Loc, hexToRgb(colors[2]));
                if (c4Loc) gl.uniform3fv(c4Loc, hexToRgb(colors[3] || '#ec4899'));
                if (c5Loc) gl.uniform3fv(c5Loc, hexToRgb(colors[4] || '#f59e0b'));
                gl.uniform1f(scaleLoc, scale);
                gl.drawArrays(gl.TRIANGLES, 0, 6);
            }
            requestAnimationFrame(render);
        }
        render();
    }
    document.querySelectorAll('.mp-webgl-canvas').forEach(init);
})();
</script>
JS;
    }

    private function renderTextTimeline(array $props): string
    {
        $items = is_array($props['items'] ?? null) ? $props['items'] : [];
        if (empty($items)) {
            return '';
        }

        $metaWidth = is_numeric($props['metaWidth'] ?? null) ? max(120, (int) $props['metaWidth']) : 170;
        $contentGap = is_numeric($props['contentGap'] ?? null) ? max(0, (int) $props['contentGap']) : 34;
        $entryGap = is_numeric($props['gap'] ?? null) ? max(0, (int) $props['gap']) : 34;
        $markerShape = ($props['markerShape'] ?? '') === 'square' ? 'square' : 'circle';
        $markerSize = is_numeric($props['markerSize'] ?? null) ? max(8, (int) $props['markerSize']) : 14;
        $railColor = is_string($props['railColor'] ?? null) && trim($props['railColor']) !== '' ? trim($props['railColor']) : '#d1d5db';
        $markerColor = is_string($props['markerColor'] ?? null) && trim($props['markerColor']) !== '' ? trim($props['markerColor']) : '#111827';
        $markerBorderColor = is_string($props['markerBorderColor'] ?? null) && trim($props['markerBorderColor']) !== '' ? trim($props['markerBorderColor']) : '#ffffff';
        $cardColor = is_string($props['cardColor'] ?? null) && trim($props['cardColor']) !== '' ? trim($props['cardColor']) : '#ffffff';
        $cardBorderColor = is_string($props['cardBorderColor'] ?? null) && trim($props['cardBorderColor']) !== '' ? trim($props['cardBorderColor']) : '#f1f1f1';
        $cardRadius = is_numeric($props['cardRadius'] ?? null) ? max(0, (int) $props['cardRadius']) : 0;
        $cardPadT = is_numeric($props['cardPaddingTop'] ?? null) ? (int) $props['cardPaddingTop'] : 42;
        $cardPadR = is_numeric($props['cardPaddingRight'] ?? null) ? (int) $props['cardPaddingRight'] : 48;
        $cardPadB = is_numeric($props['cardPaddingBottom'] ?? null) ? (int) $props['cardPaddingBottom'] : 42;
        $cardPadL = is_numeric($props['cardPaddingLeft'] ?? null) ? (int) $props['cardPaddingLeft'] : 48;
        $titleColor = is_string($props['titleColor'] ?? null) && trim($props['titleColor']) !== '' ? trim($props['titleColor']) : '#666666';
        $titleFontSize = is_numeric($props['titleFontSize'] ?? null) ? (int) $props['titleFontSize'] : 28;
        $titleFontFamily = is_string($props['titleFontFamily'] ?? null) && trim($props['titleFontFamily']) !== '' ? trim($props['titleFontFamily']) : '';
        $titleFontWeight = is_string($props['titleFontWeight'] ?? null) && trim($props['titleFontWeight']) !== '' ? trim($props['titleFontWeight']) : '';
        $descColor = is_string($props['descriptionColor'] ?? null) && trim($props['descriptionColor']) !== '' ? trim($props['descriptionColor']) : '#777777';
        $descFontSize = is_numeric($props['descriptionFontSize'] ?? null) ? (int) $props['descriptionFontSize'] : 16;
        $descFontFamily = is_string($props['descriptionFontFamily'] ?? null) && trim($props['descriptionFontFamily']) !== '' ? trim($props['descriptionFontFamily']) : '';
        $descFontWeight = is_string($props['descriptionFontWeight'] ?? null) && trim($props['descriptionFontWeight']) !== '' ? trim($props['descriptionFontWeight']) : '';
        $metaColor = is_string($props['metaColor'] ?? null) && trim($props['metaColor']) !== '' ? trim($props['metaColor']) : '#777777';
        $metaFontSize = is_numeric($props['metaFontSize'] ?? null) ? (int) $props['metaFontSize'] : 15;
        $metaFontFamily = is_string($props['metaFontFamily'] ?? null) && trim($props['metaFontFamily']) !== '' ? trim($props['metaFontFamily']) : '';
        $metaFontWeight = is_string($props['metaFontWeight'] ?? null) && trim($props['metaFontWeight']) !== '' ? trim($props['metaFontWeight']) : '';
        $versionBg = is_string($props['versionBackgroundColor'] ?? null) && trim($props['versionBackgroundColor']) !== '' ? trim($props['versionBackgroundColor']) : '#f1f1f1';
        $versionColor = is_string($props['versionColor'] ?? null) && trim($props['versionColor']) !== '' ? trim($props['versionColor']) : '#555555';

        $perPage = is_numeric($props['perPage'] ?? null) ? max(1, (int) $props['perPage']) : 20;
        $showLoadMore = ($props['showLoadMore'] ?? null) !== false;
        $loadMoreText = is_string($props['loadMoreText'] ?? null) && trim($props['loadMoreText']) !== '' ? trim($props['loadMoreText']) : 'Read More';
        $loadMoreBg = is_string($props['loadMoreBackgroundColor'] ?? null) && trim($props['loadMoreBackgroundColor']) !== '' ? trim($props['loadMoreBackgroundColor']) : '#111827';
        $loadMoreColor = is_string($props['loadMoreColor'] ?? null) && trim($props['loadMoreColor']) !== '' ? trim($props['loadMoreColor']) : '#ffffff';
        $loadMoreHoverBg = is_string($props['loadMoreHoverBackground'] ?? null) && trim($props['loadMoreHoverBackground']) !== '' ? trim($props['loadMoreHoverBackground']) : '#374151';
        $loadMoreBorderRadius = is_numeric($props['loadMoreBorderRadius'] ?? null) ? max(0, (int) $props['loadMoreBorderRadius']) : 6;

        $padT = is_numeric($props['paddingTop'] ?? null) ? (int) $props['paddingTop'] : 12;
        $padR = is_numeric($props['paddingRight'] ?? null) ? (int) $props['paddingRight'] : 0;
        $padB = is_numeric($props['paddingBottom'] ?? null) ? (int) $props['paddingBottom'] : 12;
        $padL = is_numeric($props['paddingLeft'] ?? null) ? (int) $props['paddingLeft'] : 0;
        $marT = is_numeric($props['marginTop'] ?? null) ? (int) $props['marginTop'] : 0;
        $marR = is_numeric($props['marginRight'] ?? null) ? (int) $props['marginRight'] : 0;
        $marB = is_numeric($props['marginBottom'] ?? null) ? (int) $props['marginBottom'] : 0;
        $marL = is_numeric($props['marginLeft'] ?? null) ? (int) $props['marginLeft'] : 0;
        $bgColor = is_string($props['backgroundColor'] ?? null) && trim($props['backgroundColor']) !== '' ? trim($props['backgroundColor']) : '';

        $wrapperStyle = 'position:relative;'
            . 'padding:' . $padT . 'px ' . $padR . 'px ' . $padB . 'px ' . $padL . 'px;'
            . 'margin:' . $marT . 'px ' . $marR . 'px ' . $marB . 'px ' . $marL . 'px;';
        if ($bgColor !== '') {
            $wrapperStyle .= 'background-color:' . e($bgColor) . ';';
        }

        $metaStyleBase = 'color:' . e($metaColor) . ';font-size:' . $metaFontSize . 'px;'
            . ($metaFontFamily !== '' ? 'font-family:' . e($metaFontFamily) . ';' : '')
            . ($metaFontWeight !== '' ? 'font-weight:' . e($metaFontWeight) . ';' : '');

        $titleStyleBase = 'color:' . e($titleColor) . ';font-size:' . $titleFontSize . 'px;'
            . ($titleFontFamily !== '' ? 'font-family:' . e($titleFontFamily) . ';' : '')
            . ($titleFontWeight !== '' ? 'font-weight:' . e($titleFontWeight) . ';' : '');

        $descStyleBase = 'color:' . e($descColor) . ';font-size:' . $descFontSize . 'px;'
            . ($descFontFamily !== '' ? 'font-family:' . e($descFontFamily) . ';' : '')
            . ($descFontWeight !== '' ? 'font-weight:' . e($descFontWeight) . ';' : '');

        $markerRadius = $markerShape === 'square' ? '1px' : '50%';

        $tagStyles = [
            'NEW'       => 'background:#dcfce7;color:#166534;',
            'FIX'       => 'background:#fee2e2;color:#991b1b;',
            'UPDATE'    => 'background:#dbeafe;color:#1e40af;',
            'IMPROVED'  => 'background:#fef9c3;color:#854d0e;',
            'DEPRECATED'=> 'background:#f3e8ff;color:#6b21a8;',
            'REMOVED'   => 'background:#ffe4e6;color:#9f1239;',
        ];
        $defaultTagStyle = 'background:#f3f4f6;color:#374151;';

        $containerId = 'mp-ttl-' . substr(md5(uniqid('ttl_', true)), 0, 8);
        $totalCount = count($items);
        $entriesHtml = '';

        foreach ($items as $idx => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $version = is_string($entry['version'] ?? null) ? trim($entry['version']) : ('1.0.' . $idx);
            $date = is_string($entry['date'] ?? null) ? trim($entry['date']) : 'Date';
            $events = is_array($entry['events'] ?? null) ? $entry['events'] : [];

            $hidden = $idx >= $perPage ? ' display:none;' : '';

            $metaHtml = '<div style="' . $metaStyleBase . 'padding-top:5px;padding-right:12px;text-align:right;white-space:normal;">'
                . '<div style="display:inline-block;padding:7px 12px;background-color:' . e($versionBg) . ';color:' . e($versionColor) . ';">' . e($version) . '</div>'
                . '<div style="margin-top:8px;">' . e($date) . '</div>'
                . '</div>';

            $eventsHtml = '';
            foreach ($events as $evIdx => $ev) {
                if (!is_array($ev)) {
                    continue;
                }
                $tag = is_string($ev['tag'] ?? null) ? strtoupper(trim($ev['tag'])) : 'NEW';
                $evTitle = is_string($ev['title'] ?? null) ? trim($ev['title']) : 'Event title';
                $evDesc = is_string($ev['description'] ?? null) ? trim($ev['description']) : '';
                $tagStyle = $tagStyles[$tag] ?? $defaultTagStyle;

                $evStyle = $evIdx > 0 ? 'margin-top:20px;padding-top:20px;border-top:1px solid ' . e($cardBorderColor) . ';' : '';

                $eventsHtml .= '<div style="' . $evStyle . '">'
                    . '<div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">'
                    . '<span style="font-size:11px;font-weight:700;letter-spacing:0.05em;text-transform:uppercase;padding:3px 8px;border-radius:4px;line-height:1.4;' . $tagStyle . '">' . e($tag) . '</span>'
                    . '</div>'
                    . '<div style="' . $titleStyleBase . '">' . e($evTitle) . '</div>';

                if ($evDesc !== '') {
                    $eventsHtml .= '<div style="' . $descStyleBase . 'margin-top:8px;white-space:pre-wrap;">' . e($evDesc) . '</div>';
                }
                $eventsHtml .= '</div>';
            }

            if ($eventsHtml === '') {
                $eventsHtml = '<div style="' . $titleStyleBase . '">Timeline title</div>';
            }

            $cardHtml = '<div style="position:relative;background-color:' . e($cardColor) . ';border:1px solid ' . e($cardBorderColor) . ';border-radius:' . $cardRadius . 'px;padding:' . $cardPadT . 'px ' . $cardPadR . 'px ' . $cardPadB . 'px ' . $cardPadL . 'px;min-width:0;">'
                . '<span style="position:absolute;left:' . (-$contentGap / 2 - $markerSize / 2) . 'px;top:' . ($markerSize / 2) . 'px;width:' . $markerSize . 'px;height:' . $markerSize . 'px;box-sizing:border-box;background-color:' . e($markerColor) . ';border:3px solid ' . e($markerBorderColor) . ';border-radius:' . $markerRadius . ';z-index:1;"></span>'
                . $eventsHtml
                . '</div>';

            $rowStyle = 'position:relative;display:grid;grid-template-columns:' . $metaWidth . 'px minmax(0,1fr);gap:' . $contentGap . 'px;align-items:start;' . $hidden;

            $entriesHtml .= '<div class="mp-text-timeline__row" data-idx="' . $idx . '" style="' . $rowStyle . '">'
                . $metaHtml . $cardHtml
                . '</div>';
        }

        $innerStyle = 'display:flex;flex-direction:column;gap:' . $entryGap . 'px;';

        $buttonHtml = '';
        if ($showLoadMore && $totalCount > $perPage) {
            $buttonHtml = '<div id="' . $containerId . '-btn-wrap" style="display:flex;justify-content:center;margin-top:' . $entryGap . 'px;padding-left:' . $metaWidth . 'px;">'
                . '<button type="button" id="' . $containerId . '-btn" style="padding:10px 28px;background-color:' . e($loadMoreBg) . ';color:' . e($loadMoreColor) . ';border:none;border-radius:' . $loadMoreBorderRadius . 'px;font-size:15px;font-weight:600;cursor:pointer;" '
                . 'onmouseenter="this.style.backgroundColor=\'' . e($loadMoreHoverBg) . '\'" '
                . 'onmouseleave="this.style.backgroundColor=\'' . e($loadMoreBg) . '\'" '
                . 'onclick="(function(){var c=document.getElementById(\'' . $containerId . '\');var rows=c.querySelectorAll(\'.mp-text-timeline__row\');var pg=' . $perPage . ';var shown=0;for(var i=0;i<rows.length;i++){if(rows[i].style.display==\'none\'){rows[i].style.display=\'\';shown++;if(shown>=pg)break;}}var hidden=0;for(var i=0;i<rows.length;i++){if(rows[i].style.display===\'none\')hidden++;}if(hidden===0){var bw=document.getElementById(\'' . $containerId . '-btn-wrap\');if(bw)bw.style.display=\'none\';}})();">'
                . e($loadMoreText)
                . '</button>'
                . '</div>';
        }

        return '<div class="mp-text-timeline" id="' . $containerId . '" style="' . $wrapperStyle . '">'
            . '<div style="position:relative;">'
            . '<div aria-hidden="true" class="mp-text-timeline__rail" style="position:absolute;left:' . ($metaWidth + $contentGap / 2) . 'px;top:' . $markerSize . 'px;bottom:' . $markerSize . 'px;width:2px;background-color:' . e($railColor) . ';"></div>'
            . '<div style="' . $innerStyle . '">' . $entriesHtml . '</div>'
            . '</div>'
            . $buttonHtml
            . '</div>';
    }
}
