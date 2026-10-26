import * as React from 'react';
import { TrendingDown, TrendingUp } from 'lucide-react';
import { cn } from '../../lib/utils.js';
import { Card, CardContent } from './card.jsx';

function StatCard({ title, value, change = null, icon: Icon = null, trend = 'up', className, iconClass = 'bg-primary/10 text-primary' }) {
    const TrendIcon = trend === 'down' ? TrendingDown : TrendingUp;

    return (
        <Card className={cn('w-full', className)}>
            <CardContent className="p-6">
                <div className="flex items-start justify-between gap-4">
                    <div className="min-w-0">
                        <p className="truncate text-sm text-muted-foreground">{title}</p>
                        <p className="mt-1.5 truncate text-2xl font-bold tracking-tight text-foreground">{value}</p>
                        {change !== null && change !== undefined && (
                            <div className="mt-2 flex items-center gap-1">
                                <TrendIcon className={cn('size-3.5', trend === 'down' ? 'text-destructive' : 'text-emerald-600')} />
                                <span className={cn('text-xs font-medium', trend === 'down' ? 'text-destructive' : 'text-emerald-600')}>{change}</span>
                                <span className="text-xs text-muted-foreground">vs last period</span>
                            </div>
                        )}
                    </div>
                    {Icon && (
                        <div className={cn('flex size-10 shrink-0 items-center justify-center rounded-lg', iconClass)}>
                            <Icon className="size-5" />
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export { StatCard };
