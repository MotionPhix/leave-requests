<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TenantLayout from '@/layouts/TenantLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { 
  ArrowLeft, 
  Edit, 
  Users, 
  Calendar, 
  TrendingUp, 
  Clock,
  FileText,
  DollarSign,
  Shield,
  AlertCircle
} from 'lucide-vue-next';

interface LeaveType {
  id: number;
  uuid: string;
  name: string;
  description: string | null;
  max_days_per_year: number | null;
  requires_documentation: boolean;
  gender_specific: boolean;
  gender: string;
  frequency_years: number;
  pay_percentage: number;
  minimum_notice_days: number;
  allow_negative_balance: boolean;
}

interface LeaveAnalytics {
  total_employees_eligible: number;
  total_requests_this_year: number;
  total_days_taken: number;
  average_days_per_employee: number;
  pending_requests: number;
  approved_requests: number;
  rejected_requests: number;
  most_common_months: string[];
}

const props = defineProps<{
  leaveType: LeaveType;
  analytics: LeaveAnalytics;
  workspace: { uuid: string; slug: string; name: string };
  canManageLeaveTypes: boolean;
}>();
</script>

<template>
  <Head :title="`${leaveType.name} - Leave Type Details`" />
  
  <TenantLayout>
    <div class="space-y-6 max-w-4xl">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <Link
            :href="route('tenant.management.leave-types.index', {
              tenant_slug: workspace.slug,
              tenant_uuid: workspace.uuid
            })"
            :as="Button"
            variant="ghost"
            size="sm"
          >
            <ArrowLeft class="w-4 h-4 mr-2" />
            Back to Leave Types
          </Link>
        </div>
        <Link
          v-if="canManageLeaveTypes"
          :href="route('tenant.management.leave-types.edit', {
            tenant_slug: workspace.slug,
            tenant_uuid: workspace.uuid,
            leaveType: leaveType.uuid
          })"
          :as="Button"
        >
          <Edit class="w-4 h-4 mr-2" />
          Edit Leave Type
        </Link>
      </div>

      <!-- Leave Type Overview -->
      <Card>
        <CardHeader>
          <div class="flex items-start justify-between">
            <div>
              <CardTitle class="text-2xl">{{ leaveType.name }}</CardTitle>
              <CardDescription v-if="leaveType.description" class="mt-2">
                {{ leaveType.description }}
              </CardDescription>
            </div>
          </div>
        </CardHeader>
        
        <CardContent class="space-y-6">
          <!-- Key Details -->
          <div class="grid md:grid-cols-3 gap-6">
            <div class="space-y-2">
              <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <Calendar class="w-4 h-4" />
                Annual Allowance
              </div>
              <div class="text-2xl font-bold">
                {{ leaveType.max_days_per_year || 'Unlimited' }}
                <span v-if="leaveType.max_days_per_year" class="text-sm font-normal text-muted-foreground">days</span>
              </div>
            </div>
            
            <div class="space-y-2">
              <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <DollarSign class="w-4 h-4" />
                Pay Rate
              </div>
              <div class="text-2xl font-bold">
                {{ leaveType.pay_percentage }}%
                <span class="text-sm font-normal text-muted-foreground">of salary</span>
              </div>
            </div>
            
            <div class="space-y-2">
              <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                <Clock class="w-4 h-4" />
                Notice Required
              </div>
              <div class="text-2xl font-bold">
                {{ leaveType.minimum_notice_days }}
                <span class="text-sm font-normal text-muted-foreground">days</span>
              </div>
            </div>
          </div>

          <Separator />

          <!-- Policy Details -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold">Policy Details</h3>
            <div class="grid md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium">Frequency</span>
                  <span class="text-sm">Every {{ leaveType.frequency_years }} year{{ leaveType.frequency_years > 1 ? 's' : '' }}</span>
                </div>
                
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium">Documentation Required</span>
                  <Badge :variant="leaveType.requires_documentation ? 'default' : 'secondary'">
                    {{ leaveType.requires_documentation ? 'Yes' : 'No' }}
                  </Badge>
                </div>
                
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium">Gender Restrictions</span>
                  <Badge :variant="leaveType.gender_specific ? 'default' : 'secondary'">
                    {{ leaveType.gender_specific ? (leaveType.gender.charAt(0).toUpperCase() + leaveType.gender.slice(1) + ' Only') : 'None' }}
                  </Badge>
                </div>
                
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium">Negative Balance</span>
                  <Badge :variant="leaveType.allow_negative_balance ? 'default' : 'secondary'">
                    {{ leaveType.allow_negative_balance ? 'Allowed' : 'Not Allowed' }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Analytics -->
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Eligible Employees</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ analytics.total_employees_eligible }}</div>
            <p class="text-xs text-muted-foreground">employees can use this leave type</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Requests This Year</CardTitle>
            <TrendingUp class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ analytics.total_requests_this_year }}</div>
            <p class="text-xs text-muted-foreground">total leave requests</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Days Taken</CardTitle>
            <Calendar class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ analytics.total_days_taken }}</div>
            <p class="text-xs text-muted-foreground">days used this year</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Average Usage</CardTitle>
            <Users class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-bold">{{ analytics.average_days_per_employee.toFixed(1) }}</div>
            <p class="text-xs text-muted-foreground">days per employee</p>
          </CardContent>
        </Card>
      </div>

      <!-- Request Status -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <FileText class="w-5 h-5" />
            Request Statistics
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid md:grid-cols-3 gap-6">
            <div class="text-center">
              <div class="text-2xl font-bold text-green-600">{{ analytics.approved_requests }}</div>
              <p class="text-sm text-muted-foreground">Approved</p>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-yellow-600">{{ analytics.pending_requests }}</div>
              <p class="text-sm text-muted-foreground">Pending</p>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold text-red-600">{{ analytics.rejected_requests }}</div>
              <p class="text-sm text-muted-foreground">Rejected</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Usage Patterns -->
      <Card v-if="analytics.most_common_months.length > 0">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <TrendingUp class="w-5 h-5" />
            Usage Patterns
          </CardTitle>
          <CardDescription>Most popular months for this leave type</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-2">
            <Badge v-for="month in analytics.most_common_months" :key="month" variant="secondary">
              {{ month }}
            </Badge>
          </div>
        </CardContent>
      </Card>
    </div>
  </TenantLayout>
</template>