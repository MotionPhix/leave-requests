<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

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
           class="px-4 py-2 text-sm text-muted-foreground">
        {{ link.label }}
      </div>

      <Link v-else
            :href="link.url"
            preserve-scroll>
        <Button
          :variant="link.active ? 'default' : 'outline'"
          size="sm">
          {{ link.label }}
        </Button>
      </Link>
    </template>
  </div>
</template>
