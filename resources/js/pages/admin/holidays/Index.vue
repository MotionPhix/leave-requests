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
  TableRow
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { Card, CardContent } from '@/components/ui/card';
import { PencilIcon, TrashIcon, MoreHorizontalIcon } from 'lucide-vue-next';
import Pagination from '@/components/Pagination.vue';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Holidays',
    href: route('admin.holidays.index')
  }
];

defineProps<{
  holidays: {
    data: Array<{
      uuid: string;
      name: string;
      date: string;
      description: string | null;
      type: string;
      is_recurring: boolean;
    }>;
    links: Array<{
      url: string | null;
      label: string;
      active: boolean;
    }>;
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  }
}>();

const deleteHoliday = (uuid: string) => {
  if (confirm('Are you sure you want to delete this holiday?')) {
    router.delete(route('admin.holidays.destroy', uuid));
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">

    <Head title="Holidays" />

    <div class="p-6 max-w-5xl">
      <div class="flex justify-between items-start mb-6">
        <div>
          <h1 class="text-2xl font-bold">Holidays</h1>
          <p class="text-sm text-muted-foreground mb-4 max-w-md">
            Manage your holiday days here, both public and company holidays.
          </p>
        </div>

        <Button @click="router.visit(route('admin.holidays.create'))">
          Add Holiday
        </Button>
      </div>

      <Card>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Type</TableHead>
                <TableHead>Recurring</TableHead>
                <TableHead class="hidden md:table-column">Description</TableHead>
                <TableHead class="w-[70px]"></TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow
                v-for="holiday in holidays.data"
                :key="holiday.uuid">
                <TableCell>
                  {{ holiday.name }}
                </TableCell>

                <TableCell>
                  {{ new Date(holiday.date).toLocaleDateString() }}
                </TableCell>

                <TableCell>
                  <Badge :variant="holiday.type === 'Public Holiday' ? 'default' : 'secondary'">
                    {{ holiday.type }}
                  </Badge>
                </TableCell>

                <TableCell>
                  <Badge :variant="holiday.is_recurring ? 'success' : 'secondary'">
                    {{ holiday.is_recurring ? 'Recurring' : 'One-time' }}
                  </Badge>
                </TableCell>

                <TableCell class="max-w-[300px] truncate hidden md:table-cell">
                  {{ holiday.description }}
                </TableCell>

                <TableCell>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button
                        variant="ghost"
                        size="icon">
                        <MoreHorizontalIcon class="w-4 h-4" />
                      </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end">
                      <DropdownMenuItem
                        @click="router.visit(route('admin.holidays.edit', holiday.uuid))">
                        <PencilIcon class="w-4 h-4 mr-2" />
                        Edit
                      </DropdownMenuItem>

                      <DropdownMenuItem
                        @click="deleteHoliday(holiday.uuid)"
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

          <div class="mt-4 flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
              Showing {{ holidays.current_page }} of {{ holidays.last_page }} pages
              ({{ holidays.total }} items)
            </p>

            <Pagination
              :links="holidays.links"
              class="justify-end"
            />
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
