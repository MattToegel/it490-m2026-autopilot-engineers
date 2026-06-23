<?php

//xml: Allows us to be able to acceess the publishing log function
require_once __DIR__ . '/api_log.php';

//xml: This noitifies that the message is beginning
echo "Starting API message \n";

//xml: This calls the function
$sent = publishLog();

//xml: This conditional send if the message is sent. If it is, the the message is sent out to the terminal. If not, the else cpmmand willl execute
if ($sent) {
    echo "Log sent. \n";
} else {
    echo "Log did not send.\n";
}
