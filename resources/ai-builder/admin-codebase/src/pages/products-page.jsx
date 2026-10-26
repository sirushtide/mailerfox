import * as React from 'react';
import { PackagePlus, Search, Star } from 'lucide-react';
import { Badge } from '@/components/ui/badge.jsx';
import { Button } from '@/components/ui/button.jsx';
import { Card, CardContent, CardHeader } from '@/components/ui/card.jsx';
import { Input } from '@/components/ui/input.jsx';
import { PageHeader } from '@/components/ui/page-header.jsx';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table.jsx';

const products = [
    { id: 'P-301', name: 'Aurora Sneakers', category: 'Footwear', price: '$129.00', stock: 84, rating: 4.8, status: 'Active' },
    { id: 'P-302', name: 'Pulse Smartwatch', category: 'Electronics', price: '$249.00', stock: 32, rating: 4.6, status: 'Active' },
    { id: 'P-303', name: 'Drift Backpack', category: 'Accessories', price: '$89.00', stock: 0, rating: 4.3, status: 'Out of stock' },
    { id: 'P-304', name: 'Nimbus Hoodie', category: 'Apparel', price: '$59.00', stock: 120, rating: 4.9, status: 'Active' },
    { id: 'P-305', name: 'Orbit Desk Lamp', category: 'Home', price: '$45.00', stock: 64, rating: 4.2, status: 'Active' },
    { id: 'P-306', name: 'Vertex Water Bottle', category: 'Accessories', price: '$24.00', stock: 210, rating: 4.7, status: 'Active' },
];

const stockBadge = (status, stock) => {
    if (status === 'Out of stock') return 'destructive';
    if (stock < 50) return 'warning';
    return 'success';
};

export default function ProductsPage() {
    const [query, setQuery] = React.useState('');

    const filtered = products.filter(
        (p) => p.name.toLowerCase().includes(query.toLowerCase()) || p.category.toLowerCase().includes(query.toLowerCase())
    );

    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Products"
                description="Manage your catalog and inventory."
                actions={
                    <Button>
                        <PackagePlus className="size-4" />
                        Add Product
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <div className="relative w-full max-w-sm">
                        <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input placeholder="Search products…" value={query} onChange={(e) => setQuery(e.target.value)} className="pl-8" />
                    </div>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product</TableHead>
                                <TableHead className="hidden sm:table-cell">Category</TableHead>
                                <TableHead>Price</TableHead>
                                <TableHead className="hidden md:table-cell">Stock</TableHead>
                                <TableHead className="hidden lg:table-cell">Rating</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filtered.map((product) => (
                                <TableRow key={product.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-3">
                                            <div className="flex size-9 items-center justify-center rounded-md bg-muted text-xs font-bold text-muted-foreground">
                                                {product.name.slice(0, 2).toUpperCase()}
                                            </div>
                                            <div>
                                                <p className="font-medium">{product.name}</p>
                                                <p className="text-xs text-muted-foreground">{product.id}</p>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="hidden sm:table-cell">
                                        <Badge variant="outline">{product.category}</Badge>
                                    </TableCell>
                                    <TableCell className="font-medium">{product.price}</TableCell>
                                    <TableCell className="hidden md:table-cell">{product.stock}</TableCell>
                                    <TableCell className="hidden lg:table-cell">
                                        <span className="inline-flex items-center gap-1">
                                            <Star className="size-3.5 fill-amber-400 text-amber-400" />
                                            {product.rating}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={stockBadge(product.status, product.stock)}>{product.status}</Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
