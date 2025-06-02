<script setup lang="ts">
import { Modal } from '@inertiaui/modal-vue'
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
  role: {
    id: number;
    name: string;
    permissions: { id: number; name: string }[];
  };
  permissions: { id: number; name: string }[];
}>();

const roleEditForm = ref();

const form = useForm({
  name: props.role?.name,
  permissions: props.role?.permissions.map(p => p.id) || [],
});

function handleSubmit() {

  form.put(route('admin.roles.update', props.role.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      roleEditForm.value.close();
    },
  });

}
</script>

<template>

  <Modal
    ref="roleEditForm"
    v-slot="{ close }"
    padding-classes="p-0"
    :close-explicitly="true">



  </Modal>

</template>
