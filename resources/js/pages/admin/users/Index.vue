<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { MoreHorizontalIcon, PencilIcon, TrashIcon } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Employees',
    href: '/admin/employees'
  }
];

defineProps<{
  users: {
    data: Array<{
      id: number;
      name: string;
      email: string;
      gender: string;
      role: string;
      created_at: string;
    }>;
    links: Array<{
      url: string | null;
      label: string;
      active: boolean;
    }>;
  };
}>();
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Employees" />

    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Employees</h1>
        <Link :href="route('admin.employees.create')">
          <Button>New</Button>
        </Link>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Email</TableHead>
              <TableHead>Gender</TableHead>
              <TableHead>Role</TableHead>
              <TableHead>Created</TableHead>
              <TableHead class="w-[70px]"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="user in users.data" :key="user.id">
              <TableCell>{{ user.name }}</TableCell>
              <TableCell>{{ user.email }}</TableCell>
              <TableCell class="capitalize">{{ user.gender }}</TableCell>
              <TableCell>
                <Badge>{{ user.role }}</Badge>
              </TableCell>
              <TableCell>{{ user.created_at }}</TableCell>
              <TableCell>
                <DropdownMenu>
                  <DropdownMenuTrigger as="div">
                    <Button variant="ghost" size="icon">
                      <MoreHorizontalIcon class="w-4 h-4" />
                    </Button>
                  </DropdownMenuTrigger>

                  <DropdownMenuContent align="end">
                    <Link
                      :href="route('admin.employees.edit', user)">
                      <DropdownMenuItem>
                        <PencilIcon class="w-4 h-4 mr-2" />
                        Edit
                      </DropdownMenuItem>
                    </Link>

                    <DropdownMenuItem
                      @click="router.delete(route('admin.employees.delete', user.uuid))"
                      class="text-red-600">
                      <TrashIcon class="w-4 h-4 mr-2" />
                      Delete
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </AppLayout>
</template>
