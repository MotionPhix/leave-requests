<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar } from '@/components/ui/calendar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
  leaveTypes: Array<{
    id: number;
    name: string;
    description: string;
    requires_documentation: boolean;
    minimum_notice_days: number;
    available_days: number;
    pay_percentage: number;
  }>;
  holidays: Array<{
    date: string;
    name: string;
  }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Leaves', href: '/employee/leaves' },
  { title: 'New Request', href: '/employee/leaves/create' }
];

const form = useForm('post', '/employee/leaves', {
  leave_type_id: '',
  start_date: '',
  end_date: '',
  reason: '',
  supporting_documents: [] as File[],
});

// Set validation timeout
form.setValidationTimeout(750);

const selectedLeaveType = computed(() => {
  return props.leaveTypes.find(type => type.id === Number(form.leave_type_id));
});

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    form.supporting_documents = Array.from(target.files);
  }
};

const submit = () => {
  form.submit({
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="New Leave Request" />

    <div class="p-6">
      <Card class="max-w-2xl mx-auto">
        <CardHeader>
          <CardTitle>Submit Leave Request</CardTitle>
        </CardHeader>
        <CardContent>
          <Form @submit.prevent="submit">
            <div class="space-y-6">
              <!-- Leave Type Selection -->
              <FormField name="leave_type_id">
                <FormLabel>Leave Type</FormLabel>
                <FormControl>
                  <Select
                    v-model="form.leave_type_id"
                    @update:modelValue="form.validate('leave_type_id')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select leave type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="type in leaveTypes"
                        :key="type.id"
                        :value="type.id"
                      >
                        {{ type.name }} ({{ type.available_days }} days available)
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </FormControl>
                <FormMessage />
              </FormField>

              <!-- Selected Leave Type Info -->
              <Alert v-if="selectedLeaveType" class="mt-4">
                <AlertDescription>
                  <div class="space-y-2">
                    <p>{{ selectedLeaveType.description }}</p>
                    <p v-if="selectedLeaveType.minimum_notice_days > 0">
                      Requires {{ selectedLeaveType.minimum_notice_days }} days notice
                    </p>
                    <p v-if="selectedLeaveType.pay_percentage < 100">
                      Paid at {{ selectedLeaveType.pay_percentage }}% of regular salary
                    </p>
                  </div>
                </AlertDescription>
              </Alert>

              <!-- Date Selection -->
              <div class="grid grid-cols-2 gap-4">
                <FormField name="start_date">
                  <FormLabel>Start Date</FormLabel>
                  <FormControl>
                    <Input
                      type="date"
                      v-model="form.start_date"
                      @change="form.validate('start_date')"
                    />
                  </FormControl>
                  <FormMessage />
                </FormField>

                <FormField name="end_date">
                  <FormLabel>End Date</FormLabel>
                  <FormControl>
                    <Input
                      type="date"
                      v-model="form.end_date"
                      @change="form.validate('end_date')"
                    />
                  </FormControl>
                  <FormMessage />
                </FormField>
              </div>

              <!-- Reason -->
              <FormField name="reason">
                <FormLabel>Reason</FormLabel>
                <FormControl>
                  <Textarea
                    v-model="form.reason"
                    @change="form.validate('reason')"
                    rows="4"
                  />
                </FormControl>
                <FormMessage />
              </FormField>

              <!-- Supporting Documents -->
              <FormField
                v-if="selectedLeaveType?.requires_documentation"
                name="supporting_documents"
              >
                <FormLabel>Supporting Documents</FormLabel>
                <FormControl>
                  <Input
                    type="file"
                    @change="handleFileUpload"
                    accept=".pdf,.jpg,.jpeg,.png"
                    multiple
                  />
                </FormControl>
                <FormMessage />
              </FormField>

              <Button
                type="submit"
                :disabled="form.processing || form.hasErrors"
                class="w-full"
              >
                <span v-if="form.processing">Submitting...</span>
                <span v-else>Submit Leave Request</span>
              </Button>
            </div>
          </Form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
