import * as React from 'react';
import { Save } from 'lucide-react';
import { Button } from '@/components/ui/button.jsx';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card.jsx';
import { Input } from '@/components/ui/input.jsx';
import { Label } from '@/components/ui/label.jsx';
import { PageHeader } from '@/components/ui/page-header.jsx';
import { Select } from '@/components/ui/select.jsx';
import { Textarea } from '@/components/ui/textarea.jsx';

export default function SettingsPage() {
    return (
        <div className="mx-auto flex w-full max-w-3xl flex-col gap-6">
            <PageHeader title="Settings" description="Manage your workspace and preferences." />

            <Card>
                <CardHeader>
                    <CardTitle>Profile</CardTitle>
                    <CardDescription>This information is displayed on your profile.</CardDescription>
                </CardHeader>
                <CardContent className="flex flex-col gap-4">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="name">Full name</Label>
                            <Input id="name" defaultValue="Olivia Martin" />
                        </div>
                        <div className="flex flex-col gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input id="email" type="email" defaultValue="olivia.martin@email.com" />
                        </div>
                    </div>
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="bio">Bio</Label>
                        <Textarea id="bio" placeholder="Tell us a little about yourself" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Appearance</CardTitle>
                    <CardDescription>Customize how the dashboard looks for you.</CardDescription>
                </CardHeader>
                <CardContent className="flex flex-col gap-4">
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="theme">Theme</Label>
                        <Select id="theme" defaultValue="light">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                            <option value="system">System</option>
                        </Select>
                    </div>
                    <div className="flex flex-col gap-2">
                        <Label htmlFor="lang">Language</Label>
                        <Select id="lang" defaultValue="en">
                            <option value="en">English</option>
                            <option value="es">Spanish</option>
                            <option value="fr">French</option>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Notifications</CardTitle>
                    <CardDescription>Choose which emails you want to receive.</CardDescription>
                </CardHeader>
                <CardContent className="flex flex-col gap-4">
                    <div className="flex items-center justify-between rounded-lg border border-border p-4">
                        <div>
                            <p className="text-sm font-medium">New orders</p>
                            <p className="text-xs text-muted-foreground">Get notified when a customer places an order.</p>
                        </div>
                        <input type="checkbox" defaultChecked className="size-4 accent-[var(--ds-primary)]" />
                    </div>
                    <div className="flex items-center justify-between rounded-lg border border-border p-4">
                        <div>
                            <p className="text-sm font-medium">Weekly digest</p>
                            <p className="text-xs text-muted-foreground">A summary of activity every Monday morning.</p>
                        </div>
                        <input type="checkbox" className="size-4 accent-[var(--ds-primary)]" />
                    </div>
                </CardContent>
            </Card>

            <div className="flex justify-end">
                <Button>
                    <Save className="size-4" />
                    Save changes
                </Button>
            </div>
        </div>
    );
}
