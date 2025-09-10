<template>
  <TenantLayout>
    <Head title="Leave Request Management" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Leave Request Management</h1>
          <p class="text-muted-foreground">
            Review and manage all leave requests in your workspace
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Badge variant="outline">{{ leaveRequests.total }} Total Requests</Badge>
        </div>
      </div>

      <!-- Filters -->
      <div class="flex items-center gap-4 p-4 bg-card border rounded-lg">
        <div class="flex-1">
          <Input
            v-model="searchForm.search"
            placeholder="Search by employee name..."
            type="search"
            @input="debouncedSearch"
          />
        </div>
        <Select v-model="searchForm.status" @update:model-value="applyFilters">
          <SelectTrigger class="w-40">
            <SelectValue placeholder="Status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="approved">Approved</SelectItem>
            <SelectItem value="rejected">Rejected</SelectItem>
          </SelectContent>
        </Select>
        <Select v-model="searchForm.type" @update:model-value="applyFilters">
          <SelectTrigger class="w-40">
            <SelectValue placeholder="Leave Type" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Types</SelectItem>
            <SelectItem v-for="type in leaveTypes" :key="type.id" :value="type.id.toString()">
              {{ type.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Leave Requests Table -->
      <div class="bg-card border rounded-lg">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b">
              <tr class="text-left">
                <th class="p-4 font-medium text-muted-foreground">Employee</th>
                <th class="p-4 font-medium text-muted-foreground">Leave Type</th>
                <th class="p-4 font-medium text-muted-foreground">Dates</th>
                <th class="p-4 font-medium text-muted-foreground">Duration</th>
                <th class="p-4 font-medium text-muted-foreground">Status</th>
                <th class="p-4 font-medium text-muted-foreground">Submitted</th>
                <th class="p-4 font-medium text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="request in leaveRequests.data"
                :key="request.id"
                class="border-b hover:bg-muted/50 transition-colors"
              >
                <td class="p-4">
                  <div class="flex items-center gap-3">
                    <div class="h-8 w-8 bg-primary/10 rounded-full flex items-center justify-center">
                      <span class="text-xs font-medium">{{ request.user.name.charAt(0).toUpperCase() }}</span>
                    </div>
                    <div>
                      <div class="font-medium">{{ request.user.name }}</div>
                      <div class="text-sm text-muted-foreground">{{ request.user.email }}</div>
                    </div>
                  </div>
                </td>
                <td class="p-4">
                  <Badge variant="outline">{{ request.leave_type.name }}</Badge>
                </td>
                <td class="p-4">
                  <div class="text-sm">
                    <div>{{ formatDate(request.start_date) }}</div>
                    <div class="text-muted-foreground">to {{ formatDate(request.end_date) }}</div>
                  </div>
                </td>
                <td class="p-4">
                  <span class="text-sm">{{ calculateDuration(request.start_date, request.end_date) }} days</span>
                </td>
                <td class="p-4">
                  <Badge 
                    :variant="getStatusVariant(request.status)"
                    class="capitalize"
                  >
                    {{ request.status }}
                  </Badge>
                </td>
                <td class="p-4">
                  <span class="text-sm text-muted-foreground">
                    {{ formatDate(request.created_at) }}
                  </span>
                </td>
                <td class="p-4">
                  <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" as-child>
                      <Link
                        :href="route('tenant.management.leave-requests.show', {
                          tenant_slug: workspace.slug,
                          tenant_uuid: workspace.uuid,
                          leaveRequest: request.id
                        })"
                      >
                        <Eye class="h-3 w-3 mr-1" />
                        View
                      </Link>
                    </Button>
                    <div v-if="request.status === 'pending'" class="flex gap-1">
                      <Button
                        size="sm"
                        variant="default"
                        @click="updateStatus(request, 'approved')"
                        :disabled="processing[request.id]"
                      >
                        <Check class="h-3 w-3 mr-1" />
                        Approve
                      </Button>
                      <Button
                        size="sm"
                        variant="destructive"
                        @click="updateStatus(request, 'rejected')"
                        :disabled="processing[request.id]"
                      >
                        <X class="h-3 w-3 mr-1" />
                        Reject
                      </Button>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="leaveRequests.last_page > 1" class="p-4 border-t">
          <div class="flex justify-between items-center">
            <div class="text-sm text-muted-foreground">
              Showing {{ leaveRequests.from }} to {{ leaveRequests.to }} of {{ leaveRequests.total }} results
            </div>
            <div class="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                :disabled="leaveRequests.current_page <= 1"
                @click="goToPage(leaveRequests.current_page - 1)"
              >
                <ChevronLeft class="h-4 w-4" />
                Previous
              </Button>
              <span class="text-sm px-2">
                Page {{ leaveRequests.current_page }} of {{ leaveRequests.last_page }}
              </span>
              <Button
                variant="outline"
                size="sm"
                :disabled="leaveRequests.current_page >= leaveRequests.last_page"
                @click="goToPage(leaveRequests.current_page + 1)"
              >
                Next
                <ChevronRight class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="leaveRequests.data.length === 0" class="text-center py-12">
          <div class="mx-auto w-24 h-24 bg-muted rounded-full flex items-center justify-center mb-4">
            <FileText class="h-8 w-8 text-muted-foreground" />
          </div>
          <h3 class="text-lg font-medium text-foreground mb-2">No leave requests found</h3>
          <p class="text-muted-foreground">
            {{ searchForm.search || (searchForm.status && searchForm.status !== 'all') || (searchForm.type && searchForm.type !== 'all') ? 'Try adjusting your filters.' : 'No leave requests have been submitted yet.' }}
          </p>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Eye,
  Check,
  X,
  ChevronLeft,
  ChevronRight,
  FileText
} from 'lucide-vue-next';
import { ref, reactive } from 'vue';
import { debounce } from 'lodash';

