<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { User as UserIcon, Mail, Phone, MapPin, Users } from 'lucide-vue-next';

interface Props {
  mustVerifyEmail: boolean;
  status?: string;
  departments?: Array<{ id: number; name: string }>;
  officeLocations?: string[];
  user: User
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Profile settings',
    href: '/settings/profile'
  }
];

const user = props.user as User;

const form = useForm({
  name: user.name || '',
  email: user.email || '',
  gender: user.gender || '',
  work_phone: user.work_phone || '',
  office_location: user.office_location || '',
});

const genderOptions = [
  { value: 'male', label: 'Male' },
  { value: 'female', label: 'Female' },
];

const defaultOfficeLocations = [
  'Main Office',
  'Branch Office',
  'Remote',
  'Hybrid',
  'Field Office',
];

const availableOfficeLocations = computed(() => {
  return props.officeLocations && props.officeLocations.length > 0
    ? props.officeLocations
    : defaultOfficeLocations;
});

const hasChanges = computed(() => {
  return form.name !== user.name ||
    form.email !== user.email ||
    form.gender !== user.gender ||
    form.work_phone !== user.work_phone ||
    form.office_location !== user.office_location;
});

const submit = () => {
  form.patch(route('profile.update'), {
    preserveScroll: true,
    onSuccess: () => {
      // Optional: Show success message or handle success
    },
  });
};

const formatPhoneNumber = (value: string) => {
  // Remove all non-numeric characters
  const cleaned = value.replace(/\D/g, '');

  // Format as (XXX) XXX-XXXX for US numbers
  if (cleaned.length >= 10) {
    const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
    if (match) {
      return `(${match[1]}) ${match[2]}-${match[3]}`;
    }
  }

  return value;
};

const handlePhoneInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const formatted = formatPhoneNumber(target.value);
  form.work_phone = formatted;
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Profile settings" />

    <SettingsLayout>
      <div class="space-y-8">
        <!-- Profile Information Section -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <UserIcon class="h-5 w-5" />
              Personal Information
            </CardTitle>
            <p class="text-sm text-muted-foreground">
              Update your personal details and contact information
            </p>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Basic Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                  <Label for="name">Full Name *</Label>
                  <Input
                    id="name"
                    v-model="form.name"
                    required
                    autocomplete="name"
                    placeholder="Enter your full name"
                    class="w-full"
                  />
                  <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                  <Label for="gender">Gender</Label>
                  <Select v-model="form.gender">
                    <SelectTrigger class="w-full">
                      <SelectValue placeholder="Select gender" />
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
                  <InputError :message="form.errors.gender" />
                </div>
              </div>

              <Separator />

              <!-- Contact Information -->
              <div class="space-y-4">
                <div class="flex items-center gap-2">
                  <Mail class="h-4 w-4 text-muted-foreground" />
                  <h3 class="text-lg font-medium">Contact Information</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <Label for="email">Email Address *</Label>
                    <Input
                      id="email"
                      type="email"
                      v-model="form.email"
                      required
                      autocomplete="username"
                      placeholder="Enter your email address"
                      class="w-full"
                    />
                    <InputError :message="form.errors.email" />

                    <!-- Email Verification Notice -->
                    <div v-if="mustVerifyEmail && !user.email_verified_at" class="space-y-2">
                      <div class="flex items-center gap-2">
                        <Badge variant="destructive" class="text-xs">Unverified</Badge>
                        <span class="text-sm text-muted-foreground">Your email address is unverified</span>
                      </div>
                      <div class="text-sm">
                        <Link
                          :href="route('verification.send')"
                          method="post"
                          as="button"
                          class="text-primary underline hover:no-underline">
                          Click here to resend the verification email
                        </Link>
                      </div>
                      <div v-if="status === 'verification-link-sent'" class="text-sm font-medium text-green-600">
                        A new verification link has been sent to your email address.
                      </div>
                    </div>
                    <div v-else-if="user.email_verified_at" class="flex items-center gap-2">
                      <Badge variant="default" class="text-xs">Verified</Badge>
                      <span class="text-sm text-muted-foreground">Email verified</span>
                    </div>
                  </div>

                  <div class="space-y-2">
                    <Label for="work_phone">Work Phone</Label>
                    <div class="relative">
                      <Phone class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input
                        id="work_phone"
                        v-model="form.work_phone"
                        type="tel"
                        autocomplete="tel"
                        placeholder="(555) 123-4567"
                        class="pl-10 w-full"
                        @input="handlePhoneInput"
                      />
                    </div>
                    <InputError :message="form.errors.work_phone" />
                    <p class="text-xs text-muted-foreground">
                      Your direct work phone number for internal contacts
                    </p>
                  </div>
                </div>
              </div>

              <Separator />

              <!-- Work Location -->
              <div class="space-y-4">
                <div class="flex items-center gap-2">
                  <MapPin class="h-4 w-4 text-muted-foreground" />
                  <h3 class="text-lg font-medium">Work Location</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-2">
                    <Label for="office_location">Office Location</Label>
                    <Select v-model="form.office_location">
                      <SelectTrigger class="w-full">
                        <SelectValue placeholder="Select your office location" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="location in availableOfficeLocations"
                          :key="location"
                          :value="location">
                          {{ location }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                    <InputError :message="form.errors.office_location" />
                    <p class="text-xs text-muted-foreground">
                      Your primary work location or office
                    </p>
                  </div>

                  <!-- Read-only work information -->
                  <div class="space-y-4">
                    <div class="space-y-2">
                      <Label class="text-muted-foreground">Department</Label>
                      <div class="p-3 bg-muted rounded-md">
                        <span class="text-sm">{{ user.department_model.name || 'Not assigned' }}</span>
                      </div>
                      <p class="text-xs text-muted-foreground">
                        Contact HR to update your department
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center justify-between pt-6 border-t">
                <div class="flex items-center gap-4">
                  <Button
                    type="submit"
                    :disabled="form.processing || !hasChanges"
                    :loading="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                  </Button>

                  <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 transform translate-x-2"
                    enter-to-class="opacity-100 transform translate-x-0"
                    leave-active-class="transition ease-in-out duration-300"
                    leave-from-class="opacity-100 transform translate-x-0"
                    leave-to-class="opacity-0 transform translate-x-2">
                    <div v-show="form.recentlySuccessful" class="flex items-center gap-2">
                      <div class="h-2 w-2 bg-green-500 rounded-full"></div>
                      <p class="text-sm text-green-600 font-medium">Profile updated successfully!</p>
                    </div>
                  </Transition>
                </div>

                <div v-if="hasChanges && !form.processing" class="text-sm text-muted-foreground">
                  You have unsaved changes
                </div>
              </div>
            </form>
          </CardContent>
        </Card>

        <!-- Read-only Employment Information -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Users class="h-5 w-5" />
              Employment Information
            </CardTitle>
            <p class="text-sm text-muted-foreground">
              Your employment details (contact HR to make changes)
            </p>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div class="space-y-2">
                <Label class="text-muted-foreground">Employee ID</Label>
                <div class="p-3 bg-muted rounded-md">
                  <span class="text-sm font-mono">{{ user.employee_id || 'Not assigned' }}</span>
                </div>
              </div>

              <div class="space-y-2">
                <Label class="text-muted-foreground">Position</Label>
                <div class="p-3 bg-muted rounded-md">
                  <span class="text-sm">{{ user.position || 'Not assigned' }}</span>
                </div>
              </div>

              <div class="space-y-2">
                <Label class="text-muted-foreground">Employment Type</Label>
                <div class="p-3 bg-muted rounded-md">
                  <span class="text-sm capitalize">{{ user.employment_type || 'Not specified' }}</span>
                </div>
              </div>

              <div class="space-y-2">
                <Label class="text-muted-foreground">Employment Status</Label>
                <div class="p-3 bg-muted rounded-md">
                  <div class="flex items-center gap-2">
                    <Badge
                      :variant="user.employment_status === 'active' ? 'default' : 'secondary'"
                      class="text-xs">
                      {{ user.employment_status || 'Unknown' }}
                    </Badge>
                  </div>
                </div>
              </div>

              <div class="space-y-2">
                <Label class="text-muted-foreground">Join Date</Label>
                <div class="p-3 bg-muted rounded-md">
                  <span class="text-sm">
                    {{ user.join_date ? new Date(user.join_date).toLocaleDateString() : 'Not specified' }}
                  </span>
                </div>
              </div>

              <div class="space-y-2">
                <Label class="text-muted-foreground">Reporting To</Label>
                <div class="p-3 bg-muted rounded-md">
                  <span class="text-sm">{{ user.manager?.name || 'Not assigned' }}</span>
                </div>
              </div>
            </div>

            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p class="text-sm text-blue-800">
                <strong>Need to update employment information?</strong>
                Contact your HR department or manager to make changes to your position, department, or other employment details.
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Account Deletion Section -->
        <DeleteUser />
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
