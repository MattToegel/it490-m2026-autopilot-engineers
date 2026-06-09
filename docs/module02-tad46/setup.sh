#!/bin/bash
# tad46 - This script sets up everything for the RabbitMQ PHP samples

# tad46 - Update the list of available packages
sudo apt-get update -y

# tad46 - Install RabbitMQ, PHP, and other needed tools
sudo apt-get install -y rabbitmq-server php-cli php-mbstring unzip curl git

# tad46 - Make sure RabbitMQ is running
sudo systemctl enable rabbitmq-server
sudo systemctl start rabbitmq-server

# tad46 - Install Composer, if it isn't already installed
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# tad46 - Clone the project repo if the folder doesn't already exist
if [ ! -d "$HOME/IT490-2026" ]; then
    git clone -b main https://github.com/MattToegel/IT490-2026.git "$HOME/IT490-2026"
fi

# tad46 - Go into the project folder
cd "$HOME/IT490-2026"

# tad46 - Install the PHP libraries the project needs
composer install

echo "Setup done! You can now run the sample files."
