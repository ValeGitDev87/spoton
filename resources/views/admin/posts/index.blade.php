@extends('layouts.admin', ['title' => 'Post'])

@section('content')
    <div class="page-head">
        <h1>Post</h1>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <section class="panel">
        <form class="toolbar" method="get" action="{{ route('admin.posts.index') }}">
            <div style="min-width:280px;">
                <label for="search">Cerca</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Testo, musica, autore o luogo">
            </div>
            <div style="min-width:160px;">
                <label for="status">Stato</label>
                <select id="status" name="status">
                    <option value="">Tutti</option>
                    @foreach (['active', 'expired', 'removed', 'flagged'] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn" type="submit">Filtra</button>
            <a class="btn secondary" href="{{ route('admin.posts.index') }}">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Annuncio</th>
                    <th>Autore</th>
                    <th>Luogo</th>
                    <th>Data vista</th>
                    <th>Stato</th>
                    <th>Scadenza</th>
                    <th style="text-align:right;">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                    <tr>
                        <td>
                            <strong>{{ \Illuminate\Support\Str::limit($post->text, 70) }}</strong>
                            @if ($post->musica)
                                <div style="color:#667085;font-size:12px;">{{ \Illuminate\Support\Str::limit($post->musica, 70) }}</div>
                            @endif
                        </td>
                        <td>{{ $post->author?->display_name }}</td>
                        <td>{{ $post->location?->name }}</td>
                        <td>{{ $post->sighting_date?->format('d/m/Y') }}</td>
                        <td><span class="badge status-{{ $post->status }}">{{ $post->status }}</span></td>
                        <td>{{ $post->expires_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="actions">
                                @if ($post->status !== 'removed')
                                    <form method="post" action="{{ route('admin.posts.update-status', $post) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="removed">
                                        <button class="btn danger" type="submit">Disattiva</button>
                                    </form>
                                @else
                                    <form method="post" action="{{ route('admin.posts.update-status', $post) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <button class="btn" type="submit">Riattiva</button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('admin.posts.update-status', $post) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="flagged">
                                    <button class="btn secondary" type="submit">Flag</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">Nessun post trovato.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">
            {{ $posts->links() }}
        </div>
    </section>
@endsection
