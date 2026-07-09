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
            <button class="btn" type="submit">Filtra</button>
            <a class="btn secondary" href="{{ route('admin.locations.index') }}">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Citta</th>
                    <th>Tipo</th>
                    <th>Coordinate</th>
                    <th>Raggio</th>
                    <th>Stato</th>
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
                        <td>{{ $location->latitude }}, {{ $location->longitude }}</td>
                        <td>{{ $location->geo_radius_meters }} m</td>
                        <td>
                            <span class="badge {{ $location->is_active ? '' : 'off' }}">
                                {{ $location->is_active ? 'Attivo' : 'Non attivo' }}
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
                        <td colspan="7">Nessun luogo trovato.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">
            {{ $locations->links() }}
        </div>
    </section>
@endsection
