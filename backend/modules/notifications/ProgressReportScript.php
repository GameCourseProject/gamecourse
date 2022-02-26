<?php
namespace Modules\Notifications;

error_reporting(E_ALL);
ini_set('display_errors', '1');

chdir('/var/www/html/gamecourse/backend');
include 'classes/ClassLoader.class.php';

use GameCourse\Core;

Core::init();

// TODO find variables for each student
$weekNr     = 1; // FIXME: hard-coded
$totalWeeks = 7; // FIXME: hard-coded
$currentXP  = ["xp" => 13251, "status" => "green"]; // [0-10] red; ]10-15] yellow; [16-20] green
$weeklyAvg  = ["xp" => 1201, "status" => "yellow"];
$prediction = ["xp" => 18000, "status" => "green"];

$status = [
    "red" => "background-color: ##f4cccc; color: #e06666;",
    "yellow" => "background-color: #fff2cc; color: #cf9c01;",
    "green" => "background-color: #d9ead3; color: #59a836;"
];

$to         = "joanasesinando@tecnico.ulisboa.pt"; // FIXME: only works with tecnico emails
$subject    = "Multimedia Content Production 2021/2022 - Weekly Report #" . $weekNr;
$headers    = "From: noreply@pcm.rnl.tecnico.ulisboa.pt \r\n";
$headers   .= "MIME-Version: 1.0\r\n";
$headers   .= "Content-Type: text/html; charset=UTF-8\r\n";

$message    = '<p>** Do not reply to this email **</p> 
            <h1>Weekly Report #' . $weekNr . '</h1>
            <p style="font-size: 14px"><b>Current XP:</b> <span style="' . $status[$currentXP["status"]] . ' padding: 3px;">' . number_format($currentXP["xp"], 0, ',', ' ') . '</span></p>
            <p style="font-size: 14px"><b>This week:</b> <span style="' . $status[$weeklyAvg["status"]] . ' padding: 3px;">' . number_format($weeklyAvg["xp"], 0, ',', ' ') . '</span></p>
            <p style="font-size: 14px">There are <span style="font-size: 18px; font-weight: bold;">' . ($totalWeeks - $weekNr) . '</span> weeks left. 
            If you continue with this rhythm you will achieve <span style="' . $status[$prediction["status"]] . ' padding: 5px; 
            font-weight: bold">' . number_format($prediction["xp"], 0, ',', ' ') . ' XP</span> by the end of the course.</p>';

if ($prediction >= 18000) $message .= '<p style="font-size: 14px">Nice job! Keep it up 🥳</p>';
elseif ($prediction >= 11000) $message .= '<p style="font-size: 14px">You\'re doing fine, but you can do better. Come one! 😁</p>';
else $message .= '<p style="font-size: 14px">You\'re falling behind. Try to improve this coming week! 🤓</p>';


