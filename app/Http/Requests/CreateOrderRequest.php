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
