<script setup lang="ts">
import DateRangePicker from '@/components/DateRangePicker.vue';
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head } from '@inertiajs/vue3';
import { useEchoPublic } from '@laravel/echo-vue';
import { useStorage } from '@vueuse/core';
import axios from 'axios';
import {
  Calendar as CalendarIcon,
  Clock,
  Download,
  Eye,
  FileText,
  Filter,
  MapPin,
  RefreshCw,
  Search,
  TrendingUp,
  UserIcon,
  XIcon,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';

interface LeaveEvent {
  id: string;
  title: string;
  start: string;
  end: string;
  color: string;
  extendedProps: {
    status: string;
    type: string;
    reason: string;
    comment: string;
    period: number;
    range: string;
    user_name: string;
    leave_type_id: number;
    created_at: string;
    can_cancel: boolean;
  };
}

interface LeaveType {
  id: number;
  name: string;
  color: string;
}

interface CalendarStats {
  total_requests: number;
  pending_requests: number;
  approved_requests: number;
  rejected_requests: number;
  cancelled_requests: number;
  total_days_taken: number;
  upcoming_leaves: number;
  current_month_requests: number;
  leave_balance_summary: Array<{
    leave_type: string;
    max_days: number;
    used_days: number;
    remaining_days: number;
    color: string;
  }>;
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'My Leave Calendar',
    href: '/calendar',
  },
];

const calendarEvents = ref<LeaveEvent[]>([]);
const holidayEvents = ref<any[]>([]);
const selectedEvent = ref<LeaveEvent | null>(null);
const isDialogOpen = ref(false);
const leaveTypes = ref<LeaveType[]>([]);
const calendarRef = ref(null);
const isLoading = ref(false);
const isLoadingStats = ref(false);
const searchQuery = ref('');
const calendarStats = ref<CalendarStats>({
  total_requests: 0,
  pending_requests: 0,
  approved_requests: 0,
  rejected_requests: 0,
  cancelled_requests: 0,
  total_days_taken: 0,
  upcoming_leaves: 0,
  current_month_requests: 0,
  leave_balance_summary: [],
});

const filters = ref({
  status: 'all',
  type: 'all',
  start_date: null,
  end_date: null,
  search: '',
});

const currentView = useStorage('calendar_view', 'dayGridMonth');

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: '', // Remove the view buttons since we're using tabs
  },
  initialView: currentView.value,
  height: 'auto',
  events: [...calendarEvents.value, ...holidayEvents.value],
  eventDisplay: 'block',
  dayMaxEvents: 3,
  moreLinkClick: 'popover',
  eventDidMount(info: any) {
    // Apply status-based styling
    const status = info.event.extendedProps.status;
    if (status) {
      info.el.classList.add(`status-${status}`);
    }

    // Add tooltip
    const type = info.event.extendedProps.type || info.event.title;
    info.el.setAttribute('title', `${type}${status ? ` - ${status}` : ''}`);

    // Add hover effects
    info.el.style.cursor = 'pointer';
    info.el.style.transition = 'all 0.2s ease';
  },
  eventClick(info: any) {
    // Only handle leave events, not holidays
    if (info.event.extendedProps.type !== 'holiday') {
      selectedEvent.value = {
        id: info.event.id,
        title: info.event.title,
        start: info.event.startStr,
        end: info.event.endStr,
        color: info.event.backgroundColor,
        extendedProps: {
          ...info.event.extendedProps,
        },
      };
      isDialogOpen.value = true;
    }
  },
  eventContent(arg: any) {
    // Handle holiday events differently
    if (arg.event.extendedProps.type === 'holiday') {
      return {
        html: `<div class="fc-holiday-event">${arg.event.title}</div>`,
      };
    }

    const status = arg.event.extendedProps.status;
    const type = arg.event.extendedProps.type;

    return {
      html: `
        <div class="fc-event-content-wrapper">
          <div class="fc-event-title-container">
            <div class="fc-event-title">${type}</div>
            <div class="fc-event-status">${status}</div>
          </div>
          <div class="fc-event-period">${arg.event.extendedProps.period} day${arg.event.extendedProps.period > 1 ? 's' : ''}</div>
        </div>
      `,
    };
  },
}));

