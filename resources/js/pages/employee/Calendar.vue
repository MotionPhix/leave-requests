<script setup lang="ts">
import { ref, onMounted } from 'vue';
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
import { BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'My Leave Requests',
    href: '/leave-requests'
  }
];

const calendarEvents = ref([]);
const selectedEvent = ref(null);
const isDialogOpen = ref(false);

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  events: calendarEvents.value,
  dateClick(info) {
    alert(`Date clicked: ${info.dateStr}`);
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
    const response = await axios.get('/api/calendar');
    calendarEvents.value = response.data;
    calendarOptions.value.events = calendarEvents.value;
  } catch (error) {
    console.error('Error fetching events', error);
  }
}

onMounted(fetchCalendarEvents);
</script>

<template>

  <Head title="My leave requests" />

  <AppLayout :breadcrumbs="breadcrumbs">

    <FullCalendar :options="calendarOptions" />

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

          <div>
            <p><strong>Reason:</strong></p>
            <div>{{ selectedEvent.reason }}</div>
          </div>

          <p><strong>Start Date:</strong> {{ selectedEvent.start }}</p>
          <p><strong>End Date:</strong> {{ selectedEvent.end }}</p>
        </DialogDescription>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
