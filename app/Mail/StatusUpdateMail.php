<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StatusUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $status;

    /**
     * Create a new message instance.
     */
    public function __construct($member, $status)
    {
        $this->member = $member;
        $this->status = $status;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = 'Membership Status Update';
        return $this->subject($subject)
            ->view('emails.status-update')
            ->with([
                'member' => $this->member,
                'status' => $this->status,
            ]);
    }
}
