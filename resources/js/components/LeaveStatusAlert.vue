<script setup lang="ts">
import { computed } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  AlertTriangle,
  Clock,
  Calendar,
  CheckCircle,
  XCircle,
  AlertCircle
} from 'lucide-vue-next';

interface ActiveRequest {
  id: number;
  leave_type: string;
  start_date: string;
  end_date: string;
  status: string;
  status_label: string;
  total_days: number;
}

interface Props {
  canRequestLeave: boolean;
  blockingReason?: string | null;
  activeRequests: ActiveRequest[];
}

const props = defineProps<Props>();

const statusIcons = {
  pending: Clock,
  approved: CheckCircle,
  rejected: XCircle,
  cancelled: XCircle,
  reviewed: AlertCircle,
  rescheduled: Calendar,
};

const statusVariants = {
  pending: 'secondary',
  approved: 'default',
  rejected: 'destructive',
  cancelled: 'outline',
  reviewed: 'secondary',
  rescheduled: 'secondary',
} as const;

const getStatusIcon = (status: string) => {
  return statusIcons[status as keyof typeof statusIcons] || Clock;
};

const getStatusVariant = (status: string) => {
  return statusVariants[status as keyof typeof statusVariants] || 'secondary';
};

const alertVariant = computed(() => {
  if (!props.canRequestLeave) {
    return 'destructive';
  }
  return props.activeRequests.length > 0 ? 'default' : 'default';
});
</script>

<template>
  <div class="space-y-4">
    <!-- Blocking Alert -->
    <Alert v-if="!canRequestLeave" :variant="alertVariant">
      <AlertTriangle class="h-4 w-4" />
      <AlertTitle>Cannot Request New Leave</AlertTitle>
      <AlertDescription>
        {{ blockingReason }}
      </AlertDescription>
    </Alert>

    <!-- Active Requests Info -->
    <Card v-if="activeRequests.length > 0" class="border-amber-200 bg-amber-50/50">
      <CardHeader class="pb-3">
        <CardTitle class="text-lg flex items-center gap-2">
          <Calendar class="h-5 w-5 text-amber-600" />
          Active Leave Requests
        </CardTitle>
      </CardHeader>
      <CardContent class="space-y-3">
        <div
          v-for="request in activeRequests"
          :key="request.id"
          class="flex items-center justify-between p-3 bg-white rounded-lg border">

          <div class="flex items-center gap-3">
            <component
              :is="getStatusIcon(request.status)"
              class="h-5 w-5 text-muted-foreground"
            />
            <div>
              <p class="font-medium">{{ request.leave_type }}</p>
              <p class="text-sm text-muted-foreground">
                {{ request.start_date }} - {{ request.end_date }}
                <span class="ml-2">({{ request.total_days }} {{ request.total_days === 1 ? 'day' : 'days' }})</span>
              </p>
            </div>
          </div>

          <Badge :variant="getStatusVariant(request.status)">
            {{ request.status_label }}
          </Badge>
        </div>

        <div v-if="!canRequestLeave" class="mt-4 p-3 bg-amber-100 rounded-lg">
          <p class="text-sm text-amber-800">
            <strong>Note:</strong> You must wait for your current leave request to be completed, rejected, or cancelled before submitting a new request.
          </p>
        </div>
      </CardContent>
    </Card>

    <!-- Success State -->
    <Alert v-if="canRequestLeave && activeRequests.length === 0" variant="default" class="border-green-200 bg-green-50/50">
      <CheckCircle class="h-4 w-4 text-green-600" />
      <AlertTitle class="text-green-800">Ready to Request Leave</AlertTitle>
      <AlertDescription class="text-green-700">
        You can submit a new leave request. Make sure to check your leave balance before proceeding.
      </AlertDescription>
    </Alert>
  </div>
</template>
