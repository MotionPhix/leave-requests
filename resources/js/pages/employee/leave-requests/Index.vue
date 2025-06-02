<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { Button } from '@/components/ui/button';
import { computed, ref, watch } from 'vue';
import DatePicker from '@/components/DatePicker.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowRightIcon, ArrowLeftIcon, PlusIcon, SlidersHorizontalIcon } from 'lucide-vue-next';
import { formatDate } from '@/lib/utils';
import { useEchoPublic } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';
import {
  Calendar,
  Clock,
  AlertCircle,
  CheckCircle2,
  XCircle,
  ChevronRight
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import debounce from 'lodash/debounce';

dayjs.extend(relativeTime);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'My Leave Requests',
    href: '/leave-requests'
  }
];

const { props } = usePage();
const leaveRequests = props.leaveRequests.data;
const leaveTypes = props.leaveTypes;
const initialFilters = props.filters;
const filters = ref({ ...initialFilters });
const isLoading = ref(false);

// Computed properties for formatted filters
const formattedFilters = computed(() => ({
  status: filters.value.status,
  leave_type_id: filters.value.leave_type_id,
  date_from: formatDate(filters.value.date_from),
  date_to: formatDate(filters.value.date_to)
}));

function applyFilters() {
  isLoading.value = true;

  router.get(route('leave-requests.index'), formattedFilters.value, {
    onFinish: () => isLoading.value = false
  });
}

function resetFilters() {
  filters.value = { status: '', leave_type_id: '', date_from: '', date_to: '' };
  applyFilters();
}

watch(filters.value, debounce(() => {
  applyFilters();
}, 300));

useEchoPublic(
  `leave-requests`,
  'LeaveRequestUpdated',
  (e) => {
    toast(`Leave "${e.title}" updated.`);
    router.visit(route('leave-requests.index'), {
      only: ['leaveRequests']
    });
  }
);

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'approved':
      return CheckCircle2;
    case 'rejected':
      return XCircle;
    default:
      return Clock;
  }
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'approved':
      return 'default';
    case 'rejected':
      return 'destructive';
    default:
      return 'secondary';
  }
};

const getDayCount = (start: string, end: string) => {
  return dayjs(end).diff(dayjs(start), 'day') + 1;
};
</script>

