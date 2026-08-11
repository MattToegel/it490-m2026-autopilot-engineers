<?php

//xml: This loads the flight_client file where the function that we are going to use (snedFlightRequest) is at
require_once __DIR__ . "/flight_client.php";

header("Content-Type: application/json");

/*xml: This is in charge of trimming the URL and only taking the flight number value when the specific flight
is updated*/
$flightNumber = trim($_GET["flight_number"] ?? "");

/*xml: This checks to make sure that the flight number exists within the URL, if not 
then that means the there is a missing flight number and this error is returned
The script is then stopped */
if ($flightNumber === "")
{
    echo json_encode([
        "status" => "error",
        "message" => "No flight number"
    ]);

    exit;
}
/*xml: This try block is very important to the functionality of the refresh button*/
try
{
    /*xml: This is the what sends the flight number found to the API worker through RabbitMQ
    . A flight search is then conducted in order to get the most recent information about the flight
    . The flight is checked if it is in the cached databas table and if not then a search is executed 
     From the API worker. 
    */

    $response = sendFlightRequest(
        "search.flight",
        [
            "routing_key" => "search.flight",
            "payload" => [
                "flight_number" => $flightNumber
            ]
        ]
    );

    /*xml: If the worker was able to get a flights then we take that information and send it over
    to the dashboard to display to the user*/

    if (($response["status"] ?? "") === "success")
    {
        echo json_encode([
            "status" => "success",
            "flight" => $response["flight"] ?? null
        ]);

        exit;
    }
    /*xml: This error is displayed if the API worker was not able to recieve flight information from the 
    ; therefore, this message is displayed and cached informtion is dispayed to users*/
    echo json_encode([
        "status" => "error",
        "message" =>
            $response["message"]
            ?? "Live flight information is currently unavailable."
    ]);

    exit;
}
catch (Exception $e)
{
    /*xml:  This is a  catch for when the API Wroker itself never sends back a response. So, again this error is 
    there to notify users and shows cached information 
     */

    echo json_encode([
        "status" => "error",
        "message" =>
            "Live flight updates are currently unavailable. Showing cached data."
    ]);

    exit;
}
