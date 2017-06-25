<?php
	include_once '../functions/database.php';
	include_once '../functions/security.php';
	include_once '../functions/special-schedule.php';
	include_once '../functions/notification.php';
	startSecureSession();
	if (getRole() != "Developer")
	{
		echo json_encode(false);
		return;
	}
	
	$startDate = $_GET["startDate"];
	$endDate = $_GET["endDate"];
	$mysqli = getMySQL();
	setNoSchool($startDate, $endDate, $mysqli);
	sendNotificationNoSchool($startDate, $endDate, $mysqli);
	echo json_encode(true);
	