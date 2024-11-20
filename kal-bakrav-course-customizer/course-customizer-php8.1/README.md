# Course Customizer

## Description

Course Customizer is a WordPress plugin designed to enhance LearnDash courses with custom exercises, advanced quiz handling, and detailed result tracking. It provides both admin-side management tools and front-end enhancements for quizzes and course content.

Version: 0.2.1
Author: AST

## Features

- Custom exercise management
- Advanced quiz answer validation and storage
- Detailed results management with filtering and sorting capabilities
- Data visualization for exercise results
- Expression evaluation within course content
- Time-based exercise support
- Integration with LearnDash quizzes

## Installation

1. Upload the `course-customizer` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

### Admin Interface

- **Manage Exercises**: Add, edit, and delete custom exercises
- **Manage Results**: View, filter, and manage user results for exercises

### Shortcodes

- `[data_visualization exercises="Exercise1, Exercise2"]`: Displays a graph of results for specified exercises.

- `[user_results_table]`: Displays a table of the current user's exercise results.

- `[eval formula="expression"]` : an alternative to the `%%expression%%` syntax.

### Quiz Integration

The plugin automatically integrates with LearnDash quizzes, providing:
- Custom answer validation
- Automatic result storage
- Time-based question support

### Expression Evaluation

Use `%%expression%%` syntax within course content to evaluate custom expressions based on user exercise results.

## Requirements

- WordPress 5.0+
- PHP 8.1+
- LearnDash LMS plugin

## Configuration

1. After activation, visit the 'Course Customizer' menu in the WordPress admin panel
2. Set up exercises in the 'Exercises' submenu


## Changelog

### 0.2.1
- Added Hebrew button support

### 0.2.0
- Added data visualization features
- Improved quiz integration
- Enhanced admin interface for result management

### 0.1.0
- Initial release
