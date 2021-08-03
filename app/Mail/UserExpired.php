<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserExpired extends Mailable
{
    use Queueable, SerializesModels;
    public $user_email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user_email)
    {
        $this->user_email = $user_email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('User Expired')->view('emails.user_expired');
    }
}
