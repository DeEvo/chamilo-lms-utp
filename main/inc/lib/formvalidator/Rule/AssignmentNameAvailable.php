<?php
/* For licensing terms, see /license.txt */
/**
 * Rule to check the assignments names and avoid register duplicated names
 */
require_once 'HTML/QuickForm/Rule.php';

/**
 * QuickForm rule to check if a assignment name is available
 */
class HTML_QuickForm_Rule_AssignmenNameAvailable extends HTML_QuickForm_Rule
{

    /**
     * Validate if a assignment name is available
     * @see HTML_QuickForm_Rule
     * @param string $title Wanted The assignment name
     * @param array $options Optional. The current assignment name and id
     * @return boolean True if the assignment name is available
     */
    function validate($title, $options = array())
    {
        $workTable = Database::get_course_table(TABLE_STUDENT_PUBLICATION);

        $title = Database::escape_string($title);

        $sessionId = api_get_session_id();
        $courseId = api_get_course_int_id();

        $sql = "SELECT * FROM $workTable WHERE title = '$title' "
            . "AND c_id = $courseId "
            . "AND session_id in ($sessionId, 0) ";

        if (!empty($options)) {
            $sql .= "AND title != '{$options['title']}' "
            . "AND id != {$options['id']}";
        }

        $res = Database::query($sql);

        return Database::num_rows($res) == 0;
    }

}
