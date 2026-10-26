import * as React from 'react';
import { Eye, MousePointerClick, Share2, Users } from 'lucide-react';
import { Button } from '@/components/ui/button.jsx';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card.jsx';
import { AreaChartCard, BarChartCard } from '@/components/ui/charts.jsx';
import { PageHeader } from '@/components/ui/page-header.jsx';
import { StatCard } from '@/components/ui/stat-card.jsx';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs.jsx';

const sessions = [
    { label: '00:00', value: 320 },
    { label: '02:00', value: 280 },
    { label: '04:00', value: 240 },
    { label: '06:00', value: 380 },
    { label: '08:00', value: 560 },
    { label: '10:00', value: 720 },
    { label: '12:00', value: 810 },
    { label: '14:00', value: 690 },
    { label: '16:00', value: 880 },
    { label: '18:00', value: 1020 },
    { label: '20:00', value: 940 },
    { label: '22:00', value: 610 },
];

const channels = [
    { label: 'Mon', value: 820 },
    { label: 'Tue', value: 940 },
    { label: 'Wed', value: 870 },
    { label: 'Thu', value: 1130 },
    { label: 'Fri', value: 980 },
    { label: 'Sat', value: 760 },
    { label: 'Sun', value: 690 },
];

export default function AnalyticsPage() {
    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Analytics"
                description="Understand how visitors are engaging with your product."
                actions={<Button variant="outline">Export Report</Button>}
            />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard title="Total Visitors" value="48,129" change="+8.4%" icon={Users} />
                <StatCard title="Page Views" value="132,840" change="+14.2%" icon={Eye} />
                <StatCard title="Click Rate" value="3.42%" change="+0.6%" icon={MousePointerClick} />
                <StatCard title="Shares" value="1,204" change="-2.1%" trend="down" icon={Share2} />
            </div>

            <Card>
                <CardHeader>
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Live Sessions</CardTitle>
                            <CardDescription>Active sessions throughout the day</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <Tabs defaultValue="24h">
                        <TabsList>
                            <TabsTrigger value="24h">24h</TabsTrigger>
                            <TabsTrigger value="7d">7d</TabsTrigger>
                            <TabsTrigger value="30d">30d</TabsTrigger>
                        </TabsList>
                        <TabsContent value="24h">
                            <AreaChartCard data={sessions} />
                        </TabsContent>
                        <TabsContent value="7d">
                            <AreaChartCard data={sessions.slice(0, 7)} />
                        </TabsContent>
                        <TabsContent value="30d">
                            <AreaChartCard data={channels} />
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Top Channels</CardTitle>
                    <CardDescription>Visitor volume by traffic source</CardDescription>
                </CardHeader>
                <CardContent>
                    <BarChartCard data={channels} />
                </CardContent>
            </Card>
        </div>
    );
}
