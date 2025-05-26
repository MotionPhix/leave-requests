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
  if (notification.status === 'approved') {

    toast.success(`${notification.title}: ${notification.message}`, {
      duration: 5000,
    })

  } else {

    toast.error(`${notification.title}: ${notification.message}`, {
      duration: 5000,
    })

  }
});
</script>

<template>
  <Toaster position="top-right" richColors />

  <AppLayout :breadcrumbs="breadcrumbs">
    <slot />
  </AppLayout>
</template>
