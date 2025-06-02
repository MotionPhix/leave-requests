<script setup lang="ts">
import { Modal } from '@inertiaui/modal-vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardFooter, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';

defineProps<{
  permissions: { id: number; name: string }[];
}>()

const roleCreateForm = ref()

const form = useForm({
  name: '',
  permissions: [] as number[]
});

function handleSubmit() {
  form.post(route('admin.roles.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      roleCreateForm.value.close()
    },
    onError: (err) => {
      console.log(err);
    }
  });
}
</script>

<template>
  <div>
    <Modal
      ref="roleCreateForm"
      v-slot="{ close }"
      padding-classes="p-0"
      :close-explicitly="true"
      panel-classes="">
      <Card>
        <CardHeader>
          <CardTitle>
            Create New Role
          </CardTitle>

          <CardDescription>
            Deploy your new project in one-click.
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
