<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/layouts/TenantLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { CalendarDays, Plus, Search, Filter, MapPin, Clock, Users, Eye } from 'lucide-vue-next'

interface Props {
  workspace: {
    id: number
    uuid: string
    name: string
    slug: string
  }
  events: {
    data: Array<{
      id: number
      uuid: string
      title: string
      description?: string
      type: string
      location?: string
      color: string
      start_date: string
      end_date?: string
      start_time?: string
      end_time?: string
      all_day: boolean
      is_mandatory: boolean
      created_at: string
      creator: {
        id: number
        name: string
        email: string
      }
    }>
    links: any
    meta: any
  }
  filters: {
    type?: string
    start_date?: string
    end_date?: string
  }
}

const props = defineProps<Props>()

// Local state
const searchTerm = ref('')
const filterType = ref(props.filters.type || '')
const startDateFilter = ref(props.filters.start_date || '')
const endDateFilter = ref(props.filters.end_date || '')

// Computed properties
const tenantParams = computed(() => ({
  tenant_slug: props.workspace.slug,
  tenant_uuid: props.workspace.uuid
}))

const eventTypes = [
  { value: '', label: 'All Types' },
  { value: 'meeting', label: 'Meeting' },
  { value: 'announcement', label: 'Announcement' },
  { value: 'training', label: 'Training' },
  { value: 'social', label: 'Social Event' },
  { value: 'other', label: 'Other' }
]

const filteredEvents = computed(() => {
  let filtered = props.events.data

  if (searchTerm.value) {
    const search = searchTerm.value.toLowerCase()
    filtered = filtered.filter(event => 
      event.title.toLowerCase().includes(search) ||
      event.description?.toLowerCase().includes(search) ||
      event.location?.toLowerCase().includes(search)
    )
  }

  return filtered
})

// Methods
function createEvent() {
  router.visit(route('tenant.management.events.create', tenantParams.value))
}

function viewEvent(event: any) {
  router.visit(route('tenant.management.events.show', {
    ...tenantParams.value,
    event: event.uuid
  }))
}

function applyFilters() {
  const params = {
    ...tenantParams.value,
    ...(filterType.value && { type: filterType.value }),
    ...(startDateFilter.value && { start_date: startDateFilter.value }),
    ...(endDateFilter.value && { end_date: endDateFilter.value })
  }
  
  router.visit(route('tenant.management.events.index', params))
}

function clearFilters() {
  filterType.value = ''
  startDateFilter.value = ''
  endDateFilter.value = ''
  router.visit(route('tenant.management.events.index', tenantParams.value))
}

function getEventTypeLabel(type: string): string {
  const typeMap: { [key: string]: string } = {
    meeting: 'Meeting',
    announcement: 'Announcement', 
    training: 'Training',
    social: 'Social Event',
    other: 'Other'
  }
  return typeMap[type] || 'Unknown'
}

function formatDate(date: string): string {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatTime(startTime?: string, endTime?: string, allDay = false): string {
  if (allDay) return 'All Day'
  
  if (!startTime && !endTime) return 'Time not set'
  
  const formatTimeString = (time: string) => {
    return new Date(`2000-01-01 ${time}`).toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    })
  }
  
  if (startTime && endTime) {
    return `${formatTimeString(startTime)} - ${formatTimeString(endTime)}`
  }
  
  if (startTime) return `From ${formatTimeString(startTime)}`
  return 'Time not set'
}

function goToCalendar() {
  router.visit(route('tenant.management.calendar.index', tenantParams.value))
}
</script>

