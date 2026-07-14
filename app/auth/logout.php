<?php
/*xml: This file is in charge of removing 
data within a session. Destorying that data
, and redirecting users to the login page
*/
session_start();
//xml: removes all data stored in the session
$_SESSION = [];

//xml: Destorys the session
session_destroy();

//xml: This redirects users to the login page
header('Location: login.php');

exit;
