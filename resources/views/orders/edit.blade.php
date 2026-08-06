@extends('layouts.app')

@section('title', "Bestellung #{$order->id} bearbeiten")

@section('content')
    <h1>Bestellung #{{ $order->id }} bearbeiten</h1>

    <div class="card">
        @include('orders._form', [
            'action' => route('orders.update', $order),
            'isEdit' => true,
            'order' => $order,
        ])
    </div>
@endsection
