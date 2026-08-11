<?php
/*Xaidyn Liranzo (xml): This is the file that fetches flight information from
the Api via AeroDataBox. This file ill be pivtol to the creation of our website
. Validation of request will be sent here. This is where we call for the API for
and recieve its raw responses. */


require_once __DIR__ . "/../config/config.php";

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

/*xml: This will build the Guzzle HTTP client that will communicate with our API*/

function buildAeroDataBoxClient(): array
{
    $apiKey = AERODATABOX_KEY;
    $baseUrl = AERODATABOX_BASE_URL;

    if ($apiKey === ""){
        return [
            "status" => "error",
            "message" => "Thers no api key."
        ];
    }

    $client = new Client([
        "base_uri" => rtrim($baseUrl, "/") . "/",
        "timeout" => 10
    ]);

    return [
        "status" => "success",
        "client" => $client,
        "api_key" => $apiKey
    ];
}

/*xml: This function basicaly takes a Guzzle exception and makes it a strong
So, if our API returns any sort of response ex on website (400 = bad request,
401 = unauthorized, etc.). Then the exception and the status error from the request
is sent  off */
function uhohException(GuzzleException $e): string
{
    if ($e instanceof RequestException && $e->hasResponse())
    {
        $response = $e->getResponse();
        $status = $response->getStatusCode();
        $bodySnippet = substr((string) $response->getBody(), 0, 500);
        return "HTTP {$status}: {$bodySnippet}";
    }

    return $e->getMessage();
}

/*xml: This function is what is in charrge of fetchig flight data by number for the
user. */
function fetchFlight(array $request): array
{
    /*xml: This is what basicallt takes in the users number request
    if the user inputted no number, then a message
    is returned to the user saying they are missing an input.*/

    $flightNumber = trim($request["flight_number"] ?? "");

    $date = $request["date"] ?? date("Y-m-d");

    if ($flightNumber === "")
    {
        return [
            "status" => "error",
            "message" => "A number must be inputted for search"
        ];
    }

    $built = buildAeroDataBoxClient();

    if ($built["status"] !== "success")
    {
        return $built;
    }

    $client = $built["client"];
    $apiKey = $built["api_key"];

    /*This is where the actual call to the API occurs. Here the endpoint is provided
     and the data is being called from the API*/
    try
    {
        $response = $client->request(

            "GET",

            "flights/number/{$flightNumber}/{$date}",
            [
                "headers" => [
                    "Accept" => "application/json",

                    "x-magicapi-key" => $apiKey
                ]
            ]
        );

        $body = json_decode(

            $response->getBody()->getContents(),

            true

        );

        /*xml: If the response from the API is empty, then and error is sent to the user
        explaing that the value they are searching currently has no results. */

        if (empty($body))
        {
            return [

                "status" => "error",

                "message" => "No flights available."

            ];
         }

        return [

            "status" => "success",

            "raw_data" => $body[0]

        ];
    }
    catch (GuzzleException $e)
    {
        return [

            "status" => "error",

            "message" => "Cannot reach AeroDataBox.",

            "error" => uhohException($e)

        ];
    }
}

/*xml: This function is in charge of fetching API request from users by the 
name of the airport. THIS IS VERY SIMILAR TO THE PROCESS ABOVE*/
function fetchAirport(array $request): array
{
    $airport = strtoupper(trim($request["airport"] ?? ""));

    if ($airport === "")
    {
        return [
            "status" => "error",
            "message" => "Must input airport"
        ];
    }

    $codeType = strlen($airport) === 4 ? "icao" : "iata";

    $offsetMinutes = $request["offset_minutes"] ?? -120;

    $durationMinutes = $request["duration_minutes"] ?? 720;

    $built = buildAeroDataBoxClient();

    if ($built["status"] !== "success")
    {
        return $built;
    }

    $client = $built["client"];
    $apiKey = $built["api_key"];

    try
    {
        /*xml:This is what fecthes the information requested from the user from our API
        this is also the endpoint for searching from the API via airport name*/
        $response = $client->request(

            "GET",

            "flights/airports/{$codeType}/{$airport}",

            [

                "query" => [

                    "offsetMinutes" => $offsetMinutes,

                    "durationMinutes" => $durationMinutes

                ],

                "headers" => [

                    "Accept" => "application/json",

                    "x-magicapi-key" => $apiKey

                ]

            ]

        );

        $body = json_decode(

            $response->getBody()->getContents(),

            true

        );

        $flights = [];

        if (isset($body["departures"]) || isset($body["arrivals"]))
        {
            $flights = array_merge(

                $body["departures"] ?? [],

                $body["arrivals"] ?? []

            );
        }
        else if (is_array($body))
        {
            $flights = $body;
        }

	//xml: This conditional checks if the flights from the users selected airport is
        //empty, if so , then it send sent to users that there is no flights in their
        //selected airport

        if (empty($flights))
        {
            return [

                "status" => "error",

                "message" => "{$airport} has no flights at the moment."

            ];
        }

        return [

            "status" => "success",

            "raw_data" => $flights

        ];
    }
    catch (GuzzleException $e)
    {
        return [

            "status" => "error",

            "message" => "cannot reach AeroDataBox.",

            "error" => uhohException($e)

        ];
    }
}

/*xml: This function is made in order tofetch data for users that are looking for 
their flights based on he route of the fligt. Like the other functions above, the 
logic behind this function is pretty much similar to them.
The only really big difference is that there is no endpoint for this
so there is a function to mitigate this issue.*/
function fetchRoute(array $request): array {
    $origin = strtoupper(trim($request["origin"] ?? ""));
    $destination = strtoupper(trim($request["destination"] ?? ""));

//This is a conditional statment that checks if there is an origin and destination provided
    if ($origin === "" || $destination === "")
    {
        return [
            "status" => "error",
            "message" => "Need both origin and destination airports."
        ];
    }

/*xml: This takes the flights departing from the origin airport */
    $airportResult = fetchAirport([

        "airport" => $origin,

        "offset_minutes" => $request["offset_minutes"] ?? -120,

        "duration_minutes" => $request["duration_minutes"] ?? 720

    ]);

    if ($airportResult["status"] !== "success")
    {
        return $airportResult;
    }
//xml: This filters the airport departing list and  only has the flights that are 
//headed to the destination airport
    $matches = array_values(

        array_filter(

            $airportResult["raw_data"],
//xml: This function de the filter ing and only lists flights that match the destination
            function ($leg) use ($destination)
            {
                $arrivalIata = strtoupper($leg["arrival"]["airport"]["iata"] ?? "");
                $arrivalIcao = strtoupper($leg["arrival"]["airport"]["icao"] ?? "");

                return $arrivalIata === $destination || $arrivalIcao === $destination;
            }

        )

    );
//xml: This message will return if there are no flights found betweenboth airports 
    if (empty($matches))
    {
        return [

            "status" => "error",

            "message" => "No flights found on route {$origin} to {$destination}."

        ];
    }
//xml: This will return the list of flights that satisfy the users search request.
    return [

        "status" => "success",

        "raw_data" => $matches

    ];
}
