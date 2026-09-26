<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Notifications\BankTransferUpdate;
use App\Services\ActivityLogger;
use App\Services\ReceiptPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
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

    /**
     * The customer telling us they have sent a bank transfer. This never
     * marks the order paid — only an admin can do that — it just flags the
     * order so the shop owner knows to go and check the bank.
     */
    public function declareTransfer(string $token): RedirectResponse
    {
        $order = Order::where('public_token', $token)->firstOrFail();

        if (! $order->awaitingBankTransfer()) {
            return redirect()->route('order-tracking.show', $token);
        }

        if (! $order->hasDeclaredTransfer()) {
            $order->forceFill(['transfer_declared_at' => now()])->save();

            $order->statusHistories()->create([
                'status' => $order->status,
                'note' => 'Customer marked the bank transfer as sent.',
            ]);

            ActivityLogger::visitor("Said the bank transfer for order {$order->order_number} was sent.", $order);

            try {
                Notification::send(
                    User::where('is_admin', true)->get(),
                    new BankTransferUpdate($order, BankTransferUpdate::PHASE_DECLARED),
                );
            } catch (\Throwable $e) {
                // Flagging the order already succeeded; a mail hiccup must
                // not turn into an error page for the customer.
                report($e);
            }
        }

        return redirect()->route('order-tracking.show', $token)->with('transfer-declared', true);
    }
}
