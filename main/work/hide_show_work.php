<?php
/* For licensing terms, see /license.txt */

/**
 * 	Show or hide an assignment
 * 	@package chamilo.work
 */
require_once '../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

if (!api_is_platform_admin()) {
    api_not_allowed();
}

require_once 'work.lib.php';

$workId = $_GET['id'];
$courseInfo = api_get_course_info();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();

$workIsVisible = workIsVisible($workId, $courseId, $sessionId);

if ($workIsVisible) {
    workSetInvisible($workId, $courseInfo, $sessionId);
} else {
    workSetVisible($workId, $courseInfo, $sessionId);
}

header('Location:' . api_get_path(WEB_CODE_PATH) . 'work/work.php?' . api_get_cidreq());
