<?php
	include_once '../functions/database.php';
	include_once '../functions/security.php';
	startSecureSession();

	logout(getMySQL());
	header("Location: ../index.html");