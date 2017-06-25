<?php
	include_once "../functions/database.php";
	include_once "../functions/faculty.php";

	$dictionary = getFacultyAsDictionary(getMySQL(), false);
	$dictionary["title"] = "Teachers List";
	echo json_encode($dictionary);