#!/bin/bash

# cao39: Update and upgrade the system
sudo apt update && sudo apt upgrade -y

# cao39: installed the text editor to modify local files
sudo apt install nano

#cao39: used to locate iputil packages
sudo apt search iputils

#cao39: after locating the correct package, install the ping feature using the command below
sudo apt install iputils-ping

#cao39: install git into the virtual machine
sudo apt install git

#cao39: cloning the IT490 github repository
git clone https://github.com/MattToegel/IT490-2026.git

#cao39: lists the contents inside the directory
ls

#cao39: changes the directory into the one selected below
cd IT490-2026/

#cao39: lists the contents inside the chosen directory to view the files inside
ls

#cao39: installs both composer and php
sudo apt install composer php

#cao39: Performs another update to check if any packages need to be updated
sudo apt update

#cao39: installs the package below to manage repositories that are external
sudo apt install software-properties-common

#cao39: adds the PPA repository
LC_ALL=C.UTF-8 sudo add-apt-repository ppa:ondrej/php

#cao39: installs the most recent version of php
sudo apt install php8.5

#cao39: install composer on the computer
composer install

#cao39: installs rabbitmq server on the computer
sudo apt install rabbitmq-server -y

#cao39: the command below  initiates the RabbitMQ server
php RabbitMQServerSample.php

#cao39: Please remember to initiate the RabbitMQ client to start the client message queue, use the command below in another instance
#php RabbitMQServerSample.php
