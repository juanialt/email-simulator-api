<?php
include_once "./config.php";
header('Content-Type: application/json');

// Allow from any origin
if (isset($_SERVER['HTTP_ORIGIN'])) {
// should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
    // whitelist of safe domains
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // cache for 1 day
}
// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }

}



$requestMethod = $_SERVER['REQUEST_METHOD'];

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
        } else {
            $users = getUsers();
            http_response_code(200);
            echo json_encode($users);
        }
        break;
    case "POST":



    // if (!isset($_POST["nombre"])) {
    //     http_response_code(400);
    //     $result = new stdClass();
    //     $result->errorMessage = "El nombre es obligatorio";
    //     echo json_encode($result);
    //     exit;
    // }
    // $persona = new Persona(null, $_POST["nombre"], $_POST["apellido"], $_POST["edad"], $_POST["telefono"]);
    // $stmt = $conn->prepare("INSERT INTO personas (nombre, apellido, edad, telefono) VALUES (?,?,?,?)");
    // $stmt->bind_param("ssss", $persona->nombre,$persona->apellido, $persona->edad, $persona->telefono);
    // if ($stmt->execute()) {
    //     $persona->id = $conn->insert_id;
    //     $conn->commit();
    //     http_response_code(201);
    //     echo json_encode($persona);
    // } else{
    //     $result = new stdClass();
    //     $result->errorMessage = $mysqli->error;
    //     http_response_code(500);
    //     echo json_encode($result);
    //     exit;
    // }




        try {
            $name = $_POST["name"];
            $password = $_POST["password"];
            $user = new User(null, $name, $password);
            
            $user = addUser($user);

            http_response_code(200);
            echo json_encode($user);
        } catch (Exception $e) {
            $result = new stdClass();
            $result->error = $e->getMessage();
            echo json_encode($result);
            http_response_code(500);
        }
        break;
    case "PUT":
        try {
            $data = json_decode(file_get_contents('php://input'));
            $pais = $paisesController->update($_GET["id"], $data->nombre_pais);
            http_response_code(200);
            echo json_encode($pais);
        } catch (Exception $e) {
            $result = new stdClass();
            $result->error = $e->getMessage();
            echo json_encode($result);
            http_response_code(500);
        }
        break;
    case "DELETE":
        try {

            $pais = $paisesController->getById($_GET["id"]);
            if ($pais) {
                $pais = $paisesController->delete($_GET["id"]);
                http_response_code(200);
            } else {
                http_response_code(404);
            }

        } catch (Exception $e) {
            $result = new stdClass();
            $result->error = $e->getMessage();
            echo json_encode($result);
            http_response_code(500);
        }
        break;
}

function getUsers()
{
    global $conn;
    $sql = "SELECT id, name FROM users";
    $rs = $conn->query($sql);
    $users = array();
    if ($rs->num_rows > 0) {
        while ($row = $rs->fetch_assoc()) {
            array_push($users, $row);
        }
    }
    return $users;
}

function getUser($userId)
{
    global $conn;
    if ($stmt = $conn->prepare("SELECT id, name FROM users WHERE id = ?")) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($id, $name);

        if ($stmt->fetch()) {
            $user = new User($id, $name);
            $stmt->close();
            return $user;
        }
    }
    return null;
}

function addUser($user)
{
    global $conn;
    var_dump($user);
    $stmt = $conn->prepare("INSERT INTO users (name, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $user->name, $user->password);
    $stmt->execute();

    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }
    $user->id = $conn->insert_id;
    return $user;
}

function updateUser($user)
{
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->bind_param("ss", $user->name, $user->id);
    $stmt->execute();
    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }
    return $user;
}

function deleteUser($userId)
{
    global $conn;
    $stmt = $conn->prepare("DELETE FROM users where id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    if ($conn->error) {
        $error = new Exception($conn->error);
        throw $error;
    }
}
