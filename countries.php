<?php
include_once "./config.php";
include_once "./lodash.php";
// phpinfo();

// use function _\groupBy;
use function _\map;
use function _\groupBy;

// $xx = groupBy([6.1, 4.2, 6.3], 'floor');
// var_dump($xx);



// var_dump($map);

// die;


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

// /countries
// /states

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
            $countries = getCountries();
            http_response_code(200);
            echo json_encode($countries);
        }
        break;
}

function getCountries()
{
    global $conn;

    $query = "SELECT
    country.id AS country_id,
    country.name AS country_name,
    country.code AS country_code,
    region.id AS region_id,
    region.name AS region_name,
    region.code AS region_code
    FROM
        email_simulator.countries AS country
            INNER JOIN
        email_simulator.regions AS region
    WHERE
        country.id = region.country_id;
    ";

    if ($stmt = $conn->prepare($query)) {
        $stmt->execute();
        $stmt->bind_result($countryId, $countryName, $countryCode, $regionId, $regionName, $regionCode);

        $countries = array();
        while ($stmt->fetch()) {

            $object = new stdClass();
            $object->countryId = $countryId;
            $object->countryName = $countryName;
            $object->countryCode = $countryCode;
            $object->regionId = $regionId;
            $object->regionName = $regionName;
            $object->regionCode = $regionCode;

            array_push($countries, $object);
        }

        // return group_by("countryId", $countries);

        // $aux = [];
        // foreach($countries as $country) {
        //     $aux[$country->countryId]["regions"][] = $country;
        // }
        // // $aux2 = [];
        // // foreach($aux as $country) {
        // //     $object = new stdClass();
        // //     $object->countryId = $countryId;
        // //     $object->countryName = $countryName;

        // //     array_push($aux, $object);
        // // }
        // return $aux;


// return $countries;



// return map($countries, function($country) {
//     return $country->countryId;
// });


        $gb = groupBy($countries, function($country) {
            return $country->countryId;
        });

        return map($gb, function ($rs, $k) {

            $object = new stdClass();
            $object->id = $k;
            $object->name = $rs[0]->countryName;
            $object->code = $rs[0]->countryCode;

            $object->regions = map($rs, function ($r) {
                $object = new stdClass();
                $object->id = $r->regionId;
                $object->name = $r->regionName;
                $object->code = $r->regionCode;
                return $object;
            });

            return $object;
        });

        // echo "OK";


    }
    return null;
}

// {
//     "argentina": {
//         id: 1,
//         name: "argentina",
//         regions: [...],
//         population: 222
//     }
// }

// [{
//     id: 1,
//     name: "argentina",
//     regions: [...],
//     population: 222
// }]

function group_by($key, $data) {
    $result = array();

    foreach($array as $val) {
        if(array_key_exists($key, $val)){
            $result[$val[$key]][] = $val;
        }else{
            $result[""][] = $val;
        }
    }

    return $result;
}

// function getUsers()
// {
//     global $conn;
//     $sql = "SELECT user_id, firstname FROM users";
//     $rs = $conn->query($sql);
//     $users = array();
//     if ($rs->num_rows > 0) {
//         while ($row = $rs->fetch_assoc()) {
//             array_push($users, $row);
//         }
//     }
//     return $users;
// }

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

function addUser($user, $password)
{
    global $conn;
    $md5Password = md5($password);
    $stmt = $conn->prepare("INSERT INTO users (username, password, firstname, lastname) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss",
        $user->username,
        $md5Password,
        $user->firstname,
        $user->lastname
    );
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

function getUserByUsername($username)
{
    global $conn;

    if ($stmt = $conn->prepare("SELECT id, username, firstname, lastname FROM users WHERE username = ?")) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($id, $username, $firstname, $lastname);

        if ($stmt->fetch()) {
            $user = new User($id, $username, $firstname, $lastname);
            $stmt->close();
            return $user;
        }
    }
    return null;
}
