<?php

namespace Database\Factories;

use App\Models\LoanClerk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoanClerkFactory extends Factory
{
    protected $model = LoanClerk::class;

    public function definition(): array
    {
        // Create a user with role = loan_clerk
        $user = User::factory()->loanClerk()->create();

        return [
            'user_id'       => $user->id,
            'clerk_id'      => strtoupper($this->faker->unique()->bothify('CLERK-####')),
            'full_name'     => $this->faker->name(),
            'contact_number'=> $this->faker->phoneNumber(),
            'address'       => $this->faker->address(),
            'gender'        => $this->faker->randomElement(['Male', 'Female']),
            'job_title'     => 'Loan Clerk',
            'date_hired'    => $this->faker->date(),
            'status'        => $this->faker->randomElement(['active', 'inactive', 'terminated']),
        ];
    }
}
