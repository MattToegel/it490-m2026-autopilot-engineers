#!/bin/bash


# cao39 - This script is for a cleaner table output when running the queue list
# cao39 - Script displays the name of the queue and message & consumer counts
sudo rabbitmqctl list_queues name messages consumers | awk '
BEGIN {
    printf "%-20s %-10s %-10s\n","QUEUE","MESSAGES","CONSUMERS"
}
NR>3 {
    printf "%-20s %-10s %-10s\n",$1,$2,$3
}'