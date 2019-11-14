<?php
include_once "./config.php";
include_once "./headers.php";

if (isset($_POST["username"]) && isset($_POST["password"]) && $_POST["username"] !== "" && $_POST["password"] !== "") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $user = checkUser($username, $password);

    if ($user) {
        $_SESSION["user"] = serialize($user);
        http_response_code(200);
        echo json_encode($user);
        exit();
    } else {
        showError("credenciales incorrectas", 400, $e);
    }
} else {
    showError("se requiere username y password", 400, $e);
}

function checkUser($username, $password)
{
    global $conn;

    $md5Password = md5($password);
    $sql = "SELECT
                user_id,
                username,
                firstname,
                lastname,
                address,
                phone,
                country_id,
                region_id,
                city,
                email
            FROM
                users
            WHERE
                username = ?
                AND
                password = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $username, $md5Password);
        $stmt->execute();
        $stmt->bind_result($user_id, $username, $firstname, $lastname, $address, $phone, $countryId, $regionId, $city, $email);

        if ($stmt->fetch()) {
            $user = new User($user_id, $username, $firstname, $lastname, $address, $phone, $countryId, $regionId, $city, $email);
            $stmt->close();
            return $user;
        }
    }

    return false;
}
