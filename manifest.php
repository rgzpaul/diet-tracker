<?php
header('Content-Type: application/manifest+json');
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
?>
{
  "name": "Diet Tracker",
  "short_name": "Diet",
  "start_url": "<?= $base ?>",
  "display": "standalone",
  "background_color": "#f8fafc",
  "theme_color": "#57534E",
  "icons": [
    {
      "src": "icon.png",
      "sizes": "any",
      "type": "image/png",
      "purpose": "any"
    }
  ]
}