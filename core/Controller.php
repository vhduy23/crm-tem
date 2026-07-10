<?php
namespace Core;

class Controller {
    // Render a view and pass data to it
    protected function view($view, $data = []) {
        // Extract data to variables
        extract($data);
        
        $file = __DIR__ . "/../app/Views/$view.php";
        if (file_exists($file)) {
            require $file;
        } else {
            die("View does not exist: $view");
        }
    }
}
