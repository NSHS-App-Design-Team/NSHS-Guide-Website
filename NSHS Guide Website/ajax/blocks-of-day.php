<?php
	include_once '../functions/database.php';
	include_once '../functions/special-schedule.php';

	$timeStamp = $_REQUEST['timeStamp'];
	if (!$timeStamp)
		$timeStamp = time();

	//check special schedule
	$date = date('Y-m-d', $timeStamp);
	$mysqli = getMySQL();
	$scheduleType = getScheduleTypeOfDate($date, $mysqli);
	switch ($scheduleType)
	{
		case "No School":
			return;
		case "Special Schedule":
			echo json_encode(getSpecialSchedule($date, $mysqli));
			return;
	}

	//use normal day of week
	$dayOfWeek = date('w', $timeStamp);
	switch ($dayOfWeek)
	{
		case 1:
			$blocksOfDay = array(
				array(
					'letter' => 'A',
					'num' => 1,
					'startTime' => '07:40',
					'endTime' => '08:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'B',
					'num' => 1,
					'startTime' => '08:40',
					'endTime' => '09:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'HR',
					'num' => 1,
					'startTime' => '09:40',
					'endTime' => '09:45',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'C',
					'num' => 1,
					'startTime' => '09:50',
					'endTime' => '10:45',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'D',
					'num' => 1,
					'startTime' => '10:50',
					'endTime' => '12:35',
					'isLunch' => true,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'E',
					'num' => 1,
					'startTime' => '12:40',
					'endTime' => '13:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'F',
					'num' => 1,
					'startTime' => '13:40',
					'endTime' => '14:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'J',
					'num' => 1,
					'startTime' => '14:40',
					'endTime' => '15:20',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				));
			break;
		case 2:
			$blocksOfDay = array(
				array(
					'letter' => 'G',
					'num' => 1,
					'startTime' => '07:40',
					'endTime' => '08:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'F',
					'num' => 2,
					'startTime' => '08:40',
					'endTime' => '09:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'HR',
					'num' => 2,
					'startTime' => '09:40',
					'endTime' => '10:05',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'C',
					'num' => 2,
					'startTime' => '10:10',
					'endTime' => '11:05',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'E',
					'num' => 2,
					'startTime' => '11:10',
					'endTime' => '12:55',
					'isLunch' => true,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'D',
					'num' => 2,
					'startTime' => '13:00',
					'endTime' => '13:55',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				));
			break;
		case 3:
			$blocksOfDay = array(
				array(
					'letter' => 'A',
					'num' => 2,
					'startTime' => '07:40',
					'endTime' => '08:55',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'B',
					'num' => 2,
					'startTime' => '09:00',
					'endTime' => '09:55',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'G',
					'num' => 2,
					'startTime' => '10:00',
					'endTime' => '10:55',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'F',
					'num' => 3,
					'startTime' => '11:00',
					'endTime' => '12:45',
					'isLunch' => true,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'D',
					'num' => 3,
					'startTime' => '12:50',
					'endTime' => '13:45',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'E',
					'num' => 3,
					'startTime' => '13:50',
					'endTime' => '14:45',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'J',
					'num' => 2,
					'startTime' => '14:50',
					'endTime' => '15:20',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				));
			break;
		case 4:
			$blocksOfDay = array(
				array(
					'letter' => 'A',
					'num' => 3,
					'startTime' => '07:40',
					'endTime' => '08:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'B',
					'num' => 3,
					'startTime' => '08:40',
					'endTime' => '09:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'HR',
					'num' => 3,
					'startTime' => '09:40',
					'endTime' => '09:45',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'F',
					'num' => 4,
					'startTime' => '09:50',
					'endTime' => '10:45',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'G',
					'num' => 3,
					'startTime' => '10:50',
					'endTime' => '12:35',
					'isLunch' => true,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'E',
					'num' => 4,
					'startTime' => '12:40',
					'endTime' => '13:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'C',
					'num' => 3,
					'startTime' => '13:40',
					'endTime' => '14:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'J',
					'num' => 3,
					'startTime' => '14:40',
					'endTime' => '15:20',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				));
			break;
		case 5:
			$blocksOfDay = array(
				array(
					'letter' => 'A',
					'num' => 4,
					'startTime' => '07:40',
					'endTime' => '08:35',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'B',
					'num' => 4,
					'startTime' => '08:40',
					'endTime' => '09:55',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'HR',
					'num' => 4,
					'startTime' => '10:00',
					'endTime' => '10:05',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'G',
					'num' => 4,
					'startTime' => '10:10',
					'endTime' => '11:05',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'C',
					'num' => 4,
					'startTime' => '11:10',
					'endTime' => '12:55',
					'isLunch' => true,
					'customLunchTimes' => null,
					'customBlockName' => ''
				),
				array(
					'letter' => 'D',
					'num' => 4,
					'startTime' => '13:00',
					'endTime' => '13:55',
					'isLunch' => false,
					'customLunchTimes' => null,
					'customBlockName' => ''
				));
			break;
		default:
			$blocksOfDay = null;
	}

	echo json_encode($blocksOfDay);