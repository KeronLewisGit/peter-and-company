<?php

namespace App\Notifications;

use App\Enums\LeadType;
use App\Models\Lead;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the customer or applicant: we have it, here is your reference, here is what happens next. */
class LeadReceived extends Notification
{
    public function __construct(public readonly Lead $lead) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $l = $this->lead;
        $mail = (new MailMessage)
            ->subject(peterco('short_name').": your {$this->what()} ({$l->reference})")
            ->greeting('Thank you, '.strtok($l->name, ' ').'.')
            ->line("We have your {$this->what()}. Your reference is **{$l->reference}**.");

        if ($l->type === LeadType::Order) {
            foreach ($l->lines() as $line) {
                $mail->line("{$line['cases']} × {$line['name']}".($line['pack'] ? " ({$line['pack']})" : ''));
            }
            if ($l->delivery_date) {
                $mail->line('Delivery requested for '.$l->delivery_date->format('l j F').'. This is a request until the trade desk confirms it with you.');
            }
        }

        $mail->line(match ($l->type) {
            LeadType::Order => 'The trade desk will call to confirm availability, pricing and your delivery day. Nothing is charged until the order is confirmed.',
            LeadType::Account => 'Credit control will review your application and documents and contact you. We will never ask for your bank PIN or online banking password.',
            LeadType::Principal => 'Our management team will review your brands and reply by email.',
            LeadType::Application => 'Our HR team reads every application. If your experience matches the role, we will contact you about the next step.',
            LeadType::Enquiry => 'A member of our team will get back to you.',
        });

        $phone = peterco('phone');

        return $mail
            ->line($phone ? 'Questions? Call '.format_phone((string) $phone).' and quote your reference.' : 'Questions? Reply to this email and quote your reference.')
            ->salutation(peterco('name').', '.peterco('address.area').', '.peterco('address.city'));
    }

    private function what(): string
    {
        return match ($this->lead->type) {
            LeadType::Order => 'order request',
            LeadType::Account => 'trade account application',
            LeadType::Principal => 'distribution enquiry',
            LeadType::Application => 'job application',
            LeadType::Enquiry => 'message',
        };
    }
}
