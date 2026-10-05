<?php

namespace App\Console\Commands;

use App\Models\Location;
use App\Models\Post;
use App\Models\User;
use App\Services\NapoliInfo\SourceProvider;
use App\Support\PostCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SyncNapoliInfoCommand extends Command
{
    protected $signature = 'spoton:sync-napoli-info {--dry-run : Legge la fonte senza scrivere nel database}';
    protected $description = 'Importa avvisi ufficiali sulla viabilità di Napoli';

    public function handle(SourceProvider $provider): int
    {
        $started = microtime(true);
        $stats = ['provider' => $provider->name(), 'fetched' => 0, 'created' => 0, 'updated' => 0, 'archived' => 0, 'skipped' => 0, 'errors' => 0];
        try {
            if (! config('spoton.napoli_info.enabled', false) && ! $this->option('dry-run')) {
                $this->info('Info Napoli disabilitato.');
                return self::SUCCESS;
            }
            $location = Location::query()->find(config('spoton.napoli_info.location_id'));
            if (! $location || ! $location->isPubliclyVisible() || $location->is_locked
                || $location->moderation_status !== Location::MODERATION_APPROVED
                || mb_strtolower(trim((string) $location->city)) !== 'napoli') {
                throw new \RuntimeException('Configurare un luogo approvato di Napoli, pubblico, attivo e non riservato.');
            }
            $events = $provider->fetch();
            $stats['fetched'] = count($events);
            if ($this->option('dry-run')) {
                foreach (array_slice($events, 0, 1) as $event) {
                    $this->line(json_encode([
                        'title' => $event['title'],
                        'source_url' => $event['source_url'],
                        'external_id' => $event['external_id'],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
                return self::SUCCESS;
            }
            $author = $this->officialAccount();
            foreach (array_slice($events, 0, 1) as $event) {
                try {
                    DB::transaction(function () use ($event, $author, $location, $provider, &$stats): void {
                        $existing = DB::table('imported_napoli_events')
                            ->where('source', $provider->id())->where('external_id', $event['external_id'])
                            ->lockForUpdate()->first();
                        $post = $existing ? Post::query()->findOrFail($existing->post_id) : null;
                        if ($post && in_array($post->status, ['removed', 'flagged'], true)) {
                            $stats['skipped']++;
                            return;
                        }
                        $text = '🚧 Tangenziale di Napoli — '.$event['title']."\n\nDettagli e orari aggiornati nella fonte ufficiale. Fonte: Tangenziale di Napoli.";
                        $values = [
                            'text' => mb_substr($text, 0, 2000),
                            'source_name' => $provider->name(),
                            'source_url' => $event['source_url'],
                            'is_persistent_info' => true,
                            'expires_at' => null,
                            'status' => 'active',
                        ];
                        if ($existing) {
                            if ($existing->raw_hash === $event['raw_hash'] && $post->isActive()
                                && $post->is_persistent_info && $post->expires_at === null) {
                                $stats['skipped']++;
                            } else {
                                $post->update($values);
                                DB::table('imported_napoli_events')->where('id', $existing->id)->update(['raw_hash' => $event['raw_hash'], 'updated_at' => now()]);
                                $stats['updated']++;
                            }
                        } else {
                            $post = Post::query()->create($values + [
                                'author_id' => $author->id,
                                'location_id' => $location->id,
                                'category' => PostCategory::WEATHER_TRANSPORT,
                                'sighting_date' => today(),
                                'is_anonymous' => false,
                            ]);
                            DB::table('imported_napoli_events')->insert([
                                'source' => $provider->id(), 'external_id' => $event['external_id'],
                                'post_id' => $post->id, 'raw_hash' => $event['raw_hash'],
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                            $stats['created']++;
                        }
                        Post::query()->where('author_id', $author->id)
                            ->where('source_name', $provider->name())
                            ->whereKeyNot($post->id)
                            ->where('status', 'active')
                            ->get()
                            ->each(function (Post $previous) use (&$stats): void {
                                $previous->update(['status' => 'expired', 'expires_at' => now()]);
                                $stats['archived']++;
                            });
                    });
                } catch (Throwable $e) {
                    $stats['errors']++;
                    Log::warning('Info Napoli: evento saltato', ['external_id' => $event['external_id'], 'error' => $e->getMessage()]);
                }
            }
        } catch (Throwable $e) {
            $stats['errors']++;
            Log::warning('Info Napoli: sincronizzazione non riuscita', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());
        } finally {
            Log::info('Info Napoli: sincronizzazione', $stats + ['duration_ms' => (int) ((microtime(true) - $started) * 1000)]);
        }
        $this->info(json_encode($stats));
        return $stats['errors'] ? self::FAILURE : self::SUCCESS;
    }

    private function officialAccount(): User
    {
        $email = 'info-napoli@spotonapp.cloud';
        $user = User::query()->firstOrCreate(['email' => $email], [
            'display_name' => 'SpotOn Info Napoli',
            'password' => Str::random(96),
            'auth_provider' => 'system',
            'is_system' => true,
            'avatar_url' => '/images/share/spoton-symbol.png',
            'email_verified_at' => now(),
        ]);
        if (! $user->is_system || $user->auth_provider !== 'system') {
            throw new \RuntimeException('Indirizzo account ufficiale già in uso.');
        }
        if (! $user->hasVerifiedEmail() || $user->avatar_url !== '/images/share/spoton-symbol.png'
            || $user->display_name !== 'SpotOn Info Napoli') {
            $user->forceFill([
                'email_verified_at' => $user->email_verified_at ?? now(),
                'avatar_url' => '/images/share/spoton-symbol.png',
                'display_name' => 'SpotOn Info Napoli',
            ])->save();
        }
        return $user;
    }
}
