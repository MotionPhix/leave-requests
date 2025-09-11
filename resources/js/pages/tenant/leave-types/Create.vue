<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowLeft, PlusCircle, FileText, Users, Calendar, Shield } from 'lucide-vue-next';
import { FormNumberField } from '@/components/forms';

type Workspace = {
  uuid: string;
  slug: string;
  name: string;
}

const props = defineProps<{
  workspace: Workspace;
}>();

const page = usePage();
const workspace = page.props.workspace as Workspace;

const form = useForm({
  name: '',
  description: '',
  max_days_per_year: 20,
  requires_documentation: false,
  gender_specific: false,
  gender: 'any',
  frequency_years: 1,
  pay_percentage: 100.00,
  minimum_notice_days: 0,
  allow_negative_balance: false,
});

const submit = () => {
  form.post(route('tenant.management.leave-types.store', {
    tenant_slug: workspace.slug,
    tenant_uuid: workspace.uuid,
  }));
};

const genderOptions = [
  { value: 'any', label: 'Any Gender' },
  { value: 'male', label: 'Male Only' },
  { value: 'female', label: 'Female Only' },
];
</script>

<template>
  <TenantLayout>
    <div class="space-y-6 max-w-2xl">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link
            variant="ghost"
            size="sm"
            :href="route('tenant.management.leave-types.index', {
              tenant_slug: workspace.slug,
              tenant_uuid: workspace.uuid
            })"
            :as="Button">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Leave Types
          </Link>
        </div>
      </div>

      <div>
        <h1 class="text-3xl font-bold tracking-tight">Create Leave Type</h1>
        <p class="text-muted-foreground">
          Add a new leave type for {{ workspace.name }}
        </p>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <FileText class="w-5 h-5" />
              Basic Information
            </CardTitle>
            <CardDescription>
              Define the basic details of this leave type
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label for="name">Leave Type Name *</Label>
              <Input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="e.g., Annual Leave, Sick Leave, Maternity Leave"
                class="mt-1"
                :class="{ 'border-red-500': form.errors.name }"
                required
              />
              <p v-if="form.errors.name" class="text-sm text-red-600 mt-1">
                {{ form.errors.name }}
              </p>
            </div>

            <div>
              <Label for="description">Description</Label>
              <Textarea
                id="description"
                v-model="form.description"
                placeholder="Describe when this leave type should be used..."
                class="mt-1"
                :class="{ 'border-red-500': form.errors.description }"
              />
              <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">
                {{ form.errors.description }}
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Allocation & Limits -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Calendar class="w-5 h-5" />
              Allocation & Limits
            </CardTitle>
            <CardDescription>
              Configure the amount of leave and restrictions
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <FormNumberField
                  id="max_days_per_year"
                  v-model="form.max_days_per_year"
                  label="Maximum Days Per Year"
                  :min="0"
                  :max="365"
                  :step="1"
                  :error="form.errors.max_days_per_year"
                  show-buttons
                  button-position="stacked"
                  required
                />
                <p class="text-sm text-muted-foreground mt-1">
                  Total days available per calendar year
                </p>
              </div>

              <div>
                <FormNumberField
                  id="minimum_notice_days"
                  v-model="form.minimum_notice_days"
                  label="Minimum Notice (Days)"
                  :min="0"
                  :max="365"
                  :step="1"
                  :error="form.errors.minimum_notice_days"
                  show-buttons
                  button-position="stacked"
                />
                <p class="text-sm text-muted-foreground mt-1">
                  How many days in advance must this leave be requested
                </p>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <FormNumberField
                  id="pay_percentage"
                  v-model="form.pay_percentage"
                  label="Pay Percentage (%)"
                  :min="0"
                  :max="100"
                  :step="0.01"
                  :error="form.errors.pay_percentage"
                  show-buttons
                  button-position="inline"
                />
                <p class="text-sm text-muted-foreground mt-1">
                  Percentage of salary paid during this leave (0-100%)
                </p>
              </div>

              <div>
                <FormNumberField
                  id="frequency_years"
                  v-model="form.frequency_years"
                  label="Frequency (Years)"
                  :min="1"
                  :max="10"
                  :step="1"
                  :error="form.errors.frequency_years"
                  show-buttons
                  button-position="stacked"
                />
                <p class="text-sm text-muted-foreground mt-1">
                  How often this leave can be taken (1 = annually)
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Eligibility & Requirements -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Users class="w-5 h-5" />
              Eligibility & Requirements
            </CardTitle>
            <CardDescription>
              Set who can use this leave type and what's required
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div>
              <Label for="gender">Gender Eligibility</Label>
              <Select v-model="form.gender">
                <SelectTrigger class="mt-1">
                  <SelectValue placeholder="Select gender eligibility" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="option in genderOptions"
                    :key="option.value"
                    :value="option.value">
                    {{ option.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p class="text-sm text-muted-foreground mt-1">
                Restrict this leave type to specific gender if applicable
              </p>
              <p v-if="form.errors.gender" class="text-sm text-red-600 mt-1">
                {{ form.errors.gender }}
              </p>
            </div>

            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="space-y-0.5">
                  <Label>Gender Specific</Label>
                  <p class="text-sm text-muted-foreground">
                    Enable if this leave type is restricted to a specific gender
                  </p>
                </div>
                <Switch 
                  v-model="form.gender_specific"
                  :checked="form.gender_specific"
                />
              </div>

              <div class="flex items-center justify-between">
                <div class="space-y-0.5">
                  <Label>Requires Documentation</Label>
                  <p class="text-sm text-muted-foreground">
                    Require employees to upload supporting documents
                  </p>
                </div>
                <Switch 
                  v-model="form.requires_documentation"
                  :checked="form.requires_documentation"
                />
              </div>

              <div class="flex items-center justify-between">
                <div class="space-y-0.5">
                  <Label>Allow Negative Balance</Label>
                  <p class="text-sm text-muted-foreground">
                    Allow employees to take more leave than they have accrued
                  </p>
                </div>
                <Switch 
                  v-model="form.allow_negative_balance"
                  :checked="form.allow_negative_balance"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4 pt-4">
          <Button
            type="button"
            variant="outline"
            :href="route('tenant.management.leave-types.index', {
              tenant_slug: workspace.slug,
              tenant_uuid: workspace.uuid
            })"
            as="a">
            Cancel
          </Button>
          <Button type="submit" :disabled="form.processing">
            <PlusCircle class="w-4 h-4 mr-2" />
            {{ form.processing ? 'Creating...' : 'Create Leave Type' }}
          </Button>
        </div>
      </form>
    </div>
  </TenantLayout>
</template>
