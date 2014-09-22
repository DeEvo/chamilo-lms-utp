<?php

$language_file = array('admin', 'registration');
$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script(true);

$execute = isset($_GET['execute']) ? Database::escape_string($_GET['execute']) : null;
/*$courseCode = Database::escape_string($_GET['course_code']);
if (empty($courseCode)) {
    echo 'add the ?course_code=XXX';
    exit;
}*/

$table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$tableAttempts = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
$tableRecording = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
$tableUser= Database::get_main_table(TABLE_MAIN_USER);
$tableCourse = Database::get_main_table(TABLE_MAIN_COURSE);
$tableQuiz = Database::get_course_table(TABLE_QUIZ_TEST);
$tableLpItemView = Database::get_course_table(TABLE_LP_ITEM_VIEW);

$sql = "SELECT * FROM $table WHERE status = '' AND exe_result = '0' ";
$result = Database::query($sql);
$www = api_get_path(WEB_CODE_PATH);

if ($execute) {
    echo 'Database will be modified.';
} else {
    echo 'No changes will be modified in the Database. If you want to execute the queries add this ?execute=1';
}
echo '<br />';

echo "<h2>Attempts where status is ='' and exe_result = 0 </h2><br />";

if (Database::num_rows($result)) {
//if (0) {

    while ($attempt = Database::fetch_array($result, 'ASSOC')) {

        $exeId = $attempt['exe_id'];
        $courseCode = $attempt['exe_cours_id'];
        $sessionId = $attempt['session_id'];
        $lpItemViewId = $attempt['orig_lp_item_view_id'];
        $lpItemId = $attempt['orig_lp_item_id'];

        $courseInfo = api_get_course_info($courseCode);
        $courseId = $courseInfo['real_id'];

        // Check if the exercise was reviewed by a teacher:

        $sql = "SELECT count(*) count_result
                FROM $tableRecording WHERE exe_id = $exeId ";

        $recordingResult = Database::query($sql);
        $recordings = Database::fetch_array($recordingResult, 'ASSOC');
        $recordings = $recordings['count_result'];

        // The attempt was not review by the teacher, you can modify the total
        if ($recordings == 0) {

            $questionAttempts = get_all_exercise_event_by_exe_id(
                $attempt['exe_id']
            );

            $total = 0;
            foreach ($questionAttempts as $results) {
                foreach ($results as $questionResult) {
                    $total += $questionResult['marks'];
                }
            }

            if ($total != 0) {
                echo "Attempt #$exeId<br />";
                echo 'Ready to update track_e_exercise with new score: ' . $total . '<br />';
                $sql = " UPDATE $table SET exe_result = '$total' WHERE exe_id = $exeId";
                echo $sql;
                echo '<br />';
                if ($execute) {
                    Database::query($sql);
                }

                // Updating lp_item_view

                if (!empty($lpItemViewId)) {
                    echo 'Ready to update lp_item_view with new score: ' . $total . '<br />';
                    $sql = "UPDATE $tableLpItemView
                            SET score = '$total'
                            WHERE
                              id = $lpItemViewId AND
                              c_id = $courseId AND
                              lp_item_id = $lpItemId";
                    echo $sql;
                    echo '<br />';
                    if ($execute) {
                        Database::query($sql);
                    }
                }

                echo '<br />';
                $url = $www . "exercice/exercise_show.php?cidReq=$courseCode&id_session=$sessionId&gidReq=0&action=qualify&id=$exeId";
                echo "See exercise result: " . Display::url($url, $url);
                echo '<br />';
                echo '<br />';
            }
        }
    }
} else {
    echo "Nothing to fix.<br />";
}

$sql = "SELECT
            COUNT(*) as count,
            start_time,
            course.id course_id,
            exe_user_id,
            user.firstname,
            user.lastname,
            exe_exo_id,
            track_e_exercices.session_id,
            exe_cours_id,
            orig_lp_id,
            orig_lp_item_id,
            orig_lp_item_view_id
        FROM $table track_e_exercices
            INNER JOIN $tableUser user
            ON user.user_id = exe_user_id AND user.status = 5
            INNER JOIN $tableCourse course
            ON course.code = exe_cours_id
            INNER JOIN $tableExercise c_quiz
            ON c_quiz.id = exe_exo_id  AND c_quiz.c_id = course.id
        WHERE
          track_e_exercices.status = '' AND
          c_quiz.max_attempt = 1
        GROUP BY exe_user_id, c_quiz.id, track_e_exercices.session_id, exe_cours_id
        HAVING COUNT(*) > 1
        ORDER BY exe_cours_id";

