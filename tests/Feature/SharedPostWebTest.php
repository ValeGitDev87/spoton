<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedPostWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_post_has_public_social_page_without_private_location_data(): void
    {
        $post = $this->createPost();

        $this
            ->get("/p/{$post->id}")
            ->assertOk()
            ->assertSee('Un incontro raccontato da Luca')
            ->assertSee('Ti ho visto davanti alla metro.')
            ->assertSee('Metro Test, Napoli')
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('Vedi la bacheca di questo luogo')
            ->assertSee("/l/{$post->location_id}", false)
            ->assertSee("spoton://p/{$post->id}", false)
            ->assertDontSee('40.8319')
            ->assertDontSee('14.2193')
            ->assertDontSee('Qual era il codice?');
    }

    public function test_audio_post_generates_cached_social_card_and_audio_metadata(): void
    {
        Storage::fake('public');
        $font = PHP_OS_FAMILY === 'Darwin'
            ? '/System/Library/Fonts/Supplemental/Arial.ttf'
            : '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $fontBold = PHP_OS_FAMILY === 'Darwin'
            ? '/System/Library/Fonts/Supplemental/Arial Bold.ttf'
            : '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

        if (! function_exists('imagecreatetruecolor') || ! is_file($font) || ! is_file($fontBold)) {
            $this->markTestSkipped('GD o font TrueType non disponibili.');
        }

        config()->set('spoton.social_card.disk', 'public');
        config()->set('spoton.social_card.font_path', $font);
        config()->set('spoton.social_card.font_bold_path', $fontBold);
        $post = $this->createPost([
            'audio_disk' => 'public',
            'audio_path' => 'post-audios/social.m4a',
            'audio_url' => '/storage/post-audios/social.m4a',
            'audio_mime' => 'audio/mp4',
            'audio_duration_seconds' => 8,
        ]);

        $this->get("/p/{$post->id}")
            ->assertOk()
            ->assertSee('property="og:audio"', false)
            ->assertSee('share-cards', false);

        $this->assertCount(1, Storage::disk('public')->files('share-cards'));
    }

    public function test_photo_post_shows_photo_on_public_page_and_in_social_card(): void
    {
        Storage::fake('public');
        $font = PHP_OS_FAMILY === 'Darwin'
            ? '/System/Library/Fonts/Supplemental/Arial.ttf'
            : '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';
        $fontBold = PHP_OS_FAMILY === 'Darwin'
            ? '/System/Library/Fonts/Supplemental/Arial Bold.ttf'
            : '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf';

        if (! function_exists('imagecreatetruecolor') || ! is_file($font) || ! is_file($fontBold)) {
            $this->markTestSkipped('GD o font TrueType non disponibili.');
        }

        config()->set('spoton.social_card.font_path', $font);
        config()->set('spoton.social_card.font_bold_path', $fontBold);
        $photo = imagecreatetruecolor(400, 300);
        imagefill($photo, 0, 0, imagecolorallocate($photo, 230, 20, 100));
        ob_start();
        imagejpeg($photo);
        $bytes = ob_get_clean();
        imagedestroy($photo);
        Storage::disk('public')->put('post-images/test.jpg', $bytes);

        $post = $this->createPost([
            'image_disk' => 'public',
            'image_path' => 'post-images/test.jpg',
            'image_url' => '/storage/post-images/test.jpg',
            'image_mime' => 'image/jpeg',
        ]);

        $this->get("/p/{$post->id}")
            ->assertOk()
            ->assertSee('class="post-image"', false)
            ->assertSee('src="'.asset('storage/post-images/test.jpg').'"', false)
            ->assertSee('property="og:image"', false);

        $cards = Storage::disk('public')->files('share-cards');
        $this->assertCount(1, $cards);
        $this->assertStringContainsString('-photo-v2-', $cards[0]);
        $card = imagecreatefromstring(Storage::disk('public')->get($cards[0]));
        $color = imagecolorsforindex($card, imagecolorat($card, 900, 250));
        imagedestroy($card);
        $this->assertGreaterThan(200, $color['red']);
        $this->assertLessThan(50, $color['green']);
        $this->assertGreaterThan(70, $color['blue']);
    }

    public function test_ghost_post_never_exposes_real_author(): void
    {
        $post = $this->createPost([
            'is_anonymous' => true,
            'text' => 'Un ricordo anonimo.',
        ]);

        $this
            ->get("/p/{$post->id}")
            ->assertOk()
            ->assertSee('Ghost - profilo nascosto')
            ->assertSee('Un incontro raccontato da Ghost')
            ->assertDontSee('Luca Segreto');
    }

    public function test_mobile_visitors_get_the_verified_store_fallback(): void
    {
        $post = $this->createPost();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)')
            ->get("/p/{$post->id}")
            ->assertOk()
            ->assertSee('https://apps.apple.com/it/app/spoton/id6807057516', false)
            ->assertSee("spoton://p/{$post->id}", false)
            ->assertSee('visibilitychange');

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 15; Pixel 8)')
            ->get("/p/{$post->id}")
            ->assertOk()
            ->assertSee('https://play.google.com/store/apps/details?id=it.spotonapp.app', false)
            ->assertSee('visibilitychange');

        $this->withHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15) Mobile/15E148 Safari/604.1')
            ->get("/p/{$post->id}")
            ->assertOk()
            ->assertSee('https://apps.apple.com/it/app/spoton/id6807057516', false);
    }

    public function test_crawlers_and_desktop_keep_the_public_page_and_open_graph(): void
    {
        $post = $this->createPost();

        foreach ([
            'facebookexternalhit/1.1',
            'WhatsApp/2.24.1 iPhone',
            'Googlebot/2.1 Android',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0)',
        ] as $userAgent) {
            $this->withHeader('User-Agent', $userAgent)
                ->get("/p/{$post->id}")
                ->assertOk()
                ->assertSee('property="og:image"', false)
                ->assertSee('Ti ho visto davanti alla metro.')
                ->assertDontSee('window.location.href');
        }
    }

    public function test_expired_or_removed_post_does_not_expose_content(): void
    {
        $post = $this->createPost([
            'expires_at' => now()->subMinute(),
            'text' => 'Contenuto da non mostrare.',
            'image_url' => '/storage/post-images/private.jpg',
        ]);

        $this
            ->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X)')
            ->get("/p/{$post->id}")
            ->assertOk()
            ->assertSee('Annuncio non disponibile')
            ->assertDontSee('Contenuto da non mostrare.')
            ->assertDontSee('post-images/private.jpg')
            ->assertDontSee('window.location.href');
    }

    public function test_app_association_files_are_emitted_only_when_configured(): void
    {
        $this->get('/.well-known/apple-app-site-association')->assertNotFound();
        $this->get('/.well-known/assetlinks.json')->assertNotFound();

        config()->set('spoton.app_links.apple_team_id', 'ABCDE12345');
        config()->set('spoton.app_links.android_sha256_cert_fingerprints', ['AA:BB:CC']);

        $this
            ->get('/.well-known/apple-app-site-association')
            ->assertOk()
            ->assertJsonPath('applinks.details.0.appID', 'ABCDE12345.it.spotonapp.app')
            ->assertJsonPath('applinks.details.0.paths.0', '/p/*');
        $this
            ->get('/.well-known/apple-app-site-association')
            ->assertJsonPath('applinks.details.0.paths.1', '/l/*');

        $this
            ->get('/.well-known/assetlinks.json')
            ->assertOk()
            ->assertJsonPath('0.target.package_name', 'it.spotonapp.app')
            ->assertJsonPath('0.target.sha256_cert_fingerprints.0', 'AA:BB:CC');
    }

    private function createPost(array $overrides = []): Post
    {
        $author = User::factory()->create(['display_name' => 'Luca Segreto']);
        $location = Location::query()->create([
            'name' => 'Metro Test',
            'short' => 'Metro Test',
            'city' => 'Napoli',
            'type' => 'metro',
            'latitude' => 40.8319000,
            'longitude' => 14.2193000,
            'geo_radius_meters' => 100,
            'is_active' => true,
        ]);

        return Post::query()->create([
            'author_id' => $author->id,
            'location_id' => $location->id,
            'text' => 'Ti ho visto davanti alla metro.',
            'sighting_date' => now()->toDateString(),
            'is_anonymous' => false,
            'secret_question' => 'Qual era il codice?',
            'secret_answer_hash' => 'hash-non-pubblico',
            'expires_at' => now()->addDay(),
            'status' => 'active',
            ...$overrides,
        ]);
    }
}
