<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import LeaveStatusAlert from '@/components/LeaveStatusAlert.vue';
import { CalendarDays, Clock, FileText, AlertTriangle } from 'lucide-vue-next';
import DatePicker from '@/components/DatePicker.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface LeaveType {
  id: number;
  name: string;
  description: string;
  max_days_per_year: number;
  minimum_notice_days: number;
  requires_documentation: boolean;
  gender_specific: boolean;
  gender: string;
  frequency_years: number;
  available_days: number;
  pay_percentage: number;
}

interface LeaveBalance {
  leave_type_id: number;
  leave_type_name: string;
  max_days: number;
  used_days: number;
  remaining_days: number;
}

interface ActiveRequest {
  id: number;
  leave_type: string;
  start_date: string;
  end_date: string;
  status: string;
  status_label: string;
  total_days: number;
}

interface LeaveSummary {
  can_request_new_leave: boolean;
  blocking_reason?: string | null;
  latest_leave?: any;
  active_requests_count: number;
  active_requests: ActiveRequest[];
  has_pending_requests: boolean;
  has_active_approved_leave: boolean;
}

interface Props {
  leaveTypes: LeaveType[];
  leaveBalances: LeaveBalance[];
  leaveSummary: LeaveSummary;
  canRequestLeave: boolean;
  blockingReason?: string | null;
  activeRequests: ActiveRequest[];
  holidays?: Array<{ date: string; name: string }>;
}

const props = defineProps<Props>();

const form = useForm({
  leave_type_id: '',
  start_date: '',
  end_date: '',
  reason: '',
  supporting_documents: [] as File[],
});

const selectedLeaveType = computed(() => {
  if (!form.leave_type_id) return null;
  return props.leaveTypes.find((type) => type.id.toString() === form.leave_type_id);
});

const selectedLeaveBalance = computed(() => {
  if (!form.leave_type_id) return null;
  return props.leaveBalances.find((balance) => balance.leave_type_id.toString() === form.leave_type_id);
});

const minimumStartDate = computed(() => {
  if (!selectedLeaveType.value) return new Date().toISOString().split('T')[0];

  const minDate = new Date();
  minDate.setDate(minDate.getDate() + selectedLeaveType.value.minimum_notice_days);
  return minDate.toISOString().split('T')[0];
});

const estimatedDays = computed(() => {
  if (!form.start_date || !form.end_date) return 0;

  const start = new Date(form.start_date);
  const end = new Date(form.end_date);

  if (end < start) return 0;

  // Simple calculation - in real app you'd want to exclude weekends/holidays
  const diffTime = Math.abs(end.getTime() - start.getTime());
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
});

const hasInsufficientBalance = computed(() => {
  if (!selectedLeaveBalance.value || estimatedDays.value === 0) return false;
  return estimatedDays.value > selectedLeaveBalance.value.remaining_days;
});

const balanceValidationMessage = computed(() => {
  if (!hasInsufficientBalance.value) return '';

  const remaining = selectedLeaveBalance.value?.remaining_days || 0;
  const needed = estimatedDays.value;
  const shortage = needed - remaining;

  return `Insufficient leave balance. You need ${needed} days but only have ${remaining} days remaining (${shortage} days short).`;
});

const canSubmit = computed(() => {
  return (
    props.canRequestLeave &&
    form.leave_type_id &&
    form.start_date &&
    form.end_date &&
    form.reason.trim().length > 0 &&
    !hasInsufficientBalance.value && // Add balance check
    !form.processing
  );
});

const submitButtonText = computed(() => {
  if (form.processing) return 'Submitting...';
  if (!props.canRequestLeave) return 'Cannot Submit';
  if (hasInsufficientBalance.value) return 'Insufficient Balance';
  return 'Submit Request';
});

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    form.supporting_documents = Array.from(target.files);
  }
};

const submit = () => {
  if (!canSubmit.value) return;

  form.post(route('leave-requests.store'), {
    forceFormData: true,
  });
};
</script>

