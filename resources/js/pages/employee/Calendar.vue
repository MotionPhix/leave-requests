<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogTitle,
  AlertDialogHeader,
  AlertDialogCancel,
  AlertDialogDescription
} from '@/components/ui/alert-dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useEchoPublic } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { UserIcon, XIcon } from 'lucide-vue-next';
import DateRangePicker from '@/components/DateRangePicker.vue';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'My Leave Requests',
    href: '/leave-requests'
  }
];

const calendarEvents = ref([]);
const selectedEvent = ref(null);
const isDialogOpen = ref(false);
const leaveTypes = ref([]);
const calendarRef = ref(null);

const filters = ref({
  status: 'all',
  type: 'all',
  date_range: { start: null, end: null },
});

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  headerToolbar: {
    left: 'prev,next',
    center: 'title',
    right: 'dayGridMonth,dayGridWeek,today'
  },
  initialView: 'dayGridMonth',
  events: calendarEvents.value,
  eventDidMount(info) {
    // Apply status-based styling
    const status = info.event.extendedProps.status;
    info.el.classList.add(`status-${status}`);
    console.log('It got mounted');
  },
  eventClick(info) {
    selectedEvent.value = {
      ...info.event.extendedProps,
      id: info.event.id,
      title: info.event.title,
      start: info.event.startStr,
      end: info.event.endStr,
      color: info.event.backgroundColor,
      range: info.event.extendedProps.range,
      period: info.event.extendedProps.period,
      reason: info.event.extendedProps.reason,
      comment: info.event.extendedProps.comment,
    };

    isDialogOpen.value = true;
  },
  eventContent(arg) {
    const status = arg.event.extendedProps.status;
    return {
      html: `
        <div class="p-1 rounded shadow-sm overflow-hidden">
          <div class="text-xs">${arg.event.extendedProps.type}</div>
          <div class="text-xs italic text-gray-300">
            ${status}
          </div>
        </div>
      `
    };
  },
  height: 'auto'
});

async function fetchCalendarEvents() {
  try {
    const response = await axios.get('/api/calendar', {
      params: filters.value
    });

    calendarEvents.value = response.data;

    // Important: Update the calendar's events
    const calendarApi = calendarRef.value?.getApi();

    calendarApi.removeAllEvents();
    calendarApi.addEventSource(calendarEvents.value);
  } catch (error) {
    console.error('Error fetching calendar events:', error);
    toast.error('Failed to fetch calendar events');
  }
}

useEchoPublic(
  `leave-requests`,
  'LeaveRequestUpdated',
  (e) => {
    toast(`Leave "${e.title}" updated.`);
    fetchCalendarEvents();
  }
);

onMounted(() => {
  fetchCalendarEvents();
});

watch(filters, async () => {
  await fetchCalendarEvents();
}, { deep: true });

onMounted(async () => {
  await fetchCalendarEvents();

  // Fetch users and leave types for filters
  const [typesResponse] = await Promise.all([
    axios.get('/api/admin/leave-types')
  ]);

  leaveTypes.value = typesResponse.data;
});

// Listen for real-time updates
useEchoPublic('leave-requests', 'LeaveRequestUpdated', () => {
  fetchCalendarEvents();
});
</script>

<template>

  <Head title="My leave requests" />

  <AppLayout :breadcrumbs="breadcrumbs">

    <div class="space-y-6 p-6">
      <!-- Filters -->
      <div class="flex items-center gap-4">
        <Select v-model="filters.status">
          <SelectTrigger class="w-40">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>

          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="approved">Approved</SelectItem>
            <SelectItem value="rejected">Rejected</SelectItem>
          </SelectContent>
        </Select>

        <Select v-model="filters.type">
          <SelectTrigger class="w-40">
            <SelectValue placeholder="Filter by type" />
          </SelectTrigger>

          <SelectContent>
            <SelectItem value="all">All Types</SelectItem>
            <SelectItem
              v-for="type in leaveTypes"
              :key="type.id"
              :value="type.id">
              {{ type.name }}
            </SelectItem>
          </SelectContent>
        </Select>

        <div>
          <DateRangePicker
            v-model="filters.date_range"
            placeholder="Select your preferred leave days"
          />
        </div>
      </div>

      <!-- Calendar -->
      <FullCalendar ref="calendarRef" :options="calendarOptions" />

      <!-- Leave Details Dialog -->
      <AlertDialog v-model:open="isDialogOpen">
        <AlertDialogContent>

          <AlertDialogHeader>
            <AlertDialogTitle class="flex items-center justify-between">
              <span>
                Leave Request Details
              </span>

              <AlertDialogCancel as-child>
                <Button size="icon" variant="ghost">
                  <XIcon />
                </Button>
              </AlertDialogCancel>
            </AlertDialogTitle>

            <AlertDialogDescription class="flex items-center gap-2">
              <UserIcon class="size-4" />

              <span>
                My <span class="lowercase">{{ selectedEvent.type }}</span> details.
              </span>
            </AlertDialogDescription>

          </AlertDialogHeader>

          <div v-if="selectedEvent" class="space-y-4 border-t pt-4">
            <div class="grid gap-4 divide-y divide-gray-200 dark:divide-gray-700">
              <div class="pb-3">
                <p class="text-sm font-medium text-gray-500">Leave Type</p>
                <p class="text-lg font-semibold">{{ selectedEvent.type }}</p>
              </div>

              <div class="pb-3">
                <p class="text-sm font-medium text-gray-500">Status</p>
                <p class="capitalize text-lg font-semibold">{{ selectedEvent.status }}</p>
              </div>

              <div class="pb-3">
                <p class="text-sm font-medium text-gray-500">Duration</p>
                <p class="text-lg">
                  <span class="font-semibold">{{ selectedEvent.range }}</span>
                  |
                  <span class="font-light">{{ selectedEvent.period }} days</span>
                </p>
              </div>

              <div v-if="selectedEvent.reason">
                <p class="text-sm font-medium text-gray-500">Reason</p>
                <p class="mt-1">{{ selectedEvent.reason }}</p>
              </div>

              <div v-if="selectedEvent.comment">
                <p class="text-sm font-medium text-gray-500">Reason</p>
                <p class="mt-1">{{ selectedEvent.comment }}</p>
              </div>
            </div>
          </div>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  </AppLayout>
</template>

<style>
.fc-event {
  cursor: pointer;
  padding: 2px 4px;
}

.status-approved {
  background-color: #4CAF50 !important;
  border-color: #45a049 !important;
}

.status-rejected {
  background-color: #F44336 !important;
  border-color: #e53935 !important;
}

.status-pending {
  background-color: #FFC107 !important;
  border-color: #ffb300 !important;
}

/* Improve event text visibility */
.fc-event-title {
  color: white;
  font-weight: 500;
}
</style>

