@extends('layouts.admin', ['title' => 'Segnalazioni'])

@section('content')
    <div class="page-head">
        <h1>Segnalazioni</h1>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <section class="panel">
        <form class="toolbar" method="get" action="{{ route('admin.reports.index') }}">
            <div style="min-width:180px;">
                <label for="status">Stato</label>
                <select id="status" name="status">
                    <option value="">Tutti</option>
                    @foreach (['pending', 'reviewed', 'dismissed', 'actioned'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div style="min-width:180px;">
                <label for="target_type">Oggetto</label>
                <select id="target_type" name="target_type">
                    <option value="">Tutti</option>
                    <option value="post" @selected($targetType === 'post')>Post</option>
                    <option value="user" @selected($targetType === 'user')>Utente</option>
                </select>
            </div>
            <button class="btn" type="submit">Filtra</button>
            <a class="btn secondary" href="{{ route('admin.reports.index') }}">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Segnalante</th>
                    <th>Oggetto</th>
                    <th>Motivo</th>
                    <th>Stato</th>
                    <th>Data</th>
                    <th style="text-align:right;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr>
                        <td>
                            <strong>{{ $report->reporter?->display_name }}</strong>
                            <div style="color:#667085;font-size:12px;">{{ $report->reporter?->email }}</div>
                        </td>
                        <td>
                            @if ($report->reportable_type === 'post')
                                <strong>Post</strong>
                                <div style="color:#667085;font-size:12px;">{{ str($report->reportable?->text)->limit(70) }}</div>
                            @else
                                <strong>Utente: {{ $report->reportable?->display_name ?? 'non disponibile' }}</strong>
                                <div style="color:#667085;font-size:12px;">{{ $report->reportable?->email }}</div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ ucfirst($report->reason) }}</strong>
                            @if ($report->details)
                                <div style="color:#667085;font-size:12px;">{{ str($report->details)->limit(100) }}</div>
                            @endif
                        </td>
                        <td><span class="badge status-{{ $report->status }}">{{ $report->status }}</span></td>
                        <td>{{ $report->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            @if ($report->status === 'pending')
                                <div class="actions">
                                    <form method="post" action="{{ route('admin.reports.update', $report) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="actioned">
                                        <button class="btn danger" type="submit">Intervieni</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.reports.update', $report) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="dismissed">
                                        <button class="btn secondary" type="submit">Archivia</button>
                                    </form>
                                </div>
                            @else
                                <div style="color:#667085;font-size:12px;">
                                    {{ $report->reviewer?->display_name }}<br>
                                    {{ $report->reviewed_at?->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Nessuna segnalazione trovata.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">
            {{ $reports->links() }}
        </div>
    </section>
@endsection
