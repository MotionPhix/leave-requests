<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/layouts/TenantLayout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { Badge } from '@/components/ui/badge'
import { CalendarDays, Clock, MapPin, Users, Palette } from 'lucide-vue-next'

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
  }
  eventTypes: Array<{
    value: string
    label: string
    color: string
  }>
}

const props = defineProps<Props>()

// Form setup
const form = useForm({
  title: props.event.title,
  description: props.event.description || '',
  type: props.event.type,
  location: props.event.location || '',
  color: props.event.color,
  start_date: props.event.start_date,
  end_date: props.event.end_date || '',
  start_time: props.event.start_time || '',
  end_time: props.event.end_time || '',
  all_day: props.event.all_day,
  attendees: props.event.attendees || [],
  is_mandatory: props.event.is_mandatory,
})

// Computed properties
const selectedEventType = computed(() => {
  return props.eventTypes.find(type => type.value === form.type) || props.eventTypes[0]
})

const tenantParams = computed(() => ({
  tenant_slug: props.workspace.slug,
  tenant_uuid: props.workspace.uuid
}))

// Methods
function submit() {
  form.put(route('tenant.management.events.update', {
    ...tenantParams.value,
    event: props.event.uuid
  }))
}

function goBack() {
  router.visit(route('tenant.management.events.show', {
    ...tenantParams.value,
    event: props.event.uuid
  }))
}

function goToCalendar() {
  router.visit(route('tenant.management.calendar.index', tenantParams.value))
}

// Watch for type changes to update color
function onTypeChange(value: string) {
  form.type = value
  const eventType = props.eventTypes.find(type => type.value === value)
  if (eventType) {
    form.color = eventType.color
  }
}

// Watch for all_day changes
function onAllDayChange(value: boolean) {
  form.all_day = value
  if (value) {
    form.start_time = ''
    form.end_time = ''
  }
}
</script>

