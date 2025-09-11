<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { 
  Plus, 
  Users, 
  Shield, 
  Eye, 
  Edit, 
  MoreHorizontal,
  Settings,
  UserCog
} from 'lucide-vue-next';

interface Role {
  id: number;
  uuid: string;
  name: string;
  permissions_count: number;
  users_count: number;
  created_at: string;
}

interface AssignableRole {
  id: number;
  name: string;
  label: string;
}

interface RoleDefinition {
  label: string;
  description: string;
  permissions: string[];
  assignable_by: string[];
}

const props = defineProps<{
  workspace: { uuid: string; slug: string; name: string };
  roles: Role[];
  assignableRoles: AssignableRole[];
  canCreateRoles: boolean;
  roleDefinitions: Record<string, RoleDefinition>;
}>();

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getRoleDescription = (roleName: string) => {
  return props.roleDefinitions[roleName]?.description || 'Custom role with specific permissions';
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
  <Head title="Roles & Permissions" />
  
  <TenantLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Roles & Permissions</h1>
          <p class="text-muted-foreground">
            Manage user roles and permissions for {{ workspace.name }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            v-if="canCreateRoles"
            :href="route('tenant.management.roles-permissions.create', {
              tenant_slug: workspace.slug,
              tenant_uuid: workspace.uuid
            })"
            :as="Button"
          >
            <Plus class="w-4 h-4 mr-2" />
            Create Role
          </Link>
        </div>
      </div>

      <!-- Overview Cards -->
      <div class="grid md:grid-cols-3 gap-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Roles</CardTitle>
            <Shield class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ roles.length }}</div>
            <p class="text-xs text-muted-foreground">Available in workspace</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Assignable Roles</CardTitle>
            <UserCog class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ assignableRoles.length }}</div>
            <p class="text-xs text-muted-foreground">You can assign</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Users</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ roles.reduce((sum, role) => sum + role.users_count, 0) }}</div>
            <p class="text-xs text-muted-foreground">With assigned roles</p>
          </CardContent>
        </Card>
      </div>

      <!-- Roles Table -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Settings class="w-5 h-5" />
            Workspace Roles
          </CardTitle>
          <CardDescription>
            Manage roles and their permissions for this workspace
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Role</TableHead>
                <TableHead>Description</TableHead>
                <TableHead class="text-center">Permissions</TableHead>
                <TableHead class="text-center">Users</TableHead>
                <TableHead class="text-center">Created</TableHead>
                <TableHead class="text-center">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="role in roles" :key="role.uuid">
                <TableCell>
                  <div class="flex items-center gap-3">
                    <Badge :class="getRoleColor(role.name)" variant="secondary">
                      {{ role.name }}
                    </Badge>
                  </div>
                </TableCell>
                <TableCell>
                  <div class="max-w-xs">
                    <p class="text-sm text-muted-foreground line-clamp-2">
                      {{ getRoleDescription(role.name) }}
                    </p>
                  </div>
                </TableCell>
                <TableCell class="text-center">
                  <Badge variant="outline">
                    {{ role.permissions_count }}
                  </Badge>
                </TableCell>
                <TableCell class="text-center">
                  <Badge variant="outline">
                    {{ role.users_count }}
                  </Badge>
                </TableCell>
                <TableCell class="text-center text-sm text-muted-foreground">
                  {{ formatDate(role.created_at) }}
                </TableCell>
                <TableCell class="text-center">
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="ghost" size="sm">
                        <MoreHorizontal class="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem as-child>
                        <Link
                          :href="route('tenant.management.roles-permissions.show', {
                            tenant_slug: workspace.slug,
                            tenant_uuid: workspace.uuid,
                            role: role.uuid
                          })"
                        >
                          <Eye class="h-4 w-4 mr-2" />
                          View Details
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem v-if="canCreateRoles" as-child>
                        <Link
                          :href="route('tenant.management.roles-permissions.edit', {
                            tenant_slug: workspace.slug,
                            tenant_uuid: workspace.uuid,
                            role: role.uuid
                          })"
                        >
                          <Edit class="h-4 w-4 mr-2" />
                          Edit Role
                        </Link>
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </TenantLayout>
</template>