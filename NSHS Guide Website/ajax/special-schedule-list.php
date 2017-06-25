<?php
	include_once '../functions/database.php';
	include_once '../functions/special-schedule.php';

	$mysqli = getMySQL();

	/**
	 * Received data includes:
	 * ["dates"] = String of dates of special schedules from phone. Ex: "2016-06-01|2016-06-02"
	 * ["2016-06-01"] = String of special schedule for date. Keys = dates specified in string above
	 * ["2016-06-02"] = See above
	 */
	$datesAsString = $_REQUEST["dates"];

	$phoneDates = explode("|", $datesAsString);
	$schedulesForDates = getSpecialSchedulesForDates($mysqli);
	$length = count($schedulesForDates);

	/**
	 * Decrement to remove values from the arrays
	 * Remaining dates in $phoneDates = extraneous/incorrect special schedules stored on phone. Should be deleted
	 * Remaining dates in $schedulesForDates = dates of special schedules the phone does not have. Should be added
	 */
	foreach ($schedulesForDates as $storedDate => $storedSchedule)
	{
		$indexInDates = array_search($storedDate, $phoneDates);

		//Do dates overlap?
		if ($indexInDates === false)
			continue;
		//Dates overlap. Find out if schedules are identical
		$phoneSchedule = $_REQUEST[$storedDate];
		if ($phoneSchedule != $storedSchedule)
			continue;

		//Schedules are identical. Don't need to notify user. Remove from both arrays.
		unset($phoneDates[$indexInDates]);
		unset($schedulesForDates[$storedDate]);
	}

	//convert everything back to string & prepare for printing
	$remainingPhoneDates = implode("|", $phoneDates);
	$remainingStoredDates = implode("|", array_keys($schedulesForDates));
	$output = array(
		"title" => "Special Schedule",
		"datesToRemove" => $remainingPhoneDates,
		"datesToAdd" => $remainingStoredDates
	);
	foreach ($schedulesForDates as $storedDate => $storedSchedule)
		$output[$storedDate] = $storedSchedule;

	echo json_encode($output);