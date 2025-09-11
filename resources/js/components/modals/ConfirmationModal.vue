<script setup lang="ts">
import BaseModal from './BaseModal.vue';
import { Button } from '@/components/ui/button';
import { AlertTriangle, Trash2, Info, CheckCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
  title?: string;
  message?: string;
  confirmText?: string;
  cancelText?: string;
  type?: 'danger' | 'warning' | 'info' | 'success';
  isProcessing?: boolean;
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | '6xl' | '7xl';
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Confirm Action',
  message: 'Are you sure you want to perform this action?',
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  type: 'danger',
  isProcessing: false,
  maxWidth: 'md',
});

const emit = defineEmits<{
  confirm: [close: () => void, modalEmit: (event: string, ...args: any[]) => void];
  cancel: [close: () => void, modalEmit: (event: string, ...args: any[]) => void];
}>();

const typeConfig = computed(() => {
  const configs = {
    danger: {
      icon: Trash2,
      iconClasses: 'text-red-600 dark:text-red-400',
      iconBg: 'bg-red-100 dark:bg-red-900/30',
      confirmVariant: 'destructive' as const,
      confirmText: props.confirmText || 'Delete',
    },
    warning: {
      icon: AlertTriangle,
      iconClasses: 'text-amber-600 dark:text-amber-400',
      iconBg: 'bg-amber-100 dark:bg-amber-900/30',
      confirmVariant: 'default' as const,
      confirmText: props.confirmText || 'Continue',
    },
    info: {
      icon: Info,
      iconClasses: 'text-blue-600 dark:text-blue-400',
      iconBg: 'bg-blue-100 dark:bg-blue-900/30',
      confirmVariant: 'default' as const,
      confirmText: props.confirmText || 'Confirm',
    },
    success: {
      icon: CheckCircle,
      iconClasses: 'text-green-600 dark:text-green-400',
      iconBg: 'bg-green-100 dark:bg-green-900/30',
      confirmVariant: 'default' as const,
      confirmText: props.confirmText || 'Continue',
    },
  };
  
  return configs[props.type];
});

const handleConfirm = (close: () => void, modalEmit: (event: string, ...args: any[]) => void) => {
  emit('confirm', close, modalEmit);
};

const handleCancel = (close: () => void, modalEmit: (event: string, ...args: any[]) => void) => {
  emit('cancel', close, modalEmit);
};
</script>

<template>
  <BaseModal
    :max-width="maxWidth"
    position="center"
    :close-explicitly="true"
    :show-header="false"
    :scrollable="false"
    content-padding="p-6"
  >
    <div class="flex items-start space-x-4">
      <!-- Icon -->
      <div :class="['flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center', typeConfig.iconBg]">
        <component :is="typeConfig.icon" :class="['w-6 h-6', typeConfig.iconClasses]" />
      </div>
      
      <!-- Content -->
      <div class="flex-1 min-w-0">
        <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-100 mb-2">
          {{ title }}
        </h3>
        <p class="text-sm text-neutral-600 dark:text-neutral-400 leading-relaxed">
          {{ message }}
        </p>
        
        <!-- Custom content slot -->
        <div v-if="$slots.default" class="mt-4">
          <slot />
        </div>
      </div>
    </div>

    <!-- Actions -->
    <template #footer="{ close, emit: modalEmit }">
      <div class="flex items-center justify-end gap-3">
        <Button 
          type="button" 
          variant="outline"
          :disabled="isProcessing"
          @click="handleCancel(close, modalEmit)"
        >
          {{ cancelText }}
        </Button>
        <Button 
          type="button" 
          :variant="typeConfig.confirmVariant"
          :disabled="isProcessing"
          @click="handleConfirm(close, modalEmit)"
        >
          {{ typeConfig.confirmText }}
        </Button>
      </div>
    </template>
  </BaseModal>
</template>