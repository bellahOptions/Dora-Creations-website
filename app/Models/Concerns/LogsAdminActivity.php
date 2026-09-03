<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Auto-logs create/update/delete on the admin panel's own models, but only
 * when the acting user is a signed-in admin — regular customer activity on
 * these same models (e.g. a review submitted from the storefront) never
 * fires this, so callers still log those moments explicitly as visitor
 * activity instead.
 */
trait LogsAdminActivity
{
    public static function bootLogsAdminActivity(): void
    {
        static::created(function ($model) {
            if (! static::actingAsAdmin()) {
                return;
            }

            ActivityLogger::admin("Created {$model->activityLogType()} \"{$model->activityLogName()}\".", $model);
        });

        static::updated(function ($model) {
            if (! static::actingAsAdmin()) {
                return;
            }

            $changes = $model->activityLogChangeSummary();

            if ($changes === '') {
                return;
            }

            ActivityLogger::admin("Updated {$model->activityLogType()} \"{$model->activityLogName()}\" ({$changes}).", $model);
        });

        static::deleted(function ($model) {
            if (! static::actingAsAdmin()) {
                return;
            }

            ActivityLogger::admin("Deleted {$model->activityLogType()} \"{$model->activityLogName()}\".", $model);
        });
    }

    protected static function actingAsAdmin(): bool
    {
        $user = Auth::user();

        return (bool) $user?->is_admin;
    }

    protected function activityLogType(): string
    {
        return Str::headline(class_basename($this));
    }

    /**
     * Which columns are worth mentioning in the log, and what to call them.
     * Override per model; anything not listed here is ignored, so internal
     * columns (ids, slugs, timestamps, paths) never leak into the feed.
     */
    protected function activityLoggableAttributes(): array
    {
        return [];
    }

    /**
     * Columns whose values are kobo amounts, formatted as Naira instead of
     * a raw integer. Override per model alongside activityLoggableAttributes().
     */
    protected function activityLogMoneyKeys(): array
    {
        return [];
    }

    protected function activityLogChangeSummary(): string
    {
        $labels = $this->activityLoggableAttributes();
        $parts = [];

        foreach ($this->getChanges() as $key => $value) {
            if (! array_key_exists($key, $labels)) {
                continue;
            }

            $from = $this->activityLogFormatValue($key, $this->getOriginal($key));
            $to = $this->activityLogFormatValue($key, $value);

            $parts[] = "{$labels[$key]}: {$from} to {$to}";
        }

        return implode(', ', $parts);
    }

    protected function activityLogFormatValue(string $key, $value): string
    {
        if ($value === null) {
            return 'none';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (in_array($key, $this->activityLogMoneyKeys(), true)) {
            return \App\Support\Money::ngn((int) $value);
        }

        return (string) $value;
    }
}
