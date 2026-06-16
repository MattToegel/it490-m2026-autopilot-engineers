#!/bin/bash
# tad46: Sets up the client VM

# tad46: Prompt for the server's Tailscale IP
read -p "Enter the server VM's Tailscale IP: " SERVER_IP

# tad46: Update packages
sudo apt-get update -y

# tad46: Install PHP and other required tools
sudo apt-get install -y php-cli php-mbstring unzip curl git

# tad46: Create a RabbitMQ user (skip if already exists)
if ! sudo rabbitmqctl list_users | grep -q "test"; then
    sudo rabbitmqctl add_user test test
    sudo rabbitmqctl set_permissions -p / test ".*" ".*" ".*"
fi

# tad46: Install Composer if not already installed
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# tad46: Clone the repo if not already present
if [ ! -d "$HOME/IT490-2026" ]; then
    git clone -b main https://github.com/MattToegel/IT490-2026.git "$HOME/IT490-2026"
fi

cd "$HOME/IT490-2026"
composer install

# tad46: Update the ini file to point to the server VM
sed -i "s/^BROKER_HOST=.*/BROKER_HOST=$SERVER_IP/" testRabbitMQ.ini
sed -i "s/^USER=.*/USER=test/" testRabbitMQ.ini
sed -i "s/^PASSWORD=.*/PASSWORD=test/" testRabbitMQ.ini

echo "Client setup done!"
echo "Run: php RabbitMQClientSample.php"