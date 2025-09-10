<?php

namespace Database\Factories;

use App\Models\Holiday;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Holiday>
 */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 year');
        $endDate = clone $startDate;
        
        // 70% chance of single-day holiday, 30% chance of multi-day
        if (fake()->boolean(30)) {
            $endDate->modify('+' . fake()->numberBetween(1, 4) . ' days');
        }

        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->randomElement([
                'Christmas Day',
                'New Year\'s Day', 
                'Independence Day',
                'Labor Day',
                'Thanksgiving',
                'Memorial Day',
                'Presidents Day',
                'Columbus Day',
                'Veterans Day',
                'Martin Luther King Jr. Day',
                'Easter Monday',
                'Good Friday',
                'Company Anniversary',
                'Founders Day',
                'Summer Break',
                'Year-End Closure'
            ]),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'date' => $startDate, // Backward compatibility
            'type' => fake()->randomElement([
                'National Holiday',
                'Religious Holiday', 
                'Company Holiday',
                'Floating Holiday',
                'Company Closure'
            ]),
            'description' => fake()->boolean(60) ? fake()->sentence() : null,
            'color' => fake()->randomElement([
                '#dc2626', // Red - National
                '#7c3aed', // Purple - Religious  
                '#2563eb', // Blue - Company
                '#059669', // Green - Floating
                '#ea580c', // Orange - Closure
            ]),
            'is_recurring' => fake()->boolean(70),
            'recurrence_pattern' => fake()->boolean(70) ? 'yearly' : null,
            'is_visible_to_employees' => fake()->boolean(85),
        ];
    }

    /**
     * Create a single-day holiday
     */
    public function singleDay(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'end_date' => $attributes['start_date'],
            ];
        });
    }

    /**
     * Create a multi-day holiday
     */
    public function multiDay(int $days = null): static
    {
        return $this->state(function (array $attributes) use ($days) {
            $startDate = $attributes['start_date'];
            $endDate = clone $startDate;
            $endDate->modify('+' . ($days ?? fake()->numberBetween(2, 7)) . ' days');
            
            return [
                'end_date' => $endDate,
            ];
        });
    }

    /**
     * Create a recurring holiday
     */
    public function recurring(): static
    {
        return $this->state([
            'is_recurring' => true,
            'recurrence_pattern' => 'yearly',
        ]);
    }

    /**
     * Create a non-recurring holiday
     */
    public function oneTime(): static
    {
        return $this->state([
            'is_recurring' => false,
            'recurrence_pattern' => null,
        ]);
    }

    /**
     * Create a holiday of specific type
     */
    public function type(string $type): static
    {
        $colorMap = [
            'National Holiday' => '#dc2626',
            'Religious Holiday' => '#7c3aed', 
            'Company Holiday' => '#2563eb',
            'Floating Holiday' => '#059669',
            'Company Closure' => '#ea580c',
        ];

        return $this->state([
            'type' => $type,
            'color' => $colorMap[$type] ?? '#6b7280',
        ]);
    }

    /**
     * Create a holiday visible to employees
     */
    public function visibleToEmployees(): static
    {
        return $this->state([
            'is_visible_to_employees' => true,
        ]);
    }

    /**
     * Create a holiday hidden from employees
     */
    public function hiddenFromEmployees(): static
    {
        return $this->state([
            'is_visible_to_employees' => false,
        ]);
    }

    /**
     * Create common national holidays
     */
    public function nationalHoliday(): static
    {
        return $this->state([
            'type' => 'National Holiday',
            'color' => '#dc2626',
            'is_recurring' => true,
            'recurrence_pattern' => 'yearly',
            'is_visible_to_employees' => true,
            'name' => fake()->randomElement([
                'New Year\'s Day',
                'Independence Day',
                'Labor Day',
                'Thanksgiving',
                'Memorial Day',
                'Veterans Day'
            ])
        ]);
    }
}