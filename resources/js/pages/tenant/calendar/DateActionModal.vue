<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Modal, ModalLink } from '@inertiaui/modal-vue';
import { Building2, CalendarDays, Users } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  workspace: any;
  selectedDate: string;
  canManageHolidays: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  childClosed: [];
}>();

// Tenant params for route generation
const tenantParams = computed(() => ({
  tenant_slug: props.workspace.slug,
  tenant_uuid: props.workspace.uuid,
}));

const formattedDate = computed(() => {
  return new Date(props.selectedDate).toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  });
});

// Handle child modal close event
function handleChildClosed() {
  emit('childClosed');
}
</script>

<template>
  <Modal name="date-action-modal" :close-explicitly="true" max-width="md">
    <Card>
      <CardHeader>
        <CardTitle>What would you like to add?</CardTitle>
        <CardDescription> Choose the type of event you want to create for {{ formattedDate }} </CardDescription>
      </CardHeader>

      <CardContent>
        <div class="space-y-3">
          <!-- Add Company Holiday -->
          <ModalLink
            :href="route('tenant.management.holidays.create', tenantParams)"
            :data="{ date: selectedDate }"
            class="flex w-full items-center gap-4 rounded-lg border p-4 text-left transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800"
            @close="handleChildClosed"
          >
            <div class="flex-shrink-0">
              <CalendarDays class="h-8 w-8 text-red-500" />
            </div>
            <div class="flex-1">
              <div class="text-lg font-medium">Company Holiday</div>
              <div class="text-muted-foreground mt-1 text-sm">Add a national or company holiday that applies to all employees</div>
            </div>
          </ModalLink>

          <!-- Add Company Event -->
          <ModalLink
            :href="route('tenant.management.events.create', tenantParams)"
            :data="{ date: selectedDate, type: 'announcement' }"
            class="flex w-full items-center gap-4 rounded-lg border p-4 text-left transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800"
            @close="handleChildClosed"
          >
            <div class="flex-shrink-0">
              <Building2 class="h-8 w-8 text-blue-500" />
            </div>
            <div class="flex-1">
              <div class="text-lg font-medium">Company Event</div>
              <div class="text-muted-foreground mt-1 text-sm">Add an office event, announcement, or meeting for internal communication</div>
            </div>
          </ModalLink>

          <!-- Add Team Meeting -->
          <ModalLink
            :href="route('tenant.management.events.create', tenantParams)"
            :data="{ date: selectedDate, type: 'meeting' }"
            class="flex w-full items-center gap-4 rounded-lg border p-4 text-left transition-colors hover:bg-neutral-50 dark:hover:bg-neutral-800"
            @close="handleChildClosed"
          >
            <div class="flex-shrink-0">
              <Users class="h-8 w-8 text-green-500" />
            </div>
            <div class="flex-1">
              <div class="text-lg font-medium">Team Meeting</div>
              <div class="text-muted-foreground mt-1 text-sm">Schedule a team meeting, training session, or group discussion</div>
            </div>
          </ModalLink>
        </div>

        <div class="mt-6 border-t pt-4">
          <p class="text-muted-foreground text-xs">
            <strong>Note:</strong> <br>
            Only employees can request personal leave. These options are for company-wide events and communications.
          </p>
        </div>
      </CardContent>
    </Card>
  </Modal>
</template>
