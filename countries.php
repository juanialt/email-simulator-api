<?php
include_once "./config.php";
include_once "./headers.php";

use function _\groupBy;
use function _\map;

$requestMethod = $_SERVER["REQUEST_METHOD"];

switch ($requestMethod) {
    case "GET":
        try {
            $countries = getCountries();
            http_response_code(200);
            echo json_encode($countries);
            exit();
        } catch (Exception $e) {
            showError("error de servidor", 400, $e);
        }
        break;
}

function getCountries()
{
    global $conn;
    $sql = "SELECT
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
                country.id = region.country_id
            ORDER BY
                country.name, region.name ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $stmt->bind_result($countryId, $countryName, $countryCode, $regionId, $regionName, $regionCode);

        $countryRegions = array();
        while ($stmt->fetch()) {
            $object = new stdClass();
            $object->countryId = $countryId;
            $object->countryName = $countryName;
            $object->countryCode = $countryCode;
            $object->regionId = $regionId;
            $object->regionName = $regionName;
            $object->regionCode = $regionCode;

            array_push($countryRegions, $object);
        }

        $grouped = groupBy($countryRegions, function ($country) {
            return $country->countryId;
        });

        return map($grouped, function ($country, $countryId) {
            $id = $countryId;
            $name = $country[0]->countryName;
            $code = $country[0]->countryCode;

            $regions = map($country, function ($region) {
                $regionId = $region->regionId;
                $regionName = $region->regionName;
                $regionCode = $region->$regionCode;

                return new Region($regionId, $regionName, $regionCode);
            });

            return new Country($id, $name, $code, $regions);
        });
    }
    return null;
}
