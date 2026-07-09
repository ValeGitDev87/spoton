@extends('layouts.admin', ['title' => 'Modifica Luogo'])

@section('content')
    <div class="page-head">
        <h1>Modifica luogo</h1>
    </div>

    <section class="panel">
        <form method="post" action="{{ route('admin.locations.update', $location) }}">
            @method('PATCH')
            @include('admin.locations._form', ['submitLabel' => 'Salva modifiche'])
        </form>
    </section>
@endsection
