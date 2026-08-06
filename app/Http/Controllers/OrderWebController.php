<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\CreateOrderData;
use App\Exceptions\DomainException;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Web-Erweiterung – "Lean Controller" fürs Browser-Formular.
 *
 * Bewusst symmetrisch zum API-OrderController: gleiche FormRequests, gleiches
 * DTO, gleicher OrderService. Der EINZIGE Unterschied ist die Darstellung
 * (Blade-View + Redirect statt JSON). Die Geschäftslogik – und damit das
 * gefeuerte Event – ist identisch.
 */
final class OrderWebController
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => $this->orders->paginateForTenant($this->tenantId($request)),
        ]);
    }

    public function create(Request $request): View
    {
        return view('orders.create', [
            'products' => $this->productsForTenant($request),
        ]);
    }

    public function store(CreateOrderRequest $request): RedirectResponse
    {
        try {
            $order = $this->orders->place(CreateOrderData::fromRequest($request));
        } catch (DomainException $e) {
            // Fachliche Fehler (z. B. kein Bestand) als Formularfehler zurückspielen.
            return back()->withInput()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.index')
            ->with('status', "Bestellung #{$order->id} angelegt – OrderCreated gefeuert (siehe Log).");
    }

    public function edit(Request $request, Order $order): View
    {
        $order = $this->orders->findForTenant($this->tenantId($request), $order->id);

        return view('orders.edit', [
            'order' => $order,
            'products' => $this->productsForTenant($request),
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        // Tenant-Isolation: fremde Bestellung => 404.
        $order = $this->orders->findForTenant($this->tenantId($request), $order->id);

        try {
            $this->orders->change($order, CreateOrderData::fromRequest($request));
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['domain' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.index')
            ->with('status', "Bestellung #{$order->id} geändert – OrderUpdated gefeuert (siehe Log).");
    }

    /**
     * Mandanten-Umschalter: legt den gewählten Mandanten in die Session.
     * Das Web-Pendant zum Wechsel des X-Api-Key in der API.
     */
    public function switchTenant(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ]);

        $request->session()->put('tenant_id', (int) $validated['tenant_id']);

        return back();
    }

    private function tenantId(Request $request): int
    {
        return (int) $request->attributes->get('tenant_id');
    }

    /** @return \Illuminate\Support\Collection<int, Product> */
    private function productsForTenant(Request $request)
    {
        return Product::query()
            ->where('tenant_id', $this->tenantId($request))
            ->orderBy('name')
            ->get();
    }
}
