<?php
/*

xml: This file is the code for the protected access pages of our platform. 
if user is not  in a logged in session, then the user is redirected to 
the login in page. The page wil be protected until user logins in with 
valid credentials.
*/

//xml: conditional statment that check if there is a logged in session
if (!isset($_SESSION['user_id'])){

	//xml: redirects user to login in page if consitional is true
	header('Location: login.php');
	exit;

}
