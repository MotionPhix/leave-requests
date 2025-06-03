<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue-inertia';
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
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import DatePicker from '@/components/DatePicker.vue';
import { Modal } from '@inertiaui/modal-vue';

const form = useForm('post', route('admin.holidays.store'), {
  name: '',
  date: '',
  type: '',
  description: '',
  is_recurring: false,
});

const transformSubmit = () => {
  form
    .transform(data => ({
      ...data,
      date: data.date ? new Date(data.date).toISOString() : null,
    }))
    .post(route('admin.holidays.store'), {
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
  <div>

    <Head title="Create Holiday" />

    <Modal
      :close-explicitly="true"
      :close-button="false"
      padding-classes="p-0"
      panel-classes=""
      v-slot="{ close }">

      <div class="p-6">
        <Card class="max-w-2xl">
          <CardHeader>
            <h1 class="text-2xl font-bold mb-2">Add a new holiday</h1>

            <p class="text-sm text-muted-foreground mb-4 max-w-md">
              Add a new holiday to the system. This can be a public holiday or a company-specific holiday.
            </p>
          </CardHeader>

          <CardContent>
            <form @submit.prevent="transformSubmit"
                  class="space-y-6">
              <div class="space-y-2">
                <Label>Name</Label>
                <Input v-model="form.name"
                      @change="form.validate('name')"
                      placeholder="Give the holiday a name, e.g. Labour Day" />
                <InputError :message="form.errors.name" />
              </div>

              <section class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <Label>Date</Label>
                  <DatePicker v-model="form.date"
                              @change="form.validate('date')" />
                  <InputError :message="form.errors.date" />
                </div>

                <div class="space-y-2">
                  <Label>Type</Label>
                  <Select v-model="form.type"
                          @update:modelValue="form.validate('type')">
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
                <Textarea v-model="form.description"
                          @change="form.validate('description')"
                          placeholder="Describe the holiday, e.g. Commemoration of the fallen heroes."
                          rows="3" />
                <InputError :message="form.errors.description" />
              </div>

              <Label>
                <span>
                  Recurring Holiday
                </span>

                <Switch v-model="form.is_recurring"
                        @update:modelValue="form.validate('is_recurring')" />
              </Label>

              <div class="flex justify-end gap-4">
                <Button type="button"
                        variant="outline"
                        @click="close">
                  Cancel
                </Button>
                <Button type="submit"
                        :disabled="form.processing">
                  Create Holiday
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>

    </Modal>
  </div>
</template>
