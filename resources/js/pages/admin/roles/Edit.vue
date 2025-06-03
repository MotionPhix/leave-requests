<script setup lang="ts">
import { Modal } from '@inertiaui/modal-vue'
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardFooter, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';
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
  <div>
    <Modal
      ref="roleEditForm"
      v-slot="{ close }"
      padding-classes="p-0"
      panel-classes=""
      :close-explicitly="true">

      <Card>
        <CardHeader>
          <CardTitle>
            Edit {{ role.name }} role
          </CardTitle>

          <CardDescription>
            Edit the details of the role and its permissions.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <form
            @submit.prevent="handleSubmit"
            class="space-y-6">
            <div class="space-y-2">
              <Label>Role Name</Label>
              <Input
                v-model="form.name"
                :error="form.errors.name"
                placeholder="Enter role name"
              />

              <InputError :message="form.errors.name" />
              <InputError :message="form.errors.permissions" />
            </div>

            <div class="space-y-2">
              <Label>Permissions</Label>
              <div class="grid grid-cols-2 gap-4 mt-2">
                <div
                  v-for="permission in permissions"
                  :key="permission.id">
                  <label class="flex items-center space-x-2">
                    <Checkbox
                      variant="secondary"
                      :model-value="form.permissions.includes(permission.id)"
                      @update:model-value="(checked) => checked ? form.permissions.push(permission.id) : form.permissions = form.permissions.filter(id => id !== permission.id)"
                    />

                    <span class="text-sm capitalize">
                      {{ permission.name }}
                    </span>
                  </label>
                </div>
              </div>
            </div>

            <CardFooter class="flex justify-end space-x-2">
              <Button
                type="button"
                variant="outline"
                @click="close">
                Cancel
              </Button>

              <Button type="submit"
                      :disabled="form.processing">
                Add role
              </Button>
            </CardFooter>
          </form>
        </CardContent>
      </Card>

    </Modal>
  </div>
</template>
