<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
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
import { 
  CalendarIcon, 
  LayoutGrid, 
  UsersIcon, 
  FileTextIcon,
  SettingsIcon,
  UserPlusIcon
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
const user = page.props.auth.user;
const workspace = page.props.workspace;

// Get current tenant parameters for route generation
const tenantParams = {
  tenant_slug: workspace?.slug,
  tenant_uuid: workspace?.uuid
};

// Main navigation items (for all users)
const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: tenantParams.tenant_slug && tenantParams.tenant_uuid 
      ? route('tenant.dashboard', tenantParams) 
      : '#',
    icon: LayoutGrid
  }
];

// Leave requests navigation - only for users who can actually request leave
// Owners cannot request leave (they own the company!)
/* @vue-ignore */
const canRequestLeave = user?.permissions?.canCreateLeaveRequests && !user?.isOwner;

if (canRequestLeave) {
  mainNavItems.push({
    title: 'My Leave Requests',
    href: tenantParams.tenant_slug && tenantParams.tenant_uuid 
      ? route('tenant.leave-requests.index', tenantParams) 
      : '#',
    icon: CalendarIcon
  });
}

// Admin navigation items (shown based on permissions)
const adminNavItems: NavItem[] = [];

/* @vue-ignore */
if (user?.permissions?.canViewAllUsers) {
  adminNavItems.push({
    title: 'Team Members',
    href: tenantParams.tenant_slug && tenantParams.tenant_uuid 
      ? route('tenant.members.index', tenantParams) 
      : '#',
    icon: UsersIcon,
  });
}

/* @vue-ignore */
if (user?.permissions?.canApproveLeave || user?.permissions?.canViewAllUsers) {
  adminNavItems.push({
    title: 'All Leave Requests',
    href: tenantParams.tenant_slug && tenantParams.tenant_uuid 
      ? route('tenant.admin.leave-requests.index', tenantParams) 
      : '#',
    icon: FileTextIcon,
  });
}

/* @vue-ignore */
if (user?.permissions?.canViewAllUsers) {
  adminNavItems.push({
    title: 'Invitations',
    href: tenantParams.tenant_slug && tenantParams.tenant_uuid 
      ? route('tenant.invitations.index', tenantParams) 
      : '#',
    icon: UserPlusIcon,
  });
}

// Workspace management items (only for owners)
const workspaceManagementItems: NavItem[] = [
  {
    title: 'Switch Workspace',
    href: route('workspaces.index'),
    icon: SettingsIcon,
  }
];

// Check if user has admin access based on permissions
/* @vue-ignore */
const hasAdminAccess = user?.permissions?.canApproveLeave || user?.permissions?.canViewAllUsers || user?.isOwner;

// Check if user is owner (can manage workspaces)
/* @vue-ignore */
const isWorkspaceOwner = user?.isOwner;

// Combine navigation items based on user permissions
let combinedNavItems = [...mainNavItems];

if (hasAdminAccess) {
  combinedNavItems = [...combinedNavItems, ...adminNavItems];
}

if (isWorkspaceOwner) {
  combinedNavItems = [...combinedNavItems, ...workspaceManagementItems];
}

const footerNavItems: NavItem[] = [
  {
    title: 'Switch Workspace',
    href: route('workspaces.index'),
    icon: SettingsIcon
  },
  {
    title: 'Workspace Settings',
    href: '#', // TODO: Add workspace settings route
    icon: SettingsIcon
  }
];
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
