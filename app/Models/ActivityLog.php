<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public const TYPE_ADMIN = 'admin';

    public const TYPE_VISITOR = 'visitor';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'description',
        'causer_type',
        'causer_id',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ADMIN);
    }

    public function scopeVisitor(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_VISITOR);
    }
}
