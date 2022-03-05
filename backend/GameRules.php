<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'classes/ClassLoader.class.php';

use GameCourse\Core;
use GameCourse\Course;
use GameCourse\Views\Dictionary;
use GameCourse\Views\ViewHandler;

Core::init();
Dictionary::init();

class GameRules{

	const ROOT_FOLDER = SERVER_PATH . "/";

	private $courseId;
	private $host = "127.0.0.1";
	private $port = "8004";
	private $logFile = self::ROOT_FOLDER . "logs/log_course_";
	private $autogamePath = self::ROOT_FOLDER . "autogame/run_autogame.py";
	private $all = False;
	private $targets = null;
	private $rulePath;
	private $testMode = False;


	public function __construct($courseId, $all, $targets, $testMode=False)
    {
        $this->courseId = $courseId;
        Dictionary::$courseId = $courseId;
        $name = Core::$systemDB->select("course", ["id" => $courseId], "name");
        $this->rulePath = self::ROOT_FOLDER . Course::getCourseDataFolder($courseId, $name);
		if ($all) {
			$this->all = True;
		}
		else {
			if ($targets != null) {
				$this->targets = $targets;
			}
		}
		if ($testMode) {
			$this->autogamePath = self::ROOT_FOLDER . "autogame/run_autogame_test.py";
			$this->testMode = True;
		}
    }

	static function noConnectionsHandler ($errno, $errstr, $errfile, $errline) {
		Core::$systemDB->update("autogame", ["isRunning" => (int)0 ], ["course" => 0]);
	}

	public function logGameRules($result, $type="ERROR") {
		date_default_timezone_set("Europe/Lisbon");
		$sep = "\n================================================================================\n";
		$date = "[" . date("Y/m/d H:i:s") ."] : php : " . $type . " \n\n";
		$error = "\n\n================================================================================\n\n";
		file_put_contents($this->logFile, $sep . $date . $result . $error, FILE_APPEND);
	}

	public function checkCourseExists() {
		$result = Core::$systemDB->selectMultiple("autogame", ["course" => $this->courseId]);
		$courseExists = empty($result) ? false : true;
    	return $courseExists;
	}

	public function checkAutoGameRunning() {
		$result = Core::$systemDB->selectMultiple("autogame", ["course" => $this->courseId], "isRunning");
		$courseRunning = $result[0]["isRunning"];
    	return $courseRunning;
	}

	public function checkServerSocket() {
		// course = 0 is restricted for the autogame socket
    	$socketOpen = Core::$systemDB->selectMultiple("autogame", ["course" => 0], "isRunning");
    	return $socketOpen[0]["isRunning"];
	}

	public function resetServerSocket() {
		Core::$systemDB->update("autogame", ["isRunning" => 0], ["course" => 0]);
	}

	public function callAutogame() {
		if ($this->all) { 
			// if running for all targets
			$cmd = "python3 ". $this->autogamePath . " " . strval($this->courseId) ." \"" . $this->rulePath . "\" all >> " . self::ROOT_FOLDER . "autogame/test_log.txt &";
		}
		else {
			if ($this->targets != null) {
				// running for certain user-specified targets
				$cmd = "python3 ". $this->autogamePath . " " . strval($this->courseId) ." \"" . $this->rulePath . "\" " . $this->targets . " >> " . self::ROOT_FOLDER . "autogame/test_log.txt &";
			}
			else {
				// running normally with resort to the participations table
				$cmd = "python3 ". $this->autogamePath . " " . strval($this->courseId) ." \"" . $this->rulePath . "\" >> " . self::ROOT_FOLDER . "autogame/test_log.txt &";
			}
			
		}

	    $output = system($cmd);
		if (file_exists($this->rulePath . "/rule-tests/rule-test-output.txt")) {
			$txt = file_get_contents($this->rulePath . "/rule-tests/rule-test-output.txt");
		}
		else {
			$txt = null;
		}
		return $txt;
	    // TODO log later
	}

	public function setServerSocketRunning() {
		Core::$systemDB->update("autogame", ["isRunning" => (int)1 ], ["course" => 0]);
	}

