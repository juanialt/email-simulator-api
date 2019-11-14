<?php
include_once "./config.php";
include_once "./headers.php";

include_once "./attachments.php";
include_once "./label_functions.php";
include_once "./model/class.Label";

use function _\split;

$requestMethod = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION["user"])) {
    logoutAndExit();
}

switch ($requestMethod) {
    case "GET":
        try {
            if (isset($_GET["folder"])) {
                $folderName = $_GET["folder"];
                $user = unserialize($_SESSION["user"]);

                switch ($folderName) {
                    case "INBOX":{
                            $messages = getUserReceivedMessages($user->id);
                            http_response_code(200);
                            echo json_encode($messages);
                            exit();
                        }
                        break;
                    case "SENT":{
                            $messages = getUserSentMessages($user->id);
                            http_response_code(200);
                            echo json_encode($messages);
                            exit();
                        }
                        break;
                    case "ALL":{
                            $messages = getUserMessages($user->id);
                            http_response_code(200);
                            echo json_encode($messages);
                            exit();
                        }
                        break;
                    default:{
                            $messages = getLabelMessages($user->id, $folderName);
                            http_response_code(200);
                            echo json_encode($messages);
                            exit();
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            showError("error de servidor", 400, $e);
        }
        break;
    case "POST":
        try {
            if (isset($_POST["from"]) && isset($_POST["to"])) {
                $user = unserialize($_SESSION["user"]);
                $from = $user->id;
                $recipients = split($_POST["to"], ",");
                $subject = $_POST["subject"];
                $htmlCode = $_POST["htmlCode"];
                $date = date('Y-m-d H:i:s');

                $files = [];
                if (isset($_FILES["files"])) {
                    $files = reArrayFiles($_FILES["files"]);
                }

                $messageId = addMessage($from, $subject, $htmlCode, $date, $recipients, $files);
                $message = getMessage($messageId);

                http_response_code(200);
                echo json_encode($message);
                exit();
            }
        } catch (Exception $e) {
            showError("error de servidor", 400, $e);
        }
        break;
    case "DELETE":
        try {
            $user = unserialize($_SESSION["user"]);
            $userId = $user->id;

            $messagesIds = array();
            parse_str(getContent(), $messagesIds);

            deleteMessages($messagesIds, $userId);

            http_response_code(200);
            echo json_encode(true);
            exit();
        } catch (Exception $e) {
            showError("error de servidor", 400, $e);
        }
        break;
}

function deleteMessages($messagesIds, $userId)
{
    global $conn;
    foreach ($messagesIds as $messageId) {
        $stmt = $conn->prepare("UPDATE user_has_message SET deleted = 1 WHERE message_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $messageId, $userId);
        $stmt->execute();
        if ($conn->error) {
            $error = new Exception($conn->error);
            throw $error;
        }
    }
    return true;
}

$content = null;
function getContent()
{
    if (null === $content) {
        if (0 === strlen(trim($content = file_get_contents('php://input')))) {
            $content = false;
        }
    }
    return $content;
}

function addMessage($from, $subject, $htmlCode, $date, $recipients, $files)
{
    global $conn;

    // ADD A NEW MESSAGE
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, subject, message, date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $from, $subject, $htmlCode, $date);
    $stmt->execute();
    $stmt->close();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }

    $messageId = $conn->insert_id;

    // ADD THE SENDER AND RECIPIENTS
    $stmt = $conn->prepare("INSERT INTO user_has_message (message_id, user_id, recipient) VALUES (?, ?, 0)");
    $stmt->bind_param("ii", $messageId, $from);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO user_has_message (message_id, user_id, recipient) VALUES (?, ?, 1)");
    foreach ($recipients as $recipient => $recipientId) {
        $stmt->bind_param("ii", $messageId, $recipientId);
        $stmt->execute();
    }
    $stmt->close();

    // ADD THE ATTACHMENTS
    foreach ($files as $file) {
        $path = "./files/" . rand(1000, 1000000) . $file["name"];
        $type = $file["type"];
        $name = $file["name"];
        $size = strval($file["size"]);

        if (move_uploaded_file($file["tmp_name"], $path)) {
            addAttachment($messageId, $path, $type, $name, $size);
        } else {
            $error = new Exception($conn->error);
            throw $error;
        }
    }

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }

    return $messageId;
}

function getMessage($messageId)
{
    global $conn;

    $sql = "SELECT
                messages.message_id,
                messages.subject,
                messages.message,
                messages.date,
                messages.sender_id,
                users.username AS sender_username,
                users.firstname AS sender_firstname,
                users.lastname AS sender_lastname
            FROM
                messages
                    INNER JOIN
                users ON users.user_id = messages.sender_id
            WHERE
                messages.message_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $stmt->bind_result(
            $id,
            $subject,
            $message,
            $date,
            $senderId,
            $senderUsername,
            $senderFirstname,
            $senderLastname
        );

        $results = $stmt->fetch();
        $stmt->close();

        if ($results) {
            $message = new Message($messageId, $senderId, $subject, $message, $date, null, null);
            $message->senderUsername = $senderUsername;
            $message->senderFirstname = $senderFirstname;
            $message->senderLastname = $senderLastname;

            $message->recipients = getRecipientsByMessageId($messageId);
            $message->attachments = getAttachments($messageId);

            return $message;
        }
    }
    return null;
}

