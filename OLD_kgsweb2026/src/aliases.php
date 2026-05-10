<?php
// This file exists to satisfy the Google API Autoloader pathing
if (class_exists('Google_Client') && !class_exists('Google\Client')) {
    class_alias('Google_Client', 'Google\Client');
}