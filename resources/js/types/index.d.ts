import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
  user: User;
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export interface NavItem {
  title: string;
  href: string;
  icon?: LucideIcon;
  isActive?: boolean;
}

export interface SharedData extends PageProps {
  name: string;
  quote: { message: string; author: string };
  auth: Auth;
  ziggy: Config & { location: string };
  sidebarOpen: boolean;
}

export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  role?: string;
  isOwner?: boolean;
  permissions?: {
    canApproveLeave?: boolean;
    canViewAllUsers?: boolean;
    canCreateLeaveRequests?: boolean;
  };
}

export interface Department {
  id: number;
  name: string;
  employee_count?: number;
}

export interface Employee {
  id: number;
  name: string;
  email: string;
  position: string;
  department?: Department;
}

export interface LeaveRequest {
  id: number;
  employee?: Employee;
  user?: User;
  start_date: string;
  end_date: string;
  days: number;
  type: string;
  status: string;
  reason: string;
  created_at: string;
}

export interface Holiday {
  id: number;
  name: string;
  date: string;
  is_recurring: boolean;
}

export interface Stats {
  totalEmployees: number;
  totalDepartments: number;
  pendingLeaveRequests: number;
  approvedLeaveRequests: number;
  currentlyOnLeave: number;
  companyPendingRequests: number;
  thisMonthApproved: number;
  nextHoliday: string | null;
}

export interface ChartData {
  leaveTrends: Array<{
    name: string;
    data: number[];
  }>;
  departments: Array<{
    name: string;
    data: number[];
  }>;
}

export interface DashboardProps {
  stats: Stats;
  departments: Department[];
  recentLeaveRequests: LeaveRequest[];
  teamPendingRequests: LeaveRequest[];
  upcomingHolidays: Holiday[];
  chartData: ChartData;
  recentEmployees?: Employee[];
}

export type BreadcrumbItemType = BreadcrumbItem;
