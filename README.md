# Diet Tracker

A simple web-based meal tracking application for monitoring daily food intake and macronutrients.

## Features

- Track daily meals with protein, carbs, and fat
- Automatic calorie calculation
- Add meals from a customizable meal database
- Weekly report view
- PWA support for mobile installation

## Requirements

- PHP 7.0 or higher
- Web server (Apache, Nginx, etc.)

## Installation

1. Clone the repository
2. Deploy to a PHP-enabled web server
3. Ensure the `data/` directory is writable

## Usage

- **index.php** - Main page to log daily meals
- **create.php** - Manage your meal database
- **report.php** - View weekly meal reports

## Data Storage

Meal data is stored in JSON files in the `data/` directory:
- `meals.json` - Your saved meal templates
- `daily_meals.json` - Daily meal log entries
