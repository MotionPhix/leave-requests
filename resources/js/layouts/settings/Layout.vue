<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ContactIcon, LockIcon, PaletteIcon, HourglassIcon } from 'lucide-vue-next';

const page = usePage();

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';

const user = page.props.auth.user;

const sidebarNavItems: NavItem[] = [
  {
    title: 'Profile',
    href: user.isEmployee ? '/settings/profile' : '/admin/settings/profile',
    icon: ContactIcon
  },
  {
    title: 'Password',
    href: user.isEmployee ? '/settings/password' : '/admin/settings/password',
    icon: LockIcon
  },
  {
    title: 'Appearance',
    href: user.isEmployee ? '/settings/appearance' : '/admin/settings/appearance',
    icon: PaletteIcon
  }
];

const adminSidebarNavItems: NavItem[] = [

  {
    title: 'ID Generator',
    href: '/admin/settings/employee-id',
    icon: HourglassIcon
  }
];

const combinedSidebarNavItems = user?.isEmployee
  ? sidebarNavItems
  : [...sidebarNavItems, ...adminSidebarNavItems];
</script>

<template>
  <div class="px-4 py-6">
    <Heading title="Settings" description="Manage your profile and account settings" />

    <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
      <aside class="w-full max-w-xl lg:w-48">
        <nav class="flex flex-col space-x-0 space-y-1">
          <Button
            v-for="item in combinedSidebarNavItems"
            :key="item.href"
            variant="ghost"
            :class="['w-full justify-start', { 'bg-muted': currentPath === item.href }]"
            as-child>
            <Link :href="item.href">
              <component :is="item.icon" />
              {{ item.title }}
            </Link>
          </Button>
        </nav>
      </aside>

      <Separator class="my-6 md:hidden" />

      <div class="flex-1 md:max-w-2xl pt-10 md:mt-0">
        <section class="max-w-xl space-y-12">
          <slot />
        </section>
      </div>
    </div>
  </div>
</template>
