<script setup lang="ts">
import { type Component } from 'vue';

interface Props {
  icon?: Component
  title: string
  description: string
  trend?: {
    value: number
    type: 'increase' | 'decrease'
  }
}

const props = withDefaults(defineProps<Props>(), {
  icon: undefined,
  trend: undefined
});

const getTrendColor = (type: 'increase' | 'decrease') => {
  return type === 'increase' ? 'text-success' : 'text-destructive';
};
</script>

<template>
  <div class="relative h-[100px] p-6 bg-background hover:bg-muted/50 border rounded-lg transition-colors group">
    <!-- Background Pattern -->
    <div
      class="absolute inset-0 opacity-5 pointer-events-none overflow-hidden rounded-lg"
      aria-hidden="true"
    >
      <div class="absolute inset-0 backdrop-blur-xl"></div>
      <div class="absolute bottom-0 left-0 right-0 h-32 bg-gradient-to-t from-muted/10"></div>
    </div>

    <!-- Content -->
    <div class="relative">
      <!-- Header -->
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-xs font-medium text-muted-foreground flex items-center gap-2">
          <component
            :is="icon"
            v-if="icon"
            class="w-4 h-4 transition-transform group-hover:scale-110"
          />
          {{ title }}
        </h3>

        <!-- Trend Indicator -->
        <div v-if="trend"
             class="flex items-center text-xs font-medium"
             :class="getTrendColor(trend.type)">
          <svg
            :class="trend.type === 'increase' ? 'rotate-0' : 'rotate-180'"
            class="w-3 h-3 mr-1"
            viewBox="0 0 24 24"
          >
            <path
              fill="currentColor"
              d="M16.21 16H7.79a1.76 1.76 0 0 1-1.59-1 2.1 2.1 0 0 1 .26-2.21l4.21-5.1a1.76 1.76 0 0 1 2.66 0l4.21 5.1A2.1 2.1 0 0 1 17.8 15a1.76 1.76 0 0 1-1.59 1Z"
            />
          </svg>
          {{ trend.value }}%
        </div>
      </div>

      <!-- Description -->
      <div class="flex items-baseline gap-2">
        <p
          class="font-semibold capitalize text-xl truncate"
          :title="description"
        >
          {{ description }}
        </p>
      </div>
    </div>
  </div>
</template>
