#!/bin/bash

sudo /opt/lampp/lampp restart

echo "server restarted"

echo "*"
echo "*"
echo "*"

echo "opening website" 

url="http://localhost/kalbakrav-dev/wp-admin/"

if command -v xdg-open &> /dev/null; then
    # The & at the end runs the browser in the background
    # This allows your script to continue running
    xdg-open "$url" &
    
    # Wait a moment to ensure the browser starts
    sleep 1
    
    echo "Browser opened with $url"
else
    echo "Error: xdg-open not found. Please install xdg-utils package."
    exit 1
fi
