<?php

namespace Database\Factories;

use App\Fakers\FakeCourseNameProvider;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Course::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $this->faker->addProvider(new FakeCourseNameProvider($this->faker));
        return [
            'name' => $this->faker->courseName(),
        ];
    }
}
