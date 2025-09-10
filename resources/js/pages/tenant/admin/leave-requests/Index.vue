<script setup lang="ts">
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';
import {
  Calendar,
  Users,
  Search,
  Clock,
  CheckCircle2,
  XCircle,
  CalendarDays,
  MessageSquare,
  FileText,
  AlertTriangle
} from 'lucide-vue-next';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

dayjs.extend(relativeTime);

type LeaveRequest = {
  id: number;
  uuid: string;
  user: {
    name: string;
    email: string;
    position?: string;
    department?: string;
  };
  leave_type: {
    name: string;
    requires_documentation: boolean;
  };
  start_date: string;
  end_date: string;
  status: string;
  reason?: string;
  created_at: string;
  updated_at: string;
}

type LeaveType = {
  id: number;
  name: string;
}

const props = defineProps<{
  leaveRequests: {
    data: LeaveRequest[];
    total: number;
    from: number;
    to: number;
    links: Array<{
      label: string;
      url: string | null;
      active: boolean;
    }>;
  };
  leaveTypes: LeaveType[];
  filters: {
    search?: string;
    status?: string;
    type?: string;
  };
}>();

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const type = ref(props.filters?.type || 'all');
const isLoading = ref(false);

watch([search, status, type], debounce(() => {
  isLoading.value = true;
  
  // Convert "all" values to empty strings for the backend
  const cleanedFilters = {
    search: search.value,
    status: status.value === 'all' ? '' : status.value,
    type: type.value === 'all' ? '' : type.value,
  };
  
  router.get(
    route('tenant.admin.leave-requests.index', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
    }),
    cleanedFilters,
    {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => isLoading.value = false
    }
  );
}, 300));

const updateStatus = (leaveRequestUuid: string, newStatus: string, comment?: string) => {
  router.patch(
    route('tenant.admin.leave-requests.update', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
      leaveRequest: leaveRequestUuid,
    }),
    { status: newStatus, comment },
    {
      onSuccess: () => {
        toast.success(`Leave request ${newStatus === 'approved' ? 'approved' : 'rejected'}`);
      },
      onError: () => {
        toast.error('Failed to update leave request');
      }
    }
  );
};

const getStatusBadgeVariant = (status: string) => {
  switch (status) {
    case 'approved':
      return 'default';
    case 'rejected':
      return 'destructive';
    case 'pending':
      return 'secondary';
    default:
      return 'outline';
  }
};

const getDurationInDays = (start: string, end: string) => {
  return dayjs(end).diff(dayjs(start), 'days') + 1;
};

const stats = computed(() => ({
  total: props.leaveRequests.total,
  pending: props.leaveRequests.data.filter(l => l.status === 'pending').length,
  approved: props.leaveRequests.data.filter(l => l.status === 'approved').length,
  rejected: props.leaveRequests.data.filter(l => l.status === 'rejected').length
}));

const getPriorityLevel = (request: LeaveRequest) => {
  const daysTillStart = dayjs(request.start_date).diff(dayjs(), 'days');
  if (daysTillStart <= 3 && request.status === 'pending') return 'urgent';
  if (daysTillStart <= 7 && request.status === 'pending') return 'high';
  return 'normal';
};

const getPriorityColor = (priority: string) => {
  switch (priority) {
    case 'urgent': return 'text-red-600';
    case 'high': return 'text-orange-600';
    default: return 'text-muted-foreground';
  }
};
</script>

