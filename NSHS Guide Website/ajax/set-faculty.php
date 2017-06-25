<?php
	include_once "../functions/database.php";
	include_once "../functions/faculty.php";
	include_once '../functions/security.php';
	include_once "../functions/notification.php";
	startSecureSession();
	
	if (getRole() != "Developer")
		return;
	$firstNameToDelete = $_GET["firstNameToDelete"];
	$lastNameToDelete = $_GET["lastNameToDelete"];
	$firstNameToAdd = $_GET["firstNameToAdd"];
	$lastNameToAdd = $_GET["lastNameToAdd"];
	$mysqli = getMySQL();
	
	if ($firstNameToDelete != null && $lastNameToDelete != null)
		deleteTeacher($firstNameToDelete, $lastNameToDelete, $mysqli);
	if ($firstNameToAdd != null && $lastNameToAdd != null)
		addTeacher($firstNameToAdd, $lastNameToAdd, $mysqli);
	
	sendNotificationTeachersList(getFacultyAsDictionary($mysqli, true), $mysqli);
	//must echo something new every time so the faculty-container can update
	echo json_encode(time());