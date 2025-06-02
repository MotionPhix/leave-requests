<script setup lang="ts">
import { SidebarInset } from '@/components/ui/sidebar';
import { computed } from 'vue';
import NotificationBell from '@/components/NotificationBell.vue';

interface Props {
  variant?: 'header' | 'sidebar';
  class?: string;
}

const props = defineProps<Props>();
const className = computed(() => props.class);
</script>

<template>

  <div class="fixed top-1 right-5 z-50 flex items-center gap-2 p-4">
    <NotificationBell />
  </div>

  <SidebarInset v-if="props.variant === 'sidebar'"
                :class="className">
    <slot />
  </SidebarInset>
  <main v-else
        class="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl"
        :class="className">

    <slot />
  </main>
</template>
