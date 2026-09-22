<?php

/*
|--------------------------------------------------------------------------
| VEYRO DATABASE CONFIGURATION
|--------------------------------------------------------------------------
|
| This file automatically detects:
|
| LOCAL WAMP
|   Host     = localhost
|   Database = veyro
|   Username = root
|   Password = ""
|
| INFINITYFREE
|   Uses your InfinityFree MySQL credentials.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Detect Environment
|--------------------------------------------------------------------------
*/

$isLocalhost = false;

$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');

if (
    strpos($httpHost, 'localhost') !== false ||
    strpos($httpHost, '127.0.0.1') !== false ||
    strpos($httpHost, '::1') !== false
) {
    $isLocalhost = true;
}


/*
|--------------------------------------------------------------------------
| LOCAL WAMP DATABASE
|--------------------------------------------------------------------------
*/

if ($isLocalhost) {

    $host = "localhost";
    $dbname = "veyro";
    $username = "root";
    $password = "";

}


/*
|--------------------------------------------------------------------------
| INFINITYFREE DATABASE
|--------------------------------------------------------------------------
|
| CHANGE THESE FOUR VALUES.
|
*/

else {

    $host = "YOUR_INFINITYFREE_MYSQL_HOST";

    $dbname = "YOUR_INFINITYFREE_DATABASE_NAME";

    $username = "YOUR_INFINITYFREE_DATABASE_USERNAME";

    $password = "YOUR_INFINITYFREE_DATABASE_PASSWORD";
}


/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    error_log(
        'VEYRO database connection failed: ' .
        $e->getMessage()
    );

    http_response_code(500);


    /*
    |--------------------------------------------------------------------------
    | Show detailed error only on localhost
    |--------------------------------------------------------------------------
    */

    if ($isLocalhost) {

        die(
            "Database connection failed: " .
            htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Hosting Error
    |--------------------------------------------------------------------------
    */

    die(
        "VEYRO is temporarily unable to connect to the database. " .
        "Please check the hosting database configuration."
    );
}