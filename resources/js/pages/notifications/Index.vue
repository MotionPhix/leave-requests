<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import {
  Bell,
  CalendarPlus,
  CalendarClock,
  CalendarCheck,
  CalendarX,
  CheckCheck,
  Filter,
  Trash2,
  MoreVertical,
  Eye,
  X
} from 'lucide-vue-next';
import axios from 'axios';
import { useStorage } from '@vueuse/core';
import { toast } from 'vue-sonner';

interface Props {
  notifications: {
    data: Array<{
      id: string;
      type: string;
      title: string;
      message: string;
      data: any;
      read_at: string | null;
      created_at: string;
      action_url: string | null;
      icon: string;
      priority: 'low' | 'medium' | 'high';
    }>;
    links: any;
    meta: any;
  };
  unreadCount: number;
}

const props = defineProps<Props>();

const notifications = ref(props.notifications.data);
const activeTab = useStorage('notification_tabs', 'all');
const isDeleting = ref(false);
const isClearingAll = ref(false);

const iconComponents = {
  'calendar-plus': CalendarPlus,
  'calendar-clock': CalendarClock,
  'calendar-check': CalendarCheck,
  'calendar-x': CalendarX,
  'bell': Bell,
};

const priorityColors = {
  high: 'border-l-red-500',
  medium: 'border-l-yellow-500',
  low: 'border-l-blue-500',
};

const filteredNotifications = computed(() => {
  switch (activeTab.value) {
    case 'unread':
      return notifications.value.filter(n => !n.read_at);
    case 'read':
      return notifications.value.filter(n => n.read_at);
    default:
      return notifications.value;
  }
});

const unreadNotifications = computed(() =>
  notifications.value.filter(n => !n.read_at)
);

const markAsRead = async (id: string) => {
  try {
    await axios.post(`/api/notifications/${id}/mark-as-read`);
    const notification = notifications.value.find(n => n.id === id);
    if (notification) {
      notification.read_at = new Date().toISOString();
    }
    toast({
      title: "Success",
      description: "Notification marked as read",
    });
  } catch (error) {
    console.error('Failed to mark notification as read:', error);
    toast({
      title: "Error",
      description: "Failed to mark notification as read",
      variant: "destructive",
    });
  }
};

const markAllAsRead = async () => {
  try {
    await axios.post('/api/notifications/mark-all-as-read');
    notifications.value = notifications.value.map(notification => ({
      ...notification,
      read_at: new Date().toISOString()
    }));
    toast({
      title: "Success",
      description: "All notifications marked as read",
    });
  } catch (error) {
    console.error('Failed to mark all notifications as read:', error);
    toast({
      title: "Error",
      description: "Failed to mark all notifications as read",
      variant: "destructive",
    });
  }
};

const deleteNotification = async (id: string) => {
  if (isDeleting.value) return;

  isDeleting.value = true;
  try {
    await axios.delete(`/api/notifications/${id}`);
    notifications.value = notifications.value.filter(n => n.id !== id);
    toast.success("Notification deleted successfully");
  } catch (error) {
    console.error('Failed to delete notification:', error);
    toast.error("Failed to delete notification");
  } finally {
    isDeleting.value = false;
  }
};

const clearAllNotifications = async () => {
  if (isClearingAll.value) return;

  isClearingAll.value = true;
  try {
    await axios.delete('/api/notifications/clear-all');
    notifications.value = [];
    toast.success("All notifications cleared successfully");
  } catch (error) {
    console.error('Failed to clear all notifications:', error);
    toast.error("Failed to clear all notifications");
  } finally {
    isClearingAll.value = false;
  }
};

const getIconComponent = (iconName: string) => {
  return iconComponents[iconName as keyof typeof iconComponents] || Bell;
};

const handleNotificationClick = async (notification: any) => {
  if (!notification.read_at) {
    await markAsRead(notification.id);
  }
};