<template>
  <Head title="Request Leave" />

  <AppLayout
    :breadcrumbs="[
      { title: 'Leave Requests', href: route('leave-requests.index') },
      { title: 'Request Leave', href: route('leave-requests.create') },
    ]">
    <div class="max-w-4xl space-y-6 p-6">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Request Leave</h1>
        <p class="text-muted-foreground">Submit a new leave request for approval</p>
      </div>

      <!-- Leave Status Alert Component -->
      <LeaveStatusAlert
        :can-request-leave="canRequestLeave"
        :blocking-reason="blockingReason"
        :active-requests="activeRequests"
      />

      <!-- Leave Request Form -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Main Form -->
        <div class="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <CalendarDays class="h-5 w-5" />
                Leave Request Details
              </CardTitle>
            </CardHeader>

            <CardContent class="space-y-6">
              <form @submit.prevent="submit" class="space-y-6">
                <!-- Leave Type Selection -->
                <div class="space-y-2">
                  <Label for="leave_type_id">Leave Type *</Label>

                  <Select v-model="form.leave_type_id" :disabled="!canRequestLeave">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select leave type" />
                    </SelectTrigger>

                    <SelectContent>
                      <SelectItem v-for="leaveType in leaveTypes" :key="leaveType.id" :value="leaveType.id.toString()">
                        <div class="flex items-center justify-between w-full">
                          <span>{{ leaveType.name }}</span>
                          <div class="flex items-center gap-2 ml-2">
                            <Badge variant="outline" class="text-xs">
                              {{ leaveType.available_days }} days
                            </Badge>
                            <span v-if="leaveType.minimum_notice_days > 0" class="text-muted-foreground text-xs">
                              ({{ leaveType.minimum_notice_days }} days notice)
                            </span>
                          </div>
                        </div>
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.leave_type_id" class="text-destructive text-sm">
                    {{ form.errors.leave_type_id }}
                  </p>
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                  <div class="space-y-2">
                    <Label for="start_date">Start Date *</Label>
                    <DatePicker
                      id="start_date"
                      v-model="form.start_date"
                      :min="minimumStartDate"
                      :disabled="!canRequestLeave"
                      placeholder="Select start date"
                    />

                    <p v-if="form.errors.start_date" class="text-destructive text-sm">
                      {{ form.errors.start_date }}
                    </p>

                    <p v-if="selectedLeaveType?.minimum_notice_days" class="text-muted-foreground text-xs">
                      Minimum {{ selectedLeaveType.minimum_notice_days }} days notice required
                    </p>
                  </div>

                  <div class="space-y-2">
                    <Label for="end_date">End Date *</Label>
                    <DatePicker
                      id="end_date"
                      v-model="form.end_date"
                      :min="form.start_date || minimumStartDate"
                      :disabled="!canRequestLeave"
                      placeholder="Select end date"
                    />

                    <p v-if="form.errors.end_date" class="text-destructive text-sm">
                      {{ form.errors.end_date }}
                    </p>
                  </div>
                </div>

                <!-- Estimated Days -->
                <div v-if="estimatedDays > 0" class="space-y-3">
                  <div class="bg-muted rounded-lg p-3">
                    <div class="flex items-center gap-2">
                      <Clock class="text-muted-foreground h-4 w-4" />
                      <span class="text-sm font-medium">Estimated Duration:</span>
                      <Badge variant="secondary">{{ estimatedDays }} {{ estimatedDays === 1 ? 'day' : 'days' }}</Badge>
                    </div>
                  </div>

                  <!-- Balance Warning -->
                  <Alert v-if="hasInsufficientBalance" variant="destructive">
                    <AlertTriangle class="h-4 w-4" />
                    <AlertDescription>
                      {{ balanceValidationMessage }}
                    </AlertDescription>
                  </Alert>
                </div>

                <!-- Reason -->
                <div class="space-y-2">
                  <Label for="reason">Reason for Leave *</Label>
                  <Textarea
                    id="reason"
                    v-model="form.reason"
                    placeholder="Please provide a reason for your leave request..."
                    rows="4"
                    :disabled="!canRequestLeave"
                    class="resize-none"
                  />
                  <p v-if="form.errors.reason" class="text-destructive text-sm">
                    {{ form.errors.reason }}
                  </p>
                </div>

                <!-- Supporting Documents -->
                <div class="space-y-2">
                  <Label for="supporting_documents">
                    Supporting Documents
                    <span v-if="selectedLeaveType?.requires_documentation" class="text-destructive">*</span>
                    <span v-else class="text-muted-foreground">(Optional)</span>
                  </Label>
                  <Input
                    id="supporting_documents"
                    type="file"
                    multiple
                    accept=".pdf,.jpg,.jpeg,.png"
                    :disabled="!canRequestLeave"
                    @change="handleFileUpload"
                    class="file:bg-primary file:text-primary-foreground hover:file:bg-primary/80 file:mr-4 file:rounded-full file:border-0 file:px-4 file:py-2 file:text-sm file:font-semibold"
                  />
                  <p class="text-muted-foreground text-xs">Accepted formats: PDF, JPG, PNG (Max 5MB each)</p>
                  <p v-if="selectedLeaveType?.requires_documentation" class="text-muted-foreground text-xs">
                    This leave type requires supporting documentation
                  </p>
                  <p v-if="form.errors.supporting_documents" class="text-destructive text-sm">
                    {{ form.errors.supporting_documents }}
                  </p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end pt-4">
                  <Button
                    type="submit"
                    :disabled="!canSubmit"
                    :loading="form.processing"
                    :variant="hasInsufficientBalance ? 'destructive' : 'default'"
                    class="min-w-[140px]">
                    <FileText class="mr-2 h-4 w-4" />
                    {{ submitButtonText }}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar - Leave Balances -->
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Leave Balances</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div v-for="balance in leaveBalances" :key="balance.leave_type_id" class="space-y-2">
                <div class="flex items-center justify-between">
                  <span class="font-medium">{{ balance.leave_type_name }}</span>
                  <Badge :variant="balance.remaining_days > 0 ? 'default' : 'destructive'" class="text-xs">
                    {{ balance.remaining_days }}/{{ balance.max_days }}
                  </Badge>
                </div>
                <div class="bg-muted h-2 w-full rounded-full">
                  <div
                    class="bg-primary h-2 rounded-full transition-all"
                    :style="{ width: `${Math.min((balance.used_days / balance.max_days) * 100, 100)}%` }"
                  ></div>
                </div>
                <div class="text-muted-foreground flex justify-between text-xs">
                  <span>Used: {{ balance.used_days }}</span>
                  <span>Remaining: {{ balance.remaining_days }}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Quick Tips -->
          <Card>
            <CardHeader>
              <CardTitle class="text-lg">Quick Tips</CardTitle>
            </CardHeader>
            <CardContent class="text-muted-foreground space-y-3 text-sm">
              <div class="flex items-start gap-2">
                <span class="text-primary">•</span>
                <span>Check your leave balance before submitting</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-primary">•</span>
                <span>Some leave types require advance notice</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-primary">•</span>
                <span>Upload supporting documents if required</span>
              </div>
              <div class="flex items-start gap-2">
                <span class="text-primary">•</span>
                <span>You'll receive notifications about your request status</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
