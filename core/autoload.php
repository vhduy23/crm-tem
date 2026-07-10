<?php
// Simple PSR-4 Autoloader
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = '';
    
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/../';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Convert to path
    $path = str_replace('\\', '/', $relative_class);
    
    // Lowercase the first directory (Core -> core, App -> app) for Linux case-sensitivity
    $parts = explode('/', $path);
    if (count($parts) > 1) {
        $parts[0] = strtolower($parts[0]);
    }
    $path = implode('/', $parts);

    $file = $base_dir . $path . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
