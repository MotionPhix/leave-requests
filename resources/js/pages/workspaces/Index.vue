<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3'

defineProps<{
  workspaces: Array<{ uuid: string; slug: string; name: string }>
}>()

const form = useForm({
  name: '',
})

function createWorkspace() {
  form.post('/workspaces')
}
</script>

<template>
  <div class="p-6 space-y-6">
    <h1 class="text-2xl font-semibold">Your Workspaces</h1>
    <ul class="space-y-2">
      <li v-for="w in workspaces" :key="w.uuid" class="flex items-center justify-between">
        <span>{{ w.name }}</span>
        <Link class="text-blue-600" :href="route('workspaces.open', { slug: w.slug, uuid: w.uuid })">Open</Link>
      </li>
    </ul>

    <form @submit.prevent="createWorkspace" class="flex gap-2">
      <input v-model="form.name" placeholder="New workspace name" class="border px-3 py-2 rounded w-64" />
      <button :disabled="form.processing" class="bg-blue-600 text-white px-4 py-2 rounded">Create</button>
    </form>
  </div>
</template>
