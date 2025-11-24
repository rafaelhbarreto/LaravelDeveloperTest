<?php

declare(strict_types=1);

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
            // Index for faster lookups by state and party
            $table->index(['state', 'party_name']);

            // Index for updated_date to optimize conditional update queries
            $table->index('updated_date');
        });

        Schema::table('member_terms', function (Blueprint $table) {
            // Composite index for efficient queries and ensuring uniqueness
            $table->index(['member_id', 'chamber', 'start_year']);

            // Index for filtering by chamber
            $table->index('chamber');

            // Index for year range queries
            $table->index(['start_year', 'end_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['state', 'party_name']);
            $table->dropIndex(['updated_date']);
        });

        Schema::table('member_terms', function (Blueprint $table) {
            $table->dropIndex(['member_id', 'chamber', 'start_year']);
            $table->dropIndex(['chamber']);
            $table->dropIndex(['start_year', 'end_year']);
        });
    }
};
