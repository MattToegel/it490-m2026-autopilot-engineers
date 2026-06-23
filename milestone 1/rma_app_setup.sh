#!/bin/bash
set -euo pipefail

#rma9: start the app vm setup script
echo "Starting App VM setup..."

#rma9: update the ubuntu package list before installing software
sudo apt update

#rma9: install only the minimum software needed for the app server role
sudo apt install -y \
  apache2 \
  php \
  php-cli \
  php-curl \
  php-mbstring \
  php-xml \
  composer \
  curl \
  git \
  openssh-server

#rma9: enable and start apache because this vm acts as the app server
sudo systemctl enable --now apache2

#rma9: enable and start ssh so the team can remotely access this vm
sudo systemctl enable --now ssh

#rma9: create a basic app directory if it does not already exist
sudo mkdir -p /var/www/app

#rma9: set ownership of the app directory for the web server
sudo chown -R www-data:www-data /var/www/app

#rma9: verify the app vm setup after installation
echo "Verifying App VM setup..."
echo "User: $(whoami)"
echo "Hostname: $(hostname)"
php -v
composer --version
apache2 -v
curl --version | head -n 1
git --version
systemctl is-active apache2
systemctl is-active ssh

#rma9: finish the app vm setup script
echo "App VM setup complete."
