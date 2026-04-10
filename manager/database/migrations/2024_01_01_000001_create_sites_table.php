<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('url');
            $table->string('db_name');
            $table->string('wp_version')->default('latest');
            $table->string('php_version')->default('8.3');
            $table->string('admin_user')->default('admin');
            $table->string('admin_email')->default('admin@localhost.test');
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->bigInteger('disk_usage')->nullable(); // bytes
            $table->bigInteger('db_size')->nullable(); // bytes
            $table->text('notes')->nullable();
            $table->json('plugins')->nullable();
            $table->json('themes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
