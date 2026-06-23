<?php

// cao39 RabbitMQ Log File


function writeLog($message)
{
    // cao39 Name of the log 
    $logFile = "rabbitmq.log";

    // cao39 Current date and time
    $timestamp = date("Y-m-d H:i:s");

    // cao39 Create log entry
    $logEntry = "[" . $timestamp . "] " . $message . PHP_EOL;

    // cao39 Append the entry to file
    file_put_contents(
        $logFile,
        $logEntry,
        FILE_APPEND
    );
}


// cao39 Adding messages to the log file

writeLog("RabbitMQ setup has started.");

writeLog("Request exchange has been created.");

writeLog("Response exchange has been created.");

writeLog("API queue has been created.");

writeLog("Database queue has been created.");

writeLog("Queue bindings have been completed.");


echo "Log entries written.\n";