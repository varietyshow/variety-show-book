<?php
require_once __DIR__ . '/vendor/autoload.php';
if (class_exists('MagpieApi\\Magpie')) {
    echo "Magpie SDK loaded!";
} else {
    echo "Magpie SDK NOT FOUND!";
}
