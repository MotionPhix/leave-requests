<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Plus, Info } from 'lucide-vue-next';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: AppLayout,
});

interface Permission {
    name: string;
    group: string;
}

interface PermissionGroup {
    [key: string]: Permission[];
}

interface Props {
    permissionGroups: PermissionGroup;
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    label: '',
    description: '',
    permissions: [] as string[],
});

const submit = () => {
    form.post(route('admin.roles.store'), {
        onSuccess: () => {
            // Form will redirect on success
        },
    });
};

const togglePermission = (permissionName: string, checked: boolean) => {
    if (checked) {
        form.permissions.push(permissionName);
    } else {
        const index = form.permissions.indexOf(permissionName);
        if (index > -1) {
            form.permissions.splice(index, 1);
        }
    }
};

const toggleGroupPermissions = (groupPermissions: Permission[], checked: boolean) => {
    groupPermissions.forEach(permission => {
        const index = form.permissions.indexOf(permission.name);
        if (checked && index === -1) {
            form.permissions.push(permission.name);
        } else if (!checked && index > -1) {
            form.permissions.splice(index, 1);
        }
    });
};

const isGroupFullySelected = (groupPermissions: Permission[]) => {
    return groupPermissions.every(permission => 
        form.permissions.includes(permission.name)
    );
};

const isGroupPartiallySelected = (groupPermissions: Permission[]) => {
    const selectedCount = groupPermissions.filter(permission => 
        form.permissions.includes(permission.name)
    ).length;
    return selectedCount > 0 && selectedCount < groupPermissions.length;
};

const formatPermissionName = (name: string) => {
    return name.replace(/[._]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const formatGroupName = (name: string) => {
    return name.charAt(0).toUpperCase() + name.slice(1);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center space-x-4">
            <Link :href="route('admin.roles.index')">
                <Button variant="ghost" size="sm">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Role Templates
                </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Create Role Template</h1>
                <p class="text-muted-foreground mt-2">
                    Create a new role template that can be used across all workspaces
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Role Details -->
                <div class="lg:col-span-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Details</CardTitle>
                            <CardDescription>
                                Define the basic information for this role template
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Role Name <span class="text-red-500">*</span></Label>
                                <Input 
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g., Manager, HR, Developer"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    This will be the internal name used across the system
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="label">Display Label <span class="text-red-500">*</span></Label>
                                <Input 
                                    id="label"
                                    v-model="form.label"
                                    placeholder="e.g., Project Manager, Human Resources"
                                    :class="{ 'border-red-500': form.errors.label }"
                                />
                                <p v-if="form.errors.label" class="text-sm text-red-500">
                                    {{ form.errors.label }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    This is the friendly name shown to users
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <Textarea 
                                    id="description"
                                    v-model="form.description"
                                    placeholder="Brief description of this role's responsibilities..."
                                    rows="3"
                                    :class="{ 'border-red-500': form.errors.description }"
                                />
                                <p v-if="form.errors.description" class="text-sm text-red-500">
                                    {{ form.errors.description }}
                                </p>
                            </div>

                            <div v-if="form.permissions.length > 0" class="space-y-2">
                                <Label>Selected Permissions</Label>
                                <div class="flex flex-wrap gap-1">
                                    <Badge 
                                        v-for="permission in form.permissions" 
                                        :key="permission" 
                                        variant="secondary"
                                        class="text-xs"
                                    >
                                        {{ formatPermissionName(permission) }}
                                    </Badge>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ form.permissions.length }} permissions selected
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Permissions Selection -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Permissions <span class="text-red-500">*</span></CardTitle>
                            <CardDescription>
                                Select the permissions this role should have
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="form.errors.permissions" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                                <p class="text-sm text-red-600">{{ form.errors.permissions }}</p>
                            </div>

                            <div class="space-y-6">
                                <div v-for="(permissions, group) in permissionGroups" :key="group">
                                    <div class="space-y-3">
                                        <!-- Group Header with Select All -->
                                        <div class="flex items-center space-x-3 pb-2 border-b">
                                            <Checkbox
                                                :id="`group-${group}`"
                                                :checked="isGroupFullySelected(permissions)"
                                                :indeterminate="isGroupPartiallySelected(permissions)"
                                                @update:checked="(checked) => toggleGroupPermissions(permissions, checked)"
                                            />
                                            <Label :for="`group-${group}`" class="font-semibold text-base cursor-pointer">
                                                {{ formatGroupName(group) }}
                                            </Label>
                                            <Badge variant="outline" class="text-xs">
                                                {{ permissions.length }} permissions
                                            </Badge>
                                        </div>

                                        <!-- Individual Permissions -->
                                        <div class="grid gap-3 ml-6 md:grid-cols-2">
                                            <div 
                                                v-for="permission in permissions" 
                                                :key="permission.name"
                                                class="flex items-center space-x-2"
                                            >
                                                <Checkbox
                                                    :id="permission.name"
                                                    :checked="form.permissions.includes(permission.name)"
                                                    @update:checked="(checked) => togglePermission(permission.name, checked)"
                                                />
                                                <Label 
                                                    :for="permission.name" 
                                                    class="text-sm cursor-pointer text-muted-foreground"
                                                >
                                                    {{ formatPermissionName(permission.name) }}
                                                </Label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-6 border-t">
                <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                    <Info class="h-4 w-4" />
                    <span>This role template will be available to all workspaces</span>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('admin.roles.index')">
                        <Button variant="outline">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Plus class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Creating...' : 'Create Role Template' }}
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>