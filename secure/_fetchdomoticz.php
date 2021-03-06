<?php
/**
 * Pass2PHP
 * php version 7.3.3-1
 *
 * @category Home_Automation
 * @package  Pass2PHP
 * @author   Guy Verschuere <guy@egregius.be>
 * @license  GNU GPLv3
 * @link     https://egregius.be
 **/
$domoticz=json_decode(file_get_contents($domoticzurl.'/json.htm?type=devices&used=true'), true);
if ($domoticz) {
    foreach ($domoticz['result'] as $dom) {
        $name=$dom['Name'];
        $idx=$dom['idx'];
        if (isset($dom['SwitchType'])) {
            $switchtype=$dom['SwitchType'];
        } else {
            $switchtype='none';
        }
        if ($dom['Type']=='Temp') {
            $status=$dom['Temp'];
        } elseif ($dom['Type']=='Temp + Humidity') {
            $status=$dom['Temp'];
        } elseif ($dom['TypeImg']=='current') {
            $status=str_replace(' Watt', '', $dom['Data']);
        } elseif ($name=='luifel') {
            $status=str_replace('%', '', $dom['Level']);
        } elseif ($switchtype=='Dimmer') {
            if ($dom['Data']=='Off') {
                $status=0;
            } elseif ($dom['Data']=='On') {
                $status=100;
            } else {
                $status=filter_var($dom['Data'], FILTER_SANITIZE_NUMBER_INT);
            }
        } elseif ($switchtype=='Blinds Percentage') {
            if ($dom['Data']=='Open') {
                $status=0;
            } elseif ($dom['Data']=='Closed') {
                $status=100;
            } else {
                $status=filter_var($dom['Data'], FILTER_SANITIZE_NUMBER_INT);
            }
        } else {
            $status=$dom['Data'];
        }
        store($name, $status, $idx, false);
    }
}