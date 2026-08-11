<?php

/*xml:
API Worker VM
This file is the most important file to the identity of the worker. This file takes in
request for the API via RabbitMQ. It also checks the chached flights on the database VM 
and makes sure that if the users requsted flight is not up to par with the APIs information
then the cache info is changed to  reflect the newest data. Raw data from the API is also 
transformed in order to ensure that it will save well on the databse. Responces will be published to
the App server. If there is any error, then a message will be prompted. All events that occur in 
this worker will be logged and sent off to the database log table.*/

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . '/logging/api_log.php';
require_once __DIR__ . "/config/config.php";
require_once __DIR__ . "/flights/flight_fetcher.php";
require_once __DIR__ . "/flights/Flighttransformer.php";
require_once __DIR__ . "/flights/Flightpublisher.php";



use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

echo "Staring up API Worker ... \n";

/*xml: Starts the conection for RabbitMQ*/

$connection = new AMQPStreamConnection(

    RABBITMQ_HOST,

    RABBITMQ_PORT,

    RABBITMQ_USER,

    RABBITMQ_PASSWORD

);

$channel = $connection->channel();

echo "Connected to RabbitMQ\n";
echo "Listening on queue: api.requests\n";
echo "Waiting for search requests...\n";

/*xml: This function serves as sending a message. That way it is reusable I wouldn't have to
write out this sending message code ou a million times. It also takes the PHP array converted
information and converts it to JSON and sends the message off to the rightful exchange using the routing key*/

function publishRabbitMessage(
    string $exchange,
    string $routingKey,
    array $payload,
    array $properties = []
)
{
    global $channel;

    $message = new AMQPMessage(

        json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES
        ),

        array_merge(

            [

                "content_type" => "application/json",

                "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT

            ],

            $properties

        )

    );

    $channel->basic_publish(

        $message,

        $exchange,

        $routingKey

    );

    echo "\n";
    echo "-------------------------------------\n";
    echo "Published RabbitMQ Message\n";
    echo "Exchange : {$exchange}\n";
    echo "Routing  : {$routingKey}\n";
    echo "-------------------------------------\n";
}

