<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ApexCharts from 'vue3-apexcharts';
import { onMounted, ref } from 'vue';
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

const series = [
  props.chartData?.approved,
  props.chartData?.rejected,
  props.chartData?.pending
];

const donutOptions = {
  chart: { type: 'donut' },
  labels: ['Approved', 'Rejected', 'Pending'],
  colors: ['#22c55e', '#ef4444', '#facc15']
};

const barOptions = {
  chart: { type: 'bar' },
  xaxis: {
    categories: props.chartData?.byType.map(t => t.name)
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
    <div class="p-6">
      <h1 class="text-xl">Admin leave overview</h1>

      <ApexCharts
        :options="donutOptions"
        :series="series"
        type="donut" width="380" />

      <div class="mt-4">
        Total requests: {{ chartData?.total }}
      </div>

      <h2 class="mt-8 text-xl">
        Requests by type
      </h2>

      <ApexCharts
        :options="barOptions"
        :series="barSeries" type="bar"
        height="350" />
    </div>

    <!--stuf to skip-->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-6">
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
  </AppLayout>
</template>
