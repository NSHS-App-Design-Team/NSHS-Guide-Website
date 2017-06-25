<?php
	/**
	 * @param array $absentTeachers Array of absent teachers in format [0] = "Chu, David|011000100|info", [1] = "Lin, Eric|011000100|", etc
	 * @param mysqli $mysqli
	 */
	function setAbsentTeachers($absentTeachers, $mysqli)
	{
		$date = date("Ymd");

		//clear any previous recorded absences for today
		$mysqli->query("DELETE FROM absent_teachers WHERE date LIKE $date");

		foreach ($absentTeachers as $teacher)
		{
			$parts = explode("|", $teacher);
			$name = $parts[0];
			$absentBlocks = str_split($parts[1]);
			$info = $parts[2];

			$nameParts = explode(", ", $name);
			$lastName = $nameParts[0];
			$firstName = $nameParts[1];

			$A = $absentBlocks[0];
			$B = $absentBlocks[1];
			$C = $absentBlocks[2];
			$D = $absentBlocks[3];
			$E = $absentBlocks[4];
			$F = $absentBlocks[5];
			$G = $absentBlocks[6];
			$H = $absentBlocks[7];
			$J = $absentBlocks[8];
			$stmt = $mysqli->prepare("INSERT INTO absent_teachers(firstName,lastName,date,A,B,C,D,E,F,G,H,J,Info)
										VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("sssssssssssss", $firstName,$lastName,$date,$A,$B,$C,$D,$E,$F,$G,$H,$J,$info);
			$stmt->execute();
		}
	}

	/**
	 * @param mysqli $mysqli
	 * @return array Array of absent teachers in format [0] = "Chu, David|011000100|info", [1] = "Lin, Eric|011000100|", etc
	 */
	function getAbsentTeachers($mysqli)
	{
		$stmt = $mysqli->prepare("SELECT firstName, lastName, A,B,C,D,E,F,G,H,J,Info FROM absent_teachers WHERE date = ? ORDER BY lastName ASC");
		if (!$stmt)
			return array();
		$stmt->bind_param('i', date('Ymd'));
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($first, $last, $A, $B, $C, $D, $E, $F, $G, $H, $J, $Info);
		
		$teachers = array();
		while ($stmt->fetch())  //store it in format "Chu, David|011000100|info"
			$teachers[] = $last . ", " . $first . "|" . $A . $B . $C . $D . $E . $F . $G . $H . $J . "|" . $Info;
		$stmt->close();
		return $teachers;
	}