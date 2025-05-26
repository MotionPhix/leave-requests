<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import dayjs from 'dayjs';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import DatePicker from '@/components/DatePicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { toast } from 'vue-sonner';
import { getLocalTimeZone } from '@internationalized/date';

const props = defineProps(['leaveTypes']);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Request Leave',
    href: '/leave-requests/create'
  }
];

const form = useForm({
  leave_type_id: '',
  start_date: '',
  end_date: '',
  reason: ''
});

const selectedType = ref(null);

watch(() => form.leave_type_id, (id) => {
  selectedType.value = props.leaveTypes.find(t => t.id == id);
});

const daysRequested = computed(() => {
  if (!form.start_date || !form.end_date) return 0;
  return dayjs(form.end_date).diff(dayjs(form.start_date), 'day') + 1;
});

const formatDateForLaravel = (dateValue) => {
  const date = dateValue?.toDate(getLocalTimeZone())

  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0') // Ensure two-digit month
  const day = String(date.getDate()).padStart(2, '0') // Ensure two-digit day

  return `${year}-${month}-${day}` // Laravel-friendly format
}

const submit = () => {
  form
    .transform((data) => {
      // const formattedStartDate = data.start_date?.toDate(getLocalTimeZone()).toLocaleDateString('en-MW'); // .toISOString().split('T')[0];
      // const formattedEndDate = data.end_date?.toDate(getLocalTimeZone()).toLocaleDateString('en-MW'); // .toISOString().split('T')[0];

      const formattedStartDate = formatDateForLaravel(data.start_date)
      const formattedEndDate = formatDateForLaravel(data.end_date)

      return {
        start_date: formattedStartDate,
        end_date: formattedEndDate,
        leave_type_id: data.leave_type_id,
        reason: data.reason
      };
    })
    .post(route('leave-requests.store'), {
      onError: function(err) {
        if (err.insufficient) {
          const type = props.leaveTypes[form.leave_type_id]

          toast.error(err.insufficient, {
            description: `You are out of your ${type.name} days!`
          });

          return;
        }

        toast.error('Please fix the form errors.');
      },

      onSuccess: function() {
        toast.success('Leave request submitted successfully!');
      }
    });
};
</script>

<template>

  <Head title="Request for a leave" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <Card class="max-w-2xl w-full mx-auto mt-10">
      <CardHeader>
        <CardTitle>
          Request Leave
        </CardTitle>
      </CardHeader>

      <CardContent>
        <form @submit.prevent="submit" class="space-y-8">
          <div class="grid gap-2">
            <Label>Leave Type</Label>
            <Select v-model="form.leave_type_id">
              <SelectTrigger class="mt-1 w-full">
                <SelectValue placeholder="Select..." />
              </SelectTrigger>

              <SelectContent>

                <SelectItem v-for="type in props.leaveTypes" :key="type.id" :value="type.id">
                  {{ type.name }}
                </SelectItem>

              </SelectContent>
            </Select>

            <InputError :message="form.errors.leave_type_id" />
          </div>

          <div class="grid grid-cols-2 gap-2 items-start">
            <div class="grid gap-2">
              <Label>
                Start Date
              </Label>

              <DatePicker v-model="form.start_date" />

              <InputError :message="form.errors.start_date" />
            </div>

            <div class="grid gap-2">
              <Label>End Date</Label>
              <DatePicker v-model="form.end_date" />

              <InputError :message="form.errors.end_date" />
            </div>

            <div v-if="selectedType" class="text-sm text-gray-600 col-span-2">
              {{ selectedType.max_days_per_year }} days available annually. You're requesting {{ daysRequested }}
              day(s).
            </div>
          </div>

          <div class="grid gap-2">
            <Label>
              Reason (optional)
            </Label>

            <Textarea
              v-model="form.reason">
            </Textarea>
          </div>

          <div>
            <Button type="submit">
              Submit Request
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </AppLayout>
</template>
