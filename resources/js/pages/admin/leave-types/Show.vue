<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import ApexCharts from 'vue3-apexcharts';
import { FileTextIcon, UserIcon, CalendarIcon, PercentIcon } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
  leaveType: {
    id: number;
    name: string;
    description: string;
    max_days_per_year: number;
    requires_documentation: boolean;
    gender_specific: boolean;
    gender: string;
    frequency_years: number;
    pay_percentage: number;
  };
  stats: {
    total_requests: number;
    approved_requests: number;
    pending_requests: number;
    rejected_requests: number;
    total_days_taken: number;
  };
  monthlyStats: Record<string, number>;
  employeeStats: Record<string, number>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Leave Types',
    href: route('admin.leave-types.index')
  },
  {
    title: props.leaveType.name,
    href: route('admin.leave-types.show', props.leaveType.id)
  }
];

const monthlyChartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
  },
  series: [{
    name: 'Leave Requests',
    data: Array.from({ length: 12 }, (_, i) =>
      props.monthlyStats[i + 1] || 0
    )
  }],
  xaxis: {
    categories: [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ]
  },
  colors: ['#6366f1'],
}));

const employeeChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
  },
  series: [{
    name: 'Days Taken',
    data: Object.values(props.employeeStats)
  }],
  xaxis: {
    categories: Object.keys(props.employeeStats)
  },
  colors: ['#6366f1'],
}));
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="leaveType.name" />

    <div class="p-6 max-w-5xl">
      <div class="mb-6">
        <h1 class="text-2xl font-bold mb-2">{{ leaveType.name }}</h1>
        <p class="text-gray-600 dark:text-gray-400">{{ leaveType.description }}</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <Card>
          <CardContent class="flex items-start">
            <div class="rounded-full p-3 bg-blue-100 dark:bg-blue-900">
              <FileTextIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Requests</p>
              <h3 class="text-2xl font-bold">{{ stats.total_requests }}</h3>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="flex items-start">
            <div class="rounded-full p-3 bg-green-100 dark:bg-green-900">
              <UserIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Days Taken
              </p>
              <h3 class="text-2xl font-bold">{{ stats.total_days_taken }}</h3>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="flex items-start">
            <div class="rounded-full p-3 bg-yellow-100 dark:bg-yellow-900">
              <CalendarIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                Max <br />Days/Year
              </p>
              <h3 class="text-2xl font-bold">{{ leaveType.max_days_per_year }}</h3>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="flex items-start">
            <div class="rounded-full p-3 bg-purple-100 dark:bg-purple-900">
              <PercentIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
            </div>

            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pay Percentage</p>
              <h3 class="text-2xl font-bold">{{ leaveType.pay_percentage }}%</h3>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>Monthly Distribution</CardTitle>
          </CardHeader>

          <CardContent>
            <ApexCharts
              type="area"
              height="350"
              :options="monthlyChartOptions"
              :series="monthlyChartOptions.series"
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Employee Usage</CardTitle>
          </CardHeader>

          <CardContent>
            <ApexCharts
              type="bar"
              height="350"
              :options="employeeChartOptions"
              :series="employeeChartOptions.series"
            />
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
