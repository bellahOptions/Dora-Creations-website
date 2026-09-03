<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\ReceiptPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function lookup(): View
    {
        return view('storefront.order-tracking.lookup');
    }

    public function find(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string'],
            'email' => ['required', 'email'],
        ]);

        $order = Order::where('order_number', $validated['order_number'])
            ->where(function ($query) use ($validated) {
                $query->where('guest_email', $validated['email'])
                    ->orWhereHas('user', fn ($q) => $q->where('email', $validated['email']));
            })
            ->first();

        if (! $order) {
            return back()->withErrors(['order_number' => 'We could not find an order matching those details.']);
        }

        ActivityLogger::visitor("Looked up order {$order->order_number}.", $order);

        return redirect()->route('order-tracking.show', $order->public_token);
    }

    public function show(string $token): View
    {
        $order = Order::with(['items.product.images', 'statusHistories'])
            ->where('public_token', $token)
            ->firstOrFail();

        return view('storefront.order-tracking.show', ['order' => $order]);
    }

    public function receipt(string $token, ReceiptPdfService $receipts): Response
    {
        $order = Order::where('public_token', $token)->firstOrFail();

        return $receipts->forOrder($order)->stream($receipts->filename($order));
    }
}
