<?php

require_once("fenixedu.config.php");
require_once("RestRequest.inc.php");

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
		$this->accessToken = $config["access_token"] ?? null;
		$this->refreshToken = $config["refresh_token"] ?? null;
		$this->callbackUrl = $config["callback_url"] ?? null;
		$this->apiBaseUrl = $config["api_base_url"] ?? FENIX_API_BASE_URL;

		if (isset($_SESSION['accessToken'])) {
			$this->accessToken = $_SESSION['accessToken'];
			$this->refreshToken = $_SESSION['refreshToken'];
			$this->expirationTime = $_SESSION['expires'];
		}
	}

	public static function getSingleton(): FenixEdu
    {
		if (self::$INSTANCE == null) self::$INSTANCE = new self();
		return self::$INSTANCE;
	}

	function getAuthUrl(): string
    {
		$params = array(
			'client_id'	=> $this->accessKey,
			'redirect_uri' => $this->callbackUrl
		);
		$query = http_build_query($params, '', '&');
		return $this->apiBaseUrl . "/oauth/userdialog?" . $query;
	}

    /**
     * @throws Exception
     */
    function getAccessTokenFromCode($code): bool
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

	protected function buildURL($endpoint, $public): string
    {
		$url = $this->apiBaseUrl . "/api/fenix/v1/" . $endpoint;
		if (!$public) {
			$url .= '?access_token=' . urlencode($this->getAccessToken());
		}
		return $url;
	}

    /**
     * @throws FenixEduException
     */
    protected function getAccessToken()
	{
		if ($this->expirationTime <= time()) {
			$this->refreshAccessToken();
		}
		return $this->accessToken;
	}

    /**
     * @throws FenixEduException
     */
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

	public function downloadPhoto(string $pictureUrl, int $userId)
	{
		$img = base64_decode($pictureUrl);
		$path = USER_DATA_FOLDER . '/' . $userId . '/profile.png';
		file_put_contents($path, $img);
	}

    /**
     * @throws FenixEduException
     * @throws Exception
     */
    protected function get($endpoint, $public = false)
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

    /**
     * @throws Exception
     */
    protected function put($endpoint, $data = "")
	{
		$url = $this->buildURL($endpoint, true);
		$req = new RestRequest($url, 'POST', $data);
		$req->execute();
		return json_decode($req->getResponseBody());
	}

	public function getIstId()
	{
		return $this->getPerson()->istId;
	}

    /**
     * @throws FenixEduException
     */
    public function getAboutInfo()
	{
		return $this->get("about");
	}

    /**
     * @throws FenixEduException
     */
    public function getCourse($id)
	{
		return $this->get("courses/" . $id);
	}

    /**
     * @throws FenixEduException
     */
    public function getCourseEvaluations($id)
	{
		return $this->get("courses/" . $id . "/evaluations");
	}

    /**
     * @throws FenixEduException
     */
    public function getCourseGroups($id)
	{
		return $this->get("courses/" . $id . "/groups");
	}

    /**
     * @throws FenixEduException
     */
    public function getCourseSchedule($id)
	{
		return $this->get("courses/" . $id . "/schedule");
	}

    /**
     * @throws FenixEduException
     */
    public function getCourseStudents($id)
	{
		return $this->get("courses/" . $id . "/students");
	}

    /**
     * @throws FenixEduException
     */
    public function getDegrees()
	{
		return $this->get("degrees");
	}

    /**
     * @throws FenixEduException
     */
    public function getDegree($id)
	{
		return $this->get("degrees/" . $id);
	}

    /**
     * @throws FenixEduException
     */
    public function getDegreeCourses($id)
	{
		return $this->get("degrees/" . $id . "/courses");
	}

    /**
     * @throws FenixEduException
     */
    public function getPerson()
	{
		return $this->get("person");
	}

    /**
     * @throws FenixEduException
     */
    public function getPersonCalendarClasses()
	{
		return $this->get("person/calendar/classes");
	}

    /**
     * @throws FenixEduException
     */
    public function getPersonCalendarEvaluations()
	{
		return $this->get("person/calendar/evaluations");
	}

    /**
     * @throws FenixEduException
     */
    public function getPersonCourses()
	{
		return $this->get("person/courses");
	}

    /**
     * @throws FenixEduException
     */
    public function getCurriculum()
	{
		return $this->get("person/curriculum");
	}

    /**
     * @throws FenixEduException
     */
    public function getPersonEvaluations()
	{
		return $this->get("person/evaluations");
	}

    /**
     * @throws Exception
     */
    public function enrollPersonEvaluation($id)
	{
		return $this->put("person/evaluations/" . $id, "enrol=yes");
	}

    /**
     * @throws Exception
     */
    public function disenrollPersonEvaluation($id)
	{
		return $this->put("person/evaluations/" . $id, "enrol=no");
	}

    /**
     * @throws FenixEduException
     */
    public function getPersonPayments()
	{
		return $this->get("person/payments");
	}

    /**
     * @throws FenixEduException
     */
    public function getSpaces()
	{
		return $this->get("spaces");
	}

    /**
     * @throws FenixEduException
     */
    public function getSpace($id)
	{
		return $this->get("spaces/" . $id);
	}
}