interface User {
  id: number;
  name: string;
  email: string;
}

interface LeaveType {
  id: number;
  name: string;
}

interface LeaveRequest {
  id: number;
  user: User;
  leave_type: LeaveType;
  start_date: string;
  end_date: string;
  status: 'pending' | 'approved' | 'rejected';
  created_at: string;
}

interface PaginatedLeaveRequests {
  data: LeaveRequest[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

const props = defineProps<{
  leaveRequests: PaginatedLeaveRequests;
  leaveTypes: LeaveType[];
  filters: {
    search?: string;
    status?: string;
    type?: string;
  };
}>();

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };

const searchForm = reactive({
  search: props.filters.search || '',
  status: props.filters.status || 'all',
  type: props.filters.type || 'all',
});

const processing = ref<Record<number, boolean>>({});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const calculateDuration = (startDate: string, endDate: string) => {
  const start = new Date(startDate);
  const end = new Date(endDate);
  const diffTime = Math.abs(end.getTime() - start.getTime());
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
};

const getStatusVariant = (status: string) => {
  switch (status) {
    case 'approved': return 'default';
    case 'rejected': return 'destructive';
    case 'pending': return 'secondary';
    default: return 'outline';
  }
};

const debouncedSearch = debounce(() => {
  applyFilters();
}, 300);

const applyFilters = () => {
  // Convert "all" values to empty strings for the backend
  const cleanedForm = { ...searchForm };
  if (cleanedForm.status === 'all') cleanedForm.status = '';
  if (cleanedForm.type === 'all') cleanedForm.type = '';
  
  router.get(
    route('tenant.management.leave-requests.index', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid
    }),
    cleanedForm,
    {
      preserveState: true,
      preserveScroll: true,
    }
  );
};

const goToPage = (page: number) => {
  router.get(
    route('tenant.management.leave-requests.index', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
      page,
      ...searchForm
    }),
    {},
    {
      preserveState: true,
      preserveScroll: true,
    }
  );
};

const updateStatus = (request: LeaveRequest, status: 'approved' | 'rejected') => {
  const notes = prompt(`Add notes for ${status === 'approved' ? 'approval' : 'rejection'} (optional):`);
  
  processing.value[request.id] = true;
  
  router.patch(
    route('tenant.management.leave-requests.update', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
      leaveRequest: request.id
    }),
    {
      status,
      manager_notes: notes || null
    },
    {
      preserveScroll: true,
      onFinish: () => {
        processing.value[request.id] = false;
      }
    }
  );
};
</script>
