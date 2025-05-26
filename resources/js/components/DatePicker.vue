<script setup lang="ts">
import {
  DateFormatter,
  type DateValue,
  getLocalTimeZone, parseDate
} from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next'
import { cn, formatDate } from '@/lib/utils';
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { computed } from 'vue';

defineProps<{
  placeholder?: string
}>()

const value = defineModel<DateValue>()

const df = new DateFormatter('en-MW', {
  dateStyle: 'long',
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
        {{ value ? df.format(parseDate(value.toString()).toDate(getLocalTimeZone())) : placeholder ?? "Pick a date" }}
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0">
      <Calendar v-model="value" initial-focus />
    </PopoverContent>
  </Popover>
</template>
