<script setup lang="ts">
import { ref } from 'vue';
import FullCalendar from '@fullcalendar/vue3'; // Vue 3 version
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Leave Requests Calendar', href: '/calendar' }
];

const calendarEvents = ref([
  { title: 'Annual Leave - John Doe', start: '2025-05-28', end: '2025-05-30', color: '#1e40af' },
  { title: 'Sick Leave - Jane Smith', start: '2025-06-02', end: '2025-06-03', color: '#dc2626' }
]);

// Define calendar options object
const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  events: calendarEvents.value,
  dateClick(info) {
    alert(`Date clicked: ${info.dateStr}`);
  },
  eventClick(info) {
    alert(`Event clicked: ${info.event.title}`);
  },
  height: 'auto'
});
</script>

<template>
  <Head title="My leave requests calendar" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-4">
      <h1 class="text-2xl font-bold">Leave Calendar</h1>
      <div class="bg-white shadow rounded-lg p-4">
        <!-- Use :options to pass config to FullCalendar -->
        <FullCalendar :options="calendarOptions" class="rounded-lg" />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.fc .fc-toolbar-title {
  font-size: 1.25rem;
  font-weight: 600;
}

.fc .fc-button {
  background-color: #3b82f6;
  border-color: #3b82f6;
  color: #fff;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
}

.fc .fc-button:hover {
  background-color: #2563eb;
  border-color: #2563eb;
}
</style>
