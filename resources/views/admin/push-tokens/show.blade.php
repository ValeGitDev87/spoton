@extends('layouts.admin', ['title' => 'Dettaglio token push'])

@section('content')
    <div class="page-head">
        <h1>Dettaglio token push</h1>
        <a class="btn secondary" href="{{ route('admin.push-tokens.index') }}">Torna ai token</a>
    </div>

    <section class="panel">
        <div style="overflow-x:auto;">
            <table>
                <tbody>
                    <tr><th>ID token</th><td><code>{{ $pushToken->id }}</code></td></tr>
                    <tr><th>Utente</th><td>{{ $pushToken->user?->display_name ?? 'Utente eliminato' }}</td></tr>
                    <tr><th>ID utente</th><td><code>{{ $pushToken->user_id }}</code></td></tr>
                    <tr><th>Email utente</th><td>{{ $pushToken->user?->email ?? '—' }}</td></tr>
                    <tr><th>Hash token (SHA-256)</th><td><code style="overflow-wrap:anywhere;">{{ $pushToken->token_hash }}</code></td></tr>
                    <tr><th>Dispositivo</th><td><code style="overflow-wrap:anywhere;">{{ $pushToken->device_id }}</code></td></tr>
                    <tr><th>Piattaforma</th><td>{{ strtoupper($pushToken->platform) }}</td></tr>
                    <tr><th>Versione app</th><td>{{ $pushToken->app_version ?: '—' }}</td></tr>
                    <tr><th>Lingua</th><td>{{ $pushToken->locale ?: '—' }}</td></tr>
                    <tr><th>Fuso orario dispositivo</th><td>{{ $pushToken->timezone ?: '—' }}</td></tr>
                    <tr><th>Stato</th><td><span class="badge {{ $pushToken->is_active ? 'status-active' : 'off' }}">{{ $pushToken->is_active ? 'Attivo' : 'Revocato' }}</span></td></tr>
                    <tr><th>Ultima sincronizzazione</th><td>{{ $pushToken->last_seen_at?->timezone('Europe/Rome')->format('d/m/Y H:i:s') ?? '—' }}</td></tr>
                    <tr><th>Revocato il</th><td>{{ $pushToken->revoked_at?->timezone('Europe/Rome')->format('d/m/Y H:i:s') ?? '—' }}</td></tr>
                    <tr><th>Creato il</th><td>{{ $pushToken->created_at?->timezone('Europe/Rome')->format('d/m/Y H:i:s') ?? '—' }}</td></tr>
                    <tr><th>Aggiornato il</th><td>{{ $pushToken->updated_at?->timezone('Europe/Rome')->format('d/m/Y H:i:s') ?? '—' }}</td></tr>
                </tbody>
            </table>
        </div>

        <details style="margin-top:20px;">
            <summary style="cursor:pointer;font-weight:700;">Mostra token Expo completo</summary>
            <p style="color:#667085;font-size:13px;">Usa questo valore solo per la diagnostica. Non condividerlo pubblicamente.</p>
            <code style="display:block;padding:12px;border-radius:8px;background:#f2f4f7;overflow-wrap:anywhere;user-select:all;">{{ $pushToken->token }}</code>
        </details>
        <small style="display:block;color:#667085;margin-top:16px;">Date e orari: Europe/Rome.</small>
    </section>
@endsection
