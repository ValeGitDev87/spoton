@extends('layouts.admin', ['title' => 'Utenti'])

@section('content')
    <div class="page-head">
        <h1>Utenti</h1>
    </div>

    <section class="panel">
        <form class="toolbar" method="get" action="{{ route('admin.users.index') }}">
            <div style="min-width:260px;">
                <label for="search">Cerca</label>
                <input id="search" name="search" value="{{ $search }}" placeholder="Nome o email">
            </div>
            <div style="min-width:160px;">
                <label for="role">Ruolo</label>
                <select id="role" name="role">
                    <option value="">Tutti</option>
                    <option value="admin" @selected($role === 'admin')>Admin</option>
                    <option value="user" @selected($role === 'user')>Utenti</option>
                </select>
            </div>
            <button class="btn" type="submit">Filtra</button>
            <a class="btn secondary" href="{{ route('admin.users.index') }}">Reset</a>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Ruolo</th>
                    <th>Post</th>
                    <th>Registrato</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->display_name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge {{ $user->is_admin ? '' : 'off' }}">
                                {{ $user->is_admin ? 'Admin' : 'Utente' }}
                            </span>
                        </td>
                        <td>{{ $user->posts_count }}</td>
                        <td>{{ $user->created_at?->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Nessun utente trovato.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">
            {{ $users->links() }}
        </div>
    </section>
@endsection
