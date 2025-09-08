<script setup lang="ts">
defineProps<{ workspace: { uuid: string; slug: string; name: string } | null }>()
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-semibold">Workspace: {{ workspace?.name }}</h1>
  </div>

</template>
