@extends('layouts.admin', ['title' => 'Backup'])

@section('content')
    <div class="page-head">
        <h1>Backup PostgreSQL</h1>
        <form method="post" action="{{ route('admin.backups.store') }}">
            @csrf
            <button class="btn" type="submit">Crea backup manuale</button>
        </form>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="errors">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="panel">
        <p style="margin-top:0;color:#667085;">Percorso server: {{ $backupPath }}</p>

        <table>
            <thead>
                <tr>
                    <th>File</th>
                    <th>Dimensione</th>
                    <th>Ultima modifica</th>
                    <th style="text-align:right;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($backups as $backup)
                    <tr>
                        <td>{{ $backup['filename'] }}</td>
                        <td>{{ $backup['size_human'] }}</td>
                        <td>{{ $backup['modified_at_human'] }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn secondary" href="{{ route('admin.backups.download', $backup['filename']) }}">Scarica</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">Nessun backup disponibile.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
