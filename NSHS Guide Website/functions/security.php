<?php
	/*NOTE: Any php file that wants to access cookies in here (like login_string) must be physically located in
			the same directory the cookie was created in.*/

	//copy paste from eric
	function startSecureSession()
	{
		ini_set('session.gc_maxlifetime', 10800); // 3 hour life time. Do not set this higher
		// Forces sessions to only use cookies.
		if (ini_set('session.use_only_cookies', 1) == false)
		{
			$_COOKIE['error'] = "Could not initiate a safe session (ini_set)";
			setcookie('error', $_COOKIE['error'], time() + 2);
			return;
		}
		session_name('session_id');
		session_start();            // Start the PHP session
		session_regenerate_id(true);    // regenerated the session, delete the old one.
	}

	/**
	 * Attempts to sign up using email, password, and a reference to the database ($mysqli)
	 *
	 * @param string $email
	 * @param string $password
	 * @param mysqli $mysqli
	 * @return bool Whether sign up was successful (unsuccessful sign-ups are due to email overlaps)
	 */
	function signUpWithEmailPassword($email, $password, $mysqli)
	{
		$stmt = $mysqli->prepare("SELECT * FROM web_logins WHERE email = ? LIMIT 1");
		if (!$stmt)
			return false;
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$stmt->store_result();
		$stmt->fetch();
		$numUsers = $stmt->num_rows;    //check if any users already exist with said email
		$stmt->close();
		if ($numUsers != 0)
			return false;

		//generate a salt, hash the password with the salt appended (for further safety)
		$salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
		$password = hash('sha512', $password . $salt);

		$stmt = $mysqli->prepare("INSERT INTO web_logins (email, password, salt) VALUES (?, ?, ?)");
		if (!$stmt)
			return false;
		$stmt->bind_param('sss', $email, $password, $salt);
		$stmt->execute();
		$stmt->close();
		return true;
	}
	/**
	 * @param string $currentPassword
	 * @param string $newPassword
	 * @param mysqli $mysqli
	 * @return bool
	 */
	function changeCurrentPasswordToNew($currentPassword, $newPassword, $mysqli)
	{
		$userId = getUserId($mysqli);
		if ($userId == null)
			return false;

		$stmt = $mysqli->prepare("SELECT password, salt FROM web_logins WHERE id = ? LIMIT 1");
		if (!$stmt)
			return false;
		$stmt->bind_param('i', $userId);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($passwordToCheck, $salt);
		$stmt->fetch();
		$stmt->close();

		//make sure currentPassword matches with the saved one
		$currentPassword = hash('sha512', $currentPassword . $salt);
		if ($passwordToCheck != $currentPassword)
			return false;

		$newPassword = hash('sha512', $newPassword . $salt);
		$stmt = $mysqli->prepare("UPDATE web_logins SET password = ? WHERE id = ?");
		$stmt->bind_param('si', $newPassword, $userId);
		$stmt->execute();
		$stmt->close();
		return true;
	}

	/**
	 * Attempts to login using email, password, and a reference to the database ($mysqli)
	 *
	 * @param string $email
	 * @param string $password
	 * @param mysqli $mysqli
	 * @return bool Whether login was successful
	 */
	function loginWithEmailPassword($email, $password, $mysqli)
	{
		$stmt = $mysqli->prepare("SELECT id, password, salt FROM web_logins WHERE email = ? LIMIT 1");
		if (!$stmt)
			return false;

		//check who has this email in our userbase
		$stmt->bind_param('s', $email);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($userId, $passwordToCheck, $salt);
		$stmt->fetch();
		$stmt->close();

		if (isBruteForceLogin($userId, $mysqli))
			return false;

		$password = hash('sha512', $password . $salt);
		if ($passwordToCheck != $password)
		{
			recordLoginAttempt($userId, $mysqli);
			return false;
		}

		populateSessionWithUserId($userId, $mysqli);

		// Get the user-agent string of the user.
		$userBrowser = $_SERVER['HTTP_USER_AGENT'];
		saveLoginStringAndId(hash('sha512', $password . $userBrowser), $userId, $mysqli);
		return true;
	}

	/**
	 * @param mysqli $mysqli
	 */
	function logout($mysqli)
	{
		deleteLoginString($mysqli);
		$_SESSION = array();    //empty the session

		$params = session_get_cookie_params();
		// Delete the cookie
		setcookie(session_name(), '', time() - 42000,
			$params["path"],
			$params["domain"],
			$params["secure"],
			$params["httponly"]);

		session_destroy();
		startSecureSession();
	}

	/**
	 * Check if this user has attempted to log into his/her account many times successively, indicating a brute force
	 * attack on a particular user's account
	 *
	 * @param number $userId
	 * @param mysqli $mysqli
	 * @return bool
	 */
	function isBruteForceLogin($userId, $mysqli)
	{
		// All login attempts are counted from the past 2 hours.
		$startOfInterval = time() - (2 * 60 * 60);
		$stmt = $mysqli->prepare("SELECT time FROM web_loginattempts WHERE userid = ? AND time > '$startOfInterval'");
		if (!$stmt)
			return true;

		$stmt->bind_param('i', $userId);
		$stmt->execute();
		$stmt->store_result();
		$numFailedAttempts = $stmt->num_rows;
		$stmt->close();

		return $numFailedAttempts > 10;
	}
	/**
	 * Record that the user with this ID has attempted to log in unsuccessfully
	 *
	 * @param number $userId
	 * @param mysqli $mysqli
	 */
	function recordLoginAttempt($userId, $mysqli)
	{
		$now = time();
		$mysqli->query("INSERT INTO web_loginattempts(userid, time) VALUES ('$userId', '$now')");
	}
	/**
	 * Returns whether or not the user on this computer is logged in based on the cookie login_string.
	 * ALSO populates $_SESSION variables so things like email can be accessed quickly later
	 *
	 * @param mysqli $mysqli
	 * @return bool
	 */
	function tryLoginWithCookies($mysqli)
	{
		$userId = getUserId($mysqli);
		if ($userId == null)
			return false;

		populateSessionWithUserId($userId, $mysqli);
		return true;
	}
	/**
	 * Gets the userId of the current logged in user using the cookie login_string
	 *
	 * @param mysqli $mysqli
	 * @return number|null The userId if this user is indeed logged in. Null otherwise
	 */
	function getUserId($mysqli)
	{
		if (!isset($_COOKIE['login_string']))
			return null;
		$loginString = $_COOKIE['login_string'];

		//check if the hashed password actually exists in our list of hashed passwords
		$stmt = $mysqli->prepare("SELECT userid FROM web_loginstrings WHERE loginstring = ? LIMIT 1");
		if (!$stmt)
			return null;
		$stmt->bind_param('s', $loginString);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows != 1)
			return null;
		$stmt->bind_result($userId);
		$stmt->fetch();
		$stmt->close();
		return $userId;
	}
	/**
	 * Saves loginString & its respective ID to mysqli database AND creates the cookie, so we may verify the next time
	 * the browser is opened whether the user is logged in (without storing sensitive info)
	 *
	 * @param string $loginString Unique string generate for user based on password & current browser
	 * @param number $userId Id (number) of user
	 * @param mysqli $mysqli
	 */
	function saveLoginStringAndId($loginString, $userId, $mysqli)
	{
		setcookie('login_string', $loginString, strtotime('+1 year'));
		$mysqli->query("INSERT INTO web_loginstrings(loginstring, userid) VALUES ('$loginString', '$userId')");
	}
	/**
	 * Delete the loginString, both local (cookie) and database references
	 *
	 * @param mysqli $mysqli
	 */
	function deleteLoginString($mysqli)
	{
		if (!isset($_COOKIE['login_string']))
			return;
		$loginString = $_COOKIE['login_string'];
		$stmt = $mysqli->prepare("DELETE FROM web_loginstrings WHERE loginstring = ?");
		if (!$stmt)
			return;
		$stmt->bind_param('s', $loginString);
		$stmt->execute();

		//delete the cookie, last parameter = path to cookie
		setcookie('login_string', '', time() - 1, "/");
	}

	/**
	 * Fills in $_SESSION vars with sensitive info about user. Users cannot see or manipulate $_SESSION vars, unlike
	 * cookies, but they are deleted once the browser is closed. This function is built to provide lots of info about
	 * user with only userId required; ALWAYS check login authenticity before calling.
	 *
	 * @param number $userId
	 * @param mysqli $mysqli
	 */
	function populateSessionWithUserId($userId, $mysqli)
	{
		$stmt = $mysqli->prepare("SELECT email, role FROM web_logins WHERE id = ? LIMIT 1");
		if (!$stmt)
			return;

		//check who has this email in our userbase
		$stmt->bind_param('i', $userId);
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result($email, $role);
		$stmt->fetch();
		$stmt->close();

		// XSS protection as we might print this value
		$email = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $email);
		$_SESSION['email'] = $email;

		$role = preg_replace("/[^a-zA-Z0-9_\-]+/", "", $role);
		$_SESSION['role'] = $role;
	}
	function getRole()
	{
		return $_SESSION['role'];
	}
	function getEmail()
	{
		return $_SESSION['email'];
	}