<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['warehouses', 'projects', 'inventory_groups', 'items'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->json('photos')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['warehouses', 'projects', 'inventory_groups', 'items'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('photos'));
        }
    }
};
