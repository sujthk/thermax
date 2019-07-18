<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendUserOtp extends Mailable
{
    use Queueable, SerializesModels;
    public $name;
    public $otp;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name,$otp)
    {
        $this->name = $name;
        $this->otp = $otp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Thermax Otp')->view('emails.send_user_otp');
    }
}
