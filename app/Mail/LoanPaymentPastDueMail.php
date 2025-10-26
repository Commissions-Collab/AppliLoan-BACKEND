<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanPaymentPastDueMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $loan;
    public $dueDate;
    public $daysPast;

    public function __construct($user, $loan, $dueDate, $daysPast)
    {
        $this->user = $user;
        $this->loan = $loan;
        $this->dueDate = $dueDate;
        $this->daysPast = $daysPast;
    }

    public function build()
    {
        return $this->subject('Past Due Loan Payment Notice')
            ->view('emails.loan-payment-past-due')
            ->with([
                'user' => $this->user,
                'loan' => $this->loan,
                'dueDate' => $this->dueDate,
                'daysPast' => $this->daysPast,
            ]);
    }
}
