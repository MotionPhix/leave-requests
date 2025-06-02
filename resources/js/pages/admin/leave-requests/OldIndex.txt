<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import dayjs from 'dayjs';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { Table, TableHeader, TableHead, TableRow, TableBody, TableCell, TableCaption } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import {Card, CardContent} from '@/components/ui/card'

const props = defineProps({
  leaveRequests: Object,
  leaveTypes: Array,
  filters: Object
});

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Employee leave requests',
    href: '/admin/leave-requests'
  }
];

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const type = ref(props.filters?.type || '');

watch([search, status, type], debounce(() => {
  router.get(
    route('admin.leave-requests.index'),
    { search: search.value, status: status.value, type: type.value },
    { preserveState: true, preserveScroll: true }
  );
}, 300));

const getStatusColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  };
  return colors[status] || '';
};

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
      <!-- Header -->
      <div class="grid gap-y-4">
        <div class="mb-6">

        <h1 class="text-xl font-semibold">
          Leave Requests
        </h1>

        <p class="text-sm text-muted-foreground">
          A list of employee leave requests.
        </p>
        </div>

        <!-- Filters -->
        <div class="flex items-center space-x-4">
          <Input
            v-model="search"
            placeholder="Search employee..."
            class="w-full max-w-sm"
          />

          <div class="flex-1"></div>

          <Select v-model="status">
            <SelectTrigger class="w-60">
              <SelectValue placeholder="Filter by status" />
            </SelectTrigger>

            <SelectContent>
              <SelectItem :value="null">All Status</SelectItem>
              <SelectItem value="pending">Pending</SelectItem>
              <SelectItem value="approved">Approved</SelectItem>
              <SelectItem value="rejected">Rejected</SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="type">
            <SelectTrigger class="w-60">
              <SelectValue placeholder="Filter by type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem :value="null">All Types</SelectItem>
              <SelectItem
                v-for="leaveType in leaveTypes"
                :key="leaveType.id"
                :value="leaveType.id"
              >
                {{ leaveType.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Table -->
       <Card>
        <CardContent>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Employee</TableHead>
            <TableHead>Type</TableHead>
            <TableHead>Duration</TableHead>
            <TableHead>Status</TableHead>
            <TableHead></TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow
            v-for="leave in leaveRequests.data"
            :key="leave.id"
            class="border-t hover:bg-gray-50">
            <TableCell class="font-medium">{{ leave.user?.name }}</TableCell>

            <TableCell>
              <Badge
              :variant="leave.leave_type?.name == 'Annual Leave'
                ? 'default'
                : leave.leave_type?.name == 'Sick Leave'
                ? 'secondary' : 'destructive'">
                {{ leave.leave_type?.name }}
              </Badge>
            </TableCell>

            <TableCell>
              <div class="flex flex-col">
                <span>{{ dayjs(leave.start_date).format('MMM D') }} - {{ dayjs(leave.end_date).format('MMM D, YYYY') }}</span>
                <span class="text-sm text-gray-500">
                  A total of {{ dayjs(leave.end_date).diff(dayjs(leave.start_date), 'days') + 1 }} days
                </span>
              </div>
            </TableCell>

            <TableCell>
              <Badge :class="getStatusColor(leave.status)">
                {{ leave.status }}
              </Badge>
            </TableCell>

            <TableCell>
              <div v-if="leave.status === 'pending'" class="flex space-x-2">
                <Button
                  @click="approve(leave.id)"
                  variant="outline"
                  class="text-green-600 hover:bg-green-50">
                  Approve
                </Button>

                <Button
                  @click="reject(leave.id)"
                  variant="outline"
                  class="text-red-600 hover:bg-red-50"
                >
                  Reject
                </Button>
              </div>

              <Dialog v-else>
                <DialogTrigger>
                  <Button variant="ghost" size="sm">Details</Button>
                </DialogTrigger>

                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>Leave Request Details</DialogTitle>
                  </DialogHeader>

                  <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <p class="text-sm font-medium text-gray-500">Status</p>
                        <Badge :class="getStatusColor(leave.status)">
                          {{ leave.status }}
                        </Badge>
                      </div>

                      <div>
                        <p class="text-sm font-medium text-gray-500">Duration</p>
                        <p>{{ dayjs(leave.start_date).format('MMM D') }} - {{ dayjs(leave.end_date).format('MMM D, YYYY') }}</p>
                      </div>
                    </div>

                    <div v-if="leave.comment" class="mt-4">
                      <p class="text-sm font-medium text-gray-500">Comment</p>
                      <p class="mt-1">{{ leave.comment }}</p>
                    </div>
                  </div>
                </DialogContent>
              </Dialog>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <!-- Pagination -->
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ leaveRequests.from }} to {{ leaveRequests.to }} of {{ leaveRequests.total }} results
        </div>
        <div class="space-x-2">
          <Button
            v-for="link in leaveRequests.links"
            :key="link.label"
            :disabled="!link.url"
            :class="{ 'bg-primary text-white': link.active }"
            variant="outline"
            @click="router.get(link.url)"
            v-html="link.label"
          />
        </div>
      </div>
        </CardContent>
       </Card>
    </div>
  </AppLayout>
</template>