<template>
  <TenantLayout>
    <Head :title="`Events - ${workspace.name}`" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Events</h1>
          <p class="text-muted-foreground">
            Manage team meetings, announcements, and company events
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" @click="goToCalendar">
            <CalendarDays class="h-4 w-4 mr-2" />
            Calendar View
          </Button>
          <Button @click="createEvent">
            <Plus class="h-4 w-4 mr-2" />
            Create Event
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-lg">
            <Filter class="h-5 w-5" />
            Filters & Search
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
            <!-- Search -->
            <div class="lg:col-span-2">
              <div class="relative">
                <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                <Input
                  v-model="searchTerm"
                  placeholder="Search events..."
                  class="pl-9"
                />
              </div>
            </div>

            <!-- Type Filter -->
            <div>
              <Select v-model="filterType">
                <SelectTrigger>
                  <SelectValue placeholder="Event Type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="type in eventTypes" :key="type.value" :value="type.value">
                    {{ type.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Date Range -->
            <div class="grid grid-cols-2 gap-2">
              <Input
                v-model="startDateFilter"
                type="date"
                placeholder="Start Date"
              />
              <Input
                v-model="endDateFilter"
                type="date"
                placeholder="End Date"
                :min="startDateFilter"
              />
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <Button variant="outline" size="sm" @click="applyFilters" class="flex-1">
                Apply
              </Button>
              <Button variant="outline" size="sm" @click="clearFilters" class="flex-1">
                Clear
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Events List -->
      <div class="space-y-4">
        <div v-if="filteredEvents.length === 0" class="text-center py-12">
          <CalendarDays class="h-16 w-16 mx-auto text-muted-foreground mb-4" />
          <h3 class="text-xl font-medium mb-2">No events found</h3>
          <p class="text-muted-foreground mb-4">
            {{ searchTerm || filterType || startDateFilter || endDateFilter 
               ? 'Try adjusting your filters or search terms.' 
               : 'Get started by creating your first event.' }}
          </p>
          <Button @click="createEvent">
            <Plus class="h-4 w-4 mr-2" />
            Create Event
          </Button>
        </div>

        <div v-else class="grid gap-4">
          <Card 
            v-for="event in filteredEvents" 
            :key="event.id"
            class="transition-all hover:shadow-md cursor-pointer"
            @click="viewEvent(event)"
          >
            <CardContent class="p-6">
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <!-- Title and Type -->
                  <div class="flex items-center gap-3 mb-2">
                    <div 
                      class="w-4 h-4 rounded-full flex-shrink-0" 
                      :style="{ backgroundColor: event.color }"
                    ></div>
                    <h3 class="font-semibold text-lg truncate">{{ event.title }}</h3>
                    <Badge variant="secondary" class="flex-shrink-0">
                      {{ getEventTypeLabel(event.type) }}
                    </Badge>
                    <Badge v-if="event.is_mandatory" variant="destructive" class="text-xs flex-shrink-0">
                      Required
                    </Badge>
                  </div>

                  <!-- Description -->
                  <p v-if="event.description" class="text-muted-foreground mb-3 line-clamp-2">
                    {{ event.description }}
                  </p>

                  <!-- Event Details -->
                  <div class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                    <!-- Date -->
                    <div class="flex items-center gap-1">
                      <CalendarDays class="h-4 w-4" />
                      <span>{{ formatDate(event.start_date) }}</span>
                      <span v-if="event.end_date && event.end_date !== event.start_date">
                        - {{ formatDate(event.end_date) }}
                      </span>
                    </div>

                    <!-- Time -->
                    <div class="flex items-center gap-1">
                      <Clock class="h-4 w-4" />
                      <span>{{ formatTime(event.start_time, event.end_time, event.all_day) }}</span>
                    </div>

                    <!-- Location -->
                    <div v-if="event.location" class="flex items-center gap-1">
                      <MapPin class="h-4 w-4" />
                      <span class="truncate">{{ event.location }}</span>
                    </div>

                    <!-- Creator -->
                    <div class="flex items-center gap-1">
                      <Users class="h-4 w-4" />
                      <span>{{ event.creator.name }}</span>
                    </div>
                  </div>
                </div>

                <!-- Action Button -->
                <div class="ml-4 flex-shrink-0">
                  <Button variant="ghost" size="sm" @click.stop="viewEvent(event)">
                    <Eye class="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Pagination -->
        <div v-if="events.links && events.links.length > 3" class="flex justify-center">
          <div class="flex items-center gap-2">
            <template v-for="link in events.links" :key="link.label">
              <Link
                v-if="link.url"
                :href="link.url"
                class="px-3 py-2 text-sm rounded-md transition-colors"
                :class="[
                  link.active 
                    ? 'bg-primary text-primary-foreground' 
                    : 'bg-background text-muted-foreground hover:bg-muted'
                ]"
                v-html="link.label"
              />
              <span 
                v-else
                class="px-3 py-2 text-sm rounded-md text-muted-foreground/50"
                v-html="link.label"
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>