<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';
import {
  Calendar,
  Users,
  Search,
  Clock,
  CheckCircle2,
  XCircle,
  CalendarDays,
  MessageSquare
} from 'lucide-vue-next';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

dayjs.extend(relativeTime);

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
const isLoading = ref(false);

watch([search, status, type], debounce(() => {
  isLoading.value = true;
  router.get(
    route('admin.leave-requests.index'),
    { search: search.value, status: status.value, type: type.value },
    {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => isLoading.value = false
    }
  );
}, 300));

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

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'approved':
      return 'success';
    case 'rejected':
      return 'destructive';
    default:
      return 'warning';
  }
};

const getDurationInDays = (start: string, end: string) => {
  return dayjs(end).diff(dayjs(start), 'days') + 1;
};

const stats = computed(() => ({
  total: props.leaveRequests.total,
  pending: props.leaveRequests.data.filter(l => l.status === 'pending').length,
  approved: props.leaveRequests.data.filter(l => l.status === 'approved').length,
  rejected: props.leaveRequests.data.filter(l => l.status === 'rejected').length
}));
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6 max-w-5xl">
      <!-- Stats Overview -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        <Card>
          <CardContent class="px-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Total Requests</p>
                <p class="text-2xl font-bold">{{ stats.total }}</p>
              </div>
              <Users class="w-8 h-8 text-muted-foreground" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="px-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Pending</p>
                <p class="text-2xl font-bold text-warning">{{ stats.pending }}</p>
              </div>
              <Clock class="w-8 h-8 text-warning" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="px-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Approved</p>
                <p class="text-2xl font-bold text-success">{{ stats.approved }}</p>
              </div>
              <CheckCircle2 class="w-8 h-8 text-success" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="px-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-muted-foreground">Rejected</p>
                <p class="text-2xl font-bold text-destructive">{{ stats.rejected }}</p>
              </div>
              <XCircle class="w-8 h-8 text-destructive" />
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <div class="flex flex-col md:flex-row gap-4">
        <div class="relative w-full max-w-md">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
          <Input
            v-model="search"
            placeholder="Search by employee name..."
            class="pl-9"
          />
        </div>

        <div class="flex-1"></div>

        <Select v-model="status">
          <SelectTrigger class="w-full md:w-[180px]">
            <SelectValue placeholder="Status" />
          </SelectTrigger>

          <SelectContent>
            <SelectItem :value="null">All Status</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="approved">Approved</SelectItem>
            <SelectItem value="rejected">Rejected</SelectItem>
          </SelectContent>
        </Select>

        <Select v-model="type">
          <SelectTrigger class="w-full md:w-[180px]">
            <SelectValue placeholder="Leave Type" />
          </SelectTrigger>

          <SelectContent>
            <SelectItem
              :value="null">
              All Types
            </SelectItem>

            <SelectItem
              v-for="leaveType in leaveTypes"
              :key="leaveType.id"
              :value="leaveType.id">
              {{ leaveType.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div
        v-if="leaveRequests.data.length > 0"
        class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <Card
          v-for="leave in leaveRequests.data"
          :key="leave.id"
          class="hover:shadow-md transition-shadow">
          <CardContent class="px-6">
            <!-- Header with Employee Info and Status -->
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                  <span class="text-lg font-medium">
                    {{ leave.user.name.charAt(0) }}
                  </span>
                </div>

                <div>
                  <h3 class="font-medium">
                    {{ leave.user.name }}
                  </h3>

                  <p class="text-sm text-muted-foreground">
                    {{ leave.user.email }}
                  </p>
                </div>
              </div>

              <Badge
                class="capitalize py-1.5"
                :variant="getStatusBadgeVariant(leave.status)">
                <component
                  :is="leave.status === 'approved'
                  ? CheckCircle2
                  : leave.status === 'rejected'
                  ? XCircle
                  : Clock"
                  class="w-4 h-4 mr-1.5" />
                {{ leave.status }}
              </Badge>
            </div>

            <!-- Leave Details -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Leave Type</p>
                <Badge variant="outline" class="font-medium">
                  {{ leave.leave_type.name }}
                </Badge>
              </div>

              <div>
                <p class="text-sm text-muted-foreground mb-1">
                  Duration
                </p>

                <p class="font-medium">
                  {{ getDurationInDays(leave.start_date, leave.end_date) }} days
                </p>
              </div>
            </div>

            <!-- Date Range -->
            <div class="flex items-start gap-3 mb-6 bg-muted/50 p-3 rounded-lg">
              <CalendarDays class="w-5 h-5 text-muted-foreground mt-0.5" />

              <div>
                <p class="font-medium">
                  {{ dayjs(leave.start_date).format('MMM D') }} -
                  {{ dayjs(leave.end_date).format('MMM D, YYYY') }}
                </p>

                <p class="text-sm text-muted-foreground mt-1">
                  Submitted {{ dayjs(leave.created_at).fromNow() }}
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t">
              <div class="flex gap-2">
                <Button
                  v-if="leave.status === 'pending'"
                  variant="outline"
                  size="sm"
                  class="text-destructive hover:text-destructive"
                  @click="reject(leave.id)">
                  <XCircle class="w-4 h-4 mr-1.5" />
                  Reject
                </Button>

                <Button
                  v-if="leave.status === 'pending'"
                  size="sm"
                  class="text-success hover:text-success"
                  @click="approve(leave.id)">
                  <CheckCircle2 class="w-4 h-4 mr-1.5" />
                  Approve
                </Button>
              </div>

              <Button
                variant="ghost"
                size="sm"
                :as="Link"
                :href="route('admin.leave-requests.show', leave.uuid)">
                <MessageSquare class="w-4 h-4 mr-1.5" />
                View Details
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Loading State -->
      <div
        v-if="isLoading"
        class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <Card v-for="n in 4" :key="n">
          <CardContent class="px-6">
            <div class="space-y-4">
              <Skeleton class="h-12 w-full" />
              <div class="grid grid-cols-2 gap-4">
                <Skeleton class="h-8 w-full" />
                <Skeleton class="h-8 w-full" />
              </div>
              <Skeleton class="h-20 w-full" />
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Empty State -->
      <Card v-if="!isLoading && leaveRequests.data.length === 0">
        <CardContent class="p-12 text-center">
          <Calendar class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
          <h3 class="text-lg font-medium mb-2">No leave requests found</h3>
          <p class="text-muted-foreground">
            No leave requests match your current filters.
          </p>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          Showing {{ leaveRequests.from }} to {{ leaveRequests.to }}
          of {{ leaveRequests.total }} requests
        </p>

        <div class="flex gap-2">
          <Button
            v-for="link in leaveRequests.links"
            :key="link.label"
            :disabled="!link.url || isLoading"
            :variant="link.active ? 'default' : 'outline'"
            size="sm"
            @click="router.get(link.url)"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
