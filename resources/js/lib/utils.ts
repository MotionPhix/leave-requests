import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';
import { getLocalTimeZone, parseDate } from '@internationalized/date';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatDate(dateValue: any): string | null {
  if (!dateValue) return null

  const date = dateValue.toDate(getLocalTimeZone())

  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}
