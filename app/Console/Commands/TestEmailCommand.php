<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email {email : The email address to send test email to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email configuration by sending a sample OTP email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Sending test email to: {$email}");
        
        try {
            // Send a test OTP email
            Mail::to($email)->send(new VerificationCodeMail('123456', 'signup'));
            
            $this->info('✅ Test email sent successfully!');
            $this->info('Check your email inbox for the verification code email.');
            $this->info('If using Mailtrap, check your Mailtrap inbox.');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email: ' . $e->getMessage());
            $this->info('Check your .env email configuration and try again.');
        }
    }
}
