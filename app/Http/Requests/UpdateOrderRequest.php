<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * Block 1 (Web-Erweiterung) – Änderung nutzt dieselben Regeln wie die Anlage.
 *
 * Durch das Erben von CreateOrderRequest gilt automatisch dieselbe
 * Validierung inklusive der Gutschein-Prüfung in withValidator(). Und weil
 * UpdateOrderRequest ein CreateOrderRequest IST, akzeptiert
 * CreateOrderData::fromRequest() diese Klasse ohne Signaturänderung.
 */
class UpdateOrderRequest extends CreateOrderRequest
{
}
