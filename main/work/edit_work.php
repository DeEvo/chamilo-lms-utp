<?php
/* For licensing terms, see /license.txt */

$language_file = array('exercice', 'work', 'document', 'admin', 'gradebook');

require_once '../inc/global.inc.php';
$lib_path = api_get_path(LIBRARY_PATH);

/* Libraries */
require_once $lib_path.'fileManage.lib.php';
require_once 'work.lib.php';


// Section (for the tabs)
$this_section = SECTION_COURSES;

if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$courseInfo = api_get_course_info();
$sessionId = api_get_session_id();
$workId = isset($_GET['id']) ? intval($_GET['id']) : null;
$workData = get_work_data_by_id($workId);
$homework = get_work_assignment_by_id($workId);
$locked = api_resource_is_locked_by_gradebook($workId, LINK_STUDENTPUBLICATION);

if (api_is_platform_admin() == false && $locked == true) {
    api_not_allowed(true);
}

if (!empty($sessionId) && isset($workData['session_id'])) {
    if ($sessionId != $workData['session_id']) {
        api_not_allowed(true);
    }
}

$htmlHeadXtra[] = to_javascript_work();
$interbreadcrumb[] = array('url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(), 'name' => get_lang('StudentPublications'));
$interbreadcrumb[] = array('url' => '#', 'name' => get_lang('Edit'));

Display::display_header();

$form = new FormValidator('edit_dir', 'post', api_get_path(WEB_CODE_PATH).'work/edit_work.php?id='.$workId.'&'. api_get_cidreq());
$form->addElement('header', get_lang('Edit'));

$title = !empty($workData['title']) ? $workData['title'] : basename($workData['url']);

$defaults = $workData;
$defaults['new_dir'] = Security::remove_XSS($title);

$there_is_a_end_date = false;

if (Gradebook::is_active()) {
    $link_info = is_resource_in_course_gradebook(api_get_course_id(), LINK_STUDENTPUBLICATION, $workId);
    if (!empty($link_info)) {
        $defaults['weight'] = $link_info['weight'];
        $defaults['category_id'] = $link_info['category_id'];
        $defaults['make_calification'] = 1;
    }
} else {
    $defaults['category_id'] = '';
}

if ($homework['expires_on'] != '0000-00-00 00:00:00') {
    $homework['expires_on'] = api_get_local_time($homework['expires_on']);
    $there_is_a_expire_date = true;
    $defaults['enableExpiryDate'] = true;
} else {
    $homework['expires_on'] = api_get_local_time();
    $expires_date_array = convert_date_to_array(api_get_local_time(), 'expires');
    $defaults 			= array_merge($defaults, $expires_date_array);
    $there_is_a_expire_date = false;
}

if ($homework['ends_on'] != '0000-00-00 00:00:00') {
    $homework['ends_on'] = api_get_local_time($homework['ends_on']);
    $there_is_a_end_date = true;
    $defaults['enableEndDate'] = true;

} else {
    $homework['ends_on'] = api_get_local_time();
    $expires_date_array = convert_date_to_array(api_get_local_time(), 'ends');
    $defaults 			= array_merge($defaults, $expires_date_array);
    $there_is_a_end_date = false;
}

if ($there_is_a_end_date) {
    $end_date_array = convert_date_to_array($homework['ends_on'], 'ends');
    $defaults = array_merge($defaults, $end_date_array);
}

if ($there_is_a_expire_date) {
    $expires_date_array = convert_date_to_array($homework['expires_on'], 'expires');
    $defaults = array_merge($defaults, $expires_date_array);
}

$defaults['add_to_calendar'] = isset($homework['add_to_calendar']) ? $homework['add_to_calendar'] : null;
$form = getFormWork($form, $defaults);
$form->addElement('hidden', 'work_id', $workId);
$form->addElement('style_submit_button', 'submit', get_lang('ModifyDirectory'), 'class="save"');

$display_edit_form = true;

if ($form->validate()) {
    $params = $form->exportValues();
    $workId = $params['work_id'];
    $edit_check = false;
    $workData = get_work_data_by_id($workId);

    if (!empty($workData)) {
        $edit_check = true;
    } else {
        $edit_check = true;
    }

    if ($edit_check) {
        updateWork($workId, $params, $courseInfo);
        updatePublicationAssignment($workId, $params, $courseInfo, $group_id);
        updateDirName($workData, $params['new_dir']);

        Display::display_confirmation_message(get_lang('FolderEdited'));
    } else {
        Display::display_warning_message(get_lang('FileExists'));
    }
}

$form->display();

Display :: display_footer();