$result = Database::query($sql);
if (Database::num_rows($result)) {
    echo "<br /><h2>Double students attempts in exercises with only 1 attempt setting.</h2><br />";
    while ($attempt = Database::fetch_array($result, 'ASSOC')) {

        $userId = $attempt['exe_user_id'];
        $courseCode = $attempt['exe_cours_id'];
        $exerciseId = $attempt['exe_exo_id'];
        $sessionId = $attempt['session_id'];
        $user = $attempt["firstname"].' '.$attempt['lastname'];

        $courseInfo = api_get_course_info($courseCode);
        $courseId = $courseInfo['real_id'];

        $origLpId = $attempt['orig_lp_id'];
        $origLpItemId = $attempt['orig_lp_item_id'];
        $origLpItemViewId = $attempt['orig_lp_item_view_id'];
        $lpItemId = $attempt['orig_lp_item_id'];

        $url = $www . "exercice/exercise_report.php?cidReq=$courseCode&exerciseId=$exerciseId&id_session=$sessionId";
        echo "<br />Search the correct attempt for user '$user' here: <br /> " . Display::url($url, $url).'<br />';

        $userResults = get_all_exercise_results_by_user_by_exercise(
            $exerciseId,
            $userId,
            $courseCode,
            $sessionId,
            $origLpId,
            $origLpItemId,
            $origLpItemViewId
        );

        $counter = 0;
        $bestAttempt = 0;
        $maxScore = 0;
        $scores = array();

        foreach ($userResults as $userResult) {
            $exeId = $userResult['exe_id'];
            $score = $userResult['exe_result'];
            $counter++;

            // Skipping first attempt.
            if ($counter == 1) {
                echo "Keeping only attempt: $exeId with score: '$score'<br />";
                // Updating LP just in case.
                if (!empty($origLpItemViewId)) {
                    echo 'Ready to update lp_item_view with new score: ' . $score . '<br />';
                    $sql = "UPDATE $tableLpItemView SET score = '$score'
                            WHERE
                                  id = $origLpItemViewId AND
                                  c_id = $courseId AND
                                  lp_item_id = $origLpItemId";
                    echo $sql;
                    echo '<br />';
                    if ($execute) {
                        Database::query($sql);
                    }
                }
                continue;
            } else {
                echo "Removing attempt #$exeId after the first with score: $score<br />";
                // Removing everything else.
                $sql1 = 'DELETE FROM ' . $table . ' WHERE exe_id = ' . $exeId;
                $sql2 = 'DELETE FROM ' . $tableRecording . ' WHERE exe_id = ' . $exeId;
                $sql3 = 'DELETE FROM ' . $tableAttempts . ' WHERE exe_id = ' . $exeId;
                var_dump($sql1, $sql2, $sql3);

                if ($execute) {
                    Database::query($sql1);
                    Database::query($sql2);
                    Database::query($sql3);
                }
            }
        }


        /*foreach ($userResults as $userResult) {
            $exeId = $userResult['exe_id'];
            $score = $userResult['exe_result'];

            $scores[$exeId] = $score;

            if ($score > $maxScore) {
                $maxScore = $score;
                $bestAttempt = $exeId;
            }
        }

        if (!empty($bestAttempt)) {

            echo "Best attempt of #$bestAttempt with score $maxScore. Other attempts will be removed.<br />";
            echo "All options: <pre>" . print_r($scores, 1) . '</pre><br />';

            foreach ($userResults as $userResult) {
                $exeId = $userResult['exe_id'];
                if ($exeId != $bestAttempt) {

                    $sql1 = 'DELETE FROM ' . $table . ' WHERE exe_id = ' . $exeId;
                    $sql2 = 'DELETE FROM ' . $tableRecording . ' WHERE exe_id = ' . $exeId;
                    $sql3 = 'DELETE FROM ' . $tableAttempts . ' WHERE exe_id = ' . $exeId;
                    var_dump($sql1, $sql2, $sql3);

                    if ($execute) {
                        Database::query($sql1);
                        Database::query($sql2);
                        Database::query($sql3);
                    }
                }
            }

            // Updating lp_item_view
            if (!empty($origLpItemViewId)) {
                echo 'Ready to update lp_item_view with new score: ' . $maxScore . '<br />';
                $sql = "UPDATE $tableLpItemView
                        SET score = '$maxScore'
                        WHERE
                          id = $origLpItemViewId AND
                          c_id = $courseId AND
                          lp_item_id = $origLpItemId";
                echo $sql;
                echo '<br />';
                if ($execute) {
                    Database::query($sql);
                }
            }
        }*/


    }
}
