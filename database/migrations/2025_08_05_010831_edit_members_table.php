<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['address', 'email']);
            $table->string('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('member_number')->unique()->after('user_id');
            $table->string('tin_number')->nullable()->after('postal_code');
            $table->date('date_of_birth')->nullable()->after('tin_number');
            $table->string('place_of_birth')->nullable()->after('date_of_birth');
            $table->integer('age')->nullable()->after('place_of_birth');
            $table->integer('dependents')->nullable()->after('age');
            $table->string('employer')->nullable()->after('dependents');
            $table->string('position')->nullable()->after('employer');
            $table->decimal('monthly_income', 12, 2)->default(0.00)->after('position');
            $table->decimal('other_income', 12, 2)->default(0.00)->after('monthly_income');
            $table->enum('monthly_disposable_income_range', ['0-5000', '5001-10000', '10001-20000', '20001+'])->nullable()->after('other_income');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('monthly_disposable_income_range');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
