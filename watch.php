<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a particular instance of jitsi
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_jitsi
 * @copyright  2020 Muaz Dervent <muazdervent@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $USER;


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(dirname(dirname(__FILE__))).'/lib/moodlelib.php');
require_once(dirname(__FILE__).'/lib.php');
$PAGE->set_url($CFG->wwwroot.'/mod/jitsi/watch.php');



$themeconfig = theme_config::load($CFG->theme);
$courseid = required_param('courseid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$nombre = required_param('nom', PARAM_TEXT);
$session = required_param('ses', PARAM_TEXT);
$sessionnorm = str_replace(array(' ', ':', '"'), '', $session);
$avatar = required_param('avatar', PARAM_TEXT);
$teacher = required_param('t', PARAM_BOOL);
$vid = required_param('vid', PARAM_TEXT);
require_login($courseid);

$PAGE->set_title($session);
$PAGE->set_heading($session);
echo $OUTPUT->header();

if ($teacher == 1) {
      $teacher = true;
} else {
      $teacher = false;
}

$context = context_module::instance($cmid);

if (!has_capability('mod/jitsi:view', $context)) {
    notice(get_string('noviewpermission', 'jitsi'));
}

//son eklenen

$roles = get_user_roles($context, $USER->id);

$rolestr[] = null;
foreach ($roles as $role) {
    $rolestr[] = $role->shortname;
} //son eklenene

echo "<br>";
echo "<video width='100%' height='100%' controls controlsList='nodownload'>";
echo "<source src='*****your_video_records_path_or_url*****/".$vid."' type='video/mp4'>";
echo "</video>";

echo $OUTPUT->footer();