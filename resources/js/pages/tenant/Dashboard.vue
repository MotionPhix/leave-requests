<template>
  <TenantLayout>
    <Head :title="`${workspace?.name} - Dashboard`" />
    
    <div class="space-y-8">
      <!-- Header Section -->
      <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-8 text-white">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold mb-2">
              Welcome back, {{ user?.name }}
            </h1>
            <p class="text-indigo-100 text-lg">
              {{ workspace?.name }} Dashboard
            </p>
            <div class="flex items-center mt-3 space-x-4">
              <div class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                <span class="text-sm text-indigo-100">{{ user?.role || 'User' }}</span>
              </div>
              <div v-if="user?.isOwner" class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-yellow-400 rounded-full"></div>
                <span class="text-sm text-indigo-100">Workspace Owner</span>
              </div>
              <div class="flex items-center space-x-2">
                <CalendarIcon class="w-4 h-4 text-indigo-200" />
                <span class="text-sm text-indigo-100">{{ currentDate }}</span>
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

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <!-- Personal Leave Balance (Hidden for owners) -->
        <!-- @vue-ignore -->
        <div v-if="!user?.isOwner" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">My Leave Balance</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.myLeaveBalance || '--' }}</p>
              <p class="text-sm text-gray-500 mt-1">days remaining</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
              <CalendarIcon class="w-6 h-6 text-blue-600" />
            </div>
          </div>
        </div>

        <!-- My Pending Requests (Hidden for owners) -->
        <!-- @vue-ignore -->
        <div v-if="!user?.isOwner" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
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

        <!-- Team Stats (Visible for managers and approvers) -->
        <!-- @vue-ignore -->
        <div v-if="user?.permissions?.canApproveLeave" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Team Requests</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.teamPendingRequests || '--' }}</p>
              <p class="text-sm text-gray-500 mt-1">require approval</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Company Overview (Visible for owners and those who can view all users) -->
        <!-- @vue-ignore -->
        <div v-if="user?.permissions?.canViewAllUsers" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Employees</p>
              <p class="text-3xl font-bold text-gray-900">{{ stats.totalEmployees || '--' }}</p>
              <p class="text-sm text-gray-500 mt-1">active members</p>
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
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- My Recent Leave Requests -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">My Recent Requests</h3>
            <Link 
              :href="route('tenant.leave-requests.index', tenantParams)" 
              class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
            >
              View All
            </Link>
          </div>
          <div class="p-6">
            <div v-if="myRecentRequests.length > 0" class="space-y-4">
              <div 
                v-for="request in myRecentRequests" 
                :key="request.id"
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
              >
                <div class="flex-1">
                  <div class="flex items-center space-x-3">
                    <div class="font-medium text-gray-900">{{ request.type }}</div>
                    <span 
                      class="px-2 py-1 text-xs font-medium rounded-full"
                      :class="getStatusBadgeClass(request.status)"
                    >
                      {{ request.status }}
                    </span>
                  </div>
                  <div class="text-sm text-gray-600 mt-1">
                    {{ formatDateRange(request.start_date, request.end_date) }}
                  </div>
                </div>
                <Link 
                  :href="route('tenant.leave-requests.show', { ...tenantParams, leaveRequest: request.id })"
                  class="text-indigo-600 hover:text-indigo-700"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                  </svg>
                </Link>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <CalendarIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p class="text-gray-500">No recent leave requests</p>
              <Link 
                v-if="canCreateLeaveRequests"
                :href="route('tenant.leave-requests.create', tenantParams)"
                class="inline-flex items-center mt-3 text-sm text-indigo-600 hover:text-indigo-700"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Request Leave
              </Link>
              <div v-else class="mt-3 text-sm text-gray-500">
                As workspace owner, you don't need to request leave
              </div>
            </div>
          </div>
        </div>

        <!-- Team Requests (For Managers) or Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ canApproveRequests ? 'Pending Team Requests' : 'Quick Actions' }}
            </h3>
          </div>
          <div class="p-6">
            <!-- For Managers: Show pending team requests -->
            <div v-if="canApproveRequests && teamPendingRequests.length > 0" class="space-y-4">
              <div 
                v-for="request in teamPendingRequests" 
                :key="request.id"
                class="flex items-center justify-between p-4 bg-amber-50 rounded-lg border border-amber-200"
              >
                <div class="flex-1">
                  <div class="font-medium text-gray-900">{{ request.employee_name }}</div>
                  <div class="text-sm text-gray-600">
                    {{ request.type }} • {{ formatDateRange(request.start_date, request.end_date) }}
                  </div>
                </div>
                <div class="flex space-x-2">
                  <button class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                  </button>
                  <button class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- For Everyone: Quick Actions -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <Link 
                v-if="canCreateLeaveRequests"
                :href="route('tenant.leave-requests.create', tenantParams)" 
                class="flex items-center p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg hover:from-indigo-100 hover:to-purple-100 transition-all duration-200 group"
              >
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                  <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="font-medium text-gray-900">Request Leave</p>
                  <p class="text-sm text-gray-600">Submit a new leave request</p>
                </div>
              </Link>

              <div 
                v-else 
                class="flex items-center p-4 bg-gradient-to-r from-gray-50 to-slate-50 rounded-lg"
              >
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="font-medium text-gray-900">Company Owner</p>
                  <p class="text-sm text-gray-600">You manage leave approvals</p>
                </div>
              </div>

              <Link 
                :href="route('tenant.leave-requests.index', tenantParams)" 
                class="flex items-center p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg hover:from-green-100 hover:to-emerald-100 transition-all duration-200 group"
              >
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="font-medium text-gray-900">View My Requests</p>
                  <p class="text-sm text-gray-600">Check status and history</p>
                </div>
              </Link>

              <Link 
                v-if="canViewTeamStats"
                :href="route('tenant.members.index', tenantParams)" 
                class="flex items-center p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-lg hover:from-orange-100 hover:to-red-100 transition-all duration-200 group"
              >
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                  <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="font-medium text-gray-900">Manage Team</p>
                  <p class="text-sm text-gray-600">View and manage team members</p>
                </div>
              </Link>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Holidays & Leave Summary -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Upcoming Holidays -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Upcoming Holidays</h3>
          </div>
          <div class="p-6">
            <div v-if="upcomingHolidays.length > 0" class="space-y-4">
              <div 
                v-for="holiday in upcomingHolidays" 
                :key="holiday.id"
                class="flex items-center justify-between p-3 bg-green-50 rounded-lg"
              >
                <div>
                  <div class="font-medium text-gray-900">{{ holiday.name }}</div>
                  <div class="text-sm text-gray-600">{{ formatDate(holiday.date) }}</div>
                </div>
                <span 
                  class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800"
                >
                  {{ holiday.type }}
                </span>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 16V10a4 4 0 11-8 0v6"></path>
              </svg>
              <p class="text-gray-500">No upcoming holidays</p>
            </div>
          </div>
        </div>

        <!-- Leave Balance Summary -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Leave Balance Summary</h3>
          </div>
          <div class="p-6">
            <div v-if="leaveSummary && leaveSummary.length > 0" class="space-y-4">
              <div 
                v-for="leave in leaveSummary" 
                :key="leave.type"
                class="space-y-2"
              >
                <div class="flex justify-between items-center">
                  <span class="font-medium text-gray-900">{{ leave.type }}</span>
                  <span class="text-sm text-gray-600">{{ leave.used }}/{{ leave.total }} days</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div 
                    class="h-2 rounded-full transition-all duration-300"
                    :class="leave.color"
                    :style="{ width: `${(leave.used / leave.total) * 100}%` }"
                  ></div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <CalendarIcon class="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <p class="text-gray-500">No leave types configured</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import TenantLayout from '@/layouts/TenantLayout.vue'