/*AC2: xml: This function is what basically asks the db if it already
has the flight within the table. It will send the flight back if it
is fresh enough, the result will be null if it is no. It will then notify
saying it will resort to calling our API  */
function lookupCachedFlight(array $criteria, ?array &$previousFlight = null): ?array
{
    global $channel;

    $previousFlight = null;

    $replyQueue = null;
    $consumerTag = null;

    try
    {
        //xml: Temp exclusive reply queue
        $result = $channel->queue_declare(
            "",
            false,
            false,
            true,
            true
        );

        $replyQueue = $result[0];

        if (empty($replyQueue))
        {
            throw new Exception("Failed to create temporary reply queue.");
        }

        echo "Reply queue: {$replyQueue}\n";

        $correlationId = uniqid("lookup_", true);

        $reply = null;

        $consumerTag = $channel->basic_consume(
            $replyQueue,
            "",
            false,
            true,
            false,
            false,
            function (AMQPMessage $message) use (&$reply, $correlationId)
            {
                if (
                    $message->has("correlation_id") &&
                    $message->get("correlation_id") === $correlationId
                )
                {
                    $reply = json_decode($message->body, true);
                }
            }
        );

        $lookup = FlightPublisher::buildLookup($criteria);

        publishRabbitMessage(
            $lookup["exchange"],
            $lookup["routing_key"],
            $lookup["payload"],
            [
                "reply_to" => $replyQueue,
                "correlation_id" => $correlationId
            ]
        );

        //xml: This waits for a response from the DB.
        // If the timeout is reached, the API falls back to AeroDataBox.
        $deadline = microtime(true) + DB_LOOKUP_TIMEOUT_SECONDS;

        while ($reply === null && microtime(true) < $deadline)
        {
            $remaining = $deadline - microtime(true);

            if ($remaining <= 0)
            {
                break;
            }

            $channel->wait(null, false, $remaining);
        }

        //xml: Occurs when the db lookup took too long.
        if ($reply === null)
        {
            echo "DB VM did not reply in time - falling back to AeroDataBox.\n";
            return null;
        }

        //xml: Occurs whena cached field for a flight is not found on our database.
        if (!($reply["found"] ?? false) || empty($reply["flight"]))
        {
            echo "Cache miss.\n";
            return null;
        }

        //xml: Save the previous cached flight so it can be
        // compared with the newest AeroDataBox information.
        $previousFlight = $reply["flight"];

        /*xml Displays this information to users for clear visualiaation*/

        $cachedAtString = $reply["flight"]["cached_at"] ?? "";

        echo "cached_at from DB: "
            . ($cachedAtString !== "" ? $cachedAtString : "NULL")
            . PHP_EOL;

        echo "Current UTC time: "
            . gmdate("Y-m-d H:i:s")
            . PHP_EOL;

        echo "TTL: "
            . CACHE_TTL_SECONDS
            . PHP_EOL;

        if (empty($cachedAtString))
        {
            echo "cached_at is missing - treating cache as stale.\n";
            return null;
        }

        try
        {
            $cachedAt = new DateTime(
                $cachedAtString,
                new DateTimeZone("UTC")
            );

            /* xml: This gets the current tim
             */
            $currentTime = new DateTime(
                "now",
                new DateTimeZone("UTC")
            );

            /* xml: This calculates the age of the cache and displays it to the terminal
             */
            $age = $currentTime->getTimestamp()
                - $cachedAt->getTimestamp();

            echo "Age: {$age} seconds" . PHP_EOL;
        }
        catch (Exception $timeException)
        {
            echo "Invalid cached_at timestamp: "
                . $cachedAtString
                . PHP_EOL;

            return null;
        }

        /*
         * xml: This says that if the age of the cached flight is over 120, state you found the flight, but
         return ut null so it can go through the Aerodata API lookup*/
        if ($age > CACHE_TTL_SECONDS)
        {
            echo "Cache hit, but stale - refreshing from AeroDataBox.\n";
            return null;
        }

        /*
         xml: The cached flight can still be used for the lookup (under 120 seconds)
         */
        echo "Cache hit - using DB-cached flight.\n";

        return $reply["flight"];
    }

    /* xml; If there is an issue looking for the cache durning the database lookup, go to AeroDataBox.
     */
    catch (Exception $e)
    {
        echo "DB lookup failed ("
            . $e->getMessage()
            . ") - falling back to AeroDataBox.\n";

        return null;
    }

    finally
    {
        try
        {
            if ($consumerTag !== null)
            {
                $channel->basic_cancel($consumerTag);
            }

            if ($replyQueue !== null)
            {
                $channel->queue_delete($replyQueue);
            }
        }
        catch (Exception $cleanupException)
        {
            //xml: Ignore cleanup errors.
        }
    }
}


/*xml: This function is in charge of comparing flight calls. What this does is it saves and updates
the flight data that is with in the database cache table. This function checks the flight data called
and compares it to the previously caches data on the database. if the flights (cancellation, delay,
gate, or status is changed, then a message is displaed saying that.  */

function cacheAndDiffFlight(

    array $flight,

    ?array $previouslyCached,

    ?string $originReplyTo,

    ?string $originCorrId

)
{
    /*xml: this portion of of code strips the space between the airline and and
    numbe rof flight. For example, US 123 ---> US123*/
$flight["flight_number"] = strtoupper(
        str_replace(" ", "", trim($flight["flight_number"] ?? ""))
    );

    $cacheMessage = FlightPublisher::buildCache(

        $flight,

        $originReplyTo,

        $originCorrId

    );

    publishRabbitMessage(

        $cacheMessage["exchange"],

        $cacheMessage["routing_key"],

        $cacheMessage["payload"]

    );

    /*AC2:xml: This conditional checks to see if there is any change on a flight that
    a passenger is looking at. Only thing that triggers this message is if
    the flights (gate, delay time, cancellation, or status) is changed*/
    if (FlightTransformer::hasChanged($previouslyCached, $flight))
    {
        $changes = FlightTransformer::diff($previouslyCached, $flight);

        $changeMessage = FlightPublisher::buildStatusChange(

            $flight,

            $changes

        );

        publishRabbitMessage(

            $changeMessage["exchange"],

            $changeMessage["routing_key"],

            $changeMessage["payload"]

        );

        echo "Status change detected and published for {$flight['flight_number']}.\n";
    }
}

