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
        Schema::table('users', function (Blueprint $table) {
            // Add verification_code and verification_code_expires_at if they don't exist
            if (!Schema::hasColumn('users', 'verification_code')) {
                $table->string('verification_code', 6)->nullable()->after('otp');
            }
            
            if (!Schema::hasColumn('users', 'verification_code_expires_at')) {
                $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            }
            
            // Change otp column from integer to string if it exists as integer
            if (Schema::hasColumn('users', 'otp')) {
                $table->string('otp', 6)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'verification_code')) {
                $table->dropColumn('verification_code');
            }
            
            if (Schema::hasColumn('users', 'verification_code_expires_at')) {
                $table->dropColumn('verification_code_expires_at');
            }
            
            // Revert otp back to integer if needed
            if (Schema::hasColumn('users', 'otp')) {
                $table->integer('otp')->nullable()->change();
            }
        });
    }
};
