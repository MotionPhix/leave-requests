<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardHeader, CardDescription, CardTitle, CardContent, CardFooter } from '@/components/ui/card'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select'
import DateRangePicker from '@/components/DateRangePicker.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { toast } from 'vue-sonner'
import { formatDate } from '@/lib/utils'
import InputError from '@/components/InputError.vue'

const props = defineProps<{
  leaveTypes: Array<{
    id: number;
    name: string;
    max_days_per_year: number;
  }>
}>()

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'New Leave Request',
    href: '/leave-requests/create'
  }
];

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

  <Head title="New Leave Request" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="max-w-2xl p-6">
      <Card>
        <CardHeader>
          <CardTitle>Leave Details</CardTitle>

          <CardDescription>
            Submit a new leave request for approval
          </CardDescription>
        </CardHeader>

        <CardContent class="space-y-6">

          <div>
            <!-- Error Messages -->
            <Alert
              v-if="form.errors.insufficient || form.errors.overlap"
              variant="destructive">

              <AlertDescription>
                {{ form.errors.insufficient || form.errors.overlap }}
              </AlertDescription>
            </Alert>
          </div>

          <div>
            <Label class="mb-2">Leave Type</Label>

            <Select v-model="form.leave_type_id">
              <SelectTrigger class="w-full">
                <SelectValue placeholder="Select Leave Type" />
              </SelectTrigger>

              <SelectContent>
                <SelectItem v-for="type in props.leaveTypes" :key="type.id" :value="type.id">
                  {{ type.name }} ({{ type.max_days_per_year }} days/year)
                </SelectItem>
              </SelectContent>
            </Select>

            <span v-if="selectedLeaveType" class="text-sm text-gray-500 mt-1">
              Max allowed: {{ selectedLeaveType.max_days_per_year }} days/year
            </span>
          </div>

          <div>
            <DateRangePicker
              v-model="form.date_range"
              placeholder="Select your preferred leave days"
            />

            <div class="mt-1">
              <p v-if="estimatedDays && !(form.errors?.start_date || form.errors?.end_date)" class="text-sm text-gray-500">
                Estimated working days: {{ estimatedDays }}
              </p>

              <InputError :message="form.errors?.start_date" />

              <InputError :message="form.errors?.end_date" />
            </div>
          </div>

          <div>
            <Label class="mb-2">Reason for Leave</Label>

            <Textarea
              v-model="form.reason"
              placeholder="Please provide a reason for your leave request"
              class="w-full"
            />
          </div>

          <div>
            <InputError :message="form.errors.insufficient" />
          </div>
        </CardContent>

        <CardFooter class="flex justify-end space-x-4">
          <Button
            type="button"
            variant="outline"
            :href="route('leave-requests.index')">
            Cancel
          </Button>

          <Button :disabled="form.processing" @click="submit">
            <span v-if="form.processing">Submitting...</span>
            <span v-else>Submit Request</span>
          </Button>
        </CardFooter>
      </Card>
    </div>
  </AppLayout>
</template>
