<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { type NavItem } from '@/types';

// Import all navigation components
// Note: We need to remove these imports since the components no longer export items
// import { ownerNavItems } from '@/components/navigation/OwnerNavigation.vue';
// import { managerNavItems } from '@/components/navigation/ManagerNavigation.vue';
// import { hrNavItems } from '@/components/navigation/HrNavigation.vue';
// import { employeeNavItems } from '@/components/navigation/EmployeeNavigation.vue';

// Import all necessary icons
import {
  LayoutDashboard,
  UsersIcon,
  CalendarDays,
  FileText,
  SettingsIcon,
  Building2,
  BadgeDollarSign,
  ShieldCheck,
  TrendingUp,
  CalendarIcon,
  ClipboardList,
  BarChart3,
  Users
} from 'lucide-vue-next';

interface User {
  id: string | number;
  name: string;
  email: string;
  roles?: Array<{
    name: string;
    pivot?: {
      tenant_id?: string;
    };
  }>;
  isOwner?: boolean;
  is_system_admin?: boolean;
}

interface Workspace {
  id: string | number;
  name: string;
  slug: string;
  uuid: string;
}

interface PageProps extends Record<string, any> {
  auth: {
    user: User;
  };
  workspace?: Workspace;
}

const page = usePage<PageProps>();
const user = page.props.auth.user;
const workspace = page.props.workspace;

// Get tenant parameters for route generation
const tenantParams = computed(() => ({
  tenant_slug: workspace?.slug || '',
  tenant_uuid: workspace?.uuid || ''
}));

/**
 * Determines the user's primary role within the current workspace
 */
const getUserPrimaryRole = (): 'owner' | 'manager' | 'hr' | 'employee' => {
  if (user?.isOwner) {
    return 'owner';
  }

  if (!user?.roles || !Array.isArray(user.roles)) {
    return 'employee';
  }

  // Check for roles in priority order
  const roles = user.roles.map(role => role.name.toLowerCase());
  
  if (roles.includes('manager')) {
    return 'manager';
  }
  
  if (roles.includes('hr') || roles.includes('human resources')) {
    return 'hr';
  }
  
  return 'employee';
};

// Define navigation items for each role
const ownerNavItems = computed((): NavItem[] => [
  {
    title: 'Dashboard',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.dashboard', tenantParams.value)
      : '#',
    icon: LayoutDashboard,
  },
  {
    title: 'Team Members',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.members.index', tenantParams.value)
      : '#',
    icon: UsersIcon,
  },
  {
    title: 'Leave Management',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.leave-requests.index', tenantParams.value)
      : '#',
    icon: CalendarDays,
  },
  {
    title: 'Leave Types',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.leave-types.index', tenantParams.value)
      : '#',
    icon: ClipboardList,
  },
  {
    title: 'Departments',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.departments.index', tenantParams.value)
      : '#',
    icon: Building2,
  },
  {
    title: 'Holidays',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.holidays.index', tenantParams.value)
      : '#',
    icon: CalendarIcon,
  },
  {
    title: 'Reports',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.reports.index', tenantParams.value)
      : '#',
    icon: BarChart3,
  },
  {
    title: 'Roles & Permissions',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.roles-permissions.index', tenantParams.value)
      : '#',
    icon: ShieldCheck,
  },
  {
    title: 'Settings',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.settings.index', tenantParams.value)
      : '#',
    icon: SettingsIcon,
  }
]);

const managerNavItems = computed((): NavItem[] => [
  {
    title: 'Dashboard',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.dashboard', tenantParams.value)
      : '#',
    icon: LayoutDashboard,
  },
  {
    title: 'Team Members',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.members.index', tenantParams.value)
      : '#',
    icon: UsersIcon,
  },
  {
    title: 'Leave Management',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.leave-requests.index', tenantParams.value)
      : '#',
    icon: CalendarDays,
  },
  {
    title: 'Reports',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.reports.index', tenantParams.value)
      : '#',
    icon: BarChart3,
  }
]);

const hrNavItems = computed((): NavItem[] => [
  {
    title: 'Dashboard',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.dashboard', tenantParams.value)
      : '#',
    icon: LayoutDashboard,
  },
  {
    title: 'Team Members',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.members.index', tenantParams.value)
      : '#',
    icon: UsersIcon,
  },
  {
    title: 'Leave Management',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.leave-requests.index', tenantParams.value)
      : '#',
    icon: CalendarDays,
  },
  {
    title: 'Leave Types',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.leave-types.index', tenantParams.value)
      : '#',
    icon: ClipboardList,
  },
  {
    title: 'Holidays',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.holidays.index', tenantParams.value)
      : '#',
    icon: CalendarIcon,
  },
  {
    title: 'Reports',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.management.reports.index', tenantParams.value)
      : '#',
    icon: BarChart3,
  }
]);

const employeeNavItems = computed((): NavItem[] => [
  {
    title: 'Dashboard',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.dashboard', tenantParams.value)
      : '#',
    icon: LayoutDashboard,
  },
  {
    title: 'My Leave Requests',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.leave-requests.index', tenantParams.value)
      : '#',
    icon: CalendarDays,
  },
  {
    title: 'Apply for Leave',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.leave-requests.create', tenantParams.value)
      : '#',
    icon: FileText,
  },
  {
    title: 'Team Calendar',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.calendar.index', tenantParams.value)
      : '#',
    icon: CalendarIcon,
  },
  {
    title: 'Team Members',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.members.index', tenantParams.value)
      : '#',
    icon: Users,
  }
]);

/**
 * Returns the appropriate navigation items based on user role
 */
const getNavigationItems = computed((): NavItem[] => {
  const primaryRole = getUserPrimaryRole();
  
  switch (primaryRole) {
    case 'owner':
      return ownerNavItems.value;
    case 'manager':
      return managerNavItems.value;
    case 'hr':
      return hrNavItems.value;
    case 'employee':
    default:
      return employeeNavItems.value;
  }
});

/**
 * Returns the user's role display name
 */
const getUserRoleDisplay = computed((): string => {
  const primaryRole = getUserPrimaryRole();
  
  switch (primaryRole) {
    case 'owner':
      return 'Owner';
    case 'manager':
      return 'Manager';
    case 'hr':
      return 'HR';
    case 'employee':
    default:
      return 'Employee';
  }
});

/**
 * Determines if user has administrative privileges
 */
const isAdminUser = computed((): boolean => {
  const primaryRole = getUserPrimaryRole();
  return ['owner', 'manager', 'hr'].includes(primaryRole);
});

// Expose for use in template and other components
defineExpose({
  getNavigationItems,
  getUserPrimaryRole,
  getUserRoleDisplay,
  isAdminUser
});
</script>

<template>
  <!-- This component only exports computed properties -->
  <div style="display: none;"></div>
</template>
