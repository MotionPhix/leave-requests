# Enhanced Form Components

This directory contains enhanced form components that provide a consistent API for handling complex inputs like dates, date ranges, and numbers with proper validation integration.

## Components

### FormDatePicker

Enhanced date picker that returns HTML-compatible date strings (`YYYY-MM-DD` format) with built-in label, validation, and error handling.

```vue
<script setup>
import { FormDatePicker } from '@/components/forms'

const form = useForm({
  birthday: '', // Will be YYYY-MM-DD format
})
</script>

<template>
  <FormDatePicker
    id="birthday"
    v-model="form.birthday"
    label="Birth Date"
    :error="form.errors.birthday"
    placeholder="Select your birth date"
    min="1900-01-01"
    max="2024-12-31"
    required
  />
</template>
```

#### Props
- `label?: string` - Field label
- `placeholder?: string` - Placeholder text (default: "Pick a date")
- `disabled?: boolean` - Disable the picker
- `required?: boolean` - Show required indicator
- `min?: string` - Minimum date in YYYY-MM-DD format
- `max?: string` - Maximum date in YYYY-MM-DD format
- `id?: string` - Input ID for labels
- `error?: string` - Validation error message
- `class?: string` - Additional CSS classes

#### Features
- **HTML Compatible**: Returns `YYYY-MM-DD` format strings
- **Internationalized**: Uses @internationalized/date for proper locale handling
- **Validation Ready**: Built-in error display and styling
- **Accessible**: Proper labels and ARIA attributes
- **Date Constraints**: Support for min/max date validation

### FormDateRangePicker

Enhanced date range picker that returns an object with `start` and `end` properties in `YYYY-MM-DD` format.

```vue
<script setup>
import { FormDateRangePicker } from '@/components/forms'

const form = useForm({
  vacation_dates: null, // Will be { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' } or null
})
</script>

<template>
  <FormDateRangePicker
    id="vacation_range"
    v-model="form.vacation_dates"
    label="Vacation Period"
    :error="form.errors.vacation_dates"
    placeholder="Select date range"
    required
  />
</template>
```

#### Props
- `label?: string` - Field label
- `placeholder?: string` - Placeholder text (default: "Pick a date range")
- `disabled?: boolean` - Disable the picker
- `required?: boolean` - Show required indicator
- `id?: string` - Input ID for labels
- `error?: string` - Validation error message
- `class?: string` - Additional CSS classes

#### Return Value
```typescript
interface DateRangeStrings {
  start: string; // YYYY-MM-DD format
  end: string;   // YYYY-MM-DD format
}
```

#### Features
- **HTML Compatible**: Returns date strings in standard format
- **Partial Selection**: Shows partial state when only start date is selected
- **Two-Month Calendar**: Shows two months for easier range selection
- **Validation Ready**: Integrated error handling and display

### FormNumberField

Enhanced number input with optional increment/decrement buttons and proper validation.

```vue
<script setup>
import { FormNumberField } from '@/components/forms'

const form = useForm({
  quantity: undefined, // Will be a number
  price: 0,
})
</script>

<template>
  <!-- Basic number input -->
  <FormNumberField
    id="quantity"
    v-model="form.quantity"
    label="Quantity"
    :error="form.errors.quantity"
    :min="1"
    :max="100"
    :step="1"
    placeholder="Enter quantity"
    required
  />
  
  <!-- With inline buttons -->
  <FormNumberField
    id="price"
    v-model="form.price"
    label="Price"
    :error="form.errors.price"
    :min="0"
    :step="0.01"
    :show-buttons="true"
    button-position="inline"
    placeholder="0.00"
  />
  
  <!-- With stacked buttons -->
  <FormNumberField
    id="rating"
    v-model="form.rating"
    label="Rating"
    :min="1"
    :max="10"
    :show-buttons="true"
    button-position="stacked"
  />
  
  <!-- Simple number input without buttons -->
  <FormNumberField
    id="age"
    v-model="form.age"
    label="Age"
    :show-buttons="false"
    placeholder="Enter age"
  />
</template>
```

#### Props
- `label?: string` - Field label
- `placeholder?: string` - Placeholder text
- `required?: boolean` - Show required indicator
- `id?: string` - Input ID for labels
- `error?: string` - Validation error message
- `class?: string` - Additional CSS classes
- `showButtons?: boolean` - Show increment/decrement buttons (default: true)
- `buttonPosition?: 'inline' | 'stacked'` - Button layout (default: 'inline')
- `min?: number` - Minimum value
- `max?: number` - Maximum value
- `step?: number` - Step increment
- `disabled?: boolean` - Disable the field

