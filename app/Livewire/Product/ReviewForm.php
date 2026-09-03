<?php

namespace App\Livewire\Product;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewReviewSubmitted;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ReviewForm extends Component
{
    public Product $product;

    public int $rating = 5;

    public string $title = '';

    public string $body = '';

    public bool $submitted = false;

    public function mount(Product $product): void
    {
        $this->product = $product;
    }

    /**
     * Only a signed-in customer with a paid order containing this product,
     * who hasn't already reviewed that purchase, is eligible to review it.
     */
    public function getEligibleOrderItemProperty(): ?OrderItem
    {
        if (! Auth::check()) {
            return null;
        }

        return OrderItem::query()
            ->where('product_id', $this->product->id)
            ->whereDoesntHave('review')
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', Auth::id())
                ->whereNotNull('paid_at'))
            ->first();
    }

    public function submit(): void
    {
        $orderItem = $this->eligibleOrderItem;

        if (! $orderItem) {
            $this->addError('rating', 'Only verified customers who have purchased and paid for this product can leave a review.');

            return;
        }

        $throttleKey = 'submit-review|'.Auth::id();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('rating', 'Too many review attempts. Please try again later.');

            return;
        }

        RateLimiter::hit($throttleKey, 3600);

        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = Review::create([
            'product_id' => $this->product->id,
            'user_id' => Auth::id(),
            'order_item_id' => $orderItem->id,
            'rating' => $this->rating,
            'title' => $this->title ?: null,
            'body' => $this->body ?: null,
            'is_verified_purchase' => true,
            'is_approved' => false,
        ]);

        ActivityLogger::visitor("Submitted a {$this->rating}-star review for \"{$this->product->name}\".", $this->product);
        Notification::send(User::where('is_admin', true)->get(), new NewReviewSubmitted($review));

        $this->submitted = true;
        $this->reset(['title', 'body']);
        $this->rating = 5;
    }

    public function render()
    {
        return view('livewire.product.review-form');
    }
}
