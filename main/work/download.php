<?php
/* For licensing terms, see /license.txt */

/**
 *	This file is responsible for  passing requested documents to the browser.
 *	Html files are parsed to fix a few problems with URLs,
 *	but this code will hopefully be replaced soon by an Apache URL
 *	rewrite mechanism.
 *
 *	@package chamilo.work
 */
$language_file = array('document');
require_once '../inc/global.inc.php';
require_once 'work.lib.php';

$current_course_tool  = TOOL_STUDENTPUBLICATION;
$this_section = SECTION_COURSES;

// Course protection
api_protect_course_script(true);

$id = intval($_GET['id']);

$course_info = api_get_course_info();

if (empty($course_info)) {
    api_not_allowed(true);
}

$tbl_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

if (!empty($course_info['real_id'])) {
    $sql = 'SELECT * FROM '.$tbl_student_publication.'
            WHERE c_id = '.$course_info['real_id'].' AND id = "'.$id.'"';
    $result = Database::query($sql);
    if ($result && Database::num_rows($result)) {
        $row = Database::fetch_array($result, 'ASSOC');
        $full_file_name = api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'.$row['url'];

        $item_info = api_get_item_property_info(api_get_course_int_id(), 'work', $row['id']);

        allowOnlySubscribedUser(api_get_user_id(), $row['parent_id'], $course_info['real_id']);

        if (empty($item_info)) {
            api_not_allowed();
        }

        /*
        field show_score in table course :  0 => 	New documents are visible for all users
                                            1 =>    New documents are only visible for the teacher(s)
        field visibility in table item_property :   0 => eye closed, invisible for all students
                                                    1 => eye open
        field accepted in table c_student_publication : 0 => eye closed, invisible for all students
                                                        1 => eye open
        (we should have visibility == accepted , otherwise there is an inconsistency in the Database)
        field value in table c_course_setting :     0 => Allow learners to delete their own publications = NO
                                                    1 => Allow learners to delete their own publications = YES

        +------------------+------------------------------+----------------------------+
        |Can download work?|      doc visible for all = 0 |     doc visible for all = 1|
        +------------------+------------------------------+----------------------------+
        |  visibility = 0  | editor only                  | editor only                |
        |                  |                              |                            |
        +------------------+------------------------------+----------------------------+
        |  visibility = 1  | editor                       | editor                     |
        |                  | + owner of the work          | + any student              |
        +------------------+------------------------------+----------------------------+
        (editor = teacher + admin + anybody with right api_is_allowed_to_edit)
        */

        $work_is_visible = ($item_info['visibility'] == 1 && $row['accepted'] == 1);
        $doc_visible_for_all = ($course_info['show_score'] == 1);

        $is_editor = api_is_allowed_to_edit(true, true, true);
        $student_is_owner_of_work = user_is_author($row['id'], $row['user_id']);

        if ($is_editor || ($student_is_owner_of_work) || ($doc_visible_for_all && $work_is_visible)) {

            $title = $row['title'];

            if (array_key_exists('filename', $row) && !empty($row['filename'])) {
                $title = $row['filename'];
            }

            $title = str_replace(' ', '_', $title);
            event_download($title);
            if (Security::check_abs_path($full_file_name, api_get_path(SYS_COURSE_PATH).api_get_course_path().'/')) {
                if (!is_file($full_file_name)) {
                    Display::display_header(null);
                    Display::display_introduction_section(TOOL_STUDENTPUBLICATION);
                    Display::display_warning_message(get_lang('FileNotFound'));
                } else {
                    DocumentManager::file_send_for_download($full_file_name, true, $title);
                }
            }
        } else {
            api_not_allowed();
        }
    }
} else {
    api_not_allowed();
}
exit;
