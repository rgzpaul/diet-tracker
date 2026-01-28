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
      "src": "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect fill='%2357534E' width='100' height='100' rx='20'/><path d='M35 20v25c0 5 5 10 10 10v25a5 5 0 0010 0V55c5 0 10-5 10-10V20M40 20v20M50 20v20M60 20v20' stroke='white' stroke-width='4' fill='none' stroke-linecap='round' stroke-linejoin='round'/></svg>",
      "sizes": "any",
      "type": "image/svg+xml",
      "purpose": "any"
    }
  ]
}