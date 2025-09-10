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
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';
import { type NavItem } from '@/types';
import {
  LayoutGrid,
  Building2,
  UsersIcon,
  SettingsIcon,
  BarChart3,
  Shield,
  CreditCard,
  Bell,
  CalendarDaysIcon,
  Clock
} from 'lucide-vue-next';

interface User {
  id: string | number;
  name: string;
  email: string;
  is_system_admin?: boolean;
}

interface PageProps extends Record<string, any> {
  auth: {
    user: User;
  };
}

const page = usePage<PageProps>();
const user = page.props.auth.user;

// System Admin navigation items (for super admin users)
const navItems: NavItem[] = [
  // Core Admin Features
  {
    title: 'Dashboard',
    href: route('home'),
    icon: LayoutGrid
  },

  // Workspace Management
  {
    title: 'Workspaces',
    href: route('workspaces.index'),
    icon: Building2,
  },

  // Communication
  {
    title: 'Notifications',
    href: route('notifications.index'),
    icon: Bell,
  }
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <Link :href="route('home')">
              <AppLogo />
            </Link>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="navItems" />
    </SidebarContent>

    <SidebarFooter>
      <!-- System Admin Info -->
      <div class="px-2 py-2 text-xs text-muted-foreground border-t">
        <div class="font-medium text-foreground">System Administration</div>
        <div class="text-xs text-amber-600 dark:text-amber-400">Super Admin Access</div>
      </div>

      <NavUser />
    </SidebarFooter>
  </Sidebar>
</template>