// Computed properties for filtering and stats
const filteredEvents = computed(() => {
  let events = calendarEvents.value;

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase();
    events = events.filter(
      (event) =>
        event.title.toLowerCase().includes(search) ||
        event.extendedProps.type.toLowerCase().includes(search) ||
        event.extendedProps.reason?.toLowerCase().includes(search),
    );
  }

  return events;
});

const statusCounts = computed(() => {
  const counts = {
    all: calendarEvents.value.length,
    pending: 0,
    approved: 0,
    rejected: 0,
    cancelled: 0,
  };

  calendarEvents.value.forEach((event) => {
    const status = event.extendedProps.status;
    if (counts.hasOwnProperty(status)) {
      counts[status as keyof typeof counts]++;
    }
  });

  return counts;
});

const leaveTypeColors = computed(() => {
  const colors: Record<string, string> = {};
  leaveTypes.value.forEach((type) => {
    colors[type.name] = type.color;
  });
  return colors;
});

async function fetchCalendarEvents() {
  isLoading.value = true;
  try {
    const [eventsResponse, holidaysResponse] = await Promise.all([
      axios.get('/api/calendar', {
        params: {
          ...filters.value,
          search: searchQuery.value,
        },
      }),

      axios.get('/api/calendar/holidays'),
    ]);

    calendarEvents.value = eventsResponse.data;
    holidayEvents.value = holidaysResponse.data;

    // Update the calendar's events
    await nextTick();
    const calendarApi = calendarRef.value?.getApi();
    if (calendarApi) {
      calendarApi.removeAllEvents();
      calendarApi.addEventSource([...calendarEvents.value, ...holidayEvents.value]);
    }
  } catch (error) {
    console.error('Error fetching calendar events:', error);
    toast.error('Failed to fetch calendar events');
  } finally {
    isLoading.value = false;
  }
}

async function fetchStatistics() {
  isLoadingStats.value = true;

  try {
    const response = await axios.get('/api/calendar/statistics');
    calendarStats.value = response.data;
  } catch (error) {
    console.error('Error fetching statistics:', error);
    toast.error('Failed to fetch statistics');
  } finally {
    isLoadingStats.value = false;
  }
}

async function exportCalendar(format: 'ics' | 'pdf' | 'csv') {
  try {
    const response = await axios.get(`/api/calendar/export/${format}`, {
      params: filters.value,
      responseType: 'blob',
    });

    const blob = new Blob([response.data], {
      type: response.headers['content-type'],
    });

    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `leave-calendar.${format}`;
    link.click();
    window.URL.revokeObjectURL(url);

    toast.success(`Calendar exported as ${format.toUpperCase()}`);
  } catch (error) {
    console.error('Export failed:', error);
    toast.error('Failed to export calendar');
  }
}

function clearFilters() {
  filters.value = {
    status: 'all',
    type: 'all',
    date_range: { start: null, end: null },
    search: '',
  };
  searchQuery.value = '';
}

function goToToday() {
  const calendarApi = calendarRef.value?.getApi();
  if (calendarApi) {
    calendarApi.today();
  }
}

function changeView(view: string) {
  const calendarApi = calendarRef.value?.getApi();
  if (calendarApi) {
    calendarApi.changeView(view);
    currentView.value = view;
  }
}

async function refreshData() {
  await Promise.all([fetchCalendarEvents(), fetchStatistics()]);
}

// Real-time updates
useEchoPublic('leave-requests', 'LeaveRequestUpdated', (e) => {
  toast(`Leave "${e.title}" updated.`);
  refreshData();
});

onMounted(async () => {
  // Fetch leave types for filters
  try {
    const typesResponse = await axios.get('/api/calendar/leave-types');
    leaveTypes.value = typesResponse.data;
  } catch (error) {
    console.error('Error fetching leave types:', error);
  }

  // Fetch initial data
  await refreshData();

  // Navigate to the selected year after initial load
  await nextTick();
});

// Watch for filter changes
watch(
  filters,
  async (newFilters, oldFilters) => {
    await refreshData();

    // If year changed, navigate to that year
    if (newFilters.year !== oldFilters?.year) {
      await nextTick();
    }
  },
  { deep: true },
);

