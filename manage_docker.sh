#!/bin/bash

# Check if exactly one argument is provided
if [ "$#" -ne 1 ]; then
    echo "Usage: $0 {start|stop|status|remove_containers|image_status|remove_images}"
    exit 1
fi

# Handle the specified command
case $1 in
    start)
        # Start Docker containers in detached mode
        sudo docker compose up -d
        # Ensure write permissions for a specific file
        chmod o+w src/logs/web_server_logs.txt
        ;;
    stop)
        # Stop all running Docker containers
        sudo docker stop $(sudo docker ps -a -q)
        ;;
    status)
        # Show status of all Docker containers
        sudo docker ps -a
        ;;
    remove_containers)
        # Remove all stopped Docker containers
        sudo docker rm $(sudo docker ps -a -q)
        ;;
    image_status)
        # Show status of Docker images
        sudo docker images
        ;;
    remove_images)
        # Remove all Docker images
        sudo docker rmi $(sudo docker images -a -q)
        ;;
    *)
        # Invalid command entered
        echo "Invalid command: $1"
        echo "Usage: $0 {start|stop|status|remove_containers|image_status|remove_images}"
        exit 1
        ;;
esac
