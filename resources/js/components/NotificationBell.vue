<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Popover,
  PopoverContent,
  PopoverTrigger
} from '@/components/ui/popover';
import { Bell } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Button } from '@/components/ui/button';
import { useEchoModel } from '@laravel/echo-vue';
import axios from 'axios';

interface Notification {
  id: string;
  type: string;
  data: {
    leave_request_id: number;
    user_name: string;
    leave_type: string;
    start_date: string;
    end_date: string;
  };
  read_at: string | null;
  created_at: string;
  action_url: string | null;
}

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const { channel } = useEchoModel('App.Models.User', usePage().props.auth.user.id);

const formatDateRange = (start: string, end: string) => {
  const startDate = new Date(start).toLocaleDateString();
  const endDate = new Date(end).toLocaleDateString();
  return `${startDate} - ${endDate}`;
};

const fetchNotifications = async () => {
  try {
    const { data } = await axios.get('/api/notifications');
    notifications.value = data.notifications.data;
    unreadCount.value = data.unreadCount;
  } catch (error) {
    console.error('Failed to fetch notifications:', error);
  }
};

onMounted(() => {
  channel().notification((notification) => {
    console.log(notification);
    notifications.value.unshift(notification);
    unreadCount.value++;
  });

  fetchNotifications();
});

const markAsRead = async (id: string) => {
  try {
    const { data } = await axios.post(`/api/notifications/${id}/mark-as-read`);
    unreadCount.value = data.unreadCount;
    notifications.value = notifications.value.map(notification => {
      if (notification.id === id) {
        return { ...notification, read_at: new Date().toISOString() };
      }
      return notification;
    });
  } catch (error) {
    console.error('Failed to mark notification as read:', error);
  }
};

const getNotificationMessage = (notification: Notification): string => {
  switch (notification.type) {
    case 'LeaveRequestSubmitted':
      return `${notification.data.user_name} has requested ${notification.data.leave_type} from ${formatDateRange(notification.data.start_date, notification.data.end_date)}`;
    default:
      return '';
  }
};
</script>

<template>
  <Popover>
    <PopoverTrigger class="cursor-pointer">
      <div class="relative">
        <Bell class="w-5 h-5" />
        <Badge
          v-if="unreadCount > 0"
          class="absolute -top-2 -right-2 min-w-[20px] h-5">
          {{ unreadCount }}
        </Badge>
      </div>
    </PopoverTrigger>

    <PopoverContent class="w-[350px]" align="end">
      <ScrollArea class="h-[400px]">
        <div
          v-if="notifications.length"
          class="space-y-4">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="flex flex-col gap-1 p-4 rounded-lg hover:bg-accent"
            :class="{ 'bg-accent/50': !notification.read_at }">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium">
                {{ notification.type }}
              </p>

              <Button
                v-if="!notification.read_at"
                variant="ghost"
                size="sm"
                @click="markAsRead(notification.id)">
                Mark as read
              </Button>
            </div>

            <p class="text-sm text-muted-foreground">
              {{ getNotificationMessage(notification) }}
            </p>

            <div class="flex items-center justify-between mt-2">
              <p class="text-xs text-muted-foreground">
                {{ notification.created_at }}
              </p>

              <Link
                v-if="notification.action_url"
                :href="notification.action_url"
                class="text-sm font-medium text-primary hover:underline">
                View details
              </Link>
            </div>
          </div>
        </div>

        <div
          v-else
          class="text-center py-4 text-muted-foreground">
          No notifications
        </div>
      </ScrollArea>
    </PopoverContent>
  </Popover>
</template>
