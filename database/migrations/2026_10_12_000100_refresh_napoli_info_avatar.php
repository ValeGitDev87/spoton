<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'info-napoli@spotonapp.cloud')
            ->where('is_system', true)
            ->update(['avatar_url' => '/images/share/spoton-symbol-20261012.png']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'info-napoli@spotonapp.cloud')
            ->where('is_system', true)
            ->where('avatar_url', '/images/share/spoton-symbol-20261012.png')
            ->update(['avatar_url' => '/images/share/spoton-symbol.png']);
    }
};
