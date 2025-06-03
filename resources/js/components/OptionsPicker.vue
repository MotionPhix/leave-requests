<script setup lang="ts">
import { computed } from 'vue'
import { v4 as uuidv4 } from 'uuid';
import InputError from '@/components/InputError.vue'
import { Label } from '@/components/ui/label'

const _uid = uuidv4();

interface RadioOption {
  value: string | number
  title: string
  description: string
}

const props = defineProps({
  modelValue: {
    type: [String, Number],
    required: true
  },
  options: {
    type: Array as () => RadioOption[],
    required: true,
    validator: (options: RadioOption[]) =>
      options.every(opt => 'value' in opt && 'title' in opt && 'description' in opt)
  },
  title: {
    type: String,
    default: ''
  },
  inline: {
    type: Boolean,
    default: false
  },
  name: String,
  required: Boolean,
  error: String
})

const emit = defineEmits(['update:modelValue'])

const selectedValue = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})
</script>

<template>
  <div>
    <Label>
      {{ title }}
    </Label>

    <ul
      class="grid w-full gap-4"
      :class="{ 'grid-cols-1': inline, 'grid-cols-2': !inline, 'mt-2': title }">
      <li v-for="option in options" :key="option.value">
        <input
          type="radio"
          :id="`${_uid}-${option.value}`"
          :value="option.value"
          v-model="selectedValue"
          :name="name || _uid"
          class="hidden peer"
          :required="required"
        />
        <label
          :for="`${_uid}-${option.value}`"
          class="inline-flex items-center justify-between w-full p-5 text-gray-500 bg-white border border-gray-200 rounded-lg cursor-pointer dark:hover:text-gray-300 dark:border-gray-700 dark:peer-checked:text-blue-500 peer-checked:border-blue-600 dark:peer-checked:border-blue-600 peer-checked:text-blue-600 hover:text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-800 dark:hover:bg-gray-700"
        >
          <div class="block">
            <div class="w-full text-lg font-semibold">{{ option.title }}</div>
            <div class="w-full">{{ option.description }}</div>
          </div>

          <svg
            class="w-5 h-5 ms-3 rtl:rotate-180"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 14 10">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M1 5h12m0 0L9 1m4 4L9 9"
            />
          </svg>
        </label>
      </li>
    </ul>

    <InputError v-if="error" :message="error" />
  </div>
</template>
