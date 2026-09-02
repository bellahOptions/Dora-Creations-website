<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Demo orders spread over the last 30 days so the admin dashboard's
     * revenue chart and finance page have something real to show.
     */
    public function run(): void
    {
        $customer = User::where('email', 'customer@doracreations.test')->first();
        $products = Product::with('variants')->get();

        if ($products->isEmpty()) {
            return;
        }

        $statuses = [
            Order::STATUS_PROCESSING,
            Order::STATUS_DELIVERY_ONGOING,
            Order::STATUS_DELIVERED,
            Order::STATUS_DELIVERED,
            Order::STATUS_REVIEW_REQUESTED,
            Order::STATUS_REJECTED_REFUNDED,
        ];

        $gateways = ['paystack', 'flutterwave'];
        $states = ['Lagos', 'Abuja', 'Rivers', 'Oyo'];

        for ($i = 0; $i < 22; $i++) {
            $placedAt = now()->subDays(random_int(0, 29))->subHours(random_int(0, 23));
            $isGuest = $i % 3 === 0 || ! $customer;
            $gateway = $gateways[array_rand($gateways)];
            $status = $statuses[array_rand($statuses)];
            $isRefunded = $status === Order::STATUS_REJECTED_REFUNDED;

            $items = $products->random(random_int(1, 3));
            $subtotalKobo = 0;
            $lineItems = [];

            foreach ($items as $product) {
                $variant = $product->has_variants ? $product->variants->random() : null;
                $unitPrice = $variant?->priceKobo() ?? $product->price_kobo;
                $quantity = random_int(1, 2);
                $lineTotal = $unitPrice * $quantity;
                $subtotalKobo += $lineTotal;

                $lineItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_label' => $variant?->label(),
                    'unit_price_kobo' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total_kobo' => $lineTotal,
                ];
            }

            $shippingKobo = 250000;
            $totalKobo = $subtotalKobo + $shippingKobo;

            $order = Order::create([
                'user_id' => $isGuest ? null : $customer->id,
                'guest_email' => $isGuest ? 'guest'.$i.'@example.com' : null,
                'status' => $status,
                'display_currency' => 'NGN',
                'subtotal_kobo' => $subtotalKobo,
                'shipping_kobo' => $shippingKobo,
                'total_kobo' => $totalKobo,
                'shipping_full_name' => $isGuest ? 'Guest Customer '.$i : $customer->name,
                'shipping_phone' => '+234801234'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'shipping_country' => 'Nigeria',
                'shipping_state' => $states[array_rand($states)],
                'shipping_city' => 'Ikeja',
                'shipping_line1' => ($i + 1).' Admiralty Way',
                'payment_gateway' => $gateway,
                'payment_reference' => 'DC-SEED-'.strtoupper(Str::random(8)),
                'paid_at' => $placedAt,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            foreach ($lineItems as $lineItem) {
                $order->items()->create($lineItem);
            }

            Payment::create([
                'order_id' => $order->id,
                'gateway' => $gateway,
                'reference' => $order->payment_reference,
                'status' => $isRefunded ? Payment::STATUS_REFUNDED : Payment::STATUS_SUCCESSFUL,
                'amount_kobo' => $totalKobo,
                'currency' => 'NGN',
                'paid_at' => $placedAt,
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            $order->statusHistories()->create([
                'status' => Order::STATUS_PROCESSING,
                'note' => 'Payment confirmed via '.ucfirst($gateway).'.',
                'created_at' => $placedAt,
                'updated_at' => $placedAt,
            ]);

            if ($status !== Order::STATUS_PROCESSING) {
                $order->statusHistories()->create([
                    'status' => $status,
                    'created_at' => $placedAt->copy()->addHours(random_int(4, 48)),
                    'updated_at' => $placedAt,
                ]);
            }
        }

        // One unpaid, abandoned order for realism.
        Order::create([
            'user_id' => null,
            'guest_email' => 'abandoned@example.com',
            'status' => Order::STATUS_PENDING_PAYMENT,
            'display_currency' => 'NGN',
            'subtotal_kobo' => 1250000,
            'shipping_kobo' => 250000,
            'total_kobo' => 1500000,
            'shipping_full_name' => 'Abandoned Cart',
            'shipping_phone' => '+2348011119999',
            'shipping_country' => 'Nigeria',
            'shipping_state' => 'Lagos',
            'shipping_city' => 'Lekki',
            'shipping_line1' => '5 Admiralty Way',
        ]);
    }
}
