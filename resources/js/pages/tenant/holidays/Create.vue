<script setup lang="ts">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Modal } from '@inertiaui/modal-vue';
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
  Plus,
  Loader2
} from 'lucide-vue-next';
import { Separator } from '@/components/ui/separator';
import { toast } from 'vue-sonner';

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };
const holidayModal = ref(null);

// Holiday type reactive property
const holidayType = ref('single_day');

const form = useForm({
  name: '',
  start_date: '',
  end_date: '',
  type: '',
  description: '',
  color: '#ef4444', // Default red color for holidays
  is_recurring: false,
  recurrence_pattern: '',
  is_visible_to_employees: true,
});

// Update end date when single day is selected
const updateEndDate = () => {
  if (holidayType.value === 'single_day') {
    form.end_date = form.start_date;
  }
};

// Handle recurring change with smart defaults
const handleRecurringChange = (value: boolean) => {
  if (value) {
    form.recurrence_pattern = 'yearly';
    // Auto-suggest recurring for national/religious holidays
    if (['National Holiday', 'Religious Holiday'].includes(form.type)) {
      // Already set to recurring
    }
  } else {
    form.recurrence_pattern = '';
  }
};

// Watch for form type changes to auto-suggest recurring and colors
watch(() => form.type, (newType) => {
  // Auto-suggest recurring for national/religious holidays
  if (['National Holiday', 'Religious Holiday'].includes(newType)) {
    if (!form.is_recurring) {
      form.is_recurring = true;
      form.recurrence_pattern = 'yearly';
    }
  }
  
  // Auto-suggest colors based on holiday type
  const colorMap = {
    'National Holiday': '#dc2626', // Red
    'Religious Holiday': '#7c3aed', // Purple  
    'Company Holiday': '#2563eb', // Blue
    'Floating Holiday': '#059669', // Green
    'Company Closure': '#ea580c', // Orange
  };
  
  if (colorMap[newType] && form.color === '#ef4444') {
    form.color = colorMap[newType];
  }
});

// Watch holiday type to auto-update end date for single day
watch(() => holidayType.value, () => {
  if (holidayType.value === 'single_day' && form.start_date) {
    updateEndDate();
  }
});

// Get dynamic placeholder based on holiday type
const getNamePlaceholder = () => {
  const examples = {
    'National Holiday': 'e.g., Independence Day, Memorial Day',
    'Religious Holiday': 'e.g., Christmas Day, Eid al-Fitr, Diwali',
    'Company Holiday': 'e.g., Company Anniversary, Founders Day',
    'Floating Holiday': 'e.g., Personal Choice Day, Flexible PTO',
    'Company Closure': 'e.g., Year-End Shutdown, Office Renovation',
  };
  
  return examples[form.type] || 'e.g., Christmas Day, Independence Day';
};

const submit = () => {
  form.post(route('tenant.management.holidays.store', {
    tenant_slug: workspace.slug,
    tenant_uuid: workspace.uuid
  }), {
    onSuccess: () => {

      form.reset();
      holidayModal.value?.close();
  
      toast.success('Holiday created successfully!');
    },
  });
};
</script>

