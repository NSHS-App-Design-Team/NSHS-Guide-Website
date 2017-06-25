<?php
	/**
	 * Delete special schedule from MySQL database
	 *
	 * @param string $date Date of special schedule as string, in format YYYY-mm-dd
	 * @param mysqli $mysqli
	 */
	function deleteSpecialSchedule($date, $mysqli)
	{
		$stmt = $mysqli->prepare("DELETE FROM material_special_schedule WHERE date LIKE ?");
		if (!$stmt)
			return;
		$stmt->bind_param('s', $date);
		$stmt->execute();
		$stmt->close();
	}

	/**
	 * Store special schedule in the MySQL database
	 *
	 * @param string $date Date of special schedule as string, in format YYYY-mm-dd
	 * @param string $schedule Blocks of special schedule as string
	 * @param mysqli $mysqli
	 */
	function setSpecialSchedule($date, $schedule, $mysqli)
	{
		//delete old special schedule
		deleteSpecialSchedule($date, $mysqli);

		//insert new special schedule
		$stmt = $mysqli->prepare("INSERT INTO material_special_schedule (date, schedule) VALUES (?,?)");
		if (!$stmt)
			return;
		$stmt->bind_param('ss', $date, $schedule);
		$stmt->execute();
		$stmt->close();
	}

	/**
	 * Returns an array of blocks for a date, or null if there is no special schedule on that day.
	 *
	 * Each block is an array containing the following key/values
	 * array(
		'letter' => 'G',
		'num' => 3,
		'startTime' => '10:50',
		'endTime' => '12:35',
		'isLunch' => false,
		'customLunchTimes' => null,
		'customBlockName' => ''
		);
	 *
	 * CustomLunchTimes can contain another array with the following key/values
	 * array(
		'1stLunchEnd' => '11:20',
		'2ndLunchStart' => '11:25',
		'2rdLunchEnd' => '11:55',
		'3rdLunchStart' => '12:05'
		);
	 *
	 * @param string $date Date of special schedule as string, in format YYYY-mm-dd
	 * @param mysqli $mysqli
	 * @return array|null Array of blocks, or null if this day is not a special schedule
	 */
	function getSpecialSchedule($date, $mysqli)
	{
		$stmt = $mysqli->prepare("SELECT schedule FROM material_special_schedule WHERE date LIKE ? LIMIT 1");
		if (!$stmt)
			return null;
		$stmt->bind_param('s', $date);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows != 1)
			return null;
		$stmt->bind_result($schedule);
		$stmt->fetch();
		$stmt->close();

		$blocks = array();
		$scheduleSplit = explode("\n", $schedule);
		foreach ($scheduleSplit as $blockString)
			$blocks[] = blockFromString($blockString);
		return $blocks;
	}

	/**
	 * Sets a range of dates as no school days (schedule = "")
	 * @param string $startDate Start date as string, in format YYYY-mm-dd
	 * @param string $endDate End date as string, in format YYYY-mm-dd
	 * @param mysqli $mysqli
	 */
	function setNoSchool($startDate, $endDate, $mysqli)
	{
		$interval = new DateInterval('P1D');    //interval of 1 day
		$startDate = DateTime::createFromFormat("Y-m-d", $startDate);
		$endDate = DateTime::createFromFormat("Y-m-d", $endDate)->add($interval);   //add 1 day to end since DatePeriod is exclusive
		$dateRange = new DatePeriod($startDate, $interval, $endDate);
		foreach ($dateRange as $date)
			setSpecialSchedule($date->format("Y-m-d"), "", $mysqli); //schedule = ""
	}

	/**
	 * Transforms a block as string like so:
	 * letter|num|startTime|endTime|isLunch|customName       (typical block)
	 * letter|num|startTime|endTime|isLunch|customName|1stLunchEnd|2ndLunchStart|2ndLunchEnd|3rdLunchStart
	 * Combined with \n for each block into 1 big string
	 *
	 *
	 * Into a block as an array containing the following key/values
	 * Each block is an array containing the following key/values
	 * array(
		'letter' => 'G',
		'num' => 3,
		'startTime' => '10:50',
		'endTime' => '12:35',
		'isLunch' => false,
		'customLunchTimes' => null,
		'customBlockName' => ''
		);
	 *
	 * CustomLunchTimes can contain another array with the following key/values
	 * array(
		'firstLunchEnd' => '11:20',
		'secondLunchStart' => '11:25',
		'secondLunchEnd' => '11:55',
		'thirdLunchStart' => '12:05'
		);
	 *
	 * @param string $string
	 * @return array A single block as an array containing key/values such as "letter"
	 */
	function blockFromString($string)
	{
		$parts = explode("|", $string);
		$blockLetter = $parts[0];
		$blockNum = intval($parts[1]);
		$startTime = $parts[2];
		$endTime = $parts[3];
		$isLunch = $parts[4] === "true";
		$customBlockName = $parts[5];

		$customLunchTimes = null;
		if ($isLunch && count($parts) == 10)
		{
			$customLunchTimes = array(
				"firstLunchEnd" => $parts[6],
				"secondLunchStart" => $parts[7],
				"secondLunchEnd" => $parts[8],
				"thirdLunchStart" => $parts[9]
			);
		}

		return array(
			"letter" => $blockLetter,
			"num" => $blockNum,
			"startTime" => $startTime,
			"endTime" => $endTime,
			"isLunch" => $isLunch,
			"customBlockName" => $customBlockName,
			"customLunchTimes" => $customLunchTimes
		);
	}
	
	/**
	 * Returns a string regarding the schedule type of the particular date
	 * 
	 * @param string $date Date as string, in format YYYY-mm-dd
	 * @param mysqli $mysqli
	 * @return string "Special Schedule", "No School", or "Normal Schedule"
	 */
	function getScheduleTypeOfDate($date, $mysqli)
	{
		$stmt = $mysqli->prepare("SELECT schedule FROM material_special_schedule WHERE date LIKE ? LIMIT 1");
		if (!$stmt)
			return "Normal Schedule";
		$stmt->bind_param('s', $date);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows != 1)
			return "Normal Schedule";
		$stmt->bind_result($schedule);
		$stmt->fetch();
		$stmt->close();


		if (empty($schedule))
			return "No School";
		else
			return "Special Schedule";
	}
	
	/**
	 * Gets dates of all stored special schedules
	 * @param mysqli $mysqli
	 * @return array Map: Key = date in yyyy-MM-dd, value = schedule
	 */
	function getSpecialSchedulesForDates($mysqli)
	{
		$stmt = $mysqli->prepare("SELECT date, schedule FROM material_special_schedule");
		if (!$stmt)
			return null;
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($date, $schedule);

		$schedulesForDates = array();
		while ($stmt->fetch())
			$schedulesForDates[$date] = $schedule;
		$stmt->close();

		return $schedulesForDates;
	}