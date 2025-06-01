<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
  user: {
    id: number;
    name: string;
    email: string;
    gender: string;
    role_id: number;
  };
  roles: Array<{
    id: number;
    name: string;
  }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Employees',
    href: '/admin/employees'
  },
  {
    title: 'Edit Employee',
    href: route('admin.employees.edit', props.user.id)
  }
];

const form = useForm(
  'put', route('admin.employees.update', props.user), {
  name: props.user.name,
  email: props.user.email,
  gender: props.user.gender,
  password: '',
  role_id: props.user.role_id,
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Edit Employee" />

    <div class="p-6">
      <Card class="max-w-2xl mx-auto">
        <CardHeader>
          <CardTitle>Edit Employee</CardTitle>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="form.submit()" class="space-y-6">
            <div class="space-y-2">
              <Label>Name</Label>
              <Input
                v-model="form.name"
                @change="form.validate('name')"
              />
              <InputError :message="form.errors.name" />
            </div>

            <div class="space-y-2">
              <Label>Email</Label>
              <Input
                type="email"
                v-model="form.email"
                @change="form.validate('email')"
              />
              <InputError :message="form.errors.email" />
            </div>

            <div class="space-y-2">
              <Label>Password</Label>
              <Input
                type="password"
                v-model="form.password"
                @change="form.validate('password')"
                placeholder="Leave blank to keep current password"
              />
              <InputError :message="form.errors.password" />
            </div>

            <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label>Gender</Label>
                <Select
                  v-model="form.gender"
                  @update:modelValue="form.validate('gender')"
                >
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select gender" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="male">Male</SelectItem>
                    <SelectItem value="female">Female</SelectItem>
                  </SelectContent>
                </Select>
                <InputError :message="form.errors.gender" />
              </div>

              <div class="space-y-2">
                <Label>Role</Label>
                <Select
                  v-model="form.role_id"
                  @update:modelValue="form.validate('role_id')"
                >
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select role" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="role in roles"
                      :key="role.id"
                      :value="role.id"
                    >
                      {{ role.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <InputError :message="form.errors.role_id" />
              </div>
            </section>

            <div class="flex justify-end gap-4">
              <Button
                type="button"
                variant="outline"
                @click="router.visit(route('admin.employees.index'))">
                Cancel
              </Button>

              <Button
                type="submit"
                :disabled="form.processing">
                Update Employee
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
