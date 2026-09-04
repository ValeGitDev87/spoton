@extends('layouts.admin', ['title' => 'Luoghi'])

@section('content')
    <div class="page-head">
        <h1>Luoghi</h1>
        <a class="btn" href="{{ route('admin.locations.create') }}">Nuovo luogo</a>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <section class="panel">
        <form class="toolbar" method="get" action="{{ route('admin.locations.index') }}">
            <div style="min-width:260px;">
                <label for="search">Cerca</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Nome o citta">
            </div>
            <div>
                <label for="tier">Gestione</label>
                <select id="tier" name="tier">
                    <option value="">Tutti</option>
                    <option value="partner" @selected(request('tier') === 'partner')>Partner</option>
                    <option value="community" @selected(request('tier') === 'community')>Community</option>
                </select>
            </div>
            <div>
                <label for="moderation_status">Moderazione</label>
                <select id="moderation_status" name="moderation_status">
                    <option value="">Tutti</option>
                    <option value="pending" @selected(request('moderation_status') === 'pending')>Da controllare</option>
                    <option value="approved" @selected(request('moderation_status') === 'approved')>Approvati</option>
                    <option value="rejected" @selected(request('moderation_status') === 'rejected')>Respinti</option>
                    <option value="suspended" @selected(request('moderation_status') === 'suspended')>Sospesi</option>
                </select>
            </div>
            <button class="btn" type="submit">Filtra</button>
            <a class="btn secondary" href="{{ route('admin.locations.index') }}">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Citta</th>
                    <th>Tipo</th>
                    <th>Gestione</th>
                    <th>Moderazione</th>
                    <th>Creatore</th>
                    <th>Icona</th>
                    <th>Coordinate</th>
                    <th>Raggio</th>
                    <th>Stato</th>
                    <th>Accesso</th>
                    <th style="text-align:right;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($locations as $location)
                    <tr>
                        <td>
                            <strong>{{ $location->name }}</strong>
                            @if ($location->short && $location->short !== $location->name)
                                <div style="color:#667085;font-size:12px;">{{ $location->short }}</div>
                            @endif
                        </td>
                        <td>{{ $location->city }}</td>
                        <td>{{ $location->type }}</td>
                        <td>
                            <span class="badge {{ $location->tier === 'partner' ? '' : 'off' }}">
                                {{ $location->tier === 'partner' ? 'Partner' : 'Community' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $moderationLabels = [
                                    'pending' => 'Da controllare',
                                    'approved' => 'Approvato',
                                    'rejected' => 'Respinto',
                                    'suspended' => 'Sospeso',
                                ];
                            @endphp
                            <span class="badge {{ $location->moderation_status === 'approved' ? '' : 'status-pending' }}">
                                {{ $moderationLabels[$location->moderation_status] ?? $location->moderation_status }}
                            </span>
                        </td>
                        <td>{{ $location->creator?->display_name ?? 'Admin' }}</td>
                        <td><ion-icon class="table-icon" name="{{ $location->icon ?: 'location-outline' }}"></ion-icon></td>
                        <td>{{ $location->latitude }}, {{ $location->longitude }}</td>
                        <td>{{ $location->geo_radius_meters }} m</td>
                        <td>
                            <span class="badge {{ $location->is_active ? '' : 'off' }}">
                                {{ $location->is_active ? 'Attivo' : 'Non attivo' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $location->is_locked ? 'status-pending' : 'off' }}">
                                {{ $location->is_locked ? 'Riservato' : 'Libero' }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.locations.edit', $location) }}">Modifica</a>
                                <form method="post" action="{{ route('admin.locations.destroy', $location) }}" onsubmit="return confirm('Eliminare questo luogo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit">Elimina</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">Nessun luogo trovato.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">
            {{ $locations->links('admin._pagination') }}
        </div>
    </section>
@endsection
