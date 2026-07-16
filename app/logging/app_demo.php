<?php
//rma9: this tells the computer this file is written in php

//rma9: this file is the demo script for testing the app vm log publisher
//rma9: it does not create the logging function, it only calls the function from app_log.php

//rma9: this loads app_log.php so this demo file can use the publishAppLog function
require_once __DIR__ . '/app_log.php';

//rma9: this prints that the app vm logging demo is starting
echo "Starting App VM log demo...\n";

//rma9: this calls the app log publisher function and sends one test log event
//rma9: the first value is the log level and the second value is the log message
$sent = publishAppLog("info", "this is a test log from the app vm");

//rma9: this checks if the publishAppLog function returned true or false
if ($sent) {
    //rma9: this prints if the app vm log event was sent successfully
    echo "App VM log sent.\n";
} else {
    //rma9: this prints if the app vm log event did not send successfully
    echo "App VM log did not send.\n";
}
