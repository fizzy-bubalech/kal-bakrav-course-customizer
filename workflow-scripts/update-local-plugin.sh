#!/bin/bash

#The plugin folder
plugin_path=$HOME/Projects/course-customizer-project/course-customizer-php8.1/

#The plugins folder in the local wordpress instance
dest_path="/opt/lampp/htdocs/kalbakrav-dev/wp-content/plugins/"

if [[ -d "$plugin_path" && -d "$dest_path" ]]; then
    echo "Copying plugin folder to local destination"
    sudo cp -r "$plugin_path" "$dest_path"
    
    if [ $? -eq 0 ]; then
        echo "Copy successful"
        ls -l "${dest_path}course-customizer-php8.1/"  # Verify the files exist
        echo "Restarting server"
        sudo /opt/lampp/lampp restart

    else
        echo "Copy failed"
        exit 1
    fi
else
    echo "One or both directories don't exist"
    exit 1
fi
