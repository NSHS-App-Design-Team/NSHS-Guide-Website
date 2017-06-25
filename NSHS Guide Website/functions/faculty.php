<?php
	/**
	 * Returns all the teachers
	 * @param mysqli $mysqli
	 * @param bool $asDictionary Changes returned array so that it contains each teacher as "lastName, firstName"
	 *                          if the value is set to false. Otherwise, each is a map.
	 * @return array|null Array of all the teachers, each a map with properties "firstName" and "lastName"
	 */
	function getFacultyAsDictionary($mysqli, $asDictionary)
	{
		$stmt = $mysqli->prepare("SELECT firstName, lastName FROM teacher_list ORDER BY lastName ASC");
		if (!$stmt)
			return null;
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($firstName, $lastName);
		$facultyList = array();
		if ($asDictionary)
			while ($stmt->fetch())
				$facultyList[] = array("firstName" => $firstName, "lastName" => $lastName);
		else
			while ($stmt->fetch())
				$facultyList[] = "$lastName, $firstName";
		$stmt->close();
		return $facultyList;
	}
	
	/**
	 * Insert a teacher into the teacher list. Does not check if the teacher exists
	 * @param string $firstName
	 * @param string $lastName
	 * @param mysqli $mysqli
	 */
	function addTeacher($firstName, $lastName, $mysqli)
	{
		$stmt = $mysqli->prepare("INSERT INTO teacher_list (firstName, lastName) VALUES (?,?)");
		if (!$stmt)
			return;
		$stmt->bind_param("ss", $firstName, $lastName);
		$stmt->execute();
		$stmt->close();
	}
	/**
	 * Remove a teacher from the teacher list. Does not check if the teacher exists
	 * @param string $firstName
	 * @param string $lastName
	 * @param mysqli $mysqli
	 */
	function deleteTeacher($firstName, $lastName, $mysqli)
	{
		$stmt = $mysqli->prepare("DELETE FROM teacher_list WHERE firstName = ? AND lastName = ?");
		if (!$stmt)
			return;
		$stmt->bind_param("ss", $firstName, $lastName);
		$stmt->execute();
		$stmt->close();
	}