function getUserReceivedMessages($userId)
{
    global $conn;

    $sql = "SELECT
                messages.message_id,
                messages.subject,
                messages.message,
                messages.date,
                messages.sender_id,
                users.username AS sender_username,
                users.firstname AS sender_firstname,
                users.lastname AS sender_lastname
            FROM
                messages
                    INNER JOIN
                user_has_message ON user_has_message.message_id = messages.message_id
                    INNER JOIN
                users ON users.user_id = messages.sender_id
            WHERE
                user_has_message.user_id = ? AND
                user_has_message.deleted = 0 AND
                user_has_message.recipient = 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result(
            $messageId,
            $subject,
            $message,
            $date,
            $senderId,
            $senderUsername,
            $senderFirstname,
            $senderLastname
        );

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date, null, null);
            $message->senderUsername = $senderUsername;
            $message->senderFirstname = $senderFirstname;
            $message->senderLastname = $senderLastname;
            array_push($messages, $message);
        }
        $stmt->close();

        foreach ($messages as $message) {
            $message->recipients = getRecipientsByMessageId($message->id);
            $message->attachments = getAttachments($message->id);
            $stmt->close();
            $message->labels = getLabelsByMessage($message->id, $userId);
        }

        return $messages;
    }
    return null;
}

function getUserSentMessages($userId)
{
    global $conn;
    $sql = "SELECT
                messages.message_id,
                messages.subject,
                messages.message,
                messages.date,
                messages.sender_id,
                users.username AS sender_username,
                users.firstname AS sender_firstname,
                users.lastname AS sender_lastname
            FROM
                messages
                    INNER JOIN
                user_has_message ON user_has_message.message_id = messages.message_id
                    INNER JOIN
                users ON users.user_id = messages.sender_id
            WHERE
                user_has_message.user_id = ? AND
                user_has_message.deleted = 0 AND
                user_has_message.recipient = 0";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result(
            $messageId,
            $subject,
            $message,
            $date,
            $senderId,
            $senderUsername,
            $senderFirstname,
            $senderLastname
        );

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date, null, null);
            $message->senderUsername = $senderUsername;
            $message->senderFirstname = $senderFirstname;
            $message->senderLastname = $senderLastname;
            array_push($messages, $message);
        }

        foreach ($messages as $message) {
            $message->recipients = getRecipientsByMessageId($message->id);
            $message->attachments = getAttachments($message->id);
            $stmt->close();
            $message->labels = getLabelsByMessage($message->id, $userId);
        }

        return $messages;
    }
    return null;
}

function getLabelMessages($userId, $labelName)
{
    global $conn;
    $sql = "SELECT
                tab.message_id,
                tab.subject,
                tab.message,
                tab.date,
                tab.sender_id,
                tab.sender_username,
                tab.sender_firstname,
                tab.sender_lastname
            FROM
                label_has_message
                    INNER JOIN
                labels
                    INNER JOIN
                (SELECT
                    messages.message_id,
                    messages.subject,
                    messages.message,
                    messages.date,
                    messages.sender_id,
                    users.username AS sender_username,
                    users.firstname AS sender_firstname,
                    users.lastname AS sender_lastname
                FROM
                    messages
                INNER JOIN user_has_message ON user_has_message.message_id = messages.message_id
                INNER JOIN users ON users.user_id = messages.sender_id
                WHERE
                    user_has_message.user_id = ?
                    AND
                    user_has_message.deleted = 0
                ) AS tab
            WHERE
                label_has_message.label_id = labels.label_id
                AND
                labels.name = ?
                AND
                label_has_message.message_id = tab.message_id
            GROUP BY message_id";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $userId, $labelName);
        $stmt->execute();
        $stmt->bind_result(
            $messageId,
            $subject,
            $message,
            $date,
            $senderId,
            $senderUsername,
            $senderFirstname,
            $senderLastname
        );

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date, null, null);
            $message->senderUsername = $senderUsername;
            $message->senderFirstname = $senderFirstname;
            $message->senderLastname = $senderLastname;
            array_push($messages, $message);
        }

        foreach ($messages as $message) {
            $message->recipients = getRecipientsByMessageId($message->id);
            $message->attachments = getAttachments($message->id);
            $stmt->close();
            $message->labels = getLabelsByMessage($message->id, $userId);
        }

        return $messages;
    }
    return null;
}

function getRecipientsByMessageId($messageId)
{
    global $conn;
    $sql = "SELECT
                user_has_message.user_id,
                users.username,
                users.firstname,
                users.lastname
            FROM
                user_has_message
                    INNER JOIN
                users ON users.user_id = user_has_message.user_id
            WHERE
                message_id = ?
                AND
                recipient = 1";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $messageId);
        $stmt->execute();
        $stmt->bind_result($recipientId, $username, $firstname, $lastname);

        $recipients = array();
        while ($stmt->fetch()) {
            $recipient = new stdClass();
            $recipient->id = $recipientId;
            $recipient->username = $username;
            $recipient->firstname = $firstname;
            $recipient->lastname = $lastname;
            array_push($recipients, $recipient);
        }
        return $recipients;
    }
    return null;
}

function getUserMessages($userId)
{
    global $conn;
    $sql = "SELECT
                messages.message_id,
                messages.subject,
                messages.message,
                messages.date
            FROM
                messages
            WHERE
                messages.message_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($messageId, $subject, $message, $date);

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date);
            array_push($messages, $message);
        }
        return $messages;
    }
    return null;
}
