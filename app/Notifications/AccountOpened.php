<?php

namespace App\Notifications;

use App\Models\TradeAccount;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** To the customer, when credit control approves a trade account application. */
class AccountOpened extends Notification
{
    public function __construct(public readonly TradeAccount $account) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $a = $this->account;

        return (new MailMessage)
            ->subject(peterco('short_name').": your trade account {$a->number} is open")
            ->greeting('Welcome, '.strtok($a->contact_name, ' ').'.')
            ->line("Your trade account for {$a->business_name} is open. Your account number is **{$a->number}**.")
            ->line('Payment terms: '.$a->terms->label().'.')
            ->line('Quote the account number on the online order request form, or when you speak to your sales representative, and your orders are linked to your account.')
            ->action('Request an order', route('order.show'))
            ->salutation(peterco('name').', '.peterco('address.area').', '.peterco('address.city'));
    }
}
