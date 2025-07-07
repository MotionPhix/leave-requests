<script setup lang="ts">
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
  items: NavItem[];
}>();

const page = usePage<SharedData>();

const activeMap = computed(() => {
  return props.items.reduce((map, item) => {
    const itemPath = new URL(item.href, window.location.origin).pathname;
    map[item.href] = page.url.startsWith(itemPath);
    return map;
  }, {} as Record<string, boolean>);
});
</script>

<template>
  <SidebarGroup class="px-2 py-0">
    <SidebarGroupLabel>Platform</SidebarGroupLabel>
    <SidebarMenu>
      <SidebarMenuItem v-for="item in items" :key="item.title">
        <SidebarMenuButton
          as-child :is-active="activeMap[item.href]"
          :tooltip="item.title">
          <Link :href="item.href">
            <component :is="item.icon" />
            <span>{{ item.title }}</span>
          </Link>
        </SidebarMenuButton>
      </SidebarMenuItem>
    </SidebarMenu>
  </SidebarGroup>
</template>
