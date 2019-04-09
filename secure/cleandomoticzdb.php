<?php
/**
 * Pass2PHP
 * php version 7.0.33
 *
 * @category Home_Automation
 * @package  Pass2PHP
 * @author   Guy Verschuere <guy@egregius.be>
 * @license  GNU GPLv3
 * @link     https://egregius.be
 **/
$db = new SQLite3('/domoticz/domoticz.db');
define('TIME', $_SERVER['REQUEST_TIME']);
$clean = strftime("%G-%m-%d %k:%M:%S", TIME-3600*24);
$tables = array( 'LightingLog',
                'MultiMeter',
                'MultiMeter_Calendar',
                'Meter',
                'Meter_Calendar',
                'Percentage',
                'Percentage_Calendar',
                'Rain',
                'Rain_Calendar',
                'Temperature',
                'Temperature_Calendar',
                'UV',
                'UV_Calendar',
                'Wind',
                'Wind_Calendar');
echo PHP_EOL;
foreach ($tables as $table) {
    $query=$db->exec("DELETE FROM $table WHERE DeviceRowID not in (select ID from DeviceStatus where Used = 1)");
    if ($query) {
        $rows = $db->changes();
        if ($rows>0) {
            echo $rows." rows removed from $table".PHP_EOL;
        }
    }
    sleep(2);

}
$query=$db->exec("DELETE FROM LightingLog WHERE Date < '$clean'");
if ($query) {
    $rows = $db->changes();
    if ($rows>0) {
        echo $rows." old rows removed from LightingLog".PHP_EOL;
    }
}
sleep(2);
$query=$db->exec("DELETE FROM LightingLog WHERE DeviceRowID in (6,9,10,170,171,172,173,174,175,176,177,295,296,297,298,299,300,301,302)");
if ($query) {
    $rows = $db->changes();
    if ($rows>0) {
        echo $rows." rows removed from LightingLog".PHP_EOL;
    }
}
//$sql = 'VACUUM;';
//if(!$result = $db->exec($sql))
//    die('There was an error running the query [' . $db->error . ']');
?>