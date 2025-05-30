<script setup lang="ts">
import {
  DateFormatter,
  getLocalTimeZone,
  CalendarDate,
  DateValue,
  parseDate,
  today
} from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { computed } from 'vue'

defineProps<{
  placeholder?: string
}>()

const value = defineModel<DateValue | null>({ required: true })

const df = new DateFormatter('en-MW', {
  dateStyle: 'medium',
})

const displayDate = computed(() => {
  if (!value.value) return ''

  try {
    const date = value.value instanceof CalendarDate
      ? value.value.toDate(getLocalTimeZone())
      : new Date(value.value)

    if (isNaN(date.getTime())) return ''

    return df.format(date)
  } catch {
    return ''
  }
})

// Convert Date object back to CalendarDate when needed
const calendarValue = computed({
  get: () => value.value ? parseDate(value.value.toString()) : null,
  set: (newValue) => {
    value.value = newValue
  }
})
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        variant="outline"
        :class="cn(
          'w-full justify-start text-left font-normal',
          !value && 'text-muted-foreground',
        )">
        <CalendarIcon class="mr-2 h-4 w-4" />
        {{ displayDate || placeholder || "Pick a date" }}
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-auto p-0">
      <Calendar
        v-model="calendarValue"
        :default-value="calendarValue || today(getLocalTimeZone())"
        initial-focus
      />
    </PopoverContent>
  </Popover>
</template>