const getRequestDetailsUrl = (notification: any) => {
  // Handle different notification types and generate appropriate URLs
  if (notification.data?.leave_request_id) {
    return `/leave-requests/${notification.data.leave_request_id}`;
  }
  return notification.action_url;
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

console.log('Notifications:', props.notifications.data);
</script>

<template>
  <Head title="Notifications" />

  <AppLayout
    :breadcrumbs="[{ title: 'Notifications', href: '/notifications' }]">
    <div class="space-y-6 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Notifications</h1>
          <p class="text-muted-foreground">
            Stay updated with your latest notifications
          </p>
        </div>

        <div class="flex items-center gap-2">
          <Button
            v-if="unreadNotifications.length > 0"
            @click="markAllAsRead"
            variant="outline">
            <CheckCheck class="w-4 h-4 mr-2" />
            Mark all as read
          </Button>

          <AlertDialog>
            <AlertDialogTrigger as-child>
              <Button
                v-if="notifications.length > 0"
                variant="destructive"
                :disabled="isClearingAll">
                <Trash2 class="w-4 h-4 mr-2" />
                Clear all
              </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>Clear all notifications?</AlertDialogTitle>
                <AlertDialogDescription>
                  This action cannot be undone. This will permanently delete all your notifications.
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction @click="clearAllNotifications" class="bg-destructive text-destructive-foreground hover:bg-destructive/90">
                  Clear all
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>
      </div>

      <Tabs v-model="activeTab" class="w-full">
        <TabsList class="grid w-full grid-cols-3">
          <TabsTrigger value="all" class="flex items-center gap-2">
            <Bell class="w-4 h-4" />
            All
            <Badge variant="secondary" class="ml-1">
              {{ notifications.length }}
            </Badge>
          </TabsTrigger>
          <TabsTrigger value="unread" class="flex items-center gap-2">
            <Filter class="w-4 h-4" />
            Unread
            <Badge variant="destructive" class="ml-1" v-if="unreadNotifications.length > 0">
              {{ unreadNotifications.length }}
            </Badge>
          </TabsTrigger>
          <TabsTrigger value="read" class="flex items-center gap-2">
            <CheckCheck class="w-4 h-4" />
            Read
          </TabsTrigger>
        </TabsList>

        <TabsContent value="all" class="space-y-4">
          <div v-if="filteredNotifications.length === 0" class="text-center py-12">
            <Bell class="w-16 h-16 mx-auto mb-4 text-muted-foreground opacity-50" />
            <h3 class="text-lg font-medium mb-2">No notifications</h3>
            <p class="text-muted-foreground">You're all caught up!</p>
          </div>

          <div v-else class="space-y-4">
            <Card
              v-for="notification in filteredNotifications"
              :key="notification.id"
              class="border-l-4 transition-all hover:shadow-md"
              :class="[
                priorityColors[notification.priority],
                !notification.read_at ? 'bg-accent/20' : ''
              ]">

              <CardContent class="p-4">
                <div class="flex items-start gap-4">
                  <div class="flex-shrink-0 mt-1">
                    <component
                      :is="getIconComponent(notification.icon)"
                      class="w-6 h-6 text-muted-foreground"
                    />
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-2">
                      <h3 class="font-semibold text-lg">
                        {{ notification.title }}
                      </h3>

                      <div class="flex items-center gap-2">
                        <Badge
                          :variant="notification.priority === 'high' ? 'destructive' :
                                   notification.priority === 'medium' ? 'default' : 'secondary'"
                          class="text-xs">
                          {{ notification.priority }}
                        </Badge>

                        <DropdownMenu>
                          <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="sm">
                              <MoreVertical class="w-4 h-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem
                              v-if="!notification.read_at"
                              @click="markAsRead(notification.id)">
                              <CheckCheck class="w-4 h-4 mr-2" />
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
                    </div>

                    <p class="text-muted-foreground mb-4 leading-relaxed">
                      {{ notification.message }}
                    </p>

                    <div class="flex items-center justify-between">
                      <p class="text-sm text-muted-foreground">
                        {{ formatDate(notification.created_at) }}
                      </p>

                      <div class="flex items-center gap-2">
                        <Button
                          v-if="getRequestDetailsUrl(notification)"
                          variant="outline"
                          size="sm"
                          as-child>
                          <Link
                            :href="getRequestDetailsUrl(notification)"
                            @click="handleNotificationClick(notification)">
                            <Eye class="w-4 h-4 mr-2" />
                            View details
                          </Link>
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="unread" class="space-y-4">
          <div v-if="filteredNotifications.length === 0" class="text-center py-12">
            <CheckCheck class="w-16 h-16 mx-auto mb-4 text-muted-foreground opacity-50" />
            <h3 class="text-lg font-medium mb-2">No unread notifications</h3>
            <p class="text-muted-foreground">You're all caught up!</p>
          </div>

          <div v-else class="space-y-4">
            <Card
              v-for="notification in filteredNotifications"
              :key="notification.id"
              class="border-l-4 bg-accent/20 transition-all hover:shadow-md"
              :class="priorityColors[notification.priority]">

              <CardContent class="p-4">
                <div class="flex items-start gap-4">
                  <div class="flex-shrink-0 mt-1">
                    <component
                      :is="getIconComponent(notification.icon)"
                      class="w-6 h-6 text-muted-foreground"
                    />
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-2">
                      <h3 class="font-semibold text-lg">
                        {{ notification.title }}
                      </h3>

                      <div class="flex items-center gap-2">
                        <Badge
                          :variant="notification.priority === 'high' ? 'destructive' :
                                   notification.priority === 'medium' ? 'default' : 'secondary'"
                          class="text-xs">
                          {{ notification.priority }}
                        </Badge>

                        <DropdownMenu>
                          <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="sm">
                              <MoreVertical class="w-4 h-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="markAsRead(notification.id)">
                              <CheckCheck class="w-4 h-4 mr-2" />
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
                    </div>

                    <p class="text-muted-foreground mb-4 leading-relaxed">
                      {{ notification.message }}
                    </p>

                    <div class="flex items-center justify-between">
                      <p class="text-sm text-muted-foreground">
                        {{ formatDate(notification.created_at) }}
                      </p>

                      <div class="flex items-center gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          @click="markAsRead(notification.id)">
                          <CheckCheck class="w-4 h-4 mr-2" />
                          Mark as read
                        </Button>

                        <Button
                          v-if="getRequestDetailsUrl(notification)"
                          variant="outline"
                          size="sm"
                          as-child>
                          <Link
                            :href="getRequestDetailsUrl(notification)"
                            @click="handleNotificationClick(notification)">
                            <Eye class="w-4 h-4 mr-2" />
                            View details
                          </Link>
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="read" class="space-y-4">
          <div v-if="filteredNotifications.length === 0" class="text-center py-12">
            <Bell class="w-16 h-16 mx-auto mb-4 text-muted-foreground opacity-50" />
            <h3 class="text-lg font-medium mb-2">No read notifications</h3>
            <p class="text-muted-foreground">Read notifications will appear here</p>
          </div>

          <div v-else class="space-y-4">
            <Card
              v-for="notification in filteredNotifications"
              :key="notification.id"
              class="border-l-4 opacity-75 transition-all hover:opacity-100 hover:shadow-md"
              :class="priorityColors[notification.priority]">

              <CardContent class="p-4">
                <div class="flex items-start gap-4">
                  <div class="flex-shrink-0 mt-1">
                    <component
                      :is="getIconComponent(notification.icon)"
                      class="w-6 h-6 text-muted-foreground"
                    />
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between mb-2">
                      <h3 class="font-semibold text-lg text-muted-foreground">
                        {{ notification.title }}
                      </h3>

                      <div class="flex items-center gap-2">
                        <Badge
                          :variant="notification.priority === 'high' ? 'destructive' :
                                   notification.priority === 'medium' ? 'default' : 'secondary'"
                          class="text-xs opacity-75">
                          {{ notification.priority }}
                        </Badge>

                        <DropdownMenu>
                          <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="sm">
                              <MoreVertical class="w-4 h-4" />
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
                    </div>

                    <p class="text-muted-foreground mb-4 leading-relaxed">
                      {{ notification.message }}
                    </p>

                    <div class="flex items-center justify-between">
                      <p class="text-sm text-muted-foreground">
                        {{ formatDate(notification.created_at) }}
                      </p>

                      <Button
                        v-if="getRequestDetailsUrl(notification)"
                        variant="outline"
                        size="sm"
                        as-child>
                        <Link
                          :href="getRequestDetailsUrl(notification)"
                          @click="handleNotificationClick(notification)">
                          <Eye class="w-4 h-4 mr-2" />
                          View details
                        </Link>
                      </Button>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
