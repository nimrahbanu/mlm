<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\EPin;
class EPinFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    protected $model = EPin::class;
    public function definition()
    {
        return [
            'member_id' => $this->faker->numberBetween(1, 100),
            'member_name' => $this->faker->name,
            'balance' => $this->faker->randomFloat(2, 0, 1000),
            'quantity' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->faker ? 1:0,
            'flag' => $this->faker->boolean ? 1:0,
        ];
    }
}
