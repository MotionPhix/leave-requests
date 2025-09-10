<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
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
  Download,
  Clock,
  CheckCircle2,
  XCircle,
  MessageCircle,
  CalendarClock,
  AlertTriangle,
  Building,
  Mail,
  Phone
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog';
import { toast } from 'vue-sonner';
import dayjs from 'dayjs';

type LeaveRequest = {
  uuid: string;
  user: {
    name: string;
    email: string;
    position?: string;
    department?: string;
  };
  leave_type: {
    name: string;
    requires_documentation: boolean;
  };
  start_date: string;
  end_date: string;
  total_days: number;
  reason?: string;
  status: string;
  comments?: string;
  documentation?: {
    name: string;
    url: string;
    size: number;
    type: string;
  };
  created_at: string;
  updated_at: string;
  reviewed_by?: string;
  reviewed_at?: string;
}

const props = defineProps<{
  leaveRequest: LeaveRequest;
}>();

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };

const showApprovalDialog = ref(false);
const showRejectionDialog = ref(false);
const comment = ref('');
const isProcessing = ref(false);

const statusConfig = computed(() => {
  switch (props.leaveRequest.status) {
    case 'approved':
      return {
        icon: CheckCircle2,
        class: 'text-green-600 bg-green-100',
        text: 'Approved'
      };
    case 'rejected':
      return {
        icon: XCircle,
        class: 'text-red-600 bg-red-100',
        text: 'Rejected'
      };
    case 'pending':
      return {
        icon: Clock,
        class: 'text-orange-600 bg-orange-100',
        text: 'Pending Review'
      };
    default:
      return {
        icon: AlertTriangle,
        class: 'text-gray-600 bg-gray-100',
        text: props.leaveRequest.status
      };
  }
});

const updateStatus = (newStatus: 'approved' | 'rejected') => {
  isProcessing.value = true;
  
  router.patch(
    route('tenant.admin.leave-requests.update', {
      tenant_slug: workspace.slug,
      tenant_uuid: workspace.uuid,
      leaveRequest: props.leaveRequest.uuid,
    }),
    { 
      status: newStatus, 
      comment: comment.value || null 
    },
    {
      onSuccess: () => {
        toast.success(`Leave request ${newStatus}`);
        showApprovalDialog.value = false;
        showRejectionDialog.value = false;
        comment.value = '';
      },
      onError: () => {
        toast.error('Failed to update leave request');
      },
      onFinish: () => {
        isProcessing.value = false;
      }
    }
  );
};

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getPriorityLevel = () => {
  const daysTillStart = dayjs(props.leaveRequest.start_date).diff(dayjs(), 'days');
  if (daysTillStart <= 3 && props.leaveRequest.status === 'pending') return 'urgent';
  if (daysTillStart <= 7 && props.leaveRequest.status === 'pending') return 'high';
  return 'normal';
};
</script>

