<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import ApexCharts from 'vue3-apexcharts';
import { useStorage } from '@vueuse/core';
import {
  UserIcon,
  BriefcaseIcon,
  HourglassIcon,
  ClockIcon
} from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import SimpleStats from '@/components/SimpleStats.vue';

const props = defineProps<{
  user: {
    uuid: string;
    name: string;
    email: string;
    gender: string;
    position: string;
    department: string;
    employee_id: string;
    join_date: string;
    reporting_to: string;
    work_phone: string;
    office_location: string;
    employment_status: string;
    employment_type: string;
  };
  leaveStats: {
    total_leaves: number;
    pending_leaves: number;
    approved_leaves: number;
    rejected_leaves: number;
  };
  leaveBalances: Array<{
    type: string;
    used: number;
    remaining: number;
    total: number;
  }>;
  leaveHistory: Array<{
    id: number;
    type: string;
    start_date: string;
    end_date: string;
    status: string;
    total_days: number;
  }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Employees',
    href: route('admin.employees.index')
  },
  {
    title: props.user.name,
    href: route('admin.employees.show', props.user.uuid)
  }
];

const activeTab = useStorage('active_tab', 'balances');

const leaveChartOptions = computed(() => ({
  chart: {
    type: 'donut',
    toolbar: { show: false }
  },
  colors: ['#10b981', '#f59e0b', '#ef4444'],
  labels: ['Approved', 'Pending', 'Rejected'],
  series: [
    props.leaveStats.approved_leaves,
    props.leaveStats.pending_leaves,
    props.leaveStats.rejected_leaves
  ],
  legend: {
    position: 'bottom'
  }
}));

const monthlyLeaveData = computed(() => {
  const months = Array(12).fill(0);
  props.leaveHistory.forEach(leave => {
    if (leave.status === 'approved') {
      const month = new Date(leave.start_date).getMonth();
      months[month] += leave.total_days;
    }
  });
  return months;
});

const monthlyChartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false }
  },
  series: [{
    name: 'Leave Days',
    data: monthlyLeaveData.value
  }],
  xaxis: {
    categories: [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ]
  },
  colors: ['#6366f1'],
  stroke: {
    curve: 'smooth'
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.7,
      opacityTo: 0.3
    }
  }
}));
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head :title="user.name" />

    <div class="p-6 max-w-5xl">
      <!-- Header Section -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold mb-2">{{ user.name }}</h1>
        <div class="grid grid-cols-2 sm:grid-cols-4 items-center gap-2">

          <SimpleStats
            :icon="UserIcon"
            title="Position"
            :description="user.position"
          />

          <SimpleStats
            :icon="BriefcaseIcon"
            title="Department"
            :description="user.department"
          />

          <SimpleStats
            :icon="ClockIcon"
            title="Employment Type"
            :description="user.employment_type"
          />

          <SimpleStats
            :icon="HourglassIcon"
            title="Employment Status"
            :description="user.employment_status"
          />
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Personal Info -->
        <div>
          <Card class="relative md:sticky top-5">
            <CardHeader>
              <CardTitle>Employee Information</CardTitle>
            </CardHeader>

            <CardContent class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-muted-foreground">Employee ID</p>
                  <p class="font-medium">{{ user.employee_id }}</p>
                </div>

                <div>
                  <p class="text-sm text-muted-foreground">Join Date</p>
                  <p class="font-medium">{{ new Date(user.join_date).toLocaleDateString() }}</p>
                </div>
              </div>

              <div>
                <p class="text-sm text-muted-foreground">Reports To</p>
                <p class="font-medium">{{ user.reporting_to }}</p>
              </div>

              <div>
                <p class="text-sm text-muted-foreground">Contact</p>
                <p class="font-medium">{{ user.work_phone }}</p>
                <p class="text-sm">{{ user.email }}</p>
              </div>

              <div>
                <p class="text-sm text-muted-foreground">Office Location</p>
                <p class="font-medium">{{ user.office_location }}</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Center Column - Leave Stats -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle>Leave Overview</CardTitle>
          </CardHeader>

          <CardContent>
            <Tabs
              v-model="activeTab"
              class="w-full">
              <TabsList class="grid w-full grid-cols-3">
                <TabsTrigger value="balances">
                  Current Balances
                </TabsTrigger>

                <TabsTrigger value="history">
                  Leave History
                </TabsTrigger>

                <TabsTrigger value="analytics">
                  Analytics
                </TabsTrigger>
              </TabsList>

              <TabsContent value="balances">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <Card
                    v-for="balance in leaveBalances"
                    :key="balance.type">
                    <CardContent class="p-4">
                      <h3 class="font-medium mb-2">{{ balance.type }}</h3>

                      <div class="flex justify-between items-center">
                        <div>
                          <p class="text-2xl font-bold">{{ balance.remaining }}</p>
                          <p class="text-sm text-muted-foreground">days remaining</p>
                        </div>

                        <div class="text-right">
                          <p class="text-sm">{{ balance.used }} used</p>
                          <p class="text-sm text-muted-foreground">of {{ balance.total }}</p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </TabsContent>

              <TabsContent value="history">
                <div class="space-y-4">
                  <div
                    v-for="leave in leaveHistory"
                    :key="leave.id"
                    class="flex items-center justify-between p-4 border rounded-lg">
                    <div>
                      <p class="font-medium">{{ leave.type }}</p>
                      <p class="text-sm text-muted-foreground">
                        {{ new Date(leave.start_date).toLocaleDateString() }} -
                        {{ new Date(leave.end_date).toLocaleDateString() }}
                      </p>
                    </div>

                    <div class="text-right">
                      <Badge :variant="leave.status === 'approved' ? 'success' :
                        leave.status === 'pending' ? 'warning' : 'destructive'">
                        {{ leave.status }}
                      </Badge>

                      <p class="text-sm text-muted-foreground mt-1">
                        {{ leave.total_days }} days
                      </p>
                    </div>
                  </div>
                </div>
              </TabsContent>

              <TabsContent value="analytics">
                <div class="grid grid-cols-1 gap-6">
                  <!-- Leave Status Distribution -->
                  <Card>
                    <CardHeader>
                      <CardTitle>Leave Status Distribution</CardTitle>
                    </CardHeader>

                    <CardContent>
                      <ApexCharts
                        type="donut"
                        height="300"
                        :options="leaveChartOptions"
                        :series="leaveChartOptions.series"
                      />

                      <div class="grid grid-cols-3 gap-4 mt-4 text-center">
                        <div>
                          <p class="text-sm text-muted-foreground">Approved</p>
                          <p class="text-xl font-bold text-emerald-600">
                            {{ leaveStats.approved_leaves }}
                          </p>
                        </div>

                        <div>
                          <p class="text-sm text-muted-foreground">Pending</p>
                          <p class="text-xl font-bold text-amber-600">
                            {{ leaveStats.pending_leaves }}
                          </p>
                        </div>

                        <div>
                          <p class="text-sm text-muted-foreground">Rejected</p>
                          <p class="text-xl font-bold text-red-600">
                            {{ leaveStats.rejected_leaves }}
                          </p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  <!-- Monthly Leave Distribution -->
                  <Card>
                    <CardHeader>
                      <CardTitle>Monthly Leave Distribution</CardTitle>
                    </CardHeader>

                    <CardContent>
                      <ApexCharts
                        type="area"
                        height="300"
                        :options="monthlyChartOptions"
                        :series="monthlyChartOptions.series"
                      />
                    </CardContent>
                  </Card>
                </div>
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
