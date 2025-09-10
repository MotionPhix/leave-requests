<script setup lang="ts">
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

// System Admin navigation items (for super admin users)
const systemAdminNavItems: NavItem[] = [
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
  {
    title: 'Users',
    href: route('api.admin.users'),
    icon: UsersIcon,
  },

  // System Management
  {
    title: 'System Settings',
    href: route('workspaces.create'),
    icon: SettingsIcon,
  },
  {
    title: 'Statistics',
    href: route('api.admin.stats'),
    icon: Shield,
  },

  // Analytics & Reporting
  {
    title: 'Calendar Admin',
    href: route('api.admin.calendar'),
    icon: BarChart3,
  },

  // Leave Management
  {
    title: 'Leave Types',
    href: route('api.admin.leave-types'),
    icon: CreditCard,
  },

  // Communication
  {
    title: 'Notifications',
    href: route('notifications.index'),
    icon: Bell,
  }
];

// Expose for use in other components
defineExpose({
  systemAdminNavItems
});
</script>

<template>
  <!-- This component exports navigation items and doesn't render anything -->
  <div></div>
</template>
