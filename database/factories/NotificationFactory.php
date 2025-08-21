<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\LoanApplication;
use App\Models\Product;
use App\Models\Member;
use App\Models\Request;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        // Randomly pick a notifiable type
        $notifiableTypes = [
            LoanApplication::class,
            Product::class,
            Request::class,
        ];
        $type = $this->faker->randomElement($notifiableTypes);

        // Fake ID (you can adjust depending on seeding order)
        $notifiableId = 1; 

        return [
            'title'       => $this->faker->sentence(3),
            'description' => $this->faker->sentence(10),
            'notifiable_type' => $type,
            'notifiable_id'   => $notifiableId,
            'is_read'    => $this->faker->boolean(10), // 10% chance read
        ];
    }
}
