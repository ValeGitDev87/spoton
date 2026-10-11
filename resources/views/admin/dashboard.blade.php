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
            <span>Segnalazioni</span>
            <strong>{{ $stats['pending_reports'] }}</strong>
            <small><a href="{{ route('admin.reports.index', ['status' => 'pending']) }}">Da revisionare</a></small>
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

    <section class="panel" id="push-tokens" style="margin-top:18px;">
        <div class="page-head" style="margin-bottom:12px;">
            <div>
                <h2 style="margin-bottom:4px;">Token push salvati</h2>
                <small style="color:#667085;">
                    {{ $pushTokenStats['total'] }} totali · {{ $pushTokenStats['active'] }} attivi
                    ({{ $pushTokenStats['ios'] }} iOS, {{ $pushTokenStats['android'] }} Android)
                </small>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table style="min-width:1000px;">
                <thead>
                    <tr>
                        <th>Identificativo token</th>
                        <th>Utente</th>
                        <th>Piattaforma</th>
                        <th>Versione app</th>
                        <th>Dispositivo</th>
                        <th>Stato</th>
                        <th>Ultima sincronizzazione</th>
                        <th>Creato</th>
                        <th>Revocato</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pushTokens as $pushToken)
                        <tr>
                            <td><code>{{ substr($pushToken->token_hash, 0, 12) }}…</code></td>
                            <td>
                                {{ $pushToken->user?->display_name ?? 'Utente eliminato' }}
                                @if ($pushToken->user?->email)
                                    <small style="display:block;color:#667085;">{{ $pushToken->user->email }}</small>
                                @endif
                            </td>
                            <td>{{ strtoupper($pushToken->platform) }}</td>
                            <td>{{ $pushToken->app_version ?: '—' }}</td>
                            <td>
                                <code>{{ $pushToken->device_id }}</code>
                                @if ($pushToken->locale || $pushToken->timezone)
                                    <small style="display:block;color:#667085;">
                                        {{ implode(' · ', array_filter([$pushToken->locale, $pushToken->timezone])) }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $pushToken->is_active ? 'status-active' : 'off' }}">
                                    {{ $pushToken->is_active ? 'Attivo' : 'Revocato' }}
                                </span>
                            </td>
                            <td>{{ $pushToken->last_seen_at?->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $pushToken->created_at?->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $pushToken->revoked_at?->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">Nessun token push salvato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <small style="display:block;color:#667085;margin-top:8px;">Date e orari: Europe/Rome. Il token completo non viene mostrato.</small>

        <div class="pagination">
            {{ $pushTokens->links('admin._pagination') }}
        </div>
    </section>
@endsection
