<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AdminMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $admin_message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(\User $user, $message)
    {
        $this->user = $user;
        $this->admin_message = $message;
    }


    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.admin.admin-message')->subject('Admin message');
    }
}
