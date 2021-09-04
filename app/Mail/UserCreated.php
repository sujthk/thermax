<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserCreated extends Mailable
{
    use Queueable, SerializesModels;
    public $user_email;
    public $password;
    public $user_name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user_name,$user_email,$password)
    {
        $this->user_email = $user_email;
        $this->password = $password;
        $this->user_name = $user_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome To iChill')->view('emails.send_user_credentials');
    }
}
