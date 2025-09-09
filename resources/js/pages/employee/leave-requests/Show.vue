<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogOverlay,
  DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Calendar, Clock, FileText, MessageSquare } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface LeaveRequest {
  uuid: string;
  user: {
    name: string;
    email: string;
    position: string;
  };
  leave_type: {
    name: string;
    requires_documentation: boolean;
  };
  start_date: string;
  end_date: string;
  total_days: number;
  reason: string;
  status: 'pending' | 'approved' | 'rejected' | 'cancelled';
  comments: string | null;
  documentation_url: string | null;
  created_at: string;
}

const props = defineProps<{
  leaveRequest: LeaveRequest;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Leave Requests',
    href: route('leave-requests.index')
  },
  {
    title: `Request #${props.leaveRequest.uuid}`,
    href: route('leave-requests.show', props.leaveRequest.uuid)
  }
];

const showCancelDialog = ref(false);

const cancelForm = useForm({
  reason: '',
});

function handleCancel() {
  cancelForm.post(route('leave-requests.cancel', props.leaveRequest.uuid), {
    preserveScroll: true,
    onSuccess: () => {
      showCancelDialog.value = false;
      cancelForm.reset();
    },
  });
}

const getStatusColor = (status: string) => {
  return {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
  }[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Leave Request #${leaveRequest.uuid}`" />

    <div class="p-6">
      <div class="space-y-6 max-w-5xl">
        <!-- Header Section -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold">Leave Request Details</h1>
            <p class="text-sm text-muted-foreground">
              Submitted on {{ new Date(leaveRequest.created_at).toLocaleDateString() }}
            </p>
          </div>

          <div class="flex items-center gap-4">
            <Badge
              class="text-sm capitalize px-3 py-1"
              :class="getStatusColor(leaveRequest.status)">
              {{ leaveRequest.status }}
            </Badge>

            <Button
              v-if="leaveRequest.status === 'pending'"
              variant="destructive"
              size="sm"
              @click="showCancelDialog = true"
            >
              Cancel Request
            </Button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Main Details Card -->
          <Card class="lg:col-span-2">
            <CardHeader>
              <CardTitle>Request Information</CardTitle>
            </CardHeader>

            <CardContent>
              <div class="space-y-6">
                <!-- Leave Type -->
                <div class="flex items-start gap-4">
                  <div class="w-8 h-8 shrink-0 rounded-full bg-primary/10 flex items-center justify-center">
                    <Calendar class="w-4 h-4 text-primary" />
                  </div>
                  <div>
                    <h3 class="font-medium">Leave Type</h3>
                    <p class="text-sm text-muted-foreground">
                      {{ leaveRequest.leave_type.name }}
                    </p>
                  </div>
                </div>

                <!-- Duration -->
                <div class="flex items-start gap-4">
                  <div class="w-8 h-8 shrink-0 rounded-full bg-primary/10 flex items-center justify-center">
                    <Clock class="w-4 h-4 text-primary" />
                  </div>

                  <div>
                    <h3 class="font-medium">Duration</h3>
                    <p class="text-sm text-muted-foreground">
                      {{ new Date(leaveRequest.start_date).toLocaleDateString() }} -
                      {{ new Date(leaveRequest.end_date).toLocaleDateString() }}
                      ({{ leaveRequest.total_days }} days)
                    </p>
                  </div>
                </div>

                <!-- Reason -->
                <div class="flex items-start gap-4">
                  <div class="w-8 h-8 shrink-0 rounded-full bg-primary/10 flex items-center justify-center">
                    <MessageSquare class="w-4 h-4 text-primary" />
                  </div>
                  <div>
                    <h3 class="font-medium">Reason</h3>
                    <p class="text-sm text-muted-foreground">{{ leaveRequest.reason }}</p>
                  </div>
                </div>

                <!-- Documentation -->
                <div v-if="leaveRequest.documentation" class="flex items-start gap-4">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center">
                    <FileText class="w-4 h-4 text-primary shrink-0" />
                  </div>
                  <div>
                    <h3 class="font-medium">Supporting Documentation</h3>
                    <a
                      :href="leaveRequest.documentation.url"
                      target="_blank"
                      class="text-sm text-primary hover:underline"
                    >
                      {{ leaveRequest.documentation.name }}
                    </a>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Timeline Card -->
          <Card>
            <CardHeader>
              <CardTitle>Request Timeline</CardTitle>
            </CardHeader>

            <CardContent>
              <div class="space-y-6">
                <div class="relative pl-6 pb-6 border-l-2 border-muted">
                  <div class="absolute -left-[9px] w-4 h-4 rounded-full bg-primary"></div>

                  <p class="text-sm font-medium">Request Submitted</p>

                  <p class="text-xs text-muted-foreground">
                    {{ new Date(leaveRequest.created_at).toLocaleString() }}
                  </p>
                </div>

                <div
                  v-if="leaveRequest.status !== 'pending'"
                  class="relative pl-6 pb-6 border-l-2 border-muted">
                  <div
                    class="absolute -left-[9px] w-4 h-4 rounded-full"
                    :class="leaveRequest.status === 'approved' ? 'bg-success' : 'bg-destructive'">
                  </div>

                  <p class="text-sm font-medium">
                    Request {{ leaveRequest.status === 'approved' ? 'Approved' : 'Rejected' }}
                  </p>

                  <p class="text-xs text-muted-foreground">
                    {{ new Date(leaveRequest.updated_at).toLocaleString() }}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Cancel Request Dialog -->
      <Dialog v-model:open="showCancelDialog">
        <DialogOverlay />
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Cancel Leave Request</DialogTitle>
            <DialogDescription>
              Are you sure you want to cancel this leave request? This action cannot be undone.
            </DialogDescription>
          </DialogHeader>

          <div class="mt-4">
            <Textarea
              v-model="cancelForm.reason"
              placeholder="Optional: Provide a reason for cancellation"
              rows="3"
              class="resize-none"
            />
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              size="sm"
              @click="showCancelDialog = false"
            >
              Never mind
            </Button>
            <Button
              variant="destructive"
              size="sm"
              @click="handleCancel"
            >
              Yes, cancel request
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  </AppLayout>
</template>
