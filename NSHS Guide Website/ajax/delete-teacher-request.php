<?php
	include_once "../functions/database.php";
	include_once "../functions/teacher-requests.php";
	include_once "../functions/faculty.php";
	include_once "../functions/notification.php";

	$firstName = $_REQUEST["firstName"];
	$lastName = $_REQUEST["lastName"];
	$regID = $_REQUEST["regID"];
	$platform = $_REQUEST["platform"];
	$mysqli = getMySQL();
	deleteTeacherRequestWithFirstLastNameRegID($firstName, $lastName, $regID, $mysqli);
	
	$approved = $_REQUEST["approved"];
	if ($approved)
		addTeacher($firstName, $lastName, $mysqli);
	
	sendNotificationTeacherRequest($firstName, $lastName, $regID, $platform, $approved);