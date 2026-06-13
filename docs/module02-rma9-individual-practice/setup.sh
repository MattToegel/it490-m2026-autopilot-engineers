#!/bin/bash

echo "Updating Ubuntu packages..."
sudo apt update

echo "Installing required software..."
sudo apt install -y git php composer rabbitmq-server

echo "Starting RabbitMQ..."
sudo systemctl enable --now rabbitmq-server

echo "Getting the IT490 sample code..."
if [ ! -d "$HOME/IT490-2026" ]; then
    git clone https://github.com/MattToegel/IT490-2026.git "$HOME/IT490-2026"
else
    echo "IT490-2026 repo already exists."
fi

echo "Installing PHP dependencies..."
cd "$HOME/IT490-2026"
composer install

echo "Setup complete."
echo "Run this in one SSH terminal:"
echo "cd IT490-2026 && php RabbitMQServerSample.php"
echo "Run this in another SSH terminal:"
echo "cd IT490-2026 && php RabbitMQClientSample.php"
