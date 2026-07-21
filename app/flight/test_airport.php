<?php

require_once __DIR__ . "/flight_client.php";

echo "=====================================\n";
echo "       TESTING ROUTE SEARCH\n";
echo "=====================================\n\n";


$response = sendFlightRequest(
    "search.route",
    [
        "routing_key" => "search.route",
        "payload" => [
            "origin"      => "EWR",
            "destination" => "LAX"
        ]
    ]
);


echo "\n=====================================\n";
echo "        ROUTE SEARCH RESPONSE\n";
echo "=====================================\n\n";


print_r($response);
