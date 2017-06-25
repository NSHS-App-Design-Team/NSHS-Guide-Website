<?php
	include_once "../functions/database.php";
	include_once "../functions/faculty.php";
	echo json_encode(getFacultyAsDictionary(getMySQL(), true));