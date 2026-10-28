<?php

if (! function_exists('peterco')) {
    /** A company fact from config/peterco.php, e.g. peterco('address.street'). */
    function peterco(string $key, mixed $default = null): mixed
    {
        return config('peterco.'.$key, $default);
    }
}

if (! function_exists('us_digits')) {
    /** E.164 digits for a North American (+1, incl. St Lucia 758) number written any common way, or null. */
    function us_digits(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';
        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        return strlen($digits) === 11 && $digits[0] === '1' ? $digits : null;
    }
}

if (! function_exists('tel_href')) {
    function tel_href(?string $number): string
    {
        $digits = us_digits($number);

        return $digits ? 'tel:+'.$digits : 'tel:'.preg_replace('/[^\d+]/', '', (string) $number);
    }
}

if (! function_exists('format_phone')) {
    /** 758-452-1234 for +1 numbers (the way St Lucians write them), the input unchanged otherwise. */
    function format_phone(?string $number): string
    {
        $digits = us_digits($number);
        if (! $digits) {
            return (string) $number;
        }

        return sprintf('%s-%s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7));
    }
}

if (! function_exists('input_string')) {
    /** A request value as a string. Arrays (?type[]=x) and other junk become ''. */
    function input_string(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }
}

if (! function_exists('lines_to_array')) {
    /**
     * Textarea with one item per line, bullets optional, to a clean list.
     *
     * @return list<string>
     */
    function lines_to_array(?string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $text) ?: [];
        $clean = array_map(fn (string $l) => trim((string) preg_replace('/^(?:[-*]|•)\s*/u', '', $l)), $lines);

        return array_values(array_filter($clean, fn (string $l) => $l !== ''));
    }
}

if (! function_exists('compact_number')) {
    /** 1,284 → 1.3K, used on stat tiles. */
    function compact_number(int|float $n): string
    {
        return match (true) {
            abs($n) >= 1_000_000 => round($n / 1_000_000, 1).'M',
            abs($n) >= 10_000 => round($n / 1_000).'K',
            default => number_format($n),
        };
    }
}

if (! function_exists('photo')) {
    /**
     * Responsive <img> attributes for a photo in public/img: WebP files at 480/960/1920 wide,
     * or at the widths listed in the photo's metadata (for client photos smaller than 1920).
     *
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    function photo(string $key): array
    {
        $meta = config('peterco.photos.'.$key) ?? ['w' => 1920, 'h' => 1280];
        /** @var list<int> $widths */
        $widths = $meta['widths'] ?? [480, 960, 1920];
        $url = fn (int $w) => asset("img/{$key}-{$w}.webp");
        $src = collect($widths)->first(fn (int $w) => $w >= 960) ?? $widths[array_key_last($widths)];

        return [
            'src' => $url($src),
            'srcset' => collect($widths)->map(fn (int $w) => $url($w).' '.$w.'w')->join(', '),
            'width' => (int) $meta['w'],
            'height' => (int) $meta['h'],
        ];
    }
}

if (! function_exists('brand_logo')) {
    /** URL of a brand's logo tile (as published on acadostlucia.com), or null when there is none. */
    function brand_logo(?string $brand): ?string
    {
        // Not config('brand-logos.'.$brand): brand names such as "S.Pellegrino" contain dots.
        $logos = (array) config('brand-logos', []);
        $file = $brand === null ? null : ($logos[$brand] ?? null);

        return is_string($file) ? asset('img/brands/'.$file.'.webp') : null;
    }
}

if (! function_exists('wa_href')) {
    /** WhatsApp click-to-chat link, optionally with a prefilled message. */
    function wa_href(?string $number = null, ?string $text = null): string
    {
        $digits = us_digits($number ?? (string) peterco('whatsapp')) ?? preg_replace('/\D+/', '', (string) $number);

        return 'https://wa.me/'.$digits.($text ? '?text='.rawurlencode($text) : '');
    }
}
