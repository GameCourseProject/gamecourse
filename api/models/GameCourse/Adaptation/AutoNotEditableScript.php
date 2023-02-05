<?php
/**
 * This script is used to change a specific EditableGameElement with isEditable = false.
 * It runs when the number of days (nDays column) since the EditableGameElement was made isEditable = true
 * finish.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use GameCourse\Adaptation\EditableGameElement;

require __DIR__ . "/../../../inc/bootstrap.php";

$editableGameElementId = intval($argv[1]);
$editableGameElement = EditableGameElement::getEditableGameElementById($editableGameElementId);

$editableGameElement->setEditable(false);