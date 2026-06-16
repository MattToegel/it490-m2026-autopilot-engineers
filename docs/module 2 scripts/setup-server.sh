#!/bin/bash
# Sets up the server vm

# cao39: Update packages
sudo apt-get update -y

# rma9: Installs PHP and other required tools
sudo apt-get install -y php-cli php-mbstring unzip curl git

# rma9: Creates the RabbitMQ server if it does not already exist
if ! sudo rabbitmqctl list_users | grep -q "test"; then
    sudo rabbitmqctl add_user test test
    sudo rabbitmqctl set_permissions -p / test ".*" ".*" ".*"
fi

# rma9: Installs Composer if it is not installed already
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# cao39: Clones the repo just in case it was not cloned prior
if [ ! -d "$HOME/IT490-2026" ]; then
    git clone -b main https://github.com/MattToegel/IT490-2026.git "$HOME/IT490-2026"
fi

# cao39: Changes the directory to the main IT490-2026 folder and uses composer to install PHP libraries
cd "$HOME/IT490-2026"
composer install

# cao39: Get this VM's Tailscale IP
SERVER_IP=$(tailscale ip -4 | head -n 1)

# rma9: Update the broker host in the ini file with the users ip
sed -i "s/^BROKER_HOST=.*/BROKER_HOST=$SERVER_IP/" testRabbitMQ.ini

echo "Server IP set to $SERVER_IP"
echo "Server setup done!"
echo "Run: php RabbitMQServerSample.php"