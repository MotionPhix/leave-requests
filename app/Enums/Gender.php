<?php

namespace App\Enums;

enum Gender: string
{
  case Male = 'male';
  case Female = 'female';

  public static function toArray(): array
  {
    return array_map(fn($gender) => [
      'id' => $gender->value,
      'name' => $gender->label()
    ], self::cases());
  }

  public function label(): string
  {
    return match ($this) {
      self::Male => 'Male',
      self::Female => 'Female',
    };
  }
}
