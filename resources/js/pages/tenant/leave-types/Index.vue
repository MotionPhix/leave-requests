<template>
  <TenantLayout>
    <Head title="Leave Types" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Leave Types</h1>
          <p class="text-muted-foreground">
            Manage the types of leave available in your workspace
          </p>
        </div>
        <Link
          :href="route('tenant.leave-types.create', {
            tenant_slug: $page.props.workspace.slug,
            tenant_uuid: $page.props.workspace.uuid
          })"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        >
          <Plus class="h-4 w-4" />
          Add Leave Type
        </Link>
      </div>

      <!-- Leave Types Grid -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="leaveType in leaveTypes"
          :key="leaveType.id"
          class="bg-card border rounded-lg p-6 space-y-4"
        >
          <div class="flex items-start justify-between">
            <div class="space-y-2">
              <h3 class="font-medium text-card-foreground">{{ leaveType.name }}</h3>
              <p class="text-sm text-muted-foreground">{{ leaveType.description }}</p>
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
                    :href="route('tenant.leave-types.edit', {
                      tenant_slug: $page.props.workspace.slug,
                      tenant_uuid: $page.props.workspace.uuid,
                      leave_type: leaveType.id
                    })"
                  >
                    <Edit class="h-4 w-4 mr-2" />
                    Edit
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem
                  @click="deleteLeaveType(leaveType)"
                  class="text-destructive"
                >
                  <Trash2 class="h-4 w-4 mr-2" />
                  Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>

          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-muted-foreground">Max Days:</span>
              <div class="font-medium">{{ leaveType.max_days_per_year || 'Unlimited' }}</div>
            </div>
            <div>
              <span class="text-muted-foreground">Carry Forward:</span>
              <div class="font-medium">
                <Badge :variant="leaveType.can_carry_forward ? 'default' : 'secondary'">
                  {{ leaveType.can_carry_forward ? 'Yes' : 'No' }}
                </Badge>
              </div>
            </div>
          </div>

          <div class="text-sm">
            <span class="text-muted-foreground">Requires Approval:</span>
            <Badge :variant="leaveType.requires_approval ? 'default' : 'secondary'" class="ml-2">
              {{ leaveType.requires_approval ? 'Yes' : 'No' }}
            </Badge>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="leaveTypes.length === 0" class="text-center py-12">
        <div class="mx-auto w-24 h-24 bg-muted rounded-full flex items-center justify-center mb-4">
          <FileText class="h-8 w-8 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-medium text-foreground mb-2">No leave types yet</h3>
        <p class="text-muted-foreground mb-6">
          Get started by creating your first leave type for your workspace.
        </p>
        <Link
          :href="route('tenant.leave-types.create', {
            tenant_slug: $page.props.workspace.slug,
            tenant_uuid: $page.props.workspace.uuid
          })"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        >
          <Plus class="h-4 w-4" />
          Add First Leave Type
        </Link>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
  MoreHorizontal,
  Edit,
  Trash2,
  FileText
} from 'lucide-vue-next';

interface LeaveType {
  id: number;
  name: string;
  description: string;
  max_days_per_year: number | null;
  can_carry_forward: boolean;
  requires_approval: boolean;
  created_at: string;
  updated_at: string;
}

defineProps<{
  leaveTypes: LeaveType[];
}>();

const deleteLeaveType = (leaveType: LeaveType) => {
  if (confirm(`Are you sure you want to delete "${leaveType.name}"? This action cannot be undone.`)) {
    router.delete(
      route('tenant.leave-types.destroy', {
        tenant_slug: window.location.pathname.split('/')[1],
        tenant_uuid: window.location.pathname.split('/')[2],
        leave_type: leaveType.id
      }),
      {
        preserveScroll: true,
      }
    );
  }
};
</script>
