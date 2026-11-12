<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::create('item_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('normalized_name', 100);
            $table->timestamps();
            $table->unique(['owner_id', 'normalized_name']);
        });
        Schema::table('users', fn (Blueprint $table) => $table->string('profile_image')->nullable());
        DB::table('items')->where('category', 'Other')->update(['category' => 'Others']);
    }
    public function down(): void {
        Schema::dropIfExists('item_categories');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('profile_image'));
    }
};
