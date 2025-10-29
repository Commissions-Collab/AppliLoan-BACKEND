<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoanStatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $loan;

    public function __construct($user, $loan)
    {
        $this->user = $user;
        $this->loan = $loan;
    }

    public function build()
    {
        return $this->subject('Loan Application Status Update')
            ->view('emails.loan-status-update')
            ->with([
                'user' => $this->user,
                'loan' => $this->loan,
            ]);
    }
}
