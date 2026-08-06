@extends('layouts.app')

@section('title', 'Neue Bestellung')

@section('content')
    <h1>Neue Bestellung</h1>

    <div class="card">
        @include('orders._form', [
            'action' => route('orders.store'),
            'isEdit' => false,
            'order' => null,
        ])
    </div>
@endsection
