<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\Chat;
use App\Models\Comment;
use App\Models\Favorite;
use App\Models\Like;
use App\Models\Location;
use App\Models\Message;
use App\Models\Post;
use App\Models\PostIWasThere;
use App\Models\PresenceSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call(DemoUsersSeeder::class);

            $admin = User::query()->where('email', 'admin@spoton.local')->firstOrFail();
            $testUser = User::query()->where('email', 'luca@test.it')->firstOrFail();
            $users = User::query()
                ->whereIn('email', [
                    'admin@spoton.local',
                    ...array_column(DemoUsersSeeder::USERS, 'email'),
                ])
                ->get();

            foreach ($users->where('is_admin', false) as $user) {
                $targets = $users
                    ->where('is_admin', false)
                    ->reject(fn (User $target) => $target->id === $user->id)
                    ->pluck('display_name')
                    ->take(3);

                foreach ($targets as $targetName) {
                    Favorite::query()->firstOrCreate([
                        'owner_id' => $user->id,
                        'target_name' => $targetName,
                    ]);
                }
            }

            $locations = collect([
                ['Metro Mergellina', 'Napoli', 'metro', 40.8319000, 14.2193000, 'subway-outline'],
                ['Bar Nilo', 'Napoli', 'bar', 40.8495000, 14.2569000, 'cafe-outline'],
                ['Piazza Plebiscito', 'Napoli', 'piazza', 40.8359000, 14.2488000, 'business-outline'],
                ['Lungomare Caracciolo', 'Napoli', 'lungomare', 40.8297000, 14.2284000, 'water-outline'],
                ['Villa Comunale', 'Napoli', 'parco', 40.8331000, 14.2294000, 'leaf-outline'],
                ['Stazione Salerno', 'Salerno', 'altro', 40.6759000, 14.7720000, 'train-outline'],
            ])->map(fn (array $location) => Location::query()->updateOrCreate(
                ['name' => $location[0], 'city' => $location[1]],
                [
                    'short' => $location[0],
                    'type' => $location[2],
                    'latitude' => $location[3],
                    'longitude' => $location[4],
                    'geo_radius_meters' => 300,
                    'icon' => $location[5],
                    'is_active' => true,
                ],
            ));

            $texts = [
                'Ti ho vista leggere vicino alla metro, avevi una giacca chiara e un sorriso tranquillo.',
                'Ci siamo incrociati al bancone, tu ordinavi un caffe e io non ho avuto il coraggio di salutarti.',
                'Eri seduto sulle scale, guardavi il telefono e ridevi da solo. Mi hai migliorato la giornata.',
                'Sul lungomare camminavi con le cuffie, stessa canzone che avevo in testa io.',
                'Alla fermata hai aiutato una signora con la borsa. Bello vedere gentilezza cosi.',
                'Avevi un libro blu e sei scesa di fretta. Se ti riconosci, scrivimi.',
            ];

            $musiche = [
                'Quel ritornello che faceva la la la',
                'Una canzone lenta sentita al bar',
                'Il motivetto nelle cuffie rosse',
                'Una frase che parlava di mare',
                null,
                'La musica del locale in sottofondo',
            ];

            $posts = collect();

            for ($i = 0; $i < 18; $i++) {
                $author = $users->where('is_admin', false)->random();
                $location = $locations->random();

                $posts->push(Post::query()->create([
                    'author_id' => $author->id,
                    'location_id' => $location->id,
                    'text' => $texts[$i % count($texts)],
                    'musica' => $musiche[$i % count($musiche)],
                    'song_quote' => $musiche[$i % count($musiche)],
                    'sighting_date' => now()->subDays(random_int(0, 2))->toDateString(),
                    'is_anonymous' => $i % 4 === 0,
                    'secret_question' => $i % 4 === 0 ? 'Che dettaglio ricordi di me?' : null,
                    'secret_answer_hash' => $i % 4 === 0 ? Hash::make('sorriso') : null,
                    'expires_at' => $i < 14 ? now()->addHours(random_int(1, 24)) : now()->subHours(random_int(1, 5)),
                    'status' => $i < 14 ? 'active' : 'expired',
                ]));
            }

            foreach ($posts as $post) {
                $eligibleUsers = $users->reject(fn (User $user) => $user->id === $post->author_id)->values();

                $likers = $eligibleUsers->random(min(3, $eligibleUsers->count()));

                foreach (collect($likers) as $liker) {
                    Like::query()->firstOrCreate([
                        'post_id' => $post->id,
                        'user_id' => $liker->id,
                    ]);
                }

                $ioCeroUsers = $eligibleUsers->random(min(2, $eligibleUsers->count()));

                foreach (collect($ioCeroUsers) as $ioCeroUser) {
                    PostIWasThere::query()->firstOrCreate([
                        'post_id' => $post->id,
                        'user_id' => $ioCeroUser->id,
                    ]);
                }

                $post->update([
                    'like_count' => $post->likes()->count(),
                    'io_cero_count' => $post->iWasThere()->count(),
                    'spot_on_count' => $post->iWasThere()->count(),
                ]);
            }

            foreach ($posts->take(10) as $post) {
                $commentAuthor = $users
                    ->where('is_admin', false)
                    ->reject(fn (User $user) => $user->id === $post->author_id)
                    ->first();
                $taggedUser = $users
                    ->where('is_admin', false)
                    ->reject(fn (User $user) => $user->id === $commentAuthor->id)
                    ->first();

                Favorite::query()->firstOrCreate([
                    'owner_id' => $commentAuthor->id,
                    'target_name' => $taggedUser->display_name,
                ]);

                Comment::query()->create([
                    'post_id' => $post->id,
                    'author_id' => $commentAuthor->id,
                    'tagged_user_id' => $taggedUser->id,
                    'text' => 'Secondo me era proprio @'.strtok($taggedUser->display_name, ' '),
                ]);

                $post->update(['comment_count' => $post->comments()->count()]);
            }

            $challengePost = $posts
                ->first(fn (Post $post) => ! $post->secret_answer_hash && $post->author_id !== $testUser->id);
            $challengeTarget = $users
                ->where('is_admin', false)
                ->first(fn (User $user) => $user->id === $challengePost->author_id);

            Challenge::query()->create([
                'post_id' => $challengePost->id,
                'origin' => Challenge::ORIGIN_INVERTED,
                'challenger_id' => $testUser->id,
                'target_type' => Challenge::TARGET_POST_AUTHOR,
                'target_user_id' => $challengeTarget->id,
                'question' => 'Che dettaglio ricordi della scena?',
                'answer_hash' => Hash::make('sorriso'),
                'status' => Challenge::STATUS_PENDING,
            ]);

            $classicPost = $posts->first(fn (Post $post) => (bool) $post->secret_answer_hash);
            $counterUser = $users
                ->where('is_admin', false)
                ->first(fn (User $user) => $user->id !== $classicPost->author_id);

            Challenge::query()->create([
                'post_id' => $classicPost->id,
                'origin' => Challenge::ORIGIN_CLASSIC,
                'challenger_id' => $classicPost->author_id,
                'target_type' => Challenge::TARGET_POST_AUTHOR,
                'target_user_id' => $counterUser->id,
                'question' => $classicPost->secret_question,
                'answer_hash' => $classicPost->secret_answer_hash,
                'status' => Challenge::STATUS_COUNTER_PENDING,
                'counter_text' => 'Non ricordo la parola esatta, ma ricordo il sorriso e la giacca chiara.',
                'counter_proposed_by' => $counterUser->id,
            ]);

            foreach ($users->where('is_admin', false)->take(4) as $user) {
                $location = $locations->random();
                PresenceSession::query()->create([
                    'user_id' => $user->id,
                    'location_id' => $location->id,
                    'started_at' => now()->subMinutes(random_int(1, 4)),
                    'last_ping_at' => now()->subMinutes(random_int(0, 3)),
                ]);

                $user->update([
                    'last_known_latitude' => $location->latitude,
                    'last_known_longitude' => $location->longitude,
                    'last_location_update' => now(),
                ]);
            }

            $chatUsers = $users->where('is_admin', false)->values();

            for ($i = 0; $i < 4; $i++) {
                $first = $chatUsers[$i];
                $second = $chatUsers[$i + 1];
                [$one, $two] = Chat::sortedPair($first->id, $second->id);

                $chat = Chat::query()->firstOrCreate([
                    'user_one_id' => $one,
                    'user_two_id' => $two,
                ]);

                foreach ([
                    [$first, 'Ciao, penso di averti vista ieri.'],
                    [$second, 'Forse si, dove eri?'],
                    [$first, 'Vicino al lungomare, verso il tramonto.'],
                ] as [$sender, $message]) {
                    Message::query()->create([
                        'chat_id' => $chat->id,
                        'sender_id' => $sender->id,
                        'text' => $message,
                        'sent_at' => now()->subMinutes(random_int(1, 60)),
                    ]);
                }
            }
        });
    }
}
