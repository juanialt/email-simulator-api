<?php
include_once "./config.php";
include_once "./headers.php";

function addAttachment($messageId, $path, $type, $name, $size)
{
    global $conn;

    $stmt = $conn->prepare("INSERT INTO attachments (message_id, path, type, name, size) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $messageId, $path, $type, $name, $size);
    $stmt->execute();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }

    $attachmentId = $conn->insert_id;

    return $attachmentId;
}

function getAttachments($emailId)
{
    global $conn;

    if ($stmt = $conn->prepare("SELECT attachment_id, message_id, path, type, name, size FROM attachments WHERE message_id = ?")) {
        $stmt->bind_param("i", $emailId);
        $stmt->execute();
        $stmt->bind_result($attachmentId, $messageId, $path, $type, $name, $size);

        $attachments = array();
        while ($stmt->fetch()) {
            $attachment = new Attachment($attachmentId, $messageId, $path, $type, $name, $size);
            array_push($attachments, $attachment);
        }

        $stmt->close();
        return $attachments;
    }

    return null;
}
