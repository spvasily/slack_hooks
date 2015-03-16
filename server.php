<?php

// Slack WebHook would send this in Http POST:
/*
	token=2OgmgzGzGgUpInjAa3us07bC
	team_id=T0001
	team_domain=example
	channel_id=C2147483705
	channel_name=test
	timestamp=1355517523.000005
	user_id=U2147483697
	user_name=Steve
	text=googlebot: What is the air-speed velocity of an unladen swallow?
	trigger_word=googlebot:
*/
error_reporting(0);
date_default_timezone_set('America/Los_Angeles');

$inText  = $_POST['text'];		
$inUser  = $_POST['user_name'];
$inToken = $_POST['token'];

$myToken = '2OgmgzGzGgUpInjAa3us07bC';
$deprecated = array('$', '&', '%', '"', "'");
$inText = str_replace($deprecated, '', $inText);

$textWords = explode(" ",$inText);
$firstWord = $textWords[0];			//should be "!gimme"
$seconWord = $textWords[1];			//weather, joke, etc..

$Err_Msg   = "Dude, please send correct request (see help)";
$res = $Err_Msg; //dflt

if($inToken == $myToken){
	if($firstWord == '!gimme'){

		switch ($seconWord){
			case 'joke': 		$res = "Joke for $inUser: \n".GetJoke(); Break;
			case 'weather':		$res = "Weather for $inUser: \n".GetWeather(); Break;
			case 'time':
			case 'date':		$res = "$seconWord for $inUser: ".date("F j, Y, g:i a"); Break;
			case 'holidays':	$res = "Holidays for $inUser: \n".GetHolidays(); Break;
			case 'stock':		$res = "Stock info for $inUser: \n".GetStock(); Break;
		
			case 'fuck':
			case 'FUCK':
			case 'blowjob':		$res = "Warning! Faggots in chat!"; Break;
			case 'money':
			case 'power':
			case 'something':	$res = "come on, dude.. I can't do it"; Break;
			case 'help':		$res = "Usage: \n!gimme [joke|weather|time] [CITY]";
		}
	}
}
else {
	header('HTTP/1.0 401 Unauthorized');
	exit;
}

$ret = new stdClass;
$ret->text = $res;
$ret = json_encode($ret);
echo $ret;


//====================
function GetJoke(){
	$content = file_get_contents('http://www.anekdot.ru/last/anekdot/');
	$content = str_replace('<br />', "\n", $content);
	if (preg_match_all('|div class="text" id="txt_id_\d+">([^/]+)</div|U', $content, $matches)){
				$res = $matches[1][ rand(0, count($matches[1])) ];
	} else 		$res = 'no jokes found :(';

	return $res;
}

//====================
function GetWeather(){
	global $textWords, $Err_Msg;
	//Need City name, and it can be two words or more
	$city = $textWords[2];
	for($i=3;$i<count($textWords);$i++) $city.= ' '.$textWords[$i];  

	function ktotemps($k) {
		$obj = new stdClass;
		$obj->celsius = $k - 273.15;
		$obj->fahrenheit = ($obj->celsius*9/5) + 32;
		return $obj;
	}
	
	if($city != ''){
		$json = file_get_contents('http://api.openweathermap.org/data/2.5/weather?q='.urlencode($city));
		$obj = json_decode($json);
		
		if(isset($obj->main->temp)){
			$curTemp = ktotemps($obj->main->temp);
			return "Currently in $city ".round($curTemp->fahrenheit)."F (".round($curTemp->celsius)." grad C) and ".$obj->weather[0]->description.".";
		} else return $Err_Msg;
	} else return $Err_Msg;
}

//====================
function GetStock(){
	global $textWords, $Err_Msg;
	
	$stk = $textWords[2]; if(trim($stk) == '') return $Err_Msg;
	for($i=3;$i<count($textWords);$i++) $stk.= '+'.$textWords[$i];
	
	$stock_data = file_get_contents("http://finance.yahoo.com/d/quotes.csv?s=$stk&f=sxl1t1p2vghj1n");
	if($stock_data){
		$res = '';
		$stdat = explode("\n", $stock_data);
		
		foreach($stdat as $sd){
			if($sd == '') continue;
			$sd = str_replace('"', '', $sd);
			$s = explode(',', $sd);
			if($s[2] == 'N/A') $res.= "$s[0] - unknown\n";
			else $res .= "$s[0] ($s[1]) \$$s[2]($s[3]), $s[4], minmax:[\$$s[6],\$$s[7]], MCap \$$s[8] - $s[9]\n";
		}
		
		return $res;
	}
	else return $Err_Msg;
		
}

//==================== appspot.com is currently blocked by timeanddate.com
function GetHolidays(){
	// http://www.timeanddate.com/holidays/us/
	// !gimme holidays [COUNTRY|us|russia|showcountries] [today|thisweek|thismonth|7days|30days]
	global $textWords, $Err_Msg;
	$cnt = $textWords[2];
	$cmd = $textWords[3];
	$currentyear = date('Y', time());
	
	function PrintArray($arr){
		foreach($arr as $a) printf("%s\t\t%s\t\t%s\t\t%s\n", $a[0], $a[1], $a[2], $a[3]);
	}
	
	function getWholeWeek($week, $year)
	{
		$time = strtotime("1 January $year", time());
		$day = date('w', $time);
		$time += ((7*($week-1))+1-$day)*24*3600;
		$return[0] = date('M j', $time);
		$time += 24*3600; $return[1] = date('M j', $time);
		$time += 24*3600; $return[2] = date('M j', $time);
		$time += 24*3600; $return[3] = date('M j', $time);
		$time += 24*3600; $return[4] = date('M j', $time);
		$time += 24*3600; $return[5] = date('M j', $time);
		$time += 24*3600; $return[6] = date('M j', $time);
		return $return;
	}
	
	function getListForADay($arr, $dt){
		$lst = '';
		foreach($arr as $a){
			if($a[0] == $dt)	$lst.= "$a[0] ($a[1]) - $a[2] ($a[3])\n";
		}
		return $lst;
	}
	
	
	if($cnt == '') $cnt = 'us';
	if($cnt == 'showcountries'){
		// ...
	}
	else{
		
		$rx1 = '|<tr id=(.+)</tr>|U';
		$rx2 = '|<th class="nw" >(.+)</th><td.+>(\w+)</td><td>(.+)</td><td>(.+)</td>|U';
		
		$Hdays = array();
		$content = str_replace("\n", '', file_get_contents("http://www.timeanddate.com/holidays/$cnt/"));
		if (preg_match_all($rx1, $content, $matches)){ //PrintArray($matches);
			for($i=0;$i<count($matches[0]);$i++){
				if(preg_match($rx2, $matches[1][$i], $match1)){
						$Hdays[] = array($match1[1], $match1[2], str_replace('&#39;', '', strip_tags($match1[3])), $match1[4]);
				}
			}
		}
		
		
		if(count($Hdays)){
		
			$lst = '';	//PrintArray($matches);
			
			//today|thisweek|thismonth|7days|30days
			switch($cmd){
				case 'thisweek':	$wk = getWholeWeek(date('W', time()), $currentyear);
									foreach($wk as $w) $lst.= getListForADay($Hdays, $w);
									Break;
				case 'today':
				default:			$lst = getListForADay($Hdays, date("M j")); //'Mar 21'
									Break;
			}
			
			if($lst == '') return 'no holidays found :(';
			else return $lst;
		
		} else 	return 'no holidays found for this country';
	}
}
	
?>