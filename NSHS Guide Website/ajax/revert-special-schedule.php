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
	
	$date = $_GET["date"];
	$mysqli = getMySQL();
	deleteSpecialSchedule($date, $mysqli);
	sendNotificationRevertSchedule($date, $mysqli);
	echo json_encode(true);
	