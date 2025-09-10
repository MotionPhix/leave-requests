<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { ModalLink, Modal, visitModal } from '@inertiaui/modal-vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import { 
  Calendar as CalendarDays, 
  Plus as CalendarPlus,
  Users, 
  BarChart3, 
  ChevronLeft, 
  ChevronRight,
  ChevronDown,
  Clock
} from 'lucide-vue-next'

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
    type: 'leave' | 'holiday'
    user?: string
    leaveType?: string
    status?: string
    isOwnRequest?: boolean
    canManage?: boolean
    reason?: string
    description?: string
    duration?: number
  }
}

interface Props {
  workspace: any
  userRole: 'owner' | 'manager' | 'hr' | 'employee'
  teamMembers: User[]
  canManageHolidays: boolean
  canManageLeave: boolean
}

const props = defineProps<Props>()

// Calendar state
const currentDate = ref(new Date())
const currentView = ref<'month' | 'week' | 'day'>('month')
const isLoading = ref(false)
const events = ref<CalendarEvent[]>([])

// Filter state
const selectedTeamMembers = ref<number[]>([])
const showTeamFilter = ref(false)

// Modal states for local modals
const selectedDay = ref<CalendarDay | null>(null)
const selectedEvent = ref<CalendarEvent | null>(null)
const utilizationData = ref<any[]>([])

// Modal refs
const dayModalRef = ref(null)
const eventModalRef = ref(null)
const utilizationModalRef = ref(null)

// Calendar constants
const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const tenantParams = computed(() => ({
  tenant_slug: props.workspace.slug,
  tenant_uuid: props.workspace.uuid
}))

// Computed properties
const calendarDays = computed(() => {
  const days: CalendarDay[] = []
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  
  // Get first day of month and how many days in month
  const firstDay = new Date(year, month, 1).getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  
  // Previous month trailing days
  const prevMonth = new Date(year, month - 1, 0)
  for (let i = firstDay - 1; i >= 0; i--) {
    const day = prevMonth.getDate() - i
    const date = new Date(prevMonth.getFullYear(), prevMonth.getMonth(), day)
    const dayEvents = getEventsForDate(date)
    
    days.push({
      date,
      day,
      isCurrentMonth: false,
      isToday: false,
      hasEvents: dayEvents.length > 0,
      events: dayEvents
    })
  }
  
  // Current month days
  const today = new Date()
  for (let day = 1; day <= daysInMonth; day++) {
    const date = new Date(year, month, day)
    const isToday = date.toDateString() === today.toDateString()
    const dayEvents = getEventsForDate(date)
    
    days.push({
      date,
      day,
      isCurrentMonth: true,
      isToday,
      hasEvents: dayEvents.length > 0,
      events: dayEvents
    })
  }
  
  // Next month leading days
  const totalCells = 42 // 6 rows × 7 days
  const remainingDays = totalCells - days.length
  
  for (let day = 1; day <= remainingDays; day++) {
    const date = new Date(year, month + 1, day)
    const dayEvents = getEventsForDate(date)
    
    days.push({
      date,
      day,
      isCurrentMonth: false,
      isToday: false,
      hasEvents: dayEvents.length > 0,
      events: dayEvents
    })
  }
  
  return days
})

const weekDays = computed(() => {
  const days: CalendarDay[] = []
  const startOfWeek = new Date(currentDate.value)
  
  // Get the start of the current week (Sunday)
  const dayOfWeek = startOfWeek.getDay()
  startOfWeek.setDate(startOfWeek.getDate() - dayOfWeek)
  
  // Generate 7 days for the week
  for (let i = 0; i < 7; i++) {
    const date = new Date(startOfWeek)
    date.setDate(startOfWeek.getDate() + i)
    const dayEvents = getEventsForDate(date)
    
    const today = new Date()
    days.push({
      date,
      day: date.getDate(),
      isCurrentMonth: date.getMonth() === currentDate.value.getMonth(),
      isToday: date.toDateString() === today.toDateString(),
      hasEvents: dayEvents.length > 0,
      events: dayEvents
    })
  }
  
  return days
})

