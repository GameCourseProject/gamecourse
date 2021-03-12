<?php

//

error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'classes/ClassLoader.class.php';


use GameCourse\Core;
use GameCourse\Course;
use GameCourse\ModuleLoader;

class GameRules{

	private $courseId;

	private $host = "127.0.0.1";
	private $port = "8004";
	private $logFile = "/var/www/html/gamecourse/legacy_data/GR_log_";
	private $autogamePath = "/var/www/html/gamecourse/autogame/run_autogame.py";

	public function __construct($courseId)
    {
        $this->courseId = $courseId;
    }


	static function noConnectionsHandler ($errno, $errstr, $errfile, $errline) {
		Core::$systemDB->update("autogame", ["isRunning" => (int)0 ], ["course" => 0]);
	}


	public function logGameRules($result) {
		date_default_timezone_set("Europe/Lisbon");
		$sep = "-----------------------------------------------------------------------\n";
		$date = "  Date:\t" . date("d-m-Y") . " " . date("H:i:s") . "\n\n";
		$end = "\n\n\n";
		file_put_contents($this->logFile, $sep, FILE_APPEND);
		file_put_contents($this->logFile, $date, FILE_APPEND);
		file_put_contents($this->logFile, $sep, FILE_APPEND);
		file_put_contents($this->logFile, $result, FILE_APPEND);
		file_put_contents($this->logFile, $end, FILE_APPEND);
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


	public function callAutogame() {
		$cmd = "python3 ". $this->autogamePath . " " . strval($this->courseId) ." >> /var/www/html/gamecourse/autogame/gr_log.txt &";
	    $output = system($cmd);
	    // log later
	}

	public function setServerSocketRunning() {
		Core::$systemDB->update("autogame", ["isRunning" => (int)1 ], ["course" => 0]);
	}


    public function startServerSocket(){

		$server = "tcp://" . $this->host . ":" . $this->port;
	    	$socket = stream_socket_server($server, $errno, $errstr) or die("Could not create socket\n");

		if (!$socket) {

		    echo "Error: Could not create server socket";
		} 
		
		else {
			# command that calls python script - output is supressed by latter part of the command
	    	$this->setServerSocketRunning();
	    	$this->callAutogame();

		    while (True) {

				try {
			        $conn = stream_socket_accept($socket);

					if (!$conn){
						$error= "Could not accept connections on stream_socket_accept in startServerSocket().";
						$this->logGameRules($error);

						return;
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
					
			   	    $viewHandler = $course->getModule('views')->getViewHandler();
					
					

			            # if args are not empty on call
			            if (!empty($args)) {
			                $res = $viewHandler->callFunction($lib, $func, $args);
			            }
			            else {
			                $res = $viewHandler->callFunction($lib, $func, []);
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
					return;
				}

		    }

		}

	echo($errstr);
	
    }

	public function run()
    {
	// sets a custom error handler for correcting database inconsistencies
	//set_error_handler("GameRules::noConnectionsHandler");
 	
	// set logfile path
	$this->logFile .= $this->courseId . ".txt";
	
	    if ($this->checkAutoGameRunning()) {
	    	return;
	    }

	    if ($this->checkServerSocket()) {

	    	$this->callAutogame();
	    } 

	    else {
	    	$this->startServerSocket();
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



