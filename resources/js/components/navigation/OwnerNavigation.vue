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
  Building2,
  BarChart3,
  ClipboardList,
  CalendarIcon,
  SettingsIcon,
  CreditCard,
  Shield
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

// Owner-specific navigation items
const ownerNavItems = computed((): NavItem[] => [
  // Core Features
  {
    title: 'Dashboard',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.dashboard', tenantParams.value)
      : '#',
    icon: LayoutGrid
  },

  // Team Management
  {
    title: 'Team Members',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.members.index', tenantParams.value)
      : '#',
    icon: UsersIcon,
  },
  {
    title: 'Invitations',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.invitations.index', tenantParams.value)
      : '#',
    icon: UserPlusIcon,
  },

  // Leave Management
  {
    title: 'Leave Requests',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.leave-requests.index', tenantParams.value)
      : '#',
    icon: FileTextIcon,
  },

  // Organization Setup
  {
    title: 'Departments',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.departments.index', tenantParams.value)
      : '#',
    icon: Building2,
  },
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

  // Analytics & Reports
  {
    title: 'Reports',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.reports.index', tenantParams.value)
      : '#',
    icon: BarChart3,
  },

  // Owner-specific features
  {
    title: 'Billing & Plans',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.billing.index', tenantParams.value)
      : '#',
    icon: CreditCard,
  },
  {
    title: 'Permissions',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.permissions.index', tenantParams.value)
      : '#',
    icon: Shield,
  },
  {
    title: 'Settings',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.settings.index', tenantParams.value)
      : '#',
    icon: SettingsIcon,
  }
]);

// Expose for use in other components
defineExpose({
  ownerNavItems
});
</script>

<template>
  <!-- This component only exports the navigation items -->
  <div style="display: none;"></div>
</template>
