<?php
class FlightPublisher
{
    /*xml: this functio is responsible for sending flighs to the db cache table in order to keep
    fo easier access in the future. An error message will be sent to the db in case of an event where
    the API fails*/
    public static function buildCache(

        array $flight,

        ?string $originReplyTo,

        ?string $originCorrId,

        bool $isError = false,

        ?string $errorMessage = null

    ): array
    {
        $payload = $flight;

        $payload["origin_reply_to"] = $originReplyTo;

        $payload["origin_correlation_id"] = $originCorrId;

        if ($isError)
        {
            $payload["error"] = true;

            $payload["message"] = $errorMessage;
        }

        return [
            "exchange" => "app.requests",
            "routing_key" => "flight.cache",
            "payload" => $payload
        ];
    }

    /*xml: This function creates a lookup request. It basically asks the databse 
    if the requested flight in placed ith the cache table. */
    public static function buildLookup(array $criteria): array
    {
        return [
            "exchange" => "app.requests",
            "routing_key" => "flight.cache_lookup",
            "payload" => $criteria
        ];
    }

    /*xml: This function creates a response that will be returned to the App Vm
    when there is a sucessful flight search conductd.*/
    public static function buildReply(array $flight): array
    {
        return [
            "exchange" => "app.responses",
            "routing_key" => "job.complete",
            "payload" => [
                "status" => "success",
                "flight" => $flight
            ]
        ];
    }

    /*xml: This function creates a sucessful response for a multitude of flight
    records*/
    public static function buildList(array $flights): array
    {
        return [
            "exchange" => "app.responses",
            "routing_key" => "job.complete",
            "payload" => [
                "status" => "success",
                "flights" => $flights
            ]
        ];
    }

    /*xml: this funcion creates an error messahe when the user requests a search but
    it cannot be fully completed*/
    public static function buildError(string $message): array
    {
        return [
            "exchange" => "app.responses",
            "routing_key" => "job.failed",
            "payload" => [
                "status" => "error",
                "message" => $message,
                "timestamp" => date("Y-m-d H:i:s")
            ]
        ];
    }

    /*xml: This function creates a message and is triggred whenever our API is not 
    avilable or reachable*/
    public static function UnavailableError(): array
    {
        return [
            "exchange" => "app.responses",
            "routing_key" => "job.failed",
            "payload" => [
                "status" => "error",
                "message" => "Flight information is not available at this time. Please try again shortly.",
                "timestamp" => date("Y-m-d H:i:s")
            ]
        ];
    }

    /*xml: This function is triggered when a new flight that is searched but it differs
    from cached saved values*/
    public static function buildStatusChange(

        array $newFlight,

        array $changes

    ): array
    {
        return [
            "exchange" => "app.responses",
            "routing_key" => "flight.status_change",
            "payload" => [
                "flight_number" => $newFlight["flight_number"],
                "flight" => $newFlight,
                "changes" => $changes,
                "detected_at" => date("Y-m-d H:i:s")
            ]
        ];
    }
	/*xml: This function creates a message that descibes the changes that has occured 
        between the two flights  */
    public static function buildFlightUpdate(array $old, array $new): array
    {
        return [
            "exchange" => "app.requests",
            "routing_key" => "flight.cache",
            "payload" => [
                "flight_number" => $new["flight_number"],
                "old_status" => $old["status"],
                "new_status" => $new["status"],
                "old_gate" => $old["gate"],
                "new_gate" => $new["gate"],
                "updated_at" => date("Y-m-d H:i:s")
            ]
        ];
    }
}
