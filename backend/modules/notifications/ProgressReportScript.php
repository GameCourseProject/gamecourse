<?php
namespace Modules\Notifications;

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';

include 'lib/PHPMailer.php';
include 'lib/SMTP.php';
include 'lib/Exception.php';

require_once 'config.php';

use GameCourse\Core;
use GameCourse\Course;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Utils;

Core::init();

// To run manually, use the www-data user:
// sudo -u www-data php /var/www/html/gamecourse/backend/modules/notifications/ProgressReportScript.php 1
// Replace "1" in the above command with the appropriate course ID

$courseId = $argv[1];
$course = Course::getCourse($courseId);

$info = Notifications::getStaticInfo($courseId);
$seqNr = Core::$systemDB->select(Notifications::TABLE_PROGRESS_REPORT, ["course" => $courseId], "count(*)") + 1;

$subject = $info['courseName'] . " - " . $info['periodicity'] . " Report #" . $seqNr;
$nrReportsSent = 0;
$error = false;

// Send e-mail to each course student
$students = $course->getUsersWithRole("Student");
foreach ($students as $student) {
    // No email set, continue
    if (!isset($student["email"]) || $student["email"] == null || $student["email"] == "") continue;

    $to = $student["email"];
    $studentId = $student["id"];

    list($report, $totalXP, $currentPeriodXP, $diff, $timeLeft, $prediction, $pieChart, $areaChart) = Notifications::getStudentProgressReport($courseId, $seqNr, $studentId, $student, $info);

    if (!sendEmail($to, $subject, $report)) $error = true;
    else {
        Core::$systemDB->insert(Notifications::TABLE_PROGRESS_REPORT_HISTORY, [
            "course" => $courseId, "user" => $studentId, "emailSend" => $to, "seqNr" => $seqNr,
            "totalXP" => $totalXP, "periodXP" => $currentPeriodXP, "diffXP" => $diff,
            "timeLeft" => $timeLeft, "prediction" => $prediction ?? null,
            "pieChart" => $pieChart, "areaChart" => $areaChart
        ]);
        $nrReportsSent++;
    }
}


if (!$error) {
    Core::$systemDB->insert(Notifications::TABLE_PROGRESS_REPORT, [
        "course" => $courseId, "seqNr" => $seqNr, "reportsSent" => $nrReportsSent,
        "periodStart" => $info['startPeriodDate'], "periodEnd" => $info['endPeriodDate']
    ]);
    logProgressReport($courseId, "Progress reports sent successfully.", "SUCCESS");

    if ($timeLeft == 0) {
        Notifications::removeCronJob($courseId);
        logProgressReport($courseId, "Last progress report sent. Removed Cron Job.", "SUCCESS");
    }
} else {
    logProgressReport($courseId, "Progress reports not sent.");
}

/**
 * Sends an HTML e-mail to one recipient.
 * It uses PHPMailer to be able to send e-mails to external recipients.
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 *
 * @return bool
 * @throws Exception
 */
function sendEmail(string $to, string $subject, string $message): bool
{
    $senderName = "GameCourse";

    if (Utils::strEndsWith($to, "@tecnico.ulisboa.pt")) { // internal e-mails
        $headers  = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: " . $senderName . " <noreply@pcm.rnl.tecnico.ulisboa.pt>" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        return mail($to, $subject, $message, $headers);

    } else { // external e-mails (use PHPMailer)
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->isHTML(true);
        $mail->CharSet = "UTF-8";

        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = "true";
        $mail->SMTPSecure = "tls";
        $mail->Port = "587";

        $mail->Username = GOOGLE_ACCOUNT_EMAIL;
        $mail->Password = GOOGLE_ACCOUNT_PASSWORD;
        $mail->From = GOOGLE_ACCOUNT_EMAIL;
        $mail->FromName = $senderName;

        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->addAddress($to);

        return $mail->send();
    }
}

function logProgressReport($courseId, $result, $type="ERROR") {
    date_default_timezone_set("Europe/Lisbon");
    $sep = "\n================================================================================\n";
    $date = "[" . date("Y/m/d H:i:s") ."] : php : " . $type . " \n\n";
    $error = "\n\n================================================================================\n\n";
    file_put_contents(SERVER_PATH . "/logs/log_notifications_course_" . $courseId, $sep . $date . $result . $error, FILE_APPEND);
}
