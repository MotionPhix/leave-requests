<script setup lang="ts">
import type { DateRange } from 'reka-ui'
import {
  DateFormatter,
  getLocalTimeZone,
  CalendarDate,
  parseDate
} from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { RangeCalendar } from '@/components/ui/range-calendar'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { computed, watch } from 'vue'

interface Props {
  label?: string;
  placeholder?: string;
  disabled?: boolean;
  required?: boolean;
  id?: string;
  error?: string;
  class?: string;
}

// For returning HTML-compatible date strings
interface DateRangeStrings {
  start: string;
  end: string;
}

const props = defineProps<Props>();

// v-model for { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' } format
const value = defineModel<DateRangeStrings | null>({ required: true });

const df = new DateFormatter('en-US', { 
  dateStyle: 'medium' 
});

// Internal DateRange for the calendar component
const internalValue = computed({
  get: (): DateRange | undefined => {
    if (!value.value?.start || !value.value?.end) return undefined;

    try {
      const start = parseDate(value.value.start);
      const end = parseDate(value.value.end);
      return { start, end };
    } catch (error) {
      console.error('Error parsing date range:', error);
      return undefined;
    }
  },
  set: (newValue: DateRange | undefined) => {
    if (!newValue?.start || !newValue?.end) {
      value.value = null;
      return;
    }

    try {
      // Convert DateRange to string format
      const startDate = newValue.start instanceof CalendarDate
        ? `${newValue.start.year}-${String(newValue.start.month).padStart(2, '0')}-${String(newValue.start.day).padStart(2, '0')}`
        : newValue.start.toDate(getLocalTimeZone()).toISOString().split('T')[0];

      const endDate = newValue.end instanceof CalendarDate
        ? `${newValue.end.year}-${String(newValue.end.month).padStart(2, '0')}-${String(newValue.end.day).padStart(2, '0')}`
        : newValue.end.toDate(getLocalTimeZone()).toISOString().split('T')[0];

      value.value = {
        start: startDate,
        end: endDate
      };
    } catch (error) {
      console.error('Error converting date range:', error);
      value.value = null;
    }
  }
});

// Display formatted date range
const displayRange = computed(() => {
  if (!value.value?.start || !value.value?.end) return '';

  try {
    const startDate = new Date(value.value.start + 'T00:00:00');
    const endDate = new Date(value.value.end + 'T00:00:00');

    if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) return '';

    return `${df.format(startDate)} - ${df.format(endDate)}`;
  } catch {
    return '';
  }
});

// Computed to show if we have a partial selection (only start date)
const hasPartialSelection = computed(() => {
  return internalValue.value?.start && !internalValue.value?.end;
});
</script>

<template>
  <div :class="cn('space-y-2', props.class)">
    <!-- Label -->
    <Label v-if="label" :for="id">
      {{ label }}
      <span v-if="required" class="text-destructive">*</span>
    </Label>
    
    <!-- Date Range Picker -->
    <Popover>
      <PopoverTrigger as-child>
        <Button
          :id="id"
          variant="outline"
          :disabled="disabled"
          :class="cn(
            'w-full justify-start text-left font-normal',
            !value?.start && 'text-muted-foreground',
            error && 'border-destructive focus-visible:ring-destructive'
          )"
        >
          <CalendarIcon class="mr-2 h-4 w-4" />
          
          <template v-if="displayRange">
            {{ displayRange }}
          </template>
          <template v-else-if="hasPartialSelection">
            {{ df.format(new Date(value?.start + 'T00:00:00')) }} - ...
          </template>
          <template v-else>
            {{ placeholder || 'Pick a date range' }}
          </template>
        </Button>
      </PopoverTrigger>

      <PopoverContent class="w-auto p-0">
        <RangeCalendar
          v-model="internalValue"
          :disabled="disabled"
          initial-focus
          :number-of-months="2"
        />
      </PopoverContent>
    </Popover>
    
    <!-- Error Message -->
    <InputError :message="error" />
  </div>
</template>