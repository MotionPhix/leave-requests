<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardFooter
} from '@/components/ui/card';
import {
  ClipboardList,
  FileText,
  UserCircle,
  FileImage,
  FileIcon,
  FileArchive,
  Download,
  Eye,
  Clock,
  CheckCircle2,
  XCircle,
  MessageCircle,
  CalendarClock
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog';
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
const showPreview = ref(false);

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

// Add helper for status color
const getStatusColor = (status: string) => {
  switch (status) {
    case 'approved':
      return 'text-success border-success';
    case 'rejected':
      return 'text-destructive border-destructive';
    default:
      return 'text-warning border-warning';
  }
};

const getFileIcon = (mimeType: string) => {
  if (mimeType.startsWith('image/')) return FileImage;
  if (mimeType === 'application/pdf') return FileIcon;
  if (mimeType.includes('zip') || mimeType.includes('compressed')) return FileArchive;
  if (mimeType.includes('text/')) return FileText;
  return File;
};

const isPreviewable = (mimeType: string) => {
  return mimeType.startsWith('image/') ||
    mimeType === 'application/pdf' ||
    mimeType.includes('text/');
};

const formatFileSize = (bytes: number) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

// Add new interface for timeline events
interface TimelineEvent {
  icon: any;
  title: string;
  description?: string;
  timestamp: string;
  status?: 'success' | 'error' | 'warning' | 'default';
}

// Add computed property for timeline events
const timelineEvents = computed((): TimelineEvent[] => {
  const events: TimelineEvent[] = [
    {
      icon: Clock,
      title: 'Request Submitted',
      description: `${props.leaveRequest.user.name} requested ${props.leaveRequest.total_days} days of ${props.leaveRequest.leave_type.name}`,
      timestamp: props.leaveRequest.created_at,
      status: 'default'
    }
  ];

  if (props.leaveRequest.status !== 'pending') {
    events.push({
      icon: props.leaveRequest.status === 'approved' ? CheckCircle2 : XCircle,
      title: `Request ${props.leaveRequest.status === 'approved' ? 'Approved' : 'Rejected'}`,
      description: props.leaveRequest.comments || undefined,
      timestamp: props.leaveRequest.updated_at,
      status: props.leaveRequest.status === 'approved' ? 'success' : 'error'
    });
  }

  return events;
});

const getTimelineStatusColor = (status: TimelineEvent['status']) => {
  switch (status) {
    case 'success':
      return 'bg-success text-success-foreground';
    case 'error':
      return 'bg-destructive text-destructive-foreground';
    case 'warning':
      return 'bg-warning text-warning-foreground';
    default:
      return 'bg-primary text-primary-foreground';
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head :title="`Leave Request #${leaveRequest.uuid}`" />

    <div class="p-6">
      <!-- Status Banner -->
      <div class="mb-6">
        <div class="flex items-center gap-4">
          <h1 class="text-2xl font-bold">Leave Request Details</h1>
          <Badge
            class="text-base px-4 py-1 capitalize"
            :variant="leaveRequest.status === 'approved'
            ? 'default' : leaveRequest.status === 'rejected'
            ? 'destructive' : 'secondary'">
            {{ leaveRequest.status }}
          </Badge>
        </div>

        <p class="text-muted-foreground mt-2">
          Submitted on {{ leaveRequest.created_at }}
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Employee & Leave Details -->
        <div class="space-y-6">
          <!-- Employee Card -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <UserCircle class="w-5 h-5" />
                Employee Information
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="flex flex-col">
                  <h3 class="font-medium text-lg">{{ leaveRequest.user.name }}</h3>
                  <p class="text-muted-foreground">{{ leaveRequest.user.email }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t">
                  <div>
                    <p class="text-sm text-muted-foreground">Position</p>
                    <p class="font-medium">{{ leaveRequest.user.position }}</p>
                  </div>

                  <div>
                    <p class="text-sm text-muted-foreground">Department</p>
                    <p class="font-medium">{{ leaveRequest.user.department }}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Leave Type Card -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <ClipboardList class="w-5 h-5" />
                Leave Details
              </CardTitle>
            </CardHeader>

            <CardContent>
              <div class="space-y-4">
                <div>
                  <p class="text-sm text-muted-foreground">Type</p>
                  <p class="font-medium">{{ leaveRequest.leave_type.name }}</p>
                </div>

                <div class="pt-4 border-t">
                  <p class="text-sm text-muted-foreground">Duration</p>
                  <p class="font-medium">
                    {{ leaveRequest.start_date }} -
                    {{ leaveRequest.end_date }}
                  </p>

                  <Badge variant="secondary" class="mt-2">
                    A total of {{ leaveRequest.total_days }} days
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Center Column - Reason & Documents -->
        <Card class="lg:col-span-2">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <FileText class="w-5 h-5" />
              Request Information
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-8">
              <!-- Reason Section -->
              <div>
                <h3 class="text-sm font-medium text-muted-foreground mb-2">Reason for Leave</h3>
                <div class="bg-muted p-4 rounded-lg">
                  <p class="text-sm">{{ leaveRequest.reason }}</p>
                </div>
              </div>

              <!-- Documentation Section -->
              <div v-if="leaveRequest.documentation" class="space-y-4">
                <h3 class="text-sm font-medium text-muted-foreground">Supporting Documents</h3>
                <Card>
                  <CardContent class="p-6">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-muted flex items-center justify-center">
                          <component
                            :is="getFileIcon(leaveRequest.documentation.type)"
                            class="w-6 h-6 text-muted-foreground"
                          />
                        </div>
                        <div>
                          <h4 class="font-medium text-sm">
                            {{ leaveRequest.documentation.name }}
                          </h4>
                          <p class="text-xs text-muted-foreground mt-1">
                            {{ formatFileSize(leaveRequest.documentation.size) }}
                          </p>
                        </div>
                      </div>

                      <div class="flex items-center gap-2">
                        <Button
                          v-if="isPreviewable(leaveRequest.documentation.type)"
                          variant="outline"
                          size="sm"
                          class="flex items-center gap-2"
                          @click="showPreview = true"
                        >
                          <Eye class="w-4 h-4" />
                          Preview
                        </Button>

                        <Button
                          variant="outline"
                          size="sm"
                          class="flex items-center gap-2"
                          :href="leaveRequest.documentation.url"
                          download>
                          <Download class="w-4 h-4" />
                          Download
                        </Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <!-- Preview Modal -->
                <Dialog :open="showPreview" @update:open="showPreview = false">
                  <DialogContent class="max-w-3xl max-h-[80vh]">
                    <DialogHeader>
                      <DialogTitle>{{ leaveRequest.documentation.name }}</DialogTitle>
                    </DialogHeader>

                    <div class="mt-4 relative overflow-hidden rounded-lg">
                      <!-- Image Preview -->
                      <img
                        v-if="leaveRequest.documentation.type.startsWith('image/')"
                        :src="leaveRequest.documentation.url"
                        :alt="leaveRequest.documentation.name"
                        class="w-full h-auto"
                      />

                      <!-- PDF Preview -->
                      <iframe
                        v-else-if="leaveRequest.documentation.type === 'application/pdf'"
                        :src="leaveRequest.documentation.url"
                        class="w-full h-[60vh]"
                        type="application/pdf"
                      />

                      <!-- Text Preview -->
                      <pre
                        v-else-if="leaveRequest.documentation.type.includes('text/')"
                        class="p-4 bg-muted rounded-lg overflow-auto max-h-[60vh]"
                      >
                        <code>{{ leaveRequest.documentation.content }}</code>
                      </pre>
                    </div>
                  </DialogContent>
                </Dialog>
              </div>

              <!-- Timeline - New code for timeline events -->
              <div class="border-t pt-6">
                <h3 class="font-medium mb-6 flex items-center gap-2">
                  <CalendarClock class="w-5 h-5" />
                  Request Timeline
                </h3>

                <div class="relative space-y-8">
                  <!-- Timeline Line -->
                  <div class="absolute left-[17px] top-[24px] bottom-0 w-px bg-border"></div>

                  <!-- Timeline Events -->
                  <div
                    v-for="(event, index) in timelineEvents"
                    :key="index"
                    class="relative flex gap-4">
                    <div class="relative">
                      <component
                        :is="event.icon"
                        class="w-9 h-9 rounded-full p-2"
                        :class="getTimelineStatusColor(event.status)"
                      />
                      <!-- Connecting Line (except for last item) -->
                      <div
                        v-if="index !== timelineEvents.length - 1"
                        class="absolute left-1/2 top-9 bottom-[-40px] w-px bg-border">
                      </div>
                    </div>

                    <div class="flex-1 pb-8">
                      <div class="bg-card rounded-lg border p-4">
                        <div class="flex items-start justify-between gap-4">
                          <div>
                            <p class="font-medium">{{ event.title }}</p>
                            <p v-if="event.description"
                               class="text-sm text-muted-foreground mt-1">
                              {{ event.description }}
                            </p>
                          </div>
                          <time class="text-sm text-muted-foreground whitespace-nowrap">
                            {{ event.timestamp }}
                          </time>
                        </div>

                        <!-- Comments section for approval/rejection -->
                        <div v-if="event.status !== 'default' && event.description"
                             class="mt-3 pt-3 border-t flex items-start gap-2">
                          <MessageCircle class="w-4 h-4 text-muted-foreground mt-0.5" />
                          <p class="text-sm text-muted-foreground">
                            {{ event.description }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>

          <!-- Action Footer -->
          <CardFooter v-if="leaveRequest.status === 'pending'"
                      class="flex-col gap-4 border-t">
            <div class="w-full">
              <label class="text-sm font-medium mb-2 block">
                Comments
              </label>
              <Textarea
                v-model="comments"
                placeholder="Add your comments here (optional)"
                rows="3"
              />
            </div>

            <div class="flex justify-end gap-4">
              <Button
                variant="outline"
                :disabled="processing"
                @click="updateStatus('rejected')"
              >
                Reject Request
              </Button>
              <Button
                :disabled="processing"
                @click="updateStatus('approved')"
              >
                Approve Request
              </Button>
            </div>
          </CardFooter>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
