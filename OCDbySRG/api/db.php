<?php
$db = new PDO(
    'mysql:host=localhost;dbname=kb;charset=utf8', 
    'root',
     null, 
    [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
?>