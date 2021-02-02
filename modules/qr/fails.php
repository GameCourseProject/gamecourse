<html>

<head>
    <style>
        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
        }
    </style>
</head>

<body>
    <div id="failedAttemps">
        <table style="margin:2px">
            <?php
            set_include_path(get_include_path() . PATH_SEPARATOR . '../../');
            include('classes/ClassLoader.class.php');
            include('../../config.php');

            use \GameCourse\Core;

            Core::init();

            if (isset($_REQUEST["course"])) {
                $results = Core::$systemDB->executeQuery("select studentNumber, name, major, msg, date, ip "
                    . "from qr_error q natural join course_user u natural join game_course_user g "
                    . "where q.studentNumber=g.StudentNumber and g.id = u.id and course=" . $_REQUEST["course"] . ";");
            } else {
                $results = Core::$systemDB->executeQuery("select studentNumber, name, major, msg, date, ip "
                    . "from qr_error q natural join course_user u natural join game_course_user g "
                    . "where q.studentNumber=g.StudentNumber and g.id = u.id ;");
            }

            $sep = ";"; // separador
            $major = "";
            foreach ($results as $result) {
                /* if($row_array['campus']=="T"){
			$campus="Taguspark";
		}else{ $campus="Alameda"; } */
                //$student_name=utf8_decode($row_array['student_name']);

            ?>
                <tr>
                    <th><?= "{$result['studentNumber']}" ?></th>
                    <th><?= "{$result['name']}" ?></th>
                    <th><?= "{$result['major']}" ?></th>
                    <th><?= "{$result['ip']}" ?></th>
                    <th><?= "{$result['msg']}" ?></th>
                    <th><?= "{$result['date']}" ?></th>
                </tr>
            <?php

                //echo("{$row_array['student_id']}".$sep."{$student_name}".$sep."{$row_array['campus']}".$sep."{$row_array['ip']}".$sep." {$row_array['msg']}".$sep."{$row_array['datetime']}\n"); 
            }
            //pg_close($connection);
            // num;nome;A/T;ip;errorMsg;datetime
            ?>
        </table>
    </div>
</body>

</html>