#!/bin/bash

#The plugin folder
plugin_path=$HOME/Projects/kal-bakrav-course-customizer/src

#The plugins folder in the local wordpress instance
dest_path="$HOME/Projects/kalbakrav.co.il/wp-content/plugins/"

if [[ -d "$plugin_path" && -d "$dest_path" ]]; then
    echo "Copying plugin folder to local destination"
    sudo cp -r "$plugin_path" "$dest_path"
    
    if [ $? -eq 0 ]; then
        echo "Copy successful"
        ls -l "${dest_path}src/"  # Verify the files exist
        sudo rm -r "${dest_path}course-customizer-php8.1"
        sudo mv "${dest_path}src/" "${dest_path}course-customizer-php8.1" 
        #echo "Restarting server"
        #sudo /opt/lampp/lampp restart

    else
        echo "Copy failed"
        exit 1
    fi
else
    echo "One or both directories don't exist"
    exit 1
fi
