<template>
  <WorkspaceSelectionLayout>
    <Head title="Select Workspace" />
    
    <!-- Hero Section -->
    <div class="text-center mb-12" v-if="workspaces.length > 0">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">
        Welcome to your 
        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
          workspace hub
        </span>
      </h1>

      <p class="text-xl text-gray-600 max-w-2xl mx-auto">
        Select a workspace to jump into your leave management dashboard, or create a new workspace for your team.
      </p>
    </div>

    <!-- Workspaces Grid -->
    <div v-if="workspaces.length > 0" class="mb-16">
      <div class="flex items-center justify-between mb-8">
        <h2 class="text-2xl font-semibold text-gray-900">Your Workspaces</h2>
        <div class="text-sm text-gray-500">
          {{ workspaces.length }} workspace{{ workspaces.length !== 1 ? 's' : '' }} available
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Workspace Cards -->
        <div 
          v-for="workspace in workspaces" 
          :key="workspace.uuid" 
          class="group relative bg-white rounded-2xl border border-gray-200 p-8 hover:shadow-xl hover:shadow-indigo-500/10 transition-all duration-300 cursor-pointer transform hover:-translate-y-1"
          @click="selectWorkspace(workspace)"
        >
          <!-- Workspace Icon -->
          <div class="flex items-center justify-between mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
              <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
            
            <!-- Status Badge -->
            <div class="flex items-center space-x-2">
              <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
              <span class="text-xs font-medium text-green-600">Active</span>
            </div>
          </div>

          <!-- Workspace Info -->
          <div class="mb-6">
            <h3 class="text-xl font-semibold text-gray-900 mb-2 group-hover:text-indigo-600 transition-colors">
              {{ workspace.name }}
            </h3>
            <div class="flex items-center space-x-4 text-sm text-gray-500">
              <div class="flex items-center space-x-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                </svg>
                <span>{{ workspace.member_count || 0 }} members</span>
              </div>
              <div v-if="workspace.is_owner" class="flex items-center space-x-1">
                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                </svg>
                <span class="text-amber-600 font-medium">Owner</span>
              </div>
            </div>
          </div>

          <!-- Action Button -->
          <button
            @click.stop="selectWorkspace(workspace)"
            class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 text-white py-3 px-4 rounded-xl font-medium hover:from-indigo-600 hover:to-purple-700 transition-all duration-300 group-hover:shadow-lg flex items-center justify-center space-x-2"
          >
            <span>Open Workspace</span>
            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
            </svg>
          </button>

          <!-- Hover Glow Effect -->
          <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/5 to-purple-600/5 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
        </div>

        <!-- Create New Workspace Section -->
        <Link 
          as="button"
          :href="route('workspaces.create')" 
          class="text-center cursor-pointer">
          <div class="bg-gradient-to-br from-gray-50 to-indigo-50 rounded-3xl p-12 border-2 border-dashed border-gray-300 hover:border-indigo-400 transition-colors duration-300">
            
            <h3 class="text-2xl font-semibold text-gray-900 mb-4">
              {{ workspaces.length > 0 ? 'Create Another Workspace' : 'Create Your First Workspace' }}
            </h3>
            <!-- <p class="text-gray-600 mb-8 max-w-md mx-auto">
              {{ workspaces.length > 0 
                ? 'Need to manage leave requests for another team or department? Set up a new workspace in minutes.'
                : 'Get started by setting up your first workspace. It only takes a minute to get your team organized.'
              }}
            </p> -->
            
            <div
              class="inline-flex items-center gap-x-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-4 px-8 rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 transform hover:scale-105 hover:shadow-lg"
            >
              <Plus class="w-5 h-5" />
              <span>{{ workspaces.length > 0 ? 'Create New Workspace' : 'Create Your First Workspace' }}</span>
            </div>
          </div>
        </Link>

      </div>
    </div>

    <!-- Empty State (when no workspaces) -->
    <div v-if="workspaces.length === 0" class="text-center py-16">
      <div class="mb-8">
        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-gray-100 to-indigo-100 rounded-full mb-6">
          <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
          </svg>
        </div>
      </div>

      <h3 class="text-2xl font-semibold text-gray-900 mb-4">
        Ready to get organized?
      </h3>

      <p class="text-gray-600 mb-8 max-w-md mx-auto">
        You're not part of any workspaces yet. Create your first workspace to start managing leave requests with your team.
      </p>

      <div>
        <Link 
          :as="Button"
          :href="route('workspaces.create')" 
          class="h-12 cursor-pointer">
          Create Your First Workspace
        </Link>
      </div>
    </div>
  </WorkspaceSelectionLayout>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import WorkspaceSelectionLayout from '@/layouts/WorkspaceSelectionLayout.vue'
import { Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

interface Workspace {
  uuid: string
  slug: string
  name: string
  member_count?: number
  is_owner?: boolean
}

const props = defineProps<{
  workspaces: Workspace[]
}>()

const selectWorkspace = (workspace: Workspace) => {
  window.location.href = route('workspaces.switch', {
    slug: workspace.slug,
    uuid: workspace.uuid
  })
}
</script>
