{{--
    Gemeinsames Formular für Anlegen & Bearbeiten.

    Erwartete Variablen:
      $action       – Ziel-URL (route orders.store bzw. orders.update)
      $isEdit       – bool, steuert @method('PUT') + Button-Text
      $products     – Collection<Product> für das Dropdown
      $order        – ?Order (nur im Edit-Fall, zum Vorbefüllen)

    Bewusst ohne Build-Step: ein winziges Stück Vanilla-JS klont die
    Positionszeile. Serverseitig ist die Item-Struktur identisch zur API
    (items[].product_id, items[].quantity) – deshalb greift derselbe
    FormRequest / dasselbe DTO / derselbe Service.
--}}

@php
    // Priorität: alte Eingaben (nach Validierungsfehler) > vorhandene Bestellung > eine leere Zeile.
    $rows = old('items');

    if ($rows === null) {
        $rows = $isEdit
            ? $order->items->map(fn ($i) => ['product_id' => $i->product_id, 'quantity' => $i->quantity])->all()
            : [['product_id' => '', 'quantity' => 1]];
    }

    $customerEmail = old('customer_email', $isEdit ? $order->customer_email : '');
    $voucherCode = old('voucher_code', $isEdit ? $order->voucher_code : '');
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="field">
        <label for="customer_email">Kunden-E-Mail</label>
        <input type="email" id="customer_email" name="customer_email"
               value="{{ $customerEmail }}" placeholder="kunde@example.com" required>
    </div>

    <div class="field">
        <label for="voucher_code">Gutschein-Code <span class="muted">(optional)</span></label>
        <input type="text" id="voucher_code" name="voucher_code"
               value="{{ $voucherCode }}" placeholder="z. B. WELCOME10">
    </div>

    <div class="field">
        <label>Positionen</label>

        <div id="items">
            @foreach ($rows as $i => $row)
                <div class="item-row">
                    <div>
                        <select name="items[{{ $i }}][product_id]">
                            <option value="">– Produkt wählen –</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}"
                                    @selected((string) ($row['product_id'] ?? '') === (string) $product->id)>
                                    {{ $product->name }}
                                    ({{ number_format($product->price_cents / 100, 2, ',', '.') }} €,
                                    Bestand {{ $product->stock }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="number" name="items[{{ $i }}][quantity]"
                               min="1" value="{{ $row['quantity'] ?? 1 }}">
                    </div>
                    <button type="button" class="link-remove" title="Zeile entfernen"
                            onclick="removeRow(this)">&times;</button>
                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-ghost" onclick="addRow()">+ Position</button>
    </div>

    <div class="row" style="margin-top:8px;">
        <button type="submit" class="btn btn-primary">
            {{ $isEdit ? 'Änderungen speichern' : 'Bestellung anlegen' }}
        </button>
        <a class="btn btn-ghost" href="{{ route('orders.index') }}">Abbrechen</a>
    </div>
</form>

{{-- Produkt-Optionen als Template fürs Klonen, damit JS keine Serverdaten nachladen muss. --}}
<template id="product-options">
    <option value="">– Produkt wählen –</option>
    @foreach ($products as $product)
        <option value="{{ $product->id }}">
            {{ $product->name }}
            ({{ number_format($product->price_cents / 100, 2, ',', '.') }} €, Bestand {{ $product->stock }})
        </option>
    @endforeach
</template>

<script>
    // Fortlaufender Index, damit items[] serverseitig eindeutig bleibt.
    let itemIndex = {{ count($rows) }};

    function addRow() {
        const container = document.getElementById('items');
        const options = document.getElementById('product-options').innerHTML;

        const row = document.createElement('div');
        row.className = 'item-row';
        row.innerHTML =
            '<div><select name="items[' + itemIndex + '][product_id]">' + options + '</select></div>' +
            '<div><input type="number" name="items[' + itemIndex + '][quantity]" min="1" value="1"></div>' +
            '<button type="button" class="link-remove" title="Zeile entfernen" onclick="removeRow(this)">&times;</button>';

        container.appendChild(row);
        itemIndex++;
    }

    function removeRow(btn) {
        const rows = document.querySelectorAll('#items .item-row');
        // Mindestens eine Zeile stehen lassen.
        if (rows.length > 1) {
            btn.closest('.item-row').remove();
        }
    }
</script>