/*xml: This is the most important function as this is the beginning point of
sortng, deciding, and returning the flight requests of users. This function takes the
users request and makes sure to send that request (depedning on the routing key) to
the correct function for display of information*/

function processFlightRequest($message)
{
    try
    {
        $request = json_decode(

            $message->body,

            true

        );

        echo "\n";
        echo "Incoming RabbitMQ Request: \n";
        echo "\n";

        print_r($request);

        /*xml: This verifies that there is a request/routing key*/

        if (
            !isset($request["routing_key"])
        )
        {
            echo "Missing key.\n";
            return;
        }

        $routingKey = $request["routing_key"];

        $supportedRoutingKeys = [

            "search.flight",

            "search.airport",

            "search.route"

        ];

        if (!in_array($routingKey, $supportedRoutingKeys, true))
        {
            echo "Ignoring routing key: ";
            echo $routingKey;
            echo "\n";

            return;
        }

        $payload = $request["payload"] ?? [];

        /*xml: This takes the reply to and correlation id from the inital message
        . We store these values in variables to ensure that the message is routed back to
        the correct destination. */

        $originReplyTo = $message->has("reply_to")
            ? $message->get("reply_to")
            : null;

        $originCorrId = $message->has("correlation_id")
            ? $message->get("correlation_id")
            : null;

        /*AC1: xml: This is what splits off messages by routing keys and send them off
        to their respective functions for information handling*/

        if ($routingKey === "search.flight")
        {
            handleFlightNumberSearch($payload, $originReplyTo, $originCorrId);
        }
        else if ($routingKey === "search.airport")
        {
            handleAirportSearch($payload, $originReplyTo, $originCorrId);
        }
        else if ($routingKey === "search.route")
        {
            handleRouteSearch($payload, $originReplyTo, $originCorrId);
        }
    }
    catch (Exception $e)
    {
        $originReplyTo = (isset($message) && $message->has("reply_to"))
            ? $message->get("reply_to")
            : null;

        $originCorrId = (isset($message) && $message->has("correlation_id"))
            ? $message->get("correlation_id")
            : null;

        handleWorkerError($e, $originReplyTo, $originCorrId);
    }
}

/*xml: This function is very important to our program. This looks up one flight
by its flight number. It checks to see if the flight inputted by the user is
new compared to the cached flight, if not it displays the cached value.
However, if it is new, then the API response it sent. The raw data is then
transformed into a structured format (php array) so tat it can be pushed into
the database and the request is then sent to the user*/

function handleFlightNumberSearch(array $payload, ?string $originReplyTo, ?string $originCorrId)
{
    $flightNumber = strtoupper(
    str_replace(" ", "", trim($payload["flight_number"] ?? ""))
);

    $date = $payload["date"] ?? date("Y-m-d");

    $criteria = [

        "flight_number" => $flightNumber

    ];

    echo "\nSearching cache for flight {$flightNumber}...\n";

    //xml: AC2: Holds old flight informstion
    $previouslyCached = null;

    $cached = lookupCachedFlight($criteria, $previouslyCached);

    if ($cached !== null)
{
    $reply = FlightPublisher::buildReply($cached);
    //xml: This sends over the cahed information to the APP server
    publishRabbitMessage(
        "",
        $originReplyTo,
        $reply["payload"],
        [
            "correlation_id" => $originCorrId
        ]
    );

    echo "Response sent to App Server (cache)." . PHP_EOL;

    //xml: This log documents that the flight information was found on the in data base and was given to users from there
    logConvo("INFO", "Served from cache: " . $flightNumber);

    return;
 }

    echo "Searching AeroDataBox...\n";


    $result = fetchFlight([

        "flight_number" => $flightNumber,

        "date" => $date

    ]);


    if ($result["status"] !== "success")
    {
        throw new Exception($result["message"]);
    }

    echo "Flight found.\n";


    //This portion was simply inputted for debuging issues, after learning the API is delayed, this will be used to cross
    //reference if data is truly changing from the API or not 
    echo "\n---------------------------\n";
    echo "Raw Data: \n";
    echo json_encode(
        $result["raw_data"],
        JSON_PRETTY_PRINT
    );
    echo "\n----------------------------\n";


    $flight = FlightTransformer::transform($result["raw_data"]);

    logTransformedFlight($flight);

    /*xml (AC2): This function cheks changes between the flight and the previously 
    cached flight*/

    cacheAndDiffFlight($flight, $previouslyCached, $originReplyTo, $originCorrId);

    $reply = FlightPublisher::buildReply($flight);

    //xml: This pubishes the procssed flight information to RabbitMQ
    publishRabbitMessage(

        "",

        $originReplyTo,

        $reply["payload"],

        [

            "correlation_id" => $originCorrId

        ]

    );


    echo "Response sent to App Server.\n";

    //xml: This logs the action of a sucessful flight being processed by the worker

    logConvo("INFO", "Processed flight: " . $flight["flight_number"]);

    echo "\nFlight request completed.\n";
}

