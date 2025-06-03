<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ModalLink } from '@inertiaui/modal-vue'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2, Pencil } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import {
  AlertDialog,
  AlertDialogTrigger,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Separator } from '@/components/ui/separator';

defineProps<{
  roles: Array<{
    id: number;
    name: string;
    permissions: Array<{ id: number; name: string }>;
  }>;
  permissions: Array<{
    id: number;
    name: string;
  }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Roles & Permissions',
    href: route('admin.roles.index')
  },
];

function handleDelete(role: { id: number; name: string }) {
  router.delete(route('admin.roles.destroy', role.id));
}
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Roles & Permissions" />

    <div class="p-6 max-w-5xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Roles & Permissions</h1>
        <Button
          :as="ModalLink"
          :href="route('admin.roles.create')">
          New
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Manage Roles</CardTitle>
        </CardHeader>

        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Role Name</TableHead>
                <TableHead>Permissions</TableHead>
                <TableHead class="w-[100px]"></TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow v-for="role in roles" :key="role.id">
                <TableCell class="font-medium align-top">{{ role.name }}</TableCell>
                <TableCell class="align-top">
                  <div class="flex flex-wrap gap-1">
                    <span
                      v-for="permission in role.permissions"
                      :key="permission.id"
                      class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary">
                      {{ permission.name }}
                    </span>
                  </div>
                </TableCell>

                <TableCell>
                  <div class="flex items-center gap-2">
                    <Button
                      :as="ModalLink"
                      :href="route('admin.roles.edit', role.id)"
                      v-if="role.name !== 'Admin'"
                      variant="ghost"
                      size="icon">
                      <Pencil class="h-4 w-4" />
                    </Button>

                    <AlertDialog>
                      <AlertDialogTrigger as-child>
                        <Button
                          v-if="role.name !== 'Admin'"
                          variant="ghost"
                          size="icon">
                          <Trash2 class="h-4 w-4" />
                        </Button>
                      </AlertDialogTrigger>

                      <AlertDialogContent class="sm:max-w-sm">
                        <AlertDialogHeader>
                          <AlertDialogTitle>
                            Delete {{ role.name }} role?
                          </AlertDialogTitle>

                          <AlertDialogDescription>
                            This action cannot be undone. This will permanently delete the role, and users who are assigned this role.
                          </AlertDialogDescription>
                        </AlertDialogHeader>

                        <Separator />

                        <AlertDialogFooter>
                          <AlertDialogCancel>
                            Cancel
                          </AlertDialogCancel>

                          <AlertDialogAction
                            @click="handleDelete(role)">
                            Delete Anyway
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
