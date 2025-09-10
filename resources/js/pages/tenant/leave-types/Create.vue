<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ArrowLeft, PlusCircle, FileText, Users, Calendar, Shield } from 'lucide-vue-next';

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
          <Button
            variant="ghost"
            size="sm"
            :href="route('tenant.management.leave-types.index', {
              tenant_slug: workspace.slug,
              tenant_uuid: workspace.uuid
            })"
            as="a">
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Leave Types
          </Button>
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
                <Label for="max_days_per_year">Maximum Days Per Year *</Label>
                <Input
                  id="max_days_per_year"
                  v-model.number="form.max_days_per_year"
                  type="number"
                  min="0"
                  max="365"
                  class="mt-1"
                  :class="{ 'border-red-500': form.errors.max_days_per_year }"
                  required
                />
                <p class="text-sm text-muted-foreground mt-1">
                  Total days available per calendar year
                </p>
                <p v-if="form.errors.max_days_per_year" class="text-sm text-red-600 mt-1">
                  {{ form.errors.max_days_per_year }}
                </p>
              </div>

              <div>
                <Label for="minimum_notice_days">Minimum Notice (Days)</Label>
                <Input
                  id="minimum_notice_days"
                  v-model.number="form.minimum_notice_days"
                  type="number"
                  min="0"
                  max="365"
                  class="mt-1"
                  :class="{ 'border-red-500': form.errors.minimum_notice_days }"
                />
                <p class="text-sm text-muted-foreground mt-1">
                  How many days in advance must this leave be requested
                </p>
                <p v-if="form.errors.minimum_notice_days" class="text-sm text-red-600 mt-1">
                  {{ form.errors.minimum_notice_days }}
                </p>
              </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
              <div>
                <Label for="pay_percentage">Pay Percentage (%)</Label>
                <Input
                  id="pay_percentage"
                  v-model.number="form.pay_percentage"
                  type="number"
                  min="0"
                  max="100"
                  step="0.01"
                  class="mt-1"
                  :class="{ 'border-red-500': form.errors.pay_percentage }"
                />
                <p class="text-sm text-muted-foreground mt-1">
                  Percentage of salary paid during this leave (0-100%)
                </p>
                <p v-if="form.errors.pay_percentage" class="text-sm text-red-600 mt-1">
                  {{ form.errors.pay_percentage }}
                </p>
              </div>

              <div>
                <Label for="frequency_years">Frequency (Years)</Label>
                <Input
                  id="frequency_years"
                  v-model.number="form.frequency_years"
                  type="number"
                  min="1"
                  max="10"
                  class="mt-1"
                  :class="{ 'border-red-500': form.errors.frequency_years }"
                />
                <p class="text-sm text-muted-foreground mt-1">
                  How often this leave can be taken (1 = annually)
                </p>
                <p v-if="form.errors.frequency_years" class="text-sm text-red-600 mt-1">
                  {{ form.errors.frequency_years }}
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