/*xml: This function is important as it is in charge of handling and getting all
inbound and outbound flights from airports (more specfically EWR). The fetchAirport function is
called to get that data. If the data fetch is a sucess, then we format the response in a way
so that it matches the structure of data like the singular flight information. Then, that data is
sent off to the transformer. Which will then transform the data and then a RabbitMQ message is
published*/
function handleAirportSearch(array $payload, ?string $originReplyTo, ?string $originCorrId)
{
    $airport = trim($payload["airport"] ?? "");

    echo "\nSearching AeroDataBox for airport {$airport}...\n";

    $result = fetchAirport($payload);

    if ($result["status"] !== "success")
    {
        throw new Exception($result["message"]);
    }

    //xml: This is the array that will hold the normalized flight info

    $normalizedFlights = [];

    //xml: This traverses through the response sent from our API,
    //then structures the data to match and resemble the rest of
    //our data outputs. Any information that is not given by the API is
    //null.
    foreach ($result["raw_data"] as $flight)
    {
        $movement = $flight["movement"] ?? [];

        $movementAirport = $movement["airport"] ?? [];

        /*xml: This portion  of code determines if a flight is departing of arriving
         */
        $movementType = strtolower(
            $movement["type"] ?? ""
        );

        /*xml: This sets the default departure information for the flights
         */
        $departureAirportName = "N/A";
        $departureAirportCode = "N/A";

        /*xml: This does the same thing but for the arrival inforamtion*/
        $arrivalAirportName = "N/A";
        $arrivalAirportCode = "N/A";

        /*xml: Basically if flight is leaving then the airport that was searched will 
        become the departure and the movement apirport will become the arrival*/
        if (
            $movementType === "departure" ||
            $movementType === "departures"
        )
        {
            $departureAirportName = $airport;
            $departureAirportCode = $airport;

            $arrivalAirportName =
                $movementAirport["name"]
                ?? "N/A";

            $arrivalAirportCode =
                $movementAirport["iata"]
                ?? "N/A";
        }

        /* xml: If the flight is incoming then the airport that was seached will become 
	the arrival and the movement airport will become the depature
         */
        else if (
            $movementType === "arrival" ||
            $movementType === "arrivals"
        )
        {
            $arrivalAirportName = $airport;
            $arrivalAirportCode = $airport;

            $departureAirportName =
                $movementAirport["name"]
                ?? "N/A";

            $departureAirportCode =
                $movementAirport["iata"]
                ?? "N/A";
        }

        /* xml: This would be considered a fall back. So, if we don't know the airport 
         information, we would get that information from the flight
         */
        else
        {
	    //xml: Gets the depart airport information
            $departureAirportName =
                $flight["departure"]["airport"]["name"]
                ?? $flight["departure"]["airport"]["iata"]
                ?? $flight["departure"]["airport"]
                ?? $airport;

            $departureAirportCode =
                $flight["departure"]["airport"]["iata"]
                ?? $airport;
	    //xml: Gets the arrival airport information
            $arrivalAirportName =
                $flight["arrival"]["airport"]["name"]
                ?? $flight["arrival"]["airport"]["iata"]
                ?? $flight["arrival"]["airport"]
                ?? $airport;

            $arrivalAirportCode =
                $flight["arrival"]["airport"]["iata"]
                ?? $airport;
        }

        //xml:This will normalize the flight info into the consistent structure to send off to the flight transformer file.
        $normalizedFlights[] = [

            "number" =>
                $flight["number"] ?? "",

            "airline" => [
                "name" =>
                    $flight["airline"]["name"] ?? "N/A"
            ],

            "status" =>
                $flight["status"] ?? "unknown",

            "departure" => [
                "airport" => [
                    "name" =>
                        $departureAirportName,

                    "iata" =>
                        $departureAirportCode
                ],

                "terminal" =>
                    $movement["terminal"]
                    ?? $flight["departure"]["terminal"]
                    ?? "N/A",

                "gate" =>
                    $movement["gate"]
                    ?? $flight["departure"]["gate"]
                    ?? "N/A",

                "scheduledTime" => [
                    "utc" =>
                        $movement["scheduledTime"]["utc"]
                        ?? $flight["departure"]["scheduledTime"]["utc"]
                        ?? $flight["departureTimeUtc"]
                        ?? null
                ],

                "actualTime" => [
                    "utc" =>
                        $movement["actualTime"]["utc"]
                        ?? $flight["departure"]["actualTime"]["utc"]
                        ?? $flight["actualDepartureTimeUtc"]
                        ?? null
                ]

            ],

            "arrival" => [
                "airport" => [
                    "name" =>
                        $arrivalAirportName,

                    "iata" =>
                        $arrivalAirportCode
                ],

                "terminal" =>
                    $movement["terminal"]
                    ?? $flight["arrival"]["terminal"]
                    ?? "N/A",

                "gate" =>
                    $movement["gate"]
                    ?? $flight["arrival"]["gate"]
                    ?? "N/A",

                "scheduledTime" => [
                    "utc" =>
                        $flight["arrival"]["scheduledTime"]["utc"]
                        ?? $flight["arrivalTimeUtc"]
                        ?? null
                ],

                "actualTime" => [
                    "utc" =>
                        $flight["arrival"]["actualTime"]["utc"]
                        ?? $flight["arrival"]["actualTime"]["utc"]
                        ?? $flight["actualArrivalTimeUtc"]
                        ?? null
                ]

            ],

            "aircraft" => [
                "model" =>
                    $flight["aircraft"]["model"]
                    ?? "N/A",

                "reg" =>
                    $flight["aircraft"]["reg"]
                    ?? null
            ]
        ];
    }

    //xml: Sends the normalized airport results to the flight transformer file.
    $flights = FlightTransformer::transformList($normalizedFlights);

    //xml:This portion of code basically the RabbitMQ response that has the lidt of flighs for the selected airport.
    $reply = FlightPublisher::buildList($flights);

    //xml: This portion of code publishes the list to RabbitMQ for the App server to recieve
    publishRabbitMessage(
        "",
        $originReplyTo,
        $reply["payload"],
        [
            "correlation_id" => $originCorrId
        ]
    );

    //xml: sends log confirming that airport search was sucessful
    logConvo(
        "INFO",
        "Processed airport board: " . $airport
    );

    echo "\nAirport request completed.\n";
}

