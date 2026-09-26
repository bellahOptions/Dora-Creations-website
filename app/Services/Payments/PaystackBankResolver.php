<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over Paystack's bank list and account-resolution endpoints.
 *
 * The admin settings screen uses this to turn a bank + account number into the
 * account holder's name, so the storefront shows customers a name the bank has
 * actually confirmed rather than one somebody typed in by hand.
 */
class PaystackBankResolver
{
    protected string $baseUrl = 'https://api.paystack.co';

    public function isConfigured(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    /**
     * Nigerian banks keyed by Paystack bank code.
     *
     * An empty result is never cached, so a transient Paystack outage doesn't
     * leave the admin staring at an empty dropdown for the next 24 hours.
     *
     * @return array<string, string>
     */
    public function banks(): array
    {
        $cached = Cache::get('paystack:banks:NGN');

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::withToken(config('services.paystack.secret_key'))
                ->timeout(10)
                ->get("{$this->baseUrl}/bank", ['currency' => 'NGN', 'perPage' => 200]);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('Paystack bank list request failed', ['status' => $response->status()]);

            return [];
        }

        $banks = collect($response->json('data', []))
            ->filter(fn ($bank) => filled($bank['code'] ?? null) && filled($bank['name'] ?? null))
            ->pluck('name', 'code')
            ->sort()
            ->all();

        if ($banks !== []) {
            Cache::put('paystack:banks:NGN', $banks, now()->addDay());
        }

        return $banks;
    }

    /**
     * Resolve the account holder's name for a bank code + account number.
     *
     * Returns null when Paystack can't confirm the account, which is the signal
     * the admin UI needs to refuse saving an unverified account.
     */
    public function resolveAccountName(string $accountNumber, string $bankCode): ?string
    {
        $accountNumber = preg_replace('/\D/', '', $accountNumber) ?? '';
        $bankCode = trim($bankCode);

        if ($accountNumber === '' || $bankCode === '' || ! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withToken(config('services.paystack.secret_key'))
                ->timeout(10)
                ->get("{$this->baseUrl}/bank/resolve", [
                    'account_number' => $accountNumber,
                    'bank_code' => $bankCode,
                ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }

        if (! $response->successful() || $response->json('status') !== true) {
            Log::info('Paystack could not resolve account name', [
                'bank_code' => $bankCode,
                'account_number_last4' => substr($accountNumber, -4),
                'status' => $response->status(),
            ]);

            return null;
        }

        $name = $response->json('data.account_name');

        return filled($name) ? trim((string) $name) : null;
    }
}