$message = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
  <head>
    <!--[if gte mso 9]>
    <xml>
    <o:OfficeDocumentSettings>
    <o:AllowPNG/>
    <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <!--[if !mso]><!--><meta http-equiv="X-UA-Compatible" content="IE=edge"><!--<![endif]-->

    <style type="text/css">
    table, td { color: #484848; } a { color: #0000ee; text-decoration: underline; }
    @media only screen and (min-width: 570px) {
    .u-row {
    width: 670px !important;
    }
    .u-row .u-col {
    vertical-align: top;
    }

    .u-row .u-col-100 {
    width: 670px !important;
    }

    }

    @media (max-width: 570px) {
    .u-row-container {
    max-width: 100% !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    }
    .u-row .u-col {
    min-width: 320px !important;
    max-width: 100% !important;
    display: block !important;
    }
    .u-row {
    width: calc(100% - 40px) !important;
    }
    .u-col {
    width: 100% !important;
    }
    .u-col > div {
    margin: 0 auto;
    }
    }
    body {
    margin: 0;
    padding: 0;
    }

    table,
    tr,
    td {
    vertical-align: top;
    border-collapse: collapse;
    }

    p {
    margin: 0;
    }

    .ie-container table,
    .mso-container table {
    table-layout: fixed;
    }

    * {
    line-height: inherit;
    }

    a[x-apple-data-detectors=\'true\'] {
    color: inherit !important;
    text-decoration: none !important;
    }

    </style>

    <!--[if !mso]><!--><link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700&display=swap" rel="stylesheet" type="text/css"><!--<![endif]-->
  </head>

  <body class="clean-body u_body" style="margin: 0;padding: 0;-webkit-text-size-adjust: 100%;background-color: #f4f4f4;color: #484848">
    <!--[if IE]><div class="ie-container"><![endif]-->
    <!--[if mso]><div class="mso-container"><![endif]-->
    <table style="border-collapse: collapse;table-layout: fixed;border-spacing: 0;mso-table-lspace: 0pt;mso-table-rspace: 0pt;vertical-align: top;min-width: 320px;Margin: 0 auto;background-color: #f4f4f4;width:100%" cellpadding="0" cellspacing="0">
      <tbody>
        <tr>
          <td style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
          Weekly Progress Report
          </td>
        </tr>

        <tr style="vertical-align: top">
          <td style="word-break: break-word;border-collapse: collapse !important;vertical-align: top; position:relative">
            <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" style="background-color: #f4f4f4;"><![endif]-->

            <div style="background-color: #ffa73b; height: 300px; position: absolute; top: 0; left: 0; width: 100%; z-index: 1;"></div>
            <div class="u-row-container" style="padding: 0px;background-color: transparent; position: relative; z-index: 2; margin-top: 100px;">
              <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 670px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
                  <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:670px;"><tr style="background-color: transparent;"><![endif]-->

                  <!--[if (mso)|(IE)]><td align="center" width="670" style="background-color: #ffffff;width: 670px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
                  <div class="u-col u-col-100" style="max-width: 320px;min-width: 670px;display: table-cell;vertical-align: top;">
                    <div style="background-color: #ffffff;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px; border-radius: 10px; box-shadow: 0px 3px 5px 2px rgb(224, 223, 223)">
                      <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 60px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;"><!--<![endif]-->

                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                        <tr>
                          <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                              <tr>
                                <td style="padding-right: 0px;padding-left: 0px;" align="center">
                                  <a href="https://pcm.rnl.tecnico.ulisboa.pt/gamecourse/" target="_blank">
                                    <img align="center" border="0" src="frontend/src/assets/logo/logo_horz.svg" alt="GameCourse logo" title="GameCourse logo" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;;max-width: 249.1px;" width="249.1"/>
                                  </a>
                                </td>
                              </tr>
                            </table>

                          </td>
                        </tr>
                        </tbody>
                      </table>

                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                          <tr>
                            <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                              <h1 style="margin: 0px; color: #000000; line-height: 140%; text-align: center; word-wrap: break-word; font-weight: normal; font-family: \'Montserrat\',sans-serif; font-size: 32px;">
                                <strong>Weekly Progress Report</strong>
                              </h1>

                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                          <tr>
                            <td style="overflow-wrap:break-word;word-break:break-word;padding:10px; padding-top: 0; font-family:\'Montserrat\',sans-serif;" align="left">

                              <div style="line-height: 100%; text-align: center; word-wrap: break-word;">
                                <p style="font-size: 14px; line-height: 140%; text-align: center;"><span style="font-size: 16px; line-height: 22.4px; color: #9e9d9d;">Friday, March 25th - Friday, April 4th</span></p>
                              </div>

                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <table style="font-family:\'Montserrat\',sans-serif; margin-top: 10px;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                          <tr>
                            <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                              <div style="line-height: 140%; text-align: left; word-wrap: break-word;">
                                <p style="font-size: 14px; font-weight: 500; line-height: 140%;"><span style="font-size: 28px; line-height: 39.2px;">Hey </span><span style="font-size: 28px; line-height: 39.2px;">Joana,</span></p>
                              </div>

                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                          <tr>
                            <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                              <div style="line-height: 170%; text-align: justify; word-wrap: break-word;">
                                <p style="font-size: 16px; line-height: 25px;">Here is a summary of your progress in <strong>Multimedia Content Production 2021/2022</strong> last week.</p>
                              </div>

                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                          <tr>
                            <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                              <div>
                                <div style="display: flex; justify-content: space-between; width: 100%; margin-top: 10px;">
                                    <div style="display: flex; flex-direction: column; justify-content: center; border-color: #dfdfdf; border-width: 2; margin-right: 20px;
                                        border-style: solid; border-radius: 8px; padding-top: 12px; padding-left: 20px; padding-right: 20px; padding-bottom: 12px; width: 100%;">
                                        <div style="display: flex; align-items: center;">
                                            <img src="alert-circle.png" style="width: 20px; height: 20px; margin-right: 8px;">
                                            <span style="text-transform: uppercase; font-weight: 600; color: #757575">Total</span>
                                        </div>
                                        <p style="margin-bottom: 0; font-size: 24px; font-weight: 700; margin-top: 10px;">13 251 XP</p>
                                    </div>

                                    <div style="display: flex; flex-direction: column; justify-content: center; border-color: #dfdfdf; border-width: 2; margin-left: 20px;
                                        border-style: solid; border-radius: 8px; padding-top: 12px; padding-left: 20px; padding-right: 20px; padding-bottom: 12px; width: 100%;">
                                        <div style="display: flex; align-items: center;">
                                            <img src="alert-circle.png" style="width: 20px; height: 20px; margin-right: 8px;">
                                            <span style="text-transform: uppercase; font-weight: 600; color: #757575">This week</span>
                                        </div>
                                        <div style="display: flex; align-items: center; margin-top: 10px;">
                                            <p style="margin-bottom: 0; font-size: 24px; font-weight: 700; margin-top: 0;">2 300 XP</p>
                                            <div style="display: flex; align-items: center; margin-left: 8px;">
                                                <img src="alert-circle.png" style="width: 15px; height: 15px; margin-right: 3px;">
                                                <span style="font-size: 15px; font-weight: 700; color: #00C853">30%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                              </div>

                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                          <tr>
                            <td style="overflow-wrap:break-word;word-break:break-word;padding:10px; padding-top: 0;font-family:\'Montserrat\',sans-serif;" align="left">

                              <div style="line-height: 140%; text-align: left; word-wrap: break-word;">
                                <p style="font-size: 14px; line-height: 140%; text-align: right;"><span style="color: #9e9d9d; font-size: 14px; line-height: 19.6px;">*compared to previous week</span></p>
                              </div>

                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <table style="font-family:\'Montserrat\',sans-serif; margin-top: 10px" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                          <tr>
                            <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                  <td style="padding-right: 0px;padding-left: 0px;" align="center">

                                    <img align="center" border="0" src="https://quickchart.io/chart/render/zm-ee99aab1-d6fe-49b5-9247-78980022d6ad" alt="" title="" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 100%;max-width: 530px;" width="530"/>

                                  </td>
                                </tr>
                              </table>

                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <table style="font-family:\'Montserrat\',sans-serif; margin-top: 40px;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                          <tr>
                            <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                  <td style="padding-right: 0px;padding-left: 0px;" align="center">

                                    <img align="center" border="0" src="https://quickchart.io/chart/render/zm-dd0763ea-3f06-4a34-b9a6-12227dacb6f2" alt="" title="" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;width: 100%;max-width: 530px;" width="530"/>

                                  </td>
                                </tr>
                              </table>

                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <table style="font-family:\'Montserrat\',sans-serif; margin-top: 20px;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                        <tbody>
                          <tr>
                            <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                              <div align="center">
                                <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt;font-family:\'Montserrat\',sans-serif;"><tr><td style="font-family:\'Montserrat\',sans-serif;" align="center"><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="" style="height:39px; v-text-anchor:middle; width:224px;" arcsize="10.5%" stroke="f" fillcolor="#ffa73b"><w:anchorlock/><center style="color:#FFFFFF;font-family:\'Montserrat\',sans-serif;"><![endif]-->
                                  <a href="https://pcm.rnl.tecnico.ulisboa.pt/gamecourse/#/courses/1/pages/2/user/14" target="_blank" style="box-sizing: border-box;display: inline-block;font-family:\'Montserrat\',sans-serif;text-decoration: none;-webkit-text-size-adjust: none;text-align: center;color: #FFFFFF; background-color: #ffa73b; border-radius: 4px;-webkit-border-radius: 4px; -moz-border-radius: 4px; width:auto; max-width:100%; overflow-wrap: break-word; word-break: break-word; word-wrap:break-word; mso-border-alt: none;">
                                    <span style="display:block;padding:10px 20px;line-height:120%;"><span style="font-size: 16px; line-height: 19.2px;"><strong><span style="line-height: 19.2px; font-size: 16px;">GO TO YOUR PROFILE</span></strong></span></span>
                                  </a>
                                <!--[if mso]></center></v:roundrect></td></tr></table><![endif]-->
                              </div>

                            </td>
                          </tr>
                        </tbody>
                      </table>

                      <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
                    </div>
                  </div>
                  <!--[if (mso)|(IE)]></td><![endif]-->
                  <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                </div>
              </div>
            </div>
            
            <div class="u-row-container" style="padding: 0px;background-color: transparent; position: relative; z-index: 2; margin-top: 20px">
              <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 670px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
                  <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:670px;"><tr style="background-color: transparent;"><![endif]-->

                  <!--[if (mso)|(IE)]><td align="center" width="670" style="background-color: #ffffff;width: 670px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
                  <div class="u-col u-col-100" style="max-width: 320px;min-width: 670px;display: table-cell;vertical-align: top;">
                    <div style="background-color: #ffffff;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px; border-radius: 10px; box-shadow: 0px 3px 5px 2px rgb(224, 223, 223)">
                      <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 60px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px; margin-bottom: 30px;"><!--<![endif]-->

                        <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                          <tbody>
                            <tr>
                              <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">
  
                                <h2 style="margin: 0px; color: #000000; text-align: left; word-wrap: break-word; font-weight: normal; font-family: \'Montserrat\',sans-serif; font-size: 24px;">
                                  <strong>🏆 This Week\'s Awards</strong>
                                </h1>

                              </td>
                            </tr>
                          </tbody>
                        </table>

                        <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                          <tbody>
                            <tr>
                              <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                <ul style="margin-top: 0; margin-bottom: 0; padding-left: 0; list-style: none;">
                                  <li>
                                    <div style="display: flex; justify-content: space-between;">
                                      <table style="font-family:\'Montserrat\',sans-serif; margin-right: 10px;" role="presentation" cellpadding="0" cellspacing="0" width="25px" border="0">
                                        <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:0; padding-top: 10px; font-family:\'Montserrat\',sans-serif;" align="left">

                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                              <tr>
                                                <td style="padding: 0" align="center">
                                                  <img align="center" border="0" src="alert-circle.png" style="width: 25px; height: 25px; outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;;max-width: 249.1px;" width="249.1"/>
                                                </td>
                                              </tr>
                                            </table>

                                          </td>
                                        </tr>
                                        </tbody>
                                      </table>

                                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="300px" border="0">
                                        <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                              <tr>
                                                <td style="padding-right: 0px;padding-left: 0px;" align="left">
                                                  <p style="font-weight: 600; font-size: 16px;">Animated Publicist</p>
                                                  <p style="color: #9e9d9d; font-size: 14px; margin-top: 5px">Skill Tree</p>
                                                </td>
                                              </tr>
                                            </table>

                                          </td>
                                        </tr>
                                        </tbody>
                                      </table>

                                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="120px" border="0">
                                        <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;padding-right: 35px;font-family:\'Montserrat\',sans-serif;" align="left">

                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                              <tr>
                                                <td style="padding-right: 0px;padding-left: 0px;" align="right">
                                                  <p style="font-weight: 500;">300 XP</p>
                                                </td>
                                              </tr>
                                            </table>

                                          </td>
                                        </tr>
                                        </tbody>
                                      </table>

                                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="120px" border="0">
                                        <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                              <tr>
                                                <td style="padding-right: 0px;padding-left: 0px;" align="left">
                                                  <p>25/05/2021</p>
                                                </td>
                                              </tr>
                                            </table>

                                          </td>
                                        </tr>
                                        </tbody>
                                      </table>
                                    </div>
                                  </li>

                                  <li>
                                    <div style="display: flex; justify-content: space-between;">
                                      <table style="font-family:\'Montserrat\',sans-serif; margin-right: 10px;" role="presentation" cellpadding="0" cellspacing="0" width="25px" border="0">
                                        <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:0; padding-top: 10px; font-family:\'Montserrat\',sans-serif;" align="left">

                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                              <tr>
                                                <td style="padding: 0" align="center">
                                                  <img align="center" border="0" src="alert-circle.png" style="width: 25px; height: 25px; outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;clear: both;display: inline-block !important;border: none;height: auto;float: none;;max-width: 249.1px;" width="249.1"/>
                                                </td>
                                              </tr>
                                            </table>

                                          </td>
                                        </tr>
                                        </tbody>
                                      </table>

                                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="300px" border="0">
                                        <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                              <tr>
                                                <td style="padding-right: 0px;padding-left: 0px;" align="left">
                                                  <p style="font-weight: 600; font-size: 16px;">Animated Publicist</p>
                                                  <p style="color: #9e9d9d; font-size: 14px; margin-top: 5px">Skill Tree</p>
                                                </td>
                                              </tr>
                                            </table>

                                          </td>
                                        </tr>
                                        </tbody>
                                      </table>

                                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="120px" border="0">
                                        <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;padding-right: 35px;font-family:\'Montserrat\',sans-serif;" align="left">

                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                              <tr>
                                                <td style="padding-right: 0px;padding-left: 0px;" align="right">
                                                  <p style="font-weight: 500;">1550 XP</p>
                                                </td>
                                              </tr>
                                            </table>

                                          </td>
                                        </tr>
                                        </tbody>
                                      </table>

                                      <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="120px" border="0">
                                        <tbody>
                                        <tr>
                                          <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">

                                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                              <tr>
                                                <td style="padding-right: 0px;padding-left: 0px;" align="left">
                                                  <p>25/05/2021</p>
                                                </td>
                                              </tr>
                                            </table>

                                          </td>
                                        </tr>
                                        </tbody>
                                      </table>
                                    </div>
                                  </li>
                                </ul>

                              </td>
                            </tr>
                          </tbody>
                        </table>

                      <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
                    </div>
                  </div>
                  <!--[if (mso)|(IE)]></td><![endif]-->
                  <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                </div>
              </div>
            </div>
            
            <div class="u-row-container" style="padding: 0px;background-color: transparent; position: relative; z-index: 2;">
              <div class="u-row" style="Margin: 0 auto;min-width: 320px;max-width: 670px;overflow-wrap: break-word;word-wrap: break-word;word-break: break-word;background-color: transparent;">
                <div style="border-collapse: collapse;display: table;width: 100%;background-color: transparent;">
                  <!--[if (mso)|(IE)]><table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="padding: 0px;background-color: transparent;" align="center"><table cellpadding="0" cellspacing="0" border="0" style="width:670px;"><tr style="background-color: transparent;"><![endif]-->

                  <!--[if (mso)|(IE)]><td align="center" width="670" style="background-color: #ffffff;width: 670px;padding: 0px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px;" valign="top"><![endif]-->
                  <div class="u-col u-col-100" style="max-width: 320px;min-width: 670px;display: table-cell;vertical-align: top;">
                    <div style="background-color: #ffffff;width: 100% !important;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px; border-radius: 10px; box-shadow: 0px 3px 5px 2px rgb(224, 223, 223)">
                      <!--[if (!mso)&(!IE)]><!--><div style="padding: 30px 60px;border-top: 0px solid transparent;border-left: 0px solid transparent;border-right: 0px solid transparent;border-bottom: 0px solid transparent;border-radius: 0px;-webkit-border-radius: 0px; -moz-border-radius: 0px; margin-bottom: 30px;"><!--<![endif]-->

                        <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                          <tbody>
                            <tr>
                              <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">
  
                                <h2 style="margin: 0px; color: #000000; text-align: left; word-wrap: break-word; font-weight: normal; font-family: \'Montserrat\',sans-serif; font-size: 24px;">
                                  <strong>🔎 Foresight Remarks</strong>
                                </h1>
  
                              </td>
                            </tr>
                          </tbody>
                        </table>

                        <table style="font-family:\'Montserrat\',sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%" border="0">
                          <tbody>
                            <tr>
                              <td style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:\'Montserrat\',sans-serif;" align="left">
  
                                <div style="line-height: 170%; text-align: justify; word-wrap: break-word;">
                                  <p style="font-size: 16px; line-height: 25px;">There are <span style="font-weight: 700; font-size: 20px;">6</span> weeks left. If you continue with the current rhythm you will achieve 
                                    <span style="font-weight: 800; font-size: 17px; color:#00C853">18 000 XP</span> by the end of the course.</p>
                                  <p style="font-size: 16px; font-weight: 600; line-height: 25px; margin-top: 10px">You\'re doing a nice job. Keep it up! 🥳</p>
                                </div>
  
                              </td>
                            </tr>
                          </tbody>
                        </table>

                      <!--[if (!mso)&(!IE)]><!--></div><!--<![endif]-->
                    </div>
                  </div>
                  <!--[if (mso)|(IE)]></td><![endif]-->
                  <!--[if (mso)|(IE)]></tr></table></td></tr></table><![endif]-->
                </div>
              </div>
            </div>

            <!--[if (mso)|(IE)]></td></tr></table><![endif]-->
          </td>
        </tr>
      </tbody>
    </table>
    <!--[if mso]></div><![endif]-->
    <!--[if IE]></div><![endif]-->
  </body>

</html>
';


if (mail($to, $subject, $message, $headers)) {
    echo "Weekly reports sent successfully.";
} else {
    echo "There was an error when sending weekly reports.";
} // TODO: log something ala AutoGame
