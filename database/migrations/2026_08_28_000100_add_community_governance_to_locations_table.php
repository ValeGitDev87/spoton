<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedInteger('last_location_accuracy_meters')
                ->nullable()
                ->after('last_known_longitude');
        });

        Schema::table('locations', function (Blueprint $table): void {
            // Partner is the safe default: every location created before this migration
            // and every location manually created by an admin keeps Partner privileges.
            $table->string('tier', 20)->default('partner')->index()->after('type');
            $table->string('moderation_status', 20)->default('approved')->index()->after('tier');
            $table->foreignUuid('created_by_user_id')
                ->nullable()
                ->after('access_password_hash')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignUuid('moderated_by_user_id')
                ->nullable()
                ->after('created_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by_user_id');
            $table->text('moderation_note')->nullable()->after('moderated_at');

            $table->index(['created_by_user_id', 'created_at']);
            $table->index(['tier', 'moderation_status', 'is_active']);
        });

        DB::table('locations')->update([
            'tier' => 'partner',
            'moderation_status' => 'approved',
        ]);
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropForeign(['created_by_user_id']);
            $table->dropForeign(['moderated_by_user_id']);
            $table->dropIndex(['created_by_user_id', 'created_at']);
            $table->dropIndex(['tier', 'moderation_status', 'is_active']);
            $table->dropIndex(['tier']);
            $table->dropIndex(['moderation_status']);
            $table->dropColumn([
                'tier',
                'moderation_status',
                'created_by_user_id',
                'moderated_by_user_id',
                'moderated_at',
                'moderation_note',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('last_location_accuracy_meters');
        });
    }
};
