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

    protected static function write(string $type, string $description, ?Model $causer, ?Model $subject, array $properties): void
    {
        ActivityLog::create([
            'type' => $type,
            'description' => $description,
            'causer_type' => $causer?->getMorphClass(),
            'causer_id' => $causer?->getKey(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
