<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { type NavItem } from '@/types';
import {
  LayoutGrid,
  UsersIcon,
  FileTextIcon,
  UserPlusIcon,
  ClipboardList,
  CalendarIcon,
  UserCheck,
  BarChart3,
  FileSearch
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

// HR-specific navigation items
const hrNavItems = computed((): NavItem[] => [
  // Core Features
  {
    title: 'Dashboard',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.dashboard', tenantParams.value)
      : '#',
    icon: LayoutGrid
  },

  // My Leave Requests (HR staff can also request leave)
  {
    title: 'My Leave Requests',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.leave-requests.index', tenantParams.value)
      : '#',
    icon: UserCheck,
  },

  // Employee Management (HR's primary function)
  {
    title: 'All Employees',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.members.index', tenantParams.value)
      : '#',
    icon: UsersIcon,
  },
  {
    title: 'Employee Records',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.employee-records.index', tenantParams.value)
      : '#',
    icon: FileSearch,
  },
  {
    title: 'Invitations',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.invitations.index', tenantParams.value)
      : '#',
    icon: UserPlusIcon,
  },

  // Leave Management (HR processes most leave requests)
  {
    title: 'All Leave Requests',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.leave-requests.index', tenantParams.value)
      : '#',
    icon: FileTextIcon,
  },

  // Leave Policy Management
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

  // HR Reports and Analytics
  {
    title: 'HR Reports',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.reports.index', tenantParams.value)
      : '#',
    icon: BarChart3,
  }
]);

// Expose for use in other components
defineExpose({
  hrNavItems
});
</script>

<template>
  <!-- This component only exports the navigation items -->
  <div style="display: none;"></div>
</template>
