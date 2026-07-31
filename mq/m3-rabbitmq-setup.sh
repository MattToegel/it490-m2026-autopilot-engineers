#!/bin/bash
# cao39 - installs RabbitMQ on a target lane's MQ VM via SSH, declares
# its queues/exchanges/bindings, and fixes local hostname resolution
# Usage: ./m3-rabbitmq-setup.sh <user@host> <hostname>
# e.g.:  ./m3-rabbitmq-setup.sh cao39@100.115.108.121 mqqa

TARGET=$1
HOSTNAME_LABEL=$2
[ -z "$TARGET" ] && { echo "Usage: $0 <user@host> <hostname>"; exit 1; }
[ -z "$HOSTNAME_LABEL" ] && { echo "Usage: $0 <user@host> <hostname>"; exit 1; }

USER="${TARGET%@*}"
HOST="${TARGET#*@}"

# cao39 fix local DNS resolution on mqdev so bare hostnames like
# performs a grep check to prevent duplicate lines if this script is re-run.
echo "Ensuring /etc/hosts entry for $HOSTNAME_LABEL ($HOST) on mqdev..."
if ! grep -q "$HOSTNAME_LABEL" /etc/hosts; then
    echo "$HOST $HOSTNAME_LABEL" | sudo tee -a /etc/hosts
else
    echo "/etc/hosts entry for $HOSTNAME_LABEL already exists."
fi

# cao39 grant passwordless sudo on the target VM.
# terminal so sudo can prompt for the password ONE TIME here -
# every sudo call after this point runs with no prompt needed.
echo "Setting up passwordless sudo on $TARGET (password prompt once)..."
ssh -t "$TARGET" "echo '$USER ALL=(ALL) NOPASSWD: ALL' | sudo tee /etc/sudoers.d/$USER-nopasswd && sudo chmod 440 /etc/sudoers.d/$USER-nopasswd"

# cao39 installs and starts the RabbitMQ 
# print status as proof it's running
echo "Installing RabbitMQ on $TARGET..."
ssh "$TARGET" '
    set -e
    sudo apt-get update -y
    sudo apt-get install -y rabbitmq-server
    sudo systemctl enable --now rabbitmq-server
    sudo rabbitmq-plugins enable rabbitmq_management
    sudo rabbitmqctl add_user it490 it490 2>/dev/null || echo "RabbitMQ user already exists."
    sudo rabbitmqctl set_permissions -p / it490 ".*" ".*" ".*"
    echo "RabbitMQ ready on $(hostname)"
    sudo systemctl status rabbitmq-server --no-pager
'

# cao39 declares the project's exchanges/queues/bindings on the
# target VM - runs locally on mqdev, connects out over AMQP
echo "Declaring queues/exchanges on $HOST..."
RABBITMQ_HOST="$HOST" RABBITMQ_USER=it490 RABBITMQ_PASS=it490 \
    php ~/it490-m2026-autopilot-engineers/mq/MVP/setup-mqqueue.php

echo "Setup complete for $TARGET ($HOSTNAME_LABEL)"