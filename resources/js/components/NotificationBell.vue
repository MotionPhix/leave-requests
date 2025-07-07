<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  Popover,
  PopoverContent,
  PopoverTrigger
} from '@/components/ui/popover';
import {
  Bell,
  CalendarPlus,
  CalendarClock,
  CalendarCheck,
  CalendarX,
  MoreHorizontal,
  Check,
  CheckCheck,
  Trash2,
  Eye
} from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useEchoModel } from '@laravel/echo-vue';
import axios from 'axios';

interface NotificationData {
  leave_request_id?: number;
  user_name?: string;
  leave_type?: string;
  start_date?: string;
  end_date?: string;
  status?: string;
  [key: string]: any;
}

interface Notification {
  id: string;
  type: string;
  title: string;
  message: string;
  data: NotificationData;
  read_at: string | null;
  created_at: string;
  action_url: string | null;
  icon: string;
  priority: 'low' | 'medium' | 'high';
}

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const isLoading = ref(false);
const isOpen = ref(false);
const isDeleting = ref(false);

const { channel } = useEchoModel('App.Models.User', usePage().props.auth.user.id);

const iconComponents = {
  'calendar-plus': CalendarPlus,
  'calendar-clock': CalendarClock,
  'calendar-check': CalendarCheck,
  'calendar-x': CalendarX,
  'bell': Bell,
};

const priorityColors = {
  high: 'border-l-red-500 bg-red-50/50',
  medium: 'border-l-yellow-500 bg-yellow-50/50',
  low: 'border-l-blue-500 bg-blue-50/50',
};

const unreadNotifications = computed(() =>
  notifications.value.filter(n => !n.read_at)
);

const readNotifications = computed(() =>
  notifications.value.filter(n => n.read_at)
);

const fetchNotifications = async () => {
  if (isLoading.value) return;

  isLoading.value = true;
  try {
    const { data } = await axios.get('/api/notifications');
    notifications.value = data.notifications;
    unreadCount.value = data.unreadCount;
  } catch (error) {
    console.error('Failed to fetch notifications:', error);
  } finally {
    isLoading.value = false;
  }
};

const markAsRead = async (id: string) => {
  try {
    const { data } = await axios.post(`/api/notifications/${id}/mark-as-read`);
    unreadCount.value = data.unreadCount;

    // Update the notification in the local state
    const notificationIndex = notifications.value.findIndex(n => n.id === id);
    if (notificationIndex !== -1) {
      notifications.value[notificationIndex].read_at = new Date().toISOString();
    }
  } catch (error) {
    console.error('Failed to mark notification as read:', error);
  }
};

const markAllAsRead = async () => {
  try {
    await axios.post('/api/notifications/mark-all-as-read');
    notifications.value = notifications.value.map(notification => ({
      ...notification,
      read_at: new Date().toISOString()
    }));
    unreadCount.value = 0;
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error);
  }
};

const deleteNotification = async (id: string) => {
  if (isDeleting.value) return;

  isDeleting.value = true;
  try {
    await axios.delete(`/api/notifications/${id}`);
    notifications.value = notifications.value.filter(n => n.id !== id);
    unreadCount.value = unreadNotifications.value.length;
  } catch (error) {
    console.error('Failed to delete notification:', error);
  } finally {
    isDeleting.value = false;
  }
};

const handleNotificationClick = async (notification: Notification) => {
  if (!notification.read_at) {
    await markAsRead(notification.id);
  }

  if (notification.action_url) {
    // Close the popover before navigation
    isOpen.value = false;
  }
};

const getIconComponent = (iconName: string) => {
  return iconComponents[iconName as keyof typeof iconComponents] || Bell;
};

const getRequestDetailsUrl = (notification: Notification) => {
  return notification.action_url;
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  // Listen for new notifications
  channel().notification((notification: Notification) => {
    console.log('New notification received:', notification);
    notifications.value.unshift(notification);
    unreadCount.value++;
  });

  fetchNotifications();
});
</script>

