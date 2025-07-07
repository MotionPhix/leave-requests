<script setup lang="ts">
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
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

const userId = usePage().props.auth.user.id

const { channel } = useEchoModel('App.Models.User', userId);

channel().notification((notification) => {
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
          title: notification.title || 'New Notification',
          description: notification.message || 'You have a new notification',
        };
    }
  };

  const config = getToastConfig(notification.type);

  const toastOptions = {
    duration: 6000,
    action: notification.action_url ? {
      label: 'View Details',
      onClick: () => {
        window.location.href = notification.action_url;
      }
    } : undefined,
  };

  switch (config.type) {
    case 'success':
      toast.success(config.description, {
        ...toastOptions,
        description: config.title,
      });
      break;
    case 'error':
      toast.error(config.description, {
        ...toastOptions,
        description: config.title,
      });
      break;
    default:
      toast.info(config.description, {
        ...toastOptions,
        description: config.title,
      });
  }

  /*if (notification.status === 'approved') {

    toast.success(`${notification.title}: ${notification.message}`, {
      duration: 5000,
    })

  } else {

    toast.error(`${notification.title}: ${notification.message}`, {
      duration: 5000,
    })

  }*/
});
</script>

<template>
  <Toaster
    position="top-right"
    richColors
    :toastOptions="{
      style: {
        background: 'hsl(var(--background))',
        border: '1px solid hsl(var(--border))',
        color: 'hsl(var(--foreground))',
      }
    }"
  />

  <AppLayout :breadcrumbs="breadcrumbs">
    <slot />
  </AppLayout>
</template>
