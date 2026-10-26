import * as React from 'react';
import { BarChart3, LayoutDashboard, Package, Settings, ShoppingCart, Users } from 'lucide-react';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarRail } from '@/components/ui/sidebar.jsx';
import { Logo } from './ui/logo.jsx';
import { NavMain } from './nav-main.jsx';
import { NavUser } from './nav-user.jsx';

const data = {
    navMain: [
        { title: 'Dashboard', url: '/', icon: LayoutDashboard },
        { title: 'Analytics', url: '/analytics', icon: BarChart3 },
        { title: 'Users', url: '/users', icon: Users },
        { title: 'Orders', url: '/orders', icon: ShoppingCart },
        { title: 'Products', url: '/products', icon: Package },
        { title: 'Settings', url: '/settings', icon: Settings },
    ],
    user: {
        name: 'Admin',
        email: 'admin@example.com',
        avatar: '',
    },
};

export function AppSidebar({ ...props }) {
    return (
        <Sidebar collapsible="icon" {...props}>
            <SidebarHeader className="items-start px-2 py-4">
                <Logo />
            </SidebarHeader>
            <SidebarContent>
                <NavMain items={data.navMain} />
            </SidebarContent>
            <SidebarFooter>
                <NavUser user={data.user} />
            </SidebarFooter>
            <SidebarRail />
        </Sidebar>
    );
}
