<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Card, CardContent } from '@/components/ui/card';
import { PencilIcon, InfoIcon, TrashIcon, MoreHorizontalIcon } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Leave Types',
    href: route('admin.leave-types.index')
  }
];

defineProps<{
  leaveTypes: Array<{
    id: number;
    name: string;
    max_days_per_year: number;
    requires_documentation: boolean;
    gender_specific: boolean;
    gender: string;
    frequency_years: number;
    pay_percentage: number;
  }>;
}>();

const deleteLeaveType = (id: number) => {
  if (confirm('Are you sure you want to delete this leave type?')) {
    router.delete(route('admin.leave-types.destroy', id));
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head title="Leave Types" />

    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Leave Types</h1>
        <Button @click="router.visit(route('admin.leave-types.create'))">
          Add Leave Type
        </Button>
      </div>

      <Card>
        <CardContent>

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Days/Year</TableHead>
                <TableHead>Documentation</TableHead>
                <TableHead>Gender</TableHead>
                <TableHead>Frequency</TableHead>
                <TableHead>Pay %</TableHead>
                <TableHead class="w-[70px]"></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="type in leaveTypes"
                        :key="type.id">
                <TableCell>{{ type.name }}</TableCell>
                <TableCell>{{ type.max_days_per_year }}</TableCell>
                <TableCell>
                  <Badge :variant="type.requires_documentation ? 'default' : 'secondary'">
                    {{ type.requires_documentation ? 'Required' : 'Not Required' }}
                  </Badge>
                </TableCell>
                <TableCell class="capitalize">
                  {{ type.gender_specific ? type.gender : 'Any' }}
                </TableCell>
                <TableCell>
                  {{ type.frequency_years > 1 ? `Every ${type.frequency_years} years` : 'Yearly' }}
                </TableCell>
                <TableCell>{{ type.pay_percentage }}%</TableCell>
                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost"
                              size="icon">
                        <MoreHorizontalIcon class="w-4 h-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem @click="router.visit(route('admin.leave-types.show', type.uuid))">
                        <InfoIcon class="w-4 h-4 mr-2" />
                        Details
                      </DropdownMenuItem>

                      <DropdownMenuItem @click="router.visit(route('admin.leave-types.edit', type.uuid))">
                        <PencilIcon class="w-4 h-4 mr-2" />
                        Edit
                      </DropdownMenuItem>
                      <DropdownMenuItem @click="deleteLeaveType(type.id)"
                                        class="text-red-600">
                        <TrashIcon class="w-4 h-4 mr-2" />
                        Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>

        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