<template>

  <Head title="My leave requests" />

  <AppLayout :breadcrumbs="breadcrumbs">

    <div class="space-y-6 p-6 max-w-4xl">

      <div class="flex gap-x-6 items-center">
        <h1 class="text-xl">My Leave Requests</h1>

        <div>
          <Button
            size="sm"
            :as="Link"
            variant="outline"
            :href="route('leave-requests.create')">
            <PlusIcon />
            New
          </Button>
        </div>
      </div>

      <div class="bg-white p-4 rounded-lg shadow-md shadow-2xs dark:bg-secondary">

        <div class="flex items-center justify-between mb-4">

          <h2 class="text-lg font-semibold">
            Filter leave requests
          </h2>

          <div>
            <Button
              size="icon"
              variant="outline"
              @click="resetFilters">
              <SlidersHorizontalIcon />
            </Button>
          </div>

        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <Select
            v-model="filters.leave_type_id">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="By type" />
            </SelectTrigger>

            <SelectContent>
              <SelectItem
                v-for="type in leaveTypes"
                :key="type.id" :value="type.id">
                {{ type.name }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select
            v-model="filters.status">
            <SelectTrigger class="w-full">
              <SelectValue placeholder="By status" />
            </SelectTrigger>

            <SelectContent>
              <SelectItem value="pending">Pending</SelectItem>
              <SelectItem value="approved">Approved</SelectItem>
              <SelectItem value="rejected">Rejected</SelectItem>
            </SelectContent>
          </Select>

          <DatePicker
            v-model="filters.date_from"
            placeholder="From date" />

          <DatePicker
            v-model="filters.date_to"
            placeholder="To date" />
        </div>

        <div class="mt-4 flex gap-2">
          <span
            v-if="isLoading"
            class="text-sm text-blue-600">
            Loading...
          </span>
        </div>
      </div>

      <div
        v-if="leaveRequests?.length"
        class="space-y-4">

        <Card
          v-for="leave in leaveRequests"
          :key="leave.id"
          class="hover:shadow-md transition-shadow">
          <CardContent class="px-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-muted flex items-start justify-center">
                  <Calendar class="w-6 h-6 text-muted-foreground" />
                </div>

                <div>
                  <h3 class="font-medium">
                    {{ leave.leave_type?.name }}
                  </h3>

                  <p class="text-sm text-muted-foreground mb-1">
                    {{ getDayCount(leave.start_date, leave.end_date) }} days
                  </p>

                  <Badge
                    class="md:hidden inline-flex"
                    :variant="getStatusColor(leave.status)">
                    <component
                      :is="getStatusIcon(leave.status)"
                      class="w-4 h-4 mr-1"
                    />

                    {{ leave.status }}
                  </Badge>
                </div>
              </div>

              <Badge
                class="hidden md:inline-flex"
                :variant="getStatusColor(leave.status)">
                <component
                  :is="getStatusIcon(leave.status)"
                  class="w-4 h-4 mr-1"
                />

                {{ leave.status }}
              </Badge>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 pt-4 border-t">
              <div>
                <p class="text-sm text-muted-foreground mb-1">
                  Start Date
                </p>

                <p class="font-medium">
                  {{ dayjs(leave.start_date).format('MMM D, YYYY') }}
                </p>
              </div>

              <div>
                <p class="text-sm text-muted-foreground mb-1">
                  End Date
                </p>

                <p class="font-medium">
                  {{ dayjs(leave.end_date).format('MMM D, YYYY') }}
                </p>
              </div>
            </div>

            <div v-if="leave.reason" class="mt-4 pt-4 border-t">
              <p class="text-sm text-muted-foreground mb-1">Reason</p>
              <p class="text-sm line-clamp-2">{{ leave.reason }}</p>
            </div>

            <div class="mt-4 pt-4 border-t flex items-center justify-between">
              <p class="text-sm text-muted-foreground">
                Submitted {{ dayjs(leave.created_at).fromNow() }}
              </p>

              <Link
                :href="route('leave-requests.show', leave.uuid)">
                <span class="flex items-center text-sm font-medium text-primary hover:underline">
                <span class="hidden md:inline-flex">View details</span>
                <ChevronRight
                  class="w-4 h-4 ml-1 hidden md:inline-flex"
                />

                  <ArrowRightIcon
                    class="w-4 h-4 ml-1 md:hidden inline-flex"
                  />
                </span>
              </Link>
            </div>
          </CardContent>
        </Card>

        <!-- Pagination -->
        <div class="flex items-center md:justify-between justify-end pt-4">
          <p class="text-sm text-muted-foreground hidden md:block">
            Showing {{ props.leaveRequests.from }} to {{ props.leaveRequests.to }}
            of {{ props.leaveRequests.total }} requests
          </p>

          <div class="flex gap-x-2">
            <Button
              variant="outline"
              size="sm"
              @click="router.get(props.leaveRequests.prev_page_url)"
              :disabled="!props.leaveRequests.prev_page_url">
              <ArrowLeftIcon class="w-4 h-4 mr-1" />
              Previous
            </Button>

            <Button
              variant="outline"
              size="sm"
              @click="router.get(props.leaveRequests.next_page_url)"
              :disabled="!props.leaveRequests.next_page_url">
              Next
              <ArrowRightIcon class="w-4 h-4 ml-1" />
            </Button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12">
        <AlertCircle class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
        <h3 class="text-lg font-medium mb-2">No leave requests found</h3>
        <p class="text-muted-foreground mb-6">
          You haven't made any leave requests yet.
        </p>
        <Button
          v-if="$page.props.auth.user.can['create leave']"
          :as="Link"
          :href="route('leave-requests.create')">
          <PlusIcon class="w-4 h-4 mr-2" />
          Request Leave
        </Button>
      </div>
    </div>

  </AppLayout>
</template>
