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
$PAGE->set_url($CFG->wwwroot.'/mod/jitsi/session.php');



$themeconfig = theme_config::load($CFG->theme);
$courseid = required_param('courseid', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$nombre = required_param('nom', PARAM_TEXT);
$session = required_param('ses', PARAM_TEXT);
$sessionnorm = str_replace(array(' ', ':', '"'), '', $session);
$avatar = required_param('avatar', PARAM_TEXT);
$teacher = required_param('t', PARAM_BOOL);
require_login($courseid);

$PAGE->set_title($session);
$PAGE->set_heading($session);
echo $OUTPUT->header();

$db_ip = "x.x.x.x";           //replace  
$db_port = "3306";            //these
$db_username = "user";        //parameters
$db_password = "password";    //with
$db_db_name = "jitsi_moodle"; //yours

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



$header = json_encode([
  "kid" => "jitsi/custom_key_name",
  "typ" => "JWT",
  "alg" => "HS256"
], JSON_UNESCAPED_SLASHES);
$base64urlheader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

$payload  = json_encode([
  "context" => [
  "user" => [
      "avatar" => $avatar,
      "name" => $nombre,
      "email" => "",
      "id" => ""
    ],
    "group" => ""
  ],
  "aud" => "jitsi",
  "iss" => $CFG->jitsi_app_id,
  "sub" => $CFG->jitsi_domain,
  "room" => urlencode($sessionnorm),
  "exp" => time() + 24 * 3600,
  "moderator" => $teacher

], JSON_UNESCAPED_SLASHES);
$base64urlpayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

$secret = $CFG->jitsi_secret;
$signature = hash_hmac('sha256', $base64urlheader . "." . $base64urlpayload, $secret, true);
$base64urlsignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));



//----------- i added this for re-orchestrator
$link = new mysqli($db_ip, $db_username, $db_password, $db_db_name, $db_port);
$sql = "SELECT jitsi_web_port FROM jibri_sessions where session_id='".$sessionnorm."'";
if($result = mysqli_query($link, $sql)){
    if(mysqli_num_rows($result) > 0){

        while($row = mysqli_fetch_array($result)){
            $jitsi_web_port = $row['jitsi_web_port'];
        }
        // Free result set
        mysqli_free_result($result);

    }else if((in_array('editingteacher', $rolestr) == 1) || (in_array('manager', $rolestr) == 1) || (in_array('coursecreator', $rolestr) == 1)){
        //The URL with parameters / query string.
        //     this ip and port can be diffrent web server and this query should create a jitsi deployment on your kubernetes server.
        $url = "http://your_node_js_ip_and_port/create_jitsi_deployment?param1=create&param2=".$sessionnorm;
        //Once again, we use file_get_contents to GET the URL in question.
        $contents = file_get_contents($url);
        if($contents !== false){
            //catch created deployment port.
            //Print out the contents.
            $jitsi_web_port = $contents;
        }

    }else {
        echo "<script> window.location.href='".$CFG->wwwroot."/course/view.php?id=".$courseid."'</script>;\n";
    }
} else{
    echo "ERROR: Could not able to execute $sql. " . mysqli_error($link);
}
 
// Close connection
mysqli_close($link);
//------------added end*



$jwt = $base64urlheader . "." . $base64urlpayload . "." . $base64urlsignature;
echo "<script src=\"https://".$CFG->jitsi_domain.":".$jitsi_web_port."/external_api.js\"></script>\n";

echo "<script>\n";
echo "var domain = \"".$CFG->jitsi_domain.":".$jitsi_web_port."\";\n";
echo "var options = {\n";
echo "configOverwrite: {\n";
echo "channelLastN: ".$CFG->jitsi_channellastcam.",\n";
echo "startWithAudioMuted: true,\n";
echo "startWithVideoMuted: true,\n";
echo "},\n";
echo "roomName: \"".urlencode($sessionnorm)."\",\n";

if ($CFG->jitsi_app_id != null && $CFG->jitsi_secret != null) {
    echo "jwt: \"".$jwt."\",\n";
}
if ($CFG->branch < 36) {
    if ($CFG->theme == 'boost' || in_array('boost', $themeconfig->parents)) {
        echo "parentNode: document.querySelector('#region-main .card-body'),\n";
    } else {
        echo "parentNode: document.querySelector('#region-main'),\n";
    }
} else {
    echo "parentNode: document.querySelector('#region-main'),\n";
}
$streamingoption = '';

if ($teacher == true && $CFG->jitsi_livebutton == 1) {
    $streamingoption = 'livestreaming';
}

$desktop = '';
if (has_capability('mod/jitsi:sharedesktop', $context)) {
    $desktop = 'desktop';
}

$youtubeoption = '';
if ($CFG->jitsi_shareyoutube == 1) {
    $youtubeoption = 'sharedvideo';
}

$bluroption = '';
if ($CFG->jitsi_blurbutton == 1) {
    $bluroption = 'videobackgroundblur';
}

$security = '';
if ($CFG->jitsi_securitybutton == 1) {
    $security = 'security';
}

$invite = '';
if ($CFG->jitsi_invitebuttons == 1) {
    $invite = 'invite';
}

$buttons = "['microphone', 'camera', 'closedcaptions', '".$desktop."', 'fullscreen',
        'fodeviceselection', 'hangup', 'profile', 'chat', 'recording',
        '".$streamingoption."', 'etherpad', '".$youtubeoption."', 'settings', 'raisehand',
        'videoquality', 'filmstrip', '".$invite."', 'feedback', 'stats', 'shortcuts',
        'tileview', '".$bluroption."', 'download', 'help', 'mute-everyone', '".$security."']";

echo "interfaceConfigOverwrite:{\n";
echo "TOOLBAR_BUTTONS:".$buttons.",\n";

echo "SHOW_JITSI_WATERMARK: true,\n";
echo "JITSI_WATERMARK_LINK: '".$CFG->jitsi_watermarklink."',\n";
echo "},\n";

echo "width: '100%',\n";
echo "height: 650,\n";
echo "}\n";
echo "var api = new JitsiMeetExternalAPI(domain, options);\n";
echo "api.executeCommand('displayName', '".$nombre."');\n";
echo "api.executeCommand('avatarUrl', '".$avatar."');\n";
if ($CFG->jitsi_finishandreturn == 1) {
    echo "api.on('readyToClose', () => {\n";
    echo      "api.dispose();\n";
  //echo      "location.href=\"".$CFG->wwwroot."/course/view.php?id=".$courseid."\";";
    echo      "location.href=\"".$CFG->wwwroot."/mod/jitsi/view.php?id=".$cmid."\";";
    echo  "});\n";
}


echo "</script>\n";

echo $OUTPUT->footer();
