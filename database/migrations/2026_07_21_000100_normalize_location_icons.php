<?php

use App\Support\LocationIcon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->legacyIcons() as $legacy => $ionicon) {
            DB::table('locations')->where('icon', $legacy)->update(['icon' => $ionicon]);
        }

        DB::table('locations')->whereNull('icon')->update(['icon' => LocationIcon::DEFAULT]);
    }

    public function down(): void
    {
        foreach (array_flip($this->legacyIcons()) as $ionicon => $legacy) {
            DB::table('locations')->where('icon', $ionicon)->update(['icon' => $legacy]);
        }
    }

    private function legacyIcons(): array
    {
        return [
            'metro' => 'subway-outline',
            'coffee' => 'cafe-outline',
            'landmark' => 'business-outline',
            'waves' => 'water-outline',
            'trees' => 'leaf-outline',
            'train' => 'train-outline',
            'map-pin' => LocationIcon::DEFAULT,
        ];
    }
};
