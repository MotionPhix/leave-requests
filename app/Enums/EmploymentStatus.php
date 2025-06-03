<?php

namespace App\Enums;

enum EmploymentStatus: string
{
  case Active = 'active';
  case Probation = 'probation';
  case Terminated = 'terminated';
  case Resigned = 'resigned';

  public static function toArray(): array
  {
    return array_map(fn($status) => [
      'id' => $status->value,
      'name' => $status->label()
    ], self::cases());
  }

  public function label(): string
  {
    return match ($this) {
      self::Active => 'Active',
      self::Probation => 'Probation',
      self::Terminated => 'Terminated',
      self::Resigned => 'Resigned',
    };
  }
}
