<?php
header('Content-Type: application/manifest+json');
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
?>
{
  "name": "Meal Tracker",
  "short_name": "Meals",
  "start_url": "<?= $base ?>",
  "display": "standalone",
  "background_color": "#f8fafc",
  "theme_color": "#57534E",
  "icons": [
    {
      "src": "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%2357534E' width='100' height='100' rx='20'/><g transform='rotate(45 50 50)'><path d='M50 25v50M42 25v15c0 4 4 8 8 8s8-4 8-8V25' stroke='white' stroke-width='5' fill='none' stroke-linecap='round' stroke-linejoin='round'/></g></svg>",
      "sizes": "any",
      "type": "image/svg+xml",
      "purpose": "any"
    }
  ]
}