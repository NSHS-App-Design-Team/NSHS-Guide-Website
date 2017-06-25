<?php
	include_once "../functions/database.php";

	$mysqli = getMySQL();
	$stmt = $mysqli->prepare("SELECT firstName, lastName FROM absent_teachers");
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result($firstName, $lastName);

	$occurrencesForTeacher = array();
	while ($stmt->fetch())
	{
		$name = "$lastName, $firstName";
		if (array_key_exists($name, $occurrencesForTeacher))
		{
			$occurrencesForTeacher[$name] += 1;
		}
		else
		{
			$occurrencesForTeacher[$name] = 1;
		}
	}
	$stmt->close();

	arsort($occurrencesForTeacher);
	foreach ($occurrencesForTeacher as $name => $occurences) {
		echo "<p>$name was absent $occurences days</p>";
	}