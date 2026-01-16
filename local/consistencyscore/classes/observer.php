<?php
namespace local_consistencyscore;

defined('MOODLE_INTERNAL') || die();

class observer {


    // Check if user is a student
    private static function is_student($userid) {
        global $DB;
        return $DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON r.id = ra.roleid
             WHERE ra.userid = ? AND r.shortname = 'student'",
            [$userid]
        );
    }

    // Get latest active record for student
    private static function get_latest_record($userid) {
        global $DB;
        if (!self::is_student($userid)) {
            return false;
        }
        return $DB->get_record_sql(
            "SELECT * FROM {local_consistency_log}
             WHERE userid = ?
             ORDER BY id DESC",
            [$userid],
            IGNORE_MISSING
        );
    }

    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $DB;

        if (!self::is_student($event->userid)) {
            return;
        }

        $DB->insert_record('local_consistency_log', (object)[
            'userid' => $event->userid,
            'logintime' => time(),
            'logouttime' => null,
            'notes' => null,
            'videos' => null,
            'quiz' => null,
            'assignment' => null
        ]);
    }

    public static function user_loggedout(\core\event\user_loggedout $event) {
        global $DB;

        if ($rec = self::get_latest_record($event->userid)) {
            $rec->logouttime = time();
            $DB->update_record('local_consistency_log', $rec);
        }
    }

    public static function module_viewed(\core\event\course_module_viewed $event) {
    global $DB;

    if (!$rec = self::get_latest_record($event->userid)) {
        return;
    }

    $cm = get_coursemodule_from_id(
        null,
        $event->contextinstanceid,
        0,
        false,
        MUST_EXIST
    );

    // NOTES: page & book
    if ($cm->modname === 'page' || $cm->modname === 'book') {
        $rec->notes = 'opened';
    }

    // VIDEOS: resource with MP4
    if ($cm->modname === 'resource') {

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $event->contextid,   // ✅ THIS IS THE FIX
            'mod_resource',
            'content',
            0,
            '',
            false
        );

        foreach ($files as $file) {
            if (strpos($file->get_mimetype(), 'video/') === 0) {
                $rec->videos = 'opened';
                break;
            }
        }
    }

    // URL & H5P → video
    if ($cm->modname === 'url' || $cm->modname === 'h5pactivity') {
        $rec->videos = 'opened';
    }

    $DB->update_record('local_consistency_log', $rec);
}

    // Quiz page opened
    public static function quiz_viewed(\mod_quiz\event\course_module_viewed $event) {
        global $DB;

        if ($rec = self::get_latest_record($event->userid)) {
            $rec->quiz = 'opened';
            $DB->update_record('local_consistency_log', $rec);
        }
    }

    // Quiz submitted (Submit all and finish)
    public static function quiz_submitted(\mod_quiz\event\attempt_submitted $event) {
        global $DB;

       
        if ($rec = self::get_latest_record($event->userid)) {
            $rec->quiz = 'submitted';
            $DB->update_record('local_consistency_log', $rec);
        }
    }

    /* ================= ASSIGNMENT ================= */

    // Assignment page opened
    public static function assign_viewed(\mod_assign\event\course_module_viewed $event) {
    global $DB;

    if ($rec = self::get_latest_record($event->userid)) {

        // Do NOT override submitted state
        if ($rec->assignment !== 'submitted') {
            $rec->assignment = 'opened';
            $DB->update_record('local_consistency_log', $rec);
        }
    }
}


    // Assignment submitted (first time)
    public static function assign_submitted(\mod_assign\event\submission_created $event) {
        self::mark_assignment_submitted($event->userid);
    }

    // Assignment resubmitted / edited
    public static function assign_updated(\mod_assign\event\submission_updated $event) {
        self::mark_assignment_submitted($event->userid);
    }

    private static function mark_assignment_submitted($userid) {
        global $DB;

        if ($rec = self::get_latest_record($userid)) {
            $rec->assignment = 'submitted';
            $DB->update_record('local_consistency_log', $rec);
        }
    }
}