<?php

namespace App\Livewire\Checkout;

use App\Models\Address;
use App\Models\SiteSetting;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.storefront')]
class CheckoutPage extends Component
{
    public string $email = '';

    public ?int $addressId = null;

    public string $full_name = '';

    public string $phone = '';

    public string $state = '';

    public string $city = '';

    public string $line1 = '';

    public string $line2 = '';

    public string $postal_code = '';

    public string $customerNote = '';

    public string $gateway = 'paystack';

    public bool $submitting = false;

    public function mount(CartService $cartService): void
    {
        if (Auth::check() && Auth::user()->is_admin) {
            $this->redirect(route('filament.admin.pages.dashboard'));

            return;
        }

        if ($cartService->currentCart()->itemCount() === 0) {
            $this->redirect(route('cart.index'));

            return;
        }

        if (Auth::check()) {
            $this->email = Auth::user()->email;
            $default = Auth::user()->addresses()->where('is_default', true)->first();
            $this->addressId = $default?->id;
        }
    }

    #[On('address-selected')]
    public function onAddressSelected(int $addressId): void
    {
        $this->addressId = $addressId;
    }

    protected function usesSavedAddress(): bool
    {
        return Auth::check() && $this->addressId !== null;
    }

    protected function rules(): array
    {
        $rules = [
            'gateway' => ['required', Rule::in(['paystack', 'flutterwave'])],
            'customerNote' => ['nullable', 'string', 'max:1000'],
        ];

        if (Auth::check()) {
            $rules['addressId'] = [
                'required',
                Rule::exists('addresses', 'id')->where('user_id', Auth::id()),
            ];
        } else {
            $rules['email'] = ['required', 'email'];
            $rules += [
                'full_name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:20'],
                'state' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'line1' => ['required', 'string', 'max:255'],
                'line2' => ['nullable', 'string', 'max:255'],
                'postal_code' => ['nullable', 'string', 'max:20'],
            ];
        }

        return $rules;
    }

    protected function shippingData(): array
    {
        if ($this->usesSavedAddress()) {
            $address = Address::where('user_id', Auth::id())->findOrFail($this->addressId);

            return [
                'full_name' => $address->full_name,
                'phone' => $address->phone,
                'state' => $address->state,
                'city' => $address->city,
                'line1' => $address->line1,
                'line2' => $address->line2,
                'postal_code' => $address->postal_code,
            ];
        }

        return [
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'state' => $this->state,
            'city' => $this->city,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'postal_code' => $this->postal_code,
        ];
    }

    public function placeOrder(
        CartService $cartService,
        OrderService $orderService,
        PaymentGatewayManager $gateways
    ): void {
        $this->submitting = true;

        $throttleKey = 'place-order|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $this->submitting = false;
            $this->addError('cart', 'Too many checkout attempts. Please wait a few minutes and try again.');

            return;
        }

        RateLimiter::hit($throttleKey, 3600);

        $this->validate();

        $cart = $cartService->currentCart();

        if ($cart->itemCount() === 0) {
            $this->submitting = false;
            $this->addError('cart', 'Your cart is empty.');

            return;
        }

        $order = $orderService->createFromCart(
            $cart,
            $this->shippingData(),
            Auth::user(),
            Auth::check() ? null : $this->email,
            $this->customerNote ?: null,
        );

        $gatewayService = $gateways->get($this->gateway);

        try {
            $callbackUrl = route('checkout.callback', ['gateway' => $this->gateway]);
            $result = $gatewayService->initialize($order, $callbackUrl);
        } catch (\Throwable $e) {
            report($e);
            $this->submitting = false;
            $this->addError('gateway', 'We could not start your payment. Please try again in a moment.');

            return;
        }

        $orderService->clearCart($cart);
        $this->dispatch('cart-updated');

        $this->redirect($result['redirect_url']);
    }

    public function render(PaymentGatewayManager $gateways, CartService $cartService)
    {
        $cart = $cartService->currentCart();
        $cart->load(['items.product.images', 'items.variant']);

        $settings = SiteSetting::current();
        $subtotal = $cart->subtotalKobo();
        $freeShippingThreshold = $settings->free_shipping_threshold_kobo;
        $shippingKobo = $freeShippingThreshold && $subtotal >= $freeShippingThreshold
            ? 0
            : $settings->shipping_flat_rate_kobo;

        return view('livewire.checkout.checkout-page', [
            'gateways' => $gateways->all(),
            'cart' => $cart,
            'shippingKobo' => $shippingKobo,
        ]);
    }
}
