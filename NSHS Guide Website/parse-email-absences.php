<?php
	include_once 'functions/database.php';
	include_once 'functions/faculty.php';
	include_once 'format-email-absences.php';
	include_once 'functions/notification.php';
	include_once 'functions/absent-teachers.php';

	//gets info using $_REQUEST, don't need to escape as we're not inputting into database immediately
	$email = $_REQUEST['email'];
	if (!isset($email))
		return;

	$mysqli = getMySQL();
	$formattedTeachersAndAnnouncement = format($email);
	$formattedTeachers = $formattedTeachersAndAnnouncement[0];
	$announcement = $formattedTeachersAndAnnouncement[1];
	$faculty = getFacultyAsDictionary($mysqli, true);
	$parsedTeachers = parse($formattedTeachers, $faculty);
	setAbsentTeachers($parsedTeachers, $mysqli);
	sendNotificationAbsentTeachers($parsedTeachers, $mysqli);
	trySendAnnouncement($announcement, $mysqli);
	//use json_encode to get readable form
	mail("App Design Team <nshsappdesignteam@gmail.com>", "Processed absences: parsed", json_encode($parsedTeachers) . "\n" . $email, "From: nshsguideadmin@nshsguide.newton.k12.ma.us");


	/*
	 * FUNCTIONS BEGIN HERE
	 */

	/**
	 * Accepts array of teachers that look like this: "lastName\tfirstName\tInfo" and a list of teachers, outputs the list
	 * in a format accepted by NSHS Guide
	 *
	 * @param array $formattedTeachers Array of absent teachers; each is a map that has keys "lastName", "firstName", and "Info"
	 * @param array $teacherList Array of teachers; each is a map that has keys "lastName" and "firstName"
	 * @see format()
	 * @see getTeacherListFromMySQL()
	 * @return array Array of teachers like this: [0] = "lastName, firstName|011100011|Remaining Info"
	 */
	function parse($formattedTeachers, $teacherList)
	{
		$parsedTeachers = array();
		foreach ($formattedTeachers as $teacherWithInfo)
		{
			$lastName = correctlastName($teacherWithInfo["lastName"]);
			$firstName = $teacherWithInfo["firstName"];

			$correctName = findCorrectName($lastName, $firstName, $teacherList);
			$info = $teacherWithInfo["info"];
			$absentBlocks = findBlocksAbsent($info);

			$text = "$correctName|$absentBlocks|";
			if (isset($info))
				$text .= "$info";
			$parsedTeachers[] = $text;
		}
		return $parsedTeachers;
	}

	/**
	 * Tries to find the correct name of a teacher from a potentially misspelled name.
	 *
	 * @param string $lastName
	 * @param string $firstName
	 * @param array $teacherList
	 * @see getTeacherListFromMySQL()
	 * @return string "lastName, firstName"
	 */
	function findCorrectName($lastName, $firstName, $teacherList)
	{
		$firstNames = teachersWithLastName($lastName, $teacherList);
		//if we have lots of matching first names
		if (count($firstNames) > 0)
		{
			//if one of the first names are identical to the one we have
			if (in_array($firstName, $firstNames))
				return "$lastName, $firstName";
			else
			{
				$matchedFirstName = bestMatch($firstName, $firstNames);
				return "$lastName, $matchedFirstName";
			}
		}

		//no last name matched, return original
		//TODO Append this teacher to the faculty list?
		return "$lastName, $firstName";
	}

	/**
	 * Corrects the last name from the email by going through a list of exceptions. If the last name isn't in the list
	 * of exceptions, then the original last name will be returned.
	 * Note that this attempts to find two connected, capitalized words in the last name and separates them.
	 * So names like "WildmanZinger" will become "Wildman Zinger". Names that start with "Mc" or "O'" are ignored.
	 *
	 * @param string $lastName Last name entered by Ms. Connolly
	 * @return string Corrected last name if it's listed as an exception, otherwise the same last name given
	 */
	function correctlastName($lastName)
	{
		//exceptions where Ms. Connolly screws up and we can't fix it automatically
		switch ($lastName)
		{
			case "Sheng Chen":
				return "Chen";
			case "Soohoo":
				return "Soo Hoo";
			case "vanBeever":
				return "Van Beever";
			case "Glickman-Hock":
				return "Glickman-Hoch";
			case "Quern":
				return "Jordan Quern";
			case "Sobin-Jonash":
				return "Sobin Jonash";
			case "De Pari":
				return "DePari";
		}

		//put a space between capitalized words if possible
		$matches = array();
		if (preg_match('/^([A-Z])([a-z]+)([A-Z])([a-z]+)/', $lastName, $matches))
			if (strpos($lastName, "Mc") === false && strpos($lastName, "De") === false)
				return $matches[1] . $matches[2] . " " . $matches[3] . $matches[4];

		return $lastName;
	}

	/**
	 * Returns a list of teachers with the last name
	 *
	 * @param string $lastName
	 * @param array $teacherList
	 * @see getTeacherListFromMySQL()
	 * @return array Array of first names of teachers with the same last name
	 */
	function teachersWithLastName($lastName, $teacherList)
	{
		$firstNames = array();
		foreach ($teacherList as $teacher)
			if ($teacher["lastName"] == $lastName)
				$firstNames[] = $teacher["firstName"];
		return $firstNames;
	}

	/**
	 * Uses a fuzzy string search to find the best matching string in the haystack to the needle.
	 *
	 * @param string $needle The actual input
	 * @param array $haystack The options
	 * @return string The best matching string
	 */
	function bestMatch($needle, $haystack)
	{
		//return the first element if there's only one (nothing to compare against)
		if (count($haystack) == 1)
			return $haystack[0];

		$needleAsChars = str_split(strtolower($needle));
		$needleLength = count($needleAsChars);

		$bestMatch = null;
		$bestScore = 0;
		foreach ($haystack as $hay)
		{
			$hayAsChars = str_split(strtolower($hay));
			$hayLength = count($hayAsChars);

			$matches = 0;
			$mostConsecutiveMatches = 0;

			for ($hayIndex = 0; $hayIndex < $hayLength; $hayIndex++)
			{
				$consecutiveMatches = 0;
				for ($needleIndex = 0; $needleIndex < $needleLength; $needleIndex++)
				{
					if ($hayAsChars[$hayIndex] != $needleAsChars[$needleIndex])
						continue;
					$matches++;

					//FIND CONSECUTIVE MATCHES
					//cannot mutate indices
					$tempHayIndex = $hayIndex;
					$tempNeedleIndex = $needleIndex;
					//count number of successive, identical chars
					while ($hayAsChars[$tempHayIndex] == $needleAsChars[$tempNeedleIndex])
					{
						$consecutiveMatches++;
						$tempHayIndex++;
						$tempNeedleIndex++;

						if ($tempHayIndex >= $hayLength || $tempNeedleIndex >= $needleLength)
							break;
					}
					if ($consecutiveMatches > $mostConsecutiveMatches)
						$mostConsecutiveMatches = $consecutiveMatches;
				}
			}

			$score = $matches + $mostConsecutiveMatches * $mostConsecutiveMatches;
			if ($score > $bestScore)
			{
				$bestScore = $score;
				$bestMatch = $hay;
			}
		}
		return $bestMatch;
	}

	/**
	 * Finds the blocks a teacher is absent for depending on what is written in their info. Parses using keywords
	 * in Ms. Connolly's emails. Returns blocks as a string in binary format, with ABCDEFGHJ each represented by a 1 or 0,
	 * 1 = teacher absent, and 0 = teacher present.
	 * For example, someone who is absent all day will have "111111111" returned, someone who is only absent B block will
	 * have "010000000" returned, etc.
	 *
	 * @param string $info Info, can be null
	 * @return string String of absent blocks in binary format, 1 = teacher absent
	 * @see absentBlocksFromInstructions()
	 */
	function findBlocksAbsent($info)
	{
		$lowerCaseInfo = strtolower($info);
		//empty info defaults to absent for the whole day
		if (!isset($info) || $lowerCaseInfo == "classes cancelled")
			return "111111111";
		//any info that contains "classes" and "as usual" but not "other" means the teacher is here for the whole day
		if (strpos($lowerCaseInfo, "as usual") !== false)
			if (strpos($lowerCaseInfo, "class") !== false)
				if (strpos($lowerCaseInfo, "other") === false)
					return "000000000";

		$instructions = instructionsFromInfo($info);
		return absentBlocksFromInstructions($instructions);
	}

	/**
	 * Splits info into instructions separated by commas: "A, B, C & D blocks as usual, all other classes cancelled" will
	 * be split into "A, B, C & D blocks as usual" and the remaining. Each part is an instruction on how to format the
	 * absences.
	 *
	 * @param string $info
	 * @return array Array of instructions
	 */
	function instructionsFromInfo($info)
	{
		$instructions = explode(", ", $info);

		//just in case non-instructions were also separated, pair them back together
		$length = count($instructions);
		for ($i = 0; $i < $length; $i++)
		{
			$instruction = $instructions[$i];
			//only pair together single, capital letter instructions with spaces and commas, like "A, B" with "C & D"
			//also pairs advisory, case insensitive
			if (!preg_match("/^([A-Z ,]|(?i)(advisory)|(?i)(adv))+$/", $instruction))
				continue;

			$instructions[$i + 1] = $instruction . ", " . $instructions[$i + 1];
			array_splice($instructions, $i, 1);
			$length--;
			$i--;
		}
		return $instructions;
	}

	/**
	 * Deduces the blocks a teacher is absent for based on the information written by Ms. Connolly. Returns the absent
	 * blocks in a binary format.
	 *
	 * @param array $instructions Separate pieces of information in an array to deduce absences from
	 * @see instructionsFromInfo()
	 * @return string String of absent blocks in binary format, 1 = teacher absent
	 */
	function absentBlocksFromInstructions($instructions)
	{
		$unsetBlockLetters = array("a", "b", "c", "d", "e", "f", "g", "advisory", "j");
		$absentBlocks = str_split("111111111");  //absent until proven present

		foreach ($instructions as $instruction)
		{
			$absent = absentForInstruction($instruction);
			setAbsentForInstruction($absent, $instruction, $unsetBlockLetters, $absentBlocks);
		}

		//if the last instruction includes "other", set all other blocks to either present or absent depending on wording
		$lastInstruction = array_pop($instructions);
		if (strpos($lastInstruction, "other") !== false)
		{
			$absentForAllOtherBlocks = absentForInstruction($lastInstruction);
			foreach ($unsetBlockLetters as $index => $blockLetter)
				$absentBlocks[$index] = $absentForAllOtherBlocks ? "1" : "0";
		}

		return join("", $absentBlocks);
	}

	/**
	 * Sees if specific keywords are in the instruction in order to deduce whether the teacher is absent or not. Doesn't
	 * care about which blocks the teacher is absent for.
	 *
	 * @param string $instruction An individual piece of info to deduce absences from
	 * @see instructionsFromInfo()
	 * @return bool Whether the teacher is absent based on the instruction. Defaults to true
	 */
	function absentForInstruction($instruction)
	{
		//not absent if...
		//some blocks as "usual"
		if (strpos($instruction, "usual") !== false)
			return false;
		//some blocks "to" some person or room number that isn't 6167
		if (strpos($instruction, " to ") !== false)
			if (strpos($instruction, "6167") === false)
				return false;
		return true;
	}

	/**
	 * Finds the blocks that are listed in the instruction and sets $absentBlocks depending on whether the teacher is
	 * absent or present for those blocks.
	 *
	 * @param bool $absent Whether the teacher is absent for this particular instruction
	 * @param string $instruction An individual piece of info to find block letters from
	 * @see instructionsFromInfo()
	 * @param array $unsetBlockLetters A reference to an array of block letters that for which the teacher's presence has
	 * yet to be determined. Block letters will be deleted from this array as his/her absence/presence is determined for
	 * those blocks and translated into $absentBlocks
	 * @param array $absentBlocks A reference to an array of strings in binary form, showing 1 if the teacher is absent
	 * for that block and 0 if he/she is not. Elements correspond to that of block letters.
	 */
	function setAbsentForInstruction($absent, $instruction, &$unsetBlockLetters, &$absentBlocks)
	{
		//divide "A, B, C & D blocks as usual" into "A", "B", "C", "D", "blocks", "as", "usual"
		$parts = preg_split("/[ &,]+/", $instruction);
		$length = count($parts);
		$index = 0;

		//go through all block letters in instruction
		$blockLetterIndex = array_search(strtolower($parts[$index]), $unsetBlockLetters);
		while ($blockLetterIndex !== false)
		{
			$absentBlocks[$blockLetterIndex] = $absent ? "1" : "0";
			unset($unsetBlockLetters[$blockLetterIndex]);

			$blockLetterIndex = array_search(strtolower($parts[++$index]), $unsetBlockLetters);
			if ($index >= $length)
				return;
		}
	}
	/**
	 * Send a 1 day announcement if the $announcement is not an empty string
	 *
	 * @param string $announcement Either announcement info OR empty string, in which case we do not send the announcement
	 * @param mysqli $mysqli
	 */
	function trySendAnnouncement($announcement, $mysqli)
	{
		if ($announcement == "" || ctype_space($announcement))  //if announcement is empty or just contains spaces
			return;

		//announcement ends by midnight
		$date = date("Ymd");
		sendNotificationAnnouncement($announcement, "$date +1 day", $mysqli);
	}