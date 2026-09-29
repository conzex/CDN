<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recycle_bin', function (Blueprint $table) {
            $table->id();
            $table->string('original_path')->index();
            $table->string('trashed_path');
            $table->string('type')->default('file');
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamp('deleted_at')->useCurrent()->index();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recycle_bin');
    }
};
