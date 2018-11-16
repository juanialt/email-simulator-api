<?php

class Message
{
    public $message_id;
    public $sender_id;
    public $subject;
    public $message;
    public $date;

    public function __construct($message_id, $sender_id, $subject, $message, $date)
    {
        $this->message_id = $message_id;
        $this->sender_id = $sender_id;
        $this->subject = $subject;
        $this->message = $message;
        $this->date = $date;
    }
}