/*xml: This function is very important. It is what helps sort flight requests via route.
Since here is no end point, the process for this is a bit technical. First all the departures
from the origin airport are pulled. Then that list of flights is filtered to flights that have the
matching destination of the users choice. The list is then place is sequencial order and sent
back to the app vm for the user to be able to see*/

function handleRouteSearch(array $payload, ?string $originReplyTo, ?string $originCorrId)
{
    $origin = strtoupper(trim($payload["origin"] ?? ""));
    $destination = strtoupper(trim($payload["destination"] ?? ""));

    echo "\nSearching route {$origin} -> {$destination}...\n";


    //xml: Get all flights leaving origin airport
    $result = fetchAirport([
        "airport" => $origin
    ]);


    if ($result["status"] !== "success")
    {
        throw new Exception($result["message"]);
    }

    //xml: Declaring array that will hold matching flights (fights that have the origin and destination of users desire
    $matchingFlights = [];


    foreach ($result["raw_data"] as $flight)
    {
        //xml:  Airport board endpoint uses movement.airport.iata
        $arrivalCode = strtoupper(
            $flight["movement"]["airport"]["iata"] ?? ""
        );


        //xml:  Keep only flights going to requested destination
        if ($arrivalCode === $destination)
        {
            $matchingFlights[] = [

                "flight_number" =>
                    $flight["number"] ?? null,

                "airline" =>
                    $flight["airline"]["name"] ?? null,

                "status" =>
                    $flight["status"] ?? "unknown",

                "departure_airport" =>
                    $origin,

                "arrival_airport" =>
                    $flight["movement"]["airport"]["name"] ?? null,

                "arrival_airport_code" =>
                    $arrivalCode,

                "terminal" =>
                    $flight["movement"]["terminal"] ?? null,

                "scheduled_departure" =>
                    $flight["movement"]["scheduledTime"]["utc"] ?? null,

                "estimated_departure" =>
                    $flight["movement"]["revisedTime"]["utc"] ?? null,

                "aircraft_model" =>
                    $flight["aircraft"]["model"] ?? null,

                "aircraft_registration" =>
                    $flight["aircraft"]["reg"] ?? null,

                "cached_at" =>
                    date("Y-m-d H:i:s")
            ];
        }
    }

    //xml: Displays the number of flights that are routed
    echo "\nMatching flights found: " . count($matchingFlights) . "\n";

     //xml: If there are no flights then the exception message is thrown
    if (empty($matchingFlights))
    {
        throw new Exception(
            "No flights found from {$origin} to {$destination}."
        );
    }


    $reply = FlightPublisher::buildList(
        $matchingFlights
    );

    //xml: This publishes the result back to RabbitMQ so that the App server can display the information
    publishRabbitMessage(
        "",
        $originReplyTo,
        $reply["payload"],
        [
            "correlation_id" => $originCorrId
        ]
    );

    //xml: creates log message with the requested route search
    logConvo(
        "INFO",
        "Processed route: {$origin} -> {$destination}"
    );

    echo "\nRoute request completed.\n";
}
/*AC3: xml: This function basically handles all the errors that could possibly
occur durning the search for either the flight number, airport, or routes. What this
function does is it will send a log error to the data base log table describing the
issue on a technical level to our team. However, for the user, it will send a basic
messgae to the user */

function handleWorkerError(Exception $e, ?string $originReplyTo = null, ?string $originCorrId = null)
{
    echo "\n";
    echo "-------------------------------------\n";
    echo "ERROR\n";
    echo "-------------------------------------\n";

    echo $e->getMessage();

    echo "\n";

    //xml: detailed log of error
    logConvo("ERROR", $e->getMessage());

    // xml: error prompted to the user in case of this emergency
    $error = FlightPublisher::UnavailableError();

    publishRabbitMessage(

        "",

        $originReplyTo,

        $error["payload"],

        [

             "correlation_id" => $originCorrId

        ]

    );
}
/*xml: This was built just to make things easier for myself. When first creating the worker
I wanted an actual of the visual of the transformed data that would be sent to the
data cache table.*/
function logTransformedFlight(array $flight)
{
    echo "\n";
    echo "Transformed Flight Data\n";


    echo json_encode(

        $flight,

        JSON_PRETTY_PRINT

    );

    echo "\n\n";
}


/*xml: This is a consumer that basically tells RabbitMQ to listen on
the api.request queues. So, when a message is put in that queue, then the function
processFlightRequest should be called and handle the incoming request accordingly*/
$channel->basic_consume(

    "api.requests",

    "",

    false,

    true,

    false,

    false,

    "processFlightRequest"

);


/*xml: This is the important part of the API Worker. This is considered the consumer
and is continously running waiting for incoming API requests*/

echo "\n";
echo "Api Work Consumer is running ...";

while ($channel->is_consuming())
{
    try
    {
        $channel->wait();
    }
    catch (Exception $e)
    {
        echo "\n";
        echo "Worker Exception:\n";
        echo $e->getMessage();
        echo "\n";

//xml: This creates a log error message 
        logConvo(

            "ERROR",

            "Worker Exception: " .

            $e->getMessage()

        );
    }
}


echo "\n";
echo "Stopping API Worker...\n";

$channel->close();

$connection->close();
