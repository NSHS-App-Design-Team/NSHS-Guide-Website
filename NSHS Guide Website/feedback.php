<?php
	$name = $_REQUEST['name'];
	$email = $_REQUEST['email'];
	$feedback = $_REQUEST['feedback'];

	if (!$name || !$feedback)
		return;
	$recipient = "App Design Team <nshsappdesignteam@gmail.com>";
	$subject = "Feedback";
	$message = "Name: $name\nEmail: $email\nFeedback: $feedback";
	mail($recipient, $subject, $message, "From: nshsguideadmin@nshsguide.newton.k12.ma.us");
