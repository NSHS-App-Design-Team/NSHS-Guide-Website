<?php
	include_once '../functions/database.php';
	include_once '../functions/absent-teachers.php';
	echo json_encode(getAbsentTeachers(getMySQL()));