<?php
	include_once "../functions/database.php";
	include_once "../functions/announcements.php";

	$announcementArray = getAnnouncement(getMySQL());
	$announcementArray["title"] = "Announcement";
	echo json_encode($announcementArray);