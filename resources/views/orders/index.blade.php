@extends('layouts.app')

@section('title', 'Bestellungen')

@section('content')
    <h1>Bestellungen</h1>

    <div class="card">
        @if ($orders->isEmpty())
            <p class="muted" style="margin:0;">
                Noch keine Bestellungen für diesen Mandanten.
                <a href="{{ route('orders.create') }}">Erste Bestellung anlegen →</a>
            </p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kunde</th>
                        <th>Positionen</th>
                        <th>Status</th>
                        <th class="right">Summe</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->customer_email }}</td>
                            <td>{{ $order->items_count ?? $order->items->count() }}</td>
                            <td><span class="badge">{{ $order->status }}</span></td>
                            <td class="right">{{ number_format($order->total_cents / 100, 2, ',', '.') }} €</td>
                            <td class="right">
                                <a class="btn btn-ghost" href="{{ route('orders.edit', $order) }}">Bearbeiten</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:16px;">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <p class="muted" style="margin-top:16px;">
        Tipp: Öffne parallel ein Terminal mit
        <code>tail -f storage/logs/laravel.log | grep Domain-Event</code> –
        egal ob du hier im Browser oder per API bestellst/änderst, dasselbe Event wird geloggt.
    </p>
@endsection
