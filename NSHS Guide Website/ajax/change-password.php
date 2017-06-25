<?php
	include_once '../functions/database.php';
	include_once '../functions/security.php';
	startSecureSession();

	$currentPassword = $_POST['currentPassword'];
	$newPassword = $_POST['newPassword'];
	$success = changeCurrentPasswordToNew($currentPassword, $newPassword, getMySQL());
	echo json_encode($success);