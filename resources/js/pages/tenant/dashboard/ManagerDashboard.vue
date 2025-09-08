<template>
  <TenantLayout>
    <Head :title="`${workspace?.name} - Manager Dashboard`" />
    
    <div class="space-y-8">
      <!-- Header Section -->
      <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-8 text-white">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold mb-2">
              Welcome back, {{ user?.name }}
            </h1>
            <p class="text-green-100 text-lg">
              {{ workspace?.name }} - Manager Dashboard
            </p>
            <div class="flex items-center mt-3 space-x-4">
              <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-emerald-400 rounded-full"></div>
                <span class="text-sm text-green-100">{{ user?.role || 'Manager' }}</span>
              </div>
              <div class="flex items-center space-x-2">
                <CalendarIcon class="w-4 h-4 text-green-200" />
                <span class="text-sm text-green-100">{{ currentDate }}</span>
              </div>
            </div>
          </div>
          <div class="hidden md:block">
            <div class="w-24 h-24 bg-white/20 rounded-2xl flex items-center justify-center">
              <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Manager-specific Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <!-- My Leave Balance -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">My Leave Balance</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.myLeaveBalance || '0' }}</p>
              <p class="text-sm text-gray-500 mt-1">days remaining</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <CalendarIcon class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>

        <!-- Team Requests -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Team Requests</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.teamPendingRequests || '0' }}</p>
              <p class="text-sm text-gray-500 mt-1">require approval</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- My Pending Requests -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">My Pending Requests</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.myPendingRequests || '0' }}</p>
              <p class="text-sm text-gray-500 mt-1">awaiting approval</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Team Members -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Team Members</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.teamMembers || '0' }}</p>
              <p class="text-sm text-gray-500 mt-1">direct reports</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        <!-- Team Leave Requests Requiring Approval -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Team Leave Requests</h3>
              <Link 
                :href="route('tenant.leave-requests.index', tenantParams)" 
                class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
              >
                View All
              </Link>
            </div>
          </div>
          <div class="p-6">
            <div v-if="teamPendingRequests && teamPendingRequests.length > 0" class="space-y-4">
              <div 
                v-for="request in teamPendingRequests.slice(0, 5)" 
                :key="request.id"
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
              >
                <div class="flex-1">
                  <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                      <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                        <span class="text-sm font-medium text-indigo-600">
                          {{ request.user?.name?.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="text-sm font-medium text-gray-900">{{ request.user?.name }}</p>
                      <p class="text-sm text-gray-500">{{ request.leave_type?.name }}</p>
                    </div>
                  </div>
                </div>
                <div class="flex items-center space-x-4">
                  <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">
                      {{ formatDate(request.start_date) }} - {{ formatDate(request.end_date) }}
                    </p>
                    <p class="text-xs text-gray-500">{{ request.days }} days</p>
                  </div>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    {{ request.status }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <p class="mt-2 text-sm text-gray-500">No team requests to review</p>
            </div>
          </div>
        </div>

        <!-- My Leave Balance Summary & Recent Requests -->
        <div class="space-y-6">
          <!-- My Leave Balance Summary -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-semibold text-gray-900">My Leave Balance</h3>
            </div>
            <div class="p-6">
              <div v-if="leaveSummary && leaveSummary.length > 0" class="space-y-4">
                <div 
                  v-for="leave in leaveSummary" 
                  :key="leave.type"
                  class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                >
                  <div class="flex items-center space-x-3">
                    <div class="w-3 h-8 rounded-full" :style="{ backgroundColor: leave.color }"></div>
                    <div>
                      <p class="text-sm font-medium text-gray-900">{{ leave.type }}</p>
                      <p class="text-xs text-gray-500">{{ leave.used }}/{{ leave.total }} days used</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-lg font-bold text-gray-900">{{ leave.total - leave.used }}</p>
                    <p class="text-xs text-gray-500">remaining</p>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <CalendarIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                <p class="text-gray-500">No leave types available</p>
              </div>
            </div>
          </div>

          <!-- My Recent Requests (compact version) -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">My Recent Requests</h3>
                <Link 
                  :href="route('tenant.leave-requests.create', tenantParams)" 
                  class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                >
                  Request Leave
                </Link>
              </div>
            </div>
            <div class="p-6">
              <div v-if="myRecentRequests && myRecentRequests.length > 0" class="space-y-3">
                <div 
                  v-for="request in myRecentRequests.slice(0, 3)" 
                  :key="request.id"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ request.leave_type?.name }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(request.start_date) }} - {{ formatDate(request.end_date) }}</p>
                  </div>
                  <span 
                    :class="getStatusBadgeClass(request.status)" 
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  >
                    {{ request.status }}
                  </span>
                </div>
              </div>
              <div v-else class="text-center py-6">
                <p class="text-sm text-gray-500 mb-3">No recent requests</p>
                <Link 
                  :href="route('tenant.leave-requests.create', tenantParams)" 
                  class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-md hover:bg-indigo-700 transition-colors"
                >
                  Request Leave
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Link 
            :href="route('tenant.leave-requests.index', tenantParams)"
            class="flex flex-col items-center justify-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors"
          >
            <svg class="w-8 h-8 text-orange-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-900">Approve Requests</span>
          </Link>

          <Link 
            :href="route('tenant.leave-requests.create', tenantParams)"
            class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
          >
            <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span class="text-sm font-medium text-gray-900">Request Leave</span>
          </Link>

          <Link 
            :href="route('tenant.users.index', tenantParams)"
            class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
          >
            <svg class="w-8 h-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-900">Team Members</span>
          </Link>

          <Link 
            :href="route('tenant.reports.index', tenantParams)"
            class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors"
          >
            <svg class="w-8 h-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-900">Team Reports</span>
          </Link>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { CalendarIcon } from 'lucide-vue-next';
import { computed } from 'vue';

interface LeaveSummary {
  type: string;
  used: number;
  total: number;
  color: string;
}

interface Stats {
  myLeaveBalance: number;
  myPendingRequests: number;
  teamPendingRequests: number;
  teamMembers: number;
}

interface User {
  id: number;
  name: string;
  email?: string;
}

interface LeaveType {
  id: number;
  name: string;
}

interface LeaveRequest {
  id: number;
  user?: User;
  leave_type: LeaveType;
  start_date: string;
  end_date: string;
  days: number;
  status: string;
  reason?: string;
}

interface WorkspaceData {
  uuid: string;
  slug: string;
  name: string;
}

interface UserData {
  name: string;
  email: string;
  role: string;
  isOwner: boolean;
}

interface PageProps extends Record<string, unknown> {
  auth: {
    user: UserData;
  };
  workspace: WorkspaceData;
  stats: Stats;
  myRecentRequests: LeaveRequest[];
  teamPendingRequests: LeaveRequest[];
  leaveSummary: LeaveSummary[] | null;
}

const page = usePage<PageProps>();
const { auth, workspace, stats, myRecentRequests, teamPendingRequests, leaveSummary } = page.props;
const user = auth.user;

// Tenant route parameters
const tenantParams = {
  tenant_slug: workspace?.slug,
  tenant_uuid: workspace?.uuid,
};

const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  });
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric',
    year: 'numeric'
  });
};

const getStatusBadgeClass = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'approved':
      return 'bg-green-100 text-green-800';
    case 'rejected':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};
</script>
