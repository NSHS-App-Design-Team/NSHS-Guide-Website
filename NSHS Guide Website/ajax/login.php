<?php
	include_once '../functions/database.php';
	include_once '../functions/security.php';
	startSecureSession();

	$email = $_POST['email'];
	$password = $_POST['password'];
	$success = loginWithEmailPassword($email, $password, getMySQL());
	echo json_encode($success);