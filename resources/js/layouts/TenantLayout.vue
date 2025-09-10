<script setup lang="ts">
import TenantSidebarLayout from '@/layouts/tenant/TenantSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import 'vue-sonner/style.css'
import { toast, Toaster } from 'vue-sonner';
import { usePage } from '@inertiajs/vue3'
import { useEchoModel } from '@laravel/echo-vue';

interface Props {
  breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => []
});

interface AuthUser {
  id: string | number;
}

interface PageProps extends Record<string, any> {
  auth: {
    user: AuthUser;
  };
}

const page = usePage<PageProps>();
const userId = page.props.auth.user.id;

const { channel } = useEchoModel('App.Models.User', userId);

channel().notification((notification: any) => {
  console.log('Toast notification received:', notification);
  const getToastConfig = (type: string) => {
    switch (type) {
      case 'leave_request_approved':
        return {
          type: 'success',
          title: '✅ Leave Request Approved',
          description: notification.message || 'Your leave request has been approved',
        };
      case 'leave_request_rejected':
        return {
          type: 'error',
          title: '❌ Leave Request Rejected',
          description: notification.message || 'Your leave request has been rejected',
        };
      case 'leave_request_submitted':
        return {
          type: 'info',
          title: '📋 New Leave Request',
          description: notification.message || 'A new leave request has been submitted',
        };
      case 'leave_request_updated':
        return {
          type: 'info',
          title: '🔄 Leave Request Updated',
          description: notification.message || 'A leave request has been updated',
        };
      default:
        return {
          type: 'info',
          title: 'Notification',
          description: notification.message || 'You have a new notification',
        };
    }
  };

  const config = getToastConfig(notification.type);

  // Show toast based on type
  switch (config.type) {
    case 'success':
      toast.success(config.title, {
        description: config.description,
      });
      break;
    case 'error':
      toast.error(config.title, {
        description: config.description,
      });
      break;
    case 'info':
    default:
      toast.info(config.title, {
        description: config.description,
      });
      break;
  }
});
</script>

<template>
  <Toaster
    position="top-right"
    :duration="4000"
    :close-button="true"
    rich-colors
  />

  <TenantSidebarLayout
    :breadcrumbs="breadcrumbs">
    <slot />
  </TenantSidebarLayout>
</template>
