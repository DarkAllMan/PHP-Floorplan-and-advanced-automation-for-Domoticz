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
$start=microtime(true);
require 'secure/settings.php';
if ($home) {
    session_start();
    $_SESSION['referer']='floorplan.media.php';
    $ctx=stream_context_create(array('http'=>array('timeout'=>2)));
    echo '<html>
    <head>
		<title>Media</title>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8"/>
		<meta name="HandheldFriendly" content="true"/>
		<meta name="mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">';
	if ($udevice=='iPhone') {
	    echo '
		<meta name="viewport" content="width=device-width,height=device-height,initial-scale=0.655,user-scalable=yes,minimal-ui"/>';
	} elseif ($udevice=='iPad') {
	    echo '
		<meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.2,user-scalable=yes,minimal-ui"/>';
	}
	echo '
		<link rel="icon" type="image/png" href="images/media.png"/>
		<link rel="shortcut icon" href="images/media.png"/>
		<link rel="apple-touch-startup-image" href="images/media.png"/>
		<link rel="apple-touch-icon" href="images/media.png"/>
		<link rel="stylesheet" type="text/css" href="/styles/floorplan.php?v=5">
		<style>
			.btn{height:64px;}
			.input{width:78px;}
		</style>
	</head>';
    if (isset($_POST['Naam'])&&!isset($_POST['dimmer'])) {
        if ($_POST['Naam']=='lgtv') {
            if ($_POST['Actie']=='On') {
                shell_exec('python3 secure/lgtv.py -c on -a '.$lgtvmac.' '.$lgtvip);
            } else {
                shell_exec('python3 secure/lgtv.py -c off '.$lgtvip);
            }
        } else {
            sw($_POST['Naam'], $_POST['Actie']);
        }
        usleep(100000);
        header("Location: floorplan.media.php");
        die("Redirecting to: floorplan.media.php");
    } elseif (isset($_POST['dimmer'])) {
        if (isset($_POST['dimlevelon_x'])) {
            sl($_POST['Naam'], 100);
        } elseif (isset($_POST['dimleveloff_x'])) {
            sl($_POST['Naam'], 0);
        } else {
            sl($_POST['Naam'], $_POST['dimlevel']);
            store('dimaction'.$_POST['Naam'], 0);
        }
        usleep(100000);
        header("Location: floorplan.media.php");
        die("Redirecting to: floorplan.media.php");
    } elseif (isset($_REQUEST['nas'])) {
        if ($_REQUEST['nas']=='sleep') {
            shell_exec('secure/sleepnas.sh');
        } elseif ($_REQUEST['nas']=='wake') {
            shell_exec('secure/wakenas.sh');
        }
        header("Location: floorplan.media.php");
        die("Redirecting to: floorplan.media.php");
    } elseif (isset($_POST['Scene'])) {
        if ($_POST['Scene']=='TUNER') {
            ud('miniliving1s', 0, 'On');
        } elseif ($_POST['Scene']=='TV') {
            ud('miniliving1l', 0, 'On');
        } elseif ($_POST['Scene']=='SHIELD') {
            ud('miniliving2l', 0, 'On');
        } elseif ($_POST['Scene']=='UIT') {
            if ($d['nvidia']['s']!='Off') {
                   @kodi('{"jsonrpc":"2.0","id":1,"method":"System.Shutdown"}');
            }
            ud('miniliving4l', 0, 'On');
        }
        usleep(100000);
        header("Location: floorplan.media.php");
        die("Redirecting to: floorplan.media.php");
    } elseif (isset($_POST['vol'])) {
        @file_get_contents('http://'.$denonip.'/MainZone/index.put.asp?cmd0=PutMasterVolumeSet/-'.number_format($_POST['vol'], 0).'.0');
        usleep(120000);
    } elseif (isset($_POST['input'])) {
        @file_get_contents('http://'.$denonip.'/MainZone/index.put.asp?cmd0=PutZone_InputFunction/'.$_POST['input'].'&cmd1=aspMainZone_WebUpdateStatus%2F');
        storemode('denon', $_POST['input']);
        usleep(120000);
    } elseif (isset($_POST['surround'])) {
        @file_get_contents('http://'.$denonip.'/MainZone/index.put.asp?cmd0=PutSurroundMode/'.$_POST['surround'].'&cmd1=aspMainZone_WebUpdateStatus%2F');
        usleep(120000);
    } elseif (isset($_POST['UpdateKodi'])) {
        $profile=$_POST['UpdateKodi'];
        //echo 'Wanted profile='.$profile.'<br/>';
        profile:
        $loadedprofile=json_decode(@file_get_contents($kodiurl.'/jsonrpc?request={"jsonrpc":"2.0","id":"1","method":"Profiles.GetCurrentProfile","id":1}', false, $ctx), true);
        //echo 'loadedprofile='.$loadedprofile['result']['label'].'<br/>';
        if ($loadedprofile['result']['label']!==$profile) {
            @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.Stop","params":{"playerid":1}}');
            usleep(10000);
            $profilereply=@kodi('{"jsonrpc":"2.0","id":"1","method":"Profiles.LoadProfile","params":{"profile":"'.$profile.'"},"id":1}');
            //echo 'profilereply='.$profilereply.'</pre><br/>';
            $count=$count + 1;
            if ($count>10) {
                die('Die Endless loop');
            }
            sleep(3);
            goto profile;
        } else {
            @kodi('{"jsonrpc":"2.0","id":1,"method":"Videolibrary.Scan"}');
        }
    } elseif (isset($_POST['CleanKodi'])) {
        @kodi('{"jsonrpc":"2.0","id":1,"method":"Videolibrary.Clean"}');
    } elseif (isset($_POST['PauseKodi'])) {
        @file_get_contents($domoticzurl.'/json.htm?type=command&param=udevice&idx='.idx('miniliving2s').'&nvalue=0&svalue=On');
    } elseif (isset($_POST['StopKodi'])) {
        @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.Stop","params":{"playerid":1}}');
    } elseif (isset($_POST['bigbackward'])) {
        @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.Seek","params":{"playerid":1,"value":"bigbackward"}}');
    } elseif (isset($_POST['smallbackward'])) {
        @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.Seek","params":{"playerid":1,"value":"smallbackward"}}');
    } elseif (isset($_POST['smallforward'])) {
        @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.Seek","params":{"playerid":1,"value":"smallforward"}}');
    } elseif (isset($_POST['bigforward'])) {
        @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.Seek","params":{"playerid":1,"value":"bigforward"}}');
    } elseif (isset($_POST['audio'])) {
        @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.SetAudioStream","params":{"playerid":1,"stream":'.$_POST['audio'].'}}');
    } elseif (isset($_POST['subtitle'])) {
        if ($_POST['subtitle']=='disable') {
            @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.SetSubtitle","params":{"playerid":1,"subtitle":"off"}}');
        } elseif ($_POST['subtitle']=='enable') {
            @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.SetSubtitle","params":{"playerid":1,"subtitle":"on"}}');
        } else {
            @kodi('{"jsonrpc":"2.0","id":1,"method":"Player.SetSubtitle","params":{"playerid":1,"subtitle":'.$_POST['subtitle'].'}}');
        }
    } elseif (isset($_POST['PowerOff'])) {
        @kodi('{"jsonrpc":"2.0","id":1,"method":"System.Shutdown"}');
    } elseif (isset($_REQUEST['setdimmer'])) {
        $name=$_REQUEST['setdimmer'];
        $stat=$d[$name]['s'];
        $dimaction=$d[$name]['m'];
        echo '<div id="D'.$name.'" class="fix dimmer" >
		<form method="POST" action="floorplan.media.php" oninput="level.value = dimlevel.valueAsNumber">
				<div class="fix z" style="top:15px;left:90px;">';
        if ($stat=='Off') {
            echo '<h2>'.ucwords($name).': Off</h2>';
        } else {
            echo '<h2>'.ucwords($name).': '.$stat.'%</h2>';
        }
        echo '
					<input type="hidden" name="Naam" value="'.$name.'">
					<input type="hidden" name="dimmer" value="true">
				</div>
				<div class="fix z" style="top:100px;left:30px;">
					<input type="image" name="dimleveloff" value ="0" src="images/Light_Off.png" class="i90"/>
				</div>
				<div class="fix z" style="top:100px;left:150px;">
					<input type="image" name="dimsleep" value ="100" src="images/Sleepy.png" class="i90"/>';
        if ($dimaction==1) {
            echo '<div class="fix" style="top:0px;left:0px;z-index:-100;background:#ffba00;width:90px;height:90px;border-radius:45px;"></div>';
        }
        echo '
				</div>
				<div class="fix z" style="top:100px;left:265px;">
					<input type="image" name="dimwake" value="100" src="images/Wakeup.png" style="height:90px;width:90px"/>';
        if ($dimaction==2) {
            echo '<div class="fix" style="top:0px;left:0px;z-index:-100;background: #ffba00;width:90px;height:90px;border-radius:45px;"></div>';
        }
        echo '
					<input type="hidden" name="dimwakelevel" value="'.$stat.'">
				</div>';
        echo '
				<div class="fix z" style="top:100px;left:385px;">
					<input type="image" name="dimlevelon" value ="100" src="images/Light_On.png" class="i90"/>
				</div>
				<div class="fix z" style="top:210px;left:10px;">';

        $levels=array(1,2,3,4,5,6,7,8,9,10,12,14,16,18,20,22,24,26,28,30,32,35,40,45,50,55,60,65,70,75,80,85,90,95,100);
        if ($stat!=0&&$stat!=100) {
            if (!in_array($stat, $levels)) {
                $levels[]=$stat;
            }
        }
        asort($levels);
        $levels=array_slice($levels, 0, 35);
        foreach ($levels as $level) {
            if ($stat!='Off'&&$stat==$level) {
                echo '<input type="submit" name="dimlevel" value="'.$level.'"/ class="dimlevel dimlevela">';
            } else {
                echo '<input type="submit" name="dimlevel" value="'.$level.'" class="dimlevel"/>';
            }
        }
        echo '
				</div>
			</form>
			<div class="fix z" style="top:5px;left:5px;"><a href=\'javascript:navigator_Go("floorplan.media.php");\'><img src="/images/close.png" width="72px" height="72px"/></a></div>
		</div>
	</body>
	<script type="text/javascript">function navigator_Go(url){window.location.assign(url);}</script>
</html>';
        exit;
    }
    if ($d['denon']['s']=='On') {
        $denonmain=json_decode(json_encode(simplexml_load_string(@file_get_contents('http://'.$denonip.'/goform/formMainZone_MainZoneXml.xml?_='.time, false, $ctx))), true);
        if (!empty($denonmain)) {
            $denoninput=$denonmain['InputFuncSelect']['value'];
        } else {
            $denoninput='Not ready yet';
        }
    } else {
        $denoninput='UIT';
    }
    echo '
    <body class="floorplan">
	    <div class="fix clock">
	        <a href=\'javascript:navigator_Go("floorplan.media.php");\'>
	            '.strftime("%k:%M:%S", TIME).'
	        </a>
	    </div>
    	<div class="fix z1" style="top:5px;left:5px;">
    	    <a href=\'javascript:navigator_Go("floorplan.php");\'>
    	        <img src="/images/close.png" width="72px" height="72px"/>
    	    </a>
    	</div>
	    <div class="fix" style="top:100px;left:0px;">
		    <form method="POST">';
    $inputs=array('TUNER','UIT');
    if (!in_array($denoninput, $inputs)&&$denoninput!='') {
        $inputs[]=$denoninput;
    }
    foreach ($inputs as $input) {
        if ($denoninput==$input) {
            echo '
                <input type="submit" name="Scene" value="'.$input.'" class="btn input btna"/>
                <br/>
                <br/>';
        } else {
            if ($input=='UIT') {
                echo '
                <input type="submit" name="Scene" value="UIT" class="btn input" onclick="return confirm(\'Are you sure?\');"/>
                <br/>
                <br/>';
            } else {
                echo '
                <input type="submit" name="Scene" value="'.$input.'" class="btn input"/>
                <br/>
                <br/>';
            }
        }
    }
    echo '
            </form>
            <br/>
            <a href=\'javascript:navigator_Go("denon.php");\'>
                <img src="/images/denon.png" class="i48"/>
            </a>
            <br>
            <br>
            <br>
            <a href=\'javascript:navigator_Go("https://films.egregius.be/films.php");\'>
                <img src="/images/kodi.png" class="i48"/>
                <br>Films
            </a>
            <br>
            <br>
            <a href=\'javascript:navigator_Go("https://films.egregius.be/series.php");\'>
                <img src="/images/kodi.png" class="i48"/>
                <br>
                Series
            </a>
            <br>
            <br>
            <a href=\'javascript:navigator_Go("kodi.php");\'>
                <img src="/images/kodi.png" class="i48"/>
                <br>
                Kodi<br>
                Control
            </a>
            <br>
            <br>';
    echo '
	    </div>';
    dimmer('zithoek');
    dimmer('eettafel');
    schakelaar('jbl', 'Light');
    schakelaar('kristal', 'Light');
    if (past('kristal')<$eendag) {
        echo '
        <div class="fix z0 right" style="top:55px;left:154px;width:35px;">
            '.strftime("%k:%M", $d['kristal']['t']).'
        </div>';
    }
    schakelaar('bureel', 'Light');
    if (past('bureel')<$eendag) {
        echo '
        <div class="fix z0 right" style="top:55px;left:213px;width:35px;">
            '.strftime("%k:%M", $d['bureel']['t']).'
        </div>';
    }
    schakelaar('keuken', 'Light');
    schakelaar('wasbak', 'Light');
    schakelaar('kookplaat', 'Light');
    schakelaar('werkblad1', 'Light');
    schakelaar('denon', 'denon');
    if (past('denon')<$eendag) {
        echo '
        <div class="fix z0 right" style="top:116px;left:99px;width:35px;">
            '.strftime("%k:%M", $d['denon']['t']).'
        </div>';
    }
    if ($d['tv']['s']=='On') {
        schakelaar('lgtv', 'lgtv');
    } else {
        schakelaar('tv', 'TV');
    }
    if (past('lgtv')<$eendag) {
        echo '
        <div class="fix z0 right" style="top:116px;left:175px;width:35px;">
            '.strftime("%k:%M", $d['lgtv']['t']).'
        </div>';
    }
    schakelaar('nvidia', 'nvidia');
    if (past('nvidia')<$eendag) {
        echo '
        <div class="fix z0 right" style="top:116px;left:229px;width:35px;">
            '.strftime("%k:%M", $d['nvidia']['t']).'
        </div>';
    }
    if ($d['nas']['s']=='On') {
        echo '
        <div class="fix nas z1">
            <a href=\'javascript:navigator_Go("?nas=sleep");\'>
                <img src="images/nas_On.png" class="i48" alt=""/>
            </a>
            <br>';
    } else {
        echo '
        <div class="fix nas z1">
            <a href=\'javascript:navigator_Go("?nas=wake");\'>
                <img src="images/nas_Off.png" class="i48" alt=""/>
            </a>
            <br>';
    }
    if (past('nas')<$eendag) {
        echo strftime("%H:%M", $d['nas']['t']);
    }
    echo '
        </div>';

    //schakelaar('kerstboom','Kerstboom');
    echo '
        <div class="fix bose">
            <a href=\'javascript:navigator_Go("floorplan.bose.php?ip=3");\'>
                <img src="images/Bose_'.($d['bose3']['s']=='On'?'On':'Off').'.png" id="bose" alt=""/>
            </a>
        </div>';

    echo '
        <div class="fix blackmedia">
            <form method="POST">';
    if ($d['denon']['s']=='On') {
        if (!empty($denonmain)) {
            $cv=80+$denonmain['MasterVolume']['value'];
            if ($cv==80) {
                $cv=0;
            }
            if ($denonmain['ZonePower']['value']=='ON') {
                $levels=array($cv-10,$cv-5,$cv-3,$cv-2,$cv-1,$cv,$cv+1,$cv+2,$cv+3,$cv+5,$cv+10);
                foreach ($levels as $k) {
                    $setvalue=80-$k;
                    $showvalue=$k;
                    if ($showvalue==80) {
                        $showvalue=0;
                    }
                    if ($showvalue>=0) {
                        if ($k==$cv) {
                            echo '
                <button type="submit" name="vol" value="'.$setvalue.'" class="btn volume btna">'.$showvalue.'</button>';
                        } else {
                            echo '
                <button type="submit" name="vol" value="'.$setvalue.'" class="btn volume">'.$showvalue.'</button>';
                        }
                    }
                }
            }
        }
    }
    //echo '<pre><div align="left">';print_r($_REQUEST);echo '</div></pre>';
    if ($d['lgtv']['s']=='On') {
        $lgsource=trim(shell_exec('python3 secure/lgtv.py -c get-input 192.168.2.27'));
        if ($lgsource=='com.webos.app.hdmi2') {
            $current=@json_decode(@file_get_contents($kodiurl.'/jsonrpc?request={"jsonrpc":"2.0","method":"Player.GetItem","params":{"properties":["title","album","artist","season","episode","duration","showtitle","tvshowid","thumbnail","file","imdbnumber"],"playerid":1},"id":"VideoGetItem"}', false, $ctx), true);
            //print_r($current);
            if (isset($current['result']['item']['file'])) {
                if (!empty($current['result']['item']['file'])) {
                       $item=$current['result']['item'];
                       //print_r($item);
                    if ($item['episode']>0) {
                        echo '<h1>'.$item['showtitle'].' S '.$item['season'].' E '.$item['episode'].'</h1>';
                        echo '<h1>'.$item['label'].'</h1>';
                    } else {
                        echo '<a href="http://www.imdb.com/title/'.$item['imdbnumber'].'" style="color:#f5b324"><h1>'.$item['label'].'</h1></a>';
                    }
                    $properties=@json_decode(@file_get_contents($kodiurl.'/jsonrpc?request={"jsonrpc":"2.0","method":"Player.GetProperties","id":1,"params":{"playerid":1,"properties":["playlistid","speed","position","totaltime","time","audiostreams","currentaudiostream","subtitleenabled","subtitles","currentsubtitle"]}}', false, $ctx), true);
                    if (!empty($properties['result'])) {
                        $prop=$properties['result'];
                        $point=$prop['time'];
                        $total=$prop['totaltime'];
                        $passedtime=$point['hours'].':';
                        $point['minutes']<10?$passedtime.='0'.$point['minutes'].':':$passedtime.=$point['minutes'].':';
                        $point['seconds']<10?$passedtime.='0'.$point['seconds']:$passedtime.=$point['seconds'];
                        $totaltime=$total['hours'].':';
                        $total['minutes']<10?$totaltime.='0'.$total['minutes'].':':$totaltime.=$total['minutes'].':';
                        $total['seconds']<10?$totaltime.='0'.$total['seconds']:$totaltime.=$total['seconds'];
                        echo '<table align="center">
			  <tr>
				<td>Passed</td>
				<td>'.$passedtime.'</h2></td><td>Runtime</td><td>'.$totaltime.'</td>
			  </tr>
			  <tr>
				<td>Remaining</td>
				<td>'.strftime("%k:%M:%S", (strtotime($totaltime)-strtotime($passedtime)-3600)).'</td>
				<td>End at</td>
				<td>'.strftime("%k:%M:%S", (TIME+strtotime($totaltime)-strtotime($passedtime))).'</td>
			  </tr>
			  </table>';
                        echo $prop['speed']==1
                        ?'<input type="submit" name="PauseKodi" value="Playing" class="btn b2"/>'
                        :'<input type="submit" name="PauseKodi" value="Paused" class="btn b2"/>';
                        echo '        <input type="submit" name="StopKodi" value="STOP" class="btn b2"/>
			  ';
                        if ($prop['speed']==1) {
                            echo '<br>
				<input type="submit" name="bigbackward" value="<<" class="btn b4"/>
				<input type="submit" name="smallbackward" value="<" class="btn b4"/>
				<input type="submit" name="smallforward" value=">" class="btn b4"/>
				<input type="submit" name="bigforward" value=">>" class="btn b4"/>
				';
                        }
                        $stream=0;
                        foreach ($prop['audiostreams'] as $audio) {
                            echo $audio['index']===$prop['currentaudiostream']['index']
                            ?'<button type="submit" name="audio" value="'.$audio['index'].'" class="btn btna b3">'.$audio['name'].'</button>'
                            :'<button type="submit" name="audio" value="'.$audio['index'].'" class="btn b3">'.$audio['name'].'</button>';
                            $stream=$stream + 1;
                        }
                        echo '<br/>';
                        foreach ($prop['subtitles'] as $subtitle) {
                            echo $subtitle['index']===$prop['currentsubtitle']['index']
                            ?'<button type="submit" name="subtitle" value="'.$subtitle['index'].'" class="btn btna b3">'.lang($subtitle['language']).' '.$subtitle['name'].'</button>'
                            :'<button type="submit" name="subtitle" value="'.$subtitle['index'].'" class="btn b3">'.lang($subtitle['language']).' '.$subtitle['name'].'</button>';
                        }
                        echo '<br><button type="submit" name="subtitle" value="enable" class="btn b2">Enable</button><button type="submit" name="subtitle" value="disable" class="btn b2">Disable</button>';
                    }
                    echo '</div>';


                } else {
                    echo '<div class="box">Update Library:<br/>
				  <input type="submit" name="UpdateKodi" value="Wij" class="btn b3"/>
				  <input type="submit" name="UpdateKodi" value="Tobi" class="btn b3"/>
				  <input type="submit" name="UpdateKodi" value="Alex" class="btn b3"/><br><br>
				  <br>
				  <input type="submit" name="PowerOff" value="Power Off" class="btn b2" onclick="return confirm(\'Are you sure?\');"/>
				  <input type="submit" name="PowerOn" value="Power On" class="btn b2"/><br>
				</div>';
                }
            }
        }
    }
    $pfsense=json_decode(@file_get_contents('http://192.168.2.254:44300/egregius.php'), true);
    echo '
                </div>
            </div>
        </div>
        <div class="fix floorplanstats">
            '.$udevice.' | '.$ipaddress.' | '.number_format(((microtime(true)-$start)*1000), 3, ',', '.').' | Up:'.human_kb(round($pfsense['up']), 0).' | Down:'.human_kb(round($pfsense['down']), 0).'
        </div>';

    echo '
        <script type="text/javascript">
			function navigator_Go(url) {window.location.assign(url);}
			setTimeout("window.location.href=window.location.href;",4875);
		</script>';
}
function human_filesize($bytes,$dec=2)
{
    $size=array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor=floor((strlen($bytes)-1)/3);
    return sprintf("%.{$dec}f", $bytes/pow(1024, $factor)).@$size[$factor];
}
function human_kb($bytes,$dec=2)
{
    $size=array('kb','Mb','Gb');
    $factor=floor((strlen($bytes)-1)/3);
    return sprintf("%.{$dec}f", $bytes/pow(1000, $factor)).@$size[$factor];
}
function lang($lang)
{
    switch($lang){
    case 'dut': $taal='&nbsp;NL&nbsp;';
        break;
    case 'eng': $taal='&nbsp;EN&nbsp;';
        break;
    case 'fre': $taal='&nbsp;FR&nbsp;';
        break;
    case '': $taal='N/A';
        break;
    default: $taal=$lang;
    }
    return $taal;
}
//else{header("Location: index.php");die("Redirecting to: index.php");}
?>
</body></html>