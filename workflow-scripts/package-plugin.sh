#!/bin/bash
directory="./course-customizer-php8.1"
version="$1"
if ! [ -d "$directory" ]; then 
    echo "$directory doesn't exist"
    exit 1
fi

# Function to validate version format
validate_version() {
    local ver=$1
    if [[ $ver =~ ^v[0-9]+\.[0-9]+$ ]]; then
        return 0  # Valid format
    else
        return 1  # Invalid format
    fi
}

if [ -z "$version" ]; then
    echo "No version provided."
    echo "Please provide a version (format: vX.X):"
    read ver
    version=$ver
fi

# Check version format
if ! validate_version "$version"; then
    echo "Invalid version format. Must be vX.X (e.g., v1.2)"
    exit 1
fi

# Get current date in dd.mm format
current_date=$(date +%d.%m)
new_dir="$directory-$version-$current_date"

# Check if target directory already exists
if [ -d "$new_dir" ]; then
    echo "Directory $new_dir already exists"
else
    mkdir "$new_dir"
fi

echo "Zipping plugin"
zip -q -r "course-customizer-php8.1.zip" "$directory"
echo "zipped succesufuly"
echo "Copying plugin dir to dir"
cp -r "course-customizer-php8.1" "$new_dir" 2>/dev/null
echo "packing complete"
