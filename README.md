# Course Customizer

## Description

Course Customizer is a WordPress plugin designed to enhance LearnDash courses with custom exercises, advanced quiz handling, and detailed result tracking. It provides both admin-side management tools and front-end enhancements for quizzes and course content.
TEST
Version: 0.2.11
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

- `[quiz_completed_redirect url="URL"]` upon the ajax request submit_all_answer redirects to the url after 3 seconds.

- `[require_quiz quiz_id ="ID"]` checks if the given quiz of the quiz_id has been completed, redirects to the quiz if not.

### Quiz Integration

The plugin automatically integrates with LearnDash quizzes, providing:
- Custom answer validation
- Automatic result storage
- Time-based question support

### Expression Evaluation

Use `%%expression%%` syntax within course content to evaluate custom expressions based on user exercise results.

Put int(EXPRESSION) around a *whole* expression to force an int output. 

## Requirements

- WordPress 5.0+
- PHP 8.1+
- LearnDash LMS plugin

## Configuration

1. After activation, visit the 'Course Customizer' menu in the WordPress admin panel
2. Set up exercises in the 'Exercises' submenu


## Changelog

### 0.2.11

- Changed the way variables are rounded when evaluating expressions. 

### 0.2.10

- Fixed bug where substring detected as function call.

### 0.2.9

- Fixed deleting and adding exercises.

### 0.2.8 

- Added min and max values for exercies in the exercises table. when filling in asnwers the checks makes sure the value given is between the min and max.
- Added the `[user_display_name]` shortcode which returns the current user's display. 
- Optimized the answer checker. Now registers any input imidiatly.
- Added special messages if the user provides answers out of the min max bounds. 
- Added checkbox to quiz with message clearfing the one-time nature of the quiz 
- Move shortcodes to their own class in /includs 
- Added css to disable future lessons in course page 
- Added a script in lessons that disables the "Next Lesson" button if the next lesson hasn't been completed. 

### 0.2.7

- Fixed a bug that didn't let the user submit answers after filling them out

### 0.2.6

- Added one page quiz support

### 0.2.5

- Bug fixes

### 0.2.4

- Added forced integer output for expressions with int() 

- Added require_quiz shortcode to force linear course progression 

### 0.2.3 

- Changed the `[url_redirect]` shortcode to `[quiz_completed_redirect url="URL"]` and it's now using js ajax request when the quiz is submitted

### 0.2.2

- Added `[url_redirect url="URL"]` shortcode 

### 0.2.1

- Updated quiz validation to be check mark and cross mark.

### 0.2.0

- Added data visualization features

- Improved quiz integration

- Enhanced admin interface for result management

### 0.1.0

- Initial release
