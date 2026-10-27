<?php

namespace Database\Factories;

use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Vacancy> */
class VacancyFactory extends Factory
{
    public function definition(): array
    {
        $title = 'Sample role '.fake()->unique()->numberBetween(1, 99999);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'department' => 'Warehouse',
            'location' => 'Cul de Sac, Castries',
            'employment_type' => 'Full time',
            'summary' => 'A sample vacancy.',
            'duties' => "Pick and pack orders\nLoad delivery trucks",
            'requirements' => 'Able to lift cases safely',
            'closes_on' => today()->addDays(21),
            'published' => true,
            'is_demo' => true,
        ];
    }
}
