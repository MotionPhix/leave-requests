<template>
  <TenantLayout>
    <Head :title="`${workspace?.name} - Owner Dashboard`" />
    
    <div class="space-y-8">
      <!-- Header Section -->
      <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-8 text-white">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold mb-2">
              Welcome back, {{ user?.name }}
            </h1>
            <p class="text-purple-100 text-lg">
              {{ workspace?.name }} - Owner Dashboard
            </p>
            <div class="flex items-center mt-3 space-x-4">
              <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                <span class="text-sm text-purple-100">Workspace Owner</span>
              </div>
              <div class="flex items-center space-x-2">
                <CalendarIcon class="w-4 h-4 text-purple-200" />
                <span class="text-sm text-purple-100">{{ currentDate }}</span>
              </div>
            </div>
          </div>
          <div class="hidden md:block">
            <div class="w-24 h-24 bg-white/20 rounded-2xl flex items-center justify-center">
              <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Owner-specific Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <!-- Total Employees -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Employees</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.totalEmployees || '0' }}</p>
              <p class="text-sm text-gray-500 mt-1">active members</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.companyPendingRequests || '0' }}</p>
              <p class="text-sm text-gray-500 mt-1">require your attention</p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- This Month's Approved Leaves -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Approved This Month</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.thisMonthApproved || '0' }}</p>
              <p class="text-sm text-gray-500 mt-1">leave requests</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Department Count -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Departments</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.totalDepartments || '0' }}</p>
              <p class="text-sm text-gray-500 mt-1">managed departments</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        <!-- Recent Leave Requests Requiring Approval -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Recent Leave Requests</h3>
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
              <p class="mt-2 text-sm text-gray-500">No pending requests to review</p>
            </div>
          </div>
        </div>

        <!-- Upcoming Holidays -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-gray-900">Upcoming Holidays</h3>
              <Link 
                :href="route('tenant.holidays.index', tenantParams)" 
                class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
              >
                Manage
              </Link>
            </div>
          </div>
          <div class="p-6">
            <div v-if="upcomingHolidays && upcomingHolidays.length > 0" class="space-y-4">
              <div 
                v-for="holiday in upcomingHolidays.slice(0, 5)" 
                :key="holiday.id"
                class="flex items-center justify-between p-4 bg-blue-50 rounded-lg"
              >
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <CalendarIcon class="w-5 h-5 text-blue-600" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ holiday.name }}</p>
                    <p class="text-sm text-gray-500">{{ formatDate(holiday.date) }}</p>
                  </div>
                </div>
                <span v-if="holiday.is_recurring" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  Recurring
                </span>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <CalendarIcon class="mx-auto h-12 w-12 text-gray-400" />
              <p class="mt-2 text-sm text-gray-500">No upcoming holidays</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Link 
            :href="route('tenant.users.index', tenantParams)"
            class="flex flex-col items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
          >
            <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-900">Manage Users</span>
          </Link>

          <Link 
            :href="route('tenant.departments.index', tenantParams)"
            class="flex flex-col items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors"
          >
            <svg class="w-8 h-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            <span class="text-sm font-medium text-gray-900">Departments</span>
          </Link>

          <Link 
            :href="route('tenant.leave-types.index', tenantParams)"
            class="flex flex-col items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
          >
            <svg class="w-8 h-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            <span class="text-sm font-medium text-gray-900">Leave Types</span>
          </Link>

          <Link 
            :href="route('tenant.reports.index', tenantParams)"
            class="flex flex-col items-center justify-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors"
          >
            <svg class="w-8 h-8 text-orange-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-900">Reports</span>
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

interface Stats {
  totalEmployees: number;
  companyPendingRequests: number;
  thisMonthApproved: number;
  totalDepartments: number;
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
  user: User;
  leave_type: LeaveType;
  start_date: string;
  end_date: string;
  days: number;
  status: string;
  reason?: string;
}

interface Holiday {
  id: number;
  name: string;
  date: string;
  is_recurring: boolean;
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
  teamPendingRequests: LeaveRequest[];
  upcomingHolidays: Holiday[];
}

const page = usePage<PageProps>();
const { auth, workspace, stats, teamPendingRequests, upcomingHolidays } = page.props;
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
</script>
