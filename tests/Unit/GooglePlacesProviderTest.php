<?php

namespace Tests\Unit;

use App\Services\Places\GooglePlacesProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GooglePlacesProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('spoton.smart_location.google_api_key', 'test-key');
        config()->set('spoton.smart_location.provider_timeout_seconds', 2);
    }

    public function test_it_maps_only_the_required_nearby_place_data(): void
    {
        Http::fake([
            'https://places.googleapis.com/v1/places:searchNearby' => Http::response([
                'places' => [[
                    'id' => 'google-place-1',
                    'displayName' => ['text' => 'Bar Sole'],
                    'location' => ['latitude' => 40.8447, 'longitude' => 14.2306],
                    'primaryType' => 'bar',
                    'formattedAddress' => 'Via Roma 1, Napoli',
                    'addressComponents' => [[
                        'longText' => 'Napoli',
                        'types' => ['locality'],
                    ]],
                ]],
            ]),
        ]);

        $provider = new GooglePlacesProvider;
        $candidates = $provider->nearby(40.8447, 14.2306, 150, 5);

        $this->assertSame('google-place-1', $candidates[0]['provider_place_id']);
        $this->assertSame('Bar Sole', $candidates[0]['name']);
        $this->assertSame('Napoli', $candidates[0]['city']);
        $this->assertSame('bar', $candidates[0]['type']);
        $this->assertFalse($provider->cacheable());

        Http::assertSent(fn ($request): bool => $request->hasHeader('X-Goog-Api-Key', 'test-key')
            && str_contains($request->header('X-Goog-FieldMask')[0], 'places.id')
            && $request['maxResultCount'] === 5);
    }

    public function test_it_falls_back_to_a_normalized_area_without_a_street_number(): void
    {
        Http::fake([
            'https://places.googleapis.com/v1/places:searchNearby' => Http::response(['places' => []]),
            'https://maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'place_id' => 'route-1',
                    'formatted_address' => 'Via Toledo, Napoli',
                    'address_components' => [
                        ['long_name' => 'Via Toledo', 'types' => ['route']],
                        ['long_name' => 'Napoli', 'types' => ['locality']],
                    ],
                ]],
            ]),
        ]);

        $candidate = (new GooglePlacesProvider)->nearby(40.8447, 14.2306, 150, 5)[0];

        $this->assertSame('Via Toledo', $candidate['name']);
        $this->assertSame('area', $candidate['location_kind']);
        $this->assertSame('altro', $candidate['type']);
    }
}
