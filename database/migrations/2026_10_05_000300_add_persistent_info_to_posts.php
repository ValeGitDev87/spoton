<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->boolean('is_persistent_info')->default(false)->index();
            $table->timestamp('expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('posts')->whereNull('expires_at')->update([
            'status' => 'expired',
            'expires_at' => now(),
        ]);
        Schema::table('posts', function (Blueprint $table): void {
            $table->timestamp('expires_at')->nullable(false)->change();
            $table->dropColumn('is_persistent_info');
        });
    }
};
