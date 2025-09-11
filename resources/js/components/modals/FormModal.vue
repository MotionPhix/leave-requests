<script setup lang="ts">
import BaseModal from './BaseModal.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Loader2 } from 'lucide-vue-next';

interface Props {
  title?: string;
  subtitle?: string;
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | '6xl' | '7xl';
  position?: 'top' | 'center' | 'bottom';
  slideover?: boolean;
  closeExplicitly?: boolean;
  showCloseButton?: boolean;
  scrollable?: boolean;
  
  // Form specific props
  submitText?: string;
  submitLoadingText?: string;
  cancelText?: string;
  submitVariant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  submitIcon?: any;
  isProcessing?: boolean;
  showSeparator?: boolean;
  formActionsAlign?: 'left' | 'center' | 'right' | 'between';
}

const props = withDefaults(defineProps<Props>(), {
  maxWidth: '2xl',
  position: 'center',
  slideover: false,
  closeExplicitly: true,
  showCloseButton: true,
  scrollable: true,
  submitText: 'Save',
  submitLoadingText: 'Saving...',
  cancelText: 'Cancel',
  submitVariant: 'default',
  isProcessing: false,
  showSeparator: true,
  formActionsAlign: 'right',
});

const emit = defineEmits<{
  submit: [close: () => void, modalEmit: (event: string, ...args: any[]) => void];
  cancel: [close: () => void, modalEmit: (event: string, ...args: any[]) => void];
}>();

const handleCancel = (close: () => void, modalEmit: (event: string, ...args: any[]) => void) => {
  emit('cancel', close, modalEmit);
};

const handleSubmit = (close: () => void, modalEmit: (event: string, ...args: any[]) => void) => {
  emit('submit', close, modalEmit);
};

const actionsClasses = {
  left: 'justify-start',
  center: 'justify-center',
  right: 'justify-end',
  between: 'justify-between',
};
</script>

<template>
  <BaseModal
    :title="title"
    :subtitle="subtitle"
    :max-width="maxWidth"
    :position="position"
    :slideover="slideover"
    :close-explicitly="closeExplicitly"
    :show-close-button="showCloseButton"
    :scrollable="scrollable"
  >
    <template #default="{ close, emit: modalEmit }">
      <!-- Form Content -->
      <div class="space-y-6">
        <!-- Optional separator after header -->
        <Separator v-if="showSeparator && !slideover" />
        
        <!-- Form fields slot -->
        <slot :close="close" :emit="modalEmit" />
      </div>
    </template>

    <!-- Footer with form actions -->
    <template #footer="{ close, emit: modalEmit }">
      <div :class="['flex items-center gap-3', actionsClasses[formActionsAlign]]">
        <!-- Custom footer actions slot -->
        <slot name="actions" :close="close" :emit="modalEmit" :handle-cancel="() => handleCancel(close, modalEmit)" :handle-submit="() => handleSubmit(close, modalEmit)">
          <Button 
            type="button" 
            variant="outline"
            :disabled="isProcessing"
            @click="handleCancel(close, modalEmit)"
          >
            {{ cancelText }}
          </Button>
          <Button 
            type="submit" 
            :variant="submitVariant"
            :disabled="isProcessing"
            class="inline-flex items-center gap-2"
            @click="handleSubmit(close, modalEmit)"
          >
            <Loader2 v-if="isProcessing" class="h-4 w-4 animate-spin" />
            <component v-else-if="submitIcon" :is="submitIcon" class="h-4 w-4" />
            {{ isProcessing ? submitLoadingText : submitText }}
          </Button>
        </slot>
      </div>
    </template>
  </BaseModal>
</template>