import * as React from 'react';
import { Download, Search, Truck } from 'lucide-react';
import { Badge } from '@/components/ui/badge.jsx';
import { Button } from '@/components/ui/button.jsx';
import { Card, CardContent, CardHeader } from '@/components/ui/card.jsx';
import { Input } from '@/components/ui/input.jsx';
import { PageHeader } from '@/components/ui/page-header.jsx';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table.jsx';

const orders = [
    { id: 'ORD-1024', date: 'Jul 31, 2026', customer: 'Olivia Martin', items: 4, payment: 'Visa •••• 1234', status: 'Completed', total: '$1,999.00' },
    { id: 'ORD-1023', date: 'Jul 30, 2026', customer: 'Jackson Lee', items: 1, payment: 'PayPal', status: 'Pending', total: '$39.00' },
    { id: 'ORD-1022', date: 'Jul 30, 2026', customer: 'Isabella Nguyen', items: 2, payment: 'Mastercard •••• 9981', status: 'Completed', total: '$299.00' },
    { id: 'ORD-1021', date: 'Jul 29, 2026', customer: 'William Kim', items: 3, payment: 'Visa •••• 5532', status: 'Shipped', total: '$99.00' },
    { id: 'ORD-1020', date: 'Jul 28, 2026', customer: 'Sofia Davis', items: 1, payment: 'PayPal', status: 'Refunded', total: '$129.00' },
    { id: 'ORD-1019', date: 'Jul 28, 2026', customer: 'Ethan Johnson', items: 5, payment: 'Amex •••• 0077', status: 'Completed', total: '$459.00' },
    { id: 'ORD-1018', date: 'Jul 27, 2026', customer: 'Ava Thompson', items: 2, payment: 'Visa •••• 3221', status: 'Shipped', total: '$159.00' },
];

const statusVariant = (status) => {
    if (status === 'Completed') return 'success';
    if (status === 'Pending') return 'warning';
    if (status === 'Shipped') return 'info';
    if (status === 'Refunded') return 'destructive';
    return 'muted';
};

export default function OrdersPage() {
    const [query, setQuery] = React.useState('');

    const filtered = orders.filter(
        (o) => o.id.toLowerCase().includes(query.toLowerCase()) || o.customer.toLowerCase().includes(query.toLowerCase())
    );

    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Orders"
                description="Track and manage all customer orders."
                actions={
                    <>
                        <Button variant="outline">
                            <Download className="size-4" />
                            Export
                        </Button>
                        <Button>
                            <Truck className="size-4" />
                            New Order
                        </Button>
                    </>
                }
            />

            <Card>
                <CardHeader>
                    <div className="relative w-full max-w-sm">
                        <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input placeholder="Search orders…" value={query} onChange={(e) => setQuery(e.target.value)} className="pl-8" />
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Order ID</TableHead>
                                <TableHead className="hidden lg:table-cell">Date</TableHead>
                                <TableHead>Customer</TableHead>
                                <TableHead className="hidden md:table-cell">Items</TableHead>
                                <TableHead className="hidden xl:table-cell">Payment</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Total</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filtered.map((order) => (
                                <TableRow key={order.id}>
                                    <TableCell className="font-medium">{order.id}</TableCell>
                                    <TableCell className="hidden lg:table-cell text-muted-foreground">{order.date}</TableCell>
                                    <TableCell>{order.customer}</TableCell>
                                    <TableCell className="hidden md:table-cell">{order.items}</TableCell>
                                    <TableCell className="hidden xl:table-cell text-muted-foreground">{order.payment}</TableCell>
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
