<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLogged()
{
    return isset($_SESSION['eposta']);
}

function redirect_location($page = "index.php")
{
    return header("Location: $page");
}
function connect_database()
{
    $serverName = "db_server";
    $databaseName = "test_PROJEODEV";
    $uid = "sa";
    $pwd = "GucluBirSifre123!";
    try {
        $conn = new PDO("sqlsrv:server=$serverName;Database=$databaseName;TrustServerCertificate=true", $uid, $pwd);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        echo $e->getMessage();
        return null;
    }
}