<template>
  <TenantLayout>
    <div class="space-y-6 max-w-4xl">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2 mb-2">
            <h1 class="text-3xl font-bold tracking-tight">Leave Request Details</h1>
            <Badge v-if="getPriorityLevel() !== 'normal'" variant="secondary" class="gap-1">
              <AlertTriangle class="h-3 w-3" />
              {{ getPriorityLevel() }} Priority
            </Badge>
          </div>
          <p class="text-muted-foreground">
            Review and manage leave request for {{ leaveRequest.user.name }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <component
            :is="statusConfig.icon"
            :class="statusConfig.class"
            class="w-8 h-8 p-1.5 rounded-full"
          />
          <div>
            <p class="font-semibold" :class="statusConfig.class.split(' ')[0]">
              {{ statusConfig.text }}
            </p>
            <p v-if="leaveRequest.reviewed_at" class="text-xs text-muted-foreground">
              {{ dayjs(leaveRequest.reviewed_at).format('MMM D, YYYY h:mm A') }}
            </p>
          </div>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Employee Information -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <UserCircle class="w-5 h-5" />
                Employee Information
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                  <span class="text-2xl font-bold">
                    {{ leaveRequest.user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div class="flex-1">
                  <h3 class="text-xl font-semibold">{{ leaveRequest.user.name }}</h3>
                  <div class="flex items-center gap-4 mt-2 text-sm text-muted-foreground">
                    <div class="flex items-center gap-1">
                      <Mail class="w-4 h-4" />
                      {{ leaveRequest.user.email }}
                    </div>
                    <div v-if="leaveRequest.user.position" class="flex items-center gap-1">
                      <Building class="w-4 h-4" />
                      {{ leaveRequest.user.position }}
                    </div>
                  </div>
                  <p v-if="leaveRequest.user.department" class="text-sm text-muted-foreground mt-1">
                    Department: {{ leaveRequest.user.department }}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Leave Details -->
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <CalendarClock class="w-5 h-5" />
                Leave Details
              </CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="grid md:grid-cols-2 gap-6">
                <div>
                  <h4 class="font-semibold mb-3">Leave Information</h4>
                  <div class="space-y-3">
                    <div>
                      <label class="text-sm font-medium text-muted-foreground">Leave Type</label>
                      <div class="flex items-center gap-2 mt-1">
                        <Badge variant="outline" class="font-medium">
                          {{ leaveRequest.leave_type.name }}
                        </Badge>
                        <FileText v-if="leaveRequest.leave_type.requires_documentation" class="w-4 h-4 text-muted-foreground" />
                      </div>
                    </div>
                    <div>
                      <label class="text-sm font-medium text-muted-foreground">Duration</label>
                      <p class="font-semibold mt-1">{{ leaveRequest.total_days }} days</p>
                    </div>
                  </div>
                </div>

                <div>
                  <h4 class="font-semibold mb-3">Timeline</h4>
                  <div class="space-y-3">
                    <div>
                      <label class="text-sm font-medium text-muted-foreground">Start Date</label>
                      <p class="font-medium mt-1">{{ dayjs(leaveRequest.start_date).format('dddd, MMMM D, YYYY') }}</p>
                    </div>
                    <div>
                      <label class="text-sm font-medium text-muted-foreground">End Date</label>
                      <p class="font-medium mt-1">{{ dayjs(leaveRequest.end_date).format('dddd, MMMM D, YYYY') }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="leaveRequest.reason">
                <label class="text-sm font-medium text-muted-foreground">Reason</label>
                <div class="mt-2 p-4 bg-muted/50 rounded-lg">
                  <p class="text-sm">{{ leaveRequest.reason }}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Management Comments -->
          <Card v-if="leaveRequest.comments || leaveRequest.reviewed_by">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <MessageCircle class="w-5 h-5" />
                Management Review
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-3">
                <div v-if="leaveRequest.reviewed_by">
                  <label class="text-sm font-medium text-muted-foreground">Reviewed By</label>
                  <p class="font-medium">{{ leaveRequest.reviewed_by }}</p>
                </div>
                <div v-if="leaveRequest.comments">
                  <label class="text-sm font-medium text-muted-foreground">Comments</label>
                  <div class="mt-2 p-4 bg-muted/50 rounded-lg">
                    <p class="text-sm">{{ leaveRequest.comments }}</p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Action Buttons -->
          <Card v-if="leaveRequest.status === 'pending'">
            <CardHeader>
              <CardTitle>Review Actions</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <Button 
                @click="showApprovalDialog = true"
                class="w-full bg-green-600 hover:bg-green-700"
                :disabled="isProcessing">
                <CheckCircle2 class="w-4 h-4 mr-2" />
                Approve Request
              </Button>
              <Button 
                @click="showRejectionDialog = true"
                variant="outline" 
                class="w-full text-red-600 border-red-200 hover:bg-red-50"
                :disabled="isProcessing">
                <XCircle class="w-4 h-4 mr-2" />
                Reject Request
              </Button>
            </CardContent>
          </Card>

          <!-- Documentation -->
          <Card v-if="leaveRequest.documentation">
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <FileText class="w-5 h-5" />
                Supporting Documents
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div class="flex items-center justify-between p-3 border rounded-lg">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
                    <FileText class="w-5 h-5" />
                  </div>
                  <div>
                    <p class="font-medium text-sm">{{ leaveRequest.documentation.name }}</p>
                    <p class="text-xs text-muted-foreground">
                      {{ formatFileSize(leaveRequest.documentation.size) }}
                    </p>
                  </div>
                </div>
                <Button
                  size="sm"
                  variant="ghost"
                  :as="'a'"
                  :href="leaveRequest.documentation.url"
                  target="_blank">
                  <Download class="w-4 h-4" />
                </Button>
              </div>
            </CardContent>
          </Card>

          <!-- Request Timeline -->
          <Card>
            <CardHeader>
              <CardTitle>Request Timeline</CardTitle>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                    <ClipboardList class="w-4 h-4 text-blue-600" />
                  </div>
                  <div>
                    <p class="font-medium text-sm">Request Submitted</p>
                    <p class="text-xs text-muted-foreground">
                      {{ dayjs(leaveRequest.created_at).format('MMM D, YYYY h:mm A') }}
                    </p>
                  </div>
                </div>

                <div v-if="leaveRequest.reviewed_at" class="flex items-start gap-3">
                  <component
                    :is="statusConfig.icon"
                    :class="statusConfig.class"
                    class="w-8 h-8 p-1.5 rounded-full"
                  />
                  <div>
                    <p class="font-medium text-sm">{{ statusConfig.text }}</p>
                    <p class="text-xs text-muted-foreground">
                      {{ dayjs(leaveRequest.reviewed_at).format('MMM D, YYYY h:mm A') }}
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <!-- Approval Dialog -->
    <Dialog :open="showApprovalDialog" @update:open="showApprovalDialog = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Approve Leave Request</DialogTitle>
          <DialogDescription>
            Are you sure you want to approve this leave request for {{ leaveRequest.user.name }}?
          </DialogDescription>
        </DialogHeader>
        
        <div class="space-y-4">
          <div>
            <Label for="approval-comment">Comments (Optional)</Label>
            <Textarea
              id="approval-comment"
              v-model="comment"
              placeholder="Add any comments or notes..."
              class="mt-1"
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showApprovalDialog = false" :disabled="isProcessing">
            Cancel
          </Button>
          <Button 
            @click="updateStatus('approved')"
            :disabled="isProcessing"
            class="bg-green-600 hover:bg-green-700">
            <CheckCircle2 class="w-4 h-4 mr-2" />
            {{ isProcessing ? 'Processing...' : 'Approve Request' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Rejection Dialog -->
    <Dialog :open="showRejectionDialog" @update:open="showRejectionDialog = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Reject Leave Request</DialogTitle>
          <DialogDescription>
            Please provide a reason for rejecting this leave request. This will be communicated to {{ leaveRequest.user.name }}.
          </DialogDescription>
        </DialogHeader>
        
        <div class="space-y-4">
          <div>
            <Label for="rejection-comment">Rejection Reason *</Label>
            <Textarea
              id="rejection-comment"
              v-model="comment"
              placeholder="Please explain why this request is being rejected..."
              class="mt-1"
              required
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="showRejectionDialog = false" :disabled="isProcessing">
            Cancel
          </Button>
          <Button 
            @click="updateStatus('rejected')"
            :disabled="isProcessing || !comment.trim()"
            variant="destructive">
            <XCircle class="w-4 h-4 mr-2" />
            {{ isProcessing ? 'Processing...' : 'Reject Request' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </TenantLayout>
</template>
