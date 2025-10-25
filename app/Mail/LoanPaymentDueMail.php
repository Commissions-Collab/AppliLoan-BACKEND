<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanPaymentDueMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $loan;
    public $dueDate;
    public $daysRemaining;

    public function __construct($user, $loan, $dueDate, $daysRemaining)
    {
        $this->user = $user;
        $this->loan = $loan;
        $this->dueDate = $dueDate;
        $this->daysRemaining = $daysRemaining;
    }

    public function build()
    {
        return $this->subject('Loan Payment Reminder')
            ->view('emails.loan-payment-due')
            ->with([
                'user' => $this->user,
                'loan' => $this->loan,
                'dueDate' => $this->dueDate,
                'daysRemaining' => $this->daysRemaining,
            ]);
    }
}
