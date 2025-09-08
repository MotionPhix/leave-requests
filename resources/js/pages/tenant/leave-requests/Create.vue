<template>
  <TenantLayout>
    <Head title="Create Leave Request" />
    
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Create Leave Request</h1>
          <p class="text-gray-600 mt-1">Submit a new leave request for approval</p>
        </div>
        <Link :href="route('tenant.leave-requests.index', tenantParams)">
          <Button variant="outline">
            ← Back to Requests
          </Button>
        </Link>
      </div>

      <!-- Leave Summary Alert -->
      <div v-if="!leaveSummary.can_request_new_leave" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-start gap-3">
          <AlertTriangle class="w-5 h-5 text-red-600 mt-0.5" />
          <div>
            <h3 class="font-medium text-red-900">Cannot Submit New Request</h3>
            <p class="text-red-700 text-sm mt-1">
              You currently have pending or overlapping leave requests. Please review your existing requests before submitting a new one.
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Leave Type Selection -->
              <div>
                <Label for="leave_type_id" class="text-base font-semibold">Leave Type *</Label>
                <p class="text-sm text-gray-600 mb-3">Select the type of leave you're requesting</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div 
                    v-for="leaveType in availableLeaveTypes" 
                    :key="leaveType.id"
                    class="relative"
                  >
                    <input
                      :id="`leave_type_${leaveType.id}`"
                      v-model="form.leave_type_id"
                      :value="leaveType.id"
                      name="leave_type_id"
                      type="radio"
                      class="sr-only peer"
                    />
                    <label
                      :for="`leave_type_${leaveType.id}`"
                      class="flex flex-col p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 peer-checked:bg-indigo-50 peer-checked:border-indigo-500 peer-checked:ring-2 peer-checked:ring-indigo-200 transition-all"
                    >
                      <div class="flex items-start justify-between mb-2">
                        <span class="font-medium text-gray-900">{{ leaveType.name }}</span>
                        <Badge v-if="getLeaveBalance(leaveType.id)" variant="outline" class="text-xs">
                          {{ getLeaveBalance(leaveType.id)?.remaining_days }} days left
                        </Badge>
                      </div>
                      <p class="text-sm text-gray-600">{{ leaveType.description }}</p>
                      <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                        <span>Max: {{ leaveType.max_days_per_year }} days/year</span>
                        <span v-if="leaveType.minimum_notice_days > 0">
                          Notice: {{ leaveType.minimum_notice_days }} days
                        </span>
                      </div>
                    </label>
                  </div>
                </div>
                <div v-if="form.errors.leave_type_id" class="text-sm text-red-600 mt-2">
                  {{ form.errors.leave_type_id }}
                </div>
              </div>

              <!-- Date Range -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <Label for="start_date" class="text-base font-semibold">Start Date *</Label>
                  <DatePicker
                    id="start_date"
                    v-model="form.start_date"
                    :min-date="minStartDate"
                    placeholder="Select start date"
                    class="mt-2"
                  />
                  <div v-if="form.errors.start_date" class="text-sm text-red-600 mt-1">
                    {{ form.errors.start_date }}
                  </div>
                </div>

                <div>
                  <Label for="end_date" class="text-base font-semibold">End Date *</Label>
                  <DatePicker
                    id="end_date"
                    v-model="form.end_date"
                    :min-date="form.start_date || minStartDate"
                    placeholder="Select end date"
                    class="mt-2"
                  />
                  <div v-if="form.errors.end_date" class="text-sm text-red-600 mt-1">
                    {{ form.errors.end_date }}
                  </div>
                </div>
              </div>

              <!-- Duration Display -->
              <div v-if="calculatedDays > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center gap-3">
                  <Clock class="w-5 h-5 text-blue-600" />
                  <div>
                    <p class="font-medium text-blue-900">
                      Duration: {{ calculatedDays }} {{ calculatedDays === 1 ? 'day' : 'days' }}
                    </p>
                    <p class="text-sm text-blue-700">
                      From {{ formatDate(form.start_date) }} to {{ formatDate(form.end_date) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Reason -->
              <div>
                <Label for="reason" class="text-base font-semibold">
                  Reason 
                  <span v-if="selectedLeaveType?.requires_documentation" class="text-red-600">*</span>
                </Label>
                <p class="text-sm text-gray-600 mb-3">
                  {{ selectedLeaveType?.requires_documentation 
                    ? 'Please provide a detailed reason for this leave request' 
                    : 'Optional: Provide additional context for your leave request' 
                  }}
                </p>
                <Textarea
                  id="reason"
                  v-model="form.reason"
                  placeholder="Enter the reason for your leave request..."
                  rows="4"
                />
                <div v-if="form.errors.reason" class="text-sm text-red-600 mt-1">
                  {{ form.errors.reason }}
                </div>
              </div>

              <!-- Documentation Notice -->
              <div v-if="selectedLeaveType?.requires_documentation" class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                  <FileText class="w-5 h-5 text-amber-600 mt-0.5" />
                  <div>
                    <h4 class="font-medium text-amber-900">Documentation Required</h4>
                    <p class="text-amber-700 text-sm mt-1">
                      This leave type requires supporting documentation. Please ensure you have the necessary documents ready to submit with your request.
                    </p>
                  </div>
                </div>
              </div>

              <!-- Submit Buttons -->
              <div class="flex flex-col sm:flex-row gap-3 pt-4">
                <Button 
                  type="submit" 
                  :disabled="form.processing || !leaveSummary.can_request_new_leave"
                  class="flex-1"
                >
                  <span v-if="form.processing">Submitting...</span>
                  <span v-else>Submit Leave Request</span>
                </Button>
                <Link :href="route('tenant.leave-requests.index', tenantParams)">
                  <Button variant="outline" class="w-full sm:w-auto">
                    Cancel
                  </Button>
                </Link>
              </div>
            </form>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Leave Balance Summary -->
          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <CalendarDays class="w-5 h-5" />
              Leave Balance
            </h3>
            <div class="space-y-3">
              <div 
                v-for="balance in leaveBalances" 
                :key="balance.leave_type_id"
                class="flex justify-between items-center p-3 bg-gray-50 rounded-lg"
              >
                <div>
                  <p class="font-medium text-gray-900 text-sm">{{ balance.leave_type_name }}</p>
                  <p class="text-xs text-gray-600">{{ balance.used_days }}/{{ balance.max_days }} used</p>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-lg" :class="balance.remaining_days > 0 ? 'text-green-600' : 'text-red-600'">
                    {{ balance.remaining_days }}
                  </p>
                  <p class="text-xs text-gray-600">remaining</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Active Requests -->
          <div v-if="activeRequests && activeRequests.length > 0" class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Active Requests</h3>
            <div class="space-y-3">
              <div 
                v-for="request in activeRequests" 
                :key="request.id"
                class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg"
              >
                <div class="flex justify-between items-start mb-2">
                  <p class="font-medium text-gray-900 text-sm">{{ request.leave_type }}</p>
                  <Badge :class="getStatusBadgeClass(request.status)" class="text-xs">
                    {{ request.status_label }}
                  </Badge>
                </div>
                <p class="text-xs text-gray-600">
                  {{ formatDateRange(request.start_date, request.end_date) }}
                </p>
                <p class="text-xs text-gray-600">{{ request.total_days }} days</p>
              </div>
            </div>
          </div>

          <!-- Help Text -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-900 mb-3">Need Help?</h3>
            <div class="space-y-2 text-sm text-blue-800">
              <p>• Submit requests at least the required notice period in advance</p>
              <p>• Check your remaining leave balance before submitting</p>
              <p>• Provide detailed reasons for better approval chances</p>
              <p>• Contact HR for questions about leave policies</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import TenantLayout from '@/layouts/TenantLayout.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Badge } from '@/components/ui/badge'
import DatePicker from '@/components/DatePicker.vue'
import { CalendarDays, Clock, FileText, AlertTriangle } from 'lucide-vue-next'
import { computed } from 'vue'
import dayjs from 'dayjs'

interface LeaveType {
  id: number;
  name: string;
  description: string;
  max_days_per_year: number;
  minimum_notice_days: number;
  requires_documentation: boolean;
  gender_specific: boolean;
  gender?: string;
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
}

interface PageProps extends Record<string, any> {
  workspace: {
    slug: string;
    uuid: string;
    name: string;
  };
  leaveTypes: LeaveType[];
  leaveBalances: LeaveBalance[];
  activeRequests: ActiveRequest[];
  leaveSummary: LeaveSummary;
}

const page = usePage<PageProps>();
const { workspace, leaveTypes, leaveBalances, activeRequests, leaveSummary } = page.props;

// Tenant parameters
const tenantParams = {
  tenant_slug: workspace?.slug,
  tenant_uuid: workspace?.uuid
};

// Form
const form = useForm({
  leave_type_id: '',
  start_date: '',
  end_date: '',
  reason: '',
});

// Computed properties
const availableLeaveTypes = computed(() => {
  return leaveTypes.filter(type => {
    const balance = getLeaveBalance(type.id);
    return balance && balance.remaining_days > 0;
  });
});

const selectedLeaveType = computed(() => {
  return leaveTypes.find(type => type.id === parseInt(form.leave_type_id));
});

const minStartDate = computed(() => {
  const today = dayjs();
  const noticeRequired = selectedLeaveType.value?.minimum_notice_days || 0;
  return today.add(noticeRequired, 'day').format('YYYY-MM-DD');
});

const calculatedDays = computed(() => {
  if (!form.start_date || !form.end_date) return 0;
  
  const start = dayjs(form.start_date);
  const end = dayjs(form.end_date);
  
  if (end.isBefore(start)) return 0;
  
  return end.diff(start, 'day') + 1;
});

// Methods
const getLeaveBalance = (leaveTypeId: number) => {
  return leaveBalances.find(balance => balance.leave_type_id === leaveTypeId);
};

const submit = () => {
  form.post(route('tenant.leave-requests.store', tenantParams), {
    onSuccess: () => {
      // Will redirect to index page
    }
  });
};

// Utility functions
const formatDate = (date: string) => {
  return date ? dayjs(date).format('MMM D, YYYY') : '';
};

const formatDateRange = (startDate: string, endDate: string) => {
  const start = dayjs(startDate);
  const end = dayjs(endDate);
  
  if (start.isSame(end, 'day')) {
    return start.format('MMM D, YYYY');
  }
  
  return `${start.format('MMM D')} - ${end.format('MMM D, YYYY')}`;
};

const getStatusBadgeClass = (status: string) => {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'approved': 'bg-green-100 text-green-800 border-green-200',
    'rejected': 'bg-red-100 text-red-800 border-red-200',
    'cancelled': 'bg-gray-100 text-gray-800 border-gray-200',
  };
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800 border-gray-200';
};
</script>
