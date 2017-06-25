<?php
	include_once '../functions/database.php';
	include_once '../functions/absent-teachers.php';
	
	$dictionary = getAbsentTeachers(getMySQL());
	$dictionary["title"] = "Absent Teachers";
	$dictionary["date"] = date("n|j");
	echo json_encode($dictionary);