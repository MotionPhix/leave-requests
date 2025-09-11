<script setup lang="ts">
import { Modal } from '@inertiaui/modal-vue';
import { computed } from 'vue';
import { X } from 'lucide-vue-next';

interface Props {
  title?: string;
  subtitle?: string;
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | '6xl' | '7xl';
  position?: 'top' | 'center' | 'bottom';
  slideover?: boolean;
  closeExplicitly?: boolean;
  showCloseButton?: boolean;
  showHeader?: boolean;
  scrollable?: boolean;
  contentPadding?: string;
}

const props = withDefaults(defineProps<Props>(), {
  maxWidth: '2xl',
  position: 'center',
  slideover: false,
  closeExplicitly: false,
  showCloseButton: false,
  showHeader: true,
  scrollable: true,
  contentPadding: 'p-6',
});

// Compute panel classes based on scrollable and other props
const panelClasses = computed(() => {
  const baseClasses = [
    'bg-white dark:bg-neutral-800',
    'border border-neutral-200 dark:border-neutral-700',
    'rounded-lg',
    'shadow-xl',
    'relative',
  ];

  if (props.scrollable && !props.slideover) {
    baseClasses.push(
      'max-h-[90vh]', // Prevent modal from overshooting viewport
      'overflow-hidden', // Hide outer overflow
      'flex flex-col' // Flex layout for proper scrolling
    );
  }

  return baseClasses.join(' ');
});

// Compute padding classes for modal content
const paddingClasses = computed(() => {
  if (props.scrollable && !props.slideover) {
    return ''; // No padding on container when scrollable
  }
  return props.contentPadding;
});

// Compute content classes for scrollable content
const contentClasses = computed(() => {
  const classes = [];
  
  if (props.scrollable && !props.slideover) {
    classes.push(
      'overflow-y-auto', // Enable vertical scrolling
      'scrollbar-thin', // Use thin scrollbars
      'scrollbar-thumb-neutral-300 dark:scrollbar-thumb-neutral-600', // Scrollbar thumb color
      'scrollbar-track-neutral-100 dark:scrollbar-track-neutral-800', // Scrollbar track color
      'flex-1', // Take available space
      props.contentPadding // Apply padding to scrollable content
    );
  }
  
  return classes.join(' ');
});

// Header classes
const headerClasses = computed(() => {
  const classes = ['border-b border-neutral-200 dark:border-neutral-700'];
  
  if (props.scrollable && !props.slideover) {
    classes.push('px-6 py-4 flex-shrink-0'); // Fixed header that doesn't scroll
  } else {
    classes.push('mb-6'); // Regular spacing for non-scrollable
  }
  
  return classes.join(' ');
});

// Footer slot classes
const footerClasses = computed(() => {
  const classes = ['border-t border-neutral-200 dark:border-neutral-700'];
  
  if (props.scrollable && !props.slideover) {
    classes.push('px-6 py-4 flex-shrink-0 bg-neutral-50 dark:bg-neutral-900/50'); // Fixed footer
  } else {
    classes.push('mt-6'); // Regular spacing
  }
  
  return classes.join(' ');
});
</script>

<template>
  <Modal
    :max-width="maxWidth"
    :position="position"
    :slideover="slideover"
    :close-explicitly="closeExplicitly"
    :close-button="false"
    :panel-classes="panelClasses"
    :padding-classes="paddingClasses"
    v-slot="{ close, emit }"
  >
    <!-- Header -->
    <div v-if="showHeader && (title || subtitle || $slots.header || showCloseButton)" :class="headerClasses">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <!-- Custom header slot or default header -->
          <slot name="header" :close="close" :emit="emit">
            <div v-if="title || subtitle">
              <h2 v-if="title" class="text-xl font-semibold text-neutral-900 dark:text-neutral-100 leading-6">
                {{ title }}
              </h2>
              <p v-if="subtitle" class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                {{ subtitle }}
              </p>
            </div>
          </slot>
        </div>
        
        <!-- Close button -->
        <button
          v-if="showCloseButton"
          type="button"
          class="ml-4 flex-shrink-0 inline-flex items-center justify-center w-8 h-8 text-neutral-400 hover:text-neutral-500 dark:text-neutral-500 dark:hover:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-700 rounded-md transition-colors"
          @click="close"
        >
          <X class="w-5 h-5" />
          <span class="sr-only">Close modal</span>
        </button>
      </div>
    </div>

    <!-- Scrollable Content -->
    <div :class="contentClasses">
      <slot :close="close" :emit="emit" />
    </div>

    <!-- Footer -->
    <div v-if="$slots.footer" :class="footerClasses">
      <slot name="footer" :close="close" :emit="emit" />
    </div>
  </Modal>
</template>