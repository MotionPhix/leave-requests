<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { visitModal } from '@inertiaui/modal-vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Badge } from '@/components/ui/badge'

// FullCalendar imports
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import listPlugin from '@fullcalendar/list'
import interactionPlugin from '@fullcalendar/interaction'

// Icons
import {
  Calendar as CalendarDays,
  Plus as CalendarPlus,
  Clock,
  RefreshCw,
  Search,
  UserIcon,
  Filter,
  FileText,
  RotateCcw,
} from 'lucide-vue-next'

// Echo for real-time updates
import { useEchoPublic } from '@laravel/echo-vue'
import { useStorage } from '@vueuse/core'
import axios from 'axios'
import { toast } from 'vue-sonner'

interface User {
  id: number
  uuid: string
  name: string
  email: string
}

interface CalendarEvent {
  id: string
  title: string
  start: string
  end: string
  backgroundColor: string
  borderColor: string
  textColor: string
  allDay?: boolean
  extendedProps: {
    type: 'leave' | 'holiday' | 'event'
    user?: string
    leaveType?: string
    status?: string
    isOwnRequest?: boolean
    canManage?: boolean
    reason?: string
    description?: string
    duration?: number
    period?: number
    range?: string
    user_name?: string
    leave_type_id?: number
    created_at?: string
    can_cancel?: boolean
    isRecurring?: boolean
    eventType?: string
    isMandatory?: boolean
    location?: string
  }
}

interface LeaveType {
  id: number
  name: string
  color: string
}

interface CalendarStats {
  total_requests: number
  pending_requests: number
  approved_requests: number
  rejected_requests: number
  cancelled_requests: number
  total_days_taken: number
  upcoming_leaves: number
  current_month_requests: number
  leave_balance_summary: Array<{
    leave_type: string
    max_days: number
    used_days: number
    remaining_days: number
    color: string
  }>
}

interface Props {
  workspace: any
  user: User
}

const props = defineProps<Props>()

// State
const calendarEvents = ref<CalendarEvent[]>([])
const leaveTypes = ref<LeaveType[]>([])
const calendarRef = ref(null)
const isLoading = ref(false)
const isLoadingStats = ref(false)
const searchQuery = ref('')

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
})

// Filters
const filters = ref({
  status: 'all',
  type: 'all',
  start_date: null,
  end_date: null,
  search: '',
})

// Calendar view storage
const currentView = useStorage('employee_calendar_view', 'dayGridMonth')

// Tenant params for route generation
const tenantParams = computed(() => ({
  tenant_slug: props.workspace.slug,
  tenant_uuid: props.workspace.uuid
}))

