<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('owner_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('plan')->default('free');
            $t->boolean('is_active')->default(true);
        });
        Schema::create('warehouses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $t->string('name');
            $t->text('address')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['owner_id', 'name']);
        });
        Schema::create('warehouse_user', function (Blueprint $t) {
            $t->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->primary(['warehouse_id', 'user_id']);
        });
        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('client_name')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
        Schema::create('inventory_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('type')->default('room');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->unique(['project_id', 'name']);
        });
        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $t->foreignId('inventory_group_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('sku', 80)->unique();
            $t->string('barcode', 120)->nullable()->index();
            $t->uuid('qr_code')->unique();
            $t->string('category')->default('Furniture');
            $t->string('location')->nullable();
            $t->string('condition')->default('good');
            $t->string('status')->default('available');
            $t->unsignedInteger('quantity')->default(1);
            $t->unsignedInteger('low_stock_threshold')->default(0);
            $t->decimal('unit_cost', 12, 2)->default(0);
            $t->decimal('unit_price', 12, 2)->default(0);
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['warehouse_id', 'status']);
            $t->index(['warehouse_id', 'category']);
        });
        Schema::create('inventory_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('item_name');
            $t->string('action');
            $t->json('changes')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['warehouse_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['inventory_events', 'items', 'inventory_groups', 'projects', 'warehouse_user', 'warehouses'] as $table) {
            Schema::dropIfExists($table);
        } Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('owner_id'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['plan', 'is_active']));
    }
};
