<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import { Modal, ModalLink } from '@inertiaui/modal-vue'
import TenantLayout from '@/layouts/TenantLayout.vue'
import { computed } from 'vue'
import type { DashboardProps } from '@/types'
import {
  Building,
  Users,
  Clock,
  CheckCircle,
  Building2,
  FileText,
  CalendarDays,
  BarChart3,
  UserPlus,
  TrendingUp,
  Award,
  Plus
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

// Get page instance
const page = usePage()

// Define props using centralized types with defaults
const props = withDefaults(defineProps<DashboardProps>(), {
  departments: () => [],
  recentLeaveRequests: () => [],
  chartData: () => ({
    leaveTrends: [],
    departments: []
  }),
  recentEmployees: () => []
})

// Computed values from page props
const user = computed(() => page.props.auth?.user)
const workspace = computed(() => page.props.workspace)

// Tenant parameters for routes - use both tenant_slug and tenant_uuid as required by Ziggy
const tenantParams = computed(() => {
  const workspace = page.props.workspace
  return workspace ? {
    tenant_slug: workspace.slug,
    tenant_uuid: workspace.uuid
  } : {}
})

// Current date display
const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
})

// Date formatting helper
const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

// Chart configurations with neutral color scheme
const leaveTrendsOptions = computed(() => ({
  chart: {
    type: 'line',
    toolbar: { show: false },
    background: 'transparent',
    foreColor: 'oklch(0.5 0.02 260)'
  },
  grid: {
    borderColor: 'oklch(0.9 0.02 260)',
    strokeDashArray: 3
  },
  colors: ['oklch(0.3 0.02 260)', 'oklch(0.4 0.02 260)', 'oklch(0.5 0.02 260)'],
  stroke: {
    curve: 'smooth' as const,
    width: 3
  },
  xaxis: {
    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    labels: { style: { colors: 'oklch(0.5 0.02 260)' } }
  },
  yaxis: {
    labels: { style: { colors: 'oklch(0.5 0.02 260)' } }
  },
  legend: {
    labels: { colors: 'oklch(0.5 0.02 260)' }
  }
}))

const departmentChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
    background: 'transparent',
    foreColor: 'oklch(0.5 0.02 260)'
  },
  grid: {
    borderColor: 'oklch(0.9 0.02 260)',
    strokeDashArray: 3
  },
  colors: ['oklch(0.3 0.02 260)', 'oklch(0.4 0.02 260)', 'oklch(0.5 0.02 260)', 'oklch(0.6 0.02 260)'],
  plotOptions: {
    bar: {
      borderRadius: 8,
      columnWidth: '60%'
    }
  },
  xaxis: {
    categories: ['Engineering', 'Marketing', 'Sales', 'HR', 'Finance'],
    labels: { style: { colors: 'oklch(0.5 0.02 260)' } }
  },
  yaxis: {
    labels: { style: { colors: 'oklch(0.5 0.02 260)' } }
  },
  legend: {
    labels: { colors: 'oklch(0.5 0.02 260)' }
  }
}))

// Greeting based on time
const greeting = computed(() => {
  const hour = new Date().getHours()
  if (hour < 12) return 'Good morning'
  if (hour < 17) return 'Good afternoon'
  return 'Good evening'
})
</script>

