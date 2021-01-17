<?php

namespace sync;

class XF {
	/**
	 * @return array|null
	 * @throws \Error
	 *
	 * Turn XF 2 visitor into a user and log them in
	 *
	 */
	static function user() {
		global $mysqli;

		if (!\XF::visitor()->user_id) {
			return null;
		}

		if (!($person = static::person_from_username())) {
			$username = \XF::visitor()->username;
			$email = \XF::visitor()->email ?: '';
			$password = str_shuffle(sha1(uniqid()));

			$firstName = \XF::visitor()->username;
			$lastName = 'Forum';

			$statement = $mysqli->prepare('INSERT INTO userlist (username, email, firstName, lastName, password, hideNames) VALUES (?, ?, ?, ?, ?, 1)');

			if ($statement === false)
				throw new \Error($mysqli->error);

			$statement->bind_param('sssss', $username, $email, $firstName, $lastName, $password);
			$statement->execute();
			$statement->close();
                        
                        $person = static::person_from_username();
                        $person['newInitialUser'] = 1;
		} else {
                        $id = $person['id'];
                        $hasLogin = $mysqli->query("SELECT userId FROM logins WHERE userId = $id AND sessionType = 'new'")->num_rows;
                        if ($hasLogin == 0) {
                            $person['newReturnUser'] = 1;
                        }
                }

		$_SESSION['logged_in'] = $person['id'];
		$_SESSION['firstName'] = $person['firstName'];
		$_SESSION['lastName'] = $person['lastName'];
		$_SESSION['usName'] = $person['username'];

		return $person;
	}

	/**
	 * @param array|null $username
	 * @return array|null
	 * @throws \Error
	 *
	 * Query for user details using their username as the key
	 *
	 */
	static function person_from_username($username = null) {
		global $mysqli;

		$param = $username ?: \XF::visitor()->username;
		$statement = $mysqli->prepare('SELECT id FROM userlist WHERE username = ?');

		if ($statement === false)
			throw new \Error($mysqli->error);

		$statement->bind_param('s', $param);
		$statement->execute();
		$statement->bind_result($id);
		$statement->fetch();
		$statement->close();

		return $id ? get_person_info($id) + compact('id') : null;
	}

	/**
	 * @return mixed
	 *
	 * Bootstrap the XF 2 environment
	 *
	 */
	static function app() {
                // TESTCODE
		//define('__XF__', dirname(dirname(__DIR__)) . '/forum');  // test version
		define('__XF__', dirname(dirname(__DIR__)));  // live version

		require __XF__ . '/src/XF.php';

		$_SERVER['SCRIPT_NAME'] = sprintf('%s/index.php', __XF__);
		$_SERVER['REQUEST_URI'] = sprintf('%s/index.php', str_replace($_SERVER['DOCUMENT_ROOT'], '', __XF__));

		\XF::start(__XF__);
		$app = \XF::setupApp('XF\Pub\App');
		$app->start();

		return $app;
	}

	/**
	 * @param array $params
	 *
	 * Get the output buffer, set page parameters, and output HTML
	 *
	 */
	static function render($params = array()) {
		global $app, $head, $title, $content;

		if ($params)
			extract($params, EXTR_OVERWRITE);

		if (isset($head))
			define('SCRIPT_PAGE_HEAD', $head);

		if (isset($title))
			define('SCRIPT_PAGE_TITLE', $title);

		if (isset($content) && !empty($content))
			define('SCRIPT_PAGE_CONTENT', $content);

		define('SCRIPT_PAGE_NAVIGATION_ID', 'competition');
		define('SCRIPT_PAGE_BREADCRUMBS', false);
		define('SCRIPT_PAGE_RAW', false);
		define('SCRIPT_PAGE_NAME', pathinfo(__FILE__, PATHINFO_FILENAME));

		$app->run()->send($app->request());
	}

	/**
	 *
	 * Get the web root directory
	 *
	 */
	static function webroot() {
		return basename(dirname(dirname(__DIR__))) !== 'test' ? '' : '/test';
	}
}