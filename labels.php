<?php
include_once "./config.php";
include_once "./headers.php";

$requestMethod = $_SERVER['REQUEST_METHOD'];

switch ($requestMethod) {
    case "GET":
        try {
            if (isset($_GET["id"])) {
                $userId = $_GET["id"];
                $label = getLabel($userId);

                if ($label != null) {
                    http_response_code(200);
                    echo json_encode($label);
                } else {
                    http_response_code(404);
                }
            } else {
                $user = unserialize($_SESSION["user"]);

                $labels = getUserLabels($user->id);
                http_response_code(200);
                echo json_encode($labels);
            }
        } catch (Exception $e) {
            showError("error de servidor", 400, $e);
        }
        break;
    case "POST":
        try {
            $user = unserialize($_SESSION["user"]);
            $name = $_POST["name"];

            $label = new Label(null, $user->id, $name);

            if (checkLabelName($label->userId, $label->name) == false) {
                $label = addLabel($label);

                http_response_code(200);
                echo json_encode($label);
            } else {
                showError("ya existe una etiqueta con ese nombre", 400);
            }
        } catch (Exception $e) {
            showError("error de servidor", 400, $e);
        }
        break;
}

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
