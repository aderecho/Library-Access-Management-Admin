<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $branchId = DB::table('branches')->insertGetId([
            'name' => 'Main Library',
            'code' => 'MAIN',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('scanner_tokens', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
        Schema::table('rfid_transactions', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            $table->index(['branch_id', 'scanned_at']);
        });

        DB::table('scanner_tokens')->whereNull('branch_id')->update(['branch_id' => $branchId]);
        DB::table('rfid_transactions')->whereNull('branch_id')->update(['branch_id' => $branchId]);
    }

    public function down(): void
    {
        Schema::table('rfid_transactions', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'scanned_at']);
            $table->dropConstrainedForeignId('branch_id');
        });
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('branch_id'));
        Schema::table('scanner_tokens', fn (Blueprint $table) => $table->dropConstrainedForeignId('branch_id'));
        Schema::dropIfExists('branches');
    }
};
