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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('bioguide_id')->unique();
            $table->string('depiction_attribution', 255)->nullable();
            $table->string('depiction_image_url', 255)->nullable();
            $table->string('name', 100);
            $table->string('party_name', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->integer('district')->nullable()->unsigned();
            $table->timestamp('updated_date');
            $table->string('url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
