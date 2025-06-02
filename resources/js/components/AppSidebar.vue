<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
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
import { CalendarIcon, CalendarDaysIcon, LayoutGrid, UsersIcon, ShieldIcon, DoorOpenIcon } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const user = usePage().props.auth.user

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: user?.isEmployee ? '/dashboard' : '/admin/dashboard',
    icon: LayoutGrid
  },
  {
    title: 'Leave Requests',
    href: user?.isEmployee ? '/leave-requests' : '/admin/leave-requests',
    icon: CalendarIcon
  }
];

// Admin-only navigation items
const adminNavItems: NavItem[] = [
  {
    title: 'Employees',
    href: '/admin/employees',
    icon: UsersIcon,
  },
  {
    title: 'Roles & Permissions',
    href: '/admin/roles',
    icon: ShieldIcon,
  },
  {
    title: 'Leave Types',
    href: '/admin/leave-types',
    icon: DoorOpenIcon,
  },
  {
    title: 'Holidays',
    href: route('admin.holidays.index'),
    icon: CalendarDaysIcon,
  }
];

// Filter admin items based on permissions
// const filteredAdminItems = adminNavItems.filter(item => item.show);

// Combine navigation items based on user role
const combinedNavItems = user?.isEmployee
  ? mainNavItems
  : [...mainNavItems, ...adminNavItems];

const footerNavItems: NavItem[] = [
  {
    title: 'Calendar',
    href: user?.isEmployee ? '/calendar' : '/admin/calendar',
    icon: CalendarIcon
  }
];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton
            size="lg" as-child>
            <Link
              :href="user?.isEmployee
              ? route('dashboard')
              : route('admin.dashboard')">
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
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>