// Watch for search query changes
watch(
  searchQuery,
  async () => {
    filters.value.search = searchQuery.value;
  },
  { debounce: 300 },
);

// Watch for view changes and update the calendar
watch(currentView, async (newView) => {
  await nextTick();
  changeView(newView);
});
</script>

<template>
  <Head title="My Leave Calendar" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-4xl space-y-6 p-6">
      <!-- Header with Stats -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">My Leave Calendar</h1>
          <p class="text-muted-foreground">View and manage your leave requests in calendar format</p>
        </div>

        <div class="flex items-center gap-2">
          <Button @click="goToToday" variant="outline" size="sm">
            <CalendarIcon class="mr-2 h-4 w-4" />
            Today
          </Button>

          <Button @click="refreshData" variant="outline" size="sm" :disabled="isLoading || isLoadingStats">
            <RefreshCw class="mr-2 h-4 w-4" :class="{ 'animate-spin': isLoading || isLoadingStats }" />
            Refresh
          </Button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-muted-foreground text-sm font-medium">Total Requests</p>
                <p class="text-2xl font-bold">{{ calendarStats.total_requests }}</p>
              </div>
              <FileText class="text-muted-foreground h-8 w-8" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-muted-foreground text-sm font-medium">Pending</p>
                <p class="text-2xl font-bold text-yellow-600">{{ calendarStats.pending_requests }}</p>
              </div>
              <Clock class="h-8 w-8 text-yellow-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-muted-foreground text-sm font-medium">Approved</p>
                <p class="text-2xl font-bold text-green-600">{{ calendarStats.approved_requests }}</p>
              </div>
              <UserIcon class="h-8 w-8 text-green-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-muted-foreground text-sm font-medium">Days Taken</p>
                <p class="text-2xl font-bold">{{ calendarStats.total_days_taken }}</p>
              </div>
              <CalendarIcon class="text-muted-foreground h-8 w-8" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-muted-foreground text-sm font-medium">Upcoming</p>
                <p class="text-2xl font-bold text-blue-600">{{ calendarStats.upcoming_leaves }}</p>
              </div>
              <MapPin class="h-8 w-8 text-blue-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-muted-foreground text-sm font-medium">Rejected</p>
                <p class="text-2xl font-bold text-red-600">{{ calendarStats.rejected_requests }}</p>
              </div>
              <XIcon class="h-8 w-8 text-red-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-muted-foreground text-sm font-medium">This Month</p>
                <p class="text-2xl font-bold text-purple-600">{{ calendarStats.current_month_requests }}</p>
              </div>
              <TrendingUp class="h-8 w-8 text-purple-600" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-muted-foreground text-sm font-medium">Cancelled</p>
                <p class="text-2xl font-bold text-gray-600">{{ calendarStats.cancelled_requests }}</p>
              </div>
              <XIcon class="h-8 w-8 text-gray-600" />
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Leave Balance Summary -->
      <Card v-if="calendarStats.leave_balance_summary.length > 0">
        <CardHeader>
          <CardTitle>Leave Balance Summary</CardTitle>
        </CardHeader>

        <CardContent>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="balance in calendarStats.leave_balance_summary" :key="balance.leave_type" class="rounded-lg border p-4">
              <div class="mb-2 flex items-center justify-between">
                <h4 class="font-medium">{{ balance.leave_type }}</h4>
                <Badge :variant="balance.remaining_days > 0 ? 'default' : 'destructive'"> {{ balance.remaining_days }}/{{ balance.max_days }} </Badge>
              </div>
              <div class="mb-2 h-2 w-full rounded-full bg-gray-200">
                <div
                  class="h-2 rounded-full transition-all"
                  :style="{
                    width: `${Math.min((balance.used_days / balance.max_days) * 100, 100)}%`,
                    backgroundColor: balance.color || '#3b82f6',
                  }"
                ></div>
              </div>
              <div class="text-muted-foreground flex justify-between text-sm">
                <span>Used: {{ balance.used_days }}</span>
                <span>Remaining: {{ balance.remaining_days }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Filters and Controls -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Filter class="h-5 w-5" />
            Filters & Controls
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-col gap-4 lg:flex-row">
            <!-- Search -->
            <div class="flex-1">
              <div class="relative">
                <Search class="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform" />
                <Input v-model="searchQuery" placeholder="Search leaves by type, reason..." class="pl-10" />
              </div>
            </div>

            <!-- Status Filter -->
            <Select v-model="filters.status">
              <SelectTrigger class="w-40">
                <SelectValue placeholder="Filter by status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status ({{ statusCounts.all }})</SelectItem>
                <SelectItem value="pending">Pending ({{ statusCounts.pending }})</SelectItem>
                <SelectItem value="approved">Approved ({{ statusCounts.approved }})</SelectItem>
                <SelectItem value="rejected">Rejected ({{ statusCounts.rejected }})</SelectItem>
              </SelectContent>
            </Select>

            <!-- Type Filter -->
            <Select v-model="filters.type">
              <SelectTrigger class="w-40">
                <SelectValue placeholder="Filter by type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Types</SelectItem>
                <SelectItem v-for="type in leaveTypes" :key="type.id" :value="type.id.toString()">
                  {{ type.name }}
                </SelectItem>
              </SelectContent>
            </Select>

            <!-- Date Range -->
            <DateRangePicker v-model="filters.date_range" placeholder="Filter by date range" class="w-64" />

            <!-- Clear Filters -->
            <Button @click="clearFilters" variant="outline"> Clear Filters </Button>

            <!-- Export Options -->
            <div class="flex items-center gap-2">
              <Button @click="exportCalendar('ics')" variant="outline" size="sm">
                <Download class="mr-2 h-4 w-4" />
                .ics
              </Button>
              <Button @click="exportCalendar('csv')" variant="outline" size="sm">
                <Download class="mr-2 h-4 w-4" />
                .csv
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Calendar Views Tabs -->
      <Tabs v-model="currentView" class="w-full">
        <TabsList class="mx-auto grid w-full max-w-xl grid-cols-3">
          <TabsTrigger value="dayGridMonth"> Month View </TabsTrigger>

          <TabsTrigger value="timeGridWeek"> Week View </TabsTrigger>

          <TabsTrigger value="listWeek"> List View </TabsTrigger>
        </TabsList>

        <!-- Single Calendar Instance for All Views -->
        <div class="mt-6">
          <Card>
            <CardContent class="p-6">
              <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </CardContent>
          </Card>
        </div>
      </Tabs>

      <!-- Legend -->
      <Card>
        <CardHeader>
          <CardTitle>Legend</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-4">
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded bg-green-500"></div>
              <span class="text-sm">Approved</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded bg-yellow-500"></div>
              <span class="text-sm">Pending</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded bg-red-500"></div>
              <span class="text-sm">Rejected</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded bg-gray-500"></div>
              <span class="text-sm">Cancelled</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded border border-blue-300 bg-blue-100"></div>
              <span class="text-sm">Holidays</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Leave Details Dialog -->
      <AlertDialog v-model:open="isDialogOpen">
        <AlertDialogContent class="max-w-2xl">
          <AlertDialogHeader>
            <AlertDialogTitle class="flex items-center justify-between">
              <span>Leave Request Details</span>
              <AlertDialogCancel as-child>
                <Button size="icon" variant="ghost">
                  <XIcon class="h-4 w-4" />
                </Button>
              </AlertDialogCancel>
            </AlertDialogTitle>
            <AlertDialogDescription class="flex items-center gap-2">
              <UserIcon class="size-4" />
              <span>My {{ selectedEvent?.extendedProps.type.toLowerCase() }} details</span>
            </AlertDialogDescription>
          </AlertDialogHeader>

          <div v-if="selectedEvent" class="space-y-6 border-t pt-6">
            <div class="grid gap-6 md:grid-cols-2">
              <!-- Left Column -->
              <div class="space-y-4">
                <div>
                  <p class="text-muted-foreground text-sm font-medium">Leave Type</p>
                  <p class="text-lg font-semibold">{{ selectedEvent.extendedProps.type }}</p>
                </div>

                <div>
                  <p class="text-muted-foreground text-sm font-medium">Status</p>
                  <Badge
                    :variant="
                      selectedEvent.extendedProps.status === 'approved'
                        ? 'default'
                        : selectedEvent.extendedProps.status === 'pending'
                          ? 'secondary'
                          : 'destructive'
                    "
                    class="capitalize"
                  >
                    {{ selectedEvent.extendedProps.status }}
                  </Badge>
                </div>

                <div>
                  <p class="text-muted-foreground text-sm font-medium">Duration</p>
                  <p class="text-lg">
                    <span class="font-semibold">{{ selectedEvent.extendedProps.range }}</span>
                  </p>
                  <p class="text-muted-foreground text-sm">
                    {{ selectedEvent.extendedProps.period }}
                    {{ selectedEvent.extendedProps.period === 1 ? 'day' : 'days' }}
                  </p>
                </div>
              </div>

              <!-- Right Column -->
              <div class="space-y-4">
                <div v-if="selectedEvent.extendedProps.reason">
                  <p class="text-muted-foreground text-sm font-medium">Reason</p>
                  <p class="text-sm leading-relaxed">{{ selectedEvent.extendedProps.reason }}</p>
                </div>

                <div v-if="selectedEvent.extendedProps.comment">
                  <p class="text-muted-foreground text-sm font-medium">Comments</p>
                  <p class="text-sm leading-relaxed">{{ selectedEvent.extendedProps.comment }}</p>
                </div>

                <div>
                  <p class="text-muted-foreground text-sm font-medium">Created</p>
                  <p class="text-sm">{{ new Date(selectedEvent.extendedProps.created_at).toLocaleDateString() }}</p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-2 border-t pt-4">
              <Button variant="outline" as-child>
                <a :href="`/leave-requests/${selectedEvent.id}`">
                  <Eye class="mr-2 h-4 w-4" />
                  View Details
                </a>
              </Button>
            </div>
          </div>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  </AppLayout>
</template>

<style>
/* Enhanced Calendar Styles */
.fc {
  font-family: inherit;
}

.fc-event {
  cursor: pointer;
  border-radius: 6px;
  border: none !important;
  padding: 2px 6px;
  margin: 1px 0;
  transition: all 0.2s ease;
}

.fc-event:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.fc-event-content-wrapper {
  padding: 2px;
}

.fc-event-title-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2px;
}

