<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ApexCharts from 'vue3-apexcharts'

const props = defineProps({ chartData: Object })

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Admin Dashboard',
    href: '/admin/dashboard'
  }
];

const series = [
  props.chartData?.approved,
  props.chartData?.rejected,
  props.chartData?.pending
]

const donutOptions = {
  chart: { type: 'donut' },
  labels: ['Approved', 'Rejected', 'Pending'],
  colors: ['#22c55e', '#ef4444', '#facc15']
}

const barOptions = {
  chart: { type: 'bar' },
  xaxis: {
    categories: props.chartData?.byType.map(t => t.name)
  },
  colors: ['#3b82f6']
}

const barSeries = [{
  name: 'Leave Count',
  data: props.chartData?.byType.map(t => t.count)
}]
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
  </AppLayout>
</template>