<template>
  <TenantLayout>
    <Head :title="`${workspace?.name} - Owner Dashboard`" />

    <div class="py-8">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100 mb-2">
              {{ greeting }}, {{ user?.name }}
            </h1>
            <p class="text-lg text-neutral-600 dark:text-neutral-400">
              {{ workspace?.name }} - Owner Dashboard
            </p>
          </div>
          <div class="flex items-center space-x-4">
            <div class="text-right">
              <p class="font-medium text-neutral-900 dark:text-neutral-100">Today</p>
              <span class="text-xs text-neutral-200 dark:text-neutral-300">
                {{ currentDate }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 p-6">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-neutral-100 dark:bg-neutral-700 rounded-lg flex items-center justify-center shrink-0">
              <Users class="w-6 h-6 text-neutral-600 dark:text-neutral-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Total Employees</p>
              <p class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">{{ props.stats.totalEmployees || '0' }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 p-6">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-neutral-100 dark:bg-neutral-700 rounded-lg flex items-center justify-center shrink-0">
              <Clock class="w-6 h-6 text-neutral-600 dark:text-neutral-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Pending Requests</p>
              <p class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">{{ props.stats.companyPendingRequests || '0' }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 p-6">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-neutral-100 dark:bg-neutral-700 rounded-lg flex items-center justify-center shrink-0">
              <CheckCircle class="w-6 h-6 text-neutral-600 dark:text-neutral-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Approved This Month</p>
              <p class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">{{ props.stats.thisMonthApproved || '0' }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 p-6">
          <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-neutral-100 dark:bg-neutral-700 rounded-lg flex items-center justify-center shrink-0">
              <Building class="w-6 h-6 text-neutral-600 dark:text-neutral-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Departments</p>
              <p class="text-3xl font-bold text-neutral-900 dark:text-neutral-100">{{ props.stats.totalDepartments || '0' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <Link
          as="button"
          :href="route('tenant.members.index', tenantParams)"
          class="cursor-pointer text-left group bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-neutral-100 dark:bg-neutral-700 rounded-lg flex items-center justify-center group-hover:bg-neutral-200 dark:group-hover:bg-neutral-600 transition-colors shrink-0">
              <Users class="w-6 h-6 text-neutral-600 dark:text-neutral-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Manage Members</p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400">Add, edit, or remove team members</p>
            </div>
          </div>
        </Link>

        <Link
          as="button"
          :href="route('tenant.admin.leave-requests.index', tenantParams)"
          class="cursor-pointer text-left group bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-neutral-100 dark:bg-neutral-700 rounded-lg flex items-center justify-center group-hover:bg-neutral-200 dark:group-hover:bg-neutral-600 transition-colors shrink-0">
              <FileText class="w-6 h-6 text-neutral-600 dark:text-neutral-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Leave Requests</p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400">Review and approve requests</p>
            </div>
          </div>
        </Link>

        <Link
          as="button"
          :href="route('tenant.holidays.index', tenantParams)"
          class="cursor-pointer text-left group bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-neutral-100 dark:bg-neutral-700 rounded-lg flex items-center justify-center group-hover:bg-neutral-200 dark:group-hover:bg-neutral-600 transition-colors shrink-0">
              <CalendarDays class="w-6 h-6 text-neutral-600 dark:text-neutral-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Holidays</p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400">Configure company holidays</p>
            </div>
          </div>
        </Link>

        <Link
          as="button"
          :href="route('tenant.departments.index', tenantParams)"
          class="cursor-pointer text-left group bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700 p-6 hover:shadow-md transition-shadow"
        >
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-neutral-100 dark:bg-neutral-700 rounded-lg flex items-center justify-center group-hover:bg-neutral-200 dark:group-hover:bg-neutral-600 transition-colors shrink-0">
              <Building2 class="w-6 h-6 text-neutral-600 dark:text-neutral-400" />
            </div>
            <div>
              <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Departments</p>
              <p class="text-xs text-neutral-500 dark:text-neutral-400">Organize team structure</p>
            </div>
          </div>
        </Link>
      </div>

      <!-- Team Overview Section -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-neutral-900 dark:text-neutral-100">Team Overview</h2>
          <div class="flex items-center space-x-3">
            <Link
              :href="route('tenant.members.index', tenantParams)"
              class="text-sm text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100"
            >
              View all members →
            </Link>

            <ModalLink
              :as="Button"
              class="cursor-pointer"
              :href="route('tenant.dashboard.invite-member', tenantParams)">
              <Plus class="w-4 h-4" />
              Invite Member
            </ModalLink>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
          <!-- Department Breakdown -->
          <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700">
            <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
              <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 flex items-center">
                <Building2 class="w-5 h-5 mr-2" />
                Department Breakdown
              </h3>
            </div>
            <div class="p-6">
              <div v-if="props.departments && props.departments.length > 0" class="space-y-4">
                <div
                  v-for="department in props.departments.slice(0, 5)"
                  :key="department.id"
                  class="flex items-center justify-between"
                >
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-neutral-100 dark:bg-neutral-700 rounded-lg flex items-center justify-center">
                      <Building2 class="w-4 h-4 text-neutral-600 dark:text-neutral-400" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ department.name }}</p>
                      <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ department.employee_count || 0 }} members</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="text-sm font-semibold text-neutral-900 dark:text-neutral-100">{{ department.employee_count || 0 }}</span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <Building2 class="w-12 h-12 text-neutral-400 dark:text-neutral-500 mx-auto mb-4" />
                <p class="text-neutral-500 dark:text-neutral-400">No departments found</p>
              </div>
            </div>
          </div>

          <!-- Recent Employees -->
          <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700">
            <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
              <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 flex items-center">
                <UserPlus class="w-5 h-5 mr-2" />
                Recent Employees
              </h3>
            </div>
            <div class="p-6">
              <div v-if="props.recentEmployees && props.recentEmployees.length > 0" class="space-y-4">
                <div
                  v-for="employee in props.recentEmployees.slice(0, 4)"
                  :key="employee.id"
                  class="flex items-center space-x-3"
                >
                  <div class="w-10 h-10 bg-neutral-200 dark:bg-neutral-600 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                      {{ employee.name.charAt(0) }}
                    </span>
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ employee.name }}</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ employee.position }}</p>
                  </div>
                  <div class="text-right">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                      New
                    </span>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8">
                <UserPlus class="w-12 h-12 text-neutral-400 dark:text-neutral-500 mx-auto mb-4" />
                <p class="text-neutral-500 dark:text-neutral-400">No recent employees</p>
              </div>
            </div>
          </div>

          <!-- Team Performance -->
          <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700">
            <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
              <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 flex items-center">
                <TrendingUp class="w-5 h-5 mr-2" />
                Team Performance
              </h3>
            </div>
            <div class="p-6">
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                      <CheckCircle class="w-4 h-4 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Leave Approval Rate</p>
                      <p class="text-xs text-neutral-500 dark:text-neutral-400">This month</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="text-sm font-semibold text-green-600 dark:text-green-400">{{ Math.round((props.stats.thisMonthApproved / Math.max(props.stats.companyPendingRequests + props.stats.thisMonthApproved, 1)) * 100) }}%</span>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                      <Users class="w-4 h-4 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Team Utilization</p>
                      <p class="text-xs text-neutral-500 dark:text-neutral-400">Active members</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ props.stats.totalEmployees || 0 }}</span>
                  </div>
                </div>

                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
                      <Award class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                      <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">Department Coverage</p>
                      <p class="text-xs text-neutral-500 dark:text-neutral-400">Active departments</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="text-sm font-semibold text-purple-600 dark:text-purple-400">{{ props.stats.totalDepartments || 0 }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity & Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Pending Leave Requests -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700">
          <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Pending Leave Requests</h3>
              <Link
                :href="route('tenant.admin.leave-requests.index', tenantParams)"
                class="text-sm text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100"
              >
                View all
              </Link>
            </div>
          </div>
          <div class="p-6">
            <div v-if="props.teamPendingRequests && props.teamPendingRequests.length > 0" class="space-y-4">
              <div
                v-for="request in props.teamPendingRequests.slice(0, 5)"
                :key="request.id"
                class="flex items-center justify-between p-4 bg-neutral-50 dark:bg-neutral-700/50 rounded-lg"
              >
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-neutral-200 dark:bg-neutral-600 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">
                      {{ request.user?.name.charAt(0) }}
                    </span>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ request.user?.name }}</p>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                      {{ formatDate(request.start_date) }} - {{ formatDate(request.end_date) }}
                    </p>
                  </div>
                </div>
                <div class="text-right">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                    {{ request.status }}
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <Clock class="w-12 h-12 text-neutral-400 dark:text-neutral-500 mx-auto mb-4" />
              <p class="text-neutral-500 dark:text-neutral-400">No pending leave requests</p>
            </div>
          </div>
        </div>

        <!-- Upcoming Holidays -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700">
          <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100">Upcoming Holidays</h3>
              <Link
                :href="route('tenant.holidays.index', tenantParams)"
                class="text-sm text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100"
              >
                View all
              </Link>
            </div>
          </div>
          <div class="p-6">
            <div v-if="props.upcomingHolidays && props.upcomingHolidays.length > 0" class="space-y-4">
              <div
                v-for="holiday in props.upcomingHolidays.slice(0, 5)"
                :key="holiday.id"
                class="flex items-center justify-between p-4 bg-neutral-50 dark:bg-neutral-700/50 rounded-lg"
              >
                <div class="flex items-center space-x-3">
                  <div class="w-10 h-10 bg-neutral-200 dark:bg-neutral-600 rounded-full flex items-center justify-center">
                    <CalendarDays class="w-5 h-5 text-neutral-600 dark:text-neutral-400" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ holiday.name }}</p>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ formatDate(holiday.date) }}</p>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8">
              <CalendarDays class="w-12 h-12 text-neutral-400 dark:text-neutral-500 mx-auto mb-4" />
              <p class="text-neutral-500 dark:text-neutral-400">No upcoming holidays</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div v-if="props.chartData" class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
        <!-- Leave Trends Chart -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700">
          <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 flex items-center">
              <BarChart3 class="w-5 h-5 mr-2" />
              Leave Trends
            </h3>
          </div>
          <div class="p-6">
            <apexchart
              v-if="leaveTrendsOptions && props.chartData?.leaveTrends"
              type="line"
              :options="leaveTrendsOptions"
              :series="props.chartData.leaveTrends"
              height="300"
            />
          </div>
        </div>

        <!-- Department Overview Chart -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-sm border border-neutral-200 dark:border-neutral-700">
          <div class="p-6 border-b border-neutral-200 dark:border-neutral-700">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 flex items-center">
              <Building2 class="w-5 h-5 mr-2" />
              Department Overview
            </h3>
          </div>
          <div class="p-6">
            <apexchart
              v-if="departmentChartOptions && props.chartData?.departments"
              type="bar"
              :options="departmentChartOptions"
              :series="props.chartData.departments"
              height="300"
            />
          </div>
        </div>
      </div>

      <!-- Team Overview -->
    </div>
  </TenantLayout>
</template>