.fc-event-title {
  font-weight: 600;
  font-size: 0.75rem;
  color: white;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}

.fc-event-status {
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.9);
  text-transform: capitalize;
  background: rgba(255, 255, 255, 0.2);
  padding: 1px 4px;
  border-radius: 3px;
}

.fc-event-period {
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.8);
  font-style: italic;
}

.fc-holiday-event {
  font-size: 0.7rem;
  color: #374151;
  font-weight: 500;
  padding: 1px 4px;
}

/* Status-based colors */
.status-approved {
  background-color: #22c55e !important;
  border-color: #16a34a !important;
}

.status-rejected {
  background-color: #ef4444 !important;
  border-color: #dc2626 !important;
}

.status-pending {
  background-color: #f59e0b !important;
  border-color: #d97706 !important;
}

.status-cancelled {
  background-color: #6b7280 !important;
  border-color: #4b5563 !important;
}

.status-reviewed {
  background-color: #8b5cf6 !important;
  border-color: #7c3aed !important;
}

.status-rescheduled {
  background-color: #06b6d4 !important;
  border-color: #0891b2 !important;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .fc-header-toolbar {
    flex-direction: column;
    gap: 0.5rem;
  }

  .fc-toolbar-chunk {
    display: flex;
    justify-content: center;
  }

  .fc-event-title {
    font-size: 0.7rem;
  }

  .fc-event-status,
  .fc-event-period {
    font-size: 0.6rem;
  }
}

/* List view enhancements */
.fc-list-event:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.fc-list-event-title {
  font-weight: 600;
}

.fc-list-event-time {
  color: #6b7280;
}

/* Day grid enhancements */
.fc-daygrid-day-number {
  font-weight: 600;
  color: #374151;
}

.fc-day-today {
  background-color: rgba(59, 130, 246, 0.1) !important;
}

.fc-day-today .fc-daygrid-day-number {
  background-color: #3b82f6;
  color: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 2px;
}
</style>
