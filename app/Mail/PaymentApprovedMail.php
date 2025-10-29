<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $payment;
    public $loan;

    public function __construct($user, $payment, $loan = null)
    {
        $this->user = $user;
        $this->payment = $payment;
        $this->loan = $loan;
    }

    public function build()
    {
        return $this->subject('Payment Received - AppliLoan')
            ->view('emails.payment-approved')
            ->with([
                'user' => $this->user,
                'payment' => $this->payment,
                'loan' => $this->loan,
            ]);
    }
}