<template>
  <TenantLayout>
    <Head :title="`Edit ${event.title} - ${workspace.name}`" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Edit Event</h1>
          <p class="text-muted-foreground">
            Update event details and settings
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Button variant="outline" @click="goToCalendar">
            Back to Calendar
          </Button>
          <Button variant="outline" @click="goBack">
            View Event
          </Button>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-8">
        <div class="grid gap-8 md:grid-cols-2">
          <!-- Left Column -->
          <div class="space-y-6">
            <!-- Basic Information -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <CalendarDays class="h-5 w-5" />
                  Event Details
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <!-- Title -->
                <div class="space-y-2">
                  <Label for="title" class="required">Event Title</Label>
                  <Input
                    id="title"
                    v-model="form.title"
                    placeholder="Enter event title"
                    :class="{ 'border-red-500': form.errors.title }"
                    required
                  />
                  <div v-if="form.errors.title" class="text-sm text-red-600">
                    {{ form.errors.title }}
                  </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                  <Label for="description">Description</Label>
                  <Textarea
                    id="description"
                    v-model="form.description"
                    placeholder="Describe the event details..."
                    rows="3"
                    :class="{ 'border-red-500': form.errors.description }"
                  />
                  <div v-if="form.errors.description" class="text-sm text-red-600">
                    {{ form.errors.description }}
                  </div>
                </div>

                <!-- Event Type -->
                <div class="space-y-2">
                  <Label for="type" class="required">Event Type</Label>
                  <Select :model-value="form.type" @update:model-value="onTypeChange">
                    <SelectTrigger :class="{ 'border-red-500': form.errors.type }">
                      <SelectValue placeholder="Select event type">
                        <div v-if="selectedEventType" class="flex items-center gap-2">
                          <div 
                            class="w-3 h-3 rounded-full" 
                            :style="{ backgroundColor: selectedEventType.color }"
                          ></div>
                          {{ selectedEventType.label }}
                        </div>
                      </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="type in eventTypes" :key="type.value" :value="type.value">
                        <div class="flex items-center gap-2">
                          <div 
                            class="w-3 h-3 rounded-full" 
                            :style="{ backgroundColor: type.color }"
                          ></div>
                          {{ type.label }}
                        </div>
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <div v-if="form.errors.type" class="text-sm text-red-600">
                    {{ form.errors.type }}
                  </div>
                </div>

                <!-- Location -->
                <div class="space-y-2">
                  <Label for="location" class="flex items-center gap-1">
                    <MapPin class="h-4 w-4" />
                    Location
                  </Label>
                  <Input
                    id="location"
                    v-model="form.location"
                    placeholder="Meeting room, office, or virtual link"
                    :class="{ 'border-red-500': form.errors.location }"
                  />
                  <div v-if="form.errors.location" class="text-sm text-red-600">
                    {{ form.errors.location }}
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Settings -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Users class="h-5 w-5" />
                  Event Settings
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <!-- Mandatory Event -->
                <div class="flex items-center justify-between">
                  <div class="space-y-0.5">
                    <Label>Mandatory Attendance</Label>
                    <p class="text-sm text-muted-foreground">
                      Mark if attendance is required for all team members
                    </p>
                  </div>
                  <Switch
                    :checked="form.is_mandatory"
                    @update:checked="form.is_mandatory = $event"
                  />
                </div>

                <!-- Color Picker -->
                <div class="space-y-2">
                  <Label for="color" class="flex items-center gap-1">
                    <Palette class="h-4 w-4" />
                    Display Color
                  </Label>
                  <div class="flex items-center gap-2">
                    <input
                      id="color"
                      v-model="form.color"
                      type="color"
                      class="w-12 h-8 rounded border border-input cursor-pointer"
                    />
                    <Input
                      v-model="form.color"
                      placeholder="#3b82f6"
                      pattern="^#[a-fA-F0-9]{6}$"
                      class="flex-1"
                    />
                  </div>
                  <div v-if="form.errors.color" class="text-sm text-red-600">
                    {{ form.errors.color }}
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Right Column -->
          <div class="space-y-6">
            <!-- Date & Time -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <Clock class="h-5 w-5" />
                  Date & Time
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <!-- All Day Toggle -->
                <div class="flex items-center justify-between">
                  <div class="space-y-0.5">
                    <Label>All Day Event</Label>
                    <p class="text-sm text-muted-foreground">
                      Event spans the entire day without specific times
                    </p>
                  </div>
                  <Switch
                    :checked="form.all_day"
                    @update:checked="onAllDayChange"
                  />
                </div>

                <!-- Start Date -->
                <div class="space-y-2">
                  <Label for="start_date" class="required">Start Date</Label>
                  <Input
                    id="start_date"
                    v-model="form.start_date"
                    type="date"
                    :class="{ 'border-red-500': form.errors.start_date }"
                    required
                  />
                  <div v-if="form.errors.start_date" class="text-sm text-red-600">
                    {{ form.errors.start_date }}
                  </div>
                </div>

                <!-- End Date (for multi-day events) -->
                <div class="space-y-2">
                  <Label for="end_date">End Date (Optional)</Label>
                  <Input
                    id="end_date"
                    v-model="form.end_date"
                    type="date"
                    :class="{ 'border-red-500': form.errors.end_date }"
                    :min="form.start_date"
                  />
                  <p class="text-xs text-muted-foreground">
                    Leave empty for single-day events
                  </p>
                  <div v-if="form.errors.end_date" class="text-sm text-red-600">
                    {{ form.errors.end_date }}
                  </div>
                </div>

                <!-- Time Fields (only when not all day) -->
                <div v-if="!form.all_day" class="grid grid-cols-2 gap-4">
                  <!-- Start Time -->
                  <div class="space-y-2">
                    <Label for="start_time">Start Time</Label>
                    <Input
                      id="start_time"
                      v-model="form.start_time"
                      type="time"
                      :class="{ 'border-red-500': form.errors.start_time }"
                    />
                    <div v-if="form.errors.start_time" class="text-sm text-red-600">
                      {{ form.errors.start_time }}
                    </div>
                  </div>

                  <!-- End Time -->
                  <div class="space-y-2">
                    <Label for="end_time">End Time</Label>
                    <Input
                      id="end_time"
                      v-model="form.end_time"
                      type="time"
                      :class="{ 'border-red-500': form.errors.end_time }"
                    />
                    <div v-if="form.errors.end_time" class="text-sm text-red-600">
                      {{ form.errors.end_time }}
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Preview -->
            <Card>
              <CardHeader>
                <CardTitle>Event Preview</CardTitle>
              </CardHeader>
              <CardContent>
                <div class="space-y-3">
                  <div class="flex items-center gap-2">
                    <div 
                      class="w-4 h-4 rounded-full" 
                      :style="{ backgroundColor: form.color }"
                    ></div>
                    <span class="font-medium">{{ form.title || 'Event Title' }}</span>
                  </div>
                  
                  <div class="text-sm text-muted-foreground">
                    {{ selectedEventType?.label }}
                    <span v-if="form.is_mandatory" class="ml-2">
                      <Badge variant="destructive" class="text-xs">Required</Badge>
                    </span>
                  </div>

                  <div v-if="form.location" class="flex items-center gap-1 text-sm text-muted-foreground">
                    <MapPin class="h-3 w-3" />
                    {{ form.location }}
                  </div>

                  <div class="text-sm">
                    <strong>{{ form.start_date || 'Select date' }}</strong>
                    <span v-if="form.end_date && form.end_date !== form.start_date">
                      → {{ form.end_date }}
                    </span>
                    <br v-if="!form.all_day && (form.start_time || form.end_time)" />
                    <span v-if="!form.all_day" class="text-muted-foreground">
                      {{ form.start_time || '--:--' }} - {{ form.end_time || '--:--' }}
                    </span>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4 pt-6 border-t">
          <Button type="button" variant="outline" @click="goBack">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            <span v-if="form.processing">Updating Event...</span>
            <span v-else>Update Event</span>
          </Button>
        </div>
      </form>
    </div>
  </TenantLayout>
</template>

<style scoped>
.required::after {
  content: " *";
  color: rgb(239 68 68);
}
</style>