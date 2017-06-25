<?php
	include_once "../functions/database.php";
	include_once "../functions/teacher-requests.php";
	
	$firstName = $_REQUEST["firstName"];
	$lastName = $_REQUEST["lastName"];
	$regID = $_REQUEST["regID"];
	setTeacherRequestWithFirstLastNameRegIDAndPlatform($firstName, $lastName, $regID, "Android", getMySQL());