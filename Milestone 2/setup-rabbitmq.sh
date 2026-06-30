#!/bin/bash
#cao39 - RabbitMQ Setup Installation

#cao39: command used to stop the script if a command fails 
set -e

echo "Setting up RabbitMQ"

#cao39: check for updates
sudo apt-get update -y

#cao39: Installing PHP 
sudo apt-get install -y rabbitmq-server php-cli php-mbstring unzip curl git
sudo apt install software-properties-common
LC_ALL=C.UTF-8 sudo add-apt-repository ppa:ondrej/php
sudo apt install php8.5

echo "Setting up the RabbitMQ configuration"

#cao39: Making the RabbitMQ configuration file executable and launching it
chmod +x config-rabbitmq.sh
./config-rabbitmq.sh

echo "Installing Composer..."

#Installing composer
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

echo "Cloning repository..."

if [ ! -d "$HOME/IT490-2026" ]; then
    git clone -b main https://github.com/MattToegel/IT490-2026.git "$HOME/IT490-2026"
fi

cd "$HOME/IT490-2026"

#cao39: Installing PHP library and dependencies from Composer
composer install

SERVER_IP=$(tailscale ip -4 | head -n 1)

echo "RabbitMQ Verification List:"
#cao39: Displays all bindings
rabbitmqctl list_bindings
#cao39: Displays all users on the RabbitMQ Server
rabbitmqctl list_users
#cao39: Displays all active consumers, display names, and any pending messages
rabbitmqctl list_queues name messages consumers
#cao39: Displays all exchanges
rabbitmqctl list_exchanges name type durable

echo ""
echo "RabbitMQ Setup Complete"
echo "Broker IP: $SERVER_IP"