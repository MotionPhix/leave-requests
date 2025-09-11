<script setup lang="ts">
import { useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { UserPlus, Mail, Users, Crown, Shield, UserCheck, Trash2, X } from 'lucide-vue-next';

type Member = { 
  uuid: string; 
  name: string; 
  email: string; 
  role: string | null;
}

type Invitation = { 
  id: number; 
  email: string; 
  role: string; 
  expires_at: string | null;
}

type CurrentUser = {
  uuid: string;
  role: string | null;
}

type AssignableRole = {
  id: number;
  name: string;
  label: string;
}

const props = defineProps<{
  members: Member[];
  roles: string[];
  assignableRoles: AssignableRole[];
  invitations?: Invitation[];
  currentUser: CurrentUser;
  canManageRoles: boolean;
}>();

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };

const inviteForm = useForm({
  email: '',
  role: 'Employee' as string,
});

const showRemoveDialog = ref(false);
const memberToRemove = ref<Member | null>(null);
const showCancelInviteDialog = ref(false);
const invitationToCancel = ref<Invitation | null>(null);
const isUpdatingRole = ref<string | null>(null);
const isCancellingInvite = ref<number | null>(null);
const isRemovingMember = ref(false);

const inviteUser = () => {
  inviteForm.post(
    route('tenant.management.invitations.store', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
    }),
    {
      preserveScroll: true,
      onSuccess: () => {
        inviteForm.reset('email');
      },
    },
  );
};

const updateRole = (userUuid: string, role: string) => {
  isUpdatingRole.value = userUuid;
  router.put(
    route('tenant.management.members.update', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
      userUuid,
    }),
    { role },
    { 
      preserveScroll: true,
      onFinish: () => {
        isUpdatingRole.value = null;
      }
    },
  );
};

const confirmRemove = (member: Member) => {
  memberToRemove.value = member;
  showRemoveDialog.value = true;
};

const removeMember = () => {
  if (!memberToRemove.value) return;
  
  isRemovingMember.value = true;
  router.delete(
    route('tenant.management.members.destroy', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
      userUuid: memberToRemove.value.uuid,
    }),
    { 
      preserveScroll: true,
      onSuccess: () => {
        showRemoveDialog.value = false;
        memberToRemove.value = null;
      },
      onFinish: () => {
        isRemovingMember.value = false;
      }
    },
  );
};

const confirmCancelInvite = (invitation: Invitation) => {
  invitationToCancel.value = invitation;
  showCancelInviteDialog.value = true;
};

const cancelInvitation = () => {
  if (!invitationToCancel.value) return;
  
  isCancellingInvite.value = invitationToCancel.value.id;
  router.delete(
    route('tenant.management.invitations.destroy', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
      invitationId: invitationToCancel.value.id,
    }),
    { 
      preserveScroll: true,
      onSuccess: () => {
        showCancelInviteDialog.value = false;
        invitationToCancel.value = null;
      },
      onFinish: () => {
        isCancellingInvite.value = null;
      }
    },
  );
};

const canManageMembers = computed(() => {
  const role = props.currentUser.role?.toLowerCase();
  return role === 'owner' || role === 'admin' || role === 'hr' || role === 'manager';
});

const canChangeRoles = computed(() => {
  return props.canManageRoles;
});

const getAvailableRoles = () => {
  return props.assignableRoles;
};

const getRoleIcon = (role: string) => {
  switch (role?.toLowerCase()) {
    case 'owner':
      return Crown;
    case 'admin':
      return Shield;
    case 'manager':
    case 'hr':
      return UserCheck;
    default:
      return Users;
  }
};

const getRoleBadgeVariant = (role: string) => {
  switch (role?.toLowerCase()) {
    case 'owner':
      return 'default';
    case 'admin':
      return 'destructive';
    case 'manager':
    case 'hr':
      return 'secondary';
    default:
      return 'outline';
  }
};

const memberStats = computed(() => ({
  total: props.members.length,
  pendingInvitations: props.invitations?.length || 0,
  owners: props.members.filter(m => m.role?.toLowerCase() === 'owner').length,
  admins: props.members.filter(m => m.role?.toLowerCase() === 'admin').length,
}));
</script>

