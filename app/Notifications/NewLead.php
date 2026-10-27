<?php

namespace App\Notifications;

use App\Enums\LeadType;
use App\Models\Lead;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * To the office: a new request to act on. Credit details, documents and CVs stay in the
 * back office and are never put in an email.
 */
class NewLead extends Notification
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
        $who = $l->business_name ? "{$l->business_name} ({$l->name})" : $l->name;
        $mail = (new MailMessage)
            ->subject("{$l->type->label()} {$l->reference}: {$who}")
            ->line($who.' · '.format_phone($l->phone).($l->email ? " · {$l->email}" : ''));

        if ($l->tradeAccount) {
            $mail->line('Trade account: '.$l->tradeAccount->number.' ('.$l->tradeAccount->status->label().')');
        }
        if ($subject = $l->subject()) {
            $mail->line($subject);
        }
        if ($l->type === LeadType::Order) {
            foreach ($l->lines() as $line) {
                $mail->line("{$line['cases']} × {$line['name']}".($line['pack'] ? " ({$line['pack']})" : ''));
            }
            if ($l->delivery_date) {
                $mail->line('Delivery requested for '.$l->delivery_date->format('l j F').'.');
            }
        }

        $mail->line(match ($l->type) {
            LeadType::Account => 'Business details and documents are in the back office (credit control only).',
            LeadType::Application => 'The CV is in the back office (HR only).',
            default => (string) $l->message,
        });

        $mail->line('Response target: '.$l->type->targetLabel().'.')
            ->action('Open the request', route('admin.leads.show', $l));

        if ($l->email && $l->type !== LeadType::Application) {
            $mail->replyTo($l->email, $l->name);
        }

        return $mail;
    }
}
