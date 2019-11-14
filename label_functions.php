<?php

function getUserLabels($userId)
{
    global $conn;

    if ($stmt = $conn->prepare("SELECT label_id, name, user_id FROM labels WHERE user_id = ?")) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($labelId, $name, $userId);

        $labels = array();
        while ($stmt->fetch()) {
            $label = new Label($labelId, $userId, $name);
            array_push($labels, $label);
        }
        return $labels;
    }

    return null;
}

function checkLabelName($userId, $labelName)
{
    global $conn;

    if ($stmt = $conn->prepare("SELECT name FROM labels WHERE user_id = ? AND name = ?")) {
        $stmt->bind_param("is", $userId, $labelName);
        $stmt->execute();

        if ($stmt->fetch()) {
            $stmt->close();
            return true;
        }
    }
    return false;
}

function addLabel($label)
{
    global $conn;

    $stmt = $conn->prepare("INSERT INTO labels (user_id, name) VALUES (?, ?)");
    $stmt->bind_param("is", $label->userId, $label->name);
    $stmt->execute();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }

    $label->id = $conn->insert_id;

    return $label;
}

function getLabelsByMessage($messageId, $userId)
{
    global $conn;
    $sql = "SELECT
                labels.label_id,
                labels.name
            FROM
                labels
            INNER JOIN
                label_has_message ON labels.label_id = label_has_message.label_id
            WHERE
                message_id = ?
            AND
                label_has_message.user_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $messageId, $userId);
        $stmt->execute();
        $stmt->bind_result($labelId, $labelName);

        $labels = array();
        while ($stmt->fetch()) {
            $label = new stdClass();
            $label->id = $labelId;
            $label->name = $labelName;
            array_push($labels, $label);
        }
        return $labels;
    }
    return null;
}

function removeMessageLabels($labels, $messages, $userId) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM label_has_message WHERE label_id = ? AND message_id = ? AND user_id = ?");

    foreach ($messages as $message => $messageId) {
        foreach ($labels as $label => $labelId) {
            $stmt->bind_param("iii", $labelId, $messageId, $userId);
            $stmt->execute();
        }
    }
    $stmt->close();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }
    return true;
}

function setLabelToMessage($labels, $messages, $userId)
{
    global $conn;

    $stmt = $conn->prepare("INSERT INTO label_has_message (label_id, message_id, user_id) VALUES (?, ?, ?)");

    foreach ($messages as $message => $messageId) {
        foreach ($labels as $label => $labelId) {
            $stmt->bind_param("iii", $labelId, $messageId, $userId);
            $stmt->execute();
        }
    }
    $stmt->close();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }

    return true;
}

function deleteLabel($labelId, $userId) {
    global $conn;

    $stmt = $conn->prepare("DELETE FROM label_has_message WHERE label_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $labelId, $userId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM labels WHERE label_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $labelId, $userId);
    $stmt->execute();
    $stmt->close();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }
    return true;
}