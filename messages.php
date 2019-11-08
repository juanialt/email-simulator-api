<?php
include_once "./config.php";
include_once "./lodash.php";

include_once "./attachments.php";
include_once "./label_functions.php";
include_once "./model/class.Label";

use function _\split;

header('Content-Type: application/json');

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
    // you want to allow, and if so:
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
    // may also be using PUT, PATCH, HEAD etc
    {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }

    exit(0);
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION["user"])) {
    utils.logoutAndExit();
  }

switch ($requestMethod) {
    case "GET":
        if (isset($_GET["id"])) {
            $userId = $_GET["id"];
            $user = getUser($userId);

            if ($user != null) {
                http_response_code(200);
                echo json_encode($user);
            } else {
                http_response_code(404);
            }
            exit;
        }

        if (isset($_GET["folder"])) {

            if (isset($_SESSION["user"])) {

                $folderName = $_GET["folder"];
                $user = unserialize($_SESSION["user"]);

                switch ($folderName) {
                    case "INBOX":{
                            $messages = getUserReceivedMessages($user->id);
                            http_response_code(200);
                            echo json_encode($messages);
                            break;
                        }
                    case "SENT":{
                            $messages = getUserSentMessages($user->id);
                            http_response_code(200);
                            echo json_encode($messages);
                            break;
                        }
                    case "ALL":{
                            $messages = getUserMessages($user->id);
                            http_response_code(200);
                            echo json_encode($messages);
                            break;
                        }
                    default:{
                            $messages = getLabelMessages($user->id, $folderName);
                            http_response_code(200);
                            echo json_encode($messages);
                            break;
                        }
                }
            } else {
                echo "NO SESSSION";
            }
        }
        break;
    case "POST":
        if (isset($_SESSION["user"])) {
            if (isset($_POST["from"]) && isset($_POST["to"])) {
                $user = unserialize($_SESSION["user"]);
                $from = $user->id;
                $recipients = split($_POST["to"], ",");
                $subject = $_POST["subject"];
                $htmlCode = $_POST["htmlCode"];
                $date = date('Y-m-d H:i:s');

                // $files = $_POST["files"];
                // var_dump($files);
                // var_dump($_POST["files"]);
                // var_dump($_POST);
                // var_dump($_FILES["files"]);

                // $target_dir = "./juani/";
                // $target_file = $target_dir . basename($_FILES["files"]["name"][0]);

                // $image = file_get_contents('http://www.affiliatewindow.com/logos/1961/logo.gif');
                // file_put_contents('./myDir/myFile.gif', $image);

                // echo($_FILES["files"]["tmp_name"][0]);
                // echo("\n");
                // echo($target_file);

                // file_put_contents($_FILES["files"]["tmp_name"][0], $target_file);

                // var_dump($_FILES["files"]);

                //type
                //size

                $files = [];
                if (isset($_FILES["files"])) {
                    $files = reArrayFiles($_FILES["files"]);
                }

                // var_dump($_FILES["files"]["tmp_name"][0]);

                // if (move_uploaded_file($_FILES["files"]["tmp_name"][0], "./files/".rand(1000,1000000).$_FILES['files']['name'][0])) {
                //     echo "done";
                //     exit;
                // }

                // echo "failed";

                // move_uploaded_file();

                // exit;

                // $message = new Message(null, $from, $subject, $htmlCode, $date, $recipients, $files);
                $messageId = addMessage($from, $subject, $htmlCode, $date, $recipients, $files);
                $message = getMessage($messageId);

                http_response_code(200);
                echo json_encode($message);
            }
        } else {
            echo "NO SESSION";
        }
        break;
    case "PUT":
        echo "ACTUALIZAR!";
        break;
    case "DELETE":
        if (isset($_SESSION["user"])) {
            $user = unserialize($_SESSION["user"]);
            $userId = $user->id;

            $messagesIds = array();
            parse_str(getContent(), $messagesIds);

            deleteMessages($messagesIds, $userId);

            http_response_code(200);
            echo json_encode(true);
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

    $query = "SELECT
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
        messages.message_id = ?
    ";

    if ($stmt = $conn->prepare($query)) {
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

    $query = "SELECT
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

    if ($stmt = $conn->prepare($query)) {
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
            // $label = new Label();
            // $message->labels = $label->getLabelsByMessage($message->id, $userId);
        }

        return $messages;
    }
    return null;
}

function getUserSentMessages($userId)
{
    global $conn;
    $query = "SELECT
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

    if ($stmt = $conn->prepare($query)) {
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
    $query = "SELECT
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

    if ($stmt = $conn->prepare($query)) {
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

function getUserSentMessages2($senderId)
{
    global $conn;
    $query = "SELECT
        messages.message_id,
        messages.sender_id,
        messages.subject,
        messages.message,
        messages.date,
        recipients.recipient_id,
        users.firstname,
        users.lastname,
        users.username
    FROM
        messages
            INNER JOIN
        recipients
            INNER JOIN
        users ON users.user_id = recipients.recipient_id
    WHERE
        messages.message_id = recipients.message_id
        AND
        messages.sender_id = ?
    ORDER BY message_id;
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $senderId);
        $stmt->execute();
        $stmt->bind_result(
            $messageId,
            $senderId,
            $subject,
            $message,
            $date,
            $recipientId,
            $recipientFirstName,
            $recipientLastName,
            $recipientUsername
        );

        $messages = array();
        while ($stmt->fetch()) {
            $message = new Message($messageId, $senderId, $subject, $message, $date);
            $message->recipientId = $recipientId;
            $message->recipientUsername = $recipientUsername;
            array_push($messages, $message);
        }

        $newArray = array();
        $i = 0;
        while ($i < count($messages)) {
            $newMessage = new Message(
                $messages[$i]->id,
                $messages[$i]->senderId,
                $messages[$i]->subject,
                $messages[$i]->message,
                $messages[$i]->date
            );

            $recipients = array();
            while ($i < count($messages) && $messages[$i]->id == $newMessage->id) {
                $recipient = new StdClass();
                $recipient->id = $messages[$i]->recipientId;
                array_push($recipients, $recipient);
                $i = $i + 1;
            }

            $newMessage->recipients = $recipients;
            array_push($newArray, $newMessage);
        }

        return $newArray;
    }
    return null;
}

function getRecipientsByMessageId($messageId)
{
    global $conn;

    $query = "SELECT
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
        recipient = 1;
    ";

    if ($stmt = $conn->prepare($query)) {
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
    $query = "SELECT
        messages.message_id,
        messages.subject,
        messages.message,
        messages.date,
    FROM
        messages
    WHERE
        messages.message_id = ?
    ";

    if ($stmt = $conn->prepare($query)) {
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

// function getUserReceivedMessages2($recipientId)
// {
//     global $conn;
//     $query = "SELECT
//         messages.message_id,
//         messages.subject,
//         messages.message,
//         messages.date,
//         recipients.recipient_id,
//         users.username AS recipient_username,
//         users.firstname AS recipient_firstname,
//         users.lastname AS recipient_lastname,
//         messages.sender_id,
//         u1.username AS sender_username,
//         u1.firstname AS sender_firstname,
//         u1.lastname AS sender_lastname
//     FROM
//         messages
//             INNER JOIN
//         recipients ON recipients.message_id = messages.message_id
//             INNER JOIN
//         users ON users.user_id = recipients.recipient_id
//             LEFT JOIN
//         users AS u1 ON u1.user_id = messages.sender_id
//     WHERE
//         recipients.recipient_id = ?
//     ";

//     if ($stmt = $conn->prepare($query)) {
//         $stmt->bind_param("i", $recipientId);
//         $stmt->execute();
//         $stmt->bind_result(
//             $messageId,
//             $subject,
//             $message,
//             $date,
//             $recipientId,
//             $recipientUsername,
//             $recipientFirstname,
//             $recipientLastname,
//             $senderId,
//             $senderUsername,
//             $senderFirstname,
//             $senderLastname
//         );

//         $messages = array();
//         while ($stmt->fetch()) {
//             $message = new Message($messageId, $senderId, $subject, $message, $date);
//             $message->recipientId = $recipientId;
//             $message->recipientUsername = $recipientUsername;
//             $message->recipientFirstname = $recipientFirstname;
//             $message->recipientLastname = $recipientLastname;
//             $message->senderUsername = $senderUsername;
//             $message->senderFirstname = $senderFirstname;
//             $message->senderLastname = $senderLastname;
//             array_push($messages, $message);
//         }
//         return $messages;
//     }
//     return null;
// }

// function addMessage2222($message)
// {
//     global $conn;

//     // ADD A NEW MESSAGE
//     $stmt = $conn->prepare("INSERT INTO messages (sender_id, subject, message, date) VALUES (?, ?, ?, ?)");
//     $stmt->bind_param("isss", $message->senderId, $message->subject, $message->message, $message->date);
//     $stmt->execute();

//     if ($conn->error) {
//         $error = new Exception($conn->error);
//         throw $error;
//     }
//     $message->id = $conn->insert_id;

//     // ADD THE RECIPIENTS
//     foreach ($message->recipients as $recipient => $recipientId) {
//         $stmt = $conn->prepare("INSERT INTO recipients (recipient_id, message_id) VALUES (?, ?)");
//         $stmt->bind_param("ii", $recipientId, $message->id);
//         $stmt->execute();
//     }

//     // ADD THE ATTACHMENTS
//     foreach ($message->attachments as $attachment) {
//         $path = "./files/".rand(1000,1000000).$attachment["name"];
//         if (move_uploaded_file($attachment["tmp_name"], $path)) {
//             $attachment = addAttachment(new Attachment(null, $message->id, $path, $attachment["type"]));
//         } else {
//             $error = new Exception($conn->error);
//             throw $error;
//         }
//     }

//     if ($conn->error) {
//         $error = new Exception($conn->error);
//         throw $error;
//     }

//     return true;
// }

// function getUserInbox($userId)
// {
//     global $conn;
//     if ($stmt = $conn->prepare("SELECT * FROM messages WHERE sender_id = ?")) {
//         $stmt->bind_param("i", $senderId);
//         $stmt->execute();
//         $stmt->bind_result($messageId, $senderId, $subject, $message, $date);

//         $messages = array();
//         while ($stmt->fetch()) {
//             $message = new Message($messageId, $senderId, $subject, $message, $date);
//             array_push($messages, $message);
//         }
//         return $messages;
//     }
//     return null;
// }

// function getUserSentMessages2($senderId)
// {
//     global $conn;
//     $query = "SELECT
//         message_id,
//         sender_id,
//         subject,
//         message,
//         date,
//         firstname as sender_name,
//         lastname as sender_lastname
//     FROM
//         messages
//         INNER JOIN
//             users ON messages.sender_id = users.user_id
//     WHERE
//         sender_id = ?
//     ";

//     if ($stmt = $conn->prepare($query)) {
//         $stmt->bind_param("i", $senderId);
//         $stmt->execute();
//         $stmt->bind_result($messageId, $senderId, $subject, $message, $date, $senderFirstname, $senderLastname);

//         $messages = array();
//         while ($stmt->fetch()) {
//             $message = new Message($messageId, $senderId, $subject, $message, $date);
//             $message->senderFirstname = $senderFirstname;
//             $message->senderLastname = $senderLastname;

//             // $recipients = getRecipientsByMessageId($messageId);
//             // $message->recipients = $recipients;
//             array_push($messages, $message);
//         }

//         foreach ($messages as $message) {
//             // var_dump($message->id);
//             // var_dump(getRecipientsByMessageId(55));

//             $message->recipients = getRecipientsByMessageId($message->id);
//         }

//         // var_dump(getRecipientsByMessageId(55));

//         return $messages;
//     }
//     return null;
// }
