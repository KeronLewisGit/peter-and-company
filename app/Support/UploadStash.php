<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Keeps a form's valid uploads between attempts. Browsers can't refill a file input, so
 * without this a typo in a phone number would make an applicant attach their trade licence,
 * VAT certificate and ID all over again. Files wait on the private disk (stash/), are
 * listed in the visitor's session only, move into leads/{id}/ when the form is accepted and
 * are deleted after a day otherwise.
 */
final class UploadStash
{
    private const string DIR = 'stash';

    private const int KEEP_HOURS = 24;

    public function __construct(private readonly string $form) {}

    public static function for(string $form): self
    {
        return new self($form);
    }

    /**
     * Files kept for this form, by field.
     *
     * @return array<string, StashedUpload>
     */
    public function all(): array
    {
        $kept = [];
        foreach ((array) session($this->key(), []) as $field => $entry) {
            if (is_array($entry) && is_string($entry['path'] ?? null) && str_starts_with($entry['path'], self::DIR.'/') && Storage::disk('local')->exists($entry['path'])) {
                $kept[(string) $field] = new StashedUpload($entry['path'], (string) ($entry['name'] ?? 'file'), isset($entry['mime']) ? (string) $entry['mime'] : null, (int) ($entry['size'] ?? 0));
            }
        }

        return $kept;
    }

    /**
     * Original file names by field, for "already attached" hints on the form.
     *
     * @return array<string, string>
     */
    public function names(): array
    {
        return array_map(fn (StashedUpload $u) => $u->originalName, $this->all());
    }

    /**
     * Stores every new upload that passes its own rules, replacing an earlier one for the same
     * field. Invalid files are left for the form's validator to report.
     *
     * @param  array<string, list<mixed>>  $rules  field => file rules (type and size, not "required")
     */
    public function keep(Request $request, array $rules): void
    {
        self::prune();
        $entries = (array) session($this->key(), []);
        foreach ($rules as $field => $fieldRules) {
            $file = $request->file($field);
            if (! $file instanceof UploadedFile || validator([$field => $file], [$field => $fieldRules])->fails()) {
                continue;
            }
            $path = $file->store(self::DIR, 'local');
            if (! is_string($path)) {
                continue;
            }
            if (is_array($entries[$field] ?? null) && is_string($entries[$field]['path'] ?? null)) {
                Storage::disk('local')->delete($entries[$field]['path']);
            }
            $entries[$field] = ['path' => $path, 'name' => mb_substr($file->getClientOriginalName(), 0, 200), 'mime' => $file->getMimeType(), 'size' => (int) $file->getSize()];
        }
        session([$this->key() => $entries]);
    }

    /**
     * The form's rules with "required" lifted from fields that already have a kept file and
     * no new one. A new upload is still checked in full.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public function relax(array $rules, Request $request): array
    {
        foreach (array_keys($this->all()) as $field) {
            if (array_key_exists($field, $rules) && ! $request->hasFile($field)) {
                $rules[$field] = ['nullable'];
            }
        }

        return $rules;
    }

    /** The kept file for a field, if any. */
    public function get(string $field): ?StashedUpload
    {
        return $this->all()[$field] ?? null;
    }

    /** After a successful submission: anything not moved into the request is deleted. */
    public function clear(): void
    {
        foreach ($this->all() as $upload) {
            Storage::disk('local')->delete($upload->path);
        }
        session()->forget($this->key());
    }

    private function key(): string
    {
        return 'upload_stash.'.$this->form;
    }

    /** Uploads from abandoned forms go after a day. */
    private static function prune(): void
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHours(self::KEEP_HOURS)->getTimestamp();
        foreach ($disk->files(self::DIR) as $path) {
            if ($disk->lastModified($path) < $cutoff) {
                $disk->delete($path);
            }
        }
    }
}
