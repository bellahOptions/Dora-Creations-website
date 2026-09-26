<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Record something an admin did in the panel.
     */
    public static function admin(string $description, ?Model $subject = null, array $properties = []): void
    {
        static::write(ActivityLog::TYPE_ADMIN, $description, Auth::user(), $subject, $properties);
    }

    /**
     * Record something a storefront visitor did, guest or signed-in customer.
     */
    public static function visitor(string $description, ?Model $subject = null, array $properties = []): void
    {
        $user = Auth::user();
        $causer = $user && ! $user->is_admin ? $user : null;

        static::write(ActivityLog::TYPE_VISITOR, $description, $causer, $subject, $properties);
    }

    /**
     * Record a visitor action once the response has already gone out.
     *
     * Used for high-traffic storefront hits (product views) where a
     * synchronous insert would land squarely in the page's TTFB.
     */
    public static function visitorAfterResponse(string $description, ?Model $subject = null, array $properties = []): void
    {
        $user = Auth::user();
        $causer = $user && ! $user->is_admin ? $user : null;
        $ip = request()?->ip();

        app()->terminating(fn () => static::write(
            ActivityLog::TYPE_VISITOR,
            $description,
            $causer,
            $subject,
            $properties,
            $ip,
        ));
    }

    protected static function write(string $type, string $description, ?Model $causer, ?Model $subject, array $properties, ?string $ip = null): void
    {
        ActivityLog::create([
            'type' => $type,
            // Customer-supplied names and emails end up in these descriptions.
            // Flatten and strip them so a crafted name can't forge extra lines
            // in the admin activity feed.
            'description' => static::sanitize($description),
            'causer_type' => $causer?->getMorphClass(),
            'causer_id' => $causer?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $ip ?? request()?->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * Collapse anything that could break a log line apart, and cap the
     * length so one entry can't dominate the feed.
     */
    protected static function sanitize(string $description): string
    {
        $flat = preg_replace('/\s+/u', ' ', strip_tags($description)) ?? $description;

        return mb_substr(trim($flat), 0, 1000);
    }
}
