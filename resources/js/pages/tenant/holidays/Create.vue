<script setup lang="ts">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import FormModal from '@/components/modals/FormModal.vue';
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
import { FormDatePicker, FormDateRangePicker, FormNumberField } from '@/components/forms';
import { Plus } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

const page = usePage();
const workspace = page.props.workspace as { uuid: string; slug: string; name: string };

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

// For date range picker
const dateRange = ref<{ start: string; end: string } | null>(null);

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
  } else if (holidayType.value === 'date_range') {
    // Clear single date when switching to range
    form.start_date = '';
    form.end_date = '';
  }
});

// Watch date range changes for multi-day holidays
watch(() => dateRange.value, (newRange) => {
  if (holidayType.value === 'date_range' && newRange) {
    form.start_date = newRange.start;
    form.end_date = newRange.end;
  }
}, { deep: true });

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

const handleSubmit = (close: () => void, modalEmit: (event: string, ...args: any[]) => void) => {
  form.post(route('tenant.management.holidays.store', {
    tenant_slug: workspace.slug,
    tenant_uuid: workspace.uuid
  }), {
    onSuccess: () => {
      form.reset();
      close(); // Close the modal on success
      toast.success('Holiday created successfully!');
    },
  });
};

const handleCancel = (close: () => void, modalEmit: (event: string, ...args: any[]) => void) => {
  close(); // Close the modal
  router.visit(route('tenant.management.holidays.index', { 
    tenant_slug: workspace.slug, 
    tenant_uuid: workspace.uuid 
  }));
};
</script>

<template>
  <Head title="Add Holiday" />
  
  <FormModal
    title="Add Holiday"
    subtitle="Create a new company holiday or observance"
    max-width="xl"
    :is-processing="form.processing"
    submit-text="Create Holiday"
    submit-loading-text="Creating..."
    :submit-icon="Plus"
    @submit="handleSubmit"
    @cancel="handleCancel"
  >
    <!-- Form Content -->
    <div class="space-y-6">
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
        <FormDatePicker
          v-if="holidayType === 'single_day'"
          id="holiday_date"
          v-model="form.start_date"
          label="Holiday Date"
          :error="form.errors.start_date"
          required
          @update:model-value="updateEndDate"
        />

        <!-- Date Range (for multi-day holidays) -->
        <FormDateRangePicker
          v-if="holidayType === 'date_range'"
          id="date_range"
          v-model="dateRange"
          label="Holiday Date Range"
          :error="form.errors.start_date || form.errors.end_date"
          required
        />

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
            
            <div v-if="holidayType === 'date_range'" class="text-xs text-amber-600 bg-amber-50 dark:bg-amber-900/20 p-2 rounded">
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
        <div class="space-y-2">
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
  </FormModal>
</template>