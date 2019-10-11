<?php

class Attachment
{
    public $id;
    public $messageId;
    public $path;
    public $type;

    public function __construct($id, $messageId, $path, $type, $name, $size)
    {
        $this->id = $id;
        $this->messageId = $messageId;
        $this->path = $path;
        $this->type = $type;
        $this->name = $name;
        $this->size = $size;
    }
}
