<?php


require_once __DIR__ . "/../vendor/autoload.php";
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(
    dirname(__DIR__)
);
$dotenv->load();
/*xml: These are my AeroDataBox definitions in order to have redundancy throughout my code
so I don't have to worry about hardcoding in value within my code*/
define(
    "AERODATABOX_KEY",
    $_ENV["AERODATABOX_API_KEY"]
);
define(
    "AERODATABOX_BASE_URL",
    $_ENV["AERODATABOX_BASE_URL"]
);
/*xml: These are my RabbitMQ definitions in order to have redundancy throughout my code
so I don't have to worry about hardcoding in value within my code*/

define(
    "RABBITMQ_HOST",
    $_ENV["RABBITMQ_HOST"]
);
define(
    "RABBITMQ_PORT",
    $_ENV["RABBITMQ_PORT"]
);
define(
    "RABBITMQ_USER",
    $_ENV["RABBITMQ_USER"]
);
define(
    "RABBITMQ_PASSWORD",
    $_ENV["RABBITMQ_PASSWORD"]
);
/*xml: These are my queue variables that I will use thoughout my program in order to
avoid having to hard code them*/
define(
    "FLIGHT_REQUEST_QUEUE",
    $_ENV["FLIGHT_REQUEST_QUEUE"]
);
define(
    "FLIGHT_CACHE_QUEUE",
    $_ENV["FLIGHT_CACHE_QUEUE"]
);
/*xml: This value is made so i don't have to hard code it later one. Basically,
CACHE_TIL_SECONDS is the amount of time a cached flight will be considered valid
until it must be called again from my API to ensure its displaying the most
accurate information (120s)*/
 define(
    "CACHE_TTL_SECONDS",
    isset($_ENV["CACHE_TTL_SECONDS"]) ? (int) $_ENV["CACHE_TTL_SECONDS"] : 120
);
/*xml: This value is create so i don't have to hard code it later manually. This makes
my worker timeout when trying to find a cached flght from the cache database table.
That way, my worker is not stuck waiting and it can wait a period of 3 seconds before
it calls the api*/
define(
    "DB_LOOKUP_TIMEOUT_SECONDS",
    isset($_ENV["DB_LOOKUP_TIMEOUT_SECONDS"]) ? (int) $_ENV["DB_LOOKUP_TIMEOUT_SECONDS"] : 3
);
