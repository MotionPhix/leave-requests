<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Modal } from '@inertiaui/modal-vue'
import { computed, ref } from 'vue'
import { Mail } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { toast } from 'vue-sonner'

// Define props for the modal
const props = defineProps<{
  workspace: {
    slug: string
    uuid: string
  }
}>()

const inviteModal = ref()

// Tenant parameters for routes - use both tenant_slug and tenant_uuid as required by Ziggy
const tenantParams = computed(() => ({
  tenant_slug: props.workspace.slug,
  tenant_uuid: props.workspace.uuid
}))

// Invitation form using Inertia's useForm
const inviteForm = useForm({
  email: '',
  role: ''
})

// Available roles for invitation
const availableRoles = [
  { value: 'Employee', label: 'Employee' },
  { value: 'Manager', label: 'Manager' },
  { value: 'HR', label: 'HR' }
]

// Submit invitation
const submitInvitation = () => {
  inviteForm.post(route('tenant.invitations.store', tenantParams.value), {
    onSuccess: () => {
      inviteForm.reset()
      inviteModal.value?.close()
    },
    onError: () => {
      toast.error('Failed to send invitation. Please check the form for errors.')
    }
  })
}
</script>

<template>
  <Modal
    :close-explicitly="true"
    :close-button="false"
    v-slot="{ close }"
    ref="inviteModal">
    <Card>
      <CardHeader>
        <div class="flex items-center">
          <Mail class="w-5 h-5 mr-2" />
          <h2 class="text-lg font-semibold">Invite New Member</h2>
        </div>

        <p class="text-sm text-muted-foreground">
          Send an invitation to join your workspace. They'll receive an email with instructions to accept.
        </p>
      </CardHeader>

      <CardContent>
        <div class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">
              Email Address
            </label>
            <Input
              v-model="inviteForm.email"
              type="email"
              placeholder="member@company.com"
              class="w-full"
              :disabled="inviteForm.processing"
            />
            <p v-if="inviteForm.errors.email" class="text-sm text-red-600 dark:text-red-400">
              {{ inviteForm.errors.email }}
            </p>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">
              Role
            </label>
            <Select v-model="inviteForm.role" :disabled="inviteForm.processing">
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Select a role" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="role in availableRoles"
                  :key="role.value"
                  :value="role.value"
                >
                  {{ role.label }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="inviteForm.errors.role" class="text-sm text-red-600 dark:text-red-400">
              {{ inviteForm.errors.role }}
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-4 mt-6">
          <Button
            variant="outline"
            :disabled="inviteForm.processing"
            @click="close">
            Cancel
          </Button>

          <Button
            @click="submitInvitation"
            :disabled="inviteForm.processing || !inviteForm.email || !inviteForm.role"
            class="bg-neutral-900 dark:bg-neutral-100 text-neutral-100 dark:text-neutral-900 hover:bg-neutral-800 dark:hover:bg-neutral-200">
            <Mail class="w-4 h-4 mr-2" v-if="!inviteForm.processing" />
            {{ inviteForm.processing ? 'Sending...' : 'Send Invitation' }}
          </Button>
        </div>
      </CardContent>
    </Card>
  </Modal>
</template>
