@extends('layouts.admin', ['title' => 'Nuovo Luogo'])

@section('content')
    <div class="page-head">
        <h1>Nuovo luogo</h1>
    </div>

    <section class="panel">
        <form method="post" action="{{ route('admin.locations.store') }}">
            @include('admin.locations._form', ['submitLabel' => 'Crea luogo'])
        </form>
    </section>
@endsection
