<?php
namespace local_consistencyscore;

defined('MOODLE_INTERNAL') || die();

class observer {

    private static function is_student($userid) {
        global $DB;

        return $DB->record_exists_sql(
            "SELECT 1 FROM {role_assignments} ra
             JOIN {role} r ON r.id = ra.roleid
             WHERE ra.userid = ? AND r.shortname = 'student'",
            [$userid]
        );
    }

    private static function get_active_row($userid) {
        global $DB;

        if (!self::is_student($userid)) {
            return false;
        }

        return $DB->get_record_sql(
            "SELECT * FROM {local_consistency_log}
             WHERE userid = ? AND logouttime IS NULL
             ORDER BY id DESC",
            [$userid]
        );
    }

    public static function user_loggedin(\core\event\user_loggedin $event) {
        if (!self::is_student($event->userid)) {
            return;
        }

        global $DB;

        $DB->insert_record('local_consistency_log', (object)[
            'userid' => $event->userid,
            'logintime' => time(),
            'logouttime' => null,
            'notes' => null,
            'quiz' => null,
            'assignment' => null,
            'videos' => null
        ]);
    }

    public static function user_loggedout(\core\event\user_loggedout $event) {
        global $DB;

        if ($rec = self::get_active_row($event->userid)) {
            $rec->logouttime = time();
            $DB->update_record('local_consistency_log', $rec);
        }
    }

    public static function module_viewed(\core\event\course_module_viewed $event) {
        global $DB;

        $userid = $event->userid;
        if (!$rec = self::get_active_row($userid)) return;

        $cmid = $event->contextinstanceid;

        // Get module name and instance id
        $mod = $DB->get_record_sql("
            SELECT m.name AS modulename, cm.instance AS instanceid
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE cm.id = ?", [$cmid]);

        if (!$mod) return;

        // Call the proper function based on module type
        switch ($mod->modulename) {
            case 'resource':
                self::track_resource($rec, $mod->instanceid);
                break;

            case 'page':
            case 'book':
                self::track_notes_module($rec, $mod->modulename);
                break;

            case 'url':
            case 'h5pactivity':
                self::track_video_module($rec, $mod->modulename);
                break;

            case 'assign':
                self::track_assignment($rec);
                break;

            case 'quiz':
                self::track_quiz($rec);
                break;

            default:
                return;
        }

        $DB->update_record('local_consistency_log', $rec);
    }

    // Track PDFs/resources
    private static function track_resource(&$rec, $instanceid) {
        // Minimal: treat any resource as PDF → notes
        $rec->notes = 'opened';
    }

    // Track notes modules like page/book
    private static function track_notes_module(&$rec, $modulename) {
        $rec->notes = 'opened';
    }

    // Track video modules like url/h5p
    private static function track_video_module(&$rec, $modulename) {
        $rec->videos = 'opened';
    }

    // Track assignments
    private static function track_assignment(&$rec) {
        $rec->assignment = 'opened';
    }

    // Track quizzes
    private static function track_quiz(&$rec) {
        $rec->quiz = 'opened';
    }

    public static function assignment_submitted(\mod_assign\event\submission_created $event) {
        global $DB;

        if ($rec = self::get_active_row($event->userid)) {
            $rec->assignment = 'submitted';
            $DB->update_record('local_consistency_log', $rec);
        }
    }

    public static function quiz_submitted(\mod_quiz\event\attempt_submitted $event) {
        global $DB;

        if ($rec = self::get_active_row($event->userid)) {
            $rec->quiz = 'submitted';
            $DB->update_record('local_consistency_log', $rec);
        }
    }
}
