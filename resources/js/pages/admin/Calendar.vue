<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import {
  Dialog,
  DialogContent,
  DialogTitle,
  DialogDescription
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';
import { toast } from 'vue-sonner';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'My Leave Requests',
    href: '/leave-requests'
  }
];

const calendarEvents = ref([]);
const selectedEvent = ref(null);
const isDialogOpen = ref(false);
const users = ref([]);
const leaveTypes = ref([]);

const filters = ref({
  status: 'all',
  type: 'all',
  user_id: 'all'
});

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  events: calendarEvents.value,
  dateClick(info) {
    return;
    alert(`Date clicked: ${info.dateStr}`);
  },
  eventDrop(info) {
    handleEventDrop(info);
  },
  eventClick(info) {
    selectedEvent.value = {
      ...info.event.extendedProps,
      title: info.event.title,
      start: info.event.startStr,
      end: info.event.endStr
    };
    isDialogOpen.value = true;
  },
  eventContent(arg) {
    const status = arg.event.extendedProps.status;
    const colorClass = status === 'approved' ? 'bg-blue-500 text-white'
      : status === 'pending' ? 'bg-yellow-400 text-black'
        : 'bg-red-500 text-white';
    return { html: `<div class="px-1 py-0.5 rounded ${colorClass}">${arg.event.title}</div>` };
  },
  height: 'auto'
});

async function fetchCalendarEvents() {
  try {
    const response = await axios.get('/api/admin/calendar', { params: filters.value });
    calendarEvents.value = response.data;
    calendarOptions.value.events = calendarEvents.value;
  } catch (error) {
    console.error('Error fetching events', error);
  }
}

async function handleEventDrop(info) {
  try {
    await axios.patch(`/api/calendar/u/${info.event.id}`, {
      start: info.event.startStr,
      end: info.event.endStr
    });
    toast.success('Leave rescheduled successfully!');
  } catch (error) {
    toast.error('Error updating leave dates.');
    info.revert(); // Revert the drag
  }
}

async function loadUsers() {
  const { data } = await axios.get('/api/admin/users');
  users.value = data;
}

async function loadLeaveTypes() {
  const { data } = await axios.get('/api/admin/leave-types');
  leaveTypes.value = data;
}

onMounted(() => {
  fetchCalendarEvents();
  loadLeaveTypes();
  loadUsers();
});

watch(filters, fetchCalendarEvents, { deep: true });
</script>

<template>

  <Head title="My leave requests" />

  <AppLayout :breadcrumbs="breadcrumbs">

    <div class="mb-4 flex flex-wrap gap-2 p-6">
      <Select
        v-model="filters.status">
        <SelectTrigger>
          <SelectValue placeholder="Filter by status" />
        </SelectTrigger>

        <SelectContent>
          <SelectItem value="all">All Statuses</SelectItem>
          <SelectItem value="approved">Approved</SelectItem>
          <SelectItem value="pending">Pending</SelectItem>
          <SelectItem value="rejected">Rejected</SelectItem>
        </SelectContent>
      </Select>

      <Select
        v-model="filters.type">
        <SelectTrigger>
          <SelectValue placeholder="Filter by leave type" />
        </SelectTrigger>

        <SelectContent>
          <SelectItem value="all">All Types</SelectItem>
          <SelectItem v-for="leave in leaveTypes" :key="leave.id" :value="leave.id">
            {{ leave.name }}
          </SelectItem>
        </SelectContent>
      </Select>

      <Select
        v-if="users.length" v-model="filters.user_id">
        <SelectTrigger>
          <SelectValue placeholder="Filter by user" />
        </SelectTrigger>

        <SelectContent>
          <SelectItem value="all">All Users</SelectItem>
          <SelectItem v-for="user in users" :key="user.id" :value="user.id">
            {{ user.name }}
          </SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div class="p-6">
      <FullCalendar :options="calendarOptions" />
    </div>

    <Dialog v-model:open="isDialogOpen">
      <DialogContent>
        <DialogTitle>Leave Request Details</DialogTitle>
        <DialogDescription v-if="selectedEvent">
          <p><strong>Title:</strong> {{ selectedEvent.title }}</p>
          <p><strong>Status:</strong>
            <Badge
              :variant="selectedEvent.status === 'approved' ? 'success' : selectedEvent.status === 'pending' ? 'warning' : 'destructive'">
              {{ selectedEvent.status }}
            </Badge>
          </p>
          <p><strong>Reason:</strong> {{ selectedEvent.reason }}</p>
          <p><strong>Start Date:</strong> {{ selectedEvent.start }}</p>
          <p><strong>End Date:</strong> {{ selectedEvent.end }}</p>
        </DialogDescription>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
