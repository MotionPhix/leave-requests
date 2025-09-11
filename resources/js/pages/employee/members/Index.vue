<template>
  <TenantLayout>
    <Head title="Team Members" />

    <div class="space-y-6">
      <!-- Header Section -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Team Members</h1>
          <p class="text-muted-foreground">
            View your workspace team members and their roles
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Badge variant="outline" class="gap-1">
            <Users class="h-3 w-3" />
            {{ members.length }} Members
          </Badge>
        </div>
      </div>

      <!-- Members Table -->
      <Card>
        <CardHeader>
          <CardTitle>Team Members</CardTitle>
          <CardDescription>
            View all workspace members and their roles.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Role</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="member in members" :key="member.uuid">
                <TableCell class="font-medium">
                  {{ member.name }}
                  <span v-if="member.uuid === currentUser.uuid" class="text-xs text-muted-foreground ml-2">
                    (You)
                  </span>
                </TableCell>
                <TableCell class="text-muted-foreground">{{ member.email }}</TableCell>
                <TableCell>
                  <div class="flex items-center gap-2">
                    <component :is="getRoleIcon(member.role || '')" class="h-4 w-4" />
                    <Badge :variant="getRoleBadgeVariant(member.role || '')">
                      {{ member.role || 'Employee' }}
                    </Badge>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="members.length === 0">
                <TableCell colspan="3" class="text-center py-8 text-muted-foreground">
                  No team members found.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Users, Crown, Shield, UserCheck } from 'lucide-vue-next';

type Member = { 
  uuid: string; 
  name: string; 
  email: string; 
  role: string | null;
}

type CurrentUser = {
  uuid: string;
  role: string | null;
}

const props = defineProps<{
  members: Member[];
  currentUser: CurrentUser;
}>();

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };

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
</script>