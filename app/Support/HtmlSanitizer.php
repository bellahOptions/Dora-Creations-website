<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Allow-list sanitiser for admin-authored rich text.
 *
 * RichEditor content is stored as raw HTML and rendered unescaped (Page body,
 * AdModal body), so it has to be cleaned on the way in. The editor toolbar is
 * not a security boundary: the field value travels inside the Livewire payload
 * and can be set to anything, and the CSP allows inline script, so a stored
 * <script> or onerror= would run for every storefront visitor.
 */
class HtmlSanitizer
{
    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        return static::instance()->sanitize($html);
    }

    protected static function instance(): SymfonyHtmlSanitizer
    {
        static $sanitizer = null;

        return $sanitizer ??= new SymfonyHtmlSanitizer(
            (new HtmlSanitizerConfig())
                // Safe element/attribute baseline: strips <script>, <style>,
                // <iframe>, <form>, event handlers and javascript: URLs.
                ->allowSafeElements()
                ->allowLinkSchemes(['https', 'http', 'mailto', 'tel'])
                ->allowMediaSchemes(['https', 'http'])
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                // Rich text relies on classes for its own styling.
                ->allowAttribute('class', allowedElements: '*')
                // Outbound links opened in a new tab must not get window.opener.
                ->forceAttribute('a', 'rel', 'noopener noreferrer')
                ->dropElement('script')
                ->dropElement('style')
                ->dropElement('iframe')
                ->dropElement('object')
                ->dropElement('embed')
                ->dropElement('form')
                ->withMaxInputLength(200_000)
        );
    }
}
