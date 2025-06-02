<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
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

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Leave Types',
    href: route('admin.leave-types.index')
  },
  {
    title: 'Create Leave Type',
    href: route('admin.leave-types.create')
  }
];

const form = useForm('post', route('admin.leave-types.store'), {
  name: '',
  description: '',
  max_days_per_year: 0,
  requires_documentation: false,
  gender_specific: false,
  gender: 'any',
  frequency_years: 1,
  pay_percentage: 100,
  minimum_notice_days: 0,
});

const genderSpecificToggled = (value: boolean) => {
  form.gender_specific = value;
  if (!value) {
    form.gender = 'any';
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head title="Create Leave Type" />

    <div class="max-w-2xl p-6">
      <Card>
        <CardHeader>
          <CardTitle>Add New Leave Type</CardTitle>
        </CardHeader>

        <CardContent>
          <form @submit.prevent="form.submit()"
                class="space-y-6">
            <div class="space-y-2">
              <Label>Name</Label>
              <Input v-model="form.name"
                     @change="form.validate('name')" />
              <InputError :message="form.errors.name" />
            </div>

            <div class="space-y-2">
              <Label>Description</Label>
              <Textarea v-model="form.description"
                        @change="form.validate('description')"
                        rows="3" />
              <InputError :message="form.errors.description" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label>Maximum Days Per Year</Label>
                <Input type="number"
                       v-model="form.max_days_per_year"
                       @change="form.validate('max_days_per_year')" />
                <InputError :message="form.errors.max_days_per_year" />
              </div>

              <div class="space-y-2">
                <Label>Pay Percentage</Label>
                <Input type="number"
                       v-model="form.pay_percentage"
                       @change="form.validate('pay_percentage')" />
                <InputError :message="form.errors.pay_percentage" />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-2">
                <Label>Frequency (Years)</Label>
                <Input type="number"
                       v-model="form.frequency_years"
                       @change="form.validate('frequency_years')" />
                <InputError :message="form.errors.frequency_years" />
              </div>

              <div class="space-y-2">
                <Label>Minimum Notice Days</Label>
                <Input type="number"
                       v-model="form.minimum_notice_days"
                       @change="form.validate('minimum_notice_days')" />
                <InputError :message="form.errors.minimum_notice_days" />
              </div>
            </div>

            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <Label>Requires Documentation</Label>
                <Switch v-model="form.requires_documentation"
                        @update:modelValue="form.validate('requires_documentation')" />
              </div>

              <div class="flex items-center justify-between">
                <Label>Gender Specific</Label>
                <Switch v-model="form.gender_specific"
                        @update:modelValue="genderSpecificToggled" />
              </div>

              <div v-if="form.gender_specific"
                   class="space-y-2">
                <Label>Gender</Label>
                <Select v-model="form.gender"
                        @update:modelValue="form.validate('gender')">
                  <SelectTrigger>
                    <SelectValue placeholder="Select gender" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="male">Male</SelectItem>
                    <SelectItem value="female">Female</SelectItem>
                  </SelectContent>
                </Select>
                <InputError :message="form.errors.gender" />
              </div>
            </div>

            <div class="flex justify-end gap-4">
              <Button type="button"
                      variant="outline"
                      @click="router.visit(route('admin.leave-types.index'))">
                Cancel
              </Button>
              <Button type="submit"
                      :disabled="form.processing">
                Create Leave Type
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
