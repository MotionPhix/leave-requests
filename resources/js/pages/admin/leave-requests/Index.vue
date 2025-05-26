<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import dayjs from 'dayjs';
import { Table, TableHeader, TableHead, TableRow, TableBody, TableCell, TableCaption } from '@/components/ui/table';
import { Button } from '@/components/ui/button';

defineProps({ leaveRequests: Array });

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'All Leave requests',
    href: '/admin/leave-requests'
  }
];

const approve = (id) => {
  router.put(route('admin.leave-requests.approve', id), {}, {
    onSuccess: () => toast.success('Leave approved')
  });
};

const reject = (id) => {
  router.put(route('admin.leave-requests.reject', id), {}, {
    onSuccess: () => toast.success('Leave rejected')
  });
};
</script>

<template>
  <Head title="Employee leave requests" />

  <AppLayout :breadcrumbs="breadcrumbs">

    <div class="space-y-6 p-6">

      <h1 class="text-xl">
        Leave Requests
      </h1>

      <Table>
        <TableCaption>A list of your employees leave requests.</TableCaption>

        <TableHeader>
          <TableRow class="bg-gray-100">
            <TableHead>User</TableHead>
            <TableHead>Type</TableHead>
            <TableHead>Dates</TableHead>
            <TableHead>Status</TableHead>
            <TableHead></TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow
            v-for="leave in leaveRequests"
            :key="leave.id" class="border-t">
            <TableCell>{{ leave.user?.name }}</TableCell>

            <TableCell>{{ leave.leave_type?.name }}</TableCell>

            <TableCell>
              {{ dayjs(leave.start_date).format('MMM D') }} - {{ dayjs(leave.end_date).format('MMM D, YYYY') }}
            </TableCell>

            <TableCell>
              <span class="capitalize" :class="{
                'text-yellow-600': leave.status === 'pending',
                'text-green-600': leave.status === 'approved',
                'text-red-600': leave.status === 'rejected'
              }">
                {{ leave.status }}
              </span>
            </TableCell>

            <TableCell v-if="leave.status === 'pending'">
              <Button
                @click="approve(leave.id)"
                variant="link"
                class="text-green-600 hover:underline">
                Approve
              </Button>
              |
              <Button
                @click="reject(leave.id)"
                variant="link"
                class="text-red-600 hover:underline">
                Reject
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

  </AppLayout>
</template>
