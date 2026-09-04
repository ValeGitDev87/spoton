@extends('layouts.admin', ['title' => 'Media scaduti'])

@section('content')
    <div class="page-head">
        <div>
            <h1>Media scaduti</h1>
            <p style="color:#667085;margin:7px 0 0;">Video generati non piu utilizzabili e pronti per la pulizia.</p>
        </div>
        @if ($summary['records'] > 0)
            <form method="post" action="{{ route('admin.expired-media.purge') }}" onsubmit="return confirm('Stai per eliminare {{ $summary['files'] }} file per un totale di {{ $summary['bytes_human'] }}. Continuare?')">
                @csrf
                <button class="btn danger" type="submit">Elimina video e risorse scadute</button>
            </form>
        @endif
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="stats-grid" style="margin-bottom:18px;">
        <section class="stat-card">
            <span>Record scaduti</span>
            <strong>{{ $summary['records'] }}</strong>
        </section>
        <section class="stat-card">
            <span>File presenti</span>
            <strong>{{ $summary['files'] }}</strong>
        </section>
        <section class="stat-card">
            <span>Spazio occupato</span>
            <strong style="font-size:24px;">{{ $summary['bytes_human'] }}</strong>
        </section>
    </div>

    <section class="panel">
        <table>
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Luogo</th>
                    <th>Generato</th>
                    <th>Scadenza</th>
                    <th>Dimensione</th>
                    <th>Stato file</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ \Illuminate\Support\Str::limit($item->post?->text ?: 'Post non disponibile', 70) }}</td>
                        <td>{{ $item->post?->location?->name ?: '-' }}</td>
                        <td>{{ $item->generated_at?->format('d/m/Y H:i') ?: '-' }}</td>
                        <td>{{ $item->expires_at?->format('d/m/Y H:i') ?: '-' }}</td>
                        <td>{{ $item->size_human }}</td>
                        <td><span class="badge {{ $item->file_status_label === 'Presente' ? '' : 'off' }}">{{ $item->file_status_label }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Nessun media scaduto da eliminare.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">
            @include('admin._pagination', ['paginator' => $items])
        </div>
    </section>
@endsection
