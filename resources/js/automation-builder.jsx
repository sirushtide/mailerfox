import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import ReactFlow, {
    addEdge,
    Background,
    Controls,
    Handle,
    MarkerType,
    MiniMap,
    Position,
    ReactFlowProvider,
    SmoothStepEdge,
    useEdgesState,
    useNodesState,
    useReactFlow,
} from 'reactflow';
import 'reactflow/dist/style.css';

const TRIGGER_LABELS = {
    subscriber_added: 'Subscriber added',
    subscriber_confirmed: 'Subscribed',
    subscriber_unsubscribed: 'Unsubscribed',
    webhook_received: 'Webhook received',
    wp_user_registered: 'WP user registered',
    wp_user_updated: 'WP user updated',
    woo_customer_created: 'Woo customer created',
    woo_order_created: 'Woo order created',
    woo_order_paid: 'Woo order paid',
    woo_order_completed: 'Woo order completed',
    woo_order_refunded: 'Woo order refunded',
    woo_order_cancelled: 'Woo order cancelled',
    woo_abandoned_checkout: 'Woo abandoned checkout',
    campaign_opened: 'Campaign opened',
    campaign_clicked: 'Campaign clicked',
    campaign_replied: 'Campaign replied',
    campaign_not_opened: 'Campaign not opened',
    campaign_not_replied: 'Campaign not replied',
    campaign_opened_not_clicked: 'Opened but not clicked',
    subscriber_birthday: 'Subscriber birthday',
    subscriber_anniversary: 'Subscriber anniversary',
    scheduled: 'Scheduled',
};

const TRIGGER_CATEGORIES = [
    {
        type: 'List',
        options: [
            { value: 'subscriber_added', label: TRIGGER_LABELS.subscriber_added },
            { value: 'subscriber_confirmed', label: TRIGGER_LABELS.subscriber_confirmed },
            { value: 'subscriber_unsubscribed', label: TRIGGER_LABELS.subscriber_unsubscribed },
        ],
    },
    {
        type: 'WordPress',
        options: [
            { value: 'wp_user_registered', label: TRIGGER_LABELS.wp_user_registered },
            { value: 'wp_user_updated', label: TRIGGER_LABELS.wp_user_updated },
        ],
    },
    {
        type: 'WooCommerce',
        options: [
            { value: 'woo_customer_created', label: TRIGGER_LABELS.woo_customer_created },
            { value: 'woo_order_created', label: TRIGGER_LABELS.woo_order_created },
            { value: 'woo_order_paid', label: TRIGGER_LABELS.woo_order_paid },
            { value: 'woo_order_completed', label: TRIGGER_LABELS.woo_order_completed },
            { value: 'woo_order_refunded', label: TRIGGER_LABELS.woo_order_refunded },
            { value: 'woo_order_cancelled', label: TRIGGER_LABELS.woo_order_cancelled },
            { value: 'woo_abandoned_checkout', label: TRIGGER_LABELS.woo_abandoned_checkout },
        ],
    },
    {
        type: 'Webhook',
        options: [
            { value: 'webhook_received', label: TRIGGER_LABELS.webhook_received },
        ],
    },
    {
        type: 'Campaigns',
        options: [
            { value: 'campaign_opened', label: TRIGGER_LABELS.campaign_opened },
            { value: 'campaign_clicked', label: TRIGGER_LABELS.campaign_clicked },
            { value: 'campaign_replied', label: TRIGGER_LABELS.campaign_replied },
            { value: 'campaign_not_opened', label: TRIGGER_LABELS.campaign_not_opened },
            { value: 'campaign_not_replied', label: TRIGGER_LABELS.campaign_not_replied },
            { value: 'campaign_opened_not_clicked', label: TRIGGER_LABELS.campaign_opened_not_clicked },
        ],
    },
    {
        type: 'Dates',
        options: [
            { value: 'subscriber_birthday', label: TRIGGER_LABELS.subscriber_birthday },
            { value: 'subscriber_anniversary', label: TRIGGER_LABELS.subscriber_anniversary },
            { value: 'scheduled', label: TRIGGER_LABELS.scheduled },
        ],
    },
];

function getTriggerLabel(trigger) {
    if (!trigger) return '';
    return TRIGGER_LABELS[trigger] || trigger.replace(/_/g, ' ');
}

function safeJsonParse(raw, fallback) {
    try {
        if (!raw) return fallback;
        const v = JSON.parse(raw);
        return v ?? fallback;
    } catch {
        return fallback;
    }
}

function syncSubtitle(node, { emailLists, campaigns, segments } = {}) {
    if (!node) {
        return '';
    }

    if (node.type === 'trigger') {
        const trigger = node.settings?.trigger || '';
        const label = trigger ? getTriggerLabel(trigger) : 'trigger';

        if (trigger === 'webhook_received') {
            const l = (emailLists || []).find((x) => Number(x.id) === Number(node.settings?.list_id));
            return `${l ? l.name : 'Any source'} • ${label}`;
        }

        if (trigger.startsWith('campaign_')) {
            const c = (campaigns || []).find((x) => Number(x.id) === Number(node.settings?.campaign_id));
            const negativeTriggers = ['campaign_not_opened', 'campaign_not_replied', 'campaign_opened_not_clicked'];
            if (negativeTriggers.includes(trigger)) {
                const v = Number(node.settings?.window_value) || 0;
                const u = node.settings?.window_unit || '';
                return `${c ? c.name : 'No campaign'} • ${label} • after ${v} ${u}`;
            }
            return `${c ? c.name : 'No campaign'} • ${label}`;
        }

        const list = (emailLists || []).find((l) => Number(l.id) === Number(node.settings?.list_id));

        if (trigger === 'scheduled') {
            const mode = node.settings?.schedule_mode || '';
            const time = node.settings?.schedule_time || '';
            let desc = '';
            if (mode === 'every_day') desc = 'Daily';
            else if (mode === 'weekly') {
                const days = (node.settings?.schedule_days || []).map((d) => d.charAt(0).toUpperCase() + d.slice(1, 3)).join(', ');
                desc = days ? `Weekly on ${days}` : 'Weekly';
            }
            else if (mode === 'monthly') desc = `Monthly on day ${node.settings?.schedule_date || 1}`;
            else if (mode === 'every_x_months') desc = `Every ${node.settings?.schedule_months || 3} months on day ${node.settings?.schedule_date || 1}`;
            return `${list ? list.name : 'No list'} • ${label}${desc ? ' • ' + desc : ''}${time ? ' at ' + time : ''}`;
        }

        return `${list ? list.name : 'No list'} • ${label}`;
    }

    if (node.type === 'delay') {
        const v = node.settings?.delay_value;
        const u = node.settings?.delay_unit;
        if (v === undefined || v === null || u === undefined || u === null) {
            return '';
        }
        return `${v} ${u}`;
    }

    if (node.type === 'email') {
        const subject = (node.settings?.subject || '').trim();
        return subject ? subject : 'Email';
    }

    if (node.type === 'run_campaign') {
        const c = (campaigns || []).find((x) => Number(x.id) === Number(node.settings?.campaign_id));
        return c ? `Run ${c.name}` : 'Run campaign';
    }

    if (node.type === 'move_subscribers') {
        const list = (emailLists || []).find((l) => Number(l.id) === Number(node.settings?.target_list_id));
        return list ? `Move to ${list.name}` : 'Move to list';
    }

    if (node.type === 'copy_subscribers') {
        const list = (emailLists || []).find((l) => Number(l.id) === Number(node.settings?.target_list_id));
        return list ? `Copy to ${list.name}` : 'Copy to list';
    }

    if (node.type === 'webhook') {
        const url = (node.settings?.url || '').trim();
        return url ? url : 'Webhook';
    }

    if (node.type === 'condition') {
        const groups = node.settings?.groups;
        if (Array.isArray(groups) && groups.length > 0) {
            const logic = (node.settings?.logic || 'and').toUpperCase();
            const first = groups[0];
            const firstLabel = `${first.field || ''} ${first.operator || ''} ${(first.value || '').trim()}`;
            if (groups.length === 1) {
                return firstLabel || 'Condition';
            }
            return `${firstLabel} ${logic === 'AND' ? '&&' : '||'} ${groups.length - 1} more`;
        }
        const f = node.settings?.field || '';
        const op = node.settings?.operator || '';
        const val = (node.settings?.value || '').trim();
        return (f && op && val) ? `${f} ${op} ${val}` : 'Condition';
    }

    if (node.type === 'add_tag') {
        const tags = (node.settings?.tags || []).join(', ');
        return tags ? `Add: ${tags}` : 'Add tag';
    }

    if (node.type === 'remove_tag') {
        const tags = (node.settings?.tags || []).join(', ');
        return tags ? `Remove: ${tags}` : 'Remove tag';
    }

    if (node.type === 'add_segment') {
        const s = (segments || []).find((x) => Number(x.id) === Number(node.settings?.segment_id));
        return s ? `Add to ${s.name}` : 'Add to segment';
    }

    if (node.type === 'remove_segment') {
        const s = (segments || []).find((x) => Number(x.id) === Number(node.settings?.segment_id));
        return s ? `Remove from ${s.name}` : 'Remove from segment';
    }

    if (node.type === 'update_field') {
        const f = node.settings?.field || '';
        const v = (node.settings?.value || '').trim();
        return (f && v) ? `${f} = ${v}` : 'Update field';
    }

    if (node.type === 'goal') {
        return 'Goal (exit)';
    }

    if (node.type === 'ab_split') {
        const pct = Number(node.settings?.split_a_percent) || 50;
        return `A/B split: A ${pct}% / B ${100 - pct}%`;
    }

    if (node.type === 'wait_until') {
        const mode = node.settings?.mode || 'fixed';
        if (mode === 'fixed') {
            const d = node.settings?.target_date || '';
            return d ? `Wait until ${d}` : 'Wait until';
        }
        const f = node.settings?.field || '';
        return f ? `Wait until ${f}` : 'Wait until field';
    }

    return node.subtitle || '';
}

function uid(prefix) {
    return `${prefix}_${Math.random().toString(16).slice(2)}_${Date.now()}`;
}

function collectDownstreamIds(startId, edgesList) {
    if (!startId) return new Set();
    const out = new Map();
    (edgesList || []).forEach((e) => {
        if (!e?.source || !e?.target) return;
        const s = String(e.source);
        const t = String(e.target);
        if (!out.has(s)) out.set(s, []);
        out.get(s).push(t);
    });

    const seen = new Set();
    const stack = [String(startId)];
    while (stack.length) {
        const cur = String(stack.pop());
        if (seen.has(cur)) continue;
        seen.add(cur);
        const next = out.get(cur) || [];
        next.forEach((n) => {
            if (!seen.has(String(n))) stack.push(String(n));
        });
    }
    return seen;
}

