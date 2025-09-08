<script setup lang="ts">
import { useForm, usePage, router } from '@inertiajs/vue3'

type Member = { uuid: string; name: string; email: string; role: string | null }

const props = defineProps<{
  members: Member[]
  roles: string[]
  invitations?: Array<{ id: number; email: string; role: string; expires_at: string | null }>
}>()

const page = usePage()
const workspace = page.props.workspace as { uuid: string; slug: string; name: string }

const inviteForm = useForm({
  email: '',
  role: 'Employee' as string,
})

function invite() {
  inviteForm.post(
    route('tenant.invitations.store', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        inviteForm.reset('email')
      },
    },
  )
}

function updateRole(userUuid: string, role: string) {
  router.put(
    route('tenant.members.update', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
      userUuid,
    }),
    { role },
    { preserveScroll: true },
  )
}

function removeMember(userUuid: string) {
  if (!confirm('Remove this member from the workspace?')) return
  router.delete(
    route('tenant.members.destroy', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
      userUuid,
    }),
    { preserveScroll: true },
  )
}
</script>

<template>
  <div class="p-6 space-y-8">
    <div>
      <h1 class="text-2xl font-semibold">Members · {{ workspace.name }}</h1>
      <p class="text-gray-500">Manage workspace members and roles.</p>
    </div>

    <div class="rounded border p-4 space-y-4">
      <h2 class="font-medium">Invite member</h2>
      <form class="flex flex-wrap gap-2 items-center" @submit.prevent="invite">
        <input
          class="border rounded px-3 py-2 min-w-64"
          type="email"
          placeholder="email@example.com"
          v-model="inviteForm.email"
        />
        <select class="border rounded px-3 py-2" v-model="inviteForm.role">
          <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
        </select>
        <button
          type="submit"
          class="bg-blue-600 text-white rounded px-4 py-2"
          :disabled="inviteForm.processing"
        >
          Invite
        </button>
        <div class="text-red-600 text-sm" v-if="inviteForm.errors.email">{{ inviteForm.errors.email }}</div>
      </form>
    </div>

  <div class="rounded border">
      <table class="w-full text-left">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2">Name</th>
            <th class="px-4 py-2">Email</th>
            <th class="px-4 py-2">Role</th>
            <th class="px-4 py-2 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in members" :key="m.uuid" class="border-t">
            <td class="px-4 py-2">{{ m.name }}</td>
            <td class="px-4 py-2">{{ m.email }}</td>
            <td class="px-4 py-2">
              <select
                class="border rounded px-2 py-1"
                :value="m.role ?? 'Employee'"
                @change="(e:any) => updateRole(m.uuid, e.target.value)"
              >
                <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
              </select>
            </td>
            <td class="px-4 py-2 text-right">
              <button class="text-red-600" @click="removeMember(m.uuid)">Remove</button>
            </td>
          </tr>
          <tr v-if="members.length === 0">
            <td class="px-4 py-6 text-gray-500" colspan="4">No members yet.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="rounded border p-4 space-y-2">
      <h2 class="font-medium">Pending invitations</h2>
      <div v-if="props.invitations && props.invitations.length" class="divide-y">
        <div v-for="inv in props.invitations" :key="inv.id" class="flex items-center justify-between py-2 px-1">
          <div>
            <div class="font-medium">{{ inv.email }}</div>
            <div class="text-xs text-gray-500">Role: {{ inv.role }}</div>
          </div>
          <div class="text-xs text-gray-500">Expires: {{ inv.expires_at ?? '—' }}</div>
        </div>
      </div>
      <div v-else class="text-sm text-gray-500">No pending invitations.</div>
    </div>
  </div>
</template>
