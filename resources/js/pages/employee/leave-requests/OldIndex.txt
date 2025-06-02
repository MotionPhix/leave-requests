<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { Table, TableHeader, TableHead, TableRow, TableBody, TableCell } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { computed, ref, watch } from 'vue';
import DatePicker from '@/components/DatePicker.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowRightIcon, ArrowLeftIcon, PlusIcon, SlidersHorizontalIcon} from 'lucide-vue-next';
import { formatDate } from '@/lib/utils';
import { useEchoPublic } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';
import { debounce } from 'lodash';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'My Leave Requests',
    href: '/leave-requests'
  }
];

const { props } = usePage()
const leaveRequests = props.leaveRequests.data
const leaveTypes = props.leaveTypes
const initialFilters = props.filters
const filters = ref({ ...initialFilters })
const isLoading = ref(false)

// Computed properties for formatted filters
const formattedFilters = computed(() => ({
  status: filters.value.status,
  leave_type_id: filters.value.leave_type_id,
  date_from: formatDate(filters.value.date_from),
  date_to: formatDate(filters.value.date_to)
}))

function applyFilters() {
  isLoading.value = true

  router.get(route('leave-requests.index'), formattedFilters.value, {
    onFinish: () => isLoading.value = false
  })
}

function resetFilters() {
  filters.value = { status: '', leave_type_id: '', date_from: '', date_to: '' }
  applyFilters()
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
      only: ['leaveRequests'],
    })
  }
);
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
            <PlusIcon /> New
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
          <span v-if="isLoading" class="text-sm text-blue-600">Loading...</span>
        </div>
      </div>

      <Table v-if="leaveRequests?.length">
        <TableHeader>
          <TableRow>
            <TableHead>Type</TableHead>
            <TableHead>Dates</TableHead>
            <TableHead>Status</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow
            v-for="leave in leaveRequests"
            :key="leave.id" class="border-t">
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
          </TableRow>
        </TableBody>
      </table>

      <section v-else>
        <div>You don't have any leave requests yet.</div>
        <div v-if="$page.props.auth.user.can['create leave']">
          <Button
            :as="Link"
            :href="route('leave-requests.create')">
            Request leave
          </Button>
        </div>
      </section>

      <!-- Pagination -->
      <div class="flex gap-x-4 mt-4">
        <Button
          @click="router.get(props.leaveRequests.prev_page_url)"
          :disabled="!props.leaveRequests.prev_page_url">
          <ArrowLeftIcon />
        </Button>

        <Button
          @click="router.get(props.leaveRequests.next_page_url)"
          :disabled="!props.leaveRequests.next_page_url">
          <ArrowRightIcon />
        </Button>
      </div>
    </div>

  </AppLayout>
</template>
