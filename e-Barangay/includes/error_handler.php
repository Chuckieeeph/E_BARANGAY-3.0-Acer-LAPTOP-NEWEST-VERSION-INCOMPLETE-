<?php
// Custom Error + Exception Handler

function show503() {
    http_response_code(503);
    include __DIR__ . '/../public/errors/503.php';
    exit;
}

function show404() {
    http_response_code(404);
    include __DIR__ . '/../public/errors/404.php';
    exit;
}

function errorHandler($errno, $errstr, $errfile, $errline) {
    // You can log errors here if needed.
    show503();
}

function exceptionHandler($exception) {
    // You can log exceptions here if needed.
    show503();
}

set_error_handler("errorHandler");
set_exception_handler("exceptionHandler");

// For fatal errors (shutdown)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        show503();
    }
});
