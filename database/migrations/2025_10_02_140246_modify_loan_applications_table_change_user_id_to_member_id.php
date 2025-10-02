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
        Schema::table('loan_applications', function (Blueprint $table) {
            // Drop the existing foreign key constraint for user_id
            $table->dropForeign(['user_id']);
            
            // Rename user_id column to member_id
            $table->renameColumn('user_id', 'member_id');
            
            // Add new foreign key constraint for member_id
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            // Drop the member_id foreign key constraint
            $table->dropForeign(['member_id']);
            
            // Rename member_id column back to user_id
            $table->renameColumn('member_id', 'user_id');
            
            // Add back the user_id foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
