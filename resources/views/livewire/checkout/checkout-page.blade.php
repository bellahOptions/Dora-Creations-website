@php
    $nigerianStates = ['Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT - Abuja', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara'];
@endphp

<div class="container-store py-12">
    <h1 class="font-display text-3xl uppercase sm:text-4xl">Checkout</h1>

    <form wire:submit="placeOrder" class="mt-10 grid grid-cols-1 gap-12 lg:grid-cols-[1fr_380px]">
        <div class="space-y-10">
            @unless (auth()->check())
                <section>
                    <h2 class="font-display text-lg uppercase">Contact</h2>
                    <p class="mt-1 text-sm text-ink-500">
                        No account needed to check out.
                        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-brand-600 hover:underline">Sign in</a> if you have one.
                    </p>
                    <div class="mt-4">
                        <x-input-label for="email" value="Email" />
                        <x-text-input wire:model="email" id="email" type="email" class="mt-1" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                </section>
            @endunless

            <section>
                <h2 class="font-display text-lg uppercase">Delivery address</h2>

                @auth
                    <div class="mt-4">
                        @livewire('account.address-manager', ['selectable' => true])
                    </div>
                    <x-input-error :messages="$errors->get('addressId')" class="mt-2" />
                @else
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="full_name" value="Full name" />
                            <x-text-input wire:model="full_name" id="full_name" class="mt-1" type="text" />
                            <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="phone" value="Phone" />
                            <x-text-input wire:model="phone" id="phone" class="mt-1" type="tel" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="state" value="State" />
                            <select wire:model="state" id="state" class="mt-1 w-full rounded-lg border-ink-200 focus:border-brand-500 focus:ring-brand-500">
                                <option value="">Select state</option>
                                @foreach ($nigerianStates as $stateOption)
                                    <option value="{{ $stateOption }}">{{ $stateOption }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('state')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="city" value="City" />
                            <x-text-input wire:model="city" id="city" class="mt-1" type="text" />
                            <x-input-error :messages="$errors->get('city')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="line1" value="Street address" />
                            <x-text-input wire:model="line1" id="line1" class="mt-1" type="text" />
                            <x-input-error :messages="$errors->get('line1')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="line2" value="Apartment, suite, etc. (optional)" />
                            <x-text-input wire:model="line2" id="line2" class="mt-1" type="text" />
                        </div>
                        <div>
                            <x-input-label for="postal_code" value="Postal code (optional)" />
                            <x-text-input wire:model="postal_code" id="postal_code" class="mt-1" type="text" />
                        </div>
                    </div>
                @endauth
            </section>

            <section>
                <h2 class="font-display text-lg uppercase">Payment method</h2>
                <p class="mt-1 text-sm text-ink-500">Choose whichever works best for you; both are secure.</p>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach ($gateways as $key => $gatewayInstance)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition {{ $gateway === $key ? 'border-ink-900 bg-ink-50' : 'border-ink-200' }}">
                            <input type="radio" wire:model="gateway" value="{{ $key }}" class="text-brand-600 focus:ring-brand-500">
                            <span class="font-semibold">{{ $gatewayInstance->label() }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('gateway')" class="mt-2" />
            </section>

            <section>
                <x-input-label for="customerNote" value="Order note (optional)" />
                <textarea wire:model="customerNote" id="customerNote" rows="3"
                    class="mt-1 w-full rounded-lg border-ink-200 focus:border-brand-500 focus:ring-brand-500"
                    placeholder="Delivery instructions, gift note, etc."></textarea>
            </section>
        </div>

        <div class="h-fit rounded-2xl border border-ink-100 bg-paper-soft p-6">
            <h2 class="font-display text-lg uppercase">Order summary</h2>

            <ul class="mt-4 space-y-3">
                @foreach ($cart->items as $item)
                    <li class="flex items-center gap-3 text-sm">
                        <img src="{{ $item->product->images->first()?->url() ?? asset('placeholder.svg') }}" alt="{{ $item->product->name }}"
                            onerror="this.onerror=null;this.src='{{ asset('placeholder.svg') }}';"
                            class="h-14 w-12 rounded-lg object-cover">
                        <div class="flex-1">
                            <p class="font-semibold">
                                {{ $item->product->name }}
                                @if ($item->product->is_preorder)
                                    <span class="ml-1 rounded-full bg-ink-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-paper">Pre-order</span>
                                @endif
                            </p>
                            <p class="text-ink-400">{{ $item->variant?->label() }} · Qty {{ $item->quantity }}</p>
                        </div>
                        <span class="font-semibold">{{ $item->formattedLineTotal() }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6 border-t border-ink-200 pt-4">
                @if ($appliedCouponCode && $discount)
                    <div class="flex items-center justify-between rounded-lg bg-cream/40 px-3 py-2 text-sm">
                        <span class="font-semibold text-ink-900">{{ $appliedCouponCode }} applied, {{ $discount->label() }}</span>
                        <button type="button" wire:click="removeCoupon" class="text-xs font-semibold uppercase text-ink-500 hover:text-ink-900">Remove</button>
                    </div>
                @else
                    <div class="flex gap-2">
                        <x-text-input wire:model="couponCode" type="text" placeholder="Discount code" class="flex-1 uppercase placeholder:normal-case" />
                        <button type="button" wire:click="applyCoupon" wire:loading.attr="disabled" wire:target="applyCoupon"
                            class="rounded-lg border border-ink-900 px-4 text-sm font-semibold uppercase tracking-wide text-ink-900 transition hover:bg-ink-900 hover:text-paper">
                            Apply
                        </button>
                    </div>
                    @if ($couponError)
                        <p class="mt-2 text-xs text-red-600">{{ $couponError }}</p>
                    @endif
                @endif
            </div>

            <dl class="mt-4 space-y-3 border-t border-ink-200 pt-4 text-sm">
                <div class="flex justify-between">
                    <dt class="text-ink-500">Subtotal</dt>
                    <dd class="font-mono font-semibold">{{ $cart->formattedSubtotal() }}</dd>
                </div>
                @if ($discountKobo > 0)
                    <div class="flex justify-between">
                        <dt class="text-ink-500">Discount</dt>
                        <dd class="font-mono font-semibold">−{{ \App\Support\Money::ngn($discountKobo) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-ink-500">Shipping</dt>
                    <dd class="font-mono font-semibold">{{ $shippingKobo === 0 ? 'Free' : \App\Support\Money::ngn($shippingKobo) }}</dd>
                </div>
            </dl>

            <div class="mt-4 flex justify-between border-t border-ink-200 pt-4 text-base font-semibold">
                <span>Total (charged in NGN)</span>
                <span class="font-mono">{{ \App\Support\Money::ngn(max(0, $cart->subtotalKobo() + $shippingKobo - $discountKobo)) }}</span>
            </div>

            <x-input-error :messages="$errors->get('cart')" class="mt-2" />

            <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder"
                class="mt-6 flex w-full items-center justify-center rounded-full bg-ink-900 px-6 py-3 text-sm font-semibold uppercase tracking-wide text-paper transition hover:bg-brand-500 disabled:opacity-60">
                <span wire:loading.remove wire:target="placeOrder">Place order &amp; pay</span>
                <span wire:loading wire:target="placeOrder">Redirecting to payment…</span>
            </button>
            <p class="mt-3 text-center text-xs text-ink-400">You'll be redirected to complete payment securely.</p>
        </div>
    </form>
</div>