function NodeIcon({ type }) {
    const svgClass = 'w-5 h-5';
    const common = {
        className: svgClass,
        viewBox: '0 0 24 24',
        fill: 'none',
        xmlns: 'http://www.w3.org/2000/svg',
        stroke: 'currentColor',
        strokeWidth: 2,
        strokeLinecap: 'round',
        strokeLinejoin: 'round',
    };

    if (type === 'trigger') {
        return (
            <svg {...common}>
                <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" />
            </svg>
        );
    }
    if (type === 'email') {
        return (
            <svg {...common}>
                <path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7" />
                <rect x="2" y="4" width="20" height="16" rx="2" />
            </svg>
        );
    }
    if (type === 'delay') {
        return (
            <svg {...common}>
                <circle cx="12" cy="12" r="8" />
                <path d="M12 8v5l3 2" />
            </svg>
        );
    }
    if (type === 'condition') {
        return (
            <svg {...common}>
                <line x1="6" x2="6" y1="3" y2="15" />
                <circle cx="18" cy="6" r="3" />
                <circle cx="6" cy="18" r="3" />
                <path d="M18 9a9 9 0 0 1-9 9" />
            </svg>
        );
    }
    if (type === 'webhook') {
        return (
            <svg {...common}>
                <path d="M18 16.98h-5.99c-1.1 0-1.95.94-2.48 1.9A4 4 0 0 1 2 17c.01-.7.2-1.4.57-2" />
                <path d="m6 17 3.13-5.78c.53-.97.1-2.18-.5-3.1a4 4 0 1 1 6.89-4.06" />
                <path d="m12 6 3.13 5.73C15.66 12.7 16.9 13 18 13a4 4 0 0 1 0 8" />
            </svg>
        );
    }
    if (type === 'run_campaign') {
        return (
            <svg {...common}>
                <path d="M11 6a13 13 0 0 0 8.4-2.8A1 1 0 0 1 21 4v12a1 1 0 0 1-1.6.8A13 13 0 0 0 11 14H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" />
                <path d="M6 14a12 12 0 0 0 2.4 7.2 2 2 0 0 0 3.2-2.4A8 8 0 0 1 10 14" />
                <path d="M8 6v8" />
            </svg>
        );
    }
    if (type === 'move_subscribers') {
        return (
            <svg {...common}>
                <path d="m16 3 4 4-4 4" />
                <path d="M20 7H4" />
                <path d="m8 21-4-4 4-4" />
                <path d="M4 17h16" />
            </svg>
        );
    }
    if (type === 'copy_subscribers') {
        return (
            <svg {...common}>
                <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
            </svg>
        );
    }
    if (type === 'add_tag') {
        return (
            <svg {...common}>
                <path d="M12 2H2v10l9 9 9-9-9-9z" />
                <line x1="12" y1="7" x2="12" y2="13" />
                <line x1="9" y1="10" x2="15" y2="10" />
            </svg>
        );
    }
    if (type === 'remove_tag') {
        return (
            <svg {...common}>
                <path d="M12 2H2v10l9 9 9-9-9-9z" />
                <line x1="9" y1="10" x2="15" y2="10" />
            </svg>
        );
    }
    if (type === 'add_segment') {
        return (
            <svg {...common}>
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                <line x1="16" y1="11" x2="22" y2="11" />
                <line x1="19" y1="8" x2="19" y2="14" />
            </svg>
        );
    }
    if (type === 'remove_segment') {
        return (
            <svg {...common}>
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                <line x1="16" y1="11" x2="22" y2="11" />
            </svg>
        );
    }
    if (type === 'update_field') {
        return (
            <svg {...common}>
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
            </svg>
        );
    }
    if (type === 'goal') {
        return (
            <svg {...common}>
                <circle cx="12" cy="12" r="10" />
                <polyline points="20 6 9 17 4 12" />
            </svg>
        );
    }
    if (type === 'ab_split') {
        return (
            <svg {...common}>
                <path d="M16 3h3v3h-3z" />
                <path d="M8 3h3v3H8z" />
                <path d="M12 13V6" />
                <path d="M4 16v-3a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3" />
                <path d="M6 21v-5" />
                <path d="M18 21v-5" />
            </svg>
        );
    }
    if (type === 'wait_until') {
        return (
            <svg {...common}>
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </svg>
        );
    }

    return <span className="text-xs font-semibold">{String(type || '?').slice(0, 1).toUpperCase()}</span>;
}

