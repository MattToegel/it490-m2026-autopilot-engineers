#!/bin/bash

# IT490 - Module 02 - RabbitMQ Setup Script
# UCID: ns87

# Step 1: Update system
sudo apt update -y
sudo apt upgrade -y

# Step 2: Install RabbitMQ
sudo apt install -y rabbitmq-server
sudo systemctl enable rabbitmq-server
sudo systemctl start rabbitmq-server

# Step 3: Add PHP 8.4 repository
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Step 4: Install PHP 8.4
sudo apt install -y php8.4 php8.4-cli php8.4-mbstring php8.4-bcmath unzip curl git

# Step 5: Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Step 6: Clone the repo
git clone https://github.com/MattToegel/IT490-2026.git
cd IT490-2026

# Step 7: Install php-amqplib
php8.4 /usr/local/bin/composer require php-amqplib/php-amqplib

echo "Setup complete! RabbitMQ is ready."
