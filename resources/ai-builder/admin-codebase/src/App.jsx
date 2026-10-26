import React from 'react';
import { Link, Navigate, Route, Routes } from 'react-router-dom';
import { AppSidebar } from './components/app-sidebar.jsx';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from './components/ui/breadcrumb.jsx';
import { Separator } from './components/ui/separator.jsx';
import { SidebarInset, SidebarProvider, SidebarTrigger } from './components/ui/sidebar.jsx';
import DashboardPage from './pages/dashboard-page.jsx';
import AnalyticsPage from './pages/analytics-page.jsx';
import UsersPage from './pages/users-page.jsx';
import OrdersPage from './pages/orders-page.jsx';
import ProductsPage from './pages/products-page.jsx';
import SettingsPage from './pages/settings-page.jsx';

function Shell({ title, children }) {
    return (
        <SidebarProvider>
            <AppSidebar />
            <SidebarInset>
                <header className="sidebar-header-trim flex shrink-0 items-center gap-2 transition-[width,height] ease-linear">
                    <div className="flex items-center gap-2 px-4">
                        <SidebarTrigger className="-ml-1" />
                        <Separator orientation="vertical" className="mr-2 h-4" />
                        <Breadcrumb>
                            <BreadcrumbList>
                                <BreadcrumbItem className="hidden md:block">
                                    <BreadcrumbLink asChild>
                                        <Link to="/">Home</Link>
                                    </BreadcrumbLink>
                                </BreadcrumbItem>
                                <BreadcrumbSeparator className="hidden md:block" />
                                <BreadcrumbItem>
                                    <BreadcrumbPage>{title}</BreadcrumbPage>
                                </BreadcrumbItem>
                            </BreadcrumbList>
                        </Breadcrumb>
                    </div>
                </header>
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    {children}
                </div>
            </SidebarInset>
        </SidebarProvider>
    );
}

export default function App() {
    return (
        <Routes>
            <Route path="/" element={<Shell title="Dashboard"><DashboardPage /></Shell>} />
            <Route path="/analytics" element={<Shell title="Analytics"><AnalyticsPage /></Shell>} />
            <Route path="/users" element={<Shell title="Users"><UsersPage /></Shell>} />
            <Route path="/orders" element={<Shell title="Orders"><OrdersPage /></Shell>} />
            <Route path="/products" element={<Shell title="Products"><ProductsPage /></Shell>} />
            <Route path="/settings" element={<Shell title="Settings"><SettingsPage /></Shell>} />
            <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
    );
}
