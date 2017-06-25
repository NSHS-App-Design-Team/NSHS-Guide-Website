<?php
	include_once '../functions/database.php';
	include_once '../functions/notification.php';

	$regID = $_REQUEST['regID'];

	if (!$regID)
	{
		echo json_encode("Null Reg ID (are you using a computer?)");
		return;
	}

	setRegIDOfPlatform($regID, "iOS", getMySQL());
	echo json_encode("Reg ID processed");