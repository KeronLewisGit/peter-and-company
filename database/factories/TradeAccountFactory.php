<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Enums\PaymentTerms;
use App\Enums\TradeType;
use App\Models\TradeAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TradeAccount> */
class TradeAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_name' => fake()->company(),
            'trade_type' => TradeType::Supermarket,
            'contact_name' => fake()->name(),
            'phone' => '758-452-'.fake()->numerify('####'),
            'email' => fake()->unique()->safeEmail(),
            'district' => 'castries',
            'terms' => PaymentTerms::Cod,
            'status' => AccountStatus::Active,
            'is_demo' => true,
        ];
    }

    public function pharmacy(): static
    {
        return $this->state(fn () => ['trade_type' => TradeType::Pharmacy, 'pharmacy_licence' => 'PH-0001']);
    }
}
