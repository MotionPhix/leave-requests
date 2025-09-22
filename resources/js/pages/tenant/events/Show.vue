<script setup lang="ts">
import { computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import TenantLayout from '@/layouts/TenantLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { CalendarDays, Clock, MapPin, Users, Palette, Edit, Trash2, Calendar } from 'lucide-vue-next'

interface Props {
  workspace: {
    id: number
    uuid: string
    name: string
    slug: string
  }
  event: {
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
    attendees: any[]
    created_by: number
    created_at: string
    updated_at: string
    creator: {
      id: number
      name: string
      email: string
    }
  }
  canEdit: boolean
  canDelete: boolean
  isEmployee: boolean
}

const props = defineProps<Props>()

// Computed properties
const tenantParams = computed(() => ({
  tenant_slug: props.workspace.slug,
  tenant_uuid: props.workspace.uuid
}))

const eventTypeLabel = computed(() => {
  const typeMap: { [key: string]: string } = {
    meeting: 'Meeting',
    announcement: 'Announcement',
    training: 'Training',
    social: 'Social Event',
    other: 'Other'
  }
  return typeMap[props.event.type] || 'Unknown'
})

const formattedStartDate = computed(() => {
  return new Date(props.event.start_date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

const formattedEndDate = computed(() => {
  if (!props.event.end_date || props.event.end_date === props.event.start_date) return null
  return new Date(props.event.end_date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

const formattedTime = computed(() => {
  if (props.event.all_day) return 'All Day'
  
  const startTime = props.event.start_time ? 
    new Date(`2000-01-01 ${props.event.start_time}`).toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    }) : ''
    
  const endTime = props.event.end_time ? 
    new Date(`2000-01-01 ${props.event.end_time}`).toLocaleTimeString('en-US', {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    }) : ''
    
  if (startTime && endTime) return `${startTime} - ${endTime}`
  if (startTime) return `Starts at ${startTime}`
  return 'Time not specified'
})

const createdDate = computed(() => {
  return new Date(props.event.created_at).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

// Methods
function goBack() {
  router.visit(route('tenant.management.calendar.index', tenantParams.value))
}

function editEvent() {
  router.visit(route('tenant.management.events.edit', {
    ...tenantParams.value,
    event: props.event.uuid
  }))
}

function deleteEvent() {
  if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
    router.delete(route('tenant.management.events.destroy', {
      ...tenantParams.value,
      event: props.event.uuid
    }))
  }
}

function goToEventsList() {
  router.visit(route('tenant.management.events.index', tenantParams.value))
}
</script>

<template>
  <TenantLayout>
    <Head :title="`${event.title} - ${workspace.name}`" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <div 
              class="w-4 h-4 rounded-full" 
              :style="{ backgroundColor: event.color }"
            ></div>
            <h1 class="text-3xl font-bold tracking-tight">{{ event.title }}</h1>
            <Badge v-if="event.is_mandatory" variant="destructive" class="text-xs">
              Required
            </Badge>
          </div>
          <p class="text-muted-foreground">
            {{ eventTypeLabel }} • Created by {{ event.creator.name }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" @click="goBack">
            <Calendar class="h-4 w-4 mr-2" />
            Back to Calendar
          </Button>
          <Button v-if="canEdit" variant="outline" @click="goToEventsList">
            All Events
          </Button>
          <Button v-if="canEdit" variant="outline" @click="editEvent">
            <Edit class="h-4 w-4 mr-2" />
            Edit
          </Button>
          <Button v-if="canDelete" variant="destructive" @click="deleteEvent">
            <Trash2 class="h-4 w-4 mr-2" />
            Delete
          </Button>
        </div>
      </div>

      <div class="grid gap-6 md:grid-cols-3">
        <!-- Main Content -->
        <div class="md:col-span-2 space-y-6">
          <!-- Event Details -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <CalendarDays class="h-5 w-5" />
                Event Details
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div v-if="event.description" class="space-y-2">
                <h3 class="font-medium">Description</h3>
                <p class="text-muted-foreground whitespace-pre-wrap">{{ event.description }}</p>
              </div>
              
              <div v-if="event.location" class="space-y-2">
                <h3 class="font-medium flex items-center gap-2">
                  <MapPin class="h-4 w-4" />
                  Location
                </h3>
                <p class="text-muted-foreground">{{ event.location }}</p>
              </div>

              <div class="space-y-2">
                <h3 class="font-medium">Event Type</h3>
                <div class="flex items-center gap-2">
                  <div 
                    class="w-3 h-3 rounded-full" 
                    :style="{ backgroundColor: event.color }"
                  ></div>
                  <span class="text-muted-foreground">{{ eventTypeLabel }}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Additional Information (only for management) -->
          <Card v-if="!isEmployee">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Users class="h-5 w-5" />
                Event Management
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <h3 class="font-medium">Attendance</h3>
                <div class="flex items-center gap-2">
                  <Badge :variant="event.is_mandatory ? 'destructive' : 'secondary'">
                    {{ event.is_mandatory ? 'Mandatory' : 'Optional' }}
                  </Badge>
                  <span class="text-sm text-muted-foreground">
                    {{ event.is_mandatory ? 'All team members are required to attend' : 'Team members can choose to attend' }}
                  </span>
                </div>
              </div>

              <div v-if="event.attendees && event.attendees.length > 0" class="space-y-2">
                <h3 class="font-medium">Attendees ({{ event.attendees.length }})</h3>
                <div class="text-sm text-muted-foreground">
                  Specific attendee list functionality can be implemented based on requirements
                </div>
              </div>

              <div class="space-y-2">
                <h3 class="font-medium">Created</h3>
                <p class="text-sm text-muted-foreground">
                  {{ createdDate }} by {{ event.creator.name }} ({{ event.creator.email }})
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Date & Time -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Clock class="h-5 w-5" />
                Date & Time
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div>
                <h4 class="font-medium text-sm">Start Date</h4>
                <p class="text-muted-foreground">{{ formattedStartDate }}</p>
              </div>
              
              <div v-if="formattedEndDate">
                <h4 class="font-medium text-sm">End Date</h4>
                <p class="text-muted-foreground">{{ formattedEndDate }}</p>
              </div>
              
              <div>
                <h4 class="font-medium text-sm">Time</h4>
                <p class="text-muted-foreground">{{ formattedTime }}</p>
              </div>
            </CardContent>
          </Card>

          <!-- Display Settings -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Palette class="h-5 w-5" />
                Display
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div>
                  <h4 class="font-medium text-sm">Color</h4>
                  <div class="flex items-center gap-2 mt-1">
                    <div 
                      class="w-6 h-6 rounded border border-border" 
                      :style="{ backgroundColor: event.color }"
                    ></div>
                    <span class="text-sm text-muted-foreground font-mono">{{ event.color }}</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Quick Actions (for employees) -->
          <Card v-if="isEmployee">
            <CardHeader>
              <CardTitle>Quick Actions</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-2">
                <Button variant="outline" size="sm" class="w-full" @click="goBack">
                  <Calendar class="h-4 w-4 mr-2" />
                  Back to Calendar
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>