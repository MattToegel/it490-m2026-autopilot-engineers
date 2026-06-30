#!/bin/bash
# Prints MySQL databases, users, and tables for the it490_logs database

OUTFILE="db-info.txt"

{
  echo "=== All Databases ==="
  sudo mysql --table -e "SHOW DATABASES;"
  echo ""

  echo "=== All MySQL Users ==="
  sudo mysql --table -e "SELECT user, host FROM mysql.user;"
  echo ""

  echo "=== Tables in it490_logs ==="
  sudo mysql --table it490_logs -e "SHOW TABLES;"
  echo ""
  
  echo "=== Structure of logs table ==="
  sudo mysql --table it490_logs -e "DESCRIBE logs;" 2>/dev/null || echo "logs table does not exist yet"

  echo "=== Structure of users table ==="
  sudo mysql --table it490_logs -e "DESCRIBE users;" 2>/dev/null || echo "users table does not exist yet"
} > "$OUTFILE"

echo "Database info written to $OUTFILE"
cat "$OUTFILE"