const filteredEvents = computed(() => {
  let filtered = events.value
  
  // Filter by selected team members if any
  if (selectedTeamMembers.value.length > 0) {
    filtered = filtered.filter(event => {
      // Always show holidays and own requests
      if (event.extendedProps.type === 'holiday' || event.extendedProps.isOwnRequest) {
        return true
      }
      // For other leave requests, check if user is selected
      return selectedTeamMembers.value.some(memberId => 
        props.teamMembers.find(m => m.id === memberId)?.name === event.extendedProps.user
      )
    })
  }
  
  return filtered
})

// Functions
function getEventsForDate(date: Date) {
  const dateStr = date.toISOString().split('T')[0]
  return filteredEvents.value.filter(event => {
    const eventStart = new Date(event.start).toISOString().split('T')[0]
    const eventEnd = new Date(event.end).toISOString().split('T')[0]
    return dateStr >= eventStart && dateStr <= eventEnd
  })
}

function navigateMonth(direction: number) {
  const newDate = new Date(currentDate.value)
  if (currentView.value === 'month') {
    newDate.setMonth(newDate.getMonth() + direction)
  } else if (currentView.value === 'week') {
    newDate.setDate(newDate.getDate() + (direction * 7))
  }
  currentDate.value = newDate
  fetchEvents()
}

function goToToday() {
  currentDate.value = new Date()
  fetchEvents()
}

async function fetchEvents() {
  isLoading.value = true
  
  try {
    const startOfMonth = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1)
    const endOfMonth = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 0)
    
    const params = new URLSearchParams({
      start: startOfMonth.toISOString().split('T')[0],
      end: endOfMonth.toISOString().split('T')[0],
    })
    
    const routeName = props.canManageLeave 
      ? 'tenant.management.calendar.events'
      : 'tenant.calendar.events'
      
    const response = await fetch(route(routeName, tenantParams.value) + '?' + params)
    const data = await response.json()
    
    events.value = data
    
  } catch (error) {
    console.error('Error fetching calendar events:', error)
  } finally {
    isLoading.value = false
  }
}

function openDayModal(day: CalendarDay) {
  selectedDay.value = day
  visitModal('#day-details')
}

function openEventModal(event: CalendarEvent) {
  selectedEvent.value = event
  visitModal('#event-details')
}

async function loadUtilizationData() {
  if (!props.canManageLeave) return
  
  try {
    const response = await fetch(route('tenant.management.calendar.utilization', tenantParams.value))
    utilizationData.value = await response.json()
    visitModal('#utilization-data')
  } catch (error) {
    console.error('Error loading utilization data:', error)
  }
}

function getStatusBadgeVariant(status: string) {
  switch (status) {
    case 'approved': return 'default'
    case 'pending': return 'secondary'
    case 'rejected': return 'destructive'
    default: return 'outline'
  }
}

function getEventTypeColor(event: CalendarEvent) {
  if (event.extendedProps.type === 'holiday') {
    return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900 dark:text-red-200'
  }
  
  if (event.extendedProps.isOwnRequest) {
    return event.extendedProps.status === 'approved' 
      ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900 dark:text-green-200'
      : 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900 dark:text-amber-200'
  }
  
  return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900 dark:text-blue-200'
}

// Watch for changes and refetch events  
watch(currentDate, () => {
  fetchEvents()
})

watch(selectedTeamMembers, () => {
  // No need to refetch, just filter existing events
})

// Close team filter when clicking outside
function closeTeamFilter(event: Event) {
  if (!(event.target as Element)?.closest('.team-filter-container')) {
    showTeamFilter.value = false
  }
}

onMounted(() => {
  fetchEvents()
  document.addEventListener('click', closeTeamFilter)
})

onUnmounted(() => {
  document.removeEventListener('click', closeTeamFilter)
})
</script>

