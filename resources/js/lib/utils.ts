import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { CalendarDate } from '@internationalized/date';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

// Accepts CalendarDate or any object with year/month/day properties
export function formatDate(dateValue: CalendarDate | { year: number, month: number, day: number } | null | undefined): string | null {
  if (!dateValue) return null;

  let year: number, month: number, day: number;

  if (dateValue instanceof CalendarDate) {
    year = dateValue.year;
    month = dateValue.month;
    day = dateValue.day;
  } else if ('year' in dateValue && 'month' in dateValue && 'day' in dateValue) {
    year = dateValue.year;
    month = dateValue.month;
    day = dateValue.day;
  } else {
    console.warn('Unsupported date format provided to formatDate');
    return null;
  }

  return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
}

