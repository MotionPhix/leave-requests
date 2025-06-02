<script setup lang="ts">
import { type Component } from 'vue';

defineProps<{
  icon?: Component
  title: string
  description: string
}>()
</script>

<template>
  <div class="p-4 border border-gray-200 rounded-lg dark:border-neutral-700">
    <h3 class="mb-2 gap-x-2 text-xs text-gray-600 dark:text-neutral-400 flex items-center">
      <component :is="icon" class="w-4 h-4" v-if="icon" />
      <span>{{ title }}</span>
    </h3>

    <p class="font-semibold capitalize text-sm text-gray-800 dark:text-neutral-200">
      {{ description }}
    </p>
  </div>
</template>
