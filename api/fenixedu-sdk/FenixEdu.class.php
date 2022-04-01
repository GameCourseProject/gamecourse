<?php

require_once("fenixedu.config.php");
require_once("RestRequest.inc.php");

ini_set('display_errors', 1);

class FenixEduException extends Exception
{

	private $error;
	private $errorDescription;

	public function __construct($result)
	{
		$this->error = $result->error;
		$this->errorDescription = $result->error_description;
	}

	public function getError()
	{
		return $this->error;
	}

	public function getErrorDescription()
	{
		return $this->errorDescription;
	}
}

class FenixEdu
{

	private static $INSTANCE;

	private $accessKey;
	private $secretKey;

	private $user;

	private $code;

	private $accessToken;
	private $refreshToken;
	private $expirationTime;

	private $callbackUrl;
	private $apiBaseUrl;

	protected function __construct()
	{
		global $_FENIX_EDU;
		global $_SESSION;
		if (php_sapi_name() != 'cli' && !session_id()) {
			session_start();
		}
		$config = $_FENIX_EDU;
		$this->accessKey = $config["access_key"];
		$this->secretKey = $config["secret_key"];
		$this->accessToken = isset($config["access_token"]) ? $config["access_token"] : null;
		$this->refreshToken = isset($config["refresh_token"]) ? $config["refresh_token"] : null;
		$this->callbackUrl = isset($config["callback_url"]) ? $config["callback_url"] : $this->getCurrentUrl();
		$this->apiBaseUrl = isset($config["api_base_url"]) ? $config["api_base_url"] : "http://fenix.ist.utl.pt";

		if (isset($_SESSION['accessToken'])) {
			$this->accessToken = $_SESSION['accessToken'];
			$this->refreshToken = $_SESSION['refreshToken'];
			$this->expirationTime = $_SESSION['expires'];
		}
	}

	public static function getSingleton()
	{
		if (self::$INSTANCE == null) {
			self::$INSTANCE = new self();
		}
		return self::$INSTANCE;
	}

	function getAuthUrl()
	{
		$params = array(
			'client_id'	=> $this->accessKey,
			'redirect_uri' => $this->callbackUrl
		);
		$query = http_build_query($params, '', '&');
		return $this->apiBaseUrl . "/oauth/userdialog?" . $query;
	}

	function getAccessTokenFromCode($code)
	{
		$reqbody = array('client_id' => $this->accessKey,  'client_secret' => $this->secretKey, 'redirect_uri' => $this->callbackUrl, 'code' => $code, 'grant_type' => 'authorization_code');
		$url = $this->apiBaseUrl . "/oauth/access_token";
		$req = new RestRequest($url, 'POST', $reqbody);
		$req->execute();
		$info = $req->getResponseInfo();

		if ($info['http_code'] == 200) {
			$json = json_decode($req->getResponseBody());
			$this->accessToken = $_SESSION['accessToken'] = $json->access_token;
			$this->refreshToken = $_SESSION['refreshToken'] = $json->refresh_token;
			$this->expirationTime = $_SESSION['expires'] = time() + $json->expires;
			return true;
		} else {
			return false;
		}
	}

	protected function buildURL($endpoint, $public)
	{
		$url = $this->apiBaseUrl . "/api/fenix/v1/" . $endpoint;
		if (!$public) {
			$url .= '?access_token=' . urlencode($this->getAccessToken());
		}
		return $url;
	}

	protected function getAccessToken()
	{
		if ($this->expirationTime <= time()) {
			$this->refreshAccessToken();
		}
		return $this->accessToken;
	}

	protected function refreshAccessToken()
	{
		$reqbody = array('client_id' => $this->accessKey,  'client_secret' => $this->secretKey, 'refresh_token' => $this->refreshToken);
		$url = $this->apiBaseUrl . "/oauth/refresh_token";
		$req = new RestRequest($url, 'POST', $reqbody);
		$req->execute();
		$info = $req->getResponseInfo();
		$result = json_decode($req->getResponseBody());
		if ($info['http_code'] == 200) {
			$this->accessToken = $_SESSION['accessToken'] = $result->access_token;
			$this->expirationTime = $_SESSION['expires'] = time() + $result->expires_in;
		} elseif ($info['http_code'] == 401) {
			throw new FenixEduException($result);
		}
	}

	public function downloadPhoto($pictureUrl, $userId)
	{
		$pic = base64_decode($pictureUrl);
		$path = 'photos/' . $userId . '.png';
		file_put_contents($path, $pic);
	}

	//mudar pra protected
	public function get($endpoint, $public = false)
	{
		$url = $this->buildURL($endpoint, $public);
		$req = new RestRequest($url, 'GET');
		$req->execute();
		$result = json_decode($req->getResponseBody());
		$info = $req->getResponseInfo();
		if ($info['http_code'] == 401)
			throw new FenixEduException($result);
		elseif ($info['http_code'] == 200)
			return $result;
	}

	protected function put($endpoint, $data = "")
	{
		$url = $this->buildURL($endpoint, $public);
		$req = new RestRequest($url, 'POST', $data);
		$req->execute();
		return json_decode($req->getResponseBody());
	}

	public function getIstId()
	{
		return $this->getPerson()->istId;
	}

	public function getAboutInfo()
	{
		return $this->get("about");
	}

	public function getCourse($id)
	{
		return $this->get("courses/" . $id);
	}

	public function getCourseEvaluations($id)
	{
		return $this->get("courses/" . $id . "/evaluations");
	}

	public function getCourseGroups($id)
	{
		return $this->get("courses/" . $id . "/groups");
	}

	public function getCourseSchedule($id)
	{
		return $this->get("courses/" . $id . "/schedule");
	}

	public function getCourseStudents($id)
	{
		return $this->get("courses/" . $id . "/students");
	}

	public function getDegrees()
	{
		return $this->get("degrees");
	}

	public function getDegree($id)
	{
		return $this->get("degrees/" . $id);
	}

	public function getDegreeCourses($id)
	{
		return $this->get("degrees/" . $id . "/courses");
	}

	public function getPerson()
	{
		return $this->get("person");
	}

	public function getPersonCalendarClasses()
	{
		return $this->get("person/calendar/classes");
	}

	public function getPersonCalendarEvaluations()
	{
		return $this->get("person/calendar/evaluations");
	}

	public function getPersonCourses()
	{
		return $this->get("person/courses");
	}

	public function getCurriculum()
	{
		return $this->get("person/curriculum");
	}

	public function getPersonEvaluations()
	{
		return $this->get("person/evaluations");
	}

	public function enrollPersonEvaluation($id)
	{
		return $this->put("person/evaluations/" . $id, "enrol=yes");
	}

	public function disenrollPersonEvaluation($id)
	{
		return $this->put("person/evaluations/" . $id, "enrol=no");
	}

	public function getPersonPayments()
	{
		return $this->get("person/payments");
	}

	public function getSpaces()
	{
		return $this->get("spaces");
	}

	public function getSpace($id)
	{
		return $this->get("spaces/" . $id);
	}
}
