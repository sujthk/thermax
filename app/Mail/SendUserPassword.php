<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendUserPassword extends Mailable
{
    use Queueable, SerializesModels;
     public $name;
     public $email_token;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name,$email_token)
    {
        $this->email_token = $email_token;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
         return $this->subject('Password Reset Link')->view('emails.password_reset');
    }
}
