<?php

namespace App\Enums;

enum EmploymentType: string
{
  case FullTime = 'full-time';
  case PartTime = 'part-time';
  case Contract = 'contract';
  case Intern = 'intern';

  public static function toArray(): array
  {
    return array_map(fn($type) => [
      'id' => $type->value,
      'name' => $type->label()
    ], self::cases());
  }

  public function label(): string
  {
    return match ($this) {
      self::FullTime => 'Full Time',
      self::PartTime => 'Part Time',
      self::Contract => 'Contract',
      self::Intern => 'Intern',
    };
  }
}