#### Features
- **Number Type**: Returns actual numbers, not strings
- **Flexible Buttons**: Choose inline, stacked, or no buttons
- **Validation Integration**: Built-in error styling and display
- **Constraints**: Full support for min, max, and step validation
- **Accessible**: Proper ARIA labels and keyboard navigation

## Integration Examples

### With Laravel Form Requests

Backend validation works seamlessly with these components:

```php
// In your Form Request
public function rules()
{
    return [
        'start_date' => 'required|date|after:today',
        'end_date' => 'required|date|after:start_date',
        'vacation_dates.start' => 'required|date',
        'vacation_dates.end' => 'required|date|after:vacation_dates.start',
        'quantity' => 'required|integer|min:1|max:100',
        'price' => 'required|numeric|min:0',
    ];
}
```

### With Inertia Forms

```vue
<script setup>
import { useForm } from '@inertiajs/vue3'
import { FormDatePicker, FormDateRangePicker, FormNumberField } from '@/components/forms'

const form = useForm({
  event_date: '',
  booking_period: null,
  attendees: 1,
  budget: 0.00,
})

const submit = () => {
  form.post('/events', {
    onSuccess: () => {
      // Dates are already in YYYY-MM-DD format
      // Numbers are proper numeric types
      console.log('Form data:', form.data())
    }
  })
}
</script>

<template>
  <form @submit.prevent="submit">
    <FormDatePicker
      v-model="form.event_date"
      label="Event Date"
      :error="form.errors.event_date"
      :min="new Date().toISOString().split('T')[0]"
      required
    />
    
    <FormDateRangePicker
      v-model="form.booking_period"
      label="Booking Period"
      :error="form.errors['booking_period.start'] || form.errors['booking_period.end']"
      required
    />
    
    <FormNumberField
      v-model="form.attendees"
      label="Number of Attendees"
      :error="form.errors.attendees"
      :min="1"
      :max="500"
      show-buttons
      button-position="stacked"
      required
    />
    
    <FormNumberField
      v-model="form.budget"
      label="Budget"
      :error="form.errors.budget"
      :min="0"
      :step="0.01"
      placeholder="0.00"
      show-buttons
      button-position="inline"
    />
  </form>
</template>
```

### In Modal Forms

Perfect integration with our modal system:

```vue
<script setup>
import FormModal from '@/components/modals/FormModal.vue'
import { FormDatePicker, FormNumberField } from '@/components/forms'

const form = useForm({
  deadline: '',
  priority: 1,
})

const handleSubmit = (close, modalEmit) => {
  form.post('/tasks', {
    onSuccess: () => {
      form.reset()
      close()
      modalEmit('taskCreated', form.data())
    }
  })
}
</script>

<template>
  <FormModal
    title="Create Task"
    :is-processing="form.processing"
    @submit="handleSubmit"
  >
    <div class="space-y-4">
      <FormDatePicker
        v-model="form.deadline"
        label="Deadline"
        :error="form.errors.deadline"
        :min="new Date().toISOString().split('T')[0]"
        required
      />
      
      <FormNumberField
        v-model="form.priority"
        label="Priority Level"
        :error="form.errors.priority"
        :min="1"
        :max="5"
        show-buttons
        button-position="stacked"
        required
      />
    </div>
  </FormModal>
</template>
```

## Benefits

### 1. **Consistent API**
- All components follow the same prop patterns
- Uniform styling and behavior across forms
- Standard error handling and validation display

### 2. **HTML Compatibility**
- Date components return standard YYYY-MM-DD strings
- Works perfectly with HTML date inputs and Laravel validation
- No complex date object conversion required

### 3. **Validation Integration**
- Built-in error display and styling
- Automatic error state indication
- Works seamlessly with Inertia form validation

### 4. **Accessibility**
- Proper labels and ARIA attributes
- Keyboard navigation support
- Screen reader compatible

### 5. **Developer Experience**
- TypeScript support with proper types
- Intuitive props and events
- Easy to use and extend

### 6. **Internationalization Ready**
- Uses @internationalized/date for proper locale handling
- Respects user's locale settings
- Handles time zones correctly

## Migration from Basic Inputs

### Before (Basic HTML inputs)
```vue
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
```

### After (Enhanced component)
```vue
<FormDatePicker
  id="start_date"
  v-model="form.start_date"
  label="Start Date"
  :error="form.errors.start_date"
  required
/>
```

The enhanced version provides:
- ✅ Better UX with calendar popup
- ✅ Cleaner, more maintainable code
- ✅ Consistent styling and behavior
- ✅ Built-in validation integration
- ✅ Accessibility improvements