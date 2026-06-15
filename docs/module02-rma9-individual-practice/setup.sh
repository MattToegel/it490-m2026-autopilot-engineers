#!/bin/bash
set -e

# UCID: rma9

# UCID: rma9 - update Ubuntu package list
echo "Updating Ubuntu packages..."
sudo apt update

# UCID: rma9 - install Git, PHP, Composer, and RabbitMQ server
echo "Installing required software..."
sudo apt install -y git php php-cli composer rabbitmq-server

# UCID: rma9 - enable and start the RabbitMQ service
echo "Starting RabbitMQ..."
sudo systemctl enable --now rabbitmq-server

# UCID: rma9 - download the IT490 RabbitMQ sample code from the professor's GitHub repository
echo "Getting the IT490 sample code..."
if [ ! -d "$HOME/IT490-2026" ]; then
    git clone https://github.com/MattToegel/IT490-2026.git "$HOME/IT490-2026"
else
    echo "IT490-2026 repo already exists."
fi

# UCID: rma9 - move into the sample code folder
cd "$HOME/IT490-2026"

# UCID: rma9 - install PHP dependencies listed in composer.json
echo "Installing PHP dependencies..."
composer install

# UCID: rma9 - print commands for testing the server and client
echo "Setup complete."
echo "Run this in one SSH terminal:"
echo "cd IT490-2026 && php RabbitMQServerSample.php"
echo "Run this in another SSH terminal:"
echo "cd IT490-2026 && php RabbitMQClientSample.php"
echo "For ping/pong test:"
echo "cd IT490-2026 && php RabbitMQClientSample.php ping"
