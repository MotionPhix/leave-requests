<script setup lang="ts">
import type { NumberFieldRootProps } from 'reka-ui'
import {
  NumberField,
  NumberFieldContent,
  NumberFieldDecrement,
  NumberFieldIncrement,
  NumberFieldInput,
} from '@/components/ui/number-field'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { cn } from '@/lib/utils'

interface Props extends Omit<NumberFieldRootProps, 'modelValue'> {
  label?: string;
  placeholder?: string;
  required?: boolean;
  id?: string;
  error?: string;
  class?: string;
  showButtons?: boolean;
  buttonPosition?: 'inline' | 'stacked';
  formatOptions?: Intl.NumberFormatOptions;
}

const props = withDefaults(defineProps<Props>(), {
  showButtons: true,
  buttonPosition: 'inline',
});

// v-model for number values
const value = defineModel<number | undefined>({ required: true });
</script>

<template>
  <div :class="cn('space-y-2', props.class)">
    <!-- Label -->
    <Label v-if="label" :for="id">
      {{ label }}
      <span v-if="required" class="text-destructive">*</span>
    </Label>
    
    <!-- Number Field with stacked buttons -->
    <NumberField
      v-if="showButtons && buttonPosition === 'stacked'"
      :id="id"
      v-model="value"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      :format-options="formatOptions"
      :class="cn(
        error && '[&_input]:border-destructive [&_input]:focus-visible:ring-destructive'
      )"
    >
      <NumberFieldContent class="flex items-center gap-2">
        <NumberFieldDecrement />
        <NumberFieldInput :placeholder="placeholder" />
        <NumberFieldIncrement />
      </NumberFieldContent>
    </NumberField>
    
    <!-- Number Field with inline buttons -->
    <NumberField
      v-else-if="showButtons && buttonPosition === 'inline'"
      :id="id"
      v-model="value"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      :format-options="formatOptions"
      :class="cn(
        error && '[&_input]:border-destructive [&_input]:focus-visible:ring-destructive'
      )"
    >
      <NumberFieldContent>
        <NumberFieldDecrement />
        <NumberFieldInput :placeholder="placeholder" />
        <NumberFieldIncrement />
      </NumberFieldContent>
    </NumberField>
    
    <!-- Number Field without buttons -->
    <NumberField
      v-else
      :id="id"
      v-model="value"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      :format-options="formatOptions"
      :class="cn(
        error && '[&_input]:border-destructive [&_input]:focus-visible:ring-destructive'
      )"
    >
      <NumberFieldInput :placeholder="placeholder" />
    </NumberField>
    
    <!-- Error Message -->
    <InputError :message="error" />
  </div>
</template>