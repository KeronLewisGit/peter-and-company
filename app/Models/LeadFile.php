<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uploads that come with a request. Always on the private "local" disk. Credit documents
 * (trade licence, VAT certificate, ID, pharmacy licence, bank reference) open for the credit
 * role only; CVs for HR and administrators; anything else follows the request's visibility.
 *
 * @property int $id
 * @property int $lead_id
 * @property string $kind
 * @property string $path
 * @property string $original_name
 * @property string|null $mime
 * @property int $size
 * @property-read Lead $lead
 */
class LeadFile extends Model
{
    public const array CREDIT_KINDS = ['trade_licence', 'vat_certificate', 'id', 'pharmacy_licence', 'bank_reference'];

    public const array HR_KINDS = ['cv', 'letter'];

    protected $fillable = ['lead_id', 'kind', 'path', 'original_name', 'mime', 'size'];

    /** @return BelongsTo<Lead, $this> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function isCreditDocument(): bool
    {
        return in_array($this->kind, self::CREDIT_KINDS, true);
    }

    public function isHrDocument(): bool
    {
        return in_array($this->kind, self::HR_KINDS, true);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }

    public function label(): string
    {
        return match ($this->kind) {
            'trade_licence' => 'Trade licence',
            'vat_certificate' => 'VAT certificate',
            'id' => 'Owner or director ID',
            'pharmacy_licence' => 'Pharmacy licence',
            'bank_reference' => 'Bank reference letter',
            'cv' => 'CV',
            'letter' => 'Cover letter',
            'deck' => 'Brand presentation',
            default => 'File',
        };
    }

    /** Who may open it, for the lock chip shown to everyone else. */
    public function restrictedTo(): ?string
    {
        return match (true) {
            $this->isCreditDocument() => 'Credit control only',
            $this->isHrDocument() => 'HR only',
            default => null,
        };
    }
}
