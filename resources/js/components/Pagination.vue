<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

defineProps<{
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
}>();
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <template v-for="(link, key) in links" :key="key">
      <div v-if="link.url === null"
           v-html="link.label"
           class="px-4 py-2 text-sm text-muted-foreground" />

      <Link v-else
            :href="link.url"
            preserve-scroll>
        <Button
          :variant="link.active ? 'default' : 'outline'"
          size="sm"
          v-html="link.label"
        />
      </Link>
    </template>
  </div>
</template>
