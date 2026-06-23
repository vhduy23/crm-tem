<?php
echo "GD extension: " . (extension_loaded('gd') ? 'enabled' : 'disabled') . "\n";
echo "Webp support: " . (function_exists('imagewebp') ? 'yes' : 'no') . "\n";

echo phpinfo();