function BaseNode({ data, selected }) {
    const isSelected = !!selected;
    const nodeType = String(data?.type || '');
    const [menuOpen, setMenuOpen] = useState(false);
    const menuRef = useRef(null);
    const nodeId = data?.id;

    useEffect(() => {
        if (!menuOpen) return;
        const onDocClick = (e) => {
            if (menuRef.current && !menuRef.current.contains(e.target)) {
                setMenuOpen(false);
            }
        };
        document.addEventListener('click', onDocClick);
        return () => document.removeEventListener('click', onDocClick);
    }, [menuOpen]);

    useEffect(() => {
        const onOtherOpen = (e) => {
            if (e.detail !== nodeId && menuOpen) {
                setMenuOpen(false);
            }
        };
        window.addEventListener('node-menu-open', onOtherOpen);
        return () => window.removeEventListener('node-menu-open', onOtherOpen);
    }, [nodeId, menuOpen]);

    // Generate a consistent color for the node type icon background
    const typeColors = {
        trigger: 'bg-blue-50 text-blue-600 border-blue-200',
        email: 'bg-emerald-50 text-emerald-600 border-emerald-200',
        run_campaign: 'bg-violet-50 text-violet-600 border-violet-200',
        delay: 'bg-amber-50 text-amber-600 border-amber-200',
        condition: 'bg-rose-50 text-rose-600 border-rose-200',
        webhook: 'bg-sky-50 text-sky-600 border-sky-200',
        move_subscribers: 'bg-orange-50 text-orange-600 border-orange-200',
        copy_subscribers: 'bg-teal-50 text-teal-600 border-teal-200',
        add_tag: 'bg-lime-50 text-lime-600 border-lime-200',
        remove_tag: 'bg-red-50 text-red-600 border-red-200',
        add_segment: 'bg-cyan-50 text-cyan-600 border-cyan-200',
        remove_segment: 'bg-pink-50 text-pink-600 border-pink-200',
        update_field: 'bg-indigo-50 text-indigo-600 border-indigo-200',
        goal: 'bg-green-50 text-green-600 border-green-200',
        ab_split: 'bg-purple-50 text-purple-600 border-purple-200',
        wait_until: 'bg-yellow-50 text-yellow-600 border-yellow-200',
    };
    const colorClass = typeColors[nodeType] || 'bg-gray-50 text-gray-600 border-gray-200';

    const tagLabels = {
        trigger: 'Starts with',
        email: 'Send email',
        run_campaign: 'Campaign',
        delay: 'Wait',
        condition: 'Condition',
        webhook: 'Webhook',
        move_subscribers: 'Move',
        copy_subscribers: 'Copy',
        add_tag: 'Tags',
        remove_tag: 'Tags',
        add_segment: 'Segment',
        remove_segment: 'Segment',
        update_field: 'Update',
        goal: 'Goal',
        ab_split: 'A/B Test',
        wait_until: 'Wait until',
    };
    const tagLabel = tagLabels[nodeType] || nodeType.replace(/_/g, ' ');

    const isTrigger = nodeType === 'trigger';

    return (
        <div
            className={
                `rounded-xl border bg-white text-sm transition-shadow duration-200 ` +
                (isSelected
                    ? 'border-blue-400 shadow-lg shadow-blue-500/10'
                    : 'border-gray-200 shadow-sm hover:shadow-md')
            }
            style={{ width: 280 }}
        >
            {/* Top pill tag */}
            <div className="px-4 pt-3 pb-0">
                <span className="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-500 uppercase tracking-wide">
                    {tagLabel}
                </span>
            </div>

            {/* Main content */}
            <div className="px-4 py-3">
                <div className="flex items-start gap-3">
                    {/* Icon */}
                    <div className={`shrink-0 w-9 h-9 rounded-lg border flex items-center justify-center ${colorClass}`}>
                        <NodeIcon type={nodeType} />
                    </div>

                    {/* Text */}
                    <div className="flex-1 min-w-0">
                        <div className="text-sm font-semibold text-gray-900 leading-tight">{data?.label || 'Node'}</div>
                        {data?.subtitle ? (
                            <div className="mt-1 text-xs text-gray-400 leading-relaxed line-clamp-2">{data?.subtitle}</div>
                        ) : null}
                    </div>

                    {/* Menu button */}
                    <div className="relative shrink-0 mt-0.5" ref={menuRef}>
                        <button
                            type="button"
                            className="text-gray-300 hover:text-gray-500 transition-colors"
                            onClick={(e) => {
                                e.stopPropagation();
                                setMenuOpen((v) => {
                                    const next = !v;
                                    if (next) {
                                        window.dispatchEvent(new CustomEvent('node-menu-open', { detail: nodeId }));
                                    }
                                    return next;
                                });
                            }}
                        >
                            <svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
                                <circle cx="12" cy="5" r="1" />
                                <circle cx="12" cy="12" r="1" />
                                <circle cx="12" cy="19" r="1" />
                            </svg>
                        </button>
                        {menuOpen ? (
                            <div className="absolute right-0 top-full mt-1.5 w-28 rounded-lg border border-gray-100 bg-white shadow-lg py-1 z-50">
                                {!isTrigger ? (
                                    <button
                                        type="button"
                                        className="w-full text-left px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 transition-colors flex items-center gap-2"
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            setMenuOpen(false);
                                            console.log('DELETE clicked', data?.id, typeof data?.onDelete);
                                            data?.onDelete?.(data?.id);
                                        }}
                                    >
                                        <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                            <polyline points="3 6 5 6 21 6" />
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                        </svg>
                                        Delete
                                    </button>
                                ) : null}
                                <button
                                    type="button"
                                    className="w-full text-left px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-50 transition-colors flex items-center gap-2"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        setMenuOpen(false);
                                        console.log('DUPLICATE clicked', data?.id, typeof data?.onDuplicate);
                                        data?.onDuplicate?.(data?.id);
                                    }}
                                >
                                    <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                                    </svg>
                                    Duplicate
                                </button>
                            </div>
                        ) : null}
                    </div>
                </div>
            </div>

            {/* Branch buttons for condition / ab_split */}
            {data?.type === 'condition' ? (
                <div className="px-4 pb-3 flex items-center gap-2">
                    <button
                        type="button"
                        className="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 transition-colors"
                        onClick={(e) => {
                            e.stopPropagation();
                            data?.onInsert?.(data?.id, 'yes', { x: e.clientX, y: e.clientY });
                        }}
                    >
                        <span>Yes</span>
                        <svg className="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5l7 7-7 7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        className="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100 transition-colors"
                        onClick={(e) => {
                            e.stopPropagation();
                            data?.onInsert?.(data?.id, 'no', { x: e.clientX, y: e.clientY });
                        }}
                    >
                        <span>No</span>
                        <svg className="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            ) : null}
            {data?.type === 'ab_split' ? (
                <div className="px-4 pb-3 flex items-center gap-2">
                    <button
                        type="button"
                        className="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 transition-colors"
                        onClick={(e) => {
                            e.stopPropagation();
                            data?.onInsert?.(data?.id, 'a', { x: e.clientX, y: e.clientY });
                        }}
                    >
                        <span>Path A</span>
                        <svg className="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5l7 7-7 7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        className="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 transition-colors"
                        onClick={(e) => {
                            e.stopPropagation();
                            data?.onInsert?.(data?.id, 'b', { x: e.clientX, y: e.clientY });
                        }}
                    >
                        <span>Path B</span>
                        <svg className="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            ) : null}

            {/* Add button */}
            {data?.type !== 'ab_split' ? (
                <button
                    type="button"
                    className="absolute left-1/2 -bottom-3.5 -translate-x-1/2 inline-flex items-center justify-center w-7 h-7 rounded-full bg-white border border-gray-300 text-gray-400 shadow-sm hover:border-blue-400 hover:text-blue-500 hover:shadow-md transition-all"
                    onClick={(e) => {
                        e.stopPropagation();
                        data?.onInsert?.(data?.id, null, { x: e.clientX, y: e.clientY });
                    }}
                >
                    <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M5 12h14" />
                        <path d="M12 5v14" />
                    </svg>
                </button>
            ) : null}

            <Handle type="target" position={Position.Top} style={{ opacity: 0 }} />
            <Handle type="source" position={Position.Bottom} style={{ opacity: 0 }} />
            {data?.type === 'condition' ? (
                <>
                    <Handle type="source" id="yes" position={Position.Bottom} style={{ opacity: 0, left: 80 }} />
                    <Handle type="source" id="no" position={Position.Bottom} style={{ opacity: 0, left: 200 }} />
                </>
            ) : null}
            {data?.type === 'ab_split' ? (
                <>
                    <Handle type="source" id="a" position={Position.Bottom} style={{ opacity: 0, left: 80 }} />
                    <Handle type="source" id="b" position={Position.Bottom} style={{ opacity: 0, left: 200 }} />
                </>
            ) : null}
        </div>
    );
}

const nodeTypes = {
    base: BaseNode,
};

function SmoothEdge(props) {
    return <SmoothStepEdge {...props} borderRadius={20} />;
}

const edgeTypes = {
    customSmooth: SmoothEdge,
};

function toFlowNodes(graphNodes, callbacks) {
    const byId = new Map();
    (graphNodes || []).forEach((n) => {
        if (n && n.id) byId.set(n.id, n);
    });

    return (graphNodes || []).map((n) => {
        const pos = {
            x: Number(n?.x) || 0,
            y: Number(n?.y) || 0,
        };
        return {
            id: String(n.id),
            type: 'base',
            position: pos,
            data: {
                id: String(n.id),
                type: n.type,
                label: n.label,
                subtitle: n.subtitle,
                settings: n.settings || {},
                onSelect: callbacks.onSelect,
                onInsert: callbacks.onInsert,
                onDelete: callbacks.onDelete,
                onDuplicate: callbacks.onDuplicate,
            },
        };
    });
}

function toFlowEdges(graphEdges) {
    return (graphEdges || []).map((e) => {
        const id = String(e.id || uid('edge'));
        const branch = e.branch || undefined;
        return {
            id,
            source: String(e.from),
            target: String(e.to),
            sourceHandle: branch || undefined,
            type: 'customSmooth',
            markerEnd: {
                type: MarkerType.ArrowClosed,
                width: 10,
                height: 10,
                color: '#cbd5e1',
            },
            style: {
                stroke: '#cbd5e1',
                strokeWidth: 1.5,
            },
            data: {
                branch,
            },
        };
    });
}

function fromFlow(nodes, edges, graphNodeMetaById) {
    const outNodes = nodes.map((n) => {
        const meta = graphNodeMetaById.get(n.id) || {};
        return {
            id: n.id,
            type: meta.type || n.data?.type || 'action',
            label: meta.label || n.data?.label || 'Node',
            subtitle: meta.subtitle || n.data?.subtitle || '',
            x: n.position?.x ?? 0,
            y: n.position?.y ?? 0,
            settings: meta.settings || n.data?.settings || {},
        };
    });

    const outEdges = edges.map((e) => {
        return {
            id: e.id,
            from: e.source,
            to: e.target,
            branch: e.data?.branch || undefined,
        };
    });

    return { nodes: outNodes, edges: outEdges };
}

function ensureTrigger(graph) {
    const nodes = Array.isArray(graph.nodes) ? [...graph.nodes] : [];
    const edges = Array.isArray(graph.edges) ? [...graph.edges] : [];

    let trigger = nodes.find((n) => n.id === 'trigger_1');
    if (!trigger) {
        trigger = {
            id: 'trigger_1',
            type: 'trigger',
            label: 'Trigger',
            subtitle: 'Start',
            x: 0,
            y: 0,
            settings: { list_id: null, campaign_id: null, trigger: '', window_value: 24, window_unit: 'hours', webhook_token: '' },
        };
        nodes.unshift(trigger);
    }

    return { nodes, edges };
}

function simpleVerticalLayout(graph) {
    const nodeWidth = 280;
    const rowGap = 170;

    const nodes = [...(graph.nodes || [])];
    const edges = [...(graph.edges || [])];

    const byId = new Map(nodes.map((n) => [n.id, n]));
    const outgoing = new Map();
    edges.forEach((e) => {
        if (!outgoing.has(e.from)) outgoing.set(e.from, []);
        outgoing.get(e.from).push(e);
    });

    // Depth via BFS from trigger
    const depth = new Map();
    const lane = new Map();
    const startId = byId.has('trigger_1') ? 'trigger_1' : (nodes[0]?.id || null);
    if (!startId) return { nodes, edges };

    depth.set(startId, 0);
    lane.set(startId, 0);

    const q = [startId];
    while (q.length) {
        const currentId = q.shift();
        const current = byId.get(currentId);
        const outs = outgoing.get(currentId) || [];

        outs.forEach((edge) => {
            if (!byId.has(edge.to)) return;

            const nextDepth = (depth.get(currentId) ?? 0) + 1;
            const prevDepth = depth.get(edge.to);
            if (prevDepth === undefined || nextDepth > prevDepth) {
                depth.set(edge.to, nextDepth);
                q.push(edge.to);
            }

            let nextLane = lane.get(currentId) ?? 0;
            if (current?.type === 'condition') {
                if ((edge.branch || '') === 'yes') nextLane -= 1;
                if ((edge.branch || '') === 'no') nextLane += 1;
            }
            if (current?.type === 'ab_split') {
                if ((edge.branch || '') === 'a') nextLane -= 1;
                if ((edge.branch || '') === 'b') nextLane += 1;
            }

            if (!lane.has(edge.to)) {
                lane.set(edge.to, nextLane);
            }
        });
    }

    // Center lane 0
    const laneStep = 360;
    const centerX = 0;

    // Assign positions
    nodes.forEach((n) => {
        const d = depth.get(n.id) ?? 0;
        const l = lane.get(n.id) ?? 0;
        n.x = centerX + l * laneStep;
        n.y = d * rowGap;
    });

    // Move to positive space
    const padX = 60;
    const padY = 60;

    const anchor = byId.get('trigger_1') || nodes[0] || { x: 0, y: 0 };
    const anchorX = Number(anchor.x) || 0;
    const anchorY = Number(anchor.y) || 0;
    nodes.forEach((n) => {
        n.x = n.x - anchorX + padX;
        n.y = n.y - anchorY + padY;
    });

    return { nodes, edges, nodeWidth };
}

function ConditionGroupEditor({ settings, onChange }) {
    const logic = settings.logic || 'and';
    const groups = Array.isArray(settings.groups) && settings.groups.length > 0
        ? settings.groups
        : settings.field
            ? [{ field: settings.field, operator: settings.operator || 'equals', value: settings.value || '' }]
            : [{ field: '', operator: 'equals', value: '' }];

    const fieldOptions = [
        { value: '', label: 'Select field' },
        { value: 'email', label: 'Subscriber email' },
        { value: 'first_name', label: 'First name' },
        { value: 'last_name', label: 'Last name' },
        { value: 'status', label: 'Subscriber status' },
        { value: 'tags', label: 'Tags' },
        { value: 'custom_fields.plan', label: 'Custom: Plan' },
        { value: 'custom_fields.wp_user_id', label: 'Custom: WP user id' },
        { value: 'custom_fields.woo_customer_id', label: 'Custom: Woo customer id' },
        { value: 'payload.order_id', label: 'Payload: order id' },
        { value: 'payload.total', label: 'Payload: order total' },
        { value: 'payload.currency', label: 'Payload: currency' },
    ];

    const operatorOptions = [
        { value: 'equals', label: 'Equals' },
        { value: 'not_equals', label: 'Not equals' },
        { value: 'contains', label: 'Contains' },
        { value: 'not_contains', label: 'Not contains' },
        { value: 'starts_with', label: 'Starts with' },
        { value: 'ends_with', label: 'Ends with' },
        { value: 'greater_than', label: 'Greater than' },
        { value: 'less_than', label: 'Less than' },
        { value: 'greater_or_equal', label: 'Greater or equal' },
        { value: 'less_or_equal', label: 'Less or equal' },
        { value: 'is_empty', label: 'Is empty' },
        { value: 'is_not_empty', label: 'Is not empty' },
        { value: 'in_list', label: 'In list (comma-separated)' },
    ];

    const updateGroup = (index, patch) => {
        const next = groups.map((g, i) => (i === index ? { ...g, ...patch } : g));
        onChange({ logic, groups: next });
    };

    const addGroup = () => {
        onChange({ logic, groups: [...groups, { field: '', operator: 'equals', value: '' }] });
    };

    const removeGroup = (index) => {
        const next = groups.filter((_, i) => i !== index);
        onChange({ logic, groups: next.length ? next : [{ field: '', operator: 'equals', value: '' }] });
    };

    const showValue = (op) => !['is_empty', 'is_not_empty'].includes(op);

    return (
        <div className="space-y-3">
            <div className="flex items-center gap-2">
                <span className="text-xs font-medium text-gray-700 dark:text-gray-200">Logic</span>
                <div className="inline-flex rounded-md border border-gray-300 dark:border-gray-600 overflow-hidden">
                    <button
                        type="button"
                        className={`px-2 py-1 text-xs ${logic === 'and' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200'}`}
                        onClick={() => onChange({ logic: 'and', groups })}
                    >
                        AND
                    </button>
                    <button
                        type="button"
                        className={`px-2 py-1 text-xs ${logic === 'or' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200'}`}
                        onClick={() => onChange({ logic: 'or', groups })}
                    >
                        OR
                    </button>
                </div>
            </div>

            {groups.map((group, i) => (
                <div key={i} className="rounded-md border border-gray-200 dark:border-gray-700 p-3 space-y-2">
                    <div className="flex items-center justify-between">
                        <span className="text-[11px] font-semibold text-gray-500 dark:text-gray-400">Condition {i + 1}</span>
                        {groups.length > 1 ? (
                            <button
                                type="button"
                                className="text-xs text-red-500 hover:text-red-600"
                                onClick={() => removeGroup(i)}
                            >
                                Remove
                            </button>
                        ) : null}
                    </div>
                    <div>
                        <label className="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Field</label>
                        <select
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                            value={group.field || ''}
                            onChange={(e) => updateGroup(i, { field: e.target.value })}
                        >
                            {fieldOptions.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Operator</label>
                        <select
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                            value={group.operator || 'equals'}
                            onChange={(e) => updateGroup(i, { operator: e.target.value })}
                        >
                            {operatorOptions.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>
                    {showValue(group.operator) ? (
                        <div>
                            <label className="block text-[11px] font-medium text-gray-600 dark:text-gray-300">Value</label>
                            <input
                                type="text"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={group.value || ''}
                                onChange={(e) => updateGroup(i, { value: e.target.value })}
                            />
                        </div>
                    ) : null}
                </div>
            ))}

            <button
                type="button"
                className="w-full rounded-md border border-dashed border-gray-300 dark:border-gray-600 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5"
                onClick={addGroup}
            >
                + Add condition
            </button>
        </div>
    );
}

function SettingsPanel({ selected, setSelected, emailLists, campaigns, templates, deliveryServers, segments, onUpdateSelected, automationId, onDeleteSelected }) {
    if (!selected) {
        return (
            <div className="p-4">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">Settings</h3>
                <div className="mt-3 text-sm text-gray-500 dark:text-gray-400">Select a node to edit its settings.</div>
            </div>
        );
    }

    const setField = (path, value) => {
        onUpdateSelected((n) => {
            const next = { ...n };
            if (path === 'label') next.label = value;
            if (path === 'settings') next.settings = { ...(next.settings || {}), ...(value || {}) };
            return next;
        });
    };

    const trigger = (selected.settings?.trigger || '');

    const generateToken = () => {
        try {
            const bytes = new Uint8Array(24);
            window.crypto.getRandomValues(bytes);
            return Array.from(bytes)
                .map((b) => b.toString(16).padStart(2, '0'))
                .join('');
        } catch {
            return Math.random().toString(16).slice(2) + Math.random().toString(16).slice(2);
        }
    };

    const webhookUrl = useMemo(() => {
        if (!automationId) return '';
        const token = selected.settings?.webhook_token || '';
        const base = `${window.location.origin}/webhooks/automations/${automationId}`;
        return token ? `${base}?token=${encodeURIComponent(token)}` : base;
    }, [automationId, selected.settings?.webhook_token]);

    return (
        <div className="p-4">
            <div className="flex items-start justify-between gap-2">
                <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">Settings</h3>
                <div className="flex items-center gap-2">
                    {selected.id !== 'trigger_1' ? (
                        <button
                            type="button"
                            className="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            onClick={() => onDeleteSelected?.()}
                        >
                            Delete
                        </button>
                    ) : null}
                    <button type="button" className="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-200" onClick={() => setSelected(null)}>
                        Close
                    </button>
                </div>
            </div>

            <div className="mt-3 space-y-4">
                <div>
                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Node label</label>
                    <input
                        type="text"
                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                        value={selected.label || ''}
                        onChange={(e) => setField('label', e.target.value)}
                    />
                </div>

                {selected.type === 'trigger' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Trigger event <span className="text-red-500">*</span></label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={trigger}
                                onChange={(e) => {
                                    const next = e.target.value;
                                    const patch = { trigger: next };

                                    if (next === 'webhook_received' && !selected.settings?.webhook_token) {
                                        patch.webhook_token = generateToken();
                                    }

                                    setField('settings', patch);
                                }}
                            >
                                <option value="">Select trigger</option>
                                {TRIGGER_CATEGORIES.map((group) => (
                                    <optgroup
                                        key={group.type}
                                        label={group.disabled ? `${group.type} (Coming soon)` : group.type}
                                        disabled={!!group.disabled}
                                    >
                                        {group.options.map((opt) => (
                                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                                        ))}
                                    </optgroup>
                                ))}
                            </select>
                        </div>

                        {trigger.startsWith('subscriber_') ? (
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Email list <span className="text-red-500">*</span></label>
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                    value={selected.settings?.list_id || ''}
                                    onChange={(e) => setField('settings', { list_id: e.target.value || null })}
                                >
                                    <option value="">Select list</option>
                                    {(emailLists || []).map((l) => (
                                        <option key={l.id} value={l.id}>{l.name}</option>
                                    ))}
                                </select>
                            </div>
                        ) : null}

                        {(trigger.startsWith('wp_') || trigger.startsWith('woo_')) ? (
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Email list (optional)</label>
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                    value={selected.settings?.list_id || ''}
                                    onChange={(e) => setField('settings', { list_id: e.target.value || null })}
                                >
                                    <option value="">Any list</option>
                                    {(emailLists || []).map((l) => (
                                        <option key={l.id} value={l.id}>{l.name}</option>
                                    ))}
                                </select>
                            </div>
                        ) : null}

                        {trigger === 'webhook_received' ? (
                            <div className="space-y-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Email list (optional)</label>
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                        value={selected.settings?.list_id || ''}
                                        onChange={(e) => setField('settings', { list_id: e.target.value || null })}
                                    >
                                        <option value="">Auto (Webhook contacts)</option>
                                        {(emailLists || []).map((l) => (
                                            <option key={l.id} value={l.id}>{l.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Webhook token <span className="text-red-500">*</span></label>
                                    <div className="mt-1 flex items-center gap-2">
                                        <input
                                            type="text"
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                            value={selected.settings?.webhook_token || ''}
                                            onChange={(e) => setField('settings', { webhook_token: e.target.value })}
                                        />
                                        <button
                                            type="button"
                                            className="shrink-0 rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5"
                                            onClick={() => setField('settings', { webhook_token: generateToken() })}
                                        >
                                            Regenerate
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Webhook URL</label>
                                    <div className="mt-1 flex items-center gap-2">
                                        <input
                                            type="text"
                                            readOnly
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 shadow-sm text-sm text-gray-700 dark:text-gray-200"
                                            value={webhookUrl}
                                        />
                                        <button
                                            type="button"
                                            className="shrink-0 rounded-md border border-gray-200 dark:border-gray-700 px-2 py-1 text-xs text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-white/5"
                                            onClick={() => {
                                                if (!webhookUrl) return;
                                                navigator.clipboard?.writeText(webhookUrl);
                                            }}
                                        >
                                            Copy
                                        </button>
                                    </div>
                                    <div className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Send JSON with at least <code className="px-1">email</code> in the body. If no list is selected, contacts will be stored in an auto-created "Webhook Contacts" list.
                                    </div>
                                </div>
                            </div>
                        ) : null}

                        {trigger.startsWith('campaign_') ? (
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Campaign <span className="text-red-500">*</span></label>
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                    value={selected.settings?.campaign_id || ''}
                                    onChange={(e) => setField('settings', { campaign_id: e.target.value || null })}
                                >
                                    <option value="">Select campaign</option>
                                    {(campaigns || []).map((c) => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                            </div>
                        ) : null}

                        {['campaign_not_opened', 'campaign_not_replied', 'campaign_opened_not_clicked'].includes(trigger) ? (
                            <div className="grid grid-cols-2 gap-2">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Check after <span className="text-red-500">*</span></label>
                                    <input
                                        type="number"
                                        min="1"
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                        value={Number(selected.settings?.window_value || 24)}
                                        onChange={(e) => setField('settings', { window_value: Number(e.target.value || 0) })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Unit <span className="text-red-500">*</span></label>
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                        value={selected.settings?.window_unit || 'hours'}
                                        onChange={(e) => setField('settings', { window_unit: e.target.value })}
                                    >
                                        <option value="minutes">minutes</option>
                                        <option value="hours">hours</option>
                                        <option value="days">days</option>
                                        <option value="weeks">weeks</option>
                                    </select>
                                </div>
                            </div>
                        ) : null}

                        {['subscriber_birthday', 'subscriber_anniversary'].includes(trigger) ? (
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Date field <span className="text-red-500">*</span></label>
                                <select
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                    value={selected.settings?.date_field || ''}
                                    onChange={(e) => setField('settings', { date_field: e.target.value })}
                                >
                                    <option value="">Select custom field</option>
                                    <option value="birthday">birthday</option>
                                    <option value="anniversary">anniversary</option>
                                    <option value="plan_start">plan_start</option>
                                    <option value="wp_user_registered">wp_user_registered</option>
                                </select>
                                <div className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                    Subscribers with this custom field matching today's month/day will enter the automation.
                                </div>
                            </div>
                        ) : null}

                        {trigger === 'scheduled' ? (
                            <div className="space-y-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Email list (optional)</label>
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                        value={selected.settings?.list_id || ''}
                                        onChange={(e) => setField('settings', { list_id: e.target.value || null })}
                                    >
                                        <option value="">None (determined by next step)</option>
                                        {(emailLists || []).map((l) => (
                                            <option key={l.id} value={l.id}>{l.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Schedule mode <span className="text-red-500">*</span></label>
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                        value={selected.settings?.schedule_mode || ''}
                                        onChange={(e) => setField('settings', { schedule_mode: e.target.value })}
                                    >
                                        <option value="">Select schedule</option>
                                        <option value="every_day">Every day</option>
                                        <option value="weekly">Every specific day(s) of week</option>
                                        <option value="monthly">Every month at date</option>
                                        <option value="every_x_months">Every 3/4/6/12 months</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Time of day <span className="text-red-500">*</span></label>
                                    <input
                                        type="time"
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                        value={selected.settings?.schedule_time || '09:00'}
                                        onChange={(e) => setField('settings', { schedule_time: e.target.value })}
                                    />
                                </div>

                                {selected.settings?.schedule_mode === 'weekly' ? (
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Days of week <span className="text-red-500">*</span></label>
                                        <div className="mt-1 flex flex-wrap gap-2">
                                            {[
                                                { key: 'mon', label: 'Mon' },
                                                { key: 'tue', label: 'Tue' },
                                                { key: 'wed', label: 'Wed' },
                                                { key: 'thu', label: 'Thu' },
                                                { key: 'fri', label: 'Fri' },
                                                { key: 'sat', label: 'Sat' },
                                                { key: 'sun', label: 'Sun' },
                                            ].map((d) => {
                                                const days = selected.settings?.schedule_days || [];
                                                const active = days.includes(d.key);
                                                return (
                                                    <button
                                                        key={d.key}
                                                        type="button"
                                                        onClick={() => {
                                                            const next = active
                                                                ? days.filter((x) => x !== d.key)
                                                                : [...days, d.key];
                                                            setField('settings', { schedule_days: next });
                                                        }}
                                                        className={`px-2 py-1 rounded text-xs font-medium border transition-colors ${
                                                            active
                                                                ? 'bg-primary-50 dark:bg-primary-500/20 border-primary-300 dark:border-primary-600 text-primary-700 dark:text-primary-300'
                                                                : 'bg-white dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600'
                                                        }`}
                                                    >
                                                        {d.label}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                ) : null}

                                {(selected.settings?.schedule_mode === 'monthly' || selected.settings?.schedule_mode === 'every_x_months') ? (
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Day of month <span className="text-red-500">*</span></label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="31"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                            value={Number(selected.settings?.schedule_date || 1)}
                                            onChange={(e) => setField('settings', { schedule_date: Math.min(31, Math.max(1, Number(e.target.value || 1))) })}
                                        />
                                    </div>
                                ) : null}

                                {selected.settings?.schedule_mode === 'every_x_months' ? (
                                    <div>
                                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Every N months <span className="text-red-500">*</span></label>
                                        <select
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                            value={Number(selected.settings?.schedule_months || 3)}
                                            onChange={(e) => setField('settings', { schedule_months: Number(e.target.value) })}
                                        >
                                            <option value={3}>Every 3 months</option>
                                            <option value={4}>Every 4 months</option>
                                            <option value={6}>Every 6 months</option>
                                            <option value={12}>Every 12 months</option>
                                        </select>
                                    </div>
                                ) : null}
                            </div>
                        ) : null}
                    </div>
                ) : null}

                {selected.type === 'run_campaign' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Campaign <span className="text-red-500">*</span></label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.campaign_id || ''}
                                onChange={(e) => setField('settings', { campaign_id: e.target.value || null })}
                            >
                                <option value="">Select campaign</option>
                                {(campaigns || []).map((c) => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                ) : null}

                {selected.type === 'move_subscribers' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Move to list <span className="text-red-500">*</span></label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.target_list_id || ''}
                                onChange={(e) => setField('settings', { target_list_id: e.target.value || null })}
                            >
                                <option value="">Select list</option>
                                {(emailLists || []).map((l) => (
                                    <option key={l.id} value={l.id}>{l.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                ) : null}

                {selected.type === 'copy_subscribers' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Copy to list <span className="text-red-500">*</span></label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.target_list_id || ''}
                                onChange={(e) => setField('settings', { target_list_id: e.target.value || null })}
                            >
                                <option value="">Select list</option>
                                {(emailLists || []).map((l) => (
                                    <option key={l.id} value={l.id}>{l.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                ) : null}

                {selected.type === 'delay' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Delay value <span className="text-red-500">*</span></label>
                            <input
                                type="number"
                                min="0"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={Number(selected.settings?.delay_value || 0)}
                                onChange={(e) => setField('settings', { delay_value: Number(e.target.value || 0) })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Delay unit <span className="text-red-500">*</span></label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.delay_unit || ''}
                                onChange={(e) => setField('settings', { delay_unit: e.target.value })}
                            >
                                <option value="">Select unit</option>
                                <option value="minutes">Minutes</option>
                                <option value="hours">Hours</option>
                                <option value="days">Days</option>
                                <option value="weeks">Weeks</option>
                            </select>
                        </div>
                    </div>
                ) : null}

                {selected.type === 'email' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Subject <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.subject || ''}
                                onChange={(e) => setField('settings', { subject: e.target.value })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Template <span className="text-red-500">*</span></label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.template_id || ''}
                                onChange={(e) => setField('settings', { template_id: e.target.value || null })}
                            >
                                <option value="">Select template</option>
                                {(templates || []).map((t) => (
                                    <option key={t.id} value={t.id}>{t.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Delivery server</label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.delivery_server_id || ''}
                                onChange={(e) => setField('settings', { delivery_server_id: e.target.value || null })}
                            >
                                <option value="">Default</option>
                                {(deliveryServers || []).map((s) => (
                                    <option key={s.id} value={s.id}>{s.name}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">From name</label>
                            <input
                                type="text"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.from_name || ''}
                                onChange={(e) => setField('settings', { from_name: e.target.value })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">From email</label>
                            <input
                                type="email"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.from_email || ''}
                                onChange={(e) => setField('settings', { from_email: e.target.value })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Reply-to</label>
                            <input
                                type="email"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.reply_to || ''}
                                onChange={(e) => setField('settings', { reply_to: e.target.value })}
                            />
                        </div>
                        <div className="space-y-2">
                            <label className="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                    checked={!!selected.settings?.track_opens}
                                    onChange={(e) => setField('settings', { track_opens: e.target.checked })}
                                />
                                Track opens
                            </label>
                            <label className="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    className="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                                    checked={!!selected.settings?.track_clicks}
                                    onChange={(e) => setField('settings', { track_clicks: e.target.checked })}
                                />
                                Track clicks
                            </label>
                        </div>
                    </div>
                ) : null}

                {selected.type === 'webhook' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">URL</label>
                            <input
                                type="url"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.url || ''}
                                onChange={(e) => setField('settings', { url: e.target.value })}
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Method</label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.method || 'POST'}
                                onChange={(e) => setField('settings', { method: e.target.value })}
                            >
                                <option value="POST">POST</option>
                                <option value="GET">GET</option>
                                <option value="PUT">PUT</option>
                                <option value="PATCH">PATCH</option>
                            </select>
                        </div>
                    </div>
                ) : null}

                {selected.type === 'condition' ? (
                    <div className="space-y-3">
                        <ConditionGroupEditor
                            settings={selected.settings || {}}
                            onChange={(next) => setField('settings', next)}
                        />
                    </div>
                ) : null}

                {(selected.type === 'add_tag' || selected.type === 'remove_tag') ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Tags <span className="text-red-500">*</span></label>
                            <div className="mt-1 flex flex-wrap gap-2 rounded-md border border-gray-300 dark:border-gray-600 p-2">
                                {(selected.settings?.tags || []).map((tag, i) => (
                                    <span key={i} className="inline-flex items-center gap-1 rounded bg-primary-50 dark:bg-primary-500/20 px-2 py-1 text-xs text-primary-700 dark:text-primary-300">
                                        {tag}
                                        <button
                                            type="button"
                                            className="ml-1 text-primary-500 hover:text-primary-700"
                                            onClick={() => {
                                                const next = [...(selected.settings?.tags || [])];
                                                next.splice(i, 1);
                                                setField('settings', { tags: next });
                                            }}
                                        >
                                            ×
                                        </button>
                                    </span>
                                ))}
                                <input
                                    type="text"
                                    className="flex-1 min-w-[80px] border-0 p-0 text-sm focus:ring-0 dark:bg-transparent dark:text-gray-100"
                                    placeholder="Type tag and press Enter"
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            const v = e.target.value.trim();
                                            if (!v) return;
                                            const next = [...new Set([...(selected.settings?.tags || []), v])];
                                            setField('settings', { tags: next });
                                            e.target.value = '';
                                        }
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                ) : null}

                {(selected.type === 'add_segment' || selected.type === 'remove_segment') ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Segment <span className="text-red-500">*</span></label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.segment_id || ''}
                                onChange={(e) => setField('settings', { segment_id: Number(e.target.value) || null })}
                            >
                                <option value="">Select segment</option>
                                {(segments || []).map((s) => (
                                    <option key={s.id} value={s.id}>{s.name}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                ) : null}

                {selected.type === 'update_field' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Field <span className="text-red-500">*</span></label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.field || ''}
                                onChange={(e) => setField('settings', { field: e.target.value })}
                            >
                                <option value="">Select field</option>
                                <option value="first_name">First name</option>
                                <option value="last_name">Last name</option>
                                <option value="status">Status</option>
                                <option value="tags">Tags (comma-separated)</option>
                                <option value="custom_fields.plan">Custom: Plan</option>
                                <option value="custom_fields.wp_user_id">Custom: WP user id</option>
                                <option value="custom_fields.woo_customer_id">Custom: Woo customer id</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Value <span className="text-red-500">*</span></label>
                            <input
                                type="text"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.value || ''}
                                onChange={(e) => setField('settings', { value: e.target.value })}
                            />
                        </div>
                    </div>
                ) : null}

                {selected.type === 'goal' ? (
                    <div className="space-y-3">
                        <div className="rounded-md bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 p-3">
                            <div className="text-xs font-medium text-green-800 dark:text-green-300">Goal reached</div>
                            <div className="text-xs text-green-700 dark:text-green-400 mt-1">
                                When a subscriber reaches this step, the automation run is marked as completed.
                            </div>
                        </div>
                    </div>
                ) : null}

                {selected.type === 'ab_split' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Path A % <span className="text-red-500">*</span></label>
                            <input
                                type="range"
                                min="1"
                                max="99"
                                className="mt-1 block w-full"
                                value={Number(selected.settings?.split_a_percent || 50)}
                                onChange={(e) => setField('settings', { split_a_percent: Number(e.target.value) })}
                            />
                            <div className="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span>Path A: {selected.settings?.split_a_percent || 50}%</span>
                                <span>Path B: {100 - (selected.settings?.split_a_percent || 50)}%</span>
                            </div>
                        </div>
                        <div className="text-[11px] text-gray-500 dark:text-gray-400">
                            Subscribers are deterministically assigned to Path A or B based on their email hash. The same subscriber always gets the same path.
                        </div>
                    </div>
                ) : null}

                {selected.type === 'wait_until' ? (
                    <div className="space-y-3">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Mode</label>
                            <select
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                value={selected.settings?.mode || 'fixed'}
                                onChange={(e) => setField('settings', { mode: e.target.value })}
                            >
                                <option value="fixed">Fixed date/time</option>
                                <option value="field">Subscriber field</option>
                            </select>
                        </div>
                        {selected.settings?.mode === 'field' ? (
                            <>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Field <span className="text-red-500">*</span></label>
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                        value={selected.settings?.field || ''}
                                        onChange={(e) => setField('settings', { field: e.target.value })}
                                    >
                                        <option value="">Select field</option>
                                        <option value="created_at">Created at</option>
                                        <option value="custom_fields.birthday">Custom: Birthday</option>
                                        <option value="custom_fields.anniversary">Custom: Anniversary</option>
                                        <option value="custom_fields.plan_start">Custom: Plan start</option>
                                        <option value="custom_fields.wp_user_registered">Custom: WP registered</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Time of day</label>
                                    <input
                                        type="time"
                                        className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                        value={selected.settings?.time || '00:00'}
                                        onChange={(e) => setField('settings', { time: e.target.value })}
                                    />
                                </div>
                            </>
                        ) : (
                            <div>
                                <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">Target date/time <span className="text-red-500">*</span></label>
                                <input
                                    type="datetime-local"
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-100 text-sm"
                                    value={selected.settings?.target_date || ''}
                                    onChange={(e) => setField('settings', { target_date: e.target.value })}
                                />
                            </div>
                        )}
                        <div className="text-[11px] text-gray-500 dark:text-gray-400">
                            The automation run will pause at this node until the specified date/time is reached.
                        </div>
                    </div>
                ) : null}
            </div>
        </div>
    );
}

function NodesPalette({ onAdd }) {
    const items = [
        { type: 'email', label: 'Send Email', subtitle: 'Follow-up step' },
        { type: 'run_campaign', label: 'Run Campaign', subtitle: 'Send an existing campaign' },
        { type: 'move_subscribers', label: 'Move Subscriber(s)', subtitle: 'Move to another list' },
        { type: 'copy_subscribers', label: 'Copy Subscriber(s)', subtitle: 'Copy to another list' },
        { type: 'add_tag', label: 'Add Tag(s)', subtitle: 'Tag the subscriber' },
        { type: 'remove_tag', label: 'Remove Tag(s)', subtitle: 'Untag the subscriber' },
        { type: 'add_segment', label: 'Add to Segment', subtitle: 'Add subscriber to segment' },
        { type: 'remove_segment', label: 'Remove from Segment', subtitle: 'Remove subscriber from segment' },
        { type: 'update_field', label: 'Update Field', subtitle: 'Change a subscriber field' },
        { type: 'goal', label: 'Goal', subtitle: 'Exit automation' },
        { type: 'ab_split', label: 'A/B Split', subtitle: 'Split path A/B' },
        { type: 'wait_until', label: 'Wait Until', subtitle: 'Wait for a date/time' },
        { type: 'delay', label: 'Delay', subtitle: 'Wait before next step' },
        { type: 'condition', label: 'Condition', subtitle: 'Branching (basic)' },
        { type: 'webhook', label: 'Webhook', subtitle: 'HTTP request' },
    ];

    const typeColors = {
        trigger: 'bg-blue-50 text-blue-600 border-blue-100',
        email: 'bg-emerald-50 text-emerald-600 border-emerald-100',
        run_campaign: 'bg-violet-50 text-violet-600 border-violet-100',
        delay: 'bg-amber-50 text-amber-600 border-amber-100',
        condition: 'bg-rose-50 text-rose-600 border-rose-100',
        webhook: 'bg-sky-50 text-sky-600 border-sky-100',
        move_subscribers: 'bg-orange-50 text-orange-600 border-orange-100',
        copy_subscribers: 'bg-teal-50 text-teal-600 border-teal-100',
        add_tag: 'bg-lime-50 text-lime-600 border-lime-100',
        remove_tag: 'bg-red-50 text-red-600 border-red-100',
        add_segment: 'bg-cyan-50 text-cyan-600 border-cyan-100',
        remove_segment: 'bg-pink-50 text-pink-600 border-pink-100',
        update_field: 'bg-indigo-50 text-indigo-600 border-indigo-100',
        goal: 'bg-green-50 text-green-600 border-green-100',
        ab_split: 'bg-purple-50 text-purple-600 border-purple-100',
        wait_until: 'bg-yellow-50 text-yellow-600 border-yellow-100',
    };

    return (
        <div>
            <div>
                {items.map((it, idx) => {
                    const color = typeColors[it.type] || 'bg-gray-50 text-gray-600 border-gray-100';
                    const isLast = idx === items.length - 1;
                    return (
                        <button
                            key={it.type}
                            type="button"
                            className={`w-full text-left px-3 py-2.5 hover:bg-gray-50 transition-colors group ${isLast ? '' : 'border-b border-gray-50'}`}
                            onClick={() => onAdd(it.type)}
                        >
                            <div className="flex items-center gap-2.5">
                                <div className={`shrink-0 w-8 h-8 rounded-lg border flex items-center justify-center ${color}`}>
                                    <NodeIcon type={it.type} />
                                </div>
                                <div className="min-w-0">
                                    <div className="text-sm font-medium text-gray-800 group-hover:text-gray-900">{it.label}</div>
                                    <div className="text-[11px] text-gray-400">{it.subtitle}</div>
                                </div>
                            </div>
                        </button>
                    );
                })}
            </div>

            <div className="mt-4 text-[11px] text-gray-400 text-center">Click a node to add it after the selected step</div>
        </div>
    );
}

function AutomationBuilderApp({ initialGraph, emailLists, campaigns, templates, deliveryServers, segments, hideAttribution, automationId, floatingSidebar = false }) {
    const initialHasPositions = useMemo(() => {
        const ns = (initialGraph?.nodes || []);
        if (ns.length <= 1) return false;
        return ns.some((n) => (Number(n?.x) || 0) !== 0 || (Number(n?.y) || 0) !== 0);
    }, [initialGraph]);

    const initial = useMemo(() => {
        const ensured = ensureTrigger(initialGraph || {});
        return initialHasPositions ? ensured : simpleVerticalLayout(ensured);
    }, [initialGraph, initialHasPositions]);

    const graphNodeMetaById = useMemo(() => {
        const m = new Map();
        (initialGraph?.nodes || []).forEach((n) => {
            if (n?.id) m.set(String(n.id), n);
        });
        return m;
    }, [initialGraph]);

    const [selectedId, setSelectedId] = useState('trigger_1');

    const hasExplicitSelectionRef = useRef(false);

    const hasManualLayoutRef = useRef(initialHasPositions);

    const [rightTab, setRightTab] = useState('nodes');

    const sidebarRef = useRef(null);
    const [canvasHeight, setCanvasHeight] = useState(520);
    const maxSidebarHeightRef = useRef(520);
    const fullHeightModeRef = useRef(false);

    const [needsLayout, setNeedsLayout] = useState(false);

    const [insertMenu, setInsertMenu] = useState({ open: false, x: 0, y: 0, fromId: null, branch: null });
    const deleteNodeRef = useRef(() => {});
    const duplicateNodeRef = useRef(() => {});

    const callbacks = useMemo(
        () => ({
            onSelect: (id) => {
                hasExplicitSelectionRef.current = true;
                setSelectedId(id);
            },
            onInsert: (fromId, branch, point) => {
                if (fromId) {
                    hasExplicitSelectionRef.current = true;
                    setSelectedId(fromId);
                }
                const p = point || { x: 0, y: 0 };
                setInsertMenu({ open: true, x: Number(p.x) || 0, y: Number(p.y) || 0, fromId, branch: branch || null });
            },
            onDelete: (id) => deleteNodeRef.current(id),
            onDuplicate: (id) => duplicateNodeRef.current(id),
        }),
        []
    );

    const [nodes, setNodes, onNodesChange] = useNodesState(toFlowNodes(initial.nodes, callbacks));
    const [edges, setEdges, onEdgesChange] = useEdgesState(toFlowEdges(initial.edges));

    const deleteNodeById = useCallback(
        (id) => {
            console.log('deleteNodeById called', id);
            if (!id || id === 'trigger_1') return;

            const incoming = (edges || []).filter((e) => e.target === id);
            const outgoing = (edges || []).filter((e) => e.source === id);

            const nextEdges = (edges || []).filter((e) => e.source !== id && e.target !== id);

            // If this node sits in a simple chain (1 incoming, 1 outgoing), bypass it so the flow stays connected.
            if (incoming.length === 1 && outgoing.length === 1) {
                const inE = incoming[0];
                const outE = outgoing[0];

                const source = inE.source;
                const target = outE.target;
                const branch = inE.data?.branch || undefined;

                if (source && target && source !== id && target !== id && source !== target) {
                    const already = nextEdges.some((e) => {
                        if (e.source !== source) return false;
                        if (e.target !== target) return false;
                        const b = e.data?.branch || undefined;
                        return (b || undefined) === (branch || undefined);
                    });

                    if (!already) {
                        nextEdges.push({
                            id: uid('edge'),
                            source,
                            target,
                            sourceHandle: branch || undefined,
                            type: 'customSmooth',
                            markerEnd: { type: MarkerType.ArrowClosed, width: 10, height: 10, color: '#cbd5e1' },
                            style: { stroke: '#cbd5e1', strokeWidth: 1.5 },
                            data: { branch },
                        });
                    }
                }
            }

            setEdges(nextEdges);
            setNodes((nds) => nds.filter((n) => n.id !== id));
            if (selectedId === id) {
                setSelectedId('trigger_1');
                setRightTab('nodes');
            }
            if (!hasManualLayoutRef.current) {
                setNeedsLayout(true);
            }
        },
        [selectedId, setEdges, setNodes, edges]
    );

    const duplicateNode = useCallback(
        (id) => {
            console.log('duplicateNode called', id);
            const original = nodes.find((n) => n.id === id);
            if (!original) {
                console.log('duplicateNode: original not found');
                return;
            }

            const newId = uid('node');
            const pos = original.position || { x: 0, y: 0 };
            const newNode = {
                id: newId,
                type: 'base',
                position: { x: pos.x + 60, y: pos.y + 60 },
                data: {
                    id: newId,
                    type: original.data?.type,
                    label: original.data?.label,
                    subtitle: original.data?.subtitle,
                    settings: JSON.parse(JSON.stringify(original.data?.settings || {})),
                    onSelect: callbacks.onSelect,
                    onInsert: callbacks.onInsert,
                    onDelete: callbacks.onDelete,
                    onDuplicate: callbacks.onDuplicate,
                },
            };

            setNodes((nds) => [...nds, newNode]);
            setSelectedId(newId);
            setRightTab('settings');
            hasExplicitSelectionRef.current = true;
        },
        [nodes, setNodes, setSelectedId, callbacks]
    );

    // Keep refs pointing to latest implementations
    deleteNodeRef.current = deleteNodeById;
    duplicateNodeRef.current = duplicateNode;

    const onNodesChangeTracked = useCallback(
        (changes) => {
            if (
                Array.isArray(changes) &&
                changes.some((c) => c && c.type === 'position' && (c.dragging === true || c.dragging === false))
            ) {
                hasManualLayoutRef.current = true;
            }
            onNodesChange(changes);
        },
        [onNodesChange]
    );

    const selectedNode = useMemo(() => {
        const n = nodes.find((x) => x.id === selectedId);
        if (!n) return null;
        return {
            id: n.id,
            type: n.data?.type,
            label: n.data?.label,
            subtitle: n.data?.subtitle,
            settings: n.data?.settings || {},
        };
    }, [nodes, selectedId]);

    const updateSelected = useCallback(
        (updater) => {
            setNodes((nds) =>
                nds.map((n) => {
                    if (n.id !== selectedId) return n;
                    const cur = {
                        id: n.id,
                        type: n.data?.type,
                        label: n.data?.label,
                        subtitle: n.data?.subtitle,
                        settings: n.data?.settings || {},
                    };
                    let next = updater(cur);

                    next = {
                        ...next,
                        subtitle: syncSubtitle(next, { emailLists, campaigns, segments }),
                    };
                    return {
                        ...n,
                        data: {
                            ...n.data,
                            type: next.type,
                            label: next.label,
                            subtitle: next.subtitle,
                            settings: next.settings,
                        },
                    };
                })
            );
        },
        [selectedId, setNodes, emailLists, campaigns]
    );

    const hiddenInputRef = useRef(null);

    useEffect(() => {
        hiddenInputRef.current = document.querySelector('input[name="graph_json"]');
    }, []);

    const syncGraphJson = useCallback(
        (nextNodes, nextEdges) => {
            const meta = new Map();
            nextNodes.forEach((n) => {
                meta.set(n.id, {
                    type: n.data?.type,
                    label: n.data?.label,
                    subtitle: n.data?.subtitle,
                    settings: n.data?.settings || {},
                });
            });
            const g = fromFlow(nextNodes, nextEdges, meta);
            if (hiddenInputRef.current) {
                hiddenInputRef.current.value = JSON.stringify(g);
            }
        },
        []
    );

    useEffect(() => {
        syncGraphJson(nodes, edges);
    }, [nodes, edges, syncGraphJson]);

    useEffect(() => {
        const mountEl = document.getElementById('automation-builder-react');
        const wantsFullHeight = mountEl && ['true', '1', 'yes'].includes(String(mountEl.getAttribute('data-full-height') || '').toLowerCase());
        fullHeightModeRef.current = !!wantsFullHeight;

        const el = sidebarRef.current;
        if (!el) return;

        const update = () => {
            if (fullHeightModeRef.current) {
                // Full-screen mode: compute available height based on viewport and the mount container.
                try {
                    if (!mountEl) return;
                    const rect = mountEl.getBoundingClientRect();
                    const available = Math.max(240, Math.floor(window.innerHeight - rect.top - 16));
                    setCanvasHeight(available);
                } catch {
                    // ignore
                }
                return;
            }

            const h = el.getBoundingClientRect().height;
            if (!Number.isFinite(h) || h <= 0) return;

            const next = Math.round(h);
            if (next > maxSidebarHeightRef.current) {
                maxSidebarHeightRef.current = next;
                setCanvasHeight(next);
            }
        };

        update();

        const ro = new ResizeObserver(() => update());
        ro.observe(el);
        window.addEventListener('resize', update);
        return () => {
            ro.disconnect();
            window.removeEventListener('resize', update);
        };
    }, [rightTab]);

    useEffect(() => {
        if (!needsLayout) return;
        if (hasManualLayoutRef.current) {
            setNeedsLayout(false);
            return;
        }

        const meta = new Map();
        nodes.forEach((n) => {
            meta.set(n.id, {
                type: n.data?.type,
                label: n.data?.label,
                subtitle: n.data?.subtitle,
                settings: n.data?.settings || {},
            });
        });

        const g = fromFlow(nodes, edges, meta);
        const laid = simpleVerticalLayout(g);
        const posById = new Map((laid.nodes || []).map((n) => [String(n.id), { x: Number(n.x) || 0, y: Number(n.y) || 0 }]));

        const nextNodes = nodes.map((n) => {
            const p = posById.get(n.id);
            if (!p) return n;
            if (n.position?.x === p.x && n.position?.y === p.y) return n;
            return { ...n, position: p };
        });

        setNeedsLayout(false);
        setNodes(nextNodes);
    }, [needsLayout, nodes, edges, setNodes]);

    const onConnect = useCallback(
        (params) => {
            setEdges((eds) =>
                addEdge(
                    {
                        ...params,
                        id: uid('edge'),
                        type: 'customSmooth',
                        markerEnd: { type: MarkerType.ArrowClosed, width: 10, height: 10, color: '#cbd5e1' },
                        style: { stroke: '#cbd5e1', strokeWidth: 1.5 },
                    },
                    eds
                )
            );
        },
        [setEdges]
    );

    const doInsert = useCallback(
        (type, fromId, branch) => {
            if (!fromId) return;

            const INSERT_GAP_Y = 200;

            // Create node
            const base = {
                trigger: { label: 'Trigger', subtitle: 'Start', settings: { list_id: null, campaign_id: null, trigger: '', window_value: 24, window_unit: 'hours', schedule_mode: '', schedule_time: '09:00', schedule_days: [], schedule_date: 1, schedule_months: 3, date_field: '' } },
                email: { label: 'Send Email', subtitle: 'Email action', settings: { subject: '', template_id: null, delivery_server_id: null, from_name: '', from_email: '', reply_to: '', track_opens: true, track_clicks: true } },
                run_campaign: { label: 'Run Campaign', subtitle: 'Send campaign', settings: { campaign_id: null } },
                move_subscribers: { label: 'Move Subscriber(s)', subtitle: 'Move to list', settings: { target_list_id: null } },
                copy_subscribers: { label: 'Copy Subscriber(s)', subtitle: 'Copy to list', settings: { target_list_id: null } },
                delay: { label: 'Delay', subtitle: 'Wait time', settings: { delay_value: 0, delay_unit: 'hours' } },
                webhook: { label: 'Webhook', subtitle: 'HTTP request', settings: { url: '', method: 'POST' } },
                condition: { label: 'Condition', subtitle: 'Branch', settings: { field: '', operator: 'equals', value: '' } },
                add_tag: { label: 'Add Tag(s)', subtitle: 'Add subscriber tags', settings: { tags: [] } },
                remove_tag: { label: 'Remove Tag(s)', subtitle: 'Remove subscriber tags', settings: { tags: [] } },
                add_segment: { label: 'Add to Segment', subtitle: 'Add to segment', settings: { segment_id: null } },
                remove_segment: { label: 'Remove from Segment', subtitle: 'Remove from segment', settings: { segment_id: null } },
                update_field: { label: 'Update Field', subtitle: 'Update subscriber field', settings: { field: '', value: '' } },
                goal: { label: 'Goal', subtitle: 'Exit automation', settings: {} },
                ab_split: { label: 'A/B Split', subtitle: 'Split path', settings: { split_a_percent: 50 } },
                wait_until: { label: 'Wait Until', subtitle: 'Wait for a date/time', settings: { mode: 'fixed', target_date: '', field: '', time: '00:00' } },
            };
            const meta = base[type] || { label: 'Action', subtitle: '', settings: {} };

            const newId = uid(type);
            const fromNode = nodes.find((n) => n.id === fromId);
            const fromPos = fromNode?.position || { x: 0, y: 0 };

            const newNode = {
                id: newId,
                type: 'base',
                position: { x: fromPos.x, y: fromPos.y + INSERT_GAP_Y },
                data: {
                    id: newId,
                    type,
                    label: meta.label,
                    subtitle: syncSubtitle({ id: newId, type, label: meta.label, subtitle: meta.subtitle, settings: meta.settings }, { emailLists, campaigns, segments }),
                    settings: meta.settings,
                    onSelect: callbacks.onSelect,
                    onInsert: callbacks.onInsert,
                    onDelete: callbacks.onDelete,
                    onDuplicate: callbacks.onDuplicate,
                },
            };

            const wantBranch = branch || undefined;
            const nextNodes = [...nodes, newNode];

            const existingOutIdx = edges.findIndex((e) => {
                if (e.source !== fromId) return false;
                const b = e.data?.branch || undefined;
                if (!wantBranch) return !b;
                return b === wantBranch;
            });

            let nextEdges = [...edges];
            if (existingOutIdx >= 0) {
                const existing = edges[existingOutIdx];
                const oldTarget = existing.target;

                if (hasManualLayoutRef.current) {
                    const targetNode = nodes.find((n) => n.id === oldTarget);
                    const targetPos = targetNode?.position || { x: fromPos.x, y: fromPos.y + (INSERT_GAP_Y * 2) };

                    const currentSpan = (Number(targetPos.y) || 0) - (Number(fromPos.y) || 0);
                    const requiredSpan = INSERT_GAP_Y * 2;
                    const shiftDownBy = Math.max(0, requiredSpan - currentSpan);

                    if (shiftDownBy > 0) {
                        const downstreamIds = collectDownstreamIds(oldTarget, edges);
                        for (let i = 0; i < nextNodes.length; i++) {
                            const n = nextNodes[i];
                            if (!downstreamIds.has(String(n.id))) continue;
                            nextNodes[i] = {
                                ...n,
                                position: {
                                    ...(n.position || { x: 0, y: 0 }),
                                    y: (Number(n.position?.y) || 0) + shiftDownBy,
                                },
                            };
                        }
                    }

                    newNode.position = { x: Number(fromPos.x) || 0, y: (Number(fromPos.y) || 0) + INSERT_GAP_Y };
                }

                nextEdges[existingOutIdx] = {
                    ...existing,
                    target: newId,
                    sourceHandle: wantBranch || existing.sourceHandle,
                    data: { ...(existing.data || {}), branch: wantBranch },
                };

                nextEdges.push({
                    id: uid('edge'),
                    source: newId,
                    target: oldTarget,
                    type: 'customSmooth',
                    markerEnd: { type: MarkerType.ArrowClosed, width: 10, height: 10, color: '#cbd5e1' },
                    style: { stroke: '#cbd5e1', strokeWidth: 1.5 },
                    data: {},
                });
            } else {
                nextEdges.push({
                    id: uid('edge'),
                    source: fromId,
                    target: newId,
                    sourceHandle: wantBranch || undefined,
                    type: 'customSmooth',
                    markerEnd: { type: MarkerType.ArrowClosed, width: 10, height: 10, color: '#cbd5e1' },
                    style: { stroke: '#cbd5e1', strokeWidth: 1.5 },
                    data: { branch: wantBranch },
                });
            }

            setNodes(nextNodes);
            setEdges(nextEdges);
            if (!hasManualLayoutRef.current) {
                setNeedsLayout(true);
            }

            setSelectedId(newId);
            setRightTab('settings');

            if (!hasManualLayoutRef.current) {
                pendingCenterOnIdRef.current = newId;
            }
        },
        [nodes, edges, setNodes, setEdges, callbacks, emailLists, campaigns]
    );

    const addAfterSelected = useCallback(
        (type) => {
            let fromId = selectedId || 'trigger_1';

            if (!hasExplicitSelectionRef.current && fromId === 'trigger_1') {
                const sources = new Set((edges || []).map((e) => String(e.source)));
                const triggerX = Number(nodes.find((n) => n.id === 'trigger_1')?.position?.x) || 0;
                const endCandidates = (nodes || []).filter((n) => n.id !== 'trigger_1' && !sources.has(String(n.id)));

                const pickDefaultAppendNode = (arr) => {
                    const xs = [...(arr || [])];
                    xs.sort((a, b) => {
                        const ay = Number(a.position?.y) || 0;
                        const by = Number(b.position?.y) || 0;
                        if (ay !== by) return by - ay;

                        const ax = Number(a.position?.x) || 0;
                        const bx = Number(b.position?.x) || 0;
                        const ad = Math.abs(ax - triggerX);
                        const bd = Math.abs(bx - triggerX);
                        if (ad !== bd) return ad - bd;

                        return ax - bx;
                    });
                    return xs[0];
                };

                const picked = endCandidates.length
                    ? pickDefaultAppendNode(endCandidates)
                    : pickDefaultAppendNode((nodes || []).filter((n) => n.id !== 'trigger_1'));

                if (picked?.id) {
                    fromId = picked.id;
                }
            }

            doInsert(type, fromId, null);
        },
        [doInsert, selectedId, nodes, edges]
    );

    const insertItems = useMemo(
        () => [
            { type: 'email', label: 'Send Email' },
            { type: 'run_campaign', label: 'Run Campaign' },
            { type: 'move_subscribers', label: 'Move Subscriber(s)' },
            { type: 'copy_subscribers', label: 'Copy Subscriber(s)' },
            { type: 'add_tag', label: 'Add Tag(s)' },
            { type: 'remove_tag', label: 'Remove Tag(s)' },
            { type: 'add_segment', label: 'Add to Segment' },
            { type: 'remove_segment', label: 'Remove from Segment' },
            { type: 'update_field', label: 'Update Field' },
            { type: 'goal', label: 'Goal' },
            { type: 'ab_split', label: 'A/B Split' },
            { type: 'wait_until', label: 'Wait Until' },
            { type: 'delay', label: 'Delay' },
            { type: 'condition', label: 'Condition' },
            { type: 'webhook', label: 'Webhook' },
        ],
        []
    );

    const closeInsertMenu = useCallback(() => {
        setInsertMenu({ open: false, x: 0, y: 0, fromId: null, branch: null });
    }, []);

    const deleteSelected = useCallback(() => {
        deleteNodeById(selectedId);
    }, [selectedId, deleteNodeById]);

    useEffect(() => {
        if (!insertMenu.open) return;

        const onKeyDown = (e) => {
            if (e.key === 'Escape') {
                closeInsertMenu();
            }
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [insertMenu.open, closeInsertMenu]);

    useEffect(() => {
        const onKeyDown = (e) => {
            if (e.key !== 'Backspace' && e.key !== 'Delete') {
                return;
            }

            const el = e.target;
            const tag = (el?.tagName || '').toLowerCase();
            const editable = el?.isContentEditable;
            if (editable || tag === 'input' || tag === 'textarea' || tag === 'select') {
                return;
            }

            e.preventDefault();
            deleteSelected();
        };

        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [deleteSelected]);

    const { setViewport, getZoom, setCenter } = useReactFlow();
    const didInitViewportRef = useRef(false);
    useEffect(() => {
        if (didInitViewportRef.current) return;
        didInitViewportRef.current = true;

        const t = setTimeout(() => {
            const trigger = nodes.find((n) => n.id === 'trigger_1');
            const p = trigger?.position || { x: 0, y: 0 };
            try {
                const cx = (Number(p.x) || 0) + 140; // half node width (280)
                const cy = (Number(p.y) || 0) + 80;
                setViewport({ x: 0, y: 0, zoom: 0.5 }, { duration: 0 });
                setCenter(cx, cy, { zoom: 0.5, duration: 0 });
            } catch {
                // ignore
            }
        }, 50);

        return () => clearTimeout(t);
    }, [nodes, setViewport, setCenter]);

    const pendingCenterOnIdRef = useRef(null);
    useEffect(() => {
        const targetId = pendingCenterOnIdRef.current;
        if (!targetId) return;

        const n = nodes.find((x) => x.id === targetId);
        if (!n) return;

        pendingCenterOnIdRef.current = null;
        const p = n.position || { x: 0, y: 0 };
        const z = Number(getZoom?.()) || 0.5;
        try {
            setCenter(Number(p.x) || 0, Number(p.y) || 0, { zoom: z, duration: 250 });
        } catch {
            // ignore
        }
    }, [nodes, getZoom, setCenter]);

    const insertMenuOverlay = insertMenu.open ? (
        <>
            <div
                className="fixed inset-0 z-40"
                onClick={() => closeInsertMenu()}
            />
            <div
                className="fixed z-50"
                style={{ left: insertMenu.x, top: insertMenu.y }}
            >
                <div className="w-48 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg p-2">
                    <div className="text-[11px] font-semibold text-gray-500 dark:text-gray-400 px-2 py-1">Add step</div>
                    {insertItems.map((it) => (
                        <button
                            key={it.type}
                            type="button"
                            className="w-full text-left rounded-md px-2 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/5"
                            onClick={() => {
                                const fromId = insertMenu.fromId;
                                const branch = insertMenu.branch;
                                closeInsertMenu();
                                doInsert(it.type, fromId, branch);
                            }}
                        >
                            <span className="inline-flex items-center gap-2">
                                <span className="text-primary-600 dark:text-primary-300"><NodeIcon type={it.type} /></span>
                                <span>{it.label}</span>
                            </span>
                        </button>
                    ))}
                    <div className="mt-1 px-2">
                        <button
                            type="button"
                            className="text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-200"
                            onClick={() => closeInsertMenu()}
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </>
    ) : null;

    const reactFlowEl = (
        <ReactFlow
            nodes={nodes}
            edges={edges}
            onNodesChange={onNodesChangeTracked}
            onEdgesChange={onEdgesChange}
            onConnect={onConnect}
            nodeTypes={nodeTypes}
            edgeTypes={edgeTypes}
            onNodeClick={(_, node) => {
                hasExplicitSelectionRef.current = true;
                setSelectedId(node.id);
                setRightTab('settings');
            }}
            proOptions={hideAttribution ? { hideAttribution: true } : undefined}
        >
            <Background variant="dots" gap={16} size={1.5} color="#94a3b8" />
            <Controls className="rounded-lg border border-gray-200 bg-white shadow-sm" showInteractive={false} />
        </ReactFlow>
    );

    const sidebarTabs = (
        <div className="flex items-center">
            <button
                type="button"
                className={`flex-1 px-3 py-2 text-sm font-medium ${rightTab === 'nodes' ? 'text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-white/5' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'}`}
                onClick={() => setRightTab('nodes')}
            >
                Nodes
            </button>
            <button
                type="button"
                className={`flex-1 px-3 py-2 text-sm font-medium ${rightTab === 'settings' ? 'text-primary-700 dark:text-primary-300 bg-primary-50 dark:bg-white/5' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5'}`}
                onClick={() => setRightTab('settings')}
            >
                Settings
            </button>
        </div>
    );

    const sidebarBody = (
        <div className="overflow-y-auto flex-1 min-h-0">
            {rightTab === 'nodes' ? (
                <NodesPalette
                    onAdd={(type) => {
                        addAfterSelected(type);
                        setRightTab('settings');
                    }}
                />
            ) : (
                <SettingsPanel
                    selected={selectedNode}
                    setSelected={setSelectedId}
                    emailLists={emailLists}
                    campaigns={campaigns}
                    templates={templates}
                    deliveryServers={deliveryServers}
                    segments={segments}
                    onUpdateSelected={updateSelected}
                    automationId={automationId}
                    onDeleteSelected={deleteSelected}
                />
            )}
        </div>
    );

    if (floatingSidebar) {
        return (
            <div className="absolute inset-0">
                <div className="absolute inset-0 bg-white dark:bg-gray-900">
                    {reactFlowEl}
                </div>
                {insertMenuOverlay}
                <div
                    ref={sidebarRef}
                    className="absolute top-4 right-4 bottom-4 w-72 z-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-lg flex flex-col overflow-hidden"
                >
                    {sidebarTabs}
                    {sidebarBody}
                </div>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div className="lg:col-span-9">
                <div className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
                    <div className="relative w-full" style={{ height: canvasHeight }}>
                        {insertMenuOverlay}
                        {reactFlowEl}
                    </div>
                </div>
            </div>

            <div className="lg:col-span-3">
                <div
                    ref={sidebarRef}
                    className="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden flex flex-col"
                    style={{ minHeight: canvasHeight }}
                >
                    {sidebarTabs}
                    {sidebarBody}
                </div>
            </div>
        </div>
    );
}

function Mount() {
    const el = document.getElementById('automation-builder-react');
    if (!el) return;

    if (mountedEl === el && root) {
        return;
    }

    if (root && mountedEl && mountedEl !== el) {
        try {
            root.unmount();
        } catch (_) {
            //
        }
        root = null;
        mountedEl = null;
    }

    const initialGraph = safeJsonParse(el.getAttribute('data-initial-graph'), {});
    const emailLists = safeJsonParse(el.getAttribute('data-email-lists'), []);
    const campaigns = safeJsonParse(el.getAttribute('data-campaigns'), []);
    const templates = safeJsonParse(el.getAttribute('data-templates'), []);
    const segments = safeJsonParse(el.getAttribute('data-segments'), []);
    const deliveryServers = safeJsonParse(el.getAttribute('data-delivery-servers'), []);
    const automationId = el.getAttribute('data-automation-id') || '';
    const hideAttribution = ['true', '1', 'yes'].includes(
        String(el.getAttribute('data-hide-attribution') || '').toLowerCase()
    );
    const floatingSidebar = ['true', '1', 'yes'].includes(
        String(el.getAttribute('data-floating-sidebar') || '').toLowerCase()
    );

    root = createRoot(el);
    mountedEl = el;
    root.render(
        <ReactFlowProvider>
            <AutomationBuilderApp
                initialGraph={initialGraph}
                emailLists={emailLists}
                campaigns={campaigns}
                templates={templates}
                segments={segments}
                deliveryServers={deliveryServers}
                hideAttribution={hideAttribution}
                automationId={automationId}
                floatingSidebar={floatingSidebar}
            />
        </ReactFlowProvider>
    );
}

let root = null;
let mountedEl = null;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', Mount);
} else {
    Mount();
}

document.addEventListener('turbo:load', Mount);
document.addEventListener('turbo:render', Mount);
document.addEventListener('turbo:before-cache', () => {
    if (!root) return;
    try {
        root.unmount();
    } catch (_) {
        //
    }
    root = null;
    mountedEl = null;
});
