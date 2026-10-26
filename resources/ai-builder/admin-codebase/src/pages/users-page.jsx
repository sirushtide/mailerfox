import * as React from 'react';
import { Download, Filter, MoreHorizontal, Search, UserPlus } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar.jsx';
import { Badge } from '@/components/ui/badge.jsx';
import { Button } from '@/components/ui/button.jsx';
import { Card, CardContent, CardHeader } from '@/components/ui/card.jsx';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu.jsx';
import { Input } from '@/components/ui/input.jsx';
import { PageHeader } from '@/components/ui/page-header.jsx';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table.jsx';

const users = [
    { id: 1, name: 'Olivia Martin', email: 'olivia.martin@email.com', role: 'Admin', status: 'Active', joined: 'Jan 2024' },
    { id: 2, name: 'Jackson Lee', email: 'jackson.lee@email.com', role: 'Editor', status: 'Active', joined: 'Feb 2024' },
    { id: 3, name: 'Isabella Nguyen', email: 'isabella.nguyen@email.com', role: 'Viewer', status: 'Invited', joined: 'Mar 2024' },
    { id: 4, name: 'William Kim', email: 'will@email.com', role: 'Editor', status: 'Suspended', joined: 'Apr 2024' },
    { id: 5, name: 'Sofia Davis', email: 'sofia.davis@email.com', role: 'Admin', status: 'Active', joined: 'May 2024' },
    { id: 6, name: 'Ethan Johnson', email: 'ethan.johnson@email.com', role: 'Viewer', status: 'Active', joined: 'Jun 2024' },
];

const initials = (name) =>
    name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

const statusVariant = (status) => {
    if (status === 'Active') return 'success';
    if (status === 'Invited') return 'info';
    if (status === 'Suspended') return 'destructive';
    return 'muted';
};

const roleVariant = (role) => {
    if (role === 'Admin') return 'default';
    if (role === 'Editor') return 'secondary';
    return 'outline';
};

export default function UsersPage() {
    const [query, setQuery] = React.useState('');

    const filtered = users.filter(
        (u) => u.name.toLowerCase().includes(query.toLowerCase()) || u.email.toLowerCase().includes(query.toLowerCase())
    );

    return (
        <div className="flex flex-col gap-6">
            <PageHeader
                title="Users"
                description="Manage team members and their access."
                actions={
                    <>
                        <Button variant="outline">
                            <Download className="size-4" />
                            Export
                        </Button>
                        <Button>
                            <UserPlus className="size-4" />
                            Add User
                        </Button>
                    </>
                }
            />

            <Card>
                <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="relative w-full max-w-sm">
                        <Search className="absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input placeholder="Search users…" value={query} onChange={(e) => setQuery(e.target.value)} className="pl-8" />
                    </div>
                    <Button variant="outline" size="sm">
                        <Filter className="size-4" />
                        Filter
                    </Button>
                </CardHeader>
                <CardContent className="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead className="hidden md:table-cell">Email</TableHead>
                                <TableHead className="hidden sm:table-cell">Role</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="hidden lg:table-cell">Joined</TableHead>
                                <TableHead className="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filtered.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>
                                        <div className="flex items-center gap-3">
                                            <Avatar className="h-8 w-8">
                                                <AvatarImage src="" alt={user.name} />
                                                <AvatarFallback>{initials(user.name)}</AvatarFallback>
                                            </Avatar>
                                            <span className="font-medium">{user.name}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="hidden md:table-cell text-muted-foreground">{user.email}</TableCell>
                                    <TableCell className="hidden sm:table-cell">
                                        <Badge variant={roleVariant(user.role)}>{user.role}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={statusVariant(user.status)}>{user.status}</Badge>
                                    </TableCell>
                                    <TableCell className="hidden lg:table-cell text-muted-foreground">{user.joined}</TableCell>
                                    <TableCell className="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon" className="h-8 w-8">
                                                    <MoreHorizontal className="size-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                <DropdownMenuItem>Edit user</DropdownMenuItem>
                                                <DropdownMenuItem>Reset password</DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem variant="destructive">Suspend user</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
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
