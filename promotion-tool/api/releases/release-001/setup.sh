#!/bin/bash
# xml: Sets up the client VM

# xml: Prompt for the server's Tailscale IP
read -p "Enter the server VM's Tailscale IP: " SERVER_IP

#xml: Prompts for the RabbitMQ username
read -p "Enter the RabbitMQ user: " RABBITMQ_USER

#xml: Prompts for the RabbitMQ password
read -s -p "Enter RabbitMQ password: " RABBITMQ_PASSWORD

# xml: Update packages
sudo apt-get update -y

#xml: This upgrades packages
sudo apt upgrade -y

# xml Install PHP and other required tools
sudo apt-get install -y php-cli
sudo apt-get install -y php-mbstring
sudo apt-get install -y php-json
sudo apt-get install -y php-xml
sudo apt-get install -y php-zip
sudo apt-get install -y unzip

#xml:this command insalls the library that will help out api server
#communicate to our external apis over HTTP/HTTPS
sudo apt-get install -y curl
#xml:This installs git
sudo apt-get install -y git

# xml: Create a RabbitMQ user (skip if already exists)
if ! sudo rabbitmqctl list_users | grep -q "it490"; then
    sudo rabbitmqctl add_user it490 it490
    sudo rabbitmqctl set_permissions -p / it490 ".*" ".*" ".*"
fi

sudo apt install composer php #xml: installs composer and php onto the computer
sudo apt update #xml: pulls information about any packages that need upgrading
sudo apt install software-properties-common #xml: install packages for the repo tool to get php 8.5
LC_ALL=C.UTF-8 sudo add-apt-repository ppa:ondrej/php #xml: command to add PPA repo
sudo apt install php8.5 #xml: installs latest version of php
composer install #xml: installs composer on computer


# xml: Clone the repo if not already present
if [ ! -d "$HOME/it490-m2026-autopilot-engineers" ]; then
   git clone https://github.com/MattToegel/it490-m2026-autopilot-engineers.git "$HOME/it490-m2026-autopilot-engineers"
fi

cd "$HOME/IT490-2026/it490-m2026-autopilot-engineers/api"
composer install

#xml: handles requests, responds, headers, and errors within api communication
composer require guzzlehttp/guzzle


#This changes the directory to the destination
cd "$HOME/it490-m2026-autopilot-engineers/api"

#This creates the .env file
touch .env

#This creates the values within the file as well as adds the ip of the RabbitMq Server
cat > .env <<EOF
RABBITMQ_HOST=$SERVER_IP
RABBITMQ_PORT=5672
RABBITMQ_USER=$RABBITMQ_USER
RABBITMQ_PASSWORD=$RABBITMQ_PASSWORD
RABBITMQ_VHOST=/
EOF

# xml: Update the ini file to point to the server VM
sed -i "s/^BROKER_HOST=.*/BROKER_HOST=$SERVER_IP/" testRabbitMQ.ini
sed -i "s/^USER=.*/USER=$RABBITMQ_USER/" testRabbitMQ.ini
sed -i "s/^PASSWORD=.*/PASSWORD=$RABBITMQ_PASSWORD/" testRabbitMQ.ini

#xml: This prompts the user to change directories
echo "cd into the directory it490-m2026-autopilot-engineers/api"
echo "Run: php RabbitMQClientSample.php to test queue"
