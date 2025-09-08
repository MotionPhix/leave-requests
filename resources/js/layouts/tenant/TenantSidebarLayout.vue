<script setup lang="ts">
import AppShell from '@/components/AppShell.vue';
import TenantSidebar from '@/components/TenantSidebar.vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import { TooltipProvider } from '@/components/ui/tooltip';
import type { BreadcrumbItemType } from '@/types';

interface Props {
  breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => []
});
</script>

<template>
  <TooltipProvider :delay-duration="0">
    <SidebarProvider :default-open="true">
      <AppShell variant="sidebar">
        <TenantSidebar />
        <main class="flex-1 overflow-auto p-6">
          <slot />
        </main>
      </AppShell>
    </SidebarProvider>
  </TooltipProvider>
</template>
