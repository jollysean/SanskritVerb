<?php
/* Script to regenerate various variables of function.php e.g. $bhvAdi, $adAdi etc, based on $verbdata.
	Usage: php listgen.php
	Manually copy paste and replace the variables to function.php.
	If any change is made to the $verbdata variable, its effect will have to be carried into all derived variables.
	This script does exactly the same.
	Also generates a log file with summary - '../Data/verbdatasummary.txt'
*/
include 'function.php';
include 'slp-dev.php';

// Function to display the data in $array=array("...","...",......); format
function arraydisplayer($inputarray,$arrayname)
{
	echo $arrayname.'=array("'.implode('","',$inputarray).'");'."<br/>\n";
}
// Function to generate list based on $verbdata
function listgenerator($debug)
{
	global $verbdata;
	// for each entry in $verbdata
	foreach ($verbdata as $verbdatum)
	{
		// Typical line separated by a colon
		// "aMsa:samAGAte:aMsa:10:0460:u:sew:अं॑स॑:1420::1475:aMsa1_aMsa_curAxiH+samAGAwe:"
		list($verb[],$meaning[],$verb1[],$verbset[],$verbnumber[],$verbpada[],$verbit[],$deva[],$ma[],$ks[],$dh[],$uohyd[],$jnu[]) = explode(':',$verbdatum);
	}
	// for every entry in $verbdata
	for($i=0;$i<count($verbdata);$i++)
	{
		$allverbs[] = $verb[$i];
		$allverbs1[] = $deva[$i];
		// parasmai / Atmane / ubhaya decision
		if ($verbpada[$i]==="pa")
		{
			$parasmai[] = $verb[$i];
		}
		elseif ($verbpada[$i]==="A")
		{
			$Atmane[] = $verb[$i];
		}
		elseif ($verbpada[$i]==="u")
		{
			$ubhaya[] = $verb[$i];
		}
		else
		{
			echo "Verb pada fishy", $verb[$i];
		}
		// Verb set (gana) separator
		if ($verbset[$i]==="01")
		{
			$bhvAdi[] = $verb[$i];
			$bhvAdi1[] = $deva[$i];
		}
		elseif ($verbset[$i]==="02")
		{
			$adAdi[] = $verb[$i];
			$adAdi1[] = $deva[$i];
		}
		elseif ($verbset[$i]==="03")
		{
			$juhotyAdi[] = $verb[$i];
			$juhotyAdi1[] = $deva[$i];
		}
		elseif ($verbset[$i]==="04")
		{
			$divAdi[] = $verb[$i];
			$divAdi1[] = $deva[$i];
		}
		elseif ($verbset[$i]==="05")
		{
			$svAdi[] = $verb[$i];
			$svAdi1[] = $deva[$i];
		}
		elseif ($verbset[$i]==="06")
		{
			$tudAdi[] = $verb[$i];
			$tudAdi1[] = $deva[$i];
		}
		elseif ($verbset[$i]==="07")
		{
			$ruDAdi[] = $verb[$i];
			$ruDAdi1[] = $deva[$i];
		}
		elseif ($verbset[$i]==="08")
		{
			$tanAdi[] = $verb[$i];
			$tanAdi1[] = $deva[$i];
		}
		elseif ($verbset[$i]==="09")
		{
			$kryAdi[] = $verb[$i];
			$kryAdi1[] = $deva[$i];
		}
		elseif ($verbset[$i]==="10")
		{
			$curAdi[] = $verb[$i];
			$curAdi1[] = $deva[$i];
		}
		else
		{
			echo "Bad entry"; print_r($verbdata[$i]);
		}
		// anudAtta / svarita segregator
		if (preg_match('/॒॑/',$deva[$i]))
		{
			$svaritetverbs[] = $verb[$i];
		}
		elseif (preg_match('/॒/',$deva[$i]))
		{
			$anudAttetverbs[] = $verb[$i];
		}
		else
		{
			$udAttetverbs[] = $verb[$i];
		}
	}
	// Nonuniqueverbs, Gitverbs, Jitverbs separation
	// Create a $key=>count array by using PHP function array_count_values
	$arrayvalues = array_count_values($allverbs);
	foreach ($arrayvalues as $key=>$value)
	{
		// If the verb occurs more than one times, it is added to $nonuniquverbs
		if ($value>1)
		{
			$nonuniqueverbs[] = $key;
		}
		// If verb ends with 'N', it is added to $Gitverbs
		if (preg_match('/N$/',$key))
		{
			$Gitverbs[] = $key;
		}
		// If verb ends with 'Y', it is added to $Jitverbs
		if (preg_match('/Y$/',$key))
		{
			$Jitverbs[] = $key;
		}
	}
	// Devanagariallverbs
	$devanagariallverbs = array_map('convert',$allverbs);
	// An array of the arrays to be printed to file.
	$basearray = array($bhvAdi,$adAdi,$juhotyAdi,$divAdi,$svAdi,$tudAdi,$ruDAdi,$tanAdi,$kryAdi,$curAdi,$bhvAdi1,$adAdi1,$juhotyAdi1,$divAdi1,$svAdi1,$tudAdi1,$ruDAdi1,$tanAdi1,$kryAdi1,$curAdi1,$allverbs,$allverbs1,$parasmai,$Atmane,$ubhaya,$svaritetverbs,$anudAttetverbs,$udAttetverbs,$nonuniqueverbs,$Gitverbs,$Jitverbs,$devanagariallverbs);
	// Their names, as they are in function.php
	$basearraynames = array('$bhvAdi','$adAdi','$juhotyAdi','$divAdi','$svAdi','$tudAdi','$ruDAdi','$tanAdi','$kryAdi','$curAdi','$bhvAdi1','$adAdi1','$juhotyAdi1','$divAdi1','$svAdi1','$tudAdi1','$ruDAdi1','$tanAdi1','$kryAdi1','$curAdi1','$allverbs','$allverbs1','$parasmai','$Atmane','$ubhaya','$svaritetverbs','$anudAttetverbs','$udAttetverbs','$nonuniqueverbs','$Gitverbs','$Jitverbs','$devanagariallverbs');
	// Create a summary log file (to keep track of changes)
	$logfile = fopen('../Data/verbdatasummary.txt','w');
	// For each entry in $basearray
	for($i=0;$i<count($basearray);$i++)
	{
		// Display it
		arraydisplayer($basearray[$i],$basearraynames[$i]);
		// Write summary to log file.
		fputs($logfile,$basearraynames[$i].":".count($basearray[$i])."\n");
	}
	fclose($logfile);
	// If the user wants to see it on terminal, show it.
	if($debug==='1')
	{
		echo "bhvAdi ".count($bhvAdi)."<br/>\n";
		echo "adAdi ".count($adAdi)."<br/>\n";
		echo "juhotyAdi ".count($juhotyAdi)."<br/>\n";
		echo "divAdi ".count($divAdi)."<br/>\n";
		echo "svAdi ".count($svAdi)."<br/>\n";
		echo "tudAdi ".count($tudAdi)."<br/>\n";
		echo "ruDAdi ".count($ruDAdi)."<br/>\n";
		echo "tanAdi ".count($tanAdi)."<br/>\n";
		echo "kryAdi ".count($kryAdi)."<br/>\n";
		echo "curAdi ".count($curAdi)."<br/>\n";
		echo "Summation ",count($bhvAdi)+count($adAdi)+count($juhotyAdi)+count($divAdi)+count($svAdi)+count($tudAdi)+count($ruDAdi)+count($tanAdi)+count($kryAdi)+count($curAdi)."<br/>\n";
		echo "Total ".count($verbdata)."<br/>\n";
		echo "<br/>\n";
		echo "Parasmai ".count($parasmai)."<br>\n";
		echo "Atmane ".count($Atmane)."<br>\n";
		echo "Ubhaya ".count($ubhaya)."<br>\n";
		echo "Summation ",count($parasmai)+count($Atmane)+count($ubhaya)."<br/>\n";
		echo "Total ".count($verbdata)."<br/>\n";
		echo "<br/>\n";
		echo "svaritet verbs ".count($svaritetverbs)."<br>\n";
		echo "anudAttet verbs ".count($anudAttetverbs)."<br>\n";
		echo "udAttet verbs ".count($udAttetverbs)."<br>\n";
		echo "Summation ",count($svaritetverbs)+count($anudAttetverbs)+count($udAttetverbs)."<br/>\n";
		echo "Total ".count($verbdata)."<br/>\n";
		echo "<br/>\n";
	}
}
listgenerator('');
?>