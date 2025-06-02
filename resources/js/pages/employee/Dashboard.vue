<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CalendarIcon } from 'lucide-vue-next';
import ApexCharts from 'vue3-apexcharts';
import { Badge } from '@/components/ui/badge';
import dayjs from 'dayjs';

const props = defineProps<{
  leaveSummary: Array<{
    name: string;
    used: number;
    remaining: number;
    max: number;
    color: string;
  }>;
  upcomingLeaves: Array<{
    id: number;
    type: string;
    start_date: string;
    end_date: string;
    days: number;
  }>;
  pendingRequests: Array<{
    id: number;
    type: string;
    start_date: string;
    end_date: string;
    created_at: string;
  }>;
  holidays: Array<{
    name: string;
    date: string;
    type: string;
  }>;
  monthlyStats: {
    labels: string[];
    data: number[];
  };
  chartData: {
    total: number;
    approved: number;
    rejected: number;
    pending: number;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'My Dashboard',
    href: '/dashboard'
  }
];

// Chart options for donut chart
const donutOptions = {
  labels: ['Approved', 'Rejected', 'Pending'],
  colors: ['#4CAF50', '#F44336', '#FFC107'],
  plotOptions: {
    pie: {
      donut: {
        size: '70%'
      }
    }
  }
};

// Chart options for monthly statistics
const lineOptions = {
  chart: {
    type: 'line',
    toolbar: {
      show: false
    }
  },
  stroke: {
    curve: 'smooth'
  },
  xaxis: {
    categories: props.monthlyStats.labels
  },
  title: {
    text: 'Monthly Leave Trends',
    align: 'left'
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="My Dashboard" />

    <div class="p-6 space-y-6 max-w-5xl">
      <!-- Stats Overview -->
      <div class="grid gap-4 md:grid-cols-4">
        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Total Requests</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ chartData.total }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium text-green-600">Approved</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ chartData.approved }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium text-yellow-600">Pending</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ chartData.pending }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium text-red-600">Rejected</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ chartData.rejected }}</div>
          </CardContent>
        </Card>
      </div>

      <!-- Leave Balances -->
      <div class="grid gap-6 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Leave Balances</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="leave in leaveSummary" :key="leave.name" class="flex items-center">
                <div class="flex-1">
                  <p class="text-sm font-medium">{{ leave.name }}</p>
                  <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                    <div
                      class="h-2.5 rounded-full"
                      :style="{
                        width: `${(leave.used / leave.max) * 100}%`,
                        backgroundColor: leave.color
                      }"
                    ></div>
                  </div>
                </div>
                <div class="ml-4 text-right">
                  <p class="text-sm font-medium">{{ leave.remaining }}</p>
                  <p class="text-xs text-gray-500">remaining</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Leave Distribution</CardTitle>
          </CardHeader>
          <CardContent>
            <ApexCharts
              type="donut"
              height="300"
              :options="donutOptions"
              :series="[chartData.approved, chartData.rejected, chartData.pending]"
            />
          </CardContent>
        </Card>
      </div>

      <!-- Upcoming Leaves & Holidays -->
      <div class="grid gap-6 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Upcoming Leaves</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="leave in upcomingLeaves" :key="leave.id"
                   class="flex items-start space-x-4 p-3 rounded-lg border">
                <CalendarIcon class="h-5 w-5 text-gray-400" />
                <div>
                  <p class="font-medium">{{ leave.type }}</p>
                  <p class="text-sm text-gray-500">
                    {{ dayjs(leave.start_date).format('MMM D') }} -
                    {{ dayjs(leave.end_date).format('MMM D, YYYY') }}
                  </p>
                  <Badge>{{ leave.days }} days</Badge>
                </div>
              </div>
              <p v-if="!upcomingLeaves.length" class="text-sm text-gray-500">
                No upcoming leaves scheduled
              </p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Upcoming Holidays</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="holiday in holidays" :key="holiday.date"
                   class="flex items-start space-x-4 p-3 rounded-lg border">
                <CalendarIcon class="h-5 w-5 text-gray-400" />
                <div>
                  <p class="font-medium">{{ holiday.name }}</p>
                  <p class="text-sm text-gray-500">
                    {{ dayjs(holiday.date).format('MMM D, YYYY') }}
                  </p>
                  <Badge>{{ holiday.type }}</Badge>
                </div>
              </div>
              <p v-if="!holidays.length" class="text-sm text-gray-500">
                No upcoming holidays
              </p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Monthly Trends -->
      <Card>
        <CardHeader>
          <CardTitle>Leave Trends</CardTitle>
        </CardHeader>
        <CardContent>
          <ApexCharts
            type="line"
            height="350"
            :options="lineOptions"
            :series="[{ name: 'Leaves', data: monthlyStats.data }]"
          />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<style scoped>
.status-badge {
  /* @apply px-2 py-1 text-xs font-medium rounded-full;*/
}
</style>
