<?php
	include_once '../functions/database.php';
	include_once '../functions/security.php';
	startSecureSession();

	$mysql = getMySQL();
	$loggedIn = tryLoginWithCookies($mysql);
	$email = getEmail();
	$role = getRole();

	/*NOTE: Lots of info are stuffed into a single response since only 1 startSecureSession() call is
	allowed per page. If we were to separate info into 2 php pages, both of which may call startSecureSession(),
					the second call will fail.*/
	$userInfo = array(
		"loggedIn" => $loggedIn,
		"email" => $email,
		"role" => $role
	);
	echo json_encode($userInfo);