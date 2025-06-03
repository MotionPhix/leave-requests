<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import dayjs from 'dayjs';
import { computed } from 'vue';
import OptionsPicker from '@/components/OptionsPicker.vue';

interface Props {
  settings: {
    prefix: string;
    separator: string;
    number_length: number;
    suffix: string | null;
    include_year: boolean;
    year_format: 'y' | 'Y';
  }
}

const props = defineProps<Props>();

const twoDigit = dayjs(new Date()).format('YY').toString();
const fourDigit = dayjs(new Date()).format('YYYY').toString();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Settings',
    href: route('admin.profile.edit')
  },
  {
    title: 'Employee ID Format',
    href: route('admin.employee-id.edit')
  }
];

const form = useForm({
  prefix: props.settings.prefix,
  separator: props.settings.separator,
  number_length: props.settings.number_length,
  suffix: props.settings.suffix,
  include_year: props.settings.include_year,
  year_format: props.settings.year_format,
});

const preview = computed(() => {
  const parts = [];
  if (form.prefix) parts.push(form.prefix);
  if (form.include_year) parts.push(form.year_format === 'Y' ? fourDigit : twoDigit);
  parts.push(form.number_length ? '0'.repeat(form.number_length) : '0000');
  if (form.suffix) parts.push(form.suffix);
  return parts.join(form.separator);
});

const yearOptions = [
  {
    value: 'y',
    title: `2 digits (${twoDigit})`,
    description: 'Short year format'
  },

  {
    value: 'Y',
    title: `4 digits (${fourDigit})`,
    description: 'Full year format'
  }
] as const

const submit = () => {
  form.patch(route('admin.settings.employee-id.update'), {
    preserveScroll: true
  });
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Employee ID Settings" />

    <SettingsLayout>
      <div class="flex flex-col space-y-6">
        <HeadingSmall
          title="Employee ID Format"
          description="Configure how employee IDs are automatically generated"
        />

        <form @submit.prevent="submit" class="space-y-6">
          <Card>
            <CardContent>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                <div class="grid gap-2">
                  <Label>Prefix</Label>
                  <Input
                    v-model="form.prefix"
                    placeholder="e.g., EMP" />
                  <InputError :message="form.errors.prefix" />
                  <p class="text-sm text-muted-foreground">Added at the start of each ID</p>
                </div>

                <div class="grid gap-2">
                  <Label>Separator</Label>
                  <Input
                    v-model="form.separator"
                    placeholder="-" />
                  <InputError :message="form.errors.separator" />
                  <p class="text-sm text-muted-foreground">Character used between parts</p>
                </div>

                <div class="grid gap-2 align-top">
                  <Label>Number Length</Label>
                  <Input
                    type="number"
                    v-model="form.number_length"
                    min="1"
                    max="10" />
                  <InputError :message="form.errors.number_length" />
                  <p class="text-sm text-muted-foreground">How many digits in sequence</p>
                </div>

                <div class="grid gap-2">
                  <Label>Suffix (Optional)</Label>
                  <Input
                    v-model="form.suffix"
                    placeholder="e.g., HQ" />
                  <InputError :message="form.errors.suffix" />
                  <p class="text-sm text-muted-foreground">Added at the end of each ID</p>
                </div>

                <div class="grid gap-2">
                  <Label class="flex items-center justify-between">
                    Include Year
                    <Switch v-model="form.include_year" />
                  </Label>

                  <InputError :message="form.errors.include_year" />

                  <p class="text-sm text-muted-foreground">
                    Add current year to ID
                  </p>

                  <div class="grid mt-4">
                    <OptionsPicker
                      v-if="form.include_year"
                      v-model="form.year_format"
                      :options="yearOptions"
                      title="Year Format"
                      :inline="true"
                    />
                  </div>
                </div>
              </div>

              <div class="mt-6 p-4 bg-muted rounded-lg">
                <p class="text-sm font-medium text-muted-foreground mb-1">Preview</p>
                <p class="font-mono text-lg">{{ preview }}</p>
              </div>

              <div class="flex items-center gap-4 mt-6">
                <Button type="submit" :disabled="form.processing">Save Changes</Button>

                <Transition
                  enter-active-class="transition ease-in-out"
                  enter-from-class="opacity-0"
                  leave-active-class="transition ease-in-out"
                  leave-to-class="opacity-0"
                >
                  <p v-show="form.recentlySuccessful" class="text-sm text-muted-foreground">
                    Saved.
                  </p>
                </Transition>
              </div>
            </CardContent>
          </Card>
        </form>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
