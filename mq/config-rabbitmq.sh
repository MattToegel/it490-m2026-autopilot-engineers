#!/bin/bash

echo "Configuring RabbitMQ..."

# cao39: Enable the RabbitMQ Management Plugin 
sudo rabbitmq-plugins enable rabbitmq_management

# cao39: Wait 5 seconds to start
sleep 5

# cao39: Check if the RabbitMQ user named "it490" already exists.
if ! sudo rabbitmqctl list_users | grep -q "it490"; then

    # cao39: Create a new RabbitMQ user named "it490" with the password "it490".
    sudo rabbitmqctl add_user it490 it490

    # cao39: Give the user full configure, write, and read permissions 
    sudo rabbitmqctl set_permissions -p / it490 ".*" ".*" ".*"

else

    # cao39: Display a message if the user already exists 
    echo "RabbitMQ user already exists."

fi


echo "Downloading rabbitmqadmin..."

# cao39: Check if rabbitmqadmin already exists 
if [ ! -f rabbitmqadmin ]; then

    # cao39: Download rabbitmqadmin from the local RabbitMQ 
    curl -u guest:guest \
        http://localhost:15672/cli/rabbitmqadmin \
        -o rabbitmqadmin

    # cao39: Make the rabbitmqadmin executable
    chmod +x rabbitmqadmin

fi

echo "RabbitMQ configuration complete."