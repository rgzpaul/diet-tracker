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
      "src": "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%2357534E' width='100' height='100' rx='20' ry='20'/><path d='M68.3,31.7l-43.6,43.6M61.3,24.7l-13.1,13.1c-3.5,3.5-3.5,10.5,0,14s10.5,3.5,14,0l13.1-13.1' stroke='white' stroke-width='5' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>",
      "sizes": "any",
      "type": "image/svg+xml",
      "purpose": "any"
    }
  ]
}