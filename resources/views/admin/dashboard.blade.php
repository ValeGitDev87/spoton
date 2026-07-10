@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
    <div class="page-head">
        <h1>Dashboard</h1>
    </div>

    <div class="stats-grid">
        <section class="stat-card">
            <span>Utenti</span>
            <strong>{{ $stats['users'] }}</strong>
            <small>{{ $stats['admins'] }} admin</small>
        </section>
        <section class="stat-card">
            <span>Luoghi</span>
            <strong>{{ $stats['locations'] }}</strong>
            <small>{{ $stats['active_locations'] }} attivi</small>
        </section>
        <section class="stat-card">
            <span>Post totali</span>
            <strong>{{ $stats['posts'] }}</strong>
            <small>{{ $stats['active_posts'] }} attivi</small>
        </section>
        <section class="stat-card">
            <span>Post rimossi</span>
            <strong>{{ $stats['removed_posts'] }}</strong>
            <small>{{ $stats['expired_posts'] }} scaduti</small>
        </section>
    </div>

    <section class="panel" style="margin-top:18px;">
        <div class="page-head" style="margin-bottom:12px;">
            <h2>Ultimi post</h2>
            <a class="btn secondary" href="{{ route('admin.posts.index') }}">Vedi tutti</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Testo</th>
                    <th>Autore</th>
                    <th>Luogo</th>
                    <th>Stato</th>
                    <th>Creato</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($latestPosts as $post)
                    <tr>
                        <td>{{ \Illuminate\Support\Str::limit($post->text, 80) }}</td>
                        <td>{{ $post->author?->display_name }}</td>
                        <td>{{ $post->location?->name }}</td>
                        <td><span class="badge status-{{ $post->status }}">{{ $post->status }}</span></td>
                        <td>{{ $post->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Nessun post presente.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
