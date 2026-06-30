#!/bin/bash
# tad46 Database VM setup script for IT490 centralized logging system

# tad46 Update package list
sudo apt-get update -y

# tad46 Install MySQL server
sudo apt-get install -y mysql-server

# tad46 Enable MySQL to start on boot and start it now
sudo systemctl enable mysql
sudo systemctl start mysql

# tad46 Install PHP and the required extensions
sudo apt-get install -y php-cli php-mbstring php-mysql unzip curl git

# tad46 Install Composer if not already installed
if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
fi

# tad46 Clone the team repo if not already present
REPO_DIR="$HOME/it490-m2026-autopilot-engineers"
if [ ! -d "$REPO_DIR" ]; then
    git clone -b main \
        https://github.com/MattToegel/it490-m2026-autopilot-engineers.git \
        "$REPO_DIR"
fi

# tad46 Install PHP dependencies
cd "$REPO_DIR/db"
composer install

# tad46 Load credentials from the local .env file
source "$REPO_DIR/db/.env"

# tad46 Create the database and a local MySQL user for the consumer
sudo mysql -e "CREATE DATABASE IF NOT EXISTS $MYSQL_DATABASE;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$MYSQL_USER'@'localhost' IDENTIFIED BY '$MYSQL_PASSWORD';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $MYSQL_DATABASE.* TO '$MYSQL_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# tad46 Apply the schema file if it exists (team finalizes this separately)
if [ -f "$REPO_DIR/db/db_schema.sql" ]; then
    sudo mysql $MYSQL_DATABASE < "$REPO_DIR/db/db_schema.sql"
fi

echo ""
echo "Database VM setup complete."
echo "MySQL is running with the $MYSQL_DATABASE database ready."