<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\PaymentTerms;
use App\Enums\TradeType;
use Database\Factories\TradeAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An approved trade customer. Order requests that quote the account number are linked to it,
 * and pharmaceutical order requests are only accepted from an active pharmacy or clinic account.
 *
 * @property int $id
 * @property string $number
 * @property string $business_name
 * @property TradeType $trade_type
 * @property string $contact_name
 * @property string $phone
 * @property string|null $email
 * @property string|null $district
 * @property PaymentTerms $terms
 * @property AccountStatus $status
 * @property string|null $pharmacy_licence
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property bool $is_demo
 * @property Carbon|null $created_at
 * @property-read User|null $approver
 */
class TradeAccount extends Model
{
    /** @use HasFactory<TradeAccountFactory> */
    use HasFactory;

    protected $fillable = ['number', 'business_name', 'trade_type', 'contact_name', 'phone', 'email', 'district', 'terms', 'status', 'pharmacy_licence', 'approved_by', 'approved_at', 'is_demo'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'trade_type' => TradeType::class,
            'terms' => PaymentTerms::class,
            'status' => AccountStatus::class,
            'approved_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(function (self $account): void {
            $account->number ??= 'pending-'.bin2hex(random_bytes(4));
        });
        // PC-10001, PC-10002 …: short enough to read out over the phone.
        static::created(function (self $account): void {
            if (str_starts_with($account->number, 'pending-')) {
                $account->forceFill(['number' => sprintf('PC-%05d', 10000 + $account->id)])->saveQuietly();
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<Lead, $this> */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', AccountStatus::Active);
    }

    /** Typed account numbers: "pc 10042", "PC10042" and "10042" all find PC-10042. */
    public static function findByNumber(string $typed): ?self
    {
        $digits = preg_replace('/\D+/', '', $typed) ?? '';
        if ($digits === '') {
            return null;
        }

        return self::query()->where('number', 'PC-'.str_pad($digits, 5, '0', STR_PAD_LEFT))->first();
    }

    public function canOrderPharmaceuticals(): bool
    {
        return $this->status === AccountStatus::Active && $this->trade_type->isMedical() && filled($this->pharmacy_licence);
    }
}
