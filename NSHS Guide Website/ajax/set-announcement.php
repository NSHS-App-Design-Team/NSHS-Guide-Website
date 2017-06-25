<?php
	include_once "../functions/database.php";
	include_once "../functions/announcements.php";
	include_once "../functions/notification.php";
	include_once '../functions/security.php';
	startSecureSession();
	
	if (getRole() != "Developer")
		return;
	$endEpoch = $_GET["endEpoch"];
	$info = $_GET["info"];
	$mysqli = getMySQL();
	setAnnouncement($info, $endEpoch, $mysqli);
	sendNotificationAnnouncement($info, $endEpoch, $mysqli);