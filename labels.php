<?php
include_once "./config.php";
include_once "./headers.php";
include_once "./label_functions.php";

$requestMethod = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION["user"])) {
    logoutAndExit();
}

switch ($requestMethod) {
    case "GET":
        try {
            if (isset($_GET["id"])) {
                $userId = $_GET["id"];
                $label = getLabel($userId);

                if ($label != null) {
                    http_response_code(200);
                    echo json_encode($label);
                    exit();
                } else {
                    showError("no se encuentra etiqueta con ese ID", 400, $e);
                }
            } else {
                $user = unserialize($_SESSION["user"]);

                $labels = getUserLabels($user->id);
                http_response_code(200);
                echo json_encode($labels);
                exit();
            }
        } catch (Exception $e) {
            showError("error de servidor", 400, $e);
        }
        break;
    case "POST":
        try {
            $user = unserialize($_SESSION["user"]);

            if (isset($user)) {
                // SET LABEL TO EMAIL
                if (isset($_POST["emails"])) {
                    if (isset($_POST["selectLabels"]) || isset($_POST["deleteLabels"])) {
                        $selectLabels = $_POST["selectLabels"];
                        $deleteLabels = $_POST["deleteLabels"];
                        $emails = $_POST["emails"];

                        removeMessageLabels($deleteLabels, $emails, $user->id);
                        setLabelToMessage($selectLabels, $emails, $user->id);

                        http_response_code(200);
                        echo json_encode(true);
                        exit();
                    } else {
                        showError("debe seleccionar una etiqueta", 400);
                    }
                }

                // CREATE NEW LABEL
                if (isset($_POST["name"])) {
                    $name = $_POST["name"];
                    $label = new Label(null, $user->id, $name);

                    if (checkLabelName($label->userId, $label->name) == false) {
                        $label = addLabel($label);

                        http_response_code(200);
                        echo json_encode($label);
                        exit();
                    } else {
                        showError("ya existe una etiqueta con ese nombre", 400);
                    }
                } else {
                    showError("se requiere un nombre para crear la etiqueta", 400);
                }
            }
        } catch (Exception $e) {
            showError("error de servidor", 400, $e);
        }
        break;
    case "DELETE":
        try {
            $user = unserialize($_SESSION["user"]);

            if (isset($user)) {
                $userId = $user->id;

                $data;
                parse_str(getContent(), $data);

                $labelId = (int) $data["labelId"];

                deleteLabel($labelId, $userId);

                http_response_code(200);
                echo json_encode(true);
                exit();
            }
        } catch (Exception $e) {
            showError("error de servidor", 400, $e);
        }
        break;
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
