<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->string('provider', 32)->nullable()->after('type');
            $table->string('provider_place_id')->nullable()->after('provider');
            $table->string('location_kind', 20)->default('poi')->after('provider_place_id');
            $table->string('normalized_name')->nullable()->after('name');

            $table->unique(['provider', 'provider_place_id']);
            $table->index(['normalized_name', 'type']);
            $table->index(['location_kind', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropUnique(['provider', 'provider_place_id']);
            $table->dropIndex(['normalized_name', 'type']);
            $table->dropIndex(['location_kind', 'is_active']);
            $table->dropColumn([
                'provider',
                'provider_place_id',
                'location_kind',
                'normalized_name',
            ]);
        });
    }
};
