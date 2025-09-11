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
          v-if="canManageLeaveTypes"
          :href="route('tenant.management.leave-types.create', {
            tenant_slug: workspace.slug,
            tenant_uuid: workspace.uuid
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
          v-for="leaveType in filteredLeaveTypes"
          :key="leaveType.id"
          class="bg-card border rounded-lg p-6 space-y-4 hover:shadow-md transition-shadow"
        >
          <!-- Header -->
          <div class="flex items-start justify-between">
            <div class="space-y-2 flex-1">
              <h3 class="text-lg font-semibold text-card-foreground">{{ leaveType?.name || 'Unnamed Leave Type' }}</h3>
              <p v-if="leaveType?.description" class="text-sm text-muted-foreground line-clamp-2">{{ leaveType.description }}</p>
            </div>
            <DropdownMenu v-if="canManageLeaveTypes">
              <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="sm" class="ml-2">
                  <MoreHorizontal class="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem as-child>
                  <Link
                    :href="route('tenant.management.leave-types.show', {
                      tenant_slug: workspace.slug,
                      tenant_uuid: workspace.uuid,
                      leaveType: leaveType.uuid
                    })"
                  >
                    <Eye class="h-4 w-4 mr-2" />
                    View Details
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                  <Link
                    :href="route('tenant.management.leave-types.edit', {
                      tenant_slug: workspace.slug,
                      tenant_uuid: workspace.uuid,
                      leaveType: leaveType.uuid
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

          <!-- Essential Details -->
          <div class="space-y-3 divide-y divide-muted-foreground/50">
            <div class="flex items-center justify-between pb-3">
              <span class="text-sm text-muted-foreground">Annual Allowance</span>
              <span class="text-sm font-semibold">{{ leaveType?.max_days_per_year || 'Unlimited' }} days</span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-muted-foreground">Pay Rate</span>
              <span class="text-sm font-semibold">{{ leaveType?.pay_percentage || 0 }}%</span>
            </div>
          </div>

          <!-- Key Features -->
          <div class="flex justify-between gap-2 pt-5 border-t border-muted-foreground/50">
            <Badge v-if="leaveType?.requires_documentation" variant="secondary" class="text-xs">
              📋 Docs Required
            </Badge>
            
            <Badge v-if="leaveType?.gender_specific" variant="secondary" class="text-xs">
              👤 {{ leaveType?.gender?.charAt(0).toUpperCase() + leaveType?.gender?.slice(1) }}
            </Badge>
            
            <Badge v-if="leaveType?.minimum_notice_days > 0" variant="outline" class="text-xs">
              ⏰ {{ leaveType?.minimum_notice_days }}d notice
            </Badge>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredLeaveTypes.length === 0" class="text-center py-12">
        <div class="mx-auto w-24 h-24 bg-muted rounded-full flex items-center justify-center mb-4">
          <FileText class="h-8 w-8 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-medium text-foreground mb-2">No leave types yet</h3>
        <p v-if="canManageLeaveTypes" class="text-muted-foreground mb-6">
          Get started by creating your first leave type for your workspace.
        </p>
        <p v-else class="text-muted-foreground mb-6">
          No leave types have been configured yet. Contact your workspace owner or HR to set up leave types.
        </p>
        <Link
          v-if="canManageLeaveTypes"
          :href="route('tenant.management.leave-types.create', {
            tenant_slug: workspace.slug,
            tenant_uuid: workspace.uuid
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
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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
  FileText,
  Eye
} from 'lucide-vue-next';

interface LeaveType {
  id: number;
  uuid: string;
  name: string;
  description: string | null;
  max_days_per_year: number | null;
  requires_documentation: boolean;
  gender_specific: boolean;
  gender: string;
  frequency_years: number;
  pay_percentage: number;
  minimum_notice_days: number;
  allow_negative_balance: boolean;
}

type CurrentUser = {
  uuid: string;
  role: string | null;
}

const props = defineProps<{
  leaveTypes: LeaveType[];
  currentUser?: CurrentUser;
}>();

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };

const filteredLeaveTypes = computed(() => {
  return (props.leaveTypes || []).filter(leaveType => leaveType && leaveType.id);
});

const canManageLeaveTypes = computed(() => {
  const role = props.currentUser?.role?.toLowerCase();
  return role === 'owner' || role === 'hr';
});

const deleteLeaveType = (leaveType: LeaveType) => {
  if (confirm(`Are you sure you want to delete "${leaveType.name}"? This action cannot be undone.`)) {
    router.delete(
      route('tenant.management.leave-types.destroy', {
        tenant_slug: workspace.slug,
        tenant_uuid: workspace.uuid,
        leaveType: leaveType.uuid
      }),
      {
        preserveScroll: true,
      }
    );
  }
};
</script>
