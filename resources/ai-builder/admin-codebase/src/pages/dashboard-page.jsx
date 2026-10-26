import * as React from 'react';
import { ArrowUpRight, CircleDollarSign, Package, ShoppingCart, Users } from 'lucide-react';
import { Button } from '@/components/ui/button.jsx';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card.jsx';
import { AreaChartCard, BarChartCard } from '@/components/ui/charts.jsx';
import { Badge } from '@/components/ui/badge.jsx';
import { PageHeader } from '@/components/ui/page-header.jsx';
import { StatCard } from '@/components/ui/stat-card.jsx';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table.jsx';

const revenue = [
    { label: 'Jan', value: 4200 },
    { label: 'Feb', value: 5100 },
    { label: 'Mar', value: 4800 },
    { label: 'Apr', value: 6300 },
    { label: 'May', value: 5900 },
    { label: 'Jun', value: 7200 },
    { label: 'Jul', value: 6800 },
    { label: 'Aug', value: 8100 },
];

const traffic = [
    { label: 'Mon', value: 1240 },
    { label: 'Tue', value: 1380 },
    { label: 'Wed', value: 1110 },
    { label: 'Thu', value: 1590 },
    { label: 'Fri', value: 1420 },
    { label: 'Sat', value: 980 },
    { label: 'Sun', value: 870 },
];

const orders = [
    { id: 'ORD-1024', customer: 'Olivia Martin', email: 'olivia@example.com', status: 'Completed', total: '$1,999.00' },
    { id: 'ORD-1023', customer: 'Jackson Lee', email: 'jackson@example.com', status: 'Pending', total: '$39.00' },
    { id: 'ORD-1022', customer: 'Isabella Nguyen', email: 'isabella@example.com', status: 'Completed', total: '$299.00' },
    { id: 'ORD-1021', customer: 'William Kim', email: 'will@example.com', status: 'Refunded', total: '$99.00' },
    { id: 'ORD-1020', customer: 'Sofia Davis', email: 'sofia@example.com', status: 'Pending', total: '$129.00' },
];

const statusVariant = (status) => {
    if (status === 'Completed') return 'success';
    if (status === 'Pending') return 'warning';
    if (status === 'Refunded') return 'destructive';
    return 'muted';
};

export default function DashboardPage() {
    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Dashboard"
                description="Welcome back! Here's an overview of your store."
                actions={<Button>Download Report</Button>}
            />

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard title="Total Revenue" value="$45,231.89" change="+20.1%" icon={CircleDollarSign} />
                <StatCard title="Subscriptions" value="2,350" change="+180.1%" icon={Users} />
                <StatCard title="Orders" value="1,234" change="+19.0%" icon={ShoppingCart} />
                <StatCard title="Active Products" value="573" change="+7.4%" icon={Package} />
            </div>

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Revenue Over Time</CardTitle>
                                <CardDescription>Monthly revenue for the current year</CardDescription>
                            </div>
                            <Badge variant="outline" className="gap-1">
                                <ArrowUpRight className="size-3 text-emerald-600" />
                                +12.5%
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <AreaChartCard data={revenue} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Weekly Traffic</CardTitle>
                        <CardDescription>Page views this week</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <BarChartCard data={traffic} barClass="bg-primary" />
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Recent Orders</CardTitle>
                    <CardDescription>Latest transactions across your store</CardDescription>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Order</TableHead>
                                <TableHead>Customer</TableHead>
                                <TableHead className="hidden md:table-cell">Email</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {orders.map((order) => (
                                <TableRow key={order.id}>
                                    <TableCell className="font-medium">{order.id}</TableCell>
                                    <TableCell>{order.customer}</TableCell>
                                    <TableCell className="hidden md:table-cell text-muted-foreground">{order.email}</TableCell>
                                    <TableCell>
                                        <Badge variant={statusVariant(order.status)}>{order.status}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right font-medium">{order.total}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
