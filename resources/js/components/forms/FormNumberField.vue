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
import { Minus, Plus } from 'lucide-vue-next'

interface Props extends Omit<NumberFieldRootProps, 'modelValue'> {
  label?: string;
  placeholder?: string;
  required?: boolean;
  id?: string;
  error?: string;
  class?: string;
  showButtons?: boolean;
  buttonPosition?: 'inline' | 'stacked';
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
    
    <!-- Number Field -->
    <NumberField
      :id="id"
      v-model="value"
      :min="min"
      :max="max"
      :step="step"
      :disabled="disabled"
      :class="cn(
        error && '[&_input]:border-destructive [&_input]:focus-visible:ring-destructive'
      )"
    >
      <NumberFieldContent
        v-if="showButtons && buttonPosition === 'inline'"
        class="relative"
      >
        <NumberFieldDecrement
          class="absolute left-2 top-1/2 -translate-y-1/2 h-4 w-4 rounded-sm hover:bg-accent"
        >
          <Minus class="h-3 w-3" />
        </NumberFieldDecrement>
        
        <NumberFieldInput
          :placeholder="placeholder"
          class="pl-8 pr-8 text-center"
        />
        
        <NumberFieldIncrement
          class="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 rounded-sm hover:bg-accent"
        >
          <Plus class="h-3 w-3" />
        </NumberFieldIncrement>
      </NumberFieldContent>
      
      <div
        v-else-if="showButtons && buttonPosition === 'stacked'"
        class="flex items-center gap-2"
      >
        <NumberFieldDecrement
          class="flex-shrink-0 w-9 h-9 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
        >
          <Minus class="h-4 w-4" />
        </NumberFieldDecrement>
        
        <NumberFieldInput
          :placeholder="placeholder"
          class="flex-1 text-center"
        />
        
        <NumberFieldIncrement
          class="flex-shrink-0 w-9 h-9 rounded-md border border-input bg-background hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
        >
          <Plus class="h-4 w-4" />
        </NumberFieldIncrement>
      </div>
      
      <!-- No buttons, just input -->
      <NumberFieldInput
        v-else
        :placeholder="placeholder"
      />
    </NumberField>
    
    <!-- Error Message -->
    <InputError :message="error" />
  </div>
</template>