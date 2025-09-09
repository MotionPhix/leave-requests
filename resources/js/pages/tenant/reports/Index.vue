<template>
  <TenantLayout>
    <Head title="Reports & Analytics" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Reports & Analytics</h1>
          <p class="text-muted-foreground">
            Comprehensive insights into your workspace's leave management
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Select v-model="selectedPeriod" @update:model-value="fetchReports">
            <SelectTrigger class="w-40">
              <SelectValue placeholder="Select period" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="current_month">This Month</SelectItem>
              <SelectItem value="last_month">Last Month</SelectItem>
              <SelectItem value="current_quarter">This Quarter</SelectItem>
              <SelectItem value="current_year">This Year</SelectItem>
              <SelectItem value="last_year">Last Year</SelectItem>
            </SelectContent>
          </Select>
          <Button variant="outline" size="sm">
            <Download class="h-4 w-4 mr-2" />
            Export
          </Button>
        </div>
      </div>

      <!-- Key Metrics Cards -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="bg-card border rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Total Requests</p>
              <p class="text-2xl font-bold text-card-foreground">{{ summary.total_requests }}</p>
            </div>
            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
              <FileText class="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
          </div>
          <div class="mt-2 flex items-center gap-1 text-xs">
            <TrendingUp class="h-3 w-3 text-green-500" />
            <span class="text-green-500">+12%</span>
            <span class="text-muted-foreground">from last period</span>
          </div>
        </div>

        <div class="bg-card border rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Approved Requests</p>
              <p class="text-2xl font-bold text-card-foreground">{{ summary.approved_requests }}</p>
            </div>
            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
              <CheckCircle class="h-5 w-5 text-green-600 dark:text-green-400" />
            </div>
          </div>
          <div class="mt-2 flex items-center gap-1 text-xs">
            <span class="text-muted-foreground">
              {{ ((summary.approved_requests / summary.total_requests) * 100).toFixed(1) }}% approval rate
            </span>
          </div>
        </div>

        <div class="bg-card border rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Total Days Used</p>
              <p class="text-2xl font-bold text-card-foreground">{{ summary.total_days_used }}</p>
            </div>
            <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
              <Calendar class="h-5 w-5 text-orange-600 dark:text-orange-400" />
            </div>
          </div>
          <div class="mt-2 flex items-center gap-1 text-xs">
            <span class="text-muted-foreground">
              {{ (summary.total_days_used / summary.total_employees).toFixed(1) }} avg per employee
            </span>
          </div>
        </div>

        <div class="bg-card border rounded-lg p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-muted-foreground">Pending Reviews</p>
              <p class="text-2xl font-bold text-card-foreground">{{ summary.pending_requests }}</p>
            </div>
            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center">
              <Clock class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
            </div>
          </div>
          <div class="mt-2 flex items-center gap-1 text-xs">
            <span class="text-muted-foreground">Awaiting approval</span>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Leave Trends Chart -->
        <div class="bg-card border rounded-lg p-6">
          <h3 class="text-lg font-semibold text-card-foreground mb-4">Leave Trends</h3>
          <div class="h-64">
            <apexchart
              v-if="trendsChartOptions"
              type="line"
              :options="trendsChartOptions"
              :series="trendsChartSeries"
              height="100%"
            />
          </div>
        </div>

        <!-- Leave Types Distribution -->
        <div class="bg-card border rounded-lg p-6">
          <h3 class="text-lg font-semibold text-card-foreground mb-4">Leave Types Distribution</h3>
          <div class="h-64">
            <apexchart
              v-if="distributionChartOptions"
              type="donut"
              :options="distributionChartOptions"
              :series="distributionChartSeries"
              height="100%"
            />
          </div>
        </div>
      </div>

      <!-- Department Analysis -->
      <div class="bg-card border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-card-foreground mb-4">Department Analysis</h3>
        <div class="space-y-4">
          <div
            v-for="dept in departmentAnalysis"
            :key="dept.department_name"
            class="flex items-center justify-between p-4 bg-muted/50 rounded-lg"
          >
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                <Building2 class="h-4 w-4 text-primary" />
              </div>
              <div>
                <h4 class="font-medium">{{ dept.department_name || 'Unassigned' }}</h4>
                <p class="text-sm text-muted-foreground">{{ dept.employee_count }} employees</p>
              </div>
            </div>
            <div class="flex items-center gap-6 text-sm">
              <div class="text-center">
                <div class="font-semibold">{{ dept.total_requests }}</div>
                <div class="text-muted-foreground">Requests</div>
              </div>
              <div class="text-center">
                <div class="font-semibold">{{ dept.total_days }}</div>
                <div class="text-muted-foreground">Days Used</div>
              </div>
              <div class="text-center">
                <div class="font-semibold">{{ dept.avg_days_per_employee.toFixed(1) }}</div>
                <div class="text-muted-foreground">Avg/Employee</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Employees -->
      <div class="bg-card border rounded-lg p-6">
        <h3 class="text-lg font-semibold text-card-foreground mb-4">Employee Usage Summary</h3>
        <div class="space-y-3">
          <div
            v-for="employee in employeeUsage"
            :key="employee.employee_name"
            class="flex items-center justify-between p-3 hover:bg-muted/50 rounded-lg transition-colors"
          >
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 bg-primary/20 rounded-full flex items-center justify-center">
                <User class="h-4 w-4 text-primary" />
              </div>
              <div>
                <h4 class="font-medium">{{ employee.employee_name }}</h4>
                <p class="text-sm text-muted-foreground">{{ employee.department_name || 'Unassigned' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
              <div class="text-right">
                <div class="font-semibold">{{ employee.total_requests }}</div>
                <div class="text-muted-foreground">Requests</div>
              </div>
              <div class="text-right">
                <div class="font-semibold">{{ employee.total_days }}</div>
                <div class="text-muted-foreground">Days Used</div>
              </div>
              <Badge
                :variant="employee.total_days > 20 ? 'destructive' : employee.total_days > 10 ? 'default' : 'secondary'"
                class="ml-2"
              >
                {{ employee.total_days > 20 ? 'High' : employee.total_days > 10 ? 'Medium' : 'Low' }} Usage
              </Badge>
            </div>
          </div>
        </div>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Building2,
  Calendar,
  CheckCircle,
  Clock,
  Download,
  FileText,
  TrendingUp,
  User
} from 'lucide-vue-next';

interface LeaveSummary {
  total_requests: number;
  approved_requests: number;
  pending_requests: number;
  rejected_requests: number;
  total_days_used: number;
  total_employees: number;
}

interface DepartmentAnalysis {
  department_name: string | null;
  employee_count: number;
  total_requests: number;
  total_days: number;
  avg_days_per_employee: number;
}

interface EmployeeUsage {
  employee_name: string;
  department_name: string | null;
  total_requests: number;
  total_days: number;
}

interface MonthlyTrend {
  month: string;
  requests: number;
  days: number;
}

const props = defineProps<{
  summary: LeaveSummary;
  departmentAnalysis: DepartmentAnalysis[];
  employeeUsage: EmployeeUsage[];
  monthlyTrends: MonthlyTrend[];
  leaveTypesDistribution: Array<{ name: string; count: number }>;
}>();

const selectedPeriod = ref('current_month');

// Charts configuration
const trendsChartOptions = computed(() => ({
  chart: {
    type: 'line',
    toolbar: {
      show: false,
    },
  },
  theme: {
    mode: 'dark', // Will be handled by CSS variables
  },
  colors: ['#3b82f6', '#10b981'],
  xaxis: {
    categories: props.monthlyTrends.map(trend => trend.month),
  },
  yaxis: {
    title: {
      text: 'Count'
    }
  },
  legend: {
    position: 'top',
  },
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  markers: {
    size: 5,
  },
}));

const trendsChartSeries = computed(() => [
  {
    name: 'Requests',
    data: props.monthlyTrends.map(trend => trend.requests),
  },
  {
    name: 'Days Used',
    data: props.monthlyTrends.map(trend => trend.days),
  },
]);

const distributionChartOptions = computed(() => ({
  chart: {
    type: 'donut',
  },
  theme: {
    mode: 'dark',
  },
  colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
  labels: props.leaveTypesDistribution.map(item => item.name),
  legend: {
    position: 'bottom',
  },
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
      },
    },
  },
}));

const distributionChartSeries = computed(() =>
  props.leaveTypesDistribution.map(item => item.count)
);

const fetchReports = () => {
  router.get(
    route('tenant.reports.index', {
      tenant_slug: window.location.pathname.split('/')[1],
      tenant_uuid: window.location.pathname.split('/')[2],
      period: selectedPeriod.value
    }),
    {},
    {
      preserveState: true,
      preserveScroll: true,
    }
  );
};
</script>