import { CalendarIcon } from 'lucide-vue-next'
import { computed } from 'vue'
import dayjs from 'dayjs'

interface UserRole {
  name: string;
}

interface AuthUser {
  id: string | number;
  name: string;
  email: string;
  roles?: UserRole[];
}

interface WorkspaceData {
  slug: string;
  uuid: string;
  name: string;
}

interface LeaveRequest {
  id: number;
  type: string;
  start_date: string;
  end_date: string;
  status: string;
  employee_name?: string;
}

interface Holiday {
  id: number;
  name: string;
  date: string;
  type: string;
}

interface LeaveSummary {
  type: string;
  used: number;
  total: number;
  color: string;
}

interface Stats {
  myLeaveBalance?: number;
  myPendingRequests?: number;
  teamPendingRequests?: number;
  totalEmployees?: number;
}

interface PageProps extends /* @vue-ignore */ Record<string, any> {
  auth: {
    user: AuthUser;
  };
  workspace: WorkspaceData;
  stats: Stats;
  myRecentRequests: LeaveRequest[];
  teamPendingRequests: LeaveRequest[];
  upcomingHolidays: Holiday[];
  leaveSummary: LeaveSummary[] | null;
}

const page = usePage<PageProps>();
const { auth, workspace, stats, myRecentRequests, teamPendingRequests, upcomingHolidays, leaveSummary } = page.props;
const user = auth.user;

// Tenant route parameters
const tenantParams = {
  tenant_slug: workspace?.slug,
  tenant_uuid: workspace?.uuid
};

// Current date
const currentDate = computed(() => dayjs().format('MMMM D, YYYY'));

// User role display
const userRole = computed(() => {
  if (!user.roles || user.roles.length === 0) return 'Employee';
  return user.roles[0].name;
});

// Permission checks
const userPermissions = computed(() => {
  // This would come from the backend in a real implementation
  // For now, we'll check based on roles
  const roleNames = user.roles?.map(r => r.name) || [];
  return {
    canViewTeamStats: roleNames.some(role => 
      ['Workspace Owner', 'Super Admin', 'HR Manager', 'Department Manager', 'Team Lead'].includes(role)
    ),
    canViewCompanyStats: roleNames.some(role => 
      ['Workspace Owner', 'Super Admin', 'HR Manager'].includes(role)
    ),
    canApproveRequests: roleNames.some(role => 
      ['Workspace Owner', 'Super Admin', 'HR Manager', 'Department Manager', 'Team Lead'].includes(role)
    ),
    canManageMembers: roleNames.some(role => 
      ['Workspace Owner', 'Super Admin', 'HR Manager'].includes(role)
    ),
    // Workspace owners CANNOT request leave - they own the company!
    // Only employees and managers can request leave, not the owners
    canCreateLeaveRequests: roleNames.some(role => 
      ['Employee', 'HR Manager', 'Department Manager', 'Team Lead', 'Project Manager', 'Senior Employee'].includes(role)
    ) && !roleNames.includes('Workspace Owner') && !roleNames.includes('Super Admin')
  };
});

const canViewTeamStats = computed(() => userPermissions.value.canViewTeamStats);
const canViewCompanyStats = computed(() => userPermissions.value.canViewCompanyStats);
const canApproveRequests = computed(() => userPermissions.value.canApproveRequests);
const canCreateLeaveRequests = computed(() => userPermissions.value.canCreateLeaveRequests);

// Utility functions
const formatDate = (date: string) => dayjs(date).format('MMM D, YYYY');

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
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'cancelled': 'bg-gray-100 text-gray-800',
  };
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-800';
};

// Default props
defineProps<PageProps>();
</script>
