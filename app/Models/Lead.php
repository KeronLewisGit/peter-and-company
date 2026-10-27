<?php

namespace App\Models;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One request from the website: an order request, a trade account application, a principal
 * enquiry, a job application or a general enquiry.
 *
 * @property int $id
 * @property string $reference
 * @property LeadType $type
 * @property LeadStatus $status
 * @property string $name
 * @property string|null $business_name
 * @property string $phone
 * @property string|null $phone_digits
 * @property string|null $email
 * @property string $contact_pref
 * @property int|null $trade_account_id
 * @property int|null $vacancy_id
 * @property int|null $assigned_to
 * @property Carbon|null $delivery_date
 * @property array<string, mixed>|null $details
 * @property string|null $message
 * @property Carbon|null $consented_at
 * @property string|null $source_url
 * @property Carbon|null $first_contacted_at
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property Carbon|null $updated_at
 * @property-read TradeAccount|null $tradeAccount
 * @property-read Vacancy|null $vacancy
 * @property-read User|null $assignee
 * @property-read Collection<int, LeadNote> $notes
 * @property-read Collection<int, LeadFile> $files
 */
class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected $fillable = [
        'type', 'status', 'name', 'business_name', 'phone', 'email', 'contact_pref', 'trade_account_id', 'vacancy_id',
        'assigned_to', 'delivery_date', 'details', 'message', 'consented_at', 'source_url', 'first_contacted_at', 'status_changed_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'type' => LeadType::class,
            'status' => LeadStatus::class,
            'details' => 'array',
            'delivery_date' => 'date',
            'consented_at' => 'datetime',
            'first_contacted_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $lead): void {
            $lead->reference ??= 'pending-'.bin2hex(random_bytes(4));
            $lead->status ??= LeadStatus::New;
        });
        static::created(function (self $lead): void {
            $lead->forceFill([
                'reference' => sprintf('%s-%s-%05d', $lead->type->prefix(), $lead->created_at->format('y'), $lead->id),
            ])->saveQuietly();
        });
        static::saving(function (self $lead): void {
            $lead->phone_digits = preg_replace('/\D+/', '', (string) $lead->phone) ?: null;
        });
    }

    /** @return BelongsTo<TradeAccount, $this> */
    public function tradeAccount(): BelongsTo
    {
        return $this->belongsTo(TradeAccount::class);
    }

    /** @return BelongsTo<Vacancy, $this> */
    public function vacancy(): BelongsTo
    {
        return $this->belongsTo(Vacancy::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return HasMany<LeadNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class)->latest('id');
    }

    /** @return HasMany<LeadFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(LeadFile::class);
    }

    /** A one-line summary of what the request is about, for lists and emails. */
    public function subject(): ?string
    {
        $d = $this->details ?? [];

        return match ($this->type) {
            LeadType::Order => $this->orderSummary(),
            LeadType::Account => ($d['trade_type_label'] ?? null) ?: null,
            LeadType::Principal => trim(($d['brands'] ?? '').(! empty($d['country']) ? ' · '.$d['country'] : '')) ?: null,
            LeadType::Application => $this->vacancy->title ?? (($d['role_interest'] ?? null) ?: 'General application'),
            LeadType::Enquiry => isset($d['topic']) ? (string) config('peterco.enquiry_topics.'.$d['topic'], $d['topic']) : null,
        };
    }

    /**
     * Order lines as stored: product id (null for a free-text line), name, pack and cases.
     *
     * @return list<array{product_id: int|null, name: string, brand: string|null, pack: string|null, sku: string|null, cases: int}>
     */
    public function lines(): array
    {
        $lines = $this->details['lines'] ?? [];

        return is_array($lines) ? array_values($lines) : [];
    }

    public function caseCount(): int
    {
        return (int) array_sum(array_column($this->lines(), 'cases'));
    }

    private function orderSummary(): string
    {
        $lines = $this->lines();
        $n = count($lines);
        $other = ! empty($this->details['other_items']);
        if ($n === 0) {
            return $other ? 'Items listed in the message' : 'Order request';
        }

        return $n.' '.($n === 1 ? 'line' : 'lines').', '.$this->caseCount().' '.($this->caseCount() === 1 ? 'case' : 'cases').($other ? ' + other items' : '');
    }

    public function detail(string $key): mixed
    {
        return ($this->details ?? [])[$key] ?? null;
    }

    public function isOverdue(): bool
    {
        return $this->status === LeadStatus::New && $this->first_contacted_at === null && $this->created_at->lt(now()->subHours($this->type->responseHours()));
    }

    public function moveTo(LeadStatus $status): bool
    {
        if ($this->status === $status || ! $this->type->allows($status)) {
            return false;
        }
        $this->status = $status;
        $this->status_changed_at = now();
        if ($status !== LeadStatus::New) {
            $this->first_contacted_at ??= now();
        }

        return true;
    }

    /**
     * Moves a trade account application to Approved in one conditional UPDATE, so of two
     * concurrent approvals only one wins (MySQL row lock / SQLite write lock). False if
     * another request approved it first.
     */
    public function claimForApproval(): bool
    {
        $now = now();
        $claimed = self::query()->whereKey($this->id)->where('type', LeadType::Account)
            ->where('status', '!=', LeadStatus::Approved)->whereNull('trade_account_id')
            ->update(['status' => LeadStatus::Approved, 'status_changed_at' => $now, 'first_contacted_at' => $this->first_contacted_at ?? $now, 'updated_at' => $now]) === 1;
        if ($claimed) {
            $this->refresh();
        }

        return $claimed;
    }

    /**
     * Requests this user's role works on.
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->whereIn('type', $user->leadTypeValues());
    }

    /**
     * The inbox order: new requests first, then newest first.
     *
     * @param  Builder<self>  $query
     */
    public function scopeInboxOrder(Builder $query): void
    {
        $query->orderByRaw("case when status = 'new' then 0 else 1 end")->orderByDesc('id');
    }

    /**
     * Still New, nobody has spoken to the customer yet, and past the response target for its
     * type (LeadType::responseHours). Logging a call stops the clock.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', LeadStatus::New)->whereNull('first_contacted_at')->where(function (Builder $q) {
            foreach (LeadType::cases() as $type) {
                $q->orWhere(fn (Builder $w) => $w->where('type', $type)->where('created_at', '<', now()->subHours($type->responseHours())));
            }
        });
    }

    /**
     * Not in a final stage (delivered, approved, hired, closed, cancelled or declined).
     *
     * @param  Builder<self>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNotIn('status', array_map(fn (LeadStatus $s) => $s->value, array_filter(LeadStatus::cases(), fn (LeadStatus $s) => $s->isFinal())));
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $f
     */
    public function scopeFilter(Builder $query, array $f): void
    {
        $query
            ->when($f['type'] ?? null, fn (Builder $q, $v) => $q->where('type', $v))
            ->when($f['status'] ?? null, fn (Builder $q, $v) => $v === 'open' ? $q->open() : $q->where('status', $v))
            ->when($f['assigned'] ?? null, fn (Builder $q, $v) => $v === 'none' ? $q->whereNull('assigned_to') : $q->where('assigned_to', $v))
            ->when($f['overdue'] ?? null, fn (Builder $q) => $q->overdue())
            ->when($f['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $digits = preg_replace('/\D+/', '', (string) $term);
                $q->where(function (Builder $w) use ($like, $digits) {
                    $w->where('name', 'like', $like)->orWhere('business_name', 'like', $like)
                        ->orWhere('reference', 'like', $like)->orWhere('email', 'like', $like);
                    if (strlen((string) $digits) >= 3) {
                        $w->orWhere('phone_digits', 'like', '%'.$digits.'%');
                    }
                });
            });
    }
}