// FullCalendar configuration
const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: '', // Remove default view buttons since we use tabs
  },
  initialView: currentView.value,
  height: 'auto',
  events: calendarEvents.value,
  eventDisplay: 'block',
  dayMaxEvents: 3,
  moreLinkClick: 'popover',
  eventDidMount(info: any) {
    const status = info.event.extendedProps.status
    if (status) {
      info.el.classList.add(`status-${status}`)
    }

    const type = info.event.extendedProps.type || info.event.title
    info.el.setAttribute('title', `${type}${status ? ` - ${status}` : ''}`)

    info.el.style.cursor = 'pointer'
    info.el.style.transition = 'all 0.2s ease'
  },
  eventClick(info: any) {
    const event = info.event
    
    if (event.extendedProps.type === 'holiday') {
      toast.info(`Holiday: ${event.title}`)
    } else if (event.extendedProps.type === 'event') {
      // Show event details for employees (read-only)
      visitModal(route('tenant.events.show', {
        ...tenantParams.value,
        event: event.id
      }))
    } else {
      // Leave request - navigate to employee view
      visitModal(route('tenant.leave-requests.show', {
        ...tenantParams.value,
        leaveRequest: event.id
      }))
    }
  },
  dateClick(info: any) {
    const clickedDate = info.dateStr
    
    // Check if there are existing events/holidays on this date
    const eventsOnDate = calendarEvents.value.filter(event => {
      const eventStart = new Date(event.start).toISOString().split('T')[0]
      const eventEnd = event.end ? new Date(event.end).toISOString().split('T')[0] : eventStart
      return clickedDate >= eventStart && clickedDate <= eventEnd
    })
    
    // If there are company events/holidays, show them in toast
    const companyEventsOnDate = eventsOnDate.filter(e => 
      e.extendedProps.type === 'holiday' || e.extendedProps.type === 'event'
    )
    
    if (companyEventsOnDate.length > 0) {
      const eventTitles = companyEventsOnDate.map(e => e.title).join(', ')
      toast.info(`Events on this date: ${eventTitles}`)
    }
    
    // Always allow employees to create leave requests
    visitModal(route('tenant.leave-requests.create', {
      ...tenantParams.value,
      date: clickedDate
    }))
  },
  eventContent(arg: any) {
    const event = arg.event
    const status = event.extendedProps.status
    const type = event.extendedProps.type

    if (type === 'holiday') {
      return {
        html: `<div class="fc-holiday-event">${event.title}</div>`,
      }
    }

    if (type === 'event') {
      return {
        html: `
          <div class="fc-event-content-wrapper">
            <div class="fc-event-title">${event.title}</div>
            ${event.extendedProps.isMandatory ? '<div class="fc-event-mandatory">Required</div>' : ''}
          </div>
        `,
      }
    }

    // Leave request
    const leaveType = event.extendedProps.leaveType || event.title
    const duration = event.extendedProps.duration || event.extendedProps.period || 1

    return {
      html: `
        <div class="fc-event-content-wrapper">
          <div class="fc-event-title-container">
            <div class="fc-event-title">${leaveType}</div>
            <div class="fc-event-status">${status}</div>
          </div>
          <div class="fc-event-period">${duration} day${duration > 1 ? 's' : ''}</div>
        </div>
      `,
    }
  },
}))

// Computed properties
const statusCounts = computed(() => {
  const counts = {
    all: calendarEvents.value.filter(e => e.extendedProps.type === 'leave').length,
    pending: 0,
    approved: 0,
    rejected: 0,
    cancelled: 0,
  }

  calendarEvents.value
    .filter(e => e.extendedProps.type === 'leave')
    .forEach((event) => {
      const status = event.extendedProps.status
      if (status && counts.hasOwnProperty(status)) {
        counts[status as keyof typeof counts]++
      }
    })

  return counts
})

// Functions
async function fetchCalendarEvents() {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      ...filters.value,
      search: searchQuery.value,
    })

    const response = await axios.get(route('tenant.calendar.events', tenantParams.value), { params })
    calendarEvents.value = response.data

    // Update the calendar's events
    await nextTick()
    const calendarApi = calendarRef.value?.getApi()
    if (calendarApi) {
      calendarApi.removeAllEvents()
      calendarApi.addEventSource(calendarEvents.value)
    }
  } catch (error) {
    console.error('Error fetching calendar events:', error)
    toast.error('Failed to fetch calendar events')
  } finally {
    isLoading.value = false
  }
}

async function fetchStatistics() {
  try {
    isLoadingStats.value = true
    const response = await axios.get('/api/calendar/statistics')
    calendarStats.value = response.data
  } catch (error) {
    console.error('Error fetching statistics:', error)
    toast.error('Failed to fetch statistics')
  } finally {
    isLoadingStats.value = false
  }
}

function clearFilters() {
  filters.value = {
    status: 'all',
    type: 'all',
    start_date: null,
    end_date: null,
    search: '',
  }
  searchQuery.value = ''
}

function goToToday() {
  const calendarApi = calendarRef.value?.getApi()
  if (calendarApi) {
    calendarApi.today()
  }
}

function changeView(view: string) {
  const calendarApi = calendarRef.value?.getApi()
  if (calendarApi) {
    calendarApi.changeView(view)
    currentView.value = view
  }
}

