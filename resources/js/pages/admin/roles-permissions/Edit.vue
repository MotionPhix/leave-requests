<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Save, Trash2, AlertTriangle, Info } from 'lucide-vue-next';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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

interface Role {
    id: number;
    name: string;
    permissions: string[];
}

interface Props {
    role: Role;
    permissionGroups: PermissionGroup;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.role.name,
    permissions: [...props.role.permissions],
});

const showDeleteConfirm = ref(false);

const submit = () => {
    form.put(route('admin.roles.update', props.role.id), {
        onSuccess: () => {
            // Form will redirect on success
        },
    });
};

const deleteRole = () => {
    form.delete(route('admin.roles.destroy', props.role.id));
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

const isProtectedRole = () => {
    return ['Owner', 'Manager', 'HR', 'Employee'].includes(props.role.name);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <Link :href="route('admin.roles.index')">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Role Templates
                    </Button>
                </Link>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Edit Role Template</h1>
                    <p class="text-muted-foreground mt-2">
                        Update the role template: <strong>{{ role.name }}</strong>
                    </p>
                </div>
            </div>
            <div v-if="!isProtectedRole()" class="flex items-center space-x-2">
                <Button 
                    variant="destructive" 
                    size="sm"
                    @click="showDeleteConfirm = true"
                >
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete Template
                </Button>
            </div>
        </div>

        <!-- Protected Role Warning -->
        <Card v-if="isProtectedRole()" class="border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/50">
            <CardContent class="pt-4">
                <div class="flex items-start space-x-3">
                    <AlertTriangle class="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5" />
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                            Core System Role
                        </p>
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            This is a core system role. The name cannot be changed, but you can modify its permissions.
                            Deleting this role is not allowed as it may break system functionality.
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Role Details -->
                <div class="lg:col-span-1">
                    <Card>
                        <CardHeader>
                            <CardTitle>Role Details</CardTitle>
                            <CardDescription>
                                Basic information for this role template
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Role Name <span class="text-red-500">*</span></Label>
                                <Input 
                                    id="name"
                                    v-model="form.name"
                                    :disabled="isProtectedRole() || form.processing"
                                    placeholder="e.g., Manager, HR, Developer"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                                <p v-if="isProtectedRole()" class="text-xs text-muted-foreground">
                                    Core system role names cannot be changed
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
                    <span>Changes will apply to all workspaces using this role template</span>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('admin.roles.index')">
                        <Button variant="outline">
                            Cancel
                        </Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </div>
        </form>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteConfirm" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <Card class="w-full max-w-md mx-4">
                <CardHeader>
                    <CardTitle class="flex items-center space-x-2">
                        <AlertTriangle class="h-5 w-5 text-red-500" />
                        <span>Delete Role Template</span>
                    </CardTitle>
                    <CardDescription>
                        This action cannot be undone. This will permanently delete the role template and remove it from all workspaces.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <p class="text-sm">
                        Are you sure you want to delete the <strong>{{ role.name }}</strong> role template?
                    </p>
                    <div class="flex items-center justify-end space-x-3">
                        <Button variant="outline" @click="showDeleteConfirm = false">
                            Cancel
                        </Button>
                        <Button variant="destructive" @click="deleteRole" :disabled="form.processing">
                            {{ form.processing ? 'Deleting...' : 'Delete Role Template' }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>