<template>
  <TenantLayout>
    <Head title="My Leave Requests" />
    
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">My Leave Requests</h1>
          <p class="text-gray-600 mt-1">View and manage your leave requests</p>
        </div>
        <div class="flex items-center gap-3">
          <Button 
            variant="outline" 
            size="sm" 
            @click="toggleFilters"
            class="flex items-center gap-2"
          >
            <SlidersHorizontalIcon class="w-4 h-4" />
            Filters
          </Button>
          <Link :href="route('tenant.leave-requests.create', tenantParams)">
            <Button class="flex items-center gap-2">
              <PlusIcon class="w-4 h-4" />
              New Request
            </Button>
          </Link>
        </div>
      </div>

      <!-- Filters Panel -->
      <div v-if="showFilters" class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <Select v-model="filters.status" @update:model-value="applyFilters">
              <SelectTrigger>
                <SelectValue placeholder="All statuses" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All statuses</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="approved">Approved</SelectItem>
                <SelectItem value="rejected">Rejected</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type</label>
            <Select v-model="filters.leave_type_id" @update:model-value="applyFilters">
              <SelectTrigger>
                <SelectValue placeholder="All types" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All types</SelectItem>
                <SelectItem 
                  v-for="type in leaveTypes" 
                  :key="type.id" 
                  :value="type.id.toString()"
                >
                  {{ type.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
            <DatePicker 
              v-model="filters.date_from" 
              @update:model-value="applyFilters"
              placeholder="Select start date" 
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
            <DatePicker 
              v-model="filters.date_to" 
              @update:model-value="applyFilters"
              placeholder="Select end date" 
            />
          </div>
        </div>

        <div class="flex justify-end mt-4">
          <Button variant="outline" @click="clearFilters" size="sm">
            Clear Filters
          </Button>
        </div>
      </div>

      <!-- Leave Requests List -->
      <div v-if="leaveRequests && leaveRequests.length > 0" class="space-y-4">
        <div 
          v-for="request in leaveRequests" 
          :key="request.id"
          class="bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow duration-200"
        >
          <div class="p-6">
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <!-- Request Type and Status -->
                <div class="flex items-center gap-3 mb-3">
                  <h3 class="text-lg font-semibold text-gray-900">
                    {{ request.leave_type?.name || 'Leave Request' }}
                  </h3>
                  <Badge :class="getStatusBadgeClass(request.status)">
                    {{ capitalizeFirst(request.status) }}
                  </Badge>
                </div>

                <!-- Date Range -->
                <div class="flex items-center gap-6 text-sm text-gray-600 mb-4">
                  <div class="flex items-center gap-2">
                    <Calendar class="w-4 h-4" />
                    <span>
                      {{ formatDateRange(request.start_date, request.end_date) }}
                    </span>
                  </div>
                  <div class="flex items-center gap-2">
                    <Clock class="w-4 h-4" />
                    <span>{{ request.days }} {{ request.days === 1 ? 'day' : 'days' }}</span>
                  </div>
                </div>

                <!-- Reason (if provided) -->
                <div v-if="request.reason" class="mb-4">
                  <p class="text-sm text-gray-700">
                    <span class="font-medium">Reason:</span> {{ request.reason }}
                  </p>
                </div>

                <!-- Status Details -->
                <div class="flex items-center gap-4 text-xs text-gray-500">
                  <span>Submitted {{ formatRelativeTime(request.created_at) }}</span>
                  <span v-if="request.approved_at && request.status === 'approved'">
                    Approved {{ formatRelativeTime(request.approved_at) }}
                  </span>
                  <span v-if="request.rejected_at && request.status === 'rejected'">
                    Rejected {{ formatRelativeTime(request.rejected_at) }}
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2">
                <!-- Cancel button for pending requests -->
                <Button 
                  v-if="request.status === 'pending'" 
                  variant="outline" 
                  size="sm"
                  @click="cancelRequest(request)"
                  class="text-red-600 border-red-300 hover:bg-red-50"
                >
                  Cancel
                </Button>

                <!-- View Details -->
                <Link :href="route('tenant.leave-requests.show', { ...tenantParams, leaveRequest: request.id })">
                  <Button variant="outline" size="sm" class="flex items-center gap-1">
                    View
                    <ChevronRight class="w-4 h-4" />
                  </Button>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-lg border border-gray-200 p-12 text-center">
        <div class="max-w-md mx-auto">
          <Calendar class="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-semibold text-gray-900 mb-2">No leave requests found</h3>
          <p class="text-gray-600 mb-6">
            {{ hasActiveFilters ? 'Try adjusting your filters or' : 'You haven\'t submitted any leave requests yet.' }}
            {{ hasActiveFilters ? 'create your first leave request.' : 'Get started by creating your first request.' }}
          </p>
          <Link :href="route('tenant.leave-requests.create', tenantParams)">
            <Button class="flex items-center gap-2">
              <PlusIcon class="w-4 h-4" />
              Create Leave Request
            </Button>
          </Link>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>
        <div class="flex items-center gap-2">
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="!pagination.prev_page_url"
            @click="goToPage(pagination.current_page - 1)"
          >
            <ArrowLeftIcon class="w-4 h-4" />
            Previous
          </Button>
          <span class="px-3 py-1 text-sm text-gray-600">
            Page {{ pagination.current_page }} of {{ pagination.last_page }}
          </span>
          <Button 
            variant="outline" 
            size="sm" 
            :disabled="!pagination.next_page_url"
            @click="goToPage(pagination.current_page + 1)"
          >
            Next
            <ArrowRightIcon class="w-4 h-4" />
          </Button>
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import DatePicker from '@/components/DatePicker.vue'
import { 
  PlusIcon, 
  SlidersHorizontalIcon, 
  Calendar, 
  Clock, 
  ChevronRight, 
  ArrowLeftIcon, 
  ArrowRightIcon 
} from 'lucide-vue-next'
import { computed, ref } from 'vue'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import debounce from 'lodash/debounce'

dayjs.extend(relativeTime)

interface LeaveType {
  id: number;
  name: string;
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
  leave_type?: LeaveType;
}

interface Pagination {
  current_page: number;
  last_page: number;
  from: number;
  to: number;
  total: number;
  prev_page_url?: string;
  next_page_url?: string;
}

interface PageProps extends Record<string, any> {
  auth: {
    user: any;
  };
  workspace: {
    slug: string;
    uuid: string;
    name: string;
  };
  leaveRequests: LeaveRequest[];
  leaveTypes: LeaveType[];
  filters: {
    status?: string;
    leave_type_id?: string;
    date_from?: string;
    date_to?: string;
  };
  pagination?: Pagination;
}

const page = usePage<PageProps>();
const { workspace, leaveRequests, leaveTypes, filters: initialFilters, pagination } = page.props;

// Tenant parameters for routing
const tenantParams = {
  tenant_slug: workspace?.slug,
  tenant_uuid: workspace?.uuid
};

// Filter state
const showFilters = ref(false);
const filters = ref({ ...initialFilters });

// Computed properties
const hasActiveFilters = computed(() => 
  Object.values(filters.value).some(value => value && value !== '' && value !== 'all')
);

// Methods
const toggleFilters = () => {
  showFilters.value = !showFilters.value;
};

const applyFilters = debounce(() => {
  // Convert "all" values to empty strings for the backend
  const cleanedFilters = { ...filters.value };
  if (cleanedFilters.status === 'all') cleanedFilters.status = '';
  if (cleanedFilters.leave_type_id === 'all') cleanedFilters.leave_type_id = '';
  
  router.get(route('tenant.leave-requests.index', tenantParams), cleanedFilters, {
    preserveState: true,
    preserveScroll: true,
  });
}, 300);

const clearFilters = () => {
  filters.value = {
    status: 'all',
    leave_type_id: 'all',
    date_from: '',
    date_to: '',
  };
  applyFilters();
};

const goToPage = (page: number) => {
  router.get(route('tenant.leave-requests.index', tenantParams), {
    ...filters.value,
    page
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const cancelRequest = (request: LeaveRequest) => {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.patch(
      route('tenant.leave-requests.cancel', { 
        ...tenantParams, 
        leaveRequest: request.id 
      }),
      {},
      {
        onSuccess: () => {
          // Handle success (notification will be shown by the backend)
        }
      }
    );
  }
};

// Utility functions
const formatDateRange = (startDate: string, endDate: string) => {
  const start = dayjs(startDate);
  const end = dayjs(endDate);
  
  if (start.isSame(end, 'day')) {
    return start.format('MMM D, YYYY');
  }
  
  if (start.isSame(end, 'month')) {
    return `${start.format('MMM D')} - ${end.format('D, YYYY')}`;
  }
  
  return `${start.format('MMM D')} - ${end.format('MMM D, YYYY')}`;
};

const formatRelativeTime = (date: string) => {
  return dayjs(date).fromNow();
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
