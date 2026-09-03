<div>
    @if ($submitted)
        <div class="rounded-2xl border border-ink-100 bg-cream/30 p-6 text-sm text-ink-700">
            <p class="font-semibold text-ink-900">Thanks for your review!</p>
            <p class="mt-1">It's awaiting a quick check before it appears on this page.</p>
        </div>
    @elseif ($this->eligibleOrderItem)
        <form wire:submit="submit" class="rounded-2xl border border-ink-100 p-6">
            <p class="font-display text-lg uppercase">Write a review</p>
            <p class="mt-1 text-sm text-ink-500">You purchased this — share what you thought.</p>

            <div class="mt-4">
                <x-input-label value="Rating" />
                <div class="mt-2 flex items-center gap-1" x-data="{ hover: 0 }">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" wire:click="$set('rating', {{ $i }})"
                            @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0"
                            class="text-2xl leading-none transition"
                            :class="(hover || {{ $rating }}) >= {{ $i }} ? 'text-gold' : 'text-ink-200'">
                            ★
                        </button>
                    @endfor
                </div>
                <x-input-error :messages="$errors->get('rating')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="review-title" value="Title (optional)" />
                <x-text-input wire:model="title" id="review-title" class="mt-1" type="text" maxlength="255" />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="review-body" value="Your review (optional)" />
                <textarea wire:model="body" id="review-body" rows="4" maxlength="2000"
                    class="mt-1 block w-full rounded-lg border-ink-200 text-ink-900 placeholder:text-ink-300 focus:border-brand-500 focus:ring-brand-500"></textarea>
                <x-input-error :messages="$errors->get('body')" class="mt-2" />
            </div>

            <x-primary-button class="mt-5">Submit review</x-primary-button>
        </form>
    @endif
</div>
