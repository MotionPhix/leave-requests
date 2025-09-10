<template>
  <TenantLayout>
    <Head title="Edit Holiday" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold text-foreground">Edit Holiday</h1>
          <p class="text-muted-foreground">
            Update holiday information
          </p>
        </div>
        <Link
          :href="route('tenant.holidays.index', {
            tenant_slug: workspace.slug,
            tenant_uuid: workspace.uuid
          })"
          class="inline-flex items-center gap-2 px-4 py-2 border border-input rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
        >
          <ArrowLeft class="h-4 w-4" />
          Back to Holidays
        </Link>
      </div>

      <!-- Form -->
      <div class="max-w-2xl">
        <form @submit.prevent="submit" class="space-y-6">
          <div class="bg-card border rounded-lg p-6 space-y-6">
            <div class="space-y-4">
              <h2 class="text-lg font-medium text-card-foreground">Holiday Details</h2>
              
              <!-- Name -->
              <div class="space-y-2">
                <Label for="name">Holiday Name *</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  placeholder="e.g., Christmas Day, Independence Day"
                  :class="{ 'border-destructive': form.errors.name }"
                  required
                />
                <InputError :message="form.errors.name" />
              </div>

              <!-- Date -->
              <div class="space-y-2">
                <Label for="date">Date *</Label>
                <Input
                  id="date"
                  v-model="form.date"
                  type="date"
                  :class="{ 'border-destructive': form.errors.date }"
                  required
                />
                <InputError :message="form.errors.date" />
              </div>

              <!-- Type -->
              <div class="space-y-2">
                <Label for="type">Holiday Type *</Label>
                <Select v-model="form.type" required>
                  <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                    <SelectValue placeholder="Select holiday type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Public Holiday">Public Holiday</SelectItem>
                    <SelectItem value="Company Holiday">Company Holiday</SelectItem>
                  </SelectContent>
                </Select>
                <InputError :message="form.errors.type" />
              </div>

              <!-- Description -->
              <div class="space-y-2">
                <Label for="description">Description</Label>
                <Textarea
                  id="description"
                  v-model="form.description"
                  placeholder="Optional description or notes about this holiday"
                  rows="3"
                  :class="{ 'border-destructive': form.errors.description }"
                />
                <InputError :message="form.errors.description" />
              </div>

              <!-- Recurring -->
              <div class="flex items-center space-x-2">
                <Checkbox
                  id="is_recurring"
                  v-model="form.is_recurring"
                />
                <Label for="is_recurring" class="text-sm font-normal">
                  This is a recurring annual holiday
                </Label>
              </div>
              <p class="text-xs text-muted-foreground">
                Recurring holidays will automatically appear every year on the same date
              </p>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex items-center justify-end gap-3">
            <Link
              :href="route('tenant.holidays.index', {
                tenant_slug: workspace.slug,
                tenant_uuid: workspace.uuid
              })"
              class="inline-flex items-center gap-2 px-4 py-2 border border-input rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
            >
              Cancel
            </Link>
            <Button 
              type="submit" 
              :disabled="form.processing"
              class="inline-flex items-center gap-2"
            >
              <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
              <Save v-else class="h-4 w-4" />
              {{ form.processing ? 'Updating...' : 'Update Holiday' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </TenantLayout>
</template>

<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import {
  ArrowLeft,
  Save,
  Loader2
} from 'lucide-vue-next';

interface Holiday {
  id: number;
  uuid: string;
  name: string;
  type: string;
  date: string;
  description: string;
  is_recurring: boolean;
}

const props = defineProps<{
  holiday: Holiday;
}>();

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };

const form = useForm({
  name: props.holiday.name,
  date: props.holiday.date,
  type: props.holiday.type,
  description: props.holiday.description || '',
  is_recurring: props.holiday.is_recurring,
});

const submit = () => {
  form.put(route('tenant.holidays.update', {
    tenant_slug: workspace.slug,
    tenant_uuid: workspace.uuid,
    holiday: props.holiday.id
  }), {
    onSuccess: () => {
      // Redirect will be handled by the controller
    },
  });
};
</script>