<template>
  <Popover v-model:open="isOpen">
    <PopoverTrigger as-child>
      <Button variant="ghost" size="icon" class="relative">
        <Bell class="w-5 h-5" />
        <Badge
          v-if="unreadCount > 0"
          class="absolute -top-1 -right-1 w-5 h-5 text-xs flex items-center justify-center p-0 min-w-[20px]"
          variant="destructive">
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </Badge>
      </Button>
    </PopoverTrigger>

    <PopoverContent class="w-[420px] p-0" align="end">
      <div class="flex items-center justify-between p-4 border-b">
        <h3 class="font-semibold text-lg">Notifications</h3>

        <div class="flex items-center gap-2">
          <Button
            v-if="unreadCount > 0"
            variant="ghost"
            size="sm"
            @click="markAllAsRead"
            class="text-xs">
            <CheckCheck class="w-4 h-4 mr-1" />
            Mark all read
          </Button>

          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="ghost" size="icon" class="w-8 h-8">
                <MoreHorizontal class="w-4 h-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem as-child>
                <Link href="/notifications">
                  View all notifications
                </Link>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      <ScrollArea class="h-[500px]">
        <div v-if="isLoading" class="p-4 text-center text-muted-foreground">
          Loading notifications...
        </div>

        <div v-else-if="notifications.length === 0" class="p-8 text-center text-muted-foreground">
          <Bell class="w-12 h-12 mx-auto mb-4 opacity-50" />
          <p class="text-sm">No notifications yet</p>
        </div>

        <div v-else class="divide-y">
          <!-- Unread Notifications -->
          <div v-if="unreadNotifications.length > 0">
            <div class="px-4 py-2 bg-muted/50">
              <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Unread ({{ unreadNotifications.length }})
              </p>
            </div>

            <div
              v-for="notification in unreadNotifications"
              :key="notification.id"
              class="relative border-l-4 transition-colors hover:bg-accent/50"
              :class="priorityColors[notification.priority]">

              <div class="p-4">
                <div class="flex items-start gap-3">
                  <div class="flex-shrink-0 mt-1">
                    <component
                      :is="getIconComponent(notification.icon)"
                      class="w-5 h-5 text-muted-foreground"
                    />
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between mb-1">
                      <div class="flex-1">
                        <p class="text-sm font-medium text-foreground mb-1">
                          {{ notification.title }}
                        </p>
                        <Badge
                          :variant="notification.priority === 'high' ? 'destructive' :
                                   notification.priority === 'medium' ? 'default' : 'secondary'"
                          class="text-xs mb-2">
                          {{ notification.priority }}
                        </Badge>
                      </div>

                      <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                          <Button variant="ghost" size="sm" class="h-6 w-6 p-0">
                            <MoreHorizontal class="w-3 h-3" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem @click="markAsRead(notification.id)">
                            <Check class="w-4 h-4 mr-2" />
                            Mark as read
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            v-if="getRequestDetailsUrl(notification)"
                            as-child>
                            <Link
                              :href="getRequestDetailsUrl(notification)"
                              @click="handleNotificationClick(notification)">
                              <Eye class="w-4 h-4 mr-2" />
                              View details
                            </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            @click="deleteNotification(notification.id)"
                            class="text-destructive focus:text-destructive">
                            <Trash2 class="w-4 h-4 mr-2" />
                            Delete
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>

                    <p class="text-sm text-muted-foreground mb-2 leading-relaxed">
                      {{ notification.message }}
                    </p>

                    <div class="flex items-center justify-between">
                      <p class="text-xs text-muted-foreground">
                        {{ formatDate(notification.created_at) }}
                      </p>

                      <div class="flex items-center gap-1">
                        <Button
                          variant="ghost"
                          size="sm"
                          @click="markAsRead(notification.id)"
                          class="text-xs h-6 px-2">
                          <Check class="w-3 h-3 mr-1" />
                          Mark as read
                        </Button>

                        <Button
                          v-if="getRequestDetailsUrl(notification)"
                          variant="outline"
                          size="sm"
                          as-child
                          class="text-xs h-6 px-2">
                          <Link
                            :href="getRequestDetailsUrl(notification)"
                            @click="handleNotificationClick(notification)">
                            <Eye class="w-3 h-3 mr-1" />
                            View Leave Request
                          </Link>
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Read Notifications -->
          <div v-if="readNotifications.length > 0">
            <div class="px-4 py-2 bg-muted/30">
              <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">
                Earlier
              </p>
            </div>

            <div
              v-for="notification in readNotifications.slice(0, 10)"
              :key="notification.id"
              class="relative opacity-75 transition-opacity hover:opacity-100">

              <div class="p-4">
                <div class="flex items-start gap-3">
                  <div class="flex-shrink-0 mt-1">
                    <component
                      :is="getIconComponent(notification.icon)"
                      class="w-4 h-4 text-muted-foreground"
                    />
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between mb-1">
                      <div class="flex-1">
                        <p class="text-sm font-medium text-muted-foreground mb-1">
                          {{ notification.title }}
                        </p>
                        <Badge
                          :variant="notification.priority === 'high' ? 'destructive' :
                                   notification.priority === 'medium' ? 'default' : 'secondary'"
                          class="text-xs mb-2 opacity-75 capitalize">
                          {{ notification.priority }}
                        </Badge>
                      </div>

                      <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                          <Button variant="ghost" size="sm" class="h-6 w-6 p-0">
                            <MoreHorizontal class="w-3 h-3" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            v-if="getRequestDetailsUrl(notification)"
                            as-child>
                            <Link
                              :href="getRequestDetailsUrl(notification)"
                              @click="handleNotificationClick(notification)">
                              <Eye class="w-4 h-4 mr-2" />
                              View details
                            </Link>
                          </DropdownMenuItem>
                          <DropdownMenuItem
                            @click="deleteNotification(notification.id)"
                            class="text-destructive focus:text-destructive">
                            <Trash2 class="w-4 h-4 mr-2" />
                            Delete
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>

                    <p class="text-sm text-muted-foreground mb-2 leading-relaxed">
                      {{ notification.message }}
                    </p>

                    <div class="flex items-center justify-between">
                      <p class="text-xs text-muted-foreground">
                        {{ notification.created_at }}
                      </p>

                      <Button
                        v-if="getRequestDetailsUrl(notification)"
                        variant="outline"
                        size="sm"
                        as-child
                        class="text-xs h-6 px-2">
                        <Link
                          :href="getRequestDetailsUrl(notification)"
                          @click="handleNotificationClick(notification)">
                          <Eye class="w-3 h-3 mr-1" />
                          View
                        </Link>
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </ScrollArea>

      <Separator />

      <div class="p-3">
        <Link
          href="/notifications"
          class="block w-full text-center text-sm font-medium text-primary hover:underline">
          View all notifications
        </Link>
      </div>
    </PopoverContent>
  </Popover>
</template>
