import * as React from 'react';
import { cn } from '../../lib/utils.js';

const CHART_WIDTH = 600;
const CHART_HEIGHT = 220;
const PAD = { top: 16, right: 16, bottom: 28, left: 44 };

function scaleValues(values) {
    const min = Math.min(...values);
    const max = Math.max(...values);
    const span = max - min || 1;
    const pad = span * 0.15;
    return { min: min - pad, max: max + pad };
}

function niceLabel(value) {
    if (Math.abs(value) >= 1000000) return `${(value / 1000000).toFixed(1)}M`;
    if (Math.abs(value) >= 1000) return `${(value / 1000).toFixed(1)}k`;
    return String(Math.round(value * 10) / 10);
}

function buildPoints(data, key) {
    const values = data.map((d) => Number(d[key]) || 0);
    const { min, max } = scaleValues(values);
    const innerW = CHART_WIDTH - PAD.left - PAD.right;
    const innerH = CHART_HEIGHT - PAD.top - PAD.bottom;

    const points = data.map((d, i) => {
        const x = PAD.left + (data.length === 1 ? innerW / 2 : (i / (data.length - 1)) * innerW);
        const y = PAD.top + innerH - ((Number(d[key]) || 0) - min) / (max - min || 1) * innerH;
        return { x, y, label: d.label, raw: d[key] };
    });

    return { points, min, max, innerW, innerH };
}

function AreaChartCard({ title, subtitle, data = [], className, valueKey = 'value', strokeClass = 'stroke-primary', fillClass = 'fill-primary/15' }) {
    if (!data.length) {
        return null;
    }

    const { points, min, max, innerW, innerH } = buildPoints(data, valueKey);

    const linePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' ');
    const areaPath = `${linePath} L ${points[points.length - 1].x.toFixed(1)} ${(PAD.top + innerH).toFixed(1)} L ${points[0].x.toFixed(1)} ${(PAD.top + innerH).toFixed(1)} Z`;
    const grid = Array.from({ length: 5 }, (_, i) => i / 4);

    const labelStep = Math.ceil(data.length / 8);

    return (
        <div className={cn('w-full', className)}>
            {(title || subtitle) && (
                <div className="mb-3 flex items-start justify-between gap-4">
                    <div>
                        {title && <h4 className="text-sm font-semibold text-foreground">{title}</h4>}
                        {subtitle && <p className="mt-0.5 text-xs text-muted-foreground">{subtitle}</p>}
                    </div>
                </div>
            )}
            <svg viewBox={`0 0 ${CHART_WIDTH} ${CHART_HEIGHT}`} className="h-auto w-full" role="img">
                {grid.map((g) => {
                    const y = PAD.top + g * innerH;
                    const val = max - g * (max - min);
                    return (
                        <g key={g}>
                            <line x1={PAD.left} x2={CHART_WIDTH - PAD.right} y1={y} y2={y} className="stroke-border" strokeWidth="1" />
                            <text x={PAD.left - 8} y={y + 3} textAnchor="end" className="fill-muted-foreground" style={{ fontSize: 10 }}>
                                {niceLabel(val)}
                            </text>
                        </g>
                    );
                })}
                <path d={areaPath} className={fillClass} />
                <path d={linePath} fill="none" className={cn(strokeClass)} strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
                {points.map((p, i) => (
                    <circle key={i} cx={p.x} cy={p.y} r="3.5" className="fill-background stroke-current text-primary" strokeWidth="2" />
                ))}
                {points.map((p, i) =>
                    i % labelStep === 0 ? (
                        <text key={`l${i}`} x={p.x} y={CHART_HEIGHT - 8} textAnchor="middle" className="fill-muted-foreground" style={{ fontSize: 10 }}>
                            {p.label}
                        </text>
                    ) : null
                )}
            </svg>
        </div>
    );
}

function BarChartCard({ title, subtitle, data = [], className, valueKey = 'value', barClass = 'bg-primary' }) {
    if (!data.length) {
        return null;
    }

    const values = data.map((d) => Number(d[valueKey]) || 0);
    const { max } = scaleValues(values);
    const innerW = CHART_WIDTH - PAD.left - PAD.right;
    const innerH = CHART_HEIGHT - PAD.top - PAD.bottom;
    const slot = innerW / data.length;
    const barW = Math.max(6, Math.min(40, slot * 0.6));
    const labelStep = Math.ceil(data.length / 8);

    return (
        <div className={cn('w-full', className)}>
            {(title || subtitle) && (
                <div className="mb-3 flex items-start justify-between gap-4">
                    <div>
                        {title && <h4 className="text-sm font-semibold text-foreground">{title}</h4>}
                        {subtitle && <p className="mt-0.5 text-xs text-muted-foreground">{subtitle}</p>}
                    </div>
                </div>
            )}
            <svg viewBox={`0 0 ${CHART_WIDTH} ${CHART_HEIGHT}`} className="h-auto w-full" role="img">
                {Array.from({ length: 5 }, (_, i) => i / 4).map((g) => {
                    const y = PAD.top + g * innerH;
                    const val = max - g * (max - 0);
                    return (
                        <g key={g}>
                            <line x1={PAD.left} x2={CHART_WIDTH - PAD.right} y1={y} y2={y} className="stroke-border" strokeWidth="1" />
                            <text x={PAD.left - 8} y={y + 3} textAnchor="end" className="fill-muted-foreground" style={{ fontSize: 10 }}>
                                {niceLabel(val)}
                            </text>
                        </g>
                    );
                })}
                {data.map((d, i) => {
                    const h = ((Number(d[valueKey]) || 0) / (max || 1)) * innerH;
                    const x = PAD.left + i * slot + (slot - barW) / 2;
                    const y = PAD.top + innerH - h;
                    return (
                        <g key={i}>
                            <rect x={x} y={y} width={barW} height={Math.max(0, h)} rx="3" className={barClass} />
                            {i % labelStep === 0 && (
                                <text x={x + barW / 2} y={CHART_HEIGHT - 8} textAnchor="middle" className="fill-muted-foreground" style={{ fontSize: 10 }}>
                                    {d.label}
                                </text>
                            )}
                        </g>
                    );
                })}
            </svg>
        </div>
    );
}

function Sparkline({ data = [], className, valueKey = 'value', strokeClass = 'stroke-primary' }) {
    if (!data.length) {
        return null;
    }

    const values = data.map((d) => Number(d[valueKey]) || 0);
    const { min, max } = scaleValues(values);
    const innerW = 120;
    const innerH = 36;
    const points = values.map((v, i) => ({
        x: (i / (values.length - 1 || 1)) * innerW,
        y: innerH - ((v - min) / (max - min || 1)) * innerH,
    }));
    const path = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' ');

    return (
        <svg viewBox={`0 0 ${innerW} ${innerH}`} className={cn('h-auto w-full', className)} role="img">
            <path d={path} fill="none" className={strokeClass} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}

function MiniBars({ data = [], className, valueKey = 'value', barClass = 'bg-primary' }) {
    const values = data.map((d) => Number(d[valueKey]) || 0);
    const { max } = scaleValues(values);

    return (
        <div className={cn('flex h-10 items-end gap-1', className)} role="img">
            {values.map((v, i) => (
                <div key={i} title={String(data[i]?.label ?? '')} className={cn('flex-1 rounded-sm', barClass)} style={{ height: `${Math.max(6, ((v - 0) / (max || 1)) * 100)}%` }} />
            ))}
        </div>
    );
}

export { AreaChartCard, BarChartCard, Sparkline, MiniBars };
