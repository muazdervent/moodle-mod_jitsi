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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

global $USER;

$id = optional_param('id', 0, PARAM_INT);
$n  = optional_param('n', 0, PARAM_INT);
if ($id) {
    $cm = get_coursemodule_from_id('jitsi', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $jitsi = $DB->get_record('jitsi', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $jitsi  = $DB->get_record('jitsi', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $jitsi->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('jitsi', $jitsi->id, $course->id, false, MUST_EXIST);
} else {
    print_error('missingparam');
}
require_login($course, true, $cm);
$event = \mod_jitsi\event\course_module_viewed::create(array(
  'objectid' => $PAGE->cm->instance,
  'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $jitsi);
$event->trigger();
$PAGE->set_url('/mod/jitsi/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($jitsi->name));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();
echo $OUTPUT->heading($jitsi->name);
$context = context_module::instance($cm->id);
if (!has_capability('mod/jitsi:view', $context)) {
    notice(get_string('noviewpermission', 'jitsi'));
}
$courseid = $course->id;
$context = context_course::instance($courseid);

$roles = get_user_roles($context, $USER->id);

$rolestr[] = null;
foreach ($roles as $role) {
    $rolestr[] = $role->shortname;
}
if ($jitsi->intro) {
    echo $OUTPUT->box(format_module_intro('jitsi', $jitsi, $cm->id), 'generalbox mod_introbox', 'jitsiintro');
}

$moderation = false;
if (has_capability('mod/jitsi:moderation', $context)) {
    $moderation = true;
}

$nom = null;
switch ($CFG->jitsi_id) {
    case 'username':
        $nom = $USER->username;
        break;
    case 'nameandsurname':
        $nom = $USER->firstname.' '.$USER->lastname;
        break;
    case 'alias':
        break;
}
$sessionoptionsparam = ['$course->shortname', '$jitsi->id', '$jitsi->name'];
$fieldssessionname = $CFG->jitsi_sesionname;

$allowed = explode(',', $fieldssessionname);
$max = count($allowed);

$sesparam = '';
$optionsseparator = ['.', '-', '_', ''];
for ($i = 0; $i < $max; $i++) {
    if ($i != $max - 1) {
        if ($allowed[$i] == 0) {
            $sesparam .= string_sanitize($course->shortname).$optionsseparator[$CFG->jitsi_separator];
        } else if ($allowed[$i] == 1) {
            $sesparam .= $jitsi->id.$optionsseparator[$CFG->jitsi_separator];
        } else if ($allowed[$i] == 2) {
            $sesparam .= string_sanitize($jitsi->name).$optionsseparator[$CFG->jitsi_separator];
        }
    } else {
        if ($allowed[$i] == 0) {
            $sesparam .= string_sanitize($course->shortname);
        } else if ($allowed[$i] == 1) {
            $sesparam .= $jitsi->id;
        } else if ($allowed[$i] == 2) {
            $sesparam .= string_sanitize($jitsi->name);
        }
    }
}

$avatar = $CFG->wwwroot.'/user/pix.php/'.$USER->id.'/f1.jpg';
$urlparams = array('avatar' => $avatar, 'nom' => $nom, 'ses' => $sesparam,
    'courseid' => $course->id, 'cmid' => $id, 't' => $moderation);


$db_ip = "x.x.x.x";           //replace  
$db_port = "3306";            //these
$db_username = "user";        //parameters
$db_password = "password";    //with
$db_db_name = "jitsi_moodle"; //yours

$sessionnorm = str_replace(array(' ', ':', '"'), '', $sesparam);
$sessionnorm = str_replace(array('Ğ', 'ğ','İ','ı','ü','Ü','ç','Ç','ö','Ö','ş','Ş'), array('G','g','I','i','u','U','c','C','o','O','s','S'), $sessionnorm);


if ( (in_array('editingteacher', $rolestr) == 1) || (in_array('manager', $rolestr) == 1) || (in_array('coursecreator', $rolestr) == 1) ) {
    echo $OUTPUT->box(get_string('instruction', 'jitsi'));
    echo $OUTPUT->single_button(new moodle_url('/mod/jitsi/session.php', $urlparams), get_string('access', 'jitsi'), 'post');
}else{
	$link = new mysqli($db_ip, $db_username, $db_password, $db_db_name, $db_port);
	  
	 	 $sql = "SELECT jitsi_web_port FROM jibri_sessions where session_id='".$sessionnorm."'";
	 	 if($result = mysqli_query($link, $sql)){
	 	   if(mysqli_num_rows($result) > 0){
                echo $OUTPUT->box(get_string('instruction', 'jitsi'));
                echo $OUTPUT->single_button(new moodle_url('/mod/jitsi/session.php', $urlparams), get_string('access', 'jitsi'), 'post');
	            
	 	    } else{
	 	       echo "<br><td> Meeting not found! Please wait for the instructor to start the meeting! </td>";
            }
            mysqli_free_result($result);
		} else{
		   // echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
		    echo "Database connection ERROR! Code:321 .Report the code to the system administrator if the error occurs again.";
		}
		 
		// Close connection
		mysqli_close($link);
}



if ( in_array('editingteacher', $rolestr) == 1 || (in_array('manager', $rolestr) == 1) || (in_array('coursecreator', $rolestr) == 1) ){
    
    $link = new mysqli($db_ip, $db_username, $db_password, $db_db_name, $db_port);
	  
	 	 $sql = "SELECT jitsi_web_port FROM jibri_sessions where session_id='".$sessionnorm."'";
	 	 if($result = mysqli_query($link, $sql)){
	 	   if(mysqli_num_rows($result) > 0){
            echo '<form method="post">';
            echo '<input type="submit" name="buttonsp" id="buttonsp" value="Finish Meeting" /><br/>';
            echo '</form>';
	            
	 	    }
            mysqli_free_result($result);
		} else{
		   // echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
           echo "Database connection ERROR! Code:321 .Report the code to the system administrator if the error occurs again.";
		}
		 
		// Close connection
		mysqli_close($link);

	if(isset($_POST['buttonsp'])){
        	
		$link = new mysqli($db_ip, $db_username, $db_password, $db_db_name, $db_port);
	  
        $sql = "SELECT jitsi_web_port FROM jibri_sessions where session_id='".$sessionnorm."'";
	 	if($result = mysqli_query($link, $sql)){
	 	   if(mysqli_num_rows($result) > 0){
	 	       while($row = mysqli_fetch_array($result)){
				//The URL with parameters / query string.
		        $url = "http://*******your_node_js_ip_and_port******/destroy_jitsi_deployment?param1=destroy&param2=".$row['session_web_port'];
		        //Once again, we use file_get_contents to GET the URL in question.
		        $contents = file_get_contents($url);

		        //If $contents is not a boolean FALSE value.
		        if($contents !== "unavaible"){
		             //Print out the contents.
		             echo "Toplantı başarı ile bitirildi.";
		        }

	 	       }
	        // Free result set
	        mysqli_free_result($result);
	 	   } else{
            echo "<br><td> Meeting not found! Please wait for the instructor to start the meeting! </td>";
	 	   }
		} else{
		   // echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
		    echo "Database connection ERROR: 321";
		}
		 
		// Close connection
		mysqli_close($link);
	
	}
	
}




$link = new mysqli($db_ip, $db_username, $db_password, $db_db_name, $db_port);
$rec_ids = array();
$sql = "SELECT record_id, video_record_path, create_time FROM jitsi_conference_records where session_id='".$sessionnorm."'";
if($result = mysqli_query($link, $sql)){
    echo "<table><tr><th>Video Records</th></tr>";
    if(mysqli_num_rows($result) > 0){
	    $counter=1;
        while($row = mysqli_fetch_array($result)){
            echo "<tr><td>";
            $rec_ids[]=$row['record_id'];
            $urlparams1 = array('avatar' => $avatar, 'nom' => $nom, 'ses' => $sesparam,
                'courseid' => $course->id, 'cmid' => $id, 't' => $moderation, 'vid' => $row['record_path']);
            echo $OUTPUT->single_button(new moodle_url('/mod/jitsi/watch.php', $urlparams1), "Ders Kaydi - ".$sayac." | ".date('d.m.Y H:i', strtotime($row['create_time'])), 'post');
            if ( (in_array('editingteacher', $rolestr) == 1) || (in_array('manager', $rolestr) == 1) || (in_array('coursecreator', $rolestr) == 1) ) {
                    echo "</td><td>";
                    echo '<form method="post" onsubmit="return confirm(\'Are you sure to delete?\');">';
                    echo '<input type="submit" name="'.$row['record_id'].'" id="'.$row['record_id'].'" value="Delete record" /><br/>';
                    echo '</form>';
                    echo "</td></tr>";
            }else{
            echo "</td></tr>";
            }
            $sayac=$sayac + 1;  
        }     
        mysqli_free_result($result);
    } else{
        echo "<br><td> Record not found! </td>";
    }
    echo "</table>"
} else{
   // echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
    echo "Database connection ERROR: 321";
}
 
// Close connection
mysqli_close($link);

echo $CFG->jitsi_help;
echo $OUTPUT->footer();

if ( (in_array('editingteacher', $rolestr) == 1) || (in_array('manager', $rolestr) == 1) || (in_array('coursecreator', $rolestr) == 1) ) {

    foreach($rec_ids as &$i){
            if(isset($_POST[$i] ) ){
               $url = "your_url_web_service/jitsi_delete_video_record?param1=delete_record&param2=".$i;
               //Once again, we use file_get_contents to GET the URL in question.
               $contents = file_get_contents($url);
               header("Refresh:0"); 
               echo "Result: ".$contents;
        }

    }
}


/**
 * Sanitize strings
 * @param $string - The string to sanitize.
 * @param $forcelowercase - Force the string to lowercase?
 * @param $anal - If set to *true*, will remove all non-alphanumeric characters.
 */
function string_sanitize($string, $forcelowercase = true, $anal = false) {
    $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")",
            "_", "=", "+", "[", "{", "]", "}", "\\", "|", ";", ":", "\"",
            "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
            "â€”", "â€“", ",", "<", ".", ">", "/", "?");
    $clean = trim(str_replace($strip, "", strip_tags($string)));
    $clean = preg_replace('/\s+/', "-", $clean);
    $clean = ($anal) ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean;
    return ($forcelowercase) ?
        (function_exists('mb_strtolower')) ?
            mb_strtolower($clean, 'UTF-8') :
            strtolower($clean) :
        $clean;
}
