<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { UserPlus, Mail, Building, Shield } from 'lucide-vue-next'

// Define props
const props = defineProps<{
  invitation: {
    email: string
    workspace_name: string
    role: string
  }
  workspace: {
    id: number
    name: string
    slug: string
    uuid: string
  }
  token: string
  errors: Record<string, string>
}>()

// Registration form
const form = useForm({
  name: '',
  password: '',
  password_confirmation: '',
})

// Submit registration
const submit = () => {
  form.post(`/invitation/${props.workspace.id}/${props.token}/register`, {
    onSuccess: () => {
      // Will redirect to dashboard automatically
    },
    onError: () => {
      // Handle errors
    }
  })
}

// Get role display name
const getRoleDisplayName = (role: string) => {
  const roleMap: Record<string, string> = {
    'Employee': 'Employee',
    'Manager': 'Manager',
    'HR': 'HR Manager',
    'Admin': 'Administrator',
    'Super Admin': 'Super Administrator'
  }
  return roleMap[role] || role
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <Head title="Join Workspace" />

    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <UserPlus class="mx-auto h-12 w-12 text-primary" />
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
          Join Your Team
        </h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
          Create your account to get started
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Building class="h-5 w-5" />
            {{ invitation.workspace_name }}
          </CardTitle>
          <CardDescription>
            You've been invited to join this workspace as a {{ getRoleDisplayName(invitation.role) }}
          </CardDescription>
        </CardHeader>

        <CardContent class="space-y-6">
          <!-- Email (read-only) -->
          <div class="space-y-2">
            <Label for="email">Email Address</Label>
            <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-md">
              <Mail class="h-4 w-4 text-gray-400" />
              <span class="text-sm font-medium">{{ invitation.email }}</span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              This email was used for your invitation
            </p>
          </div>

          <!-- Role Display -->
          <div class="space-y-2">
            <Label>Role</Label>
            <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-800 rounded-md">
              <Shield class="h-4 w-4 text-gray-400" />
              <span class="text-sm font-medium">{{ getRoleDisplayName(invitation.role) }}</span>
            </div>
          </div>

          <!-- Name Input -->
          <div class="space-y-2">
            <Label for="name">Full Name</Label>
            <Input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Enter your full name"
              :disabled="form.processing"
              required
            />
            <p v-if="form.errors.name" class="text-sm text-red-600 dark:text-red-400">
              {{ form.errors.name }}
            </p>
          </div>

          <!-- Password Input -->
          <div class="space-y-2">
            <Label for="password">Password</Label>
            <Input
              id="password"
              v-model="form.password"
              type="password"
              placeholder="Create a strong password"
              :disabled="form.processing"
              required
            />
            <p v-if="form.errors.password" class="text-sm text-red-600 dark:text-red-400">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Password Confirmation -->
          <div class="space-y-2">
            <Label for="password_confirmation">Confirm Password</Label>
            <Input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              placeholder="Confirm your password"
              :disabled="form.processing"
              required
            />
            <p v-if="form.errors.password_confirmation" class="text-sm text-red-600 dark:text-red-400">
              {{ form.errors.password_confirmation }}
            </p>
          </div>

          <!-- Error Alert -->
          <Alert v-if="Object.keys(form.errors).length > 0 && !form.errors.name && !form.errors.password && !form.errors.password_confirmation" variant="destructive">
            <AlertDescription>
              <div v-for="(error, field) in form.errors" :key="field">
                {{ error }}
              </div>
            </AlertDescription>
          </Alert>

          <!-- Submit Button -->
          <Button
            @click="submit"
            :disabled="form.processing || !form.name || !form.password || !form.password_confirmation"
            class="w-full"
          >
            <UserPlus class="w-4 h-4 mr-2" v-if="!form.processing" />
            {{ form.processing ? 'Creating Account...' : 'Create Account & Join Workspace' }}
          </Button>
        </CardContent>
      </Card>

      <div class="text-center">
        <p class="text-sm text-gray-600 dark:text-gray-400">
          By creating an account, you agree to join the workspace and accept the invitation.
        </p>
      </div>
    </div>
  </div>
</template>