<template>
  <TenantLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Leave Requests Admin</h1>
          <p class="text-muted-foreground">
            Manage and approve leave requests for {{ workspace.name }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Badge v-if="stats.pending > 0" variant="secondary" class="gap-1">
            <Clock class="h-3 w-3" />
            {{ stats.pending }} Pending
          </Badge>
          <Badge variant="outline" class="gap-1">
            <Users class="h-3 w-3" />
            {{ stats.total }} Total
          </Badge>
        </div>
      </div>

      <!-- Stats Overview -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Requests</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ stats.total }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending Review</CardTitle>
            <Clock class="h-4 w-4 text-orange-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-orange-600">{{ stats.pending }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Approved</CardTitle>
            <CheckCircle2 class="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-green-600">{{ stats.approved }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Rejected</CardTitle>
            <XCircle class="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-red-600">{{ stats.rejected }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Filters -->
      <Card>
        <CardHeader>
          <CardTitle>Filter Requests</CardTitle>
          <CardDescription>Search and filter leave requests by status, type, or employee</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-col md:flex-row gap-4">
            <div class="relative flex-1">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
              <Input
                v-model="search"
                placeholder="Search by employee name..."
                class="pl-9"
              />
            </div>

            <Select v-model="status">
              <SelectTrigger class="w-full md:w-[180px]">
                <SelectValue placeholder="All Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Status</SelectItem>
                <SelectItem value="pending">Pending</SelectItem>
                <SelectItem value="approved">Approved</SelectItem>
                <SelectItem value="rejected">Rejected</SelectItem>
              </SelectContent>
            </Select>

            <Select v-model="type">
              <SelectTrigger class="w-full md:w-[180px]">
                <SelectValue placeholder="All Types" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Types</SelectItem>
                <SelectItem
                  v-for="leaveType in leaveTypes"
                  :key="leaveType.id"
                  :value="leaveType.id.toString()">
                  {{ leaveType.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <!-- Leave Requests List -->
      <div v-if="leaveRequests.data.length > 0" class="grid gap-6 lg:grid-cols-2">
        <Card
          v-for="leave in leaveRequests.data"
          :key="leave.id"
          class="hover:shadow-md transition-shadow"
          :class="{
            'border-orange-200 bg-orange-50/50': getPriorityLevel(leave) === 'high',
            'border-red-200 bg-red-50/50': getPriorityLevel(leave) === 'urgent'
          }"
        >
          <CardContent class="p-6">
            <!-- Header with Employee Info and Status -->
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                  <span class="text-lg font-medium">
                    {{ leave.user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <h3 class="font-semibold">{{ leave.user.name }}</h3>
                  <p class="text-sm text-muted-foreground">{{ leave.user.email }}</p>
                  <p v-if="leave.user.position" class="text-xs text-muted-foreground">
                    {{ leave.user.position }}{{ leave.user.department ? ` • ${leave.user.department}` : '' }}
                  </p>
                </div>
              </div>

              <div class="flex flex-col items-end gap-2">
                <Badge :variant="getStatusBadgeVariant(leave.status)" class="capitalize">
                  <component
                    :is="leave.status === 'approved' ? CheckCircle2 : leave.status === 'rejected' ? XCircle : Clock"
                    class="w-3 h-3 mr-1"
                  />
                  {{ leave.status }}
                </Badge>
                <div v-if="getPriorityLevel(leave) !== 'normal'" class="flex items-center gap-1">
                  <AlertTriangle :class="getPriorityColor(getPriorityLevel(leave))" class="w-3 h-3" />
                  <span :class="getPriorityColor(getPriorityLevel(leave))" class="text-xs font-medium capitalize">
                    {{ getPriorityLevel(leave) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Leave Details -->
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <p class="text-sm text-muted-foreground mb-1">Leave Type</p>
                <div class="flex items-center gap-2">
                  <Badge variant="outline">{{ leave.leave_type.name }}</Badge>
                  <FileText v-if="leave.leave_type.requires_documentation" class="w-3 h-3 text-muted-foreground" />
                </div>
              </div>
              <div>
                <p class="text-sm text-muted-foreground mb-1">Duration</p>
                <p class="font-medium">{{ getDurationInDays(leave.start_date, leave.end_date) }} days</p>
              </div>
            </div>

            <!-- Date Range -->
            <div class="flex items-start gap-3 mb-4 bg-muted/50 p-3 rounded-lg">
              <CalendarDays class="w-5 h-5 text-muted-foreground mt-0.5" />
              <div class="flex-1">
                <p class="font-medium">
                  {{ dayjs(leave.start_date).format('MMM D') }} - {{ dayjs(leave.end_date).format('MMM D, YYYY') }}
                </p>
                <p class="text-sm text-muted-foreground mt-1">
                  Submitted {{ dayjs(leave.created_at).fromNow() }}
                </p>
                <p v-if="leave.reason" class="text-sm text-muted-foreground mt-2 line-clamp-2">
                  "{{ leave.reason }}"
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t">
              <div class="flex gap-2">
                <Button
                  v-if="leave.status === 'pending'"
                  variant="outline"
                  size="sm"
                  class="text-red-600 border-red-200 hover:bg-red-50"
                  @click="updateStatus(leave.uuid, 'rejected')">
                  <XCircle class="w-4 h-4 mr-1" />
                  Reject
                </Button>
                <Button
                  v-if="leave.status === 'pending'"
                  size="sm"
                  class="bg-green-600 hover:bg-green-700"
                  @click="updateStatus(leave.uuid, 'approved')">
                  <CheckCircle2 class="w-4 h-4 mr-1" />
                  Approve
                </Button>
              </div>

              <Button
                variant="ghost"
                size="sm"
                :as="Link"
                :href="route('tenant.admin.leave-requests.show', {
                  tenant_slug: workspace.slug,
                  tenant_uuid: workspace.uuid,
                  leaveRequest: leave.uuid
                })">
                <MessageSquare class="w-4 h-4 mr-1" />
                Details
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="grid gap-6 lg:grid-cols-2">
        <Card v-for="n in 4" :key="n">
          <CardContent class="p-6">
            <div class="space-y-4">
              <Skeleton class="h-12 w-full" />
              <div class="grid grid-cols-2 gap-4">
                <Skeleton class="h-8 w-full" />
                <Skeleton class="h-8 w-full" />
              </div>
              <Skeleton class="h-20 w-full" />
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Empty State -->
      <Card v-if="!isLoading && leaveRequests.data.length === 0">
        <CardContent class="p-12 text-center">
          <Calendar class="w-16 h-16 mx-auto text-muted-foreground mb-4" />
          <h3 class="text-xl font-semibold mb-2">No Leave Requests Found</h3>
          <p class="text-muted-foreground mb-4">
            {{ search || status || type ? 'No requests match your current filters.' : 'No leave requests have been submitted yet.' }}
          </p>
          <Button
            v-if="search || status || type"
            variant="outline"
            @click="() => { search = ''; status = ''; type = ''; }">
            Clear Filters
          </Button>
        </CardContent>
      </Card>

      <!-- Pagination -->
      <div v-if="leaveRequests.data.length > 0" class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          Showing {{ leaveRequests.from }} to {{ leaveRequests.to }} of {{ leaveRequests.total }} requests
        </p>
        <div class="flex gap-1">
          <Button
            v-for="link in leaveRequests.links"
            :key="link.label"
            :disabled="!link.url || isLoading"
            :variant="link.active ? 'default' : 'outline'"
            size="sm"
            @click="link.url && router.get(link.url)"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </TenantLayout>
</template>
