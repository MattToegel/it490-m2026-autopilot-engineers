#!/bin/bash
sudo apt upgrade -y #xml:command used to confirm all updates
sudo apt install nano #xml: used for any local file edits: the text editor of the terminal
sudo apt search iputils #xml: used to find all packages associated with ip utilities
sudo apt install iputils-ping #xml: used to install the ping feature that allows pinging to other devices
sudo apt install git #xml: installs git onto vm
git clone https://github.com/MattToegel/IT490-2026.git #xml: allows you to clone the professor's repo locally on the computer.
ls #xml: list all contents in selected directory
cd IT490-2026 #xml: changes directory into this specific one
sudo apt install composer php #xml: installs composer and php onto the computer
sudo apt update #xml: pulls information about any packages that need upgrading
sudo apt install software-properties-common #xml: install packages for the repo tool to get php 8.5
LC_ALL=C.UTF-8 sudo add-apt-repository ppa:ondrej/php #xml: command to add PPA repo
sudo apt install php8.5 #xml: installs latest version of php
composer install #xml: installs composer on computer
sudo apt install rabbitmq-server -y #xml: This installs the rabbitmq server on to the server computer
 php RabbitMQServerSample.php #xml: this command starts the rabbit mq servers
On another instance, comment out the previous command and type
#php RabbitMQClientSample.php #This starts the client message queues

