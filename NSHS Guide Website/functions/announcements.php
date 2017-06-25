<?php
	/**
	 * Stores announcement in database whilst deleting any old ones
	 * 
	 * @param string $info
	 * @param int $endEpoch
	 * @param mysqli $mysqli
	 */
	function setAnnouncement($info, $endEpoch, $mysqli)
	{
		//delete all old announcements
		$stmt = $mysqli->prepare("TRUNCATE TABLE material_announcements");
		if (!$stmt)
			return;
		$stmt->execute();
		$stmt->close();

		$stmt = $mysqli->prepare("INSERT INTO material_announcements (info, endEpoch) VALUES (?,?)");
		if (!$stmt)
			return;
		$stmt->bind_param("si", $info, $endEpoch);
		$stmt->execute();
		$stmt->close();
	}
	/**
	 * Returns announcement as a map if it exists & has not already ended
	 * 
	 * @param mysqli $mysqli
	 * @return array Array with keys "info" => string and "endEpoch" => int
	 */
	function getAnnouncement($mysqli)
	{
		$stmt = $mysqli->prepare("SELECT info, endEpoch FROM material_announcements LIMIT 1");
		if (!$stmt)
			return array();
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows != 1)
			return array();
		$stmt->bind_result($info, $endEpoch);
		$stmt->fetch();
		$stmt->close();

		//make sure endEpoch is before start of today
		if ($endEpoch < mktime(0, 0, 0, date('m'), date('d')))
			return array();
		return array("info" => $info, "endEpoch" => $endEpoch);
	}