<template>
  <TenantLayout>
    <div class="space-y-6">
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Members</h1>
          <p class="text-muted-foreground">
            Manage workspace members, roles, and invitations for {{ workspace.name }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Badge variant="outline" class="gap-1">
            <Users class="h-3 w-3" />
            {{ memberStats.total }} Members
          </Badge>
          <Badge v-if="memberStats.pendingInvitations > 0" variant="secondary" class="gap-1">
            <Mail class="h-3 w-3" />
            {{ memberStats.pendingInvitations }} Pending
          </Badge>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Members</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ memberStats.total }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Owners</CardTitle>
            <Crown class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ memberStats.owners }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Admins</CardTitle>
            <Shield class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ memberStats.admins }}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending Invitations</CardTitle>
            <Mail class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ memberStats.pendingInvitations }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Invite Member Section -->
      <Card v-if="canManageMembers">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <UserPlus class="h-5 w-5" />
            Invite New Member
          </CardTitle>
          <CardDescription>
            Add new members to your workspace by sending them an email invitation.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="inviteUser" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
              <Label for="email">Email Address</Label>
              <Input
                id="email"
                type="email"
                placeholder="colleague@company.com"
                v-model="inviteForm.email"
                :disabled="inviteForm.processing"
                class="mt-1"
                required
              />
              <p v-if="inviteForm.errors.email" class="text-sm text-destructive mt-1">
                {{ inviteForm.errors.email }}
              </p>
            </div>
            <div class="w-full sm:w-48">
              <Label>Role</Label>
              <Select v-model="inviteForm.role" :disabled="inviteForm.processing" required>
                <SelectTrigger class="mt-1">
                  <SelectValue placeholder="Select role" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="role in assignableRoles" :key="role.name" :value="role.name">
                    {{ role.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="inviteForm.errors.role" class="text-sm text-destructive mt-1">
                {{ inviteForm.errors.role }}
              </p>
            </div>
            <div class="flex items-end">
              <Button type="submit" :disabled="inviteForm.processing || !inviteForm.email || !inviteForm.role" class="w-full sm:w-auto">
                <UserPlus class="mr-2 h-4 w-4" />
                {{ inviteForm.processing ? 'Sending...' : 'Send Invitation' }}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Members Table -->
      <Card>
        <CardHeader>
          <CardTitle>Current Members</CardTitle>
          <CardDescription>
            Manage existing workspace members and their roles.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Role</TableHead>
                <TableHead class="text-right">Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="member in members" :key="member.uuid">
                <TableCell class="font-medium">{{ member.name }}</TableCell>
                <TableCell class="text-muted-foreground">{{ member.email }}</TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <component :is="getRoleIcon(member.role || '')" class="h-4 w-4" />
                    <template v-if="canChangeRoles && member.uuid !== currentUser.uuid">
                      <Select 
                        :model-value="member.role || 'Employee'" 
                        @update:model-value="(value) => updateRole(member.uuid, value as string)"
                        :disabled="isUpdatingRole === member.uuid"
                      >
                        <SelectTrigger class="w-32">
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem v-for="role in getAvailableRoles()" :key="role.name" :value="role.name">
                            {{ role.label }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                      <div v-if="isUpdatingRole === member.uuid" class="text-xs text-muted-foreground">
                        Updating...
                      </div>
                    </template>
                    <template v-else>
                      <Badge :variant="getRoleBadgeVariant(member.role || '')">
                        {{ member.role || 'Employee' }}
                      </Badge>
                    </template>
                  </div>
                </TableCell>
                <TableCell class="text-right">
                  <Button 
                    v-if="canManageMembers && member.uuid !== currentUser.uuid"
                    variant="ghost" 
                    size="sm" 
                    @click="confirmRemove(member)"
                    class="text-destructive hover:text-destructive"
                    :title="'Remove ' + member.name + ' from workspace'"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                  <span v-else-if="member.uuid === currentUser.uuid" class="text-xs text-muted-foreground">
                    (You)
                  </span>
                </TableCell>
              </TableRow>
              <TableRow v-if="members.length === 0">
                <TableCell colspan="4" class="text-center py-8 text-muted-foreground">
                  No members found. Start by inviting your first team member!
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      <!-- Pending Invitations -->
      <Card v-if="canManageMembers && invitations && invitations.length > 0">
        <CardHeader>
          <CardTitle>Pending Invitations</CardTitle>
          <CardDescription>
            Invitations that have been sent but not yet accepted.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div 
              v-for="invitation in invitations" 
              :key="invitation.id" 
              class="flex items-center justify-between p-3 border rounded-lg"
            >
              <div class="flex items-center gap-3">
                <Mail class="h-4 w-4 text-muted-foreground" />
                <div>
                  <p class="font-medium">{{ invitation.email }}</p>
                  <p class="text-sm text-muted-foreground">Role: {{ invitation.role }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="text-right">
                  <Badge variant="secondary">Pending</Badge>
                  <p v-if="invitation.expires_at" class="text-xs text-muted-foreground mt-1">
                    Expires: {{ new Date(invitation.expires_at).toLocaleDateString() }}
                  </p>
                </div>
                <Button
                  v-if="canManageMembers"
                  variant="ghost"
                  size="sm"
                  @click="confirmCancelInvite(invitation)"
                  :disabled="isCancellingInvite === invitation.id"
                  class="text-destructive hover:text-destructive"
                >
                  <X class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Remove Member Dialog -->
    <AlertDialog :open="showRemoveDialog" @update:open="showRemoveDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Remove Member</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to remove <strong>{{ memberToRemove?.name }}</strong> from this workspace? 
            This action cannot be undone and they will lose access to all workspace resources.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="showRemoveDialog = false" :disabled="isRemovingMember">Cancel</AlertDialogCancel>
          <AlertDialogAction @click="removeMember" :disabled="isRemovingMember" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            {{ isRemovingMember ? 'Removing...' : 'Remove Member' }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <!-- Cancel Invitation Dialog -->
    <AlertDialog :open="showCancelInviteDialog" @update:open="showCancelInviteDialog = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Cancel Invitation</AlertDialogTitle>
          <AlertDialogDescription>
            Are you sure you want to cancel the invitation for <strong>{{ invitationToCancel?.email }}</strong>? 
            This will prevent them from joining the workspace using this invitation link.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel @click="showCancelInviteDialog = false">Cancel</AlertDialogCancel>
          <AlertDialogAction @click="cancelInvitation" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
            Cancel Invitation
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </TenantLayout>
</template>
