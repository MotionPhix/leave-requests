<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { MailCheck, Bell, BellOff } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import type { BreadcrumbItem } from '@/types';

interface Notification {
  id: string;
  type: string;
  data: Record<string, any>;
  read_at: string | null;
  created_at: string;
  action_url: string | null;
}

interface Props {
  notifications: {
    data: Notification[];
    meta: {
      current_page: number;
      from: number;
      last_page: number;
      per_page: number;
      to: number;
      total: number;
    };
    links: Array<{
      url: string | null;
      label: string;
      active: boolean;
    }>;
  };
  unreadCount: number;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Notifications',
    href: route('notifications.index')
  }
];

const getNotificationIcon = (type: string) => {
  switch (type) {
    case 'Leave Request Submitted':
      return Bell;
    case 'Leave Request Status Updated':
      return MailCheck;
    default:
      return BellOff;
  }
};

const getStatusColor = (status: string | undefined) => {
  switch (status?.toLowerCase()) {
    case 'approved':
      return 'success';
    case 'rejected':
      return 'destructive';
    case 'pending':
      return 'warning';
    default:
      return 'secondary';
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Notifications" />

    <div class="p-6">
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <CardTitle class="flex items-center gap-2">
              <Bell class="w-5 h-5" />
              Notifications
            </CardTitle>
            <Link v-if="unreadCount > 0"
                  :href="route('notifications.markAllAsRead')"
                  method="post"
                  as="button">
              <Button variant="outline" size="sm">
                Mark all as read
              </Button>
            </Link>
          </div>
        </CardHeader>
        <CardContent>
          <div v-if="notifications.data.length > 0" class="space-y-4">
            <div v-for="notification in notifications.data"
                 :key="notification.id"
                 class="flex items-start gap-4 p-4 rounded-lg transition-colors"
                 :class="{ 'bg-accent': !notification.read_at }">
              <component :is="getNotificationIcon(notification.type)"
                        class="w-5 h-5 mt-1 text-muted-foreground" />

              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-4">
                  <p class="font-medium">
                    {{ notification.type }}
                  </p>
                  <div class="flex items-center gap-2">
                    <Badge v-if="notification.data.status"
                           :variant="getStatusColor(notification.data.status)">
                      {{ notification.data.status }}
                    </Badge>
                    <Link v-if="!notification.read_at"
                          :href="route('notifications.markAsRead', notification.id)"
                          method="post"
                          as="button">
                      <Button variant="ghost" size="sm">
                        Mark as read
                      </Button>
                    </Link>
                  </div>
                </div>

                <p class="text-sm text-muted-foreground mt-1">
                  {{ notification.data.message || notification.data.user_name }}
                </p>

                <div class="flex items-center justify-between mt-2">
                  <p class="text-xs text-muted-foreground">
                    {{ notification.created_at }}
                  </p>
                  <Link v-if="notification.action_url"
                        :href="notification.action_url"
                        class="text-sm font-medium text-primary hover:underline">
                    View details
                  </Link>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="py-12 text-center text-muted-foreground">
            <BellOff class="w-8 h-8 mx-auto mb-4" />
            <p>No notifications yet</p>
          </div>

          <Pagination v-if="notifications.data.length"
                     :links="notifications.links"
                     class="mt-4" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
