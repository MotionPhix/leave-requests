<template>
  <TenantLayout>
    <Head title="Departments" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Departments</h1>
          <p class="text-muted-foreground">
            Organize your team into departments and assign department heads
          </p>
        </div>
        <Link
          :href="route('tenant.departments.create', {
            tenant_slug: $page.props.workspace.slug,
            tenant_uuid: $page.props.workspace.uuid
          })"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        >
          <Plus class="h-4 w-4" />
          Add Department
        </Link>
      </div>

      <!-- Departments Grid -->
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="department in departments"
          :key="department.id"
          class="bg-card border rounded-lg p-6 space-y-4"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                <Building2 class="h-5 w-5 text-primary" />
              </div>
              <div>
                <h3 class="font-medium text-card-foreground">{{ department.name }}</h3>
                <p class="text-sm text-muted-foreground">{{ department.description }}</p>
              </div>
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="sm">
                  <MoreHorizontal class="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem as-child>
                  <Link
                    :href="route('tenant.departments.edit', {
                      tenant_slug: $page.props.workspace.slug,
                      tenant_uuid: $page.props.workspace.uuid,
                      department: department.id
                    })"
                  >
                    <Edit class="h-4 w-4 mr-2" />
                    Edit
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem
                  @click="deleteDepartment(department)"
                  class="text-destructive"
                >
                  <Trash2 class="h-4 w-4 mr-2" />
                  Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

          <div class="space-y-3">
            <!-- Department Head -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-muted-foreground">Department Head:</span>
              <div class="flex items-center gap-2">
                <div v-if="department.head" class="flex items-center gap-2">
                  <div class="w-6 h-6 bg-primary/20 rounded-full flex items-center justify-center">
                    <User class="h-3 w-3 text-primary" />
                  </div>
                  <span class="text-sm font-medium">{{ department.head.name }}</span>
                </div>
                <Badge v-else variant="secondary">Unassigned</Badge>
              </div>
            </div>

            <!-- Employee Count -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-muted-foreground">Employees:</span>
              <Badge variant="outline">{{ department.employees_count || 0 }}</Badge>
            </div>

            <!-- Active Leave Requests -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-muted-foreground">Active Requests:</span>
              <Badge
                :variant="department.active_leave_requests_count > 0 ? 'default' : 'secondary'"
              >
                {{ department.active_leave_requests_count || 0 }}
              </Badge>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="pt-4 border-t flex gap-2">
            <Button
              variant="outline"
              size="sm"
              as-child
            >
              <Link
                :href="route('tenant.members.index', {
                  tenant_slug: $page.props.workspace.slug,
                  tenant_uuid: $page.props.workspace.uuid,
                  department: department.id
                })"
              >
                <Users class="h-4 w-4 mr-2" />
                View Members
              </Link>
            </Button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="departments.length === 0" class="text-center py-12">
        <div class="mx-auto w-24 h-24 bg-muted rounded-full flex items-center justify-center mb-4">
          <Building2 class="h-8 w-8 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-medium text-foreground mb-2">No departments yet</h3>
        <p class="text-muted-foreground mb-6">
          Start organizing your team by creating your first department.
        </p>
        <Link
          :href="route('tenant.departments.create', {
            tenant_slug: $page.props.workspace.slug,
            tenant_uuid: $page.props.workspace.uuid
          })"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        >
          <Plus class="h-4 w-4" />
          Create First Department
        </Link>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Plus,
  Building2,
  User,
  Users,
  MoreHorizontal,
  Edit,
  Trash2
} from 'lucide-vue-next';

interface User {
  id: number;
  name: string;
  email: string;
}

interface Department {
  id: number;
  name: string;
  description: string;
  head: User | null;
  employees_count: number;
  active_leave_requests_count: number;
  created_at: string;
  updated_at: string;
}

const props = defineProps<{
  departments: Department[];
}>();

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };

const deleteDepartment = (department: Department) => {
  if (department.employees_count > 0) {
    alert('Cannot delete a department that has employees assigned to it. Please reassign employees first.');
    return;
  }

  if (confirm(`Are you sure you want to delete "${department.name}"? This action cannot be undone.`)) {
    router.delete(
      route('tenant.departments.destroy', {
        tenant_slug: workspace.slug,
        tenant_uuid: workspace.uuid,
        department: department.id
      }),
      {
        preserveScroll: true,
      }
    );
  }
};
</script>
