<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import type { BreadcrumbItem } from '@/types';

interface LeaveRequest {
  uuid: string;
  user: {
    name: string;
    email: string;
    position: string;
    department: string;
  };
  leave_type: {
    name: string;
    requires_documentation: boolean;
  };
  start_date: string;
  end_date: string;
  total_days: number;
  reason: string;
  status: 'pending' | 'approved' | 'rejected';
  comments: string | null;
  documentation_url: string | null;
  created_at: string;
  updated_at: string;
}

const props = defineProps<{
  leaveRequest: LeaveRequest;
}>();

const comments = ref('');
const processing = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Leave Requests',
    href: route('admin.leave-requests.index')
  },
  {
    title: `Request #${props.leaveRequest.uuid}`,
    href: route('admin.leave-requests.show', props.leaveRequest.uuid)
  }
];

const updateStatus = async (status: 'approved' | 'rejected') => {
  processing.value = true;

  try {
    await router.post(route('admin.leave-requests.update-status', props.leaveRequest.uuid), {
      status,
      comments: comments.value
    });
  } finally {
    processing.value = false;
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head :title="`Leave Request #${leaveRequest.uuid}`" />

    <div class="p-6">
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle class="flex items-center gap-3">
              Leave Request Details
              <Badge :variant="leaveRequest.status === 'approved' ? 'success' :
                leaveRequest.status === 'rejected' ? 'destructive' :
                  'warning'">
                {{ leaveRequest.status }}
              </Badge>
            </CardTitle>
          </div>
        </CardHeader>

        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Employee Details -->
            <div class="space-y-6">
              <h3 class="text-sm font-medium text-muted-foreground mb-1">Employee</h3>
              <p class="font-medium">{{ leaveRequest.user.name }}</p>
              <p class="text-sm text-muted-foreground">{{ leaveRequest.user.position }}</p>
              <p class="text-sm text-muted-foreground">{{ leaveRequest.user.department }}</p>
            </div>

            <!-- Request Details -->
            <div class="space-y-6">
              <div>
                <h3 class="text-sm font-medium text-muted-foreground mb-1">Leave Type</h3>
                <p class="font-medium">{{ leaveRequest.leave_type.name }}</p>
              </div>

              <div>
                <h3 class="text-sm font-medium text-muted-foreground mb-1">Duration</h3>
                <p class="font-medium">
                  {{ new Date(leaveRequest.start_date).toLocaleDateString() }} -
                  {{ new Date(leaveRequest.end_date).toLocaleDateString() }}
                </p>
                <p class="text-sm text-muted-foreground mt-1">
                  {{ leaveRequest.total_days }} days
                </p>
              </div>

              <div>
                <h3 class="text-sm font-medium text-muted-foreground mb-1">Reason</h3>
                <p class="text-sm">{{ leaveRequest.reason }}</p>
              </div>

              <div v-if="leaveRequest.documentation_url">
                <h3 class="text-sm font-medium text-muted-foreground mb-1">Documentation</h3>
                <a :href="leaveRequest.documentation_url"
                   target="_blank"
                   class="text-sm text-primary hover:underline">
                  View Attachment
                </a>
              </div>

              <div v-if="leaveRequest.comments">
                <h3 class="text-sm font-medium text-muted-foreground mb-1">Comments</h3>
                <p class="text-sm">{{ leaveRequest.comments }}</p>
              </div>
            </div>

            <!-- Request Timeline -->
            <div class="space-y-4">
              <h3 class="font-medium">Request Timeline</h3>
              <div class="border-l-2 pl-4 space-y-6">
                <div class="relative">
                  <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-primary"></div>
                  <p class="text-sm font-medium">Request Submitted</p>
                  <p class="text-xs text-muted-foreground">
                    {{ new Date(leaveRequest.created_at).toLocaleString() }}
                  </p>
                </div>

                <div v-if="leaveRequest.status !== 'pending'"
                     class="relative">
                  <div class="absolute -left-[25px] w-4 h-4 rounded-full"
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
            </div>
          </div>
        </CardContent>

        <CardFooter v-if="leaveRequest.status === 'pending'"
                    class="flex-col gap-4">
          <Textarea v-model="comments"
                    placeholder="Add comments (optional)"
                    rows="3" />

          <div class="flex justify-end gap-4">
            <Button variant="destructive"
                    :disabled="processing"
                    @click="updateStatus('rejected')">
              Reject Request
            </Button>
            <Button :disabled="processing"
                    @click="updateStatus('approved')">
              Approve Request
            </Button>
          </div>
        </CardFooter>
      </Card>
    </div>
  </AppLayout>
</template>
