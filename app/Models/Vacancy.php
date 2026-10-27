<?php

namespace App\Models;

use Database\Factories\VacancyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $department
 * @property string $location
 * @property string $employment_type
 * @property string $summary
 * @property string|null $duties
 * @property string|null $requirements
 * @property Carbon|null $closes_on
 * @property bool $published
 * @property bool $is_demo
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Vacancy extends Model
{
    /** @use HasFactory<VacancyFactory> */
    use HasFactory;

    protected $fillable = ['slug', 'title', 'department', 'location', 'employment_type', 'summary', 'duties', 'requirements', 'closes_on', 'published', 'is_demo'];

    #[\Override]
    protected function casts(): array
    {
        return ['closes_on' => 'date', 'published' => 'boolean', 'is_demo' => 'boolean'];
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<Lead, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Published and not past its closing date.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('published', true)->where(fn (Builder $q) => $q->whereNull('closes_on')->orWhereDate('closes_on', '>=', today()));
    }

    public function isOpen(): bool
    {
        return $this->published && ($this->closes_on === null || $this->closes_on->gte(today()));
    }

    /** @return list<string> */
    public function dutyList(): array
    {
        return lines_to_array($this->duties);
    }

    /** @return list<string> */
    public function requirementList(): array
    {
        return lines_to_array($this->requirements);
    }
}
