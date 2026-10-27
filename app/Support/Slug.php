<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Slug
{
    /**
     * A slug for $title that no row of $model uses yet: "2019-suzuki-swift", then "2019-suzuki-swift-2".
     *
     * @param  class-string<Model>  $model
     */
    public static function unique(string $model, string $title, string $fallback): string
    {
        $base = Str::slug($title) ?: $fallback;
        $slug = $base;
        for ($i = 2; $model::query()->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
