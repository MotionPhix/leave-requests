<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ApexCharts from 'vue3-apexcharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { onMounted, ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({ chartData: Object });

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Admin Dashboard',
    href: '/admin/dashboard'
  }
];

const monthlyData = ref([]);
const monthlyLabels = ref([]);
const leaveTypes = ref([]);
const leaveCounts = ref([]);
const topUsers = ref([]);

// Computed properties for stats
const totalRequests = computed(() => props.chartData?.total || 0);
const approvalRate = computed(() => {
  const approved = props.chartData?.approved || 0;
  const total = totalRequests.value;
  return total > 0 ? Math.round((approved / total) * 100) : 0;
});

// Chart options
const donutOptions = {
  chart: { type: 'donut' },
  labels: ['Approved', 'Rejected', 'Pending'],
  colors: ['#22c55e', '#ef4444', '#facc15'],
  plotOptions: {
    pie: {
      donut: {
        labels: {
          show: true,
          total: {
            show: true,
            label: 'Total',
            formatter: () => totalRequests.value
          }
        }
      }
    }
  }
};

const barOptions = {
  chart: { type: 'bar' },
  plotOptions: {
    bar: { horizontal: false, columnWidth: '55%', borderRadius: 4 }
  },
  dataLabels: { enabled: false },
  stroke: { show: true, width: 2, colors: ['transparent'] },
  xaxis: {
    categories: props.chartData?.byType.map(t => t.name)
  },
  yaxis: { title: { text: 'Requests' } },
  fill: { opacity: 1 },
  tooltip: {
    y: {
      formatter: function(val) {
        return val + ' requests';
      }
    }
  },
  colors: ['#3b82f6']
};

const barSeries = [{
  name: 'Leave Count',
  data: props.chartData?.byType.map(t => t.count)
}];

async function fetchDashboardData() {
  const { data } = await axios.get('/api/admin/stats');
  monthlyLabels.value = Object.keys(data.monthly).map(m => `Month ${m}`);
  monthlyData.value = Object.values(data.monthly);
  leaveTypes.value = Object.keys(data.types);
  leaveCounts.value = Object.values(data.types);
  topUsers.value = data.topUsers;
}

onMounted(() => {
  fetchDashboardData();
});
</script>

<template>
  <Head title="Admin Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 space-y-6 max-w-5xl">
      <!-- Stats Overview -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
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
            <CardTitle class="text-sm font-medium">Approval Rate</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ approvalRate }}%</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Pending Requests</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold text-yellow-500">{{ chartData.pending }}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-sm font-medium">Most Used Leave</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="text-lg font-bold">{{ chartData.byType[0]?.name }}</div>
            <div class="text-sm text-muted-foreground">{{ chartData.byType[0]?.percentage }}% of total</div>
          </CardContent>
        </Card>
      </div>

      <!-- Charts -->
      <div class="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Leave Status Distribution</CardTitle>
          </CardHeader>
          <CardContent>
            <ApexCharts
              :options="donutOptions"
              :series="[chartData.approved, chartData.rejected, chartData.pending]"
              type="donut"
              height="350"
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Leaves by Type</CardTitle>
          </CardHeader>
          <CardContent>
            <ApexCharts
              :options="barOptions"
              :series="[{ name: 'Requests', data: chartData.byType.map(t => t.count) }]"
              type="bar"
              height="350"
            />
          </CardContent>
        </Card>
      </div>

      <!-- Top Employees -->
      <Card v-if="chartData.topEmployees?.length">
        <CardHeader>
          <CardTitle>Top Leave Takers</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="employee in chartData.topEmployees" :key="employee.name" class="flex items-center">
              <div class="flex-1">
                <div class="text-sm font-medium">{{ employee.name }}</div>
                <div class="text-xs text-muted-foreground">{{ employee.approved }} approved / {{ employee.count }}
                  total
                </div>
              </div>
              <div class="text-sm font-medium">{{ Math.round((employee.approved / employee.count) * 100) }}%</div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!--stuf to skip-->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <!-- Monthly Leave Requests -->
        <div class="bg-white dark:bg-transparent dark:border dark:border-neutral-700 p-4 rounded-lg shadow">
          <ApexCharts
            type="line"
            height="350"
            :options="{
              chart: { id: 'monthly-leaves' },
              xaxis: { categories: monthlyLabels },
              title: { text: 'Monthly Leave Requests' }
            }"
            :series="[{ name: 'Requests', data: monthlyData }]"
          />
        </div>

        <!-- Leave Types Distribution -->
        <div class="bg-white dark:bg-transparent dark:border dark:border-neutral-700 p-4 rounded-lg shadow">
          <ApexCharts
            type="pie"
            height="350"
            :options="{
              labels: leaveTypes,
              title: { text: 'Leave Types Distribution' }
            }"
            :series="leaveCounts"
          />
        </div>

        <!-- Top Users by Leave Days -->
        <div class="bg-white p-4 rounded-lg shadow col-span-1 md:col-span-2">
          <ApexCharts
            type="bar"
            height="350"
            :options="{
              chart: { id: 'top-users' },
              xaxis: { categories: topUsers.map(u => u.name) },
              title: { text: 'Top Users by Leave Days' }
            }"
            :series="[{ name: 'Days', data: topUsers.map(u => u.days) }]"
          />
        </div>
      </div>

    </div>
  </AppLayout>
</template>
