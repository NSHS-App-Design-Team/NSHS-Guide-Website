<?php

	/**
	 * Formats the absences email from Ms. Connolly into an array of absent teachers. Does not check if the teachers
	 * exist on the teacher list or which blocks the teacher is absent for. Also attempts to extract an announcement
	 * from remaining info
	 * 
	 * @param string $email Email from Ms. Connolly
	 * @return array 0 = Array of absent teachers; each is a map that has keys "lastName", "firstName", and "info"
	 *               1 = Announcement if any was received, empty otherwise
	 */
	function format($email)
	{
		$lines = separateByNewline($email);
		$lines = fixinfoOverflow($lines);
		$lines = removeLeadingCarats($lines);
		$lines = removeSignature($lines);
		$lines = removeEmptyLines($lines);
		$lines = array_values($lines);  //reorient the indices so they start at 0 again
		$linesAndAnnouncement = formatTeachersOutsideDataTable($lines);
		$lines = $linesAndAnnouncement[0];
		$announcement = $linesAndAnnouncement[1];
		return array(teacherMapFromLines($lines), $announcement);
	}

	/**
	 * Accepts email from Ms. Connolly that has any part that isn't the absence list edited out, returns the teachers
	 * separated by newline.
	 * Email should look like this: "lastName\tfirstName\tinfo\nlastName\tfirstName\n..."
	 *
	 * @param string $email Email from Ms. Connolly
	 * @return array Lines
	 */
	function separateByNewline($email)
	{
		return explode("\n", $email);
	}

	/**
	 * Address case where info flows into next line (which is also separated by \n but contains no \t
	 * for example: "Marder Robyn	field trip, G block to 6167, C block cancelled, B&E blocks as
	 *              usual"
	 *
	 * @param array $lines
	 * @see separateByNewline()
	 * @return array Lines without info overflows
	 */
	function fixinfoOverflow($lines)
	{
		$fixedLines = array();
		$length = count($lines);
		for ($i = 0; $i < $length; $i++)
		{
			$line = $lines[$i];
			$fixedLines[] = $line;

			if ($i == 0)
				continue;
			//if this line contains \t, then it should be an actual teacher in the data table
			if (strpos($line, "\t") !== false)
				continue;
			//the last line must contain exactly 2 tabs (so we're sure this is overflow of info, not something else)
			if (substr_count($lines[$i - 1], "\t") != 2)
				continue;

			$lastElementIndex = count($fixedLines) - 1;
			$fixedLines[$lastElementIndex - 1] .= ' ' . $line;    //append the last element to the previous line with a space
			array_pop($fixedLines);  //remove the last element
		}
		return $fixedLines;
	}

	/**
	 * Removes leading carats from lines
	 *
	 * @param array $lines
	 * @see separateByNewline()
	 * @return array Lines without ">" at the start
	 */
	function removeLeadingCarats($lines)
	{
		return array_map("removeLeadingCarat", $lines);
	}
	function removeLeadingCarat($string)
	{
		return ltrim($string, ">");
	}

	/**
	 * Removes Ms. Connolly's signature from the email. Note that this should've been removed ALREADY when splitting
	 * the email into the parts we wish to parse and the parts we do not; however, as Ms. Connolly may have "updated" the
	 * absence list, she may have more than 1 signature in the email.
	 *
	 * @param array $lines
	 * @see separateByNewline()
	 * @return array Lines without Ms. Connolly's signature at the end
	 */
	function removeSignature($lines)
	{
		return array_filter($lines, "isntPartOfSignature");
	}
	function isntPartOfSignature($string)
	{
		$partsOfSignature = array("Tracy A. Connolly", "Newton South High School", "Executive Secretary", "617-559-6502", "tracy_connolly@newton.k12.ma.us", "Tracy A. Connolly writes:");
		foreach ($partsOfSignature as $part)
			if (strpos($string, $part) !== false)
				return false;
		return true;
	}

	/**
	 * Removes any empty lines from array.
	 *
	 * @param array $lines
	 * @see separateByNewline()
	 * @return array Lines without empty elements
	 */
	function removeEmptyLines($lines)
	{
		return array_filter($lines, "isntEmptyString");
	}
	function isntEmptyString($string)
	{
		return !ctype_space($string) && !empty($string);
	}

	/**
	 * Finds any teachers outside the data table and parses them accordingly. Interprets any non-name words outside the
	 * data table as info for an announcement
	 *
	 * @param array $lines
	 * @see separateByNewline()
	 * @return array 0 = Lines without any teachers outside the data table, 1 = announcement (empty if none exist)
	 */
	function formatTeachersOutsideDataTable($lines)
	{
		$announcement = "";
		$length = count($lines);
		for ($i = 0; $i < $length; $i++)
		{
			$line = $lines[$i];
			//if this line contains 2 tabs (\t), then it should be an actual teacher in the data table
			$numTabs = substr_count($line, "\t");
			if ($numTabs >= 2)
				continue;

			$line = trim($line);

			$firstlastName = firstlastNameOutsideTable($line);
			if ($firstlastName == null)   //the first/last name matched to nothing (only spaces exist), remaining info must be an announcement
			{
				$start = $i;
				$announcementArray = array_splice($lines, $start);  //split the lines into lines & announcement parts
				$announcement = implode("\n", $announcementArray);
				break;
			}

			$lines[$i] = $firstlastName;
			$info = infoOutsideTable($line);
			if ($info != null)
				$lines[$i] .= "\t" . $info;
		}
		return array($lines, trim($announcement));
	}

	/**
	 * Finds the first and last name of a teacher outside the data table and returns it in correct format.
	 *
	 * @param string $line Line outside the data table
	 * @return string|null Teacher name in "lastName \t firstName" format OR null if nothing matches
	 */
	function firstlastNameOutsideTable($line)
	{
		$matches = array();

		//test lastName, firstName pattern first (regex wants any letter or apostrophe, for names like O'Reilly)
		preg_match('/^(?<lastName>[A-Z][a-zA-Z\']+), (?<firstName>[A-Z][a-zA-Z\']+)/', $line, $matches);
		//test firstName lastName pattern next
		if (empty($matches))
			preg_match('/^(?<firstName>[A-Z][a-zA-Z\']+) (?<lastName>[A-Z][a-zA-Z\']+)/', $line, $matches);
		if (empty($matches))
			return null;

		return $matches['lastName'] . "\t" . $matches['firstName'];
	}

	/**
	 * Finds the info outside the table (whatever follows "-"), or null if no info exists
	 *
	 * @param string $line Line outside the data table
	 * @return string info
	 */
	function infoOutsideTable($line)
	{
		//split string from first dash "-"
		$line = explode("-", $line, 2);
		if (count($line) < 2)
			return null;
		return trim($line[1]);
	}

	/**
	 * Turns the lines into an array of absent teachers; each is a map that has keys "lastName", "firstName", and "info".
	 * Does not check whether the lines given are actually teachers; that must be formatted correctly beforehand.
	 *
	 * @param array $lines
	 * @see separateByNewline()
	 * @return array Array of absent teachers; each is a map that has keys "lastName", "firstName", and "info"
	 */
	function teacherMapFromLines($lines)
	{
		return array_map("teacherMapFromLine", $lines);
	}
	function teacherMapFromLine($string)
	{
		$parts = explode("\t", $string);
		$parts = array_map("trim", $parts); //remove leading/trailing spaces from last names & first names
		$teacher = array(
			"lastName" => $parts[0],
			"firstName" => $parts[1],
		);
		if (count($parts) == 3 && isntEmptyString($parts[2]))
			$teacher["info"] = $parts[2];
		return $teacher;
	}