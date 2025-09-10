<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { computed } from 'vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem
} from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';
import { type NavItem } from '@/types';

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
  Users,
  Home,
  Star
} from 'lucide-vue-next';

interface WorkspaceData {
  slug: string;
  uuid: string;
  name: string;
}

interface UserRole {
  name: string;
}

interface AuthUser {
  roles?: UserRole[];
  role?: string;
  isOwner?: boolean;
  is_system_admin?: boolean;
  name: string;
  email: string;
}

interface PageProps extends Record<string, any> {
  auth: {
    user: AuthUser;
  };
  workspace: WorkspaceData;
}

const page = usePage<PageProps>();

const user = computed(() => page.props.auth.user);
const workspace = computed(() => page.props.workspace);

// Get current tenant parameters for route generation
const tenantParams = computed(() => ({
  tenant_slug: workspace.value?.slug,
  tenant_uuid: workspace.value?.uuid
}));

/**
 * Determines the user's primary role within the current workspace
 */
const getUserPrimaryRole = (): 'owner' | 'manager' | 'hr' | 'employee' => {
  if (user.value?.isOwner) {
    return 'owner';
  }

  if (!user.value?.roles || !Array.isArray(user.value.roles)) {
    return 'employee';
  }

  // Check for roles in priority order
  const roles = user.value.roles.map((role: any) => role.name.toLowerCase());
  
  if (roles.includes('manager')) {
    return 'manager';
  }
  
  if (roles.includes('hr') || roles.includes('human resources')) {
    return 'hr';
  }
  
  return 'employee';
};

// Define navigation items based on user role
const getNavItemsForRole = (role: string): NavItem[] => {
  const baseParams = tenantParams.value;
  
  switch (role) {
    case 'owner':
      return [
        {
          title: 'Dashboard',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.dashboard', baseParams)
            : '#',
          icon: Home,
        },
        {
          title: 'Team Members',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.members.index', baseParams)
            : '#',
          icon: UsersIcon,
        },
        {
          title: 'Leave Requests',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.leave-requests.index', baseParams)
            : '#',
          icon: CalendarDays,
        },
        {
          title: 'Calendar',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.calendar.index', baseParams)
            : '#',
          icon: CalendarIcon,
        },
        {
          title: 'Leave Types',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.leave-types.index', baseParams)
            : '#',
          icon: ClipboardList,
        },
        {
          title: 'Holidays',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.holidays.index', baseParams)
            : '#',
          icon: Star,
        },
        {
          title: 'Roles & Permissions',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.roles.index', baseParams)
            : '#',
          icon: ShieldCheck,
        },
        {
          title: 'Departments',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.departments.index', baseParams)
            : '#',
          icon: Building2,
        },
        {
          title: 'Reports',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.reports.index', baseParams)
            : '#',
          icon: BarChart3,
        },
        {
          title: 'Settings',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.settings.index', baseParams)
            : '#',
          icon: SettingsIcon,
        },
      ];
    case 'manager':
      return [
        {
          title: 'Dashboard',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.dashboard', baseParams)
            : '#',
          icon: LayoutDashboard,
        },
        {
          title: 'Team Members',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.members.index', baseParams)
            : '#',
          icon: UsersIcon,
        },
        {
          title: 'Leave Requests',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.leave-requests.index', baseParams)
            : '#',
          icon: CalendarDays,
        },
        {
          title: 'Calendar',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.calendar.index', baseParams)
            : '#',
          icon: CalendarIcon,
        },
        {
          title: 'Leave Types',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.leave-types.index', baseParams)
            : '#',
          icon: ClipboardList,
        },
        {
          title: 'Holidays',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.holidays.index', baseParams)
            : '#',
          icon: Star,
        },
        {
          title: 'Departments',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.departments.index', baseParams)
            : '#',
          icon: Building2,
        },
        {
          title: 'Reports',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.reports.index', baseParams)
            : '#',
          icon: BarChart3,
        }
      ];
    case 'hr':
      return [
        {
          title: 'Dashboard',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.dashboard', baseParams)
            : '#',
          icon: LayoutDashboard,
        },
        {
          title: 'Members',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.members.index', baseParams)
            : '#',
          icon: UsersIcon,
        },
        {
          title: 'Leave Requests',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.leave-requests.index', baseParams)
            : '#',
          icon: CalendarDays,
        },
        {
          title: 'Calendar',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.calendar.index', baseParams)
            : '#',
          icon: CalendarIcon,
        },
        {
          title: 'Leave Types',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.leave-types.index', baseParams)
            : '#',
          icon: ClipboardList,
        },
        {
          title: 'Holidays',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.holidays.index', baseParams)
            : '#',
          icon: Star,
        },
        {
          title: 'Reports',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.management.reports.index', baseParams)
            : '#',
          icon: BarChart3,
        }
      ];
    case 'employee':
    default:
      return [
        {
          title: 'Dashboard',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.dashboard', baseParams)
            : '#',
          icon: LayoutDashboard,
        },
        {
          title: 'My Leave Requests',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.leave-requests.index', baseParams)
            : '#',
          icon: CalendarDays,
        },
        {
          title: 'Apply for Leave',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.leave-requests.create', baseParams)
            : '#',
          icon: FileText,
        },
        {
          title: 'Team Members',
          href: baseParams.tenant_slug && baseParams.tenant_uuid
            ? route('tenant.members.index', baseParams)
            : '#',
          icon: Users,
        }
      ];
  }
};

const primaryRole = computed(() => getUserPrimaryRole());
const navItems = computed(() => getNavItemsForRole(primaryRole.value));
const userRoleDisplay = computed(() => {
  switch (primaryRole.value) {
    case 'owner': return 'Owner';
    case 'manager': return 'Manager'; 
    case 'hr': return 'HR';
    case 'employee': 
    default: return 'Employee';
  }
});
const isAdmin = computed(() => ['owner', 'manager', 'hr'].includes(primaryRole.value));

// Determine the correct dashboard route based on user role
const dashboardRoute = computed(() => {
  if (!tenantParams.value.tenant_slug || !tenantParams.value.tenant_uuid) {
    return '#';
  }
  
  // Management roles use management dashboard
  if (['owner', 'manager', 'hr'].includes(primaryRole.value)) {
    return route('tenant.management.dashboard', tenantParams.value);
  }
  
  // Employees use regular dashboard
  return route('tenant.dashboard', tenantParams.value);
});
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="dashboardRoute">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <!-- Role-based navigation -->
      <NavMain :items="navItems" />
    </SidebarContent>

    <SidebarFooter>
      <!-- Workspace Info -->
      <div class="px-2 py-2 text-xs text-muted-foreground border-t">
        <div class="font-medium text-foreground">{{ workspace?.name }}</div>
        <div class="truncate">{{ workspace?.slug }}</div>
        <div class="text-xs text-muted-foreground mt-1">
          Role: {{ userRoleDisplay }}
          <span v-if="isAdmin" class="text-blue-600 dark:text-blue-400 ml-1">•</span>
        </div>
      </div>

      <NavUser />
    </SidebarFooter>
  </Sidebar>
</template>
