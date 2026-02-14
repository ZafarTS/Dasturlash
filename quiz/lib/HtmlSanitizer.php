<?php

namespace Quiz\Lib;

class HtmlSanitizer
{
    /**
     * Lightweight sanitizer: strips script tags and dangerous inline handlers.
     * Replace with HTML Purifier in production for strict policy handling.
     */
    public static function clean(string $html): string
    {
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html) ?? '';
        $html = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $html) ?? '';
        $html = preg_replace('/javascript:/i', '', $html) ?? '';
        return trim($html);
    }
}
