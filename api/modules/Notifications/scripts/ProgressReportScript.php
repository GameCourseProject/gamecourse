<?php
/**
 * This is the Progress Report script, which sends progress reports
 * to students' emails.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Module\Notifications\Notifications;
use PHPMailer\PHPMailer\PHPMailer;
use Utils\CronJob;
use Utils\Utils;

include '../lib/PHPMailer.php';
include '../lib/SMTP.php';
include '../lib/Exception.php';

require __DIR__ . "/../../../inc/bootstrap.php";

$courseId = $argv[1];
$course = Course::getCourseById($courseId);

$notifications = new Notifications($course);
$info = $notifications->getStaticInfo($courseId);
$seqNr = Core::database()->select(Notifications::TABLE_NOTIFICATIONS_PROGRESS_REPORT, ["course" => $courseId], "count(*)") + 1;

$subject = $info['courseName'] . " - " . $info['periodicity'] . " Report #" . $seqNr;
$nrReportsSent = 0;

$timeLeft = null;
$error = false;

// Send e-mail to each course student
$students = $course->getStudents(true);
foreach ($students as $student) {
    // No email set, continue
    if (!isset($student["email"])) continue;

    $to = $student["email"];
    $studentId = $student["id"];

    list($report, $totalXP, $currentPeriodXP, $diff, $tLeft, $prediction, $pieChart, $areaChart) = $notifications->getUserProgressReport($studentId, $seqNr, $info);
    $timeLeft = $tLeft;

    if (!sendEmail($to, $subject, $report)) $error = true;
    else {
        Core::database()->insert(Notifications::TABLE_NOTIFICATIONS_PROGRESS_REPORT_HISTORY, [
            "course" => $courseId, "user" => $studentId, "emailSend" => $to, "seqNr" => $seqNr,
            "totalXP" => $totalXP, "periodXP" => $currentPeriodXP, "diffXP" => $diff,
            "timeLeft" => $timeLeft, "prediction" => $prediction ?? null,
            "pieChart" => $pieChart, "areaChart" => $areaChart
        ]);
        $nrReportsSent++;
    }
}


if (!$error) {
    Core::database()->insert(Notifications::TABLE_NOTIFICATIONS_PROGRESS_REPORT, [
        "course" => $courseId, "seqNr" => $seqNr, "reportsSent" => $nrReportsSent,
        "periodStart" => $info['startPeriodDate'], "periodEnd" => $info['endPeriodDate']
    ]);
    logProgressReport($notifications->getLogsPath(), "Progress reports sent successfully.", "SUCCESS");

    if ($timeLeft == 0) {
        CronJob::removeCronJob("ProgressReport", $courseId);
        logProgressReport($notifications->getLogsPath(), "Last progress report sent. Removed Cron Job.", "SUCCESS");
    }
} else {
    logProgressReport($notifications->getLogsPath(), "Progress reports not sent.");
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
        $mail->isHTML();
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

function logProgressReport($file, $result, $type="ERROR") {
    $sep = "\n================================================================================\n";
    $date = "[" . date("Y/m/d H:i:s") ."] : php : " . $type . " \n\n";
    $error = "\n\n================================================================================\n\n";
    file_put_contents($file, $sep . $date . $result . $error, FILE_APPEND);
}
