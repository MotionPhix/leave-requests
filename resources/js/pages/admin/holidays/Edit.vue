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
import { Card, CardContent } from '@/components/ui/card';
import DatePicker from '@/components/DatePicker.vue';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
  holiday: {
    uuid: string;
    name: string;
    date: string;
    type: string;
    description: string;
    is_recurring: boolean;
  };
}>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Holidays',
    href: route('admin.holidays.index')
  },
  {
    title: 'Edit Holiday',
    href: route('admin.holidays.edit', props.holiday.uuid)
  }
];

const form = useForm('put', route('admin.holidays.update', props.holiday.uuid), {
  name: props.holiday.name,
  date: props.holiday.date,
  type: props.holiday.type,
  description: props.holiday.description,
  is_recurring: props.holiday.is_recurring,
});

const transformSubmit = () => {
  form
    .transform(data => ({
      ...data,
      date: data.date ? new Date(data.date).toISOString() : null,
    }))
    .put(route('admin.holidays.update', props.holiday.uuid), {
      onSuccess: () => {
        router.visit(route('admin.holidays.index'));
      },
      onError: (err) => {
        console.log(err);
      }
    });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Edit Holiday" />

    <div class="p-6">
      <div class="mb-10">
        <h1 class="text-2xl font-bold mb-2">Edit holiday</h1>
        <p class="text-sm text-muted-foreground mb-4 max-w-md">
          Update the holiday details. Changes will be reflected in the leave calculations.
        </p>
      </div>

      <Card class="max-w-2xl">
        <CardContent>
          <form @submit.prevent="transformSubmit" class="space-y-6">
            <div class="space-y-2">
              <Label>Name</Label>
              <Input
                v-model="form.name"
                @change="form.validate('name')"
                placeholder="Give the holiday a name, e.g. Labour Day"
              />
              <InputError :message="form.errors.name" />
            </div>

            <section class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div class="space-y-2">
                <Label>Date</Label>
                <DatePicker
                  v-model="form.date"
                  @change="form.validate('date')"
                />
                <InputError :message="form.errors.date" />
              </div>

              <div class="space-y-2">
                <Label>Type</Label>
                <Select
                  v-model="form.type"
                  @update:modelValue="form.validate('type')"
                >
                  <SelectTrigger class="w-full">
                    <SelectValue placeholder="Select holiday type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Public Holiday">Public Holiday</SelectItem>
                    <SelectItem value="Company Holiday">Company Holiday</SelectItem>
                  </SelectContent>
                </Select>
                <InputError :message="form.errors.type" />
              </div>
            </section>

            <div class="space-y-2">
              <Label>Description</Label>
              <Textarea
                v-model="form.description"
                @change="form.validate('description')"
                placeholder="Describe the holiday, e.g. Commemoration of the fallen heroes."
                rows="3"
              />
              <InputError :message="form.errors.description" />
            </div>

            <Label>
              <span>Recurring Holiday</span>
              <Switch
                v-model="form.is_recurring"
                @update:modelValue="form.validate('is_recurring')"
              />
            </Label>

            <div class="flex justify-end gap-4">
              <Button
                type="button"
                variant="outline"
                @click="router.visit(route('admin.holidays.index'))"
              >
                Cancel
              </Button>
              <Button
                type="submit"
                :disabled="form.processing"
              >
                Update Holiday
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
