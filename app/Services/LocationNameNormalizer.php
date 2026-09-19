<?php

namespace App\Services;

use Illuminate\Support\Str;

class LocationNameNormalizer
{
    public function displayName(string $name, string $kind = 'poi'): string
    {
        $name = Str::squish($name);

        if ($kind === 'area') {
            $name = preg_replace('/(?:,?\s+(?:civico\s+)?\d+[a-z]?(?:[\/-]\d+[a-z]?)?)$/iu', '', $name) ?? $name;
        }

        return Str::squish($name);
    }

    public function normalize(string $name, string $kind = 'poi'): string
    {
        return Str::of($this->displayName($name, $kind))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }
}
