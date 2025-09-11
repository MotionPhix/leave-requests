<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Separator } from '@/components/ui/separator';
import { 
  ArrowLeft, 
  Edit, 
  Users, 
  Shield, 
  Settings,
  User,
  Check,
  UserPlus,
  UserMinus,
  Plus
} from 'lucide-vue-next';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Permission {
  id: number;
  name: string;
  group: string;
}

interface User {
  id: number;
  uuid: string;
  name: string;
  email: string;
  avatar?: string;
  current_roles?: string[];
}

interface Role {
  uuid: string;
  name: string;
  permissions: Permission[];
  users: User[];
  created_at: string;
}

const props = defineProps<{
  workspace: { uuid: string; slug: string; name: string };
  role: Role;
  assignableUsers: User[];
  permissionGroups: Record<string, string[]>;
  canManageRoles: boolean;
}>();

const selectedUserId = ref<number | null>(null);
const isAssigning = ref(false);
const isRemoving = ref<number | null>(null);

const assignForm = useForm({
  user_id: null as number | null,
  role_uuid: props.role.uuid,
});

const removeForm = useForm({
  user_id: null as number | null,
  role_uuid: props.role.uuid,
});

const assignUserToRole = () => {
  if (!selectedUserId.value) return;
  
  assignForm.user_id = selectedUserId.value;
  assignForm.post(route('tenant.management.roles-permissions.assign-role', {
    tenant_slug: props.workspace.slug,
    tenant_uuid: props.workspace.uuid,
  }), {
    preserveScroll: true,
    onSuccess: () => {
      selectedUserId.value = null;
      assignForm.reset();
    },
  });
};

