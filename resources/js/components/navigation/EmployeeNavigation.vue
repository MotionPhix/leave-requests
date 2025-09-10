<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { type NavItem } from '@/types';
import {
  LayoutGrid,
  UserCheck,
  CalendarIcon,
  Users,
  FileText,
  Clock
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

// Employee-specific navigation items (minimal, focused on their own needs)
const employeeNavItems = computed((): NavItem[] => [
  // Core Features
  {
    title: 'Dashboard',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.dashboard', tenantParams.value)
      : '#',
    icon: LayoutGrid
  },

  // My Leave Management
  {
    title: 'My Leave Requests',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.leave-requests.index', tenantParams.value)
      : '#',
    icon: UserCheck,
  },
  {
    title: 'Request Leave',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.leave-requests.create', tenantParams.value)
      : '#',
    icon: FileText,
  },

  // Leave Balance & History
  {
    title: 'Leave Balance',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.leave-balance.index', tenantParams.value)
      : '#',
    icon: Clock,
  },

  // Company Information
  {
    title: 'Company Calendar',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.holidays.calendar', tenantParams.value)
      : '#',
    icon: CalendarIcon,
  },
  {
    title: 'Team Directory',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.team-directory.index', tenantParams.value)
      : '#',
    icon: Users,
  }
]);

// Expose for use in other components
defineExpose({
  employeeNavItems
});
</script>

<template>
  <!-- This component only exports the navigation items -->
  <div style="display: none;"></div>
</template>
