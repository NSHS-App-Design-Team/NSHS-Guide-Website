<?php
	/**
	 * Sends notifications that the absence list has been updated. Calculates time for notification
	 * to live based on how much time is between now & the end of the day.
	 *
	 * @param array $absentTeachers Array of absent teachers in format [0] = "Chu, David|011000100|info", [1] = "Lin, Eric|011000100|", etc
	 * @param mysqli $mysqli
	 */
	function sendNotificationAbsentTeachers($absentTeachers, $mysqli)
	{
		$absentTeachers["title"] = "Absent Teachers";
		$absentTeachers["date"] = date('n|j');

		$secondsToLive = strtotime("tomorrow") - time();
		sendNotification($absentTeachers, $mysqli, $secondsToLive);
	}

	/**
	 * @param string $date Date of special schedule as string, in format YYYY-mm-dd
	 * @param mysqli $mysqli
	 */
	function sendNotificationRevertSchedule($date, $mysqli)
	{
		$data = array(
			"title" => "Special Schedule",
			"datesToRemove" => $date,
			"datesToAdd" => ""
		);
		$secondsToLive = strtotime($date) + 86400;  //give an extra day for the notification to deliver
		sendNotification($data, $mysqli, $secondsToLive);
	}
	
	/**
	 * @param string $startDate Start date as string, in format YYYY-mm-dd
	 * @param string $endDate End date as string, in format YYYY-mm-dd
	 * @param mysqli $mysqli
	 */
	function sendNotificationNoSchool($startDate, $endDate, $mysqli)
	{
		$interval = new DateInterval('P1D');    //interval of 1 day
		$startDate = DateTime::createFromFormat("Y-m-d", $startDate);
		$endDate = DateTime::createFromFormat("Y-m-d", $endDate)->add($interval);   //add 1 day to end since DatePeriod is exclusive
		$dateRange = new DatePeriod($startDate, $interval, $endDate);
		foreach ($dateRange as $date)
			sendNotificationSpecialSchedule($date->format("Y-m-d"), "", $mysqli); //schedule = ""
	}
	
	/**
	 * @param string $date Date of special schedule as string, in format YYYY-mm-dd
	 * @param string $schedule Blocks of special schedule as string
	 * @param mysqli $mysqli
	 */
	function sendNotificationSpecialSchedule($date, $schedule, $mysqli)
	{
		$data = array(
			"title" => "Special Schedule",
			"datesToRemove" => "",
			"datesToAdd" => $date,
			$date => $schedule
		);
		$secondsToLive = strtotime($date) + 86400;  //give an extra day for the notification to deliver
		sendNotification($data, $mysqli, $secondsToLive);
	}
	
	/**
	 * @param string $info The content of the announcement to be sent
	 * @param int $endEpoch
	 * @param mysqli $mysqli
	 */
	function sendNotificationAnnouncement($info, $endEpoch, $mysqli)
	{
		$data = array(
			'title' => 'Announcement',
			'endEpoch' => "$endEpoch",
			'info' => $info
		);
		$secondsToLive = $endEpoch - time();
		sendNotification($data, $mysqli, $secondsToLive);
	}

	/**
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $regID
	 * @param string $platform Either "Android" or "iOS"
	 * @param bool $approved Whether the teacher was added to the faculty list (approved) or not
	 */
	function sendNotificationTeacherRequest($firstName, $lastName, $regID, $platform, $approved)
	{
		$regIDs = array($regID);
		$data = array(
			"title" => "Request Teacher",
			"name" => "$lastName, $firstName",
			"approved" => $approved
		);

		if ($platform == "Android")
			sendAndroidNotification($data, $regIDs, 86400); //TODO change 86400 (secs in day) to something meaningful
		else
			sendiOSDistributionNotification("Teacher request approved: $approved", $regIDs, 86400); //TODO change message to data once data works
	}

	/**
	 * @param array $teachersList Array of all the teachers, each a map with properties "firstName" and "lastName"
	 * @param mysqli $mysqli
	 */
	function sendNotificationTeachersList($teachersList, $mysqli)
	{
		$data = array("title" => "Teachers List");
		foreach ($teachersList as $teacher)
			$data[] = $teacher["lastName"] . ", " . $teacher["firstName"];
		sendNotification($data, $mysqli, 86400);
	}

	/**
	 * @param array $data Should contain (at least) "title" as key
	 * @param mysqli $mysqli
	 * @param int $secondsToLive
	 */
	function sendNotification($data, $mysqli, $secondsToLive)
	{
		$androidRegIDs = getRegIDsOfPlatform("Android", $mysqli);
		//$iOSRegIDs = getRegIDsOfPlatform("iOS", $mysqli);
		sendAndroidNotification($data, $androidRegIDs, $secondsToLive);
		//sendiOSDistributionNotification($data, $iOSRegIDs, $secondsToLive); //TODO uncomment once data works
	}

	/**
	 * @param array $data Should contain (at least) "title" as key
	 * @param array $regIDs
	 * @param int $secondsToLive
	 */
	function sendAndroidNotification($data, $regIDs, $secondsToLive)
	{
		//GCM
		$apiKey = 'AIzaSyCyZMO6qczciBYhdF932FZ_06e5bYjV_aw';
		$url = 'https://gcm-http.googleapis.com/gcm/send';
		//the JSON stuff
		$post = array('registration_ids' => $regIDs, 'data' => $data, 'content_available' => true, 'collapse_key' => "meh", 'time_to_live' => $secondsToLive);
		$headers = array('Authorization: key=' . $apiKey, 'Content-Type: application/json');
		$curlHandle = curl_init();
		//send URL to GCM
		curl_setopt($curlHandle, CURLOPT_URL, $url);
		//send request to post
		curl_setopt($curlHandle, CURLOPT_POST, true);
		//send headers
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
		//get response
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		//set post data type as JSON
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, json_encode($post));
		//SSL stuff
		curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
		//now send the push
		curl_exec($curlHandle);
		curl_close($curlHandle);
	}

	function sendiOSDevelopmentNotification($message, $regIDs, $secondsToLive)
	{
		sendiOSNotification($message, $regIDs, $secondsToLive, 'ck_development.pem', 'ssl://gateway.sandbox.push.apple.com:2195');
	}
	function sendiOSDistributionNotification($message, $regIDs, $secondsToLive)
	{
		sendiOSNotification($message, $regIDs, $secondsToLive, 'ck_distribution.pem', 'ssl://gateway.push.apple.com:2195');
	}

	/**
	 * @param string $message
	 * @param array $regIDs
	 * @param int $secondsToLive
	 * @param string $certificateName Either the development or distribution certificate
	 * @param string $serverUrl Either official or sandbox URL
	 */
	function sendiOSNotification($message, $regIDs, $secondsToLive, $certificateName, $serverUrl)
	{
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', $certificateName);
		stream_context_set_option($ctx, 'ssl', 'passphrase', 'he1lB1gBr0ther');

		// Open a connection to the APNS server
		$fp = stream_socket_client($serverUrl, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);

		// Create the payload body
		//$iOSBody['data'] = $data;
		$iOSBody['aps'] = array(
			'alert' => $message,
			'sound' => 'default'    //TODO change back to silent notification with info
		);
		// Encode the payload as JSON
		$payload = json_encode($iOSBody);
		$expirationDate = time() + $secondsToLive;

		//Deliver message & content
		foreach ($regIDs as $deviceToken)
		{
			// Build the binary notification
			$msg =
				//stuff necessary for expiration date
				chr(1) //command
				. chr(1) . chr(1) . chr(1) . chr(1) //id
				. pack('N', $expirationDate)
				//actual package
				. chr(0) . chr(32) . pack('H*', $deviceToken) . chr(0) . chr(strlen($payload)) . $payload;
			// Send it to the server

			fwrite($fp, $msg, strlen($msg));
		}
		// Close the connection to the server
		fclose($fp);
	}

	/**
	 * @param string $regID
	 * @param string $platform Either "Android" or "iOS"
	 * @param mysqli $mysqli
	 */
	function setRegIDOfPlatform($regID, $platform, $mysqli)
	{
		$platformDataTable = $platform == "Android" ? "reg_ids" : "ios_reg_ids";
		$stmt = $mysqli->prepare("SELECT 1 FROM $platformDataTable WHERE reg_id = ? LIMIT 1");
		if (!$stmt)
			return;
		$stmt->bind_param('s', $regID);
		$stmt->execute();
		$stmt->store_result();
		if($stmt->num_rows != 0)    //attempt to store same regID more than once
			return;
		$stmt->close();
		$stmt = $mysqli->prepare("INSERT INTO $platformDataTable (reg_id) VALUES (?)");
		if (!$stmt)
			return;
		$stmt->bind_param('s', $regID);
		$stmt->execute();
		$stmt->close();
	}

	/**
	 * @param string $platform Either "Android" or "iOS"
	 * @param mysqli $mysqli
	 * @return array Array of regIDs for the chosen platform. Can be empty
	 */
	function getRegIDsOfPlatform($platform, $mysqli)
	{
		$platformDataTable = $platform == "Android" ? "reg_ids" : "ios_reg_ids";
		$stmt = $mysqli->prepare("SELECT reg_id FROM $platformDataTable");
		if (!$stmt)
			return array();
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($regID);
		$regIDs = array();
		while ($stmt->fetch())
			$regIDs[] = $regID;
		$stmt->close();
		return $regIDs;
	}