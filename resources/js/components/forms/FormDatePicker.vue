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
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { computed } from 'vue'

interface Props {
  label?: string;
  placeholder?: string;
  disabled?: boolean;
  required?: boolean;
  min?: string;
  max?: string;
  id?: string;
  error?: string;
  class?: string;
}

const props = defineProps<Props>();

// v-model for YYYY-MM-DD format (HTML date input compatible)
const value = defineModel<string>({ required: true });

const df = new DateFormatter('en-US', {
  dateStyle: 'medium',
});

// Convert string to DateValue for internal use
const internalValue = computed({
  get: (): DateValue | null => {
    if (!value.value) return null;

    try {
      // Parse YYYY-MM-DD string to CalendarDate
      return parseDate(value.value);
    } catch (error) {
      console.error('Error parsing date:', error);
      return null;
    }
  },
  set: (newValue: DateValue | null) => {
    if (!newValue) {
      value.value = '';
      return;
    }

    try {
      // Convert DateValue to YYYY-MM-DD string (HTML date input format)
      if (newValue instanceof CalendarDate) {
        const year = newValue.year;
        const month = String(newValue.month).padStart(2, '0');
        const day = String(newValue.day).padStart(2, '0');
        value.value = `${year}-${month}-${day}`;
      } else {
        // Fallback for other DateValue types
        const date = newValue.toDate ? newValue.toDate(getLocalTimeZone()) : new Date(newValue);
        value.value = date.toISOString().split('T')[0];
      }
    } catch (error) {
      console.error('Error converting date:', error);
      value.value = '';
    }
  }
});

const displayDate = computed(() => {
  if (!internalValue.value) return '';

  try {
    const date = internalValue.value instanceof CalendarDate
      ? internalValue.value.toDate(getLocalTimeZone())
      : new Date(internalValue.value);

    if (isNaN(date.getTime())) return '';

    return df.format(date);
  } catch {
    return '';
  }
});

// Convert min/max props to DateValue for calendar component
const minDate = computed(() => {
  if (!props.min) return undefined;

  try {
    return parseDate(props.min);
  } catch (error) {
    console.error('Error parsing min date:', error);
    return undefined;
  }
});

const maxDate = computed(() => {
  if (!props.max) return undefined;

  try {
    return parseDate(props.max);
  } catch (error) {
    console.error('Error parsing max date:', error);
    return undefined;
  }
});

// Default value for calendar when no value is selected
const defaultCalendarValue = computed(() => {
  return internalValue.value || today(getLocalTimeZone());
});
</script>

<template>
  <div :class="cn('space-y-2', props.class)">
    <!-- Label -->
    <Label v-if="label" :for="id">
      {{ label }}
      <span v-if="required" class="text-destructive">*</span>
    </Label>
    
    <!-- Date Picker -->
    <Popover>
      <PopoverTrigger as-child>
        <Button
          :id="id"
          variant="outline"
          :disabled="disabled"
          :class="cn(
            'w-full justify-start text-left font-normal',
            !value && 'text-muted-foreground',
            error && 'border-destructive focus-visible:ring-destructive'
          )"
        >
          <CalendarIcon class="mr-2 h-4 w-4" />
          {{ displayDate || placeholder || "Pick a date" }}
        </Button>
      </PopoverTrigger>

      <PopoverContent class="w-auto p-0">
        <Calendar
          v-model="internalValue"
          :default-value="defaultCalendarValue"
          :min-value="minDate"
          :max-value="maxDate"
          :disabled="disabled"
          initial-focus
        />
      </PopoverContent>
    </Popover>
    
    <!-- Error Message -->
    <InputError :message="error" />
  </div>
</template>