<template>
  <TenantLayout>
    <Head title="Calendar" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Team Calendar</h1>
          <p class="text-neutral-600 dark:text-neutral-400">
            {{ userRole === 'employee' ? 'View team schedules and your leave requests' : 'Manage team schedules and leave requests' }}
          </p>
        </div>
        
        <div class="flex items-center space-x-3">
          <!-- Create Holiday Button (Owner only) -->
          <ModalLink
            v-if="canManageHolidays"
            :href="route('tenant.management.holidays.create', tenantParams)"
            class="inline-flex items-center gap-2 px-4 py-2 bg-neutral-900 dark:bg-white text-white dark:text-neutral-900 rounded-md hover:bg-neutral-800 dark:hover:bg-neutral-100 transition-colors"
          >
            <CalendarPlus class="w-4 h-4" />
            Add Holiday
          </ModalLink>

          <!-- Calendar View Toggle -->
          <div class="inline-flex bg-neutral-100 dark:bg-neutral-800 rounded-md p-1">
            <button
              @click="currentView = 'month'"
              :class="[
                'px-3 py-1.5 text-sm font-medium rounded transition-colors',
                currentView === 'month'
                  ? 'bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100 shadow-sm'
                  : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100'
              ]"
            >
              Month
            </button>
            <button
              @click="currentView = 'week'"
              :class="[
                'px-3 py-1.5 text-sm font-medium rounded transition-colors',
                currentView === 'week'
                  ? 'bg-white dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100 shadow-sm'
                  : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100'
              ]"
            >
              Week
            </button>
          </div>
        </div>
      </div>

      <!-- Calendar Navigation -->
      <div class="flex items-center justify-between bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
        <div class="flex items-center space-x-4">
          <!-- Month/Year Navigation -->
          <div class="flex items-center space-x-2">
            <button
              @click="navigateMonth(-1)"
              class="p-2 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors"
            >
              <ChevronLeft class="w-5 h-5 text-neutral-600 dark:text-neutral-400" />
            </button>
            
            <div class="text-center">
              <h2 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                <template v-if="currentView === 'month'">
                  {{ currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) }}
                </template>
                <template v-else-if="currentView === 'week'">
                  Week of {{ weekDays[0]?.date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }} - {{ weekDays[6]?.date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                </template>
              </h2>
            </div>
            
            <button
              @click="navigateMonth(1)"
              class="p-2 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors"
            >
              <ChevronRight class="w-5 h-5 text-neutral-600 dark:text-neutral-400" />
            </button>
          </div>

          <!-- Today Button -->
          <button
            @click="goToToday"
            class="px-3 py-1.5 bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded-md hover:bg-neutral-200 dark:hover:bg-neutral-600 transition-colors text-sm font-medium"
          >
            Today
          </button>
        </div>

        <!-- Calendar Filters -->
        <div class="flex items-center space-x-3">
          <!-- Team Member Filter (Management only) -->
          <div v-if="canManageLeave && teamMembers.length > 0" class="relative team-filter-container">
            <button
              @click="showTeamFilter = !showTeamFilter"
              class="flex items-center space-x-2 px-3 py-1.5 bg-neutral-100 dark:bg-neutral-700 rounded-md hover:bg-neutral-200 dark:hover:bg-neutral-600 transition-colors text-sm"
            >
              <Users class="w-4 h-4 text-neutral-600 dark:text-neutral-400" />
              <span class="text-neutral-700 dark:text-neutral-300">Team Filter</span>
              <ChevronDown class="w-4 h-4 text-neutral-500 dark:text-neutral-400" />
            </button>

            <!-- Team Filter Dropdown -->
            <div v-if="showTeamFilter" class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-neutral-800 rounded-lg shadow-lg border border-neutral-200 dark:border-neutral-700 p-3 z-50">
              <div class="space-y-2 max-h-64 overflow-y-auto">
                <label class="flex items-center space-x-2 p-2 rounded hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-colors">
                  <input
                    type="checkbox"
                    :checked="selectedTeamMembers.length === 0"
                    @change="selectedTeamMembers = []"
                    class="rounded border-neutral-300 dark:border-neutral-600"
                  />
                  <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">All Team Members</span>
                </label>
                <hr class="border-neutral-200 dark:border-neutral-600" />
                <label
                  v-for="member in teamMembers"
                  :key="member.id"
                  class="flex items-center space-x-2 p-2 rounded hover:bg-neutral-50 dark:hover:bg-neutral-700 transition-colors cursor-pointer"
                >
                  <input
                    type="checkbox"
                    :value="member.id"
                    v-model="selectedTeamMembers"
                    class="rounded border-neutral-300 dark:border-neutral-600"
                  />
                  <span class="text-sm text-neutral-600 dark:text-neutral-400">{{ member.name }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Utilization Button (Management only) -->
          <button
            v-if="canManageLeave"
            @click="loadUtilizationData"
            class="flex items-center space-x-2 px-3 py-1.5 bg-neutral-100 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded-md hover:bg-neutral-200 dark:hover:bg-neutral-600 transition-colors text-sm"
          >
            <BarChart3 class="w-4 h-4" />
            <span>Team Utilization</span>
          </button>
        </div>
      </div>

      <!-- Calendar Grid - Month View -->
      <div v-if="currentView === 'month'" class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <!-- Calendar Header Days -->
        <div class="grid grid-cols-7 border-b border-neutral-200 dark:border-neutral-700">
          <div
            v-for="day in weekdays"
            :key="day"
            class="text-center p-3 text-sm font-medium text-neutral-600 dark:text-neutral-400 border-r border-neutral-200 dark:border-neutral-700 last:border-r-0"
          >
            {{ day }}
          </div>
        </div>

        <!-- Calendar Days Grid -->
        <div class="grid grid-cols-7">
          <div
            v-for="(day, index) in calendarDays"
            :key="`${day.date.getFullYear()}-${day.date.getMonth()}-${day.date.getDate()}`"
            :class="[
              'aspect-square p-2 border-r border-b border-neutral-200 dark:border-neutral-700 last:border-r-0 transition-colors cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-700/50',
              (index + 1) % 7 === 0 ? 'border-r-0' : '',
              index >= (calendarDays.length - 7) ? 'border-b-0' : '',
              day.isCurrentMonth
                ? 'bg-white dark:bg-neutral-800'
                : 'bg-neutral-50/50 dark:bg-neutral-900/50',
              day.isToday
                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700'
                : ''
            ]"
            @click="openDayModal(day)"
          >
            <!-- Day Number -->
            <div class="flex justify-between items-start mb-1">
              <div
                :class="[
                  'text-sm font-medium',
                  day.isToday
                    ? 'text-blue-600 dark:text-blue-400'
                    : day.isCurrentMonth
                    ? 'text-neutral-900 dark:text-neutral-100'
                    : 'text-neutral-400 dark:text-neutral-600'
                ]"
              >
                {{ day.date.getDate() }}
              </div>
              
              <!-- Event Count Indicator -->
              <div v-if="day.hasEvents" class="text-xs text-neutral-500 dark:text-neutral-400">
                {{ day.events.length }}
              </div>
            </div>

            <!-- Events Display -->
            <div class="space-y-1 overflow-hidden">
              <div
                v-for="event in day.events.slice(0, 2)"
                :key="event.id"
                :class="[
                  'px-2 py-0.5 rounded text-xs font-medium truncate cursor-pointer',
                  event.extendedProps.type === 'holiday'
                    ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
                    : event.extendedProps.isOwnRequest
                    ? getStatusColorClasses(event.extendedProps.status, true)
                    : getStatusColorClasses(event.extendedProps.status, false)
                ]"
                @click.stop="openEventModal(event)"
              >
                {{ event.title }}
              </div>
              
              <!-- More events indicator -->
              <div v-if="day.events.length > 2" class="text-xs text-neutral-500 dark:text-neutral-400 px-2">
                +{{ day.events.length - 2 }} more
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Calendar Grid - Week View -->
      <div v-else-if="currentView === 'week'" class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <!-- Calendar Header Days -->
        <div class="grid grid-cols-7 border-b border-neutral-200 dark:border-neutral-700">
          <div
            v-for="(day, index) in weekdays"
            :key="day"
            class="p-4 text-center text-sm font-medium text-neutral-600 dark:text-neutral-400 bg-neutral-50 dark:bg-neutral-900/50"
          >
            {{ day }}
          </div>
        </div>

        <!-- Week Days Grid -->
        <div class="grid grid-cols-7 min-h-[400px]">
          <div
            v-for="(day, index) in weekDays"
            :key="`week-${day.date.getFullYear()}-${day.date.getMonth()}-${day.date.getDate()}`"
            :class="[
              'p-2 border-r border-neutral-200 dark:border-neutral-700 last:border-r-0 transition-colors cursor-pointer hover:bg-neutral-50 dark:hover:bg-neutral-700/50',
              day.isCurrentMonth
                ? 'bg-white dark:bg-neutral-800'
                : 'bg-neutral-50/50 dark:bg-neutral-900/50',
              day.isToday
                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700'
                : ''
            ]"
            @click="openDayModal(day)"
          >
            <!-- Day Number -->
            <div class="flex justify-between items-start mb-1">
              <div
                :class="[
                  'text-sm font-medium w-6 h-6 rounded-full flex items-center justify-center',
                  day.isToday
                    ? 'bg-blue-600 text-white'
                    : day.isCurrentMonth
                      ? 'text-neutral-900 dark:text-neutral-100'
                      : 'text-neutral-400 dark:text-neutral-600'
                ]"
              >
                {{ day.day }}
              </div>
            </div>

            <!-- Events -->
            <div class="space-y-1 max-h-[350px] overflow-y-auto">
              <div
                v-for="event in day.events.slice(0, 8)"
                :key="event.id"
                :style="{ backgroundColor: event.backgroundColor }"
                :class="[
                  'text-xs px-2 py-1 rounded text-white cursor-pointer hover:opacity-90 transition-opacity truncate'
                ]"
                @click.stop="openEventModal(event)"
              >
                {{ event.title }}
              </div>
              <div
                v-if="day.events.length > 8"
                class="text-xs text-neutral-600 dark:text-neutral-400 px-2 py-1 bg-neutral-100 dark:bg-neutral-700 rounded cursor-pointer hover:bg-neutral-200 dark:hover:bg-neutral-600 transition-colors"
                @click.stop="openDayModal(day)"
              >
                +{{ day.events.length - 8 }} more
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading Overlay -->
    <div v-if="isLoading" class="fixed inset-0 bg-black bg-opacity-20 flex items-center justify-center z-40">
      <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 shadow-xl">
        <div class="flex items-center space-x-3">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
          <span class="text-neutral-700 dark:text-neutral-300">Loading calendar events...</span>
        </div>
      </div>
    </div>

    <!-- Team Utilization Modal -->
    <Modal name="utilization-data" ref="utilizationModalRef">
      <div class="w-full max-w-4xl max-h-[80vh] overflow-y-auto bg-white dark:bg-neutral-800 rounded-lg shadow-xl border border-neutral-200 dark:border-neutral-700">
        <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <BarChart3 class="h-6 w-6 mr-3 text-neutral-600 dark:text-neutral-400" />
              <div>
                <h3 class="text-xl font-semibold text-neutral-900 dark:text-neutral-100">Team Utilization</h3>
                <p class="text-sm text-neutral-600 dark:text-neutral-400">
                  {{ currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) }}
                </p>
              </div>
            </div>
            <button @click="utilizationModalRef?.close()" class="p-2 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors">
              <span class="text-2xl text-neutral-500 dark:text-neutral-400">&times;</span>
            </button>
          </div>
        </div>
        <div class="p-6">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b-2 border-neutral-200 dark:border-neutral-700">
                  <th class="text-left p-4 font-semibold text-neutral-700 dark:text-neutral-300">Employee</th>
                  <th class="text-left p-4 font-semibold text-neutral-700 dark:text-neutral-300">Leave Days</th>
                  <th class="text-left p-4 font-semibold text-neutral-700 dark:text-neutral-300">Requests</th>
                  <th class="text-left p-4 font-semibold text-neutral-700 dark:text-neutral-300">Utilization</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="data in utilizationData"
                  :key="data.user_id"
                  class="border-b border-neutral-100 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-700/50 transition-colors"
                >
                  <td class="p-4">
                    <div class="flex items-center space-x-3">
                      <div class="w-8 h-8 bg-neutral-200 dark:bg-neutral-600 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                          {{ data.user_name.charAt(0) }}
                        </span>
                      </div>
                      <span class="font-medium text-neutral-900 dark:text-neutral-100">{{ data.user_name }}</span>
                    </div>
                  </td>
                  <td class="p-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-700 text-neutral-800 dark:text-neutral-200 border border-neutral-200 dark:border-neutral-600">
                      {{ data.total_leave_days }} days
                    </span>
                  </td>
                  <td class="p-4">
                    <span class="text-neutral-700 dark:text-neutral-300">{{ data.leave_requests }}</span>
                  </td>
                  <td class="p-4">
                    <div class="flex items-center space-x-3">
                      <div class="flex-1 bg-neutral-200 dark:bg-neutral-600 rounded-full h-3 min-w-[100px]">
                        <div
                          class="h-3 rounded-full transition-all duration-500"
                          :class="{
                            'bg-green-500': data.utilization_percentage <= 10,
                            'bg-yellow-500': data.utilization_percentage > 10 && data.utilization_percentage <= 20,
                            'bg-red-500': data.utilization_percentage > 20
                          }"
                          :style="`width: ${Math.min(data.utilization_percentage, 100)}%`"
                        />
                      </div>
                      <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300 min-w-[45px]">{{ data.utilization_percentage }}%</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </Modal>

    <!-- Event Details Modal -->
    <Modal name="event-details" ref="eventModalRef">
      <div v-if="selectedEvent" class="w-full max-w-lg bg-white dark:bg-neutral-800 rounded-lg shadow-xl border border-neutral-200 dark:border-neutral-700">
        <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <CalendarDays class="h-5 w-5 mr-2 text-neutral-600 dark:text-neutral-400" />
              <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">{{ selectedEvent.title }}</h3>
            </div>
            <button @click="eventModalRef?.close()" class="p-1 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors">
              <span class="text-2xl text-neutral-500 dark:text-neutral-400">&times;</span>
            </button>
          </div>
        </div>
        <div class="p-6 space-y-4">
          <div v-if="selectedEvent.extendedProps.type === 'leave'">
            <div v-if="selectedEvent.extendedProps.status" class="flex items-center justify-between py-2">
              <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Status:</span>
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  selectedEvent.extendedProps.status === 'approved' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' :
                  selectedEvent.extendedProps.status === 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' :
                  'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
                ]"
              >
                {{ selectedEvent.extendedProps.status }}
              </span>
            </div>
            
            <div v-if="selectedEvent.extendedProps.user" class="flex items-center justify-between py-2">
              <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Employee:</span>
              <div class="flex items-center space-x-2">
                <div class="w-6 h-6 bg-neutral-200 dark:bg-neutral-600 rounded-full flex items-center justify-center">
                  <span class="text-xs font-medium text-neutral-700 dark:text-neutral-300">
                    {{ selectedEvent.extendedProps.user.charAt(0) }}
                  </span>
                </div>
                <span class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ selectedEvent.extendedProps.user }}</span>
              </div>
            </div>
            
            <div v-if="selectedEvent.extendedProps.leaveType" class="flex items-center justify-between py-2">
              <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Leave Type:</span>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral-100 dark:bg-neutral-700 text-neutral-800 dark:text-neutral-200 border border-neutral-200 dark:border-neutral-600">
                {{ selectedEvent.extendedProps.leaveType }}
              </span>
            </div>
            
            <div v-if="selectedEvent.extendedProps.duration" class="flex items-center justify-between py-2">
              <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Duration:</span>
              <span class="text-sm font-medium flex items-center text-neutral-900 dark:text-neutral-100">
                <Clock class="h-4 w-4 mr-1" />
                {{ selectedEvent.extendedProps.duration }} day{{ selectedEvent.extendedProps.duration! > 1 ? 's' : '' }}
              </span>
            </div>
            
            <div v-if="selectedEvent.extendedProps.reason" class="space-y-2 pt-2">
              <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Reason:</span>
              <p class="text-sm text-neutral-700 dark:text-neutral-300 bg-neutral-50 dark:bg-neutral-700 p-3 rounded-lg">
                {{ selectedEvent.extendedProps.reason }}
              </p>
            </div>
            
            <div v-if="selectedEvent.extendedProps.canManage" class="flex space-x-2 pt-4">
              <button 
                class="flex-1 px-4 py-2 bg-neutral-900 dark:bg-white text-white dark:text-neutral-900 rounded-md hover:bg-neutral-800 dark:hover:bg-neutral-100 transition-colors"
                @click="router.visit(route('tenant.management.leave-requests.show', {...tenantParams, leaveRequest: selectedEvent.id}))"
              >
                View Details
              </button>
            </div>
          </div>
          
          <div v-if="selectedEvent.extendedProps.type === 'holiday'">
            <div v-if="selectedEvent.extendedProps.description" class="space-y-2">
              <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Description:</span>
              <p class="text-sm text-neutral-700 dark:text-neutral-300 bg-neutral-50 dark:bg-neutral-700 p-3 rounded-lg">
                {{ selectedEvent.extendedProps.description }}
              </p>
            </div>
            
            <div v-if="selectedEvent.extendedProps.duration" class="flex items-center justify-between py-2">
              <span class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Duration:</span>
              <span class="text-sm font-medium flex items-center text-neutral-900 dark:text-neutral-100">
                <Clock class="h-4 w-4 mr-1" />
                {{ selectedEvent.extendedProps.duration }} day{{ selectedEvent.extendedProps.duration! > 1 ? 's' : '' }}
              </span>
            </div>
            
            <div v-if="selectedEvent.extendedProps.canManage" class="flex space-x-2 pt-4">
              <button 
                class="flex-1 px-4 py-2 bg-neutral-900 dark:bg-white text-white dark:text-neutral-900 rounded-md hover:bg-neutral-800 dark:hover:bg-neutral-100 transition-colors"
                @click="router.visit(route('tenant.management.holidays.edit', {...tenantParams, holiday: selectedEvent.id}))"
              >
                Edit Holiday
              </button>
            </div>
          </div>
        </div>
      </div>
    </Modal>

    <!-- Day Details Modal -->
    <Modal name="day-details" ref="dayModalRef">
      <div v-if="selectedDay" class="w-full max-w-2xl bg-white dark:bg-neutral-800 rounded-lg shadow-xl border border-neutral-200 dark:border-neutral-700">
        <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <CalendarDays class="h-5 w-5 mr-2 text-neutral-600 dark:text-neutral-400" />
              <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                {{ selectedDay.date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) }}
              </h3>
            </div>
            <button @click="dayModalRef?.close()" class="p-1 rounded-md hover:bg-neutral-100 dark:hover:bg-neutral-700 transition-colors">
              <span class="text-2xl text-neutral-500 dark:text-neutral-400">&times;</span>
            </button>
          </div>
        </div>
        <div class="p-6">
          <div v-if="selectedDay.events.length > 0" class="space-y-4">
            <h4 class="text-sm font-medium text-neutral-600 dark:text-neutral-400 mb-3">
              Events for this day ({{ selectedDay.events.length }})
            </h4>
            <div class="space-y-2">
              <div
                v-for="event in selectedDay.events"
                :key="event.id"
                :style="{ backgroundColor: event.backgroundColor }"
                class="p-3 rounded-lg text-white cursor-pointer hover:opacity-90 transition-opacity"
                @click="openEventModal(event); dayModalRef?.close()"
              >
                <div class="font-medium">{{ event.title }}</div>
                <div class="text-xs opacity-75 mt-1">
                  {{ event.extendedProps.type === 'leave' ? 'Leave Request' : 'Holiday' }}
                  <span v-if="event.extendedProps.duration">
                    • {{ event.extendedProps.duration }} day{{ event.extendedProps.duration > 1 ? 's' : '' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <CalendarDays class="h-12 w-12 mx-auto text-neutral-400 dark:text-neutral-600 mb-3" />
            <p class="text-neutral-600 dark:text-neutral-400">No events scheduled for this day</p>
          </div>
        </div>
      </div>
    </Modal>
  </TenantLayout>
</template>

