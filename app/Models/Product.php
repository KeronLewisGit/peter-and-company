<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A line in the trade catalogue. There are no price or stock columns on purpose: the trade
 * desk quotes both when it confirms an order request.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $brand
 * @property string $division
 * @property string|null $category
 * @property string|null $pack_size
 * @property string|null $sku
 * @property string|null $description
 * @property string|null $photo_path
 * @property string|null $thumb_path
 * @property bool $published
 * @property bool $featured
 * @property bool $is_demo
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'name', 'brand', 'division', 'category', 'pack_size', 'sku', 'description', 'photo_path', 'thumb_path', 'published', 'featured', 'is_demo'];

    #[\Override]
    protected function casts(): array
    {
        return ['published' => 'boolean', 'featured' => 'boolean', 'is_demo' => 'boolean'];
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @param Builder<self> $query */
    public function scopeListed(Builder $query): void
    {
        $query->where('published', true)->whereIn('division', array_keys(self::publicDivisions()));
    }

    /**
     * Divisions the public catalogue shows. The pharmaceutical range is supplied to licensed
     * pharmacies and clinics only, so it is never listed on the open web.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function publicDivisions(): array
    {
        return array_filter((array) config('peterco.divisions'), fn (array $d) => empty($d['restricted']));
    }

    public function divisionName(): string
    {
        return (string) config('peterco.divisions.'.$this->division.'.name', $this->division);
    }

    public function photoUrl(bool $thumb = true): ?string
    {
        $path = $thumb ? ($this->thumb_path ?? $this->photo_path) : $this->photo_path;

        return $path ? Storage::disk('uploads')->url($path) : null;
    }

    /** "Carib Lager · 24 × 275 ml" */
    public function title(): string
    {
        return $this->name.($this->pack_size ? ' · '.$this->pack_size : '');
    }
}
