<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$message = isset($message) ? $message : '(null)';
echo "\nUncaught exception: ", $message, "\n";
if (isset($exception) && $exception instanceof Throwable) {
    echo "File: ", $exception->getFile(), " (", (int) $exception->getLine(), ")\n";
}
echo "\n";
