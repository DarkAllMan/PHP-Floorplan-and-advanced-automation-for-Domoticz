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
if ($status=='Off') {
    if ($d['heater2']['s']!='Off') {
        sw('heater2', 'Off');
    }
    if ($d['heater3']['s']!='Off') {
        sw('heater3', 'Off');
    }
    if ($d['heater4']['s']!='Off') {
        sw('heater4', 'Off');
    }
}