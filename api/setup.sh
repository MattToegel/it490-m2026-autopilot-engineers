#!/bin/bash
# xml: Sets up the client VM

# xml: Prompt for the server's Tailscale IP
read -p "Enter the server VM's Tailscale IP: " SERVER_IP

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

# xml: Install Composer if not already installed
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# xml: Clone the repo if not already present
if [ ! -d "$HOME/IT490-2026" ]; then
    git clone -b main https://github.com/MattToegel/IT490-2026.git "$HOME/IT490-2026"
fi

cd "$HOME/IT490-2026"
composer install

#xml: handles requests, responds, headers, and errors within api communication 
composer require guzzlehttp/guzzle


# xml: Update the ini file to point to the server VM
sed -i "s/^BROKER_HOST=.*/BROKER_HOST=$SERVER_IP/" testRabbitMQ.ini
sed -i "s/^USER=.*/USER=it490/" testRabbitMQ.ini
sed -i "s/^PASSWORD=.*/PASSWORD=it490/" testRabbitMQ.ini

echo "cd into the directory IT490-2026"
echo "Run: php RabbitMQClientSample.php"
