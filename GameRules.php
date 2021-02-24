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

	public function __construct($courseId)
    {
        $this->courseId = $courseId;
    }

	static function noConnectionsHandler ($errno, $errstr, $errfile, $errline) {
		Core::$systemDB->update("autogame", ["isRunning" => (int)0 ], ["course" => 0]);
	}
    public function openSocket(){

		$host = "127.0.0.1";
		$port = 8002;


		$socket = stream_socket_server("tcp://127.0.0.1:8002", $errno, $errstr) or die("Could not create socket\n");

		if (!$socket) {
		    echo "Error: Could not create server socket";
		} else {
			# command that calls python script - output is supressed by latter part of the command
	    	$cmd = "python3 /var/www/html/gamecourse_test/autogame/run_autogame.py " . strval($this->courseId) ." >> /var/www/html/gamecourse_test/gr_log.txt &";
			$output = system($cmd);
		

		    while (True) {
			try {
		        $conn = stream_socket_accept($socket);
			if (!$conn){
				echo("Help");
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
				echo("caught here");
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
    	$courseRunning = Core::$systemDB->selectMultiple("autogame", ["course" => $this->courseId], "*");

    	foreach ($courseRunning as $c) {
    	   	if ($c["isRunning"] == true) {
	    		return;
	    	}
	    }

    	// course = 0 is restricted for the autogame socket
    	$socketOpen = Core::$systemDB->selectMultiple("autogame", ["course" => 0], "*");

    	foreach ($socketOpen as $row) {
	    	if ($row["isRunning"] == true) {
	    		# command that calls python script - output is supressed by latter part of the command
				$cmd = "python3 /var/www/html/gamecourse/autogame/run_autogame.py " . strval($this->courseId) ." >> /var/www/html/gamecourse/gr_log.txt &";
				$output = system($cmd);
			}
	    	else {
	    		Core::$systemDB->update("autogame", ["isRunning" => (int)1 ], ["course" => 0]);
				$this->openSocket();
	    	}
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


