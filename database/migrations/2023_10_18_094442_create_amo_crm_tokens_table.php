<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('amo_crm_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('expires_in');
            $table->text('access_token');
            $table->text('refresh_token');
            $table->string('base_domain');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amo_crm_tokens');
    }
};
