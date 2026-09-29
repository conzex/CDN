<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->char('token', 32)->unique();
            $table->string('path')->index();
            $table->string('type')->default('file');
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_downloads')->nullable();
            $table->integer('download_count')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