    public function startServerSocket(){

		$server = "tcp://" . $this->host . ":" . $this->port;
        $socket = stream_socket_server($server, $errno, $errstr) or die("Could not create socket\n");

		if (!$socket) {
            $this->logGameRules("Could not create server socket.");
		} 
		
		else {
			# command that calls python script - output is supressed by latter part of the command
	    	$this->setServerSocketRunning();
	    	$out = $this->callAutogame();
		    while (True) {
				try {
			        $conn = stream_socket_accept($socket);
					if (!$conn){
						$error= "No connections received on the server socket.";
						$this->logGameRules($error, "WARNING");
						return $error;
					}

			        $msg = fgets($conn);

			        # check if end message was received by instance of gamerules
			        $res = strcmp($msg, "end gamerules;\n");


			        # if the exit message is received, close connection to gamerules instance
			        if ($res == 0) {
			            fclose($conn);
				        if ($this->checkSockets() == 0) {
				    		fclose($socket);
				    		Core::$systemDB->update("autogame", ["isRunning" => (int)0 ], ["course" => 0]);
				    	}

			            break;
			        }

			        # otherwise correctly process data
			        else {
			            $course = $msg; # gamerules instance that made the request
			            $library = fgets($conn);
			            $function = fgets($conn);
			            $args_str = fgets($conn);
			            $args = json_decode($args_str);

			            # format function call
			            $courseNr = trim($course, "\n");
			            $lib = trim($library, "\n");
			            $func = trim($function, "\n");
			            
			            $course = Course::getCourse(intval($courseNr));
					
			            # if args are not empty on call
			            if (!empty($args)) {
			                $res = ViewHandler::callFunction($lib, $func, $args);
			            }
			            else {
			                $res = ViewHandler::callFunction($lib, $func, []);
			            }

			            $result = $res->getValue();

			            # determine type of data to be sent
			            if (is_iterable($result["value"])) {
			                $el = "collection";
			                fwrite($conn, $el);
			                $resulttype = $el;
			            }
			            else {
			                $el = "other";
			                fwrite($conn, $el);
			                $resulttype = $el;
			            }

			            # this ok is used only for synching purposes
			            $ok = fgets($conn);

			            if($resulttype == "collection") {
			                foreach ($result["value"] as $l) {
			                    #echo json_encode($l) . "<br>";
			                    $elJson = json_encode($l) ."\n";
			                    fwrite($conn, $elJson);
			                }
			            }

			            else {
			                $elJson = json_encode($result["value"]);
			                fwrite($conn, $elJson);
			            }

			            fclose($conn);
			        }
				}

				catch (Throwable $e){
					$error= "Caught an error in startServerSocket().";
					$this->logGameRules($error);

					Core::$systemDB->update("autogame", ["isRunning" => (int)0 ], ["course" => 0]);
					fclose($conn);
					return $error;
				}

		    }
			return $out;
		}

	echo($errstr);
	
    }

	public function run()
    {
        // sets a custom error handler for correcting database inconsistencies
        // set_error_handler("GameRules::noConnectionsHandler");
        // set logfile path
        $this->logFile .= strval($this->courseId) . ".txt";

		if (!($this->checkCourseExists())) {
            $error = "The course given does not exist.";
            $this->logGameRules($error);
			return $error;
		}
	
	    if ($this->checkAutoGameRunning()) {
            $error = "Autogame for the given course id is already running.";
            $this->logGameRules($error);
	    	return $error;
	    }

	    if ($this->checkServerSocket()) {
			if (fsockopen($this->host, $this->port)) {
	    		$out = $this->callAutogame();
			}
			else {
				$this->resetServerSocket();
				$out = $this->startServerSocket();
			}
	    }

	    else {
	    	$out = $this->startServerSocket();
	    }

		if ($this->testMode) {
			$txt = file_get_contents($this->rulePath . "/rule-tests/rule-test-output.txt");
			return $txt;
		}

    }


	public function checkSockets()
    {
    	// check is sockets are open
    	$socketsOpen = Core::$systemDB->selectMultiple("autogame", null, "*");
    	$resres = 0;

    	foreach ($socketsOpen as $row){
    		if ($row["isRunning"] == true && $row["course"] != 0) {
    			$resres = $resres + 1;
    		}
    	}		
    	
    	return $resres ;
    }

}



