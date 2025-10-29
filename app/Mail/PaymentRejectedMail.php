<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $payment;
    public $reason;

    public function __construct($user, $payment, $reason = null)
    {
        $this->user = $user;
        $this->payment = $payment;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('Payment Rejected - AppliLoan')
            ->view('emails.payment-rejected')
            ->with([
                'user' => $this->user,
                'payment' => $this->payment,
                'reason' => $this->reason,
            ]);
    }
}