async function refreshData() {
  await Promise.all([fetchCalendarEvents(), fetchStatistics()])
}

function createLeaveRequest() {
  visitModal(route('tenant.leave-requests.create', tenantParams.value))
}

// Real-time updates
useEchoPublic('leave-requests', 'LeaveRequestUpdated', (e) => {
  toast(`Leave "${e.title}" updated.`)
  refreshData()
})

// Lifecycle
onMounted(async () => {
  // Fetch leave types for filters
  try {
    const typesResponse = await axios.get('/api/calendar/leave-types')
    leaveTypes.value = typesResponse.data
  } catch (error) {
    console.error('Error fetching leave types:', error)
  }

  // Fetch initial data
  await refreshData()

  // Navigate to the selected view after initial load
  await nextTick()
})

// Watchers
watch(
  filters,
  async () => {
    await fetchCalendarEvents()
  },
  { deep: true }
)

watch(
  searchQuery,
  async () => {
    filters.value.search = searchQuery.value
  },
  { debounce: 300 }
)

watch(currentView, async (newView) => {
  await nextTick()
  changeView(newView)
})
</script>

<template>
  <TenantLayout>
    <Head :title="`My Calendar - ${workspace.name}`" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
            My Leave Calendar
          </h1>
          <p class="text-neutral-600 dark:text-neutral-400">
            View your leave requests, company events, and holidays in calendar format
          </p>
        </div>
        
        <div class="flex items-center gap-2">
          <Button @click="createLeaveRequest" class="bg-blue-600 hover:bg-blue-700 text-white">
            <CalendarPlus class="h-4 w-4 mr-2" />
            Request Leave
          </Button>

          <Button @click="goToToday" variant="outline" size="sm">
            <CalendarDays class="mr-2 h-4 w-4" />
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
                <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Requests</p>
                <p class="text-2xl font-bold">{{ calendarStats.total_requests }}</p>
              </div>
              <FileText class="h-8 w-8 text-neutral-600 dark:text-neutral-400" />
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="p-4">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Pending</p>
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
                <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Approved</p>
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
                <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Days Taken</p>
                <p class="text-2xl font-bold">{{ calendarStats.total_days_taken }}</p>
              </div>
              <CalendarDays class="h-8 w-8 text-neutral-600 dark:text-neutral-400" />
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
                <Badge :variant="balance.remaining_days > 0 ? 'default' : 'destructive'">
                  {{ balance.remaining_days }}/{{ balance.max_days }}
                </Badge>
              </div>
              <div class="mb-2 h-2 w-full rounded-full bg-neutral-200 dark:bg-neutral-700">
                <div
                  class="h-2 rounded-full transition-all"
                  :style="{
                    width: `${Math.min((balance.used_days / balance.max_days) * 100, 100)}%`,
                    backgroundColor: balance.color || '#3b82f6',
                  }"
                ></div>
              </div>
              <div class="flex justify-between text-sm text-neutral-600 dark:text-neutral-400">
                <span>Used: {{ balance.used_days }}</span>
                <span>Remaining: {{ balance.remaining_days }}</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Filter class="h-5 w-5" />
            Filter My Requests
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
            <!-- Search -->
            <div class="flex-1 min-w-0">
              <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transform text-neutral-600 dark:text-neutral-400" />
                <Input v-model="searchQuery" placeholder="Search your leave requests..." class="pl-10" />
              </div>
            </div>

            <!-- Filters Row -->
            <div class="flex flex-wrap items-center gap-2">
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

              <!-- Clear Filters -->
              <Button @click="clearFilters" variant="outline" size="icon">
                <RotateCcw class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Calendar Views Tabs -->
      <Tabs v-model="currentView" class="w-full">
        <TabsList class="mx-auto grid w-full max-w-xl grid-cols-3">
          <TabsTrigger value="dayGridMonth">Month View</TabsTrigger>
          <TabsTrigger value="timeGridWeek">Week View</TabsTrigger>
          <TabsTrigger value="listWeek">List View</TabsTrigger>
        </TabsList>

        <!-- FullCalendar Component -->
        <div class="mt-6">
          <Card>
            <CardContent class="p-6">
              <FullCalendar
                ref="calendarRef"
                :options="calendarOptions"
                class="calendar-container"
              />
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
              <span class="text-sm">Approved Leave</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded bg-yellow-500"></div>
              <span class="text-sm">Pending Leave</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded bg-red-500"></div>
              <span class="text-sm">Rejected Leave</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded bg-gray-500"></div>
              <span class="text-sm">Cancelled Leave</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded border border-blue-300 bg-blue-100"></div>
              <span class="text-sm">Company Holidays</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-4 w-4 rounded bg-purple-500"></div>
              <span class="text-sm">Company Events</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Loading Overlay -->
      <div v-if="isLoading" class="fixed inset-0 z-40 flex items-center justify-center bg-black bg-opacity-20">
        <div class="rounded-lg bg-white p-6 shadow-xl dark:bg-neutral-800">
          <div class="flex items-center space-x-3">
            <div class="h-6 w-6 animate-spin rounded-full border-b-2 border-blue-500"></div>
            <span class="text-neutral-700 dark:text-neutral-300">Loading calendar events...</span>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<style>
