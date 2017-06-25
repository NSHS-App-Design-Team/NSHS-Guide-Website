<?php
	include_once "../functions/database.php";
	include_once "../functions/teacher-requests.php";
	
	echo json_encode(getTeacherRequests(getMySQL()));