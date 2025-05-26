<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts'
import { usePage } from '@inertiajs/vue3'

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard'
  }
];

const leaveSummary = usePage().props.leaveSummary

const chartSeries = leaveSummary.map(t => [t.used, t.remaining])
const chartLabels = leaveSummary.map(t => t.name)

const donutOptions = (index) => ({
  chart: {
    type: 'donut',
  },
  labels: ['Used', 'Remaining'],
  title: {
    text: chartLabels[index],
    align: 'center',
  },
  colors: ['#F87171', '#34D399'],
  legend: {
    position: 'bottom',
  },
})
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
      <div
        v-for="(series, index) in chartSeries"
        :key="index"
        class="bg-white rounded-2xl shadow p-4">
        <VueApexCharts
          :options="donutOptions(index)"
          :series="series"
          type="donut"
          height="300"
        />
      </div>
    </div>
  </AppLayout>
</template>
