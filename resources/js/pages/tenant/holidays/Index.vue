<template>
  <TenantLayout>
    <Head title="Company Holidays" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Company Holidays</h1>
          <p class="text-muted-foreground">
            Manage official company holidays and observances
          </p>
        </div>
        <Link
          :href="route('tenant.holidays.create', {
            tenant_slug: $page.props.workspace.slug,
            tenant_uuid: $page.props.workspace.uuid
          })"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        >
          <Plus class="h-4 w-4" />
          Add Holiday
        </Link>
      </div>

      <!-- Year Navigation -->
      <div class="flex items-center gap-4">
        <Button
          variant="outline"
          size="sm"
          @click="navigateYear(-1)"
        >
          <ChevronLeft class="h-4 w-4" />
          Previous Year
        </Button>
        <div class="flex items-center gap-2">
          <Calendar class="h-5 w-5 text-muted-foreground" />
          <span class="text-lg font-semibold">{{ currentYear }}</span>
        </div>
        <Button
          variant="outline"
          size="sm"
          @click="navigateYear(1)"
        >
          Next Year
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>

      <!-- Holidays by Month -->
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="month in monthsWithHolidays"
          :key="month.name"
          class="bg-card border rounded-lg p-6"
        >
          <h3 class="text-lg font-semibold text-card-foreground mb-4">{{ month.name }}</h3>

          <div v-if="month.holidays.length > 0" class="space-y-3">
            <div
              v-for="holiday in month.holidays"
              :key="holiday.id"
              class="flex items-start justify-between p-3 bg-muted/50 rounded-lg"
            >
              <div class="flex-1">
                <h4 class="font-medium text-sm">{{ holiday.name }}</h4>
                <p class="text-xs text-muted-foreground">
                  {{ formatDate(holiday.date) }}
                  <span v-if="holiday.is_recurring" class="ml-2">
                    <Badge variant="secondary" class="text-xs">Recurring</Badge>
                  </span>
                </p>
                <p v-if="holiday.description" class="text-xs text-muted-foreground mt-1">
                  {{ holiday.description }}
                </p>
              </div>
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="sm">
                    <MoreHorizontal class="h-3 w-3" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem as-child>
                    <Link
                      :href="route('tenant.holidays.edit', {
                        tenant_slug: $page.props.workspace.slug,
                        tenant_uuid: $page.props.workspace.uuid,
                        holiday: holiday.id
                      })"
                    >
                      <Edit class="h-4 w-4 mr-2" />
                      Edit
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    @click="deleteHoliday(holiday)"
                    class="text-destructive"
                  >
                    <Trash2 class="h-4 w-4 mr-2" />
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>

          <div v-else class="text-center py-6 text-muted-foreground">
            <Calendar class="h-8 w-8 mx-auto mb-2 opacity-50" />
            <p class="text-sm">No holidays this month</p>
          </div>
        </div>
      </div>

      <!-- Statistics -->
      <div class="grid gap-4 md:grid-cols-3">
        <div class="bg-card border rounded-lg p-6 text-center">
          <div class="text-2xl font-bold text-card-foreground mb-1">{{ totalHolidays }}</div>
          <div class="text-sm text-muted-foreground">Total Holidays</div>
        </div>
        <div class="bg-card border rounded-lg p-6 text-center">
          <div class="text-2xl font-bold text-card-foreground mb-1">{{ recurringHolidays }}</div>
          <div class="text-sm text-muted-foreground">Recurring Holidays</div>
        </div>
        <div class="bg-card border rounded-lg p-6 text-center">
          <div class="text-2xl font-bold text-card-foreground mb-1">{{ upcomingHolidays }}</div>
          <div class="text-sm text-muted-foreground">Upcoming This Year</div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="holidays.length === 0" class="text-center py-12">
        <div class="mx-auto w-24 h-24 bg-muted rounded-full flex items-center justify-center mb-4">
          <Calendar class="h-8 w-8 text-muted-foreground" />
        </div>
        <h3 class="text-lg font-medium text-foreground mb-2">No holidays added yet</h3>
        <p class="text-muted-foreground mb-6">
          Start by adding your company's official holidays for {{ currentYear }}.
        </p>
        <Link
          :href="route('tenant.holidays.create', {
            tenant_slug: $page.props.workspace.slug,
            tenant_uuid: $page.props.workspace.uuid
          })"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
        >
          <Plus class="h-4 w-4" />
          Add First Holiday
        </Link>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Plus,
  Calendar,
  ChevronLeft,
  ChevronRight,
  MoreHorizontal,
  Edit,
  Trash2
} from 'lucide-vue-next';

interface Holiday {
  id: number;
  name: string;
  description: string | null;
  date: string;
  is_recurring: boolean;
  created_at: string;
  updated_at: string;
}

const props = defineProps<{
  holidays: Holiday[];
  year?: number;
}>();

const currentYear = ref(props.year || new Date().getFullYear());

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

const monthsWithHolidays = computed(() => {
  return monthNames.map((name, index) => {
    const month = index + 1;
    const holidays = props.holidays.filter(holiday => {
      const holidayDate = new Date(holiday.date);
      return holidayDate.getFullYear() === currentYear.value &&
             holidayDate.getMonth() === index;
    });

    return {
      name,
      month,
      holidays: holidays.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
    };
  });
});

const totalHolidays = computed(() => props.holidays.length);
const recurringHolidays = computed(() => props.holidays.filter(h => h.is_recurring).length);
const upcomingHolidays = computed(() => {
  const today = new Date();
  const endOfYear = new Date(currentYear.value, 11, 31);
  return props.holidays.filter(holiday => {
    const holidayDate = new Date(holiday.date);
    return holidayDate >= today && holidayDate <= endOfYear;
  }).length;
});

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric'
  });
};

const navigateYear = (direction: number) => {
  currentYear.value += direction;
  router.get(
    route('tenant.holidays.index', {
      tenant_slug: window.location.pathname.split('/')[1],
      tenant_uuid: window.location.pathname.split('/')[2],
      year: currentYear.value
    }),
    {},
    {
      preserveState: true,
      preserveScroll: true,
    }
  );
};

const deleteHoliday = (holiday: Holiday) => {
  if (confirm(`Are you sure you want to delete "${holiday.name}"? This action cannot be undone.`)) {
    router.delete(
      route('tenant.holidays.destroy', {
        tenant_slug: window.location.pathname.split('/')[1],
        tenant_uuid: window.location.pathname.split('/')[2],
        holiday: holiday.id
      }),
      {
        preserveScroll: true,
      }
    );
  }
};
</script>
