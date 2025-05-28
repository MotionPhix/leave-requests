<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardHeader, CardContent, CardFooter } from '@/components/ui/card'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select'
import DateRangePicker from '@/components/DateRangePicker.vue'
import { toast } from 'vue-sonner'
import { formatDate } from '@/lib/utils'

const props = defineProps({ leaveTypes: Array })
const form = useForm({
  leave_type_id: '',
  date_range: { start: null, end: null },
  reason: ''
})

const selectedLeaveType = computed(() =>
  props.leaveTypes.find(type => type.id === form.leave_type_id)
)

const estimatedDays = computed(() => {
  if (form.date_range.start && form.date_range.end) {
    const start = new Date(form.date_range.start)
    const end = new Date(form.date_range.end)
    let days = 0
    while (start <= end) {
      if (start.getDay() !== 0 && start.getDay() !== 6) days++
      start.setDate(start.getDate() + 1)
    }
    return days
  }
  return 0
})

watch(() => form.leave_type_id, (newId) => {
  // Optionally fetch and show remaining balance
})

function submit() {
  form.processing = true
  form.transform(data => ({
    leave_type_id: data.leave_type_id,
    start_date: formatDate(data.date_range.start),
    end_date: formatDate(data.date_range.end),
    reason: data.reason
  })).post(route('leave-requests.store'), {
    onSuccess: () => {
      toast.success('Leave request submitted!')
      router.visit(route('leave-requests.index'))
    },
    onError: (errors) => {
      toast.error('Failed to submit leave request')
    },
    onFinish: () => form.processing = false
  })
}
</script>

<template>
  <Head title="Request Leave" />
  <AppLayout>
    <div class="max-w-2xl mx-auto p-4 space-y-6">
      <h1 class="text-2xl font-bold">New Leave Request</h1>

      <Card>
        <CardHeader>
          <h2 class="font-semibold text-lg">Leave Details</h2>
        </CardHeader>

        <CardContent class="space-y-4">
          <Select v-model="form.leave_type_id">
            <SelectTrigger>
              <SelectValue placeholder="Select Leave Type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="type in props.leaveTypes" :key="type.id" :value="type.id">
                {{ type.name }} (Max: {{ type.max_days_per_year }} days)
              </SelectItem>
            </SelectContent>
          </Select>
          <span v-if="selectedLeaveType">Max allowed: {{ selectedLeaveType.max_days_per_year }} days</span>

          <DateRangePicker v-model="form.date_range" placeholder="Select Date Range" />

          <p v-if="estimatedDays">Estimated working days: {{ estimatedDays }}</p>

          <Textarea v-model="form.reason" placeholder="Reason for leave" class="w-full" />

          <div v-if="form.errors.insufficient" class="text-red-500">{{ form.errors.insufficient }}</div>
        </CardContent>

        <CardFooter>
          <Button :disabled="form.processing" @click="submit">
            <span v-if="form.processing">Submitting...</span>
            <span v-else>Submit Request</span>
          </Button>
        </CardFooter>
      </Card>
    </div>
  </AppLayout>
</template>
