<?php

namespace Tests\Feature;

use App\Models\LoanApplication;
use App\Models\LoanType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppliancesLoanApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_stock_is_decremented_on_approval(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $product = Product::factory()->create([
            'stock_quantity' => 2,
        ]);

        $loanType = LoanType::factory()->create([
            // use a reasonable decimal rate so payment calc doesn't explode (controller assumes decimal)
            'interest_rate' => 0.12,
        ]);

        $application = LoanApplication::factory()->create([
            'user_id' => User::factory()->member()->create()->id,
            'loan_type_id' => $loanType->id,
            'product_id' => $product->id,
            'status' => 'pending',
        ]);

        // Hit the admin approval endpoint
        $this->withoutMiddleware(); // avoid any unregistered role middleware in tests
        $response = $this->postJson("/api/admin/appliances-loan/approved/{$application->id}");

        $response->assertCreated();

        $product->refresh();
        $application->refresh();

        $this->assertSame('approved', $application->status);
        $this->assertEquals(1, $product->stock_quantity, 'Stock should decrement by 1');
    }

    public function test_approval_fails_when_stock_is_zero(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $product = Product::factory()->create([
            'stock_quantity' => 0,
        ]);

        $loanType = LoanType::factory()->create(['interest_rate' => 0.1]);

        $application = LoanApplication::factory()->create([
            'user_id' => User::factory()->member()->create()->id,
            'loan_type_id' => $loanType->id,
            'product_id' => $product->id,
            'status' => 'pending',
        ]);

        $this->withoutMiddleware();
        $response = $this->postJson("/api/admin/appliances-loan/approved/{$application->id}");

        $response->assertStatus(422);

        $product->refresh();
        $application->refresh();

        $this->assertSame('pending', $application->status, 'Application should remain pending');
        $this->assertEquals(0, $product->stock_quantity, 'Stock should remain unchanged');
    }

    public function test_second_approval_attempt_does_not_decrement_again(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $product = Product::factory()->create([
            'stock_quantity' => 1,
        ]);

        $loanType = LoanType::factory()->create(['interest_rate' => 0.12]);

        $application = LoanApplication::factory()->create([
            'user_id' => User::factory()->member()->create()->id,
            'loan_type_id' => $loanType->id,
            'product_id' => $product->id,
            'status' => 'pending',
        ]);

        $this->withoutMiddleware();
        $first = $this->postJson("/api/admin/appliances-loan/approved/{$application->id}");
        $first->assertCreated();

        $product->refresh();
        $this->assertEquals(0, $product->stock_quantity);

        // Second attempt
        $second = $this->postJson("/api/admin/appliances-loan/approved/{$application->id}");
        $second->assertStatus(400);

        $product->refresh();
        $this->assertEquals(0, $product->stock_quantity, 'Stock should not decrement again');
    }
}
