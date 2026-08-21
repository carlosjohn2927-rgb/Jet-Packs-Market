<?php
defined('BASEPATH') OR exit('No direct script access allowed');

echo "Severity: ", isset($severity) ? $severity : '', "\n";
echo "Message:  ", isset($message) ? $message : '', "\n";
echo "Filename: ", isset($filepath) ? $filepath : '', "\n";
echo "Line Number: ", isset($line) ? (int) $line : '', "\n";
