#!/bin/bash
# launch_php.sh
# Starts the PHP built-in server for CMMS-HEC (PHP Version)

PORT=8000
HOST="localhost"

echo "==========================================="
echo "   🚀 CMMS-HEC (PHP Prototype) Launcher    "
echo "==========================================="
echo "Starting PHP Server at http://$HOST:$PORT"
echo "Press Ctrl+C to stop the server."
echo "==========================================="

# Start the server
php -S $HOST:$PORT -t "cmms php"
