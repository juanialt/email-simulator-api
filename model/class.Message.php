<?php

class Message
{
    public $id;
    public $senderId;
    public $subject;
    public $message;
    public $date;

    public function __construct($id, $senderId, $subject, $message, $date, $recipients, $attachments)
    {
        $this->id = $id;
        $this->senderId = $senderId;
        $this->subject = $subject;
        $this->message = $message;
        $this->date = $date;

        $this->recipients = $recipients;
        $this->attachments = $attachments;
    }
}