/* Enhanced FullCalendar Styles */
.calendar-container .fc {
  font-family: inherit;
}

.calendar-container .fc-event {
  cursor: pointer;
  border-radius: 6px;
  border: none !important;
  padding: 2px 6px;
  margin: 1px 0;
  transition: all 0.2s ease;
}

.calendar-container .fc-event:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.calendar-container .fc-event-content-wrapper {
  padding: 2px;
}

.calendar-container .fc-event-title-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2px;
}

.calendar-container .fc-event-title {
  font-weight: 600;
  font-size: 0.75rem;
  color: white;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}

.calendar-container .fc-event-status {
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.9);
  text-transform: capitalize;
  background: rgba(255, 255, 255, 0.2);
  padding: 1px 4px;
  border-radius: 3px;
}

.calendar-container .fc-event-period {
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.8);
  font-style: italic;
}

.calendar-container .fc-event-mandatory {
  font-size: 0.65rem;
  color: rgba(255, 255, 255, 0.9);
  text-transform: uppercase;
  background: rgba(255, 255, 255, 0.2);
  padding: 1px 4px;
  border-radius: 3px;
  margin-top: 2px;
}

.calendar-container .fc-holiday-event {
  font-size: 0.7rem;
  color: #374151;
  font-weight: 500;
  padding: 1px 4px;
}

/* Status-based colors */
.calendar-container .status-approved {
  background-color: #22c55e !important;
  border-color: #16a34a !important;
}

.calendar-container .status-rejected {
  background-color: #ef4444 !important;
  border-color: #dc2626 !important;
}

.calendar-container .status-pending {
  background-color: #f59e0b !important;
  border-color: #d97706 !important;
}

.calendar-container .status-cancelled {
  background-color: #6b7280 !important;
  border-color: #4b5563 !important;
}

/* Day grid enhancements */
.calendar-container .fc-daygrid-day-number {
  font-weight: 600;
  color: #374151;
}

.calendar-container .fc-daygrid-day {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.calendar-container .fc-daygrid-day:hover {
  background-color: rgba(59, 130, 246, 0.05) !important;
}

.calendar-container .fc-day-today {
  background-color: rgba(59, 130, 246, 0.1) !important;
}

.calendar-container .fc-day-today .fc-daygrid-day-number {
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

/* Dark mode support */
.dark .calendar-container .fc-daygrid-day-number {
  color: #f3f4f6;
}

.dark .calendar-container .fc-day-today {
  background-color: rgba(59, 130, 246, 0.2) !important;
}
</style>