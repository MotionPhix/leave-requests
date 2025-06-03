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
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import DatePicker from '@/components/DatePicker.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
  user: {
    id: number;
    name: string;
    email: string;
    gender: string;
    role_id: number;
    position: string;
    department: number;
    join_date: string;
    reporting_to: number;
    work_phone: string;
    office_location: string;
    employment_status: string;
    employment_type: string;
  };
  roles: Array<{ id: number; name: string }>;
  departments: Array<{ id: number; name: string }>;
  managers: Array<{ id: number; name: string }>;
  employmentTypes: Array<{ id: string; name: string }>;
  employmentStatuses: Array<{ id: string; name: string }>;
  genders: Array<{ id: string; name: string }>;
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
  'put',
  route('admin.employees.update', props.user.id),
  {
    name: props.user.name,
    email: props.user.email,
    gender: props.user.gender,
    password: '',
    role_id: props.user.role_id,
    position: props.user.position,
    department: props.user.department,
    join_date: props.user.join_date,
    reporting_to: props.user.reporting_to,
    work_phone: props.user.work_phone,
    office_location: props.user.office_location,
    employment_status: props.user.employment_status,
    employment_type: props.user.employment_type,
  }
);
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head title="Edit Employee" />

    <div class="p-6 max-w-4xl">
      <Card>
        <CardHeader>
          <CardTitle>Edit Employee</CardTitle>
          <CardDescription>
            Update employee information below.
          </CardDescription>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="form.submit()"
                class="space-y-8">
            <!-- Personal Information -->
            <div class="space-y-6">
              <h3 class="text-lg font-medium">Personal Information</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2 col-span-2">
                  <Label>Full Name</Label>
                  <Input v-model="form.name"
                         @change="form.validate('name')" />
                  <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                  <Label>Email Address</Label>
                  <Input type="email"
                         v-model="form.email"
                         @change="form.validate('email')" />
                  <InputError :message="form.errors.email" />
                </div>

                <div class="space-y-2">
                  <Label>Gender</Label>
                  <Select
                    v-model="form.gender"
                    @update:modelValue="form.validate('gender')">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select gender" />
                    </SelectTrigger>

                    <SelectContent>
                      <SelectItem
                        :value="gender.id"
                        v-for="gender in genders"
                        :key="gender.id">
                        {{ gender.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.gender" />
                </div>
              </div>
            </div>

            <Separator />

            <!-- Employment Details -->
            <div class="space-y-6">
              <h3 class="text-lg font-medium">Employment Details</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <Label>Position</Label>
                  <Input v-model="form.position"
                         @change="form.validate('position')" />
                  <InputError :message="form.errors.position" />
                </div>

                <div class="space-y-2">
                  <Label>Department</Label>
                  <Select
                    v-model="form.department"
                    @update:modelValue="form.validate('department')">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select department" />
                    </SelectTrigger>

                    <SelectContent>
                      <SelectItem
                        v-for="dept in departments"
                        :key="dept.id"
                        :value="dept.id">
                        {{ dept.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.department" />
                </div>

                <div class="space-y-2">
                  <Label>Join Date</Label>
                  <DatePicker
                    v-model="form.join_date"
                    @update:modelValue="form.validate('join_date')"
                  />

                  <InputError :message="form.errors.join_date" />
                </div>

                <div class="space-y-2">
                  <Label>Reports To</Label>
                  <Select
                    v-model="form.reporting_to"
                    @update:modelValue="form.validate('reporting_to')">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select manager" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="manager in managers"
                        :key="manager.id"
                        :value="manager.id">
                        {{ manager.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <InputError :message="form.errors.reporting_to" />
                </div>

                <div class="space-y-2">
                  <Label>Employment Type</Label>
                  <Select
                    v-model="form.employment_type"
                    @update:modelValue="form.validate('employment_type')">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select type" />
                    </SelectTrigger>

                    <SelectContent>
                      <SelectItem
                        v-for="empType in employmentTypes"
                        :value="empType.id"
                        :key="empType.id">
                        {{ empType.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <InputError :message="form.errors.employment_type" />
                </div>

                <div class="space-y-2">
                  <Label>Employment Status</Label>
                  <Select v-model="form.employment_status"
                          @update:modelValue="form.validate('employment_status')">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>

                    <SelectContent>
                      <SelectItem
                        v-for="status in employmentStatuses"
                        :key="status.id"
                        :value="status.id">
                        {{ status.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <InputError :message="form.errors.employment_status" />
                </div>
              </div>
            </div>

            <Separator />

            <!-- Contact Information -->
            <div class="space-y-6">
              <h3 class="text-lg font-medium">Contact & Location</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <Label>Work Phone</Label>
                  <Input
                    v-model="form.work_phone"
                    @change="form.validate('work_phone')"
                  />

                  <InputError :message="form.errors.work_phone" />
                </div>

                <div class="space-y-2">
                  <Label>Office Location</Label>
                  <Input
                    v-model="form.office_location"
                    @change="form.validate('office_location')"
                  />

                  <InputError :message="form.errors.office_location" />
                </div>
              </div>
            </div>

            <Separator />

            <!-- Account Settings -->
            <div class="space-y-6">
              <h3 class="text-lg font-medium">Account Settings</h3>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

                <div class="space-y-2">
                  <Label>New Password (Optional)</Label>
                  <Input
                    type="password"
                    v-model="form.password"
                    @change="form.validate('password')"
                    placeholder="Leave blank to keep current password"
                  />

                  <InputError :message="form.errors.password" />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-4">
              <Button
                type="button"
                variant="outline"
                :disabled="form.processing"
                @click="router.visit(route('admin.employees.index'))">
                Cancel
              </Button>

              <Button
                type="submit"
                :disabled="form.processing">
                Save Changes
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