const removeUserFromRole = (userId: number) => {
  isRemoving.value = userId;
  removeForm.user_id = userId;
  removeForm.delete(route('tenant.management.roles-permissions.remove-role', {
    tenant_slug: props.workspace.slug,
    tenant_uuid: props.workspace.uuid,
  }), {
    preserveScroll: true,
    onFinish: () => {
      isRemoving.value = null;
      removeForm.reset();
    },
  });
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getInitials = (name: string) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const groupPermissions = () => {
  const grouped: Record<string, Permission[]> = {};
  
  props.role.permissions.forEach(permission => {
    const group = permission.group;
    if (!grouped[group]) {
      grouped[group] = [];
    }
    grouped[group].push(permission);
  });
  
  return grouped;
};

const getGroupLabel = (group: string) => {
  const labels = {
    'users': 'User Management',
    'leave-requests': 'Leave Requests',
    'leave-types': 'Leave Types',
    'departments': 'Departments',
    'holidays': 'Holidays',
    'reports': 'Reports & Analytics',
    'roles': 'Roles & Permissions',
    'settings': 'Settings',
    'notifications': 'Notifications',
  };
  return labels[group] || group.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getPermissionLabel = (permission: string) => {
  const parts = permission.split('.');
  const action = parts[1];
  
  const actionLabels = {
    'view': 'View',
    'view-own': 'View Own',
    'view-team': 'View Team',
    'view-any': 'View All',
    'create': 'Create',
    'edit': 'Edit',
    'edit-own': 'Edit Own',
    'edit-team': 'Edit Team',
    'delete': 'Delete',
    'approve': 'Approve',
    'reject': 'Reject',
    'assign': 'Assign',
    'manage': 'Manage',
    'export': 'Export',
    'analytics': 'Analytics',
  };
  
  return actionLabels[action] || action.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getRoleColor = (roleName: string) => {
  const colors = {
    'Owner': 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    'Admin': 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
    'HR': 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    'Manager': 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
    'Employee': 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
  };
  return colors[roleName] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
};
</script>

<template>
  <Head :title="`${role.name} Role Details`" />
  
  <TenantLayout>
    <div class="space-y-6 max-w-5xl">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link
            :href="route('tenant.management.roles-permissions.index', {
              tenant_slug: workspace.slug,
              tenant_uuid: workspace.uuid
            })"
            :as="Button"
            variant="ghost"
            size="sm"
          >
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Roles
          </Link>
        </div>
        <div class="flex items-center gap-3">
          <Link
            v-if="canManageRoles"
            :href="route('tenant.management.roles-permissions.edit', {
              tenant_slug: workspace.slug,
              tenant_uuid: workspace.uuid,
              role: role.uuid
            })"
            :as="Button"
          >
            <Edit class="w-4 h-4 mr-2" />
            Edit Role
          </Link>
        </div>
      </div>

      <!-- Role Overview -->
      <Card>
        <CardHeader>
          <div class="flex items-start justify-between">
            <div class="space-y-3">
              <div class="flex items-center gap-3">
                <Badge :class="getRoleColor(role.name)" class="text-base px-3 py-1">
                  {{ role.name }}
                </Badge>
              </div>
              <div class="grid md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center gap-2">
                  <Shield class="w-4 h-4 text-muted-foreground" />
                  <span class="text-muted-foreground">Permissions:</span>
                  <span class="font-medium">{{ role.permissions.length }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Users class="w-4 h-4 text-muted-foreground" />
                  <span class="text-muted-foreground">Users:</span>
                  <span class="font-medium">{{ role.users.length }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <Settings class="w-4 h-4 text-muted-foreground" />
                  <span class="text-muted-foreground">Created:</span>
                  <span class="font-medium">{{ formatDate(role.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </CardHeader>
      </Card>

      <div class="grid lg:grid-cols-2 gap-6">
        <!-- Permissions -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Shield class="w-5 h-5" />
              Permissions
            </CardTitle>
            <CardDescription>
              What this role is allowed to do
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-for="(permissions, group) in groupPermissions()" :key="group" class="space-y-2">
              <h4 class="font-medium text-sm">{{ getGroupLabel(group) }}</h4>
              <div class="grid grid-cols-2 gap-2">
                <div 
                  v-for="permission in permissions" 
                  :key="permission.id"
                  class="flex items-center gap-2 text-xs p-2 bg-muted/50 rounded-md"
                >
                  <Check class="w-3 h-3 text-green-600" />
                  <span>{{ getPermissionLabel(permission.name) }}</span>
                </div>
              </div>
              <Separator v-if="Object.keys(groupPermissions()).indexOf(group) < Object.keys(groupPermissions()).length - 1" />
            </div>
          </CardContent>
        </Card>

        <!-- Users with this Role -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Users class="w-5 h-5" />
                Users ({{ role.users.length }})
              </div>
              <!-- Assign User Button -->
              <div v-if="canManageRoles && assignableUsers.length > 0" class="flex items-center gap-2">
                <Select v-model="selectedUserId" :disabled="assignForm.processing">
                  <SelectTrigger class="w-40">
                    <SelectValue placeholder="Select user" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="user in assignableUsers" :key="user.id" :value="user.id">
                      <div class="flex flex-col">
                        <span>{{ user.name }}</span>
                        <span v-if="user.current_roles && user.current_roles.length > 0" class="text-xs text-muted-foreground">
                          Current: {{ user.current_roles.join(', ') }}
                        </span>
                      </div>
                    </SelectItem>
                  </SelectContent>
                </Select>
                <Button 
                  @click="assignUserToRole" 
                  :disabled="!selectedUserId || assignForm.processing"
                  size="sm"
                >
                  <UserPlus class="w-4 h-4 mr-1" />
                  Assign
                </Button>
              </div>
            </CardTitle>
            <CardDescription>
              People assigned to this role
              <span v-if="canManageRoles && assignableUsers.length === 0" class="text-amber-600">
                • All eligible members are already assigned to this role or are Owners
              </span>
              <span v-else-if="canManageRoles" class="text-muted-foreground">
                • Assigning a new role will replace the user's current role
                • Owners cannot be reassigned to other roles
              </span>
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div v-if="role.users.length > 0" class="space-y-3">
              <div 
                v-for="user in role.users" 
                :key="user.id"
                class="flex items-center gap-3 p-3 border rounded-lg hover:bg-muted/50 transition-colors"
              >
                <Avatar class="h-8 w-8">
                  <AvatarImage :src="user.avatar" />
                  <AvatarFallback>{{ getInitials(user.name) }}</AvatarFallback>
                </Avatar>
                <div class="flex-1">
                  <p class="font-medium text-sm">{{ user.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                </div>
                <Button 
                  v-if="canManageRoles"
                  @click="removeUserFromRole(user.id)"
                  :disabled="isRemoving === user.id"
                  variant="ghost" 
                  size="sm"
                  class="text-destructive hover:text-destructive hover:bg-destructive/10"
                >
                  <UserMinus class="w-4 h-4" />
                </Button>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <User class="w-12 h-12 text-muted-foreground mx-auto mb-3" />
              <p class="text-sm text-muted-foreground">No users assigned to this role yet</p>
              <p v-if="canManageRoles && assignableUsers.length > 0" class="text-xs text-muted-foreground mt-2">
                Use the dropdown above to assign users to this role
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </TenantLayout>
</template>