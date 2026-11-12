<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
return new class extends Migration {
    public function up(): void {
        Schema::table('items', function (Blueprint $t) {
            $t->decimal('acquisition_total', 20, 2)->default(0);
            $t->date('acquired_on')->nullable();
            $t->decimal('repair_estimate', 12, 2)->nullable();
            $t->decimal('reuse_revenue_estimate', 12, 2)->nullable();
            $t->decimal('resale_estimate', 12, 2)->nullable();
            $t->unsignedInteger('revision')->default(0);
            $t->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
        DB::table('items')->update(['acquisition_total' => DB::raw('unit_cost * quantity')]);
        Schema::create('item_shares', function (Blueprint $t) {
            $t->id(); $t->foreignId('item_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $t->string('organization', 120); $t->string('role', 30); $t->string('access', 20);
            $t->timestamp('expires_at')->nullable(); $t->timestamp('revoked_at')->nullable(); $t->timestamps();
            $t->unique(['item_id', 'user_id']); $t->index(['user_id','revoked_at']);
        });
        Schema::create('item_entries', function (Blueprint $t) {
            $t->id(); $t->foreignId('item_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kind', 30); $t->date('occurred_on'); $t->decimal('amount', 12, 2)->default(0);
            $t->unsignedInteger('uses')->default(0); $t->unsignedInteger('days')->default(0);
            $t->text('notes')->nullable(); $t->uuid('request_key')->unique();
            $t->timestamp('voided_at')->nullable(); $t->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('void_reason', 255)->nullable(); $t->timestamps(); $t->index(['item_id','occurred_on']);
        });
        Schema::create('item_activities', function (Blueprint $t) {
            $t->id(); $t->foreignId('item_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kind', 30); $t->string('visibility', 20)->default('shared');
            $t->text('body'); $t->json('attachments')->nullable(); $t->timestamps();
            $t->index(['item_id','created_at']);
        });
        Schema::create('item_handoffs', function (Blueprint $t) {
            $t->id(); $t->foreignId('item_id')->constrained()->cascadeOnDelete();
            $t->foreignId('from_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('to_user_id')->constrained('users')->restrictOnDelete();
            $t->string('status', 20)->default('pending'); $t->date('due_on')->nullable(); $t->text('instructions');
            $t->text('response')->nullable(); $t->timestamp('responded_at')->nullable(); $t->timestamps();
            $t->index(['item_id','status']);
        });
    }
    public function down(): void {
        foreach (['item_handoffs','item_activities','item_entries','item_shares'] as $table) Schema::dropIfExists($table);
        Schema::table('items', function (Blueprint $t) {
            $t->dropConstrainedForeignId('responsible_user_id');
            $t->dropColumn(['acquisition_total','acquired_on','repair_estimate','reuse_revenue_estimate','resale_estimate','revision']);
        });
    }
};
