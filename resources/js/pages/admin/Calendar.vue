<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { toast } from 'vue-sonner';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useEchoPublic } from '@laravel/echo-vue';
import { UserIcon } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Leave Calendar',
    href: '/admin/calendar'
  }
];

const calendarEvents = ref([]);
const selectedEvent = ref(null);
const isDialogOpen = ref(false);
const users = ref([]);
const leaveTypes = ref([]);
const isLoading = ref(false);
const error = ref(null);
const calendarRef = ref(null);

const filters = ref({
  status: 'all',
  type: 'all',
  user_id: 'all'
});

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: {
    left: 'prev,today,next',
    center: 'title',
    right: 'dayGridMonth,dayGridWeek'
  },
  events: calendarEvents.value,
  eventDidMount(info) {
    // Apply status-based styling
    const status = info.event.extendedProps.status;
    info.el.classList.add(`status-${status}`);
    console.log('It got mounted');

  },
  editable: true,
  eventDrop: handleEventDrop,
  eventResize: handleEventDrop,
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
    };
    isDialogOpen.value = true;
  },
  eventContent(arg) {
    const status = arg.event.extendedProps.status;
    return {
      html: `
        <div class="p-1 rounded shadow-sm overflow-hidden">
          <div class="text-xs font-medium">${arg.event.extendedProps.user}</div>
          <div class="text-xs">${arg.event.extendedProps.type}</div>
          <div class="text-xs italic">${status}</div>
        </div>
      `
    };
  },
  height: 'auto'
});

async function fetchCalendarEvents() {
  try {
    const response = await axios.get('/api/admin/calendar', {
      params: filters.value
    });

    calendarEvents.value = response.data;

    // Important: Update the calendar's events
    const calendarApi = calendarRef.value.getApi();

    calendarApi.removeAllEvents();
    calendarApi.addEventSource(calendarEvents.value);
  } catch (error) {
    console.error('Error fetching calendar events:', error);
    toast.error('Failed to fetch calendar events');
  }
}

async function handleEventDrop(info) {
  if (info.event.extendedProps.status === 'approved') {
    info.revert();
    toast.error("Cannot modify approved leave dates");
    return;
  }

  try {
    isLoading.value = true;
    error.value = null;

    await axios.put(`/api/admin/calendar/u/${info.event.id}`, {
      start: info.event.startStr,
      end: info.event.endStr
    });

    toast.success('Leave dates updated successfully');
    await fetchCalendarEvents();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update leave dates';
    info.revert();
    toast.error(error.value);
  } finally {
    isLoading.value = false;
  }
}

watch(filters, async () => {
  await fetchCalendarEvents();
}, { deep: true });

onMounted(async () => {
  await fetchCalendarEvents();

  // Fetch users and leave types for filters
  const [usersResponse, typesResponse] = await Promise.all([
    axios.get('/api/admin/users'),
    axios.get('/api/admin/leave-types')
  ]);

  users.value = usersResponse.data;
  leaveTypes.value = typesResponse.data;
});

// Listen for real-time updates
useEchoPublic('leave-requests', 'LeaveRequestUpdated', () => {
  fetchCalendarEvents();
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head title="Leave Calendar" />

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

        <Select v-model="filters.user_id">
          <SelectTrigger class="w-56">
            <SelectValue placeholder="Filter by employee" />
          </SelectTrigger>

          <SelectContent>
            <SelectItem value="all">All Employees</SelectItem>

            <SelectItem
              v-for="user in users"
              :key="user.id"
              :value="user.id">
              {{ user.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Calendar -->
      <FullCalendar ref="calendarRef" :options="calendarOptions" />

      <!-- Leave Details Dialog -->
      <Dialog v-model:open="isDialogOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Leave Request Details</DialogTitle>
          </DialogHeader>

          <DialogDescription class="flex items-center gap-2">
            <UserIcon class="size-4" />

            <span>
              <strong>{{ selectedEvent.user }}</strong>'s <span class="lowercase">{{ selectedEvent.type }}</span> details.
            </span>
          </DialogDescription>

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
            </div>
          </div>
        </DialogContent>
      </Dialog>
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
