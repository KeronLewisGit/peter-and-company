<?php

namespace App\Support;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use App\Notifications\LeadReceived;
use App\Notifications\NewLead;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Every public form ends here: one lead row with a reference, the uploaded files on
 * the private disk, a first timeline entry, an email to the office and a confirmation
 * to the customer.
 */
final class LeadIntake
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, list<UploadedFile>>  $files  kind => files
     */
    public static function create(LeadType $type, array $attributes, array $files, Request $request): Lead
    {
        $lead = Lead::create($attributes + [
            'type' => $type,
            'status' => LeadStatus::New,
            'source_url' => mb_substr((string) $request->headers->get('referer', $request->fullUrl()), 0, 300),
        ]);

        foreach ($files as $kind => $list) {
            foreach ($list as $file) {
                $lead->files()->create([
                    'kind' => $kind,
                    // Random file name, private disk, one folder per lead.
                    'path' => $file->store('leads/'.$lead->id, 'local'),
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                    'mime' => $file->getMimeType(),
                    'size' => (int) $file->getSize(),
                ]);
            }
        }

        $lead->notes()->create(['kind' => 'system', 'body' => 'Received from the website'.($lead->subject() ? ' about '.$lead->subject() : '').'.']);

        Notify::office(new NewLead($lead), $type);
        Notify::customer($lead->email, new LeadReceived($lead));

        // Kept (not flashed) so refreshing the thank-you page still shows the number.
        $request->session()->put('last_lead', ['reference' => $lead->reference, 'type' => $type->value]);

        return $lead;
    }

    /** Bots fill the hidden "website" field. Pretend it worked and keep nothing. */
    public static function isBot(Request $request): bool
    {
        if ($request->filled('website')) {
            $request->session()->put('last_lead', ['reference' => 'EN-'.now()->format('y').'-00000', 'type' => 'enquiry']);

            return true;
        }

        return false;
    }
}
