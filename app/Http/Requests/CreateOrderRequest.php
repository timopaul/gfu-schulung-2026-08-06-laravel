<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Block 1 – Der FormRequest ist die EINZIGE Stelle für Eingabe-Validierung.
 * Er kennt keine Domain-Logik; er stellt nur sicher, dass die Rohdaten sauber sind.
 */
class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorisierung passiert in der Middleware (Tenant-Kontext); hier true.
        return true;
    }

    /**
     * Normalisiert die Rohdaten VOR der Validierung – vor allem für das
     * Browser-Formular: HTML sendet leere Felder als "" statt null, und eine
     * frei gelassene Positionszeile soll nicht als Fehler zählen. Die API
     * profitiert unverändert mit (JSON schickt i. d. R. bereits sauber).
     */
    protected function prepareForValidation(): void
    {
        $voucher = $this->input('voucher_code');
        $items = $this->input('items', []);

        if (is_array($items)) {
            $items = array_values(array_filter(
                $items,
                static fn ($row): bool => is_array($row)
                    && isset($row['product_id'])
                    && $row['product_id'] !== '',
            ));
        }

        $this->merge([
            'voucher_code' => $voucher === '' ? null : $voucher,
            'items' => $items,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_email' => ['required', 'email'],
            'voucher_code' => ['nullable', 'string', 'exists:vouchers,code'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Beispiel für kontextabhängige Validierung: ein abgelaufener oder
     * inaktiver Gutschein ist zwar vorhanden (exists), aber nicht einlösbar.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $code = $this->input('voucher_code');

            if ($code === null) {
                return;
            }

            $voucher = Voucher::query()->where('code', $code)->first();

            if ($voucher !== null && ! $voucher->isRedeemable()) {
                $validator->errors()->add('voucher_code', 'Dieser Gutschein ist abgelaufen oder inaktiv.');
            }
        });
    }
}