<template>
  <Head title="Add Holiday" />
  
  <Modal 
    ref="holidayModal"
     panel-classes="bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg p-6 space-y-6">
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-2xl font-semibold text-neutral-900 dark:text-neutral-100">Add Holiday</h1>
        <p class="text-neutral-600 dark:text-neutral-400">
          Create a new company holiday or observance
        </p>
      </div>

      <Separator />

      <!-- Form -->
      <div class="max-w-2xl">
        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <div class="space-y-4">
              <h2 class="text-lg font-medium text-neutral-900 dark:text-neutral-100">Holiday Details</h2>
              
              <!-- Name -->
              <div class="space-y-2">
                <Label for="name">Holiday Name *</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  type="text"
                  :placeholder="getNamePlaceholder()"
                  :class="{ 'border-destructive': form.errors.name }"
                  required
                />
                <InputError :message="form.errors.name" />
              </div>

              <!-- Holiday Duration Type -->
              <div class="space-y-2">
                <Label>Holiday Duration *</Label>
                <div class="grid grid-cols-2 gap-3">
                  <div class="flex items-center space-x-2">
                    <input
                      id="single_day"
                      v-model="holidayType"
                      type="radio"
                      value="single_day"
                      class="h-4 w-4 text-primary"
                    />
                    <Label for="single_day" class="text-sm font-normal">Single Day</Label>
                  </div>
                  <div class="flex items-center space-x-2">
                    <input
                      id="date_range"
                      v-model="holidayType"
                      type="radio"
                      value="date_range"
                      class="h-4 w-4 text-primary"
                    />
                    <Label for="date_range" class="text-sm font-normal">Date Range</Label>
                  </div>
                </div>
              </div>

              <!-- Single Date (for single day holidays) -->
              <div v-if="holidayType === 'single_day'" class="space-y-2">
                <Label for="holiday_date">Holiday Date *</Label>
                <Input
                  id="holiday_date"
                  v-model="form.start_date"
                  type="date"
                  :class="{ 'border-destructive': form.errors.start_date }"
                  required
                  @input="updateEndDate"
                />
                <InputError :message="form.errors.start_date" />
              </div>

              <!-- Date Range (for multi-day holidays) -->
              <div v-if="holidayType === 'date_range'" class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <Label for="start_date">Start Date *</Label>
                  <Input
                    id="start_date"
                    v-model="form.start_date"
                    type="date"
                    :class="{ 'border-destructive': form.errors.start_date }"
                    required
                  />
                  <InputError :message="form.errors.start_date" />
                </div>
                <div class="space-y-2">
                  <Label for="end_date">End Date *</Label>
                  <Input
                    id="end_date"
                    v-model="form.end_date"
                    type="date"
                    :class="{ 'border-destructive': form.errors.end_date }"
                    required
                  />
                  <InputError :message="form.errors.end_date" />
                </div>
              </div>

              <!-- Type -->
              <div class="space-y-2">
                <Label for="type">Holiday Type *</Label>
                <Select v-model="form.type" required>
                  <SelectTrigger :class="{ 'border-destructive': form.errors.type }">
                    <SelectValue placeholder="Select holiday type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="National Holiday">National Holiday</SelectItem>
                    <SelectItem value="Religious Holiday">Religious Holiday</SelectItem>
                    <SelectItem value="Company Holiday">Company Holiday</SelectItem>
                    <SelectItem value="Floating Holiday">Floating Holiday</SelectItem>
                    <SelectItem value="Company Closure">Company Closure</SelectItem>
                  </SelectContent>
                </Select>
                <InputError :message="form.errors.type" />
                <p class="text-xs text-muted-foreground">
                  National/Religious holidays typically recur yearly, Company Closures are often one-time events
                </p>
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

              <!-- Color -->
              <div class="space-y-2">
                <Label for="color">Holiday Color *</Label>
                <Input
                  id="color"
                  v-model="form.color"
                  type="color"
                  :class="{ 'border-destructive': form.errors.color }"
                  required
                />
                <InputError :message="form.errors.color" />
              </div>

              <!-- Recurring Options -->
              <div class="space-y-3">
                <div class="flex items-center space-x-2">
                  <Checkbox
                    id="is_recurring"
                    v-model="form.is_recurring"
                    @update:modelValue="handleRecurringChange"
                  />
                  <Label for="is_recurring" class="text-sm font-normal">
                    This holiday repeats annually
                  </Label>
                </div>
                
                <div v-if="form.is_recurring" class="ml-6 space-y-3 p-3 bg-muted/30 rounded-md">
                  <p class="text-xs text-muted-foreground">
                    <strong>Example:</strong> Christmas Day will appear on December 25th every year
                  </p>
                  
                  <div v-if="holidayType === 'date_range'" class="text-xs text-amber-600 bg-amber-50 p-2 rounded">
                    <strong>Note:</strong> Multi-day recurring holidays will maintain the same duration each year. 
                    For example, a 3-day company shutdown will always be 3 days starting from the recurring date.
                  </div>

                  <!-- Auto-set recurrence pattern based on context -->
                  <input type="hidden" v-model="form.recurrence_pattern" value="yearly" />
                </div>
                
                <p v-else class="text-xs text-muted-foreground ml-6">
                  This will be a one-time holiday for the specified date(s) only
                </p>
              </div>

              <!-- Visibility to Employees -->
              <div class="flex items-center space-x-2">
                <Checkbox
                  id="is_visible_to_employees"
                  v-model="form.is_visible_to_employees"
                />
                <Label for="is_visible_to_employees" class="text-sm font-normal">
                  Visible to employees
                </Label>
              </div>
              <p class="text-xs text-muted-foreground">
                When enabled, all employees can see this holiday in their calendar
              </p>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="flex items-center justify-end gap-3">
            <Button 
              type="button" 
              variant="outline"
              @click="router.visit(route('tenant.management.holidays.index', { tenant_slug: workspace.slug, tenant_uuid: workspace.uuid }))"
            >
              Cancel
            </Button>
            <Button 
              type="submit" 
              :disabled="form.processing"
              class="inline-flex items-center gap-2"
            >
              <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
              <Plus v-else class="h-4 w-4" />
              {{ form.processing ? 'Creating...' : 'Create Holiday' }}
            </Button>
          </div>
        </form>
      </div>
    </div>
  </Modal>
</template>