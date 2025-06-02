<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
  SelectValue
} from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Employees',
    href: '/admin/employees'
  },
  {
    title: 'Add New Employee',
    href: '/admin/employees/create'
  }
];

defineProps<{
  roles: Array<{
    id: number;
    name: string;
  }>;
}>();

const form = useForm('post', '/admin/employees', {
  name: '',
  email: '',
  gender: '',
  password: '',
  role_id: ''
});
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head title="Add New Employee" />

    <div class="p-6">
      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle>New Employee</CardTitle>
          <CardDescription>
            Fill in the details below to create a new employee.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <form
            @submit.prevent="form.submit()"
            class="space-y-6">
            <div class="space-y-2">
              <Label>Name</Label>
              <Input
                v-model="form.name"
                @change="form.validate('name')" />

              <InputError :message="form.errors.name" />
            </div>

            <div class="space-y-2 mt-4">
              <Label>Email</Label>
              <Input
                type="email"
                v-model="form.email"
                @change="form.validate('email')"
              />

              <InputError :message="form.errors.email" />
            </div>

            <div class="space-y-2 mt-4">
              <Label>Password</Label>
              <Input
                type="password"
                v-model="form.password"
                @change="form.validate('password')"
              />

              <InputError :message="form.errors.password" />
            </div>

            <section class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">

              <div class="space-y-2">
                <Label>Gender</Label>
                <Select
                  v-model="form.gender"
                  @update:modelValue="form.validate('gender')">
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
                  @update:modelValue="form.validate('role_id')">
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select role" />
                  </SelectTrigger>

                  <SelectContent>
                    <SelectItem
                      v-for="role in roles"
                      :key="role.id"
                      :value="role.id">
                      {{ role.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>

                <InputError :message="form.errors.role_id" />
              </div>
            </section>

            <div class="mt-6 flex justify-end">
              <Button
                type="submit"
                :disabled="form.processing">
                Create User
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
