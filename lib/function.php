<?php
function isDetail(){
    $current_url = $_SERVER['REQUEST_URI'];

    return strpos($current_url, 'thiet-ke') !== false;
}




