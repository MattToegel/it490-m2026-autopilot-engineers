<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/flight_client.php";

header("Content-Type: application/json");


$flightNumber = $_GET["flight_number"] ?? "";


if ($flightNumber === "")
{
    echo json_encode([
        "status" => "error",
        "message" => "Missing flight number"
    ]);

    exit;
}


// Wrap the message exactly how API Worker expects it
$response = sendFlightRequest(
    "search.flight",
    [
        "routing_key" => "search.flight",
        "payload" => [
            "flight_number" => $flightNumber
        ]
    ]
);


echo json_encode([
    "status" => "success",
    "flight" => $response
]);
