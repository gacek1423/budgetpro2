<?php
// budgetpro2/check.php
echo "Config file exists: " . (file_exists('config.php') ? 'TAK' : 'NIE') . "<br>";
echo "Functions file exists: " . (file_exists('includes/functions.php') ? 'TAK' : 'NIE') . "<br>";

require_once 'config.php';
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DEBUG_MODE: " . (DEBUG_MODE ? 'ON' : 'OFF');