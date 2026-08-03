<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->boolean('is_locked')->default(false)->index()->after('is_active');
            $table->string('access_password_hash')->nullable()->after('is_locked');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropIndex(['is_locked']);
            $table->dropColumn(['is_locked', 'access_password_hash']);
        });
    }
};
