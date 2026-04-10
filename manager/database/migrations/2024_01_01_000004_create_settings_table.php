<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Inserir configurações padrão
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            ['key' => 'default_admin_user', 'value' => 'devconecta', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_admin_password', 'value' => 'Ga96911431@', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'default_admin_email', 'value' => 'admin@localhost.test', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mysql_root_password', 'value' => 'root', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mysql_user', 'value' => 'wordpress', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mysql_password', 'value' => 'wordpress', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
