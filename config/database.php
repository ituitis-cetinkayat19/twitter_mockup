<?php
    define("DB_HOST", "localhost");
    define("DB_USER", "tarik");
    define("DB_PASS", "Tarikc134!");
    define("DB_NAME", "twitter_mockup_db");

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if($conn->connect_error)
        die("Connection failed" . $conn->connect_error);

?>