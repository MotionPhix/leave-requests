<template>
  <TenantLayout>
    <Head :title="`Leave Request #${leaveRequest.id}`" />
    
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">
            Leave Request #{{ leaveRequest.id }}
          </h1>
          <p class="text-gray-600 mt-1">{{ leaveRequest.leave_type?.name }}</p>
        </div>
        <div class="flex items-center gap-3">
          <Badge :class="getStatusBadgeClass(leaveRequest.status)" class="text-sm px-3 py-1">
            {{ capitalizeFirst(leaveRequest.status) }}
          </Badge>
          <Link :href="route('tenant.leave-requests.index', tenantParams)">
            <Button variant="outline">
              ← Back to Requests
            </Button>
          </Link>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Request Details -->
          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Request Details</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <Label class="text-sm font-medium text-gray-600">Leave Type</Label>
                <p class="text-base text-gray-900 mt-1">{{ leaveRequest.leave_type?.name }}</p>
              </div>

              <div>
                <Label class="text-sm font-medium text-gray-600">Duration</Label>
                <p class="text-base text-gray-900 mt-1">
                  {{ leaveRequest.days }} {{ leaveRequest.days === 1 ? 'day' : 'days' }}
                </p>
              </div>

              <div>
                <Label class="text-sm font-medium text-gray-600">Start Date</Label>
                <p class="text-base text-gray-900 mt-1">{{ formatDate(leaveRequest.start_date) }}</p>
              </div>

              <div>
                <Label class="text-sm font-medium text-gray-600">End Date</Label>
                <p class="text-base text-gray-900 mt-1">{{ formatDate(leaveRequest.end_date) }}</p>
              </div>

              <div class="md:col-span-2" v-if="leaveRequest.reason">
                <Label class="text-sm font-medium text-gray-600">Reason</Label>
                <p class="text-base text-gray-900 mt-1 whitespace-pre-wrap">{{ leaveRequest.reason }}</p>
              </div>
            </div>
          </div>

          <!-- Status Timeline -->
          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Status Timeline</h2>
            
            <div class="space-y-4">
              <!-- Submitted -->
              <div class="flex items-start gap-4">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mt-1">
                  <CheckCircle2 class="w-4 h-4 text-blue-600" />
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <p class="font-medium text-gray-900">Request Submitted</p>
                    <span class="text-sm text-gray-500">{{ formatDateTime(leaveRequest.created_at) }}</span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">Leave request has been submitted for approval</p>
                </div>
              </div>

              <!-- Approved -->
              <div v-if="leaveRequest.status === 'approved'" class="flex items-start gap-4">
                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mt-1">
                  <CheckCircle2 class="w-4 h-4 text-green-600" />
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <p class="font-medium text-gray-900">Request Approved</p>
                    <span class="text-sm text-gray-500">{{ formatDateTime(leaveRequest.approved_at) }}</span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">
                    Approved by {{ leaveRequest.approved_by?.name || 'Manager' }}
                  </p>
                  <p v-if="leaveRequest.approval_notes" class="text-sm text-gray-600 mt-1 italic">
                    "{{ leaveRequest.approval_notes }}"
                  </p>
                </div>
              </div>

              <!-- Rejected -->
              <div v-if="leaveRequest.status === 'rejected'" class="flex items-start gap-4">
                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mt-1">
                  <XCircle class="w-4 h-4 text-red-600" />
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <p class="font-medium text-gray-900">Request Rejected</p>
                    <span class="text-sm text-gray-500">{{ formatDateTime(leaveRequest.rejected_at) }}</span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">
                    Rejected by {{ leaveRequest.rejected_by?.name || 'Manager' }}
                  </p>
                  <p v-if="leaveRequest.rejection_notes" class="text-sm text-gray-600 mt-1 italic">
                    "{{ leaveRequest.rejection_notes }}"
                  </p>
                </div>
              </div>

              <!-- Cancelled -->
              <div v-if="leaveRequest.status === 'cancelled'" class="flex items-start gap-4">
                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mt-1">
                  <XCircle class="w-4 h-4 text-gray-600" />
                </div>
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <p class="font-medium text-gray-900">Request Cancelled</p>
                    <span class="text-sm text-gray-500">{{ formatDateTime(leaveRequest.cancelled_at) }}</span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">Request was cancelled by the employee</p>
                </div>
              </div>

              <!-- Pending -->
              <div v-if="leaveRequest.status === 'pending'" class="flex items-start gap-4">
                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mt-1">
                  <Clock class="w-4 h-4 text-yellow-600" />
                </div>
                <div class="flex-1">
                  <p class="font-medium text-gray-900">Awaiting Approval</p>
                  <p class="text-sm text-gray-600 mt-1">Your request is being reviewed by management</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Quick Actions -->
          <div v-if="leaveRequest.status === 'pending'" class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Actions</h3>
            <div class="space-y-3">
              <Button 
                variant="destructive" 
                @click="cancelRequest"
                :disabled="processing"
                class="w-full"
              >
                {{ processing ? 'Cancelling...' : 'Cancel Request' }}
              </Button>
              <p class="text-xs text-gray-600">
                You can cancel this request while it's still pending approval.
              </p>
            </div>
          </div>

          <!-- Request Summary -->
          <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Summary</h3>
            <div class="space-y-3">
              <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Request ID</span>
                <span class="font-medium">#{{ leaveRequest.id }}</span>
              </div>
              <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Status</span>
                <Badge :class="getStatusBadgeClass(leaveRequest.status)" class="text-xs">
                  {{ capitalizeFirst(leaveRequest.status) }}
                </Badge>
              </div>
              <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Duration</span>
                <span class="font-medium">{{ leaveRequest.days }} days</span>
              </div>
              <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-sm text-gray-600">Submitted</span>
                <span class="font-medium">{{ formatDate(leaveRequest.created_at) }}</span>
              </div>
              <div v-if="leaveRequest.status !== 'pending'" class="flex justify-between items-center py-2">
                <span class="text-sm text-gray-600">Last Updated</span>
                <span class="font-medium">{{ formatDate(getLastUpdatedDate()) }}</span>
              </div>
            </div>
          </div>

          <!-- Help Information -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-900 mb-3">Need Help?</h3>
            <div class="space-y-2 text-sm text-blue-800">
              <p>• Contact your manager for urgent requests</p>
              <p>• Review company leave policy for guidelines</p>
              <p>• Reach out to HR for policy questions</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import TenantLayout from '@/layouts/TenantLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Label } from '@/components/ui/label'
import { CheckCircle2, XCircle, Clock } from 'lucide-vue-next'
import { ref } from 'vue'
import dayjs from 'dayjs'

interface User {
  id: number;
  name: string;
  email: string;
}

interface LeaveType {
  id: number;
  name: string;
  description: string;
}

interface LeaveRequest {
  id: number;
  start_date: string;
  end_date: string;
  reason?: string;
  status: string;
  days: number;
  created_at: string;
  approved_at?: string;
  rejected_at?: string;
  cancelled_at?: string;
  approval_notes?: string;
  rejection_notes?: string;
  leave_type?: LeaveType;
  approved_by?: User;
  rejected_by?: User;
}

interface PageProps extends Record<string, any> {
  workspace: {
    slug: string;
    uuid: string;
    name: string;
  };
  leaveRequest: LeaveRequest;
}

const page = usePage<PageProps>();
const { workspace, leaveRequest } = page.props;

// Tenant parameters
const tenantParams = {
  tenant_slug: workspace?.slug,
  tenant_uuid: workspace?.uuid
};

// State
const processing = ref(false);

// Methods
const cancelRequest = () => {
  if (confirm('Are you sure you want to cancel this leave request? This action cannot be undone.')) {
    processing.value = true;
    
    router.patch(
      route('tenant.leave-requests.cancel', { 
        ...tenantParams, 
        leaveRequest: leaveRequest.id 
      }),
      {},
      {
        onSuccess: () => {
          processing.value = false;
        },
        onError: () => {
          processing.value = false;
        }
      }
    );
  }
};

const getLastUpdatedDate = () => {
  const dates = [
    leaveRequest.approved_at,
    leaveRequest.rejected_at,
    leaveRequest.cancelled_at
  ].filter(Boolean);
  
  return dates.length > 0 ? dates[dates.length - 1] : leaveRequest.created_at;
};

// Utility functions
const formatDate = (date: string | undefined) => {
  return date ? dayjs(date).format('MMM D, YYYY') : '';
};

const formatDateTime = (date: string | undefined) => {
  return date ? dayjs(date).format('MMM D, YYYY [at] h:mm A') : '';
};

const capitalizeFirst = (str: string) => {
  return str.charAt(0).toUpperCase() + str.slice(1);
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
