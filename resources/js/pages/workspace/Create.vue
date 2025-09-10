<template>
  <WorkspaceSelectionLayout>
    <Head title="Create Workspace" />

    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
          Create Your
          <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
            Workspace
          </span>
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-300">
          Set up a new workspace to start managing leave requests.
        </p>
      </div>

      <!-- Form Card -->
      <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-200 dark:border-gray-700 p-12">
        <form @submit.prevent="submit" class="space-y-8">
          <!-- Workspace Name Input -->
          <div>
            <label for="name" class="block text-lg font-semibold text-gray-900 dark:text-white mb-3">
              Workspace Name
            </label>
            <p class="text-gray-600 dark:text-gray-300 mb-4">
              Choose a descriptive name for your workspace. This could be your company name, department, or team name.
            </p>
            <div class="relative">
              <input
                id="name"
                v-model="form.name"
                name="name"
                type="text"
                required
                class="block w-full px-4 py-4 text-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                placeholder="e.g., Acme Corp, Marketing Team, Engineering Department"
              />
              <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                <Building2 class="w-5 h-5 text-gray-400 dark:text-gray-500" />
              </div>
            </div>
            <div v-if="form.errors.name" class="mt-3 text-sm text-red-600 dark:text-red-400 flex items-center space-x-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span>{{ form.errors.name }}</span>
            </div>
          </div>

          <!-- Features Preview -->
          <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">What you'll get with your workspace:</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                  <Users class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                </div>
                <span class="text-gray-700 dark:text-gray-300">Team member management</span>
              </div>
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                  <ClipboardList class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                </div>
                <span class="text-gray-700 dark:text-gray-300">Leave request tracking</span>
              </div>
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                  <Workflow class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                </div>
                <span class="text-gray-700 dark:text-gray-300">Approval workflows</span>
              </div>
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg flex items-center justify-center">
                  <Calendar class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                </div>
                <span class="text-gray-700 dark:text-gray-300">Holiday management</span>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row gap-4">
            <button
              type="submit"
              :disabled="form.processing"
              class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-4 px-8 rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 flex items-center justify-center space-x-3"
            >
              <span v-if="form.processing" class="flex items-center space-x-2">
                <Loader2 class="animate-spin w-5 h-5" />
                <span>Creating your workspace...</span>
              </span>
              <span v-else class="flex items-center space-x-2">
                <Plus class="w-5 h-5" />
                <span>Create Workspace</span>
              </span>
            </button>

            <Link
              :href="route('workspaces.index')"
              class="flex-none bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 py-4 px-8 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-gray-400 transition-all duration-300 flex items-center justify-center space-x-2"
            >
              <ArrowLeft class="w-5 h-5" />
              <span>Back to Workspaces</span>
            </Link>
          </div>
        </form>
      </div>
    </div>
  </WorkspaceSelectionLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import WorkspaceSelectionLayout from '@/layouts/WorkspaceSelectionLayout.vue'
import { 
  Plus, 
  Building2, 
  Users, 
  ClipboardList, 
  Workflow, 
  Calendar, 
  AlertCircle, 
  Loader2, 
  ArrowLeft 
} from 'lucide-vue-next'

const form = useForm({
  name: ''
})

const submit = () => {
  form.post(route('workspaces.store'))
}
</script>
