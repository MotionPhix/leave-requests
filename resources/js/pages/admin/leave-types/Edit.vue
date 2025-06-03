<script setup lang="ts">
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
import { Separator } from '@/components/ui/separator';

const props = defineProps<{
  leaveType: {
    id: number;
    name: string;
    description: string;
    max_days_per_year: number;
    requires_documentation: boolean;
    gender_specific: boolean;
    gender: string;
    frequency_years: number;
    pay_percentage: number;
    minimum_notice_days: number;
  };
  genders: Array<{
    id: string;
    name: string;
  }>;
}>();

console.log(props);

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Leave Types',
    href: route('admin.leave-types.index')
  },
  {
    title: 'Edit Leave Type',
    href: route('admin.leave-types.edit', props.leaveType.id)
  }
];

const form = useForm('put', route('admin.leave-types.update', props.leaveType.id), {
  name: props.leaveType.name,
  description: props.leaveType.description,
  max_days_per_year: props.leaveType.max_days_per_year,
  requires_documentation: props.leaveType.requires_documentation,
  gender_specific: props.leaveType.gender_specific,
  gender: props.leaveType.gender,
  frequency_years: props.leaveType.frequency_years,
  pay_percentage: props.leaveType.pay_percentage,
  minimum_notice_days: props.leaveType.minimum_notice_days,
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

    <Head :title="`Edit ${leaveType.name}`" />

    <div class="p-6 max-w-2xl">
      <Card>
        <CardHeader>
          <CardTitle>Edit Leave Type</CardTitle>
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

            <section class="grid grid-cols-1 sm:grid-cols-2 gap-4">

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

                  <!-- <Label>Gender</Label> -->
                  <Select
                    v-model="form.gender"
                    @update:modelValue="form.validate('gender')">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select gender" />
                    </SelectTrigger>

                    <SelectContent>
                      <SelectItem
                        v-for="gender in genders"
                        :key="gender.id"
                        :value="gender.id">
                        {{ gender.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>

                  <InputError :message="form.errors.gender" />
                </div>
              </div>

            </section>

            <Separator />

            <div class="flex justify-end gap-4">
              <Button
                type="button"
                variant="outline"
                @click="router.visit(route('admin.leave-types.index'))">
                Cancel
              </Button>

              <Button
                type="submit"
                :disabled="form.processing">
                Update Leave Type
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
