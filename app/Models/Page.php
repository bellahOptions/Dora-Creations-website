<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'meta_description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Rendered unescaped on the storefront, so it is cleaned on write.
     */
    protected function content(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => HtmlSanitizer::clean($value));
    }
}
