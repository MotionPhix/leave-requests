<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { type NavItem } from '@/types';
import {
  LayoutGrid,
  UsersIcon,
  FileTextIcon,
  Building2,
  BarChart3,
  ClipboardList,
  CalendarIcon,
  UserCheck
} from 'lucide-vue-next';

interface WorkspaceData {
  slug: string;
  uuid: string;
  name: string;
}

interface PageProps extends Record<string, any> {
  workspace: WorkspaceData;
}

const page = usePage<PageProps>();
const workspace = computed(() => page.props.workspace);

// Get current tenant parameters for route generation
const tenantParams = computed(() => ({
  tenant_slug: workspace.value?.slug,
  tenant_uuid: workspace.value?.uuid
}));

// Manager-specific navigation items
const managerNavItems = computed((): NavItem[] => [
  // Core Features
  {
    title: 'Dashboard',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.dashboard', tenantParams.value)
      : '#',
    icon: LayoutGrid
  },

  // My Leave Requests (Managers can also request leave)
  {
    title: 'My Leave Requests',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.leave-requests.index', tenantParams.value)
      : '#',
    icon: UserCheck,
  },

  // Team Management
  {
    title: 'Team Members',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.members.index', tenantParams.value)
      : '#',
    icon: UsersIcon,
  },

  // Leave Management & Approval
  {
    title: 'Leave Requests',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.leave-requests.index', tenantParams.value)
      : '#',
    icon: FileTextIcon,
  },

  // Department Management
  {
    title: 'Departments',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.departments.index', tenantParams.value)
      : '#',
    icon: Building2,
  },

  // Leave Configuration
  {
    title: 'Leave Types',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.leave-types.index', tenantParams.value)
      : '#',
    icon: ClipboardList,
  },
  {
    title: 'Holidays',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.holidays.index', tenantParams.value)
      : '#',
    icon: CalendarIcon,
  },

  // Reports for their department/team
  {
    title: 'Reports',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.reports.index', tenantParams.value)
      : '#',
    icon: BarChart3,
  }
]);

// Expose for use in other components
defineExpose({
  managerNavItems
});
</script>

<template>
  <!-- This component only exports the navigation items -->
  <div style="display: none;"></div>
</template>
