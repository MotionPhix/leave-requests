<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    Table, 
    TableBody, 
    TableCell, 
    TableHead, 
    TableHeader, 
    TableRow 
} from '@/components/ui/table';
import { Plus, ShieldCheck, Users, Settings, Calendar } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

defineOptions({
    layout: AdminLayout,
});

interface Permission {
    name: string;
    group: string;
}

interface PermissionGroup {
    [key: string]: Permission[];
}

interface RoleDefinition {
    name: string;
    label: string;
    description: string;
    permissions: string[];
    color: string;
    icon: any;
}

interface SystemRole {
    id: number;
    name: string;
    permissions_count: number;
    workspaces_count: number;
    created_at: string;
}

interface Props {
    roleDefinitions: Record<string, RoleDefinition>;
    systemRoles: SystemRole[];
    permissionGroups: PermissionGroup;
}

defineProps<Props>();

const getRoleColor = (roleName: string) => {
    const colors: Record<string, string> = {
        'Owner': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
        'Manager': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        'HR': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        'Employee': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
    };
    return colors[roleName] || 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400';
};

const getRoleIcon = (roleName: string) => {
    const icons: Record<string, any> = {
        'Owner': Settings,
        'Manager': Users,
        'HR': Users,
        'Employee': Calendar,
    };
    return icons[roleName] || ShieldCheck;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Role Templates</h1>
                <p class="text-muted-foreground mt-2">
                    Manage system-wide role templates that can be used across all workspaces
                </p>
            </div>
            <Link :href="route('admin.roles.create')">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Create Role Template
                </Button>
            </Link>
        </div>

        <!-- Role Definitions Overview -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card v-for="(role, name) in roleDefinitions" :key="name">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">{{ role.label }}</CardTitle>
                    <component :is="getRoleIcon(name)" class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <Badge :class="getRoleColor(name)" class="text-xs">
                            {{ name }}
                        </Badge>
                        <p class="text-xs text-muted-foreground">
                            {{ role.description }}
                        </p>
                        <div class="text-xs text-muted-foreground">
                            {{ role.permissions.length }} permissions
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- System Roles Table -->
        <Card>
            <CardHeader>
                <CardTitle>System Role Templates</CardTitle>
                <CardDescription>
                    These are the actual role templates that exist in the system and are used by workspaces
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Role Name</TableHead>
                            <TableHead>Permissions</TableHead>
                            <TableHead>Workspaces Using</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="systemRoles.length === 0">
                            <TableCell colspan="5" class="text-center py-6 text-muted-foreground">
                                No role templates found. Create your first role template to get started.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="role in systemRoles" :key="role.id">
                            <TableCell class="font-medium">
                                <div class="flex items-center space-x-2">
                                    <Badge :class="getRoleColor(role.name)" class="text-xs">
                                        {{ role.name }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center space-x-1">
                                    <ShieldCheck class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ role.permissions_count }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center space-x-1">
                                    <Users class="h-4 w-4 text-muted-foreground" />
                                    <span>{{ role.workspaces_count }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ formatDate(role.created_at) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <Link :href="route('admin.roles.edit', role.id)">
                                        <Button variant="ghost" size="sm">
                                            Edit
                                        </Button>
                                    </Link>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>

        <!-- Permission Groups Overview -->
        <Card>
            <CardHeader>
                <CardTitle>Available Permission Groups</CardTitle>
                <CardDescription>
                    These are the permission groups available for role templates
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="(permissions, group) in permissionGroups" :key="group" 
                         class="rounded-lg border p-4 space-y-2">
                        <h3 class="font-semibold text-sm">{{ group.charAt(0).toUpperCase() + group.slice(1) }}</h3>
                        <div class="space-y-1">
                            <div v-for="permission in permissions" :key="permission.name" 
                                 class="text-xs text-muted-foreground">
                                {{ permission.name.replace(/[._]/g, ' ') }}
                            </div>
                        </div>
                        <Badge variant="secondary" class="text-xs">
                            {{ permissions.length }} permissions
                        </Badge>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>