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
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import {
  CalendarIcon,
  LayoutGrid,
  UsersIcon,
  FileTextIcon,
  SettingsIcon,
  UserPlusIcon,
  Building2,
  BarChart3,
  ClipboardList
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

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
  permissions?: {
    canApproveLeave?: boolean;
    canViewAllUsers?: boolean;
    canCreateLeaveRequests?: boolean;
  };
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

// Main navigation items (for all users)
const mainNavItems = computed((): NavItem[] => [
  {
    title: 'Dashboard',
    href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
      ? route('tenant.dashboard', tenantParams.value)
      : '#',
    icon: LayoutGrid
  }
]);

// Leave requests navigation - only for users who can actually request leave
// Owners cannot request leave (they own the company!)
const canRequestLeave = computed(() => user.value?.permissions?.canCreateLeaveRequests && !user.value?.isOwner);

// Admin navigation items (shown based on permissions)
const adminNavItems = computed((): NavItem[] => {
  const items: NavItem[] = [];

  if (user.value?.permissions?.canViewAllUsers) {
    items.push({
      title: 'Team Members',
      href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
        ? route('tenant.members.index', tenantParams.value)
        : '#',
      icon: UsersIcon,
    });
  }

  if (user.value?.permissions?.canApproveLeave || user.value?.permissions?.canViewAllUsers) {
    items.push({
      title: 'All Leave Requests',
      href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
        ? route('tenant.admin.leave-requests.index', tenantParams.value)
        : '#',
      icon: FileTextIcon,
    });
  }

  if (user.value?.permissions?.canViewAllUsers) {
    items.push({
      title: 'Invitations',
      href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
        ? route('tenant.invitations.index', tenantParams.value)
        : '#',
      icon: UserPlusIcon,
    });
  }

  // Management features (Owners & Managers)
  if (user.value?.isOwner || user.value?.roles?.some((role: UserRole) => role.name === 'Manager')) {
    items.push(
      {
        title: 'Holidays',
        href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
          ? route('tenant.holidays.index', tenantParams.value)
          : '#',
        icon: CalendarIcon,
      },
      {
        title: 'Leave Types',
        href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
          ? route('tenant.leave-types.index', tenantParams.value)
          : '#',
        icon: ClipboardList,
      },
      {
        title: 'Departments',
        href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
          ? route('tenant.departments.index', tenantParams.value)
          : '#',
        icon: Building2,
      },
      {
        title: 'Reports',
        href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
          ? route('tenant.reports.index', tenantParams.value)
          : '#',
        icon: BarChart3,
      }
    );
  }

  return items;
});

// Workspace management items (only for owners)
const workspaceManagementItems = computed((): NavItem[] => [
  {
    title: 'Workspaces',
    href: route('workspaces.index'),
    icon: SettingsIcon,
  }
]);

// Check if user has admin access based on permissions
const hasAdminAccess = computed(() =>
  user.value?.permissions?.canApproveLeave || user.value?.permissions?.canViewAllUsers || user.value?.isOwner
);

// Check if user is owner (can manage workspaces)
const isWorkspaceOwner = computed(() => user.value?.isOwner);

// Combine navigation items based on user permissions
const combinedNavItems = computed((): NavItem[] => {
  let items = [...mainNavItems.value];

  // Add leave requests for eligible users
  if (canRequestLeave.value) {
    items.push({
      title: 'My Leave Requests',
      href: tenantParams.value.tenant_slug && tenantParams.value.tenant_uuid
        ? route('tenant.leave-requests.index', tenantParams.value)
        : '#',
      icon: CalendarIcon
    });
  }

  if (hasAdminAccess.value) {
    items = [...items, ...adminNavItems.value];
  }

  if (isWorkspaceOwner.value) {
    items = [...items, ...workspaceManagementItems.value];
  }

  return items;
});
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="tenantParams.tenant_slug && tenantParams.tenant_uuid
              ? route('tenant.dashboard', tenantParams)
              : '#'">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="combinedNavItems" />
    </SidebarContent>

    <SidebarFooter>
      <!-- Workspace Info -->
      <div class="px-2 py-2 text-xs text-muted-foreground border-t">
        <div class="font-medium text-foreground">{{ workspace?.name }}</div>
        <div class="truncate">{{ workspace?.slug }}</div>
      </div>

      <NavUser />
    </SidebarFooter>
  </Sidebar>
</template>
