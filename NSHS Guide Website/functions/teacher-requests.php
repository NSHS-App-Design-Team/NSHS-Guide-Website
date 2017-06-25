<?php
	/**
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $regID
	 * @param string $platform Either "Android" or "iOS"
	 * @param mysqli $mysqli
	 */
	function setTeacherRequestWithFirstLastNameRegIDAndPlatform($firstName, $lastName, $regID, $platform, $mysqli)
	{
		$stmt = $mysqli->prepare("INSERT INTO teacher_requests (firstName, lastName, regID, isAndroid) VALUES (?, ?, ?, ?)");
		if (!$stmt)
			return;
		$stmt->bind_param("sss", $firstName, $lastName, $regID, $platform == "Android");
		$stmt->execute();
		$stmt->close();
	}

	/**
	 * @param mysqli $mysqli
	 * @return array|null Array of all teacher requests, each a map with "firstName", "lastName", "regID", "platform" for keys
	 */
	function getTeacherRequests($mysqli)
	{
		$stmt = $mysqli->prepare("SELECT firstName, lastName, regID, isAndroid FROM teacher_requests ORDER BY lastName ASC");
		if (!$stmt)
			return null;
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($firstName, $lastName, $regID, $isAndroid);
		$teacherRequests = array();
		while ($stmt->fetch())
			$teacherRequests[] = array("firstName" => $firstName, "lastName" => $lastName, "regID" => $regID, "platform" => $isAndroid ? "Android" : "iOS");
		$stmt->close();
		return $teacherRequests;
	}

	/**
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $regID
	 * @param mysqli $mysqli
	 */
	function deleteTeacherRequestWithFirstLastNameRegID($firstName, $lastName, $regID, $mysqli)
	{
		$stmt = $mysqli->prepare("DELETE FROM teacher_requests WHERE (firstName, lastName, regID) = (?, ?, ?)");
		if (!$stmt)
			return;
		$stmt->bind_param("sss", $firstName, $lastName, $regID);
		$stmt->execute();
		$stmt->close();
	}