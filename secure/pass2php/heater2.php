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
if ($status=='On') {
    if ($d['heater1']['s']!='On') {
        sw('heater1', 'On');
    }
}