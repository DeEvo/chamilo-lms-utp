<?php
/* For licensing terms, see /license.txt */

/**
 * 	Show or hide an assignment
 * 	@package chamilo.work
 */
require_once '../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

if (!api_is_platform_admin() && !api_is_course_admin()) {
    api_not_allowed();
}

require_once 'work.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

$workId = $_GET['id'];
$userId = api_get_user_id();
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$groupId = api_get_group_id();

$courseInfo = api_get_course_info();
$courseDir = api_get_path(SYS_COURSE_PATH) . $courseInfo['path'];

$workData = get_work_data_by_id($workId);
$homework = get_work_assignment_by_id($workId);

$copyTitle = getWorkCopiedTitle($workData['title'], $courseId, $sessionId, $groupId);

$newWorkData = array_merge($workData);

$newWorkData['new_dir'] = $copyTitle;
$newWorkData['title'] = $copyTitle;

$algo = addDir($newWorkData, $userId, $courseInfo, $groupId, $sessionId);

header('Location:' . api_get_path(WEB_CODE_PATH) . 'work/work.php?' . api_get_cidreq());
