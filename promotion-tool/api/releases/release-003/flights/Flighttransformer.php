<?php
class FlightTransformer
{
    /*xml: Thie file is very important to the functionality of our project. This file 
    converts the raw data from the API and converts it into a PHP array wihch 
    makes everything uniform throughout the platform. This is primarily used 
    just for singualr flights. */
    private const WATCHED_FIELDS = [

        "status",

        "gate",

        "arrival_gate",

        "estimated_departure",

        "estimated_arrival"

    ];
//xml: This function is the transformer that is in charge of converting all of 
//these data values
    public static function transform(array $flight): array
    {
        return [

    "flight_number" =>
    strtoupper(
        str_replace(" ", "", $flight["number"] ?? "")
    ),
//This is the airline of the flight
    "airline" =>
        $flight["airline"]["name"] ?? null,
//This is the status of th flight
    "status" =>
        $flight["status"] ?? "unknown",
//This is the name of the airport
    "departure_airport" =>
        $flight["departure"]["airport"]["name"] ?? null,
    "arrival_airport" =>
        $flight["arrival"]["airport"]["name"] ?? null,
//This is the departure terminal
    "terminal" =>
        $flight["departure"]["terminal"] ?? null,

    // Will provide either a gate or a check-in desk
    "gate" =>
        $flight["departure"]["gate"]
        ?? $flight["departure"]["checkInDesk"]
        ?? null,
//Thi is the arrival terminal
    "arrival_terminal" =>
        $flight["arrival"]["terminal"] ?? null,
//This is the arrival gate
    "arrival_gate" =>
        $flight["arrival"]["gate"] ?? null,

//This Departure times
    "scheduled_departure" =>
        $flight["departure"]["scheduledTime"]["utc"]
        ?? $flight["departure"]["scheduledTimeUtc"]
        ?? null,
//This is estimated departure
    "estimated_departure" =>
        $flight["departure"]["revisedTime"]["utc"]
        ?? $flight["departure"]["estimatedTimeUtc"]
        ?? null,
//This is the actual departure time
    "actual_departure" =>
        $flight["departure"]["actualTime"]["utc"]
        ?? $flight["departure"]["actualTimeUtc"]
        ?? null,

 // This is arrival times
    "scheduled_arrival" =>
        $flight["arrival"]["scheduledTime"]["utc"]
        ?? $flight["arrival"]["scheduledTimeUtc"]
        ?? null,
//This is estimated arrival time
    "estimated_arrival" =>
        $flight["arrival"]["predictedTime"]["utc"]
        ?? $flight["arrival"]["estimatedTimeUtc"]
        ?? null,
//This is the actual arrival time
    "actual_arrival" =>
        $flight["arrival"]["actualTime"]["utc"]
        ?? $flight["arrival"]["actualTimeUtc"]
        ?? null,
//This is the aircraft model
    "aircraft_model" =>
        $flight["aircraft"]["model"] ?? null,
//This is the aircraft reistration
    "aircraft_registration" =>
        $flight["aircraft"]["reg"] ?? null,
//This is the time the information will be cached at
    "cached_at" =>
        date("Y-m-d H:i:s")

];
    }

/*xml: This function is in charge of also transforming a multitude of
API flight data. This
function is to convert airport search data and route search data into php arrays*/
    public static function transformList(array $flights): array
    {
        return array_map(

            function ($flight)
            {
                return self::transform($flight);
            },

            $flights

        );
    }

    /*xml: This function is in charge of comparing old cached flights 
    with new ones. Its primary focus to check if any important flight information
    has changed for any reason */
    public static function hasChanged(?array $old, array $new): bool
    {
        if ($old === null)
        {
            return false;
        }

        foreach (self::WATCHED_FIELDS as $field)
        {
            $oldValue = $old[$field] ?? null;
            $newValue = $new[$field] ?? null;

            if ($oldValue !== $newValue)
            {
                return true;
            }
        }

        return false;
    }

    /*xml: This function is what lists every field that has been modified.*/
    public static function diff(?array $old, array $new): array
    {
	//xml: this is an empty array
        $changes = [];
	//xml: this loops through all the values in the watched fields
        foreach (self::WATCHED_FIELDS as $field)
        {
            $oldValue = $old[$field] ?? null;
            $newValue = $new[$field] ?? null;
		//This check if the new value is different than the old value
            if ($oldValue !== $newValue)
            {
                $changes[$field] = [

                    "old" => $oldValue,

                    "new" => $newValue

                ];
            }
        }

        return $changes;
    }
}
