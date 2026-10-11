@extends('layouts.admin', ['title' => 'Token push'])

@section('content')
    <div class="page-head">
        <h1>Token push</h1>
    </div>

    <div class="stats-grid">
        <section class="stat-card">
            <span>Token salvati</span>
            <strong>{{ $stats['total'] }}</strong>
        </section>
        <section class="stat-card">
            <span>Attivi</span>
            <strong>{{ $stats['active'] }}</strong>
        </section>
        <section class="stat-card">
            <span>iOS attivi</span>
            <strong>{{ $stats['ios'] }}</strong>
        </section>
        <section class="stat-card">
            <span>Android attivi</span>
            <strong>{{ $stats['android'] }}</strong>
        </section>
    </div>

    <section class="panel" style="margin-top:18px;">
        <form class="toolbar" method="get" action="{{ route('admin.push-tokens.index') }}">
            <div style="min-width:260px;">
                <label for="search">Cerca</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Utente, email, dispositivo, ID o hash">
            </div>
            <div style="min-width:150px;">
                <label for="platform">Piattaforma</label>
                <select id="platform" name="platform">
                    <option value="">Tutte</option>
                    <option value="ios" @selected($platform === 'ios')>iOS</option>
                    <option value="android" @selected($platform === 'android')>Android</option>
                </select>
            </div>
            <div style="min-width:150px;">
                <label for="status">Stato</label>
                <select id="status" name="status">
                    <option value="">Tutti</option>
                    <option value="active" @selected($status === 'active')>Attivi</option>
                    <option value="revoked" @selected($status === 'revoked')>Revocati</option>
                </select>
            </div>
            <button class="btn" type="submit">Filtra</button>
            <a class="btn secondary" href="{{ route('admin.push-tokens.index') }}">Reset</a>
        </form>

        <div style="overflow-x:auto;">
            <table style="min-width:1000px;">
                <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Token</th>
                        <th>Piattaforma</th>
                        <th>Versione app</th>
                        <th>Dispositivo</th>
                        <th>Stato</th>
                        <th>Ultima sincronizzazione</th>
                        <th>Revocato</th>
                        <th>Dettagli</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tokens as $token)
                        <tr>
                            <td>
                                {{ $token->user?->display_name ?? 'Utente eliminato' }}
                                @if ($token->user?->email)
                                    <small style="display:block;color:#667085;">{{ $token->user->email }}</small>
                                @endif
                            </td>
                            <td><code>{{ substr($token->token_hash, 0, 12) }}…</code></td>
                            <td>{{ strtoupper($token->platform) }}</td>
                            <td>{{ $token->app_version ?: '—' }}</td>
                            <td><code>{{ $token->device_id }}</code></td>
                            <td><span class="badge {{ $token->is_active ? 'status-active' : 'off' }}">{{ $token->is_active ? 'Attivo' : 'Revocato' }}</span></td>
                            <td>{{ $token->last_seen_at?->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $token->revoked_at?->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</td>
                            <td><a class="btn secondary" href="{{ route('admin.push-tokens.show', $token) }}">Apri</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Nessun token push trovato.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <small style="display:block;color:#667085;margin-top:8px;">Date e orari: Europe/Rome. Il token completo è visibile solo nella pagina di dettaglio.</small>

        <div class="pagination">
            {{ $tokens->links('admin._pagination') }}
        </div>
    </section>
@endsection
