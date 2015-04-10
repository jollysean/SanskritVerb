<?php
//header('Content-type: text/html; charset=utf-8');
$header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<!--... Defining UTF-8 as our default character set, so that devanagari is displayed properly. -->
<meta charset="UTF-8">
<!--... Defining CSS -->
<link rel="stylesheet" type="text/css" href="mystyle.css">
<!--... including Ajax jquery. -->
</head> 
<body>
';
 /* This code is developed by Dr. Dhaval Patel (drdhaval2785@gmail.com) of www.sanskritworld.in and Ms. Sivakumari Katuri.
  * Layout assistance by Mr Marcis Gasuns.
  * Available under GNU licence.
  * Version 3.0 date 10 October 2014
  * The latest source code is available at https://github.com/drdhaval2785/sanskrit
  * Acknowledgements: I extend my heartfelt thanks to Ananda Loponen for the code to convert devanagari and various sanskrit transliterations. That can be accessed at http://www.ingmardeboer.nl/php/diCrunch.php?act=help.
  * I also extend my gratitude to gloomy.penguin of stackoverflow.com, who helped me create dvitva and lopa functions, without which I would be handicapped.
  * For setup, copy and paste subanta.php, subanta.html, script.js, ajax.php, function.php, mystyle.css, slp-dev.php and dev-slp.php to your localhost and server and run subanta.html.
  * tiGanta.html is the frontend for the code.
  * ajax.php and script.js are codes which asks for user feedback for particular words. 
  * function.php stores the frequently used functions in this code (The description on how to use the code is there in function.php).
  * tiGanta.php is the code which actually gives the output of the word derivation.
  * slp-dev.php is for converting SLP1 data to Devanagari. 
  * dev-slp.php is for converting Devanagari data to SLP1.
  * Mystyle.css is stylesheet where you can change your preferences.
  * The code uses jquery.
  * The description part uses Howard Kyoto protocol.
  * The coding uses SLP1 transliteration.
  */
 
/* Including arrays and functions */
include "function.php"; // includes the file function.php which is collection of functions used in this code.
include "slp-dev.php"; // includes code for conversion from SLP to devanagari,
include "dev-slp.php"; // includes code for devanagari to SLP.

/* hides error reports. */
// If the warning is shown with line number of function.php and you are not able to trace the line which called it, turn the all error reporting on. It will help you locate the wrong entries in a reasonably narrow space, because there are so many notices around.
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(-1);
error_reporting(0);
/* set execution time to an hour */
ini_set('max_execution_time', 36000);
/* set memory limit to 100000 MB */
ini_set("memory_limit","100000M");

/* Reading from the HTML input. */
//$first = $_GET["first"]; // word entered by the user.
$first = toslp($_GET["first"]); // to change the word input in devanagari / IAST to slp.
$us = $_GET["upasarga"]; // upasarga. Added on 14 Dec 2014.
$tran = $_GET['tran']; // "Devanagari" - devanagari, "IAST" - IAST, "SLP1" - SLP1 transliteration.
$lakAra = $_GET['lakAra'];
$gender = $_GET['gender']; // "m" - male. "f" - female. "n" - neuter.
$vAcya = $_GET['vAcya'];
if (!$vAcya) { $vAcya="kartR"; }
$sanAdi = $_GET['sanAdi'];
$number = $_GET['number'];
$verbset = $_GET['verbset'];
if (!$verbset) { $verbset = scrape1($number,8,9,1)[0]; } // for overcoming issue in https://github.com/drdhaval2785/SanskritVerb/issues/97
/* Now trying to make program equally compatible with commandline.
The proposed structure is php tiGanta.php verb verbset lakAra tran upasarga vAcya 
defaults:
verb - no default (mandatory to feed)
verbset - 'none'
lakAra - 'law'
tran - 'SLP1'
upasarga - ''
vAcya - 'kartR'
*/
// Now start the input for commandline
if (isset($argv[0]))
{
	$first = $argv[1];
	$verbset = $argv[2];
	$lakAra = $argv[3];
	$tran = $argv[4];
	$us = $argv[5];
	$vAcya = $argv[6];
	$sanAdi = $argv[7];
	if (!isset($argv[1])) { echo "Verb is not entered"; exit; }	
	if (!isset($argv[2])) { echo "Verb gaNa is not entered"; exit; }
	if (!isset($argv[3])) { $lakAra = 'law'; }
	if (!isset($argv[4])) { $tran = 'SLP1'; }
	if (!isset($argv[5])) { $us = ''; }
	if (!isset($argv[6])) { $vAcya = 'kartR'; }	
	if (!isset($argv[7])) { $sanAdi = ''; }	
}
/* Creating a log */
$logfile = fopen('D:\\!sorting\\verboutput\\log.txt','a+');
fputs($logfile,date('D, d M Y H:i:s')."\n");
fputs($logfile,"verb = $first, gaNa = $verbset, lakAra = $lakAra, transliteration = $tran, vAcya = $vAcya, upasarga = $us\n");
fclose($logfile);
$outfile = fopen("D:\\!sorting\\verboutput\\".$first."_".$verbset."_".$lakAra.".html", "wb");
// The patch to replace echo with fputs and enter the data into the output html file. See http://stackoverflow.com/questions/27708929/can-echo-be-manipulated-to-write-to-a-file-in-php
/*ob_start(function($x) use($outfile) {
	fwrite($outfile, $x);
	return '';
});*/
//ob_start(); // Now onward everything which is echoed will not be shown in frontend, but stored in buffer. If we call ob_end_clean it will not be echoed. If we call ob_end_flush it will be echoed. If we use ob_get_contents the content can be stored in string. If we do fputs($outfile,ob_get_contents()) we will be able to write it to the outfile. They have been used at the end of the code. This makes the code resilient to be used for Commandline / browser with writing to the outfile. 31/12/2014
echo $header; // creating header.

if (!$verbset) { $verbset="none"; } // I dont think this is needed now. Test and remove. Pending.
$verbset=trim($verbset);
$fo = $first; // remembering the original prakRti. Sometimes we need to know what was the original prakRti.
/* Defining the variables used in the code and their default values .
 * If there is no change in the execution of subanta.php, the default values are operated.
 */
$pada = "pratyaya"; // "pada" - pada saJjJA. "pratyaya" - not pada.
$nadi = 0; // 0 - no nadI saJjJA. 1 - nadI saJjJA
$GI = 0; // 0 - no Gyantatva. 1 - Gyantantva.
$Ap = 0; // 0 - no Abantatva. 1 - Abantatva.
$taddhita = 0; // 0 - not taddhita. 1 - taddhita.
$dhatu = 0; // 0 - no dhAtu. 1 - dhAtu.
$eranekaca = 0; // 0 - no application of eranekAco. 1 - application of eranekAco.
$tri = "m"; // for tricaturoH striyAM tisRcatasR (7.2.99). "m" - word tri is pulliGga. "f" - word tri is strIliGga.
$nityastri = 0; // for nitya strIliGga. 0 - not nityastrIliGga. 1 - nityastrIliGga.
$ekajuttarapada = 0; // 0 - no application of ekAjuttarapade NaH. 1 - application of ekAjuttarapade NaH.
$bhashitapumska = 0; // 0 - not bhASitapuMska. 1 - bhASitapuMska.
$anvadesha = 0; // 0 - no anvAdeza. 1 - anvAdeza.
$samasa = 0; // 0 - no samAsa. 1 - samAsa
$pradhana = 0; // 0 - no pradhAnatva in samAsa. 1 - pradhAnatva in samAsa 
$Jit = 0; // 0 - no 'J' as it marker. 1 - 'J' as it marker. e.g. ho hanterJNinneSu 7.3.54
$Nit = 0; // 0 - no 'N' as it marker. 1 - 'N' as it marker.
$kvin = 0; // 0 - no kvin pratyaya. 1 - kvin pratyaya.
$kvip = 0; // 0 - no kvip pratyaya. 1 - kvip pratyaya.
$asmadpada = 2; // 0 - niSedha. 1 - nitya. 2 - vibhASA. used to decide whether the conversion of asmad / yuSmad -> me / te, nau / vAm etc will happen or not.
$bhavat = 0; // 0 - bhAterDavatu. 1 - bhU+zatR.
$abhyasta = 0; // 0 - not abhyasta. 1 - abhyasta.
$shatR = 0; // 0 - not zatR pratyaya. 1 - zatR pratyaya.
$Nyanta = 0; // 0 - not Nyanta, 1 - Nyanta.
$san = 0; // 0 - non san, 1 - san. 'san' is used to create nAmadhAtus. 
$yaG = 0; // 0 - no yaG pratyaya. 1 - yaG pratyaya has applied.
$vasu = 0; // 0 - no vasvanta, 1 - vasvanta.
$shap = 0; // 0 - no zap pratyaya. 1 - zap pratyaya.
$shyan = 0; // 0 - no zyan pratyaya. 1 - zyan pratyaya.
$tRcvat=0; // 0 - not tRjvat. 1 - tRjvat. e.g. tRjvat kroSTuH.
$nance = 0;
$aniditAm = 0;
$anobahuvrihe=0;
$manah=0;
$anapatya=0;
$sarvadhatuka=0; 
$ardhadhatuka=0;
$veda=1; // to test for Chandas forms. Turn it to 0 for laukika, 1 for Chandas.
$kGiti=0;
$atolopa=0;
$halGyAbbhyo=0;
$atmanepada=0;
$ubhayapada=0;
$parasmaipada=0;
$rudhAdibhyaH=0;
$ad=0;
$nomidelision=0;
$vras1=0;
$kit=0;
$kRt=0;
$sic=0;
$caG=0;
$id_pratyaya="sew"; // right now taking it as default. Will feed later on.
$R = array(); // creating an array where we can store whether the word has 'R' as it marker.
$num = array(); // creating an array where we can store whether the word has 'num' Agama. 
$it = array(); // Creating an array where we can store it markers.
$itprakriti = array(); // creating an array where we can store it markers of prakRti.
$itpratyaya = array(); // creating an array where we can store it markers of pratyayas.
$samp = array(); // creating an array where we can store whethere samprasAraNa has happened or not.
$Agama = array(); // storing Agamas.
// rest of the variables will be defined at their particular occurence in the code.

/* Displaying information about the verb */
if ($verbset!=="none")
{
    verb_meaning_gana_number2($first);
}
elseif (in_array($first,$allverbs))
{
    verb_meaning_gana_number1($first);
}
else
{
	echo "<p class = st >धातुः - ".convert($first)."</p>\n";
	echo "<hr>\n";
}
/* upasarga display */
if ($us!=="")
{
    echo "<p class = st >upasarga:  $us</p>\n"; 
    echo "<p class = st >उपसर्गः : ".convert($us)."</p>\n";
    echo "<hr>\n";
}
/* Deciding the pratyaya by doing padanirdhARaNa of parasmai, Atmane, ubhaya */ 
/* bhAvakarmaNoH (1.3.13) */
if ( in_array($vAcya,array("karma","bhAva")) && $pada==="pratyaya" && $lakAra!=="")
{
    $suffix=$taG;
    echo "<p class = st >By bhAvakarmaNoH (".link_sutra("1.3.13").") :</p>\n"; 
    echo "<p class = st >भावकर्मणोः (१.३.१३) :</p>\n";
    echo "<hr>\n";
    $atmanepada=1;
}
/* luTi ca klRpaH (1.3.93) */
elseif ( $fo==="kfpU!" && ($san===1 || in_array($lakAra,array("lfN","lfw","luw"))))
{
    $suffix=$tiG;
    echo "<p class = st >By luTi ca klRpaH (".link_sutra("1.3.93").") :</p>\n"; 
    echo "<p class = st >लुटि च क्लृपः (१.३.९३) :</p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* na gatihiMsArthebhyH (1.3.15) */
elseif ( $_GET['cond11_1']==='1') // cond stands for condition. They are taken from user input. For all variables having cond as prefix, details can be seen at ajax requirement.docx.
{
    $suffix=$tis;
    echo "<p class = st >By na gatihiMsArthebhyH (".link_sutra("1.3.15").") :</p>\n"; 
    echo "<p class = st >न गतिहिंसार्थेभ्यः (१.३.१५) :</p>\n";
    echo "<hr>\n";
    $parasmaipada=1;
}
/* itaretarAnyo'nyopapadAcca (1.3.16) */
// parasparopapadAcceti vaktavyam is implicitly included in it. If need be, a user feedback has to be increased.
elseif ( $_GET['cond11_1']==='2')
{
    $suffix=$tis;
    echo "<p class = st >By itaretarAnyo'nyopapadAcca (".link_sutra("1.3.16").") :</p>\n"; 
    echo "<p class = st >इतरेतरान्योऽन्योपपदाच्च (१.३.१६) :</p>\n";
    echo "<hr>\n";
    $parasmaipada=1;
}
/* kartari karmavyatihAre (1.3.14) */
elseif ( $_GET['cond11_1']==='3')
{
    $suffix=$taG;
    echo "<p class = st >By kartari karmavyatihAre (".link_sutra("1.3.14").") :</p>\n"; 
    echo "<p class = st >कर्तरि कर्मव्यतिहारे (१.३.१४) :</p>\n";
    echo "<hr>\n";
    $atmanepada=1;
}
/* AGo do'nAsyaviharaNe (1.3.20) */
elseif ( $_GET['cond14']==="2" )
{
    $suffix=$taG;
    echo "<p class = st >By AGo do'nAsyaviharaNe (".link_sutra("1.3.20").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >आङो दोऽनास्यविहरणे (१.३.२०) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* parAGgakarmakAnna niSedhaH (vA 903) */
elseif ( $_GET['cond14_1']==="2" )
{
    $suffix=$taG;
    echo "<p class = st >By parAGgakarmakAnna niSedhaH (vA 903) :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >पराङ्गकर्मकान्न निषेधः (वा ९०३) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* prakAzanastheyAkhyayozca (1.3.23) */
elseif ( $_GET['cond15']==="1" )
{
    $suffix=$taG;
    echo "<p class = st >By prakAzanastheyAkhyayozca (".link_sutra("1.3.23").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >प्रकाशनस्थेयाख्ययोश्च (१.३.२३) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* udo'nUrdhvakarmaNi (1.3.24) */
elseif ( $_GET['cond16']==="2" )
{
    $suffix=$taG;
    echo "<p class = st >By udo'nUrdhvakarmaNi (".link_sutra("1.3.24").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >उदोऽनूर्ध्वकर्मणि (१.३.२४) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* upAnmantrakaraNe (1.3.25) */
elseif ( $_GET['cond17']==="1" )
{
    $suffix=$taG;
    echo "<p class = st >By upAnmantrakaraNe (".link_sutra("1.3.25").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >उपान्मन्त्रकरणे (१.३.२५) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* akarmakAcca (1.3.26) */
elseif ( $_GET['cond17']==="2" )
{
    $suffix=$taG;
    echo "<p class = st >By akarmakAcca (".link_sutra("1.3.26").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >अकर्मकाच्च (१.३.२६) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* spardhAyAmAGaH (1.3.31) */
elseif ( $_GET['cond18']==="1" )
{
    $suffix=$taG;
    echo "<p class = st >By spardhAyAmAGaH (".link_sutra("1.3.31").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >स्पर्धायामाङः (१.३.३१) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* anuparAbhyAM kruJaH (1.3.79) */
elseif ( ends(array($first),array("qukfY"),2) && in_array($us,array("anu","parA")) )
{
    $suffix=$tis;
    echo "<p class = st >By anuparAbhyAM kruJaH (".link_sutra("1.3.79").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >अनुपराभ्यां कृञः (१.३.७९) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* gandhanAvakSepaNasevanasAhasikyapratiyatnaprakathanopayogeSu kRJaH (1.3.32) */
elseif ( $_GET['cond19']==="1" )
{
    $suffix=$taG;
    echo "<p class = st >By gandhanAvakSepaNasevanasAhasikyapratiyatnaprakathanopayogeSu kRJaH (".link_sutra("1.3.32").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >गन्धनावक्षेपणसेवनसाहसिक्यप्रतियत्नप्रकथनोपयोगेषु कृञः (१.३.३२) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";$logfile = fopen('D:\\!sorting\\verboutput\\log.txt','a+');
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* adheH prahasane (1.3.33) */
elseif ( $_GET['cond20']==="1" )
{
    $suffix=$taG;
    echo "<p class = st >By adheH prahasane (".link_sutra("1.3.33").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >अधेः प्रहसने (१.३.३३) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* veH zabdakarmaNaH (1.3.34) */
elseif ( $_GET['cond21']==="1" )
{
    $suffix=$taG;
    echo "<p class = st >By veH zabdakarmaNaH (".link_sutra("1.3.34").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >वेः शब्दकर्मणः (१.३.३४) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* akarmakAcca (1.3.35) */
elseif ( $_GET['cond21']==="2" )
{
    $suffix=$taG;
    echo "<p class = st >By akarmakAcca (".link_sutra("1.3.35").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >अकर्मकाच्च (१.३.३५) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* kartRsthe cAzarIre karmaNi (1.3.37) */
elseif ( $_GET['cond23']==="1" )
{
    $suffix=$taG;
    echo "<p class = st >By kartRsthe cAzarIre karmaNi (".link_sutra("1.3.37").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >कर्तृस्थे चाशरीरे कर्मणि (१.३.३७) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* sammAnanotsaJjanAcAryakaraNajJAnabhRtivigaNanavyayeSu niyaH (1.3.36) */
elseif ( $_GET['cond22']==="1" )
{
    $suffix=$taG;
    echo "<p class = st >By sammAnanotsaJjanAcAryakaraNajJAnabhRtivigaNanavyayeSu niyaH (".link_sutra("1.3.36").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >सम्माननोत्सञ्जनाचार्यकरणज्ञानभृतिविगणनेषु नियः (१.३.३६) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* vRttisargatAyaneSu kramaH (1.3.38) and anupasargAdvA (1.3.43) */
elseif ( $_GET['cond24']==="1" && $first==="kramu!")
{
    $suffix=$tiG;
    echo "<p class = st >By vRttisargatAyaneSu kramaH (".link_sutra("1.3.38").") and anupasargAdvA (".link_sutra("1.3.43").") :</p>\n"; 
    echo "<p class = hn >These sUtras mandate optional Atmanepadam. </p>\n";
    echo "<p class = st >वृत्तिसर्गतायनेषु क्रमः (१.३.३८) तथा अनुपसर्गाद्वा (१.३.४३) :</p>\n";
    echo "<p class = hn >वैभाषिकं आत्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $ubhayapada=1;
}
/* upaparAbhyAm (1.3.39) */
elseif ( $_GET['cond24']==="1" )
{
    $suffix=$taG;
    echo "<p class = st >By upaparAbhyAm (".link_sutra("1.3.39").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >उपपराभ्याम्‌ (१.३.३९) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* AGa udgamane (1.3.40) */
elseif ( $_GET['cond25']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By AGa udgamane (".link_sutra("1.3.40").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >आङ उद्गमने (१.३.४०) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* veH pAdaviharaNe (1.3.41) */
elseif ( $_GET['cond26']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By veH pAdaviharaNe (".link_sutra("1.3.41").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >वेः पादविहरणे (१.३.४१) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* propAbhyAM samarthAbhyAm (1.3.42) */
elseif ( $_GET['cond31']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By propAbhyAM samarthAbhyAm (".link_sutra("1.3.42").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >प्रोपाभ्यां समर्थाभ्याम्‌ (१.३.४२) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* apahnave jJaH (1.3.44) */
elseif ( $_GET['cond27']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By apahnave jJaH (".link_sutra("1.3.44").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >अपह्नवे ज्ञः (१.३.४४) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* akarmakAcca (1.3.45) */
elseif ( $_GET['cond27']==="2")
{
    $suffix=$taG;
    echo "<p class = st >By akarmakAcca (".link_sutra("1.3.45").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >अकर्मकाच्च (१.३.४५) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* sampratibhyAmanAdhyAne (1.3.46) */
elseif ( $_GET['cond28']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By sampratibhyAmanAdhyAne (".link_sutra("1.3.46").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >सम्प्रतिभ्यामनाध्याने (१.३.४६) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* bhAsanopasaMbhASAjJAnayatnavimatyupanimantraNeSu vadaH (1.3.47) */
elseif ( $_GET['cond29']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By bhAsanopasaMbhASAjJAnayatnavimatyupanimantraNeSu vadaH (".link_sutra("1.3.47").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >भासनोपसंभाषाज्ञानयत्नविमत्युपनिमन्त्रणेषु वदः (१.३.४७) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* vyaktavAcAM samuccAraNe (1.3.48) */
elseif ( $_GET['cond29']==="2")
{
    $suffix=$taG;
    echo "<p class = st >By vyaktavAcAM samuccAraNe (".link_sutra("1.3.48").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >व्यक्तवाचां समुच्चारणे (१.३.४८) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* vibhASA vipralApe (1.3.50) */
elseif ( $_GET['cond29']==="3" )
{
    $suffix=$tiG;
    echo "<p class = st >By vibhASA vipralApe (".link_sutra("1.3.50").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates optional Atmanepadam. </p>\n";
    echo "<p class = st >विभाषा विप्रलापे (१.३.५०) :</p>\n";
    echo "<p class = hn >वैभाषिकं आत्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* anorakarmakAt (1.3.49) */
elseif ( $_GET['cond30']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By anorakarmakAt (".link_sutra("1.3.49").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >अनोरकर्मकात्‌ (१.३.४९) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* samaH pratijJAne (1.3.52) */
elseif ( $_GET['cond32']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By samaH pratijJAne (".link_sutra("1.3.52").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >समः प्रतिज्ञाने (१.३.५२) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* udazcaraH sakarmakAt (1.3.53) */
elseif ( $_GET['cond33']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By udazcaraH sakarmakAt (".link_sutra("1.3.53").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >उदश्चरः सकर्मकात्‌ (१.३.५३) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* samastRtIyAyuktAt (1.3.54) */
elseif ( $_GET['cond34']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By samastRtIyAyuktAt (".link_sutra("1.3.54").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >समस्तृतीयायुक्तात्‌ (१.३.५४) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* dANazca sA ceccaturthyarthe (1.3.55) */
elseif ( $_GET['cond35']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By dANazca sA ceccaturthyarthe (".link_sutra("1.3.55").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >दाणश्च सा चेच्चतुर्थ्यर्थे (१.३.५५) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* upAdyamaH svakaraNe (1.3.56) */
elseif ( $_GET['cond36']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By upAdyamaH svakaraNe (".link_sutra("1.3.56").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >उपाद्यमः स्वकरणे (१.३.५६) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* nAnorjJaH (1.3.58) */
elseif ( $first==="jYA" && $us==="anu" && $sanAdi==="san")
{
    $suffix=$tis;
    echo "<p class = st >By nAnorjJaH (".link_sutra("1.3.58").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >नानोर्ज्ञः (१.३.५८) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* pratyAGbhyAM zruvaH (1.3.59) */
elseif ( in_array($us,array("prati","A")) && $first==="Sru" && $sanAdi==="san")
{
    $suffix=$tis;
    echo "<p class = st >By pratyAGbhyAM zruvaH (".link_sutra("1.3.59").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >प्रत्याङ्भ्यां श्रुवः (१.३.५९) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* jJAzrusmRdRzAM sanaH (1.3.57) */
elseif ( $_GET['cond37']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By jJAzrusmRdRzAM sanaH (".link_sutra("1.3.57").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >ज्ञाश्रुस्मृदृशां सनः (१.३.५७) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* zadeH zitaH (1.3.60) */
// I have taken these five lakAras because they have vikaraNa pratyaya 'zap' which qualifies for this sUtra.
elseif ( in_array($first,array("Sadx!")) && in_array($lakAra,array("law","low","laN","viDiliN","sArvaDAtukalew")))
{
    $suffix=$tis;
    echo "<p class = st >By zadeH zitaH (".link_sutra("1.3.60").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >शदेः शितः (१.३.६०) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* mriyaterluGliGozca (1.3.61) */
elseif ( in_array($first,array("mfN")) && in_array($lakAra,array("law","low","laN","viDiliN","sArvaDAtukalew","ASIrliN","luN")))
{
    $suffix=$tis;
    echo "<p class = st >By mriyaterluGliGozca (".link_sutra("1.3.61").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >म्रियतेर्लुङ्लिङोश्च (१.३.६१) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* pUrvavatsanaH (1.3.62) */
// Pending. san is not taught yet. So difficult to code right now. san will be treated later on.
/* Ampratyayavat kRJo'nuprayogasya (1.3.63) */
// Pending. Right now focus is on bare verbs. Am etc will be dealt with later.
/* NeraNau yatkarma Nau cetsa kartA'nAdhyAne (1.3.67) */
// This will need not less than a PhD. Nagesha bhatta seems to have written a book on this subject (NeraNivAdArthaH). So leaving it right now.
/* samaH kSNuvaH (1.3.65) */
elseif ( in_array($first,array("kzRu")) && $us==="sam")
{
    $suffix=$taG;
    echo "<p class = st >By samaH kSNuvaH (".link_sutra("1.3.65").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >समः क्ष्णुवः (१.३.६५) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* bhujo'navane (1.3.66) */
elseif ( $_GET['cond38']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By bhujo'navane (".link_sutra("1.3.66").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >भुजोऽनवने (१.३.६६) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* bhIsmyorhetubhaye (1.3.68) */
elseif ( $_GET['cond39']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By bhIsmyorhetubhaye (".link_sutra("1.3.68").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >भीस्मयोर्हेतुभये (१.३.६८) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* gRdhivaJcyoH pralambhane (1.3.69) */
elseif ( $_GET['cond40']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By gRdhivaJcyoH pralambhane (".link_sutra("1.3.69").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >गृधिवञ्च्योः प्रलम्भने (१.३.६९) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* liyaH sammAnanazAlInIkaraNayozca (1.3.70) */
elseif ( $_GET['cond41']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By liyaH sammAnanazAlInIkaraNayozca (".link_sutra("1.3.70").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >लियः सम्माननशालीनीकरणयोश्च (१.३.७०) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* mithyopapadAt kRJo'bhyAse (1.3.71) */
elseif ( $_GET['cond42']==="1")
{
    $suffix=$taG;
    echo "<p class = st >By mithyopapadAt kRJo'bhyAse (".link_sutra("1.3.71").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >मिथ्योपपदात्‌ कृञोऽभ्यासे (१.३.७१) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* nervizaH (1.3.47), parivyavebhyaH kriyaH (1.3.18), viparAbhyAM jeH (1.3.19), krIDo'nusaMparibhyazca (1.3.21), samavaparivibhyaH sthaH (1.3.22), udvibhyAM tapaH (1.3.27), AGo yamahanaH (1.3.28), samo gamyRcCipracCisvarAyartishruvidibhyaH (1.3.29), nisamupavibhyo hvaH (1.3.30), avAdgraH (1.3.51), jJAzRsmRdRzAM sanaH (1.3.57) */
elseif ( in_array($us.$first,$toatmane) )
{
    $suffix=$taG;
    for($i=0;$i<count($toatmane);$i++)
    {
        if($us.$first===$toatmane[$i])
        {
            echo "<p class = st >By ".$sutraenglish[$i]." :</p>\n"; 
            echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
            echo "<p class = st >".$sutradeva[$i]." :</p>\n";
            echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
            echo "<hr>\n";                    
        }
    }
    $atmanepada=1;
}
/* vyAGparibhyo ramaH (1.3.83) */
elseif ( ends(array($first),array("ramu!"),2) && in_array($us,array("vi","A","pari",)) && $vAcya==="kartR")
{
    $suffix=$tis;
    echo "<p class = st >By vyAGparibhyo ramaH (".link_sutra("1.3.83").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >व्याङ्परिभ्यो रमः (१.३.८३) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* upAcca (1.3.84) */
elseif ( $_GET['cond44']==="2")
{
    $suffix=$tis;
    echo "<p class = st >By upAcca (".link_sutra("1.3.84").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >उपाच्च (१.३.८४) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* vibhASA'karmakAt (1.3.74) */
elseif ( $_GET['cond44']==='1')
{
    $suffix=$tiG;
    echo "<p class = st >By vibhASA'karmakAt (".link_sutra("1.3.74").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates optional parasmaipada.</p>\n";
    echo "<p class = st >विभाषाऽकर्मकात्‌ (१.३.७४) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण वैभाषिकं परस्मैपदं विधीयते ।</p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* Nicazca (1.3.74) */
elseif ( $sanAdi==="Ric")
{
    $suffix=$tiG;
    echo "<p class = st >By Nicazca (".link_sutra("1.3.74").") :</p>\n"; 
    echo "<p class = hn >When the fruit of the action goes to the doer, this dhAtu takes Atmanepada. Otherwise, it takes parasmaipada. vibhASopapadena pratIyamAne (".link_sutra("1.3.77").") makes the Atmanepada form optional if the upapada implies the kartRabhiprAya kriyAphala.</p>\n";
    echo "<p class = st >णिचश्चः (१.३.७४) :</p>\n";
    echo "<p class = hn >क्रिया का फल जब कर्ता को मिलता है, तब इस धातु से आत्ममनेपद होता है । अन्यथा परस्मैपद होता है । विभाषोपपदेन प्रतीयमाने (१.३.७७) से यदि उपपद से कर्त्रभिप्राय क्रियाफल प्रतीयमान है तो आत्मनेपद विभाषा होता है ।</p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* AkusmAdAtmanepadinaH (dhAtupATha) */
elseif ( $verbset==='curAdi' && ends(array($first),$AkusmIya,4) )
{
    $suffix=$taG;
    echo "<p class = st >By AkusmAdAtmanepadinaH (dhAtupAThaH) :</p>\n"; 
    echo "<p class = st >आकुस्मादात्मनेपदिनः (धातुपाठः) :</p>\n";
    echo "<hr>\n";
    $atmanepada=1;
}
/* AkusmAdAtmanepadinaH (dhAtupATha) */
elseif ( $verbset==="none"  && ends(array($first),$AkusmIya,4))
{
    $suffix=$taG;
    echo "<p class = st >By AkusmAdAtmanepadinaH (dhAtupAThaH) :</p>\n"; 
    echo "<p class = st >आकुस्मादात्मनेपदिनः (धातुपाठः) :</p>\n";
    echo "<hr>\n";
    $atmanepada=1;
}
/* AgarvAdAtmanepadinaH (dhAtupATha) */
elseif ( $verbset==='curAdi' && ends(array($first),$AgarvIya,4) )
{
    $suffix=$taG;
    echo "<p class = st >By AgarvAdAtmanepadinaH (dhAtupATha) :</p>\n"; 
    echo "<p class = st >आगर्वादात्मनेपदिनः (धातुपाठः) :</p>\n";
    echo "<hr>\n";
    $atmanepada=1;
}
/* AgarvAdAtmanepadinaH (dhAtupATha) */
elseif ( $verbset==="none"  && ends(array($first),$AgarvIya,4))
{
    $suffix=$taG;
    echo "<p class = st >By AgarvAdAtmanepadinaH (dhAtupATha) :</p>\n"; 
    echo "<p class = st >आगर्वादात्मनेपदिनः (धातुपाठः) :</p>\n";
    echo "<hr>\n";
    $atmanepada=1;
}
/* Nicazca (1.3.74) */
elseif ( $verbset==='curAdi' )
{
    $suffix=$tiG;
    echo "<p class = st >By Nicazca (".link_sutra("1.3.74").") :</p>\n"; 
    echo "<p class = hn >When the fruit of the action goes to the doer, this dhAtu takes Atmanepada. Otherwise, it takes parasmaipada. vibhASopapadena pratIyamAne (".link_sutra("1.3.77").") makes the Atmanepada form optional if the upapada implies the kartRabhiprAya kriyAphala.</p>\n";
    echo "<p class = st >णिचश्चः (१.३.७४) :</p>\n";
    echo "<p class = hn >क्रिया का फल जब कर्ता को मिलता है, तब इस धातु से आत्ममनेपद होता है । अन्यथा परस्मैपद होता है । विभाषोपपदेन प्रतीयमाने (१.३.७७) से यदि उपपद से कर्त्रभिप्राय क्रियाफल प्रतीयमान है तो आत्मनेपद विभाषा होता है ।</p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* Nicazca (1.3.74) */
elseif ( ends(array($first),$curAdi,4) && $verbset==="none" )
{
    $suffix=$tiG;
    echo "<p class = st >By Nicazca (".link_sutra("1.3.74").") :</p>\n"; 
    echo "<p class = hn >When the fruit of the action goes to the doer, this dhAtu takes Atmanepada. Otherwise, it takes parasmaipada. vibhASopapadena pratIyamAne (".link_sutra("1.3.77").") makes the Atmanepada form optional if the upapada implies the kartRabhiprAya kriyAphala.</p>\n";
    echo "<p class = st >णिचश्चः (१.३.७४) :</p>\n";
    echo "<p class = hn >क्रिया का फल जब कर्ता को मिलता है, तब इस धातु से आत्ममनेपद होता है । अन्यथा परस्मैपद होता है । विभाषोपपदेन प्रतीयमाने (१.३.७७) से यदि उपपद से कर्त्रभिप्राय क्रियाफल प्रतीयमान है तो आत्मनेपद विभाषा होता है ।</p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* dyudbhyo luGi (1.3.91) */
elseif ( ends(array($first),array("dyuta!","SvitA!","YimidA!","midA!","YizvidA!","zvidA!","svidA!","YikzvidA!","kzvidA!","ruca!","Guwa!","ruwa!","luwa!","luWa!","SuBa!","kzuBa!","RaBa!","naBa!","tuBa!","sransu!","sraMsu!","Dvansu!","DvaMsu!","Bransu!","BraMsu!","BranSu!","BraMSu!","sranBu!","sraMBu!","sramBu!","vftu!","vrDu!","SfDu!","syandU!","kfpU!","kxpU!"),4) && $vAcya==="kartR" && $lakAra==="luN")
{
    $suffix=$tiG;
    echo "<p class = st >By dyudbhyo luGi (".link_sutra("1.3.91").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates optional parasmaipada.</p>\n";
    echo "<p class = st >द्युद्भ्यो लुङि (१.३.९१) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण वैभाषिकं परस्मैपदं विधीयते ।</p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* vRdbhyaH syasanoH (1.3.92) */
// san is pending. lRluToH syAtAsI - got the lakAras from here.
elseif ( ends(array($first),array("vftu!","vrDu!","SfDu!","syandU!","kfpU!","kxpU!"),4) && $vAcya==="kartR" && in_array($lakAra,array("lfN","lfw")))
{
    $suffix=$tiG;
    echo "<p class = st >By vRdbhyaH syasanoH (".link_sutra("1.3.92").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates optional parasmaipada.</p>\n";
    echo "<p class = st >वृद्भ्यः स्यस्यनोः (१.३.९२) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण वैभाषिकं परस्मैपदं विधीयते ।</p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* luTi ca klRpaH (1.3.93) */
elseif ( ends(array($first),array("kxpU!"),4) && $vAcya==="kartR" && in_array($lakAra,array("luw")))
{
    $suffix=$tiG;
    echo "<p class = st >By luTi ca klRpaH (".link_sutra("1.3.93").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates optional parasmaipada.</p>\n";
    echo "<p class = st >लुटि च कॢपः (१.३.९३) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण वैभाषिकं परस्मैपदं विधीयते ।</p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* AziSi nAtha iti vAcyam (vA) */
elseif ( ends(array($first),array("nATf!"),4) )
{
    $suffix=$tiG;
    echo "<p class = st >By AziSi nAtha iti vAcyam (vA) :</p>\n"; 
    echo "<p class = hn >When this verb is used in the sense of 'AziS', then only it takes Atmanepada, otherwise it takes parasmaipada.</p>\n";
    echo "<p class = st >आशिषि नाथ इति वाच्यम्‌ (वा) :</p>\n";
    echo "<p class = hn >अस्याशिश्येवात्मनेपदं स्यात्‌ । </p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* anudAttaGita Atmanepadam (1.3.12) */
elseif ( (ends(array($first),$anudAttetverbs,4) || ends(array($first),$Gitverbs,4) || ends(array($first),array("fta!"),4) ) && scrape1($number,8,5,1)===array("A") && $pada==="pratyaya" && $lakAra!=="" && $_GET['cond49']!=="1" ) // the exclusion is useful for the sanAdi pratyayas e.g. paRAya has parasmai, paRa! has Atmanepadatva. See http://sanskritdocuments.org/learning_tools/ashtadhyayi/vyakhya/3/3.1.28.htm for clarification. Second addition is for RterIyaG, Atmanepada because of IyaG pratyaya.
{
    $suffix=$taG;
    echo "<p class = st >By anudAttaGita Atmanepadam (".link_sutra("1.3.12").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >अनुदात्तङित आत्मनेपदम्‌ (१.३.१२) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";
    $atmanepada=1;
}
/* vibhASopapadena pratIyamAne (1.3.77) */
// This sUtra is intervowen in 1.3.72 to 1.3.76 as optional form.
/* apAdvadaH (1.3.73) */
elseif ( $first==="vada!" && $us==="apa" && $pada==="pratyaya" && $lakAra!=="")
{
    $suffix=$tiG;
    echo "<p class = st >By apAdvadaH (".link_sutra("1.3.73").") :</p>\n"; 
    echo "<p class = hn >When the fruit of the action goes to the doer, this dhAtu takes Atmanepada. Otherwise, it takes parasmaipada. vibhASopapadena pratIyamAne (".link_sutra("1.3.77").") makes the Atmanepada form optional if the upapada implies the kartRabhiprAya kriyAphala.</p>\n";
    echo "<p class = st >अपाद्वदः (१.३.७३) :</p>\n";
    echo "<p class = hn >क्रिया का फल जब कर्ता को मिलता है, तब इस धातु से आत्ममनेपद होता है । अन्यथा परस्मैपद होता है । विभाषोपपदेन प्रतीयमाने (१.३.७७) से यदि उपपद से कर्त्रभिप्राय क्रियाफल प्रतीयमान है तो आत्मनेपद विभाषा होता है ।</p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* samudAGbhyo yamo'granthe (1.3.75) */
elseif ( $_GET['cond43']==="2" && $pada==="pratyaya" && $lakAra!=="")
{
    $suffix=$tiG;
    echo "<p class = st >By samudAGbhyo yamo'granthe (".link_sutra("1.3.75").") :</p>\n"; 
    echo "<p class = hn >When the fruit of the action goes to the doer, this dhAtu takes Atmanepada. Otherwise, it takes parasmaipada. vibhASopapadena pratIyamAne (".link_sutra("1.3.77").") makes the Atmanepada form optional if the upapada implies the kartRabhiprAya kriyAphala. </p>\n";
    echo "<p class = st >समुदाङ्भ्यो यमोऽग्रन्थे (१.३.७५) :</p>\n";
    echo "<p class = hn >क्रिया का फल जब कर्ता को मिलता है, तब इस धातु से आत्ममनेपद होता है । अन्यथा परस्मैपद होता है । विभाषोपपदेन प्रतीयमाने (१.३.७७) से यदि उपपद से कर्त्रभिप्राय क्रियाफल प्रतीयमान है तो आत्मनेपद विभाषा होता है । </p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* anupasargAjjJaH (1.3.76) */
elseif ( ends(array($first),array("jYA"),2) && $pada==="pratyaya" && $lakAra!=="")
{
    $suffix=$tiG;
    echo "<p class = st >By anupasargAjjJaH (".link_sutra("1.3.76").") :</p>\n"; 
    echo "<p class = hn >When the fruit of the action goes to the doer, this dhAtu takes Atmanepada. Otherwise, it takes parasmaipada. vibhASopapadena pratIyamAne (".link_sutra("1.3.77").") makes the Atmanepada form optional if the upapada implies the kartRabhiprAya kriyAphala. </p>\n";
    echo "<p class = st >अनुपसर्गाज्ज्ञः (१.३.७६) :</p>\n";
    echo "<p class = hn >क्रिया का फल जब कर्ता को मिलता है, तब इस धातु से आत्ममनेपद होता है । अन्यथा परस्मैपद होता है । विभाषोपपदेन प्रतीयमाने (१.३.७७) से यदि उपपद से कर्त्रभिप्राय क्रियाफल प्रतीयमान है तो आत्मनेपद विभाषा होता है । </p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* abhipratyatibhyaH kSipaH (1.3.80) */
elseif ( ends(array($first),array("kzipa!"),2) && in_array($us,array("aBi","prati","ati"))$vAcya==="kartR")
{
    $suffix=$tis;
    echo "<p class = st >By abhipratyatibhyaH kSipaH (".link_sutra("1.3.80").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >अभिप्रत्यतिभ्यः क्षिपः (१.३.८०) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* prAdvahaH (1.3.81) */
elseif ( ends(array($first),array("vaha!"),2) && $us==="pra" && $vAcya==="kartR")
{
    $suffix=$tis;
    echo "<p class = st >By prAdvahaH (".link_sutra("1.3.81").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >प्राद्वहः (१.३.८१) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* parermRSaH (1.3.82) */
elseif ( ends(array($first),array("mfza!"),2) && $us==="pari" && $vAcya==="kartR")
{
    $suffix=$tis;
    echo "<p class = st >By parermRSaH (".link_sutra("1.3.82").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >परेर्मृषः (१.३.८२) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* budhayudhanazajaneGprudrusrubhyo NeH (1.3.86) */
// NeH coding pending.
elseif ( ends(array($first),array("buDa!","yuDa!","naSa!","iN","pru","dru","sru"),4) && $vAcya==="kartR")
{
    $suffix=$tis;
    echo "<p class = st >By budhayudhanazajaneGprudrusrubhyo NeH (".link_sutra("1.3.86").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >बुधयुधनशजनेङ्प्रुद्रुस्रुभ्यो णेः (१.३.८६) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* na pAdamyAGyamAGyasaparimuharucinRtivadavasaH (1.3.89) */
elseif ( ends(array($us.$first),array("pA","Ayama!","damu!","Ayasu!","parimuha!","ruca!","nftI!","vada!","vasa!"),4) && $vAcya==="kartR" && in_array($sanAdi,array("Ric","RiN")))
{
    $suffix=$taG;
    echo "<p class = st >By na pAdamyAGyamAGyasaparimuharucinRtivadavasaH (".link_sutra("1.3.89").") and Nicazca (".link_sutra("1.3.74").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates Atmanepadam. </p>\n";
    echo "<p class = st >पादम्याङ्‍यमाङ्‍यसपरिमुहरुचिनृतिवदवसः (१.३.८९) तथा णिचश्च (१.३.७४) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेणात्मनेपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $atmanepada=1;
}
/* vA kyaSaH (1.3.90) */
// right now very coarse way of finding out kyaS. Will have to revisit.
elseif ( ends(array($first),array("Aya"),1) && $pada==="pratyaya" && $lakAra!=="")
{
    $suffix=$tiG;
    echo "<p class = st >By vA kyaSaH (".link_sutra("1.3.90").") :</p>\n"; 
    echo "<p class = hn >These roots get optional parasmaipada. </p>\n";
    echo "<p class = st >वा क्यषः (१.३.९०) :</p>\n";
    echo "<p class = hn >वैभाषिकं परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* nigaraNacalanArthebhyazca (1.3.87) */
// NeH coding pending.
elseif ( ends(array($first),array("nigF","aSa!","Buja!","cala!","cupa!","kapi!",),4) && $vAcya==="kartR")
{
    $suffix=$tis;
    echo "<p class = st >By nigaraNacalanArthebhyazca (".link_sutra("1.3.87").") :</p>\n"; 
    echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >निगरणचलनार्थेभ्यश्च (१.३.८७) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";                    
    $parasmaipada=1;
}
/* aNAvakarmakAccittavatkartRkAt (1.3.88) */
// Pending. Ni etc are very confusing at this stage. Will think later.
// budha... is an exception to cittavat part (kAzikA)
/* svaritaJitaH kartrabhiprAye kriyAphale (1.3.72) */
elseif ( ends(array($first),$ubhaya,4) && $pada==="pratyaya" && $lakAra!=="")
{
    $suffix=$tiG;
    echo "<p class = st >By svaritaJitaH kartrabhiprAye kriyAphale (".link_sutra("1.3.72").") :</p>\n"; 
    echo "<p class = hn >When the fruit of the action goes to the doer, these dhAtus take Atmanepada. Otherwise, they take parasmaipada. vibhASopapadena pratIyamAne (".link_sutra("1.3.77").") makes the Atmanepada form optional if the upapada implies the kartRabhiprAya kriyAphala. </p>\n";
    echo "<p class = st >स्वरितञितः कर्त्रभिप्राये क्रियाफले (१.३.७२) :</p>\n";
    echo "<p class = hn >क्रिया का फल जब कर्ता को मिलता है, तब इन धातुओं से आत्ममनेपद होता है । अन्यथा परस्मैपद होता है । विभाषोपपदेन प्रतीयमाने (१.३.७७) से यदि उपपद से कर्त्रभिप्राय क्रियाफल प्रतीयमान है तो आत्मनेपद विभाषा होता है । </p>\n";
    echo "<hr>\n";
    $ubhayapada=1;
}
/* In case the user has selected some gaNa, the pada has to correspond to that gaNa */
elseif($verbset!=="none")
{
    if (verb_padafinder($first)===array("u"))
    {
        $suffix=$tiG;
        $ubhayapada=1;
    echo "<p class = st >ubhayapadI :</p>\n"; 
    echo "<p class = st >उभयपदी :</p>\n";
    echo "<hr>\n";
    }
    elseif (verb_padafinder($first)===array("A"))
    {
        $suffix=$taG;
        $atmanepada=1;
    echo "<p class = st >AtmanepadI :</p>\n"; 
    echo "<p class = st >आत्मनेपदी :</p>\n";
    echo "<hr>\n";
    }
    elseif (verb_padafinder($first)===array("pa"))
    {
        $suffix=$tis;
        $parasmaipada=1;
    echo "<p class = st >parasmaipadI :</p>\n"; 
    echo "<p class = st >परस्मैपदी :</p>\n";
    echo "<hr>\n";
    }
}
/* zeSAt kartari parasmaipadam (1.3.79) */
elseif ( $pada==="pratyaya" && $lakAra!=="")
{
    $suffix=$tis;
    echo "<p class = st >zeSAt kartari parasmaipadam (".link_sutra("1.3.79").") :</p>\n"; 
            echo "<p class = hn >This sUtra mandates parasmaipadam. </p>\n";
    echo "<p class = st >शेषात्‌ कर्तरि परस्मैपदम्‌ (१.३.७९) :</p>\n";
            echo "<p class = hn >अनेन सूत्रेण परस्मैपदं विधीयते । </p>\n";
    echo "<hr>\n";
    $parasmaipada=1;
}
/* idAgama decision */
    //$verb_without_anubandha=scrape($first,0,2,1)[0];  // ejf
    $temp = scrape1($first,0,2,1); 
    $verb_without_anubandha=$temp[0];
if (in_array($lakAra,array("lfw","lfN","luw","ASIrliN","luN","liw","ArDaDAtukalew"))) // checking whether ArdhadhAtuka lakAra or not.
{
    if (anekAca($verb_without_anubandha) || $san===1 || $yaG===1 || $sanAdi==="Ric" )
    {
        $id_dhAtu="sew";
        echo "<p class = st >This is a 'seT' verb.</p>\n"; 
        echo "<p class = st >सेट्‌ धातुः </p>\n";
        echo "<hr>\n";
    }
     /* svaratisUtisUyatidhUJUdito vA (7.2.44) */
    elseif (ends(array($fo),array("svf","zUN","DUY"),4) || ends(array($fo),$Uditverbs,4))
    {
        $id_dhAtu="vew";
        echo "<p class = st >By svaratisUtisUyatidhUJUdito vA (".link_sutra("7.2.44").") :</p>\n"; 
        echo "<p class = hn >This sUtra makes this a veT dhAtu. </p>\n"; 
        echo "<p class = st >स्वरतिसूतिसूयतिधूञूदितो वा (७.२.४४) :</p>\n";
        echo "<p class = hn >अनेन सूत्रेण वेट्त्वं विधीयते ।</p>\n";
        echo "<hr>\n";        
    }
   /* RddhanoH sye (7.2.70) */
    elseif ( (ends(array($verb_without_anubandha),array("f",),1) || ends(array($fo),array("hana!",),4))&& in_array($lakAra,array("lfw","lfN")))
    {
        $id_dhAtu="sew";
        echo "<p class = st >By RddhanoH sye (".link_sutra("7.2.70").") :</p>\n"; 
        echo "<p class = hn >This sUtra makes this a seT dhAtu. </p>\n"; 
        echo "<p class = st >ऋद्धनोः स्ये (७.२.७०) :</p>\n";
        echo "<p class = hn >अनेन सूत्रेण सेट्त्वं विधीयते ।</p>\n";
        echo "<hr>\n";        
    }
    /* se'sici kRtacRtacCRdatRdanRtaH (7.2.57) */
    elseif ( ends(array($fo),array("kftI!","cfta!","Cfda!","tfda!","nfta!",),4) && in_array($lakAra,array("lfw","lfN")))
    {
        $id_dhAtu="vew";
        echo "<p class = st >By se'sici kRtacRtacCRdatRdanRtaH (".link_sutra("7.2.57").") :</p>\n"; 
        echo "<p class = hn >This sUtra makes this a veT dhAtu. </p>\n"; 
        echo "<p class = st >सेऽसिचि कृतचृतच्छृदतृदनृतः (७.२.५७) :</p>\n";
        echo "<p class = hn >अनेन सूत्रेण वेट्त्वं विधीयते ।</p>\n";
        echo "<hr>\n";
    }
    /* gameriT parasmaipadeSu (7.2.58) */
    elseif ( ends(array($fo),array("gamx!",),4) && in_array($lakAra,array("lfw","lfN")) && $parasmaipada===1)
    {
        $id_dhAtu="sew";
        echo "<p class = st >By gameriT parasmaipadeSu (".link_sutra("7.2.58").") :</p>\n"; 
        echo "<p class = hn >This sUtra makes this a seT dhAtu. </p>\n"; 
        echo "<p class = st >गमेरिट्‌ परस्मैपदेषु (७.२.५८) :</p>\n";
        echo "<p class = hn >अनेन सूत्रेण सेट्त्वं विधीयते ।</p>\n";
        echo "<hr>\n";
    }
    /* na vRdbhyazcaturbhyaH (7.2.59) */
    elseif ( ends(array($fo),array("vftu!","vfDu!","SfDu!","syandU!",),4) && in_array($lakAra,array("lfw","lfN")) )
    {
		$ubhayapada=1;
        $id_dhAtu="aniw";
		$suffix = $tiG;
        echo "<p class = st >By na vRdbhyazcaturbhyaH (".link_sutra("7.2.59").") :</p>\n"; 
        echo "<p class = hn >This sUtra makes this an aniT dhAtu and also parasmaipadI. </p>\n"; 
        echo "<p class = st >न वृद्भ्यश्चतुर्भ्यः (७.२.५९) :</p>\n";
        echo "<p class = hn >अनेन सूत्रेण अनिट्त्वं परस्मैपदित्वं च विधीयेते ।</p>\n";
        echo "<hr>\n";
    }
    /* tAsi ca klRpaH (7.2.60) */
    // sakArAdi. tAsi done elsewhere.
    elseif ( ends(array($fo),array("kxpa!",),4) && in_array($lakAra,array("lfw","lfN")) && $parasmaipada===1)
    {
        $id_dhAtu="aniw";
        echo "<p class = st >By tAsi ca klRpaH (".link_sutra("7.2.60").") :</p>\n"; 
        echo "<p class = hn >This sUtra makes this an aniT dhAtu. </p>\n"; 
        echo "<p class = st >तासि च क्लृपः (७.२.६०) :</p>\n";
        echo "<p class = hn >अनेन सूत्रेण अनिट्त्वं विधीयते ।</p>\n";
        echo "<hr>\n";
    }
    /* radhAdibhyazca (7.2.45) */
    elseif (ends(array($fo),array("raDa!","RaSa!","tfpa!","dfpa!","druha!","muha!","zRuha!","zRiha!"),4) )
    {
        $id_dhAtu="vew";
        echo "<p class = st >By radhAdibhyazca (".link_sutra("7.2.45").") :</p>\n"; 
        echo "<p class = hn >This sUtra makes this a veT dhAtu. </p>\n"; 
        echo "<p class = st >रधादिभ्यश्च (७.२.४५) :</p>\n";
        echo "<p class = hn >अनेन सूत्रेण वेट्त्वं विधीयते ।</p>\n";
        echo "<hr>\n";        
    }
    /* niraH kuSaH (7.2.46) */
    elseif (ends(array($fo),array("kuza!"),4) && $us==="nis" )
    {
        $id_dhAtu="vew";
        echo "<p class = st >By niraH kuSaH (".link_sutra("7.2.46").") :</p>\n"; 
        echo "<p class = hn >This sUtra makes this a veT dhAtu. </p>\n"; 
        echo "<p class = st >निरः कुषः (७.२.४६) :</p>\n";
        echo "<p class = hn >अनेन सूत्रेण वेट्त्वं विधीयते ।</p>\n";
        echo "<hr>\n";        
    }
	elseif (verb_itfinder($first)===array("sew"))
	{
		$id_dhAtu="sew";
		echo "<p class = st >This is a 'seT' verb.</p>\n"; 
		echo "<p class = st >सेट्‌ धातुः </p>\n";
		echo "<hr>\n";        
	}
	elseif (verb_itfinder($first)===array("aniw"))
	{
		$id_dhAtu="aniw";
		echo "<p class = st >This is an 'aniT' verb.</p>\n"; 
		echo "<p class = st >अनिट्‌ धातुः</p>\n";
		echo "<hr>\n";        
	}
}
/* a for loop for entering all sup pratyayas one by one. Sambuddhi is at the last after sup. */
//$sup1= array("su!","O","jas","am","Ow","Sas","wA","ByAm","Bis","Ne","ByAm","Byas","Nasi!","ByAm","Byas","Nas","os","Am","Ni","os","sup","su!","O","jas"); // the last three members are for sambodhana forms.
for ($w=0;$w<count($suffix);$w++) // running the loop till $sup1 is exhausted.
{
$second=$suffix[$w];    // defining the second word as su!, O, jas etc.
$id_original=$id_dhAtu;
$id_original_pratyaya=$id_pratyaya;
if(in_array($second,$tiG)||in_array($second,$sup))
{
$vibhakti=1;    
$us = $_GET["upasarga"]; // upasarga. Added on 14 Dec 2014.
$upasarga_joined=0;
}
/* Code for converting from IAST to SLP1 */
// defining IAST letters.
$iast = array("a","ā","i","ī","u","ū","ṛ","ṝ","ḷ","ḹ","e","ai","o","au","ṃ","ḥ","kh","ch","ṭh","th","ph","gh","jh","ḍh","dh","bh","ṅ","ñ","ṇ","k","c","ṭ","t","p","g","j","ḍ","d","b","n","m","y","r","l","v","s","h","ś","ṣ",);
// defining SLP1 letters.
$slp = array("a","A","i","I","u","U","f","F","x","X","e","E", "o","O", "M","H","K", "C",  "W", "T", "P","G", "J",  "Q", "D","B", "N","Y","R","k","c","w","t","p","g","j","q","d","b","n","m","y","r","l","v","s","h","S","z",);
  if (preg_match('/[āĀīĪūŪṛṚṝṜḷḶḹḸṃṂḥḤṭṬḍḌṅṄñÑṇṆśŚṣṢV]/',$first) || preg_match('/[āĀīĪūŪṛṚṝṜḷḶḹḸṃṂḥḤṭṬḍḌṅṄñÑṇṆśŚṣṢV]/',$second)) // if there is IAST letters in the input, change them to SLP1
{
    $first = str_replace($iast,$slp,$first);
    $second = str_replace($iast,$slp,$second);
}
if ($tran === "IAST") // if the user says that the input is IAST - change it to SLP1.
{
     $first = str_replace($iast,$slp,$first);
    $second = str_replace($iast,$slp,$second);
}
/* Code for converting from devanagari - SLP1 */ 
//This is innocuous. Therefore even running without the selection in dropdown menu. 
$first = json_encode($first);
$first = str_replace("\u200d","",$first); // removing whitespace
$first = str_replace("\u200c","",$first); // removing whitespace
$first = json_decode($first);
$second = json_encode($second);
$second = str_replace("\u200d","",$second); // removing whitespace
$second = str_replace("\u200c","",$second); // removing whitespace
$second = json_decode($second);
$first = convert1($first); // converting to SLP1
$second = convert1($second); // converting to SLP1

$fo = $first; // remembering the original prakRti. Sometimes we need to know what was the original prakRti.
$so = $second; ; // remembering the original pratyayas. Sometimes we need to know what was the original pratyaya.
/* displaying the data back to the user */
if ($sanAdi!=="")
{
echo "<p class = red >".convert($lakAra) ." लकार<br>".convert($fo)." + ".convert($sanAdi)." + ".convert($so)." <a href = tiGanta.html>Go Back</a></p>\n";    
}
else
{
echo "<p class = red >".convert($lakAra) ." लकार<br>".convert($fo)." + ".convert($so)." <a href = tiGanta.html>Go Back</a></p>\n";    
}
//echo "</br>\n";
/* for sambodhana vibhaktis display */
if ($w>20)
{
    echo "<p class = red >This is sambuddhi form.</p>\n";
    echo "</br>";
    if ($w===21)
    {
    $sambuddhi=1;        // 0 - no sambuddhi. 1 - sambuddhi.
    }
    else 
    {
        $sambuddhi=0;
    }
} 
else 
{
    $sambuddhi=0;
}

/* preprocessing for the sup pratyayas. */
// Joining the two input words 
if ($second === "") // if there is no pratyaya. This doesn't happen in subanta generation. But kept it for other uses.
{
    $input = ltrim(chop($first));
}
elseif ($first === "") // if there is no prakRti. This doesn't happen in subanta generation. But kept it for other uses, like sandhi etc.
{
    $input = ltrim(chop($second));
}
else // this option is used for subanta generation. $input is 'prakRti'+'pratyaya'.
{
$input = ltrim(chop($first."+".$sanAdi."+".$second));
$input = str_replace("++","+",$input);
}
 /* main coding part starts from here. Based on Siddhantakaumudi text. */
    
/* Defining an array $text. */
//Here we will store the output after the process of sUtras. The first member is $input. 
// The reason behind creating an array and not keeping it a string is - sometimes the sUtras give 2 / more optional outputs. In that case, it is impossible to manage the string. 
// Right now what will happen is that 1 member -> 2 members by some sUtra. For next sUtra, we will take these two members one by one and store their results in $text itself.
$text = array();
$text[] = $input; // Defining first member of the array as $input (combined words first and second)

/* special error message for vaca! dhAtu pra.pu.ba.va. */
if ($so==="Ji" && ends(array($fo),array("vaca!"),4)  && $verbset==="adAdi")
{
echo "<p class = red >vac dhAtu doesn't have prathama puruSa bahuvacana form. :</p>\n";
echo "<p class = red >वच्‌ धातु का प्रथम पुरुष बहुवचन का रूप नहीं होता है ।</p>\n";
display(0);
}
/* na vRdbhyazcaturbhyaH (7.2.59) */
elseif ( ends(array($fo),array("vftu!","vfDu!","SfDu!","syandU!",),4) && in_array($lakAra,array("lfw","lfN")) && in_array($so,$taG))
{
	$atmanepada=1;
	$id_dhAtu="sew";
	echo "<p class = sa >By na vRdbhyazcaturbhyaH (".link_sutra("7.2.59").") :</p>\n"; 
	echo "<p class = hn >Other than parasmaipada, this dhAtu takes iDAgama.</p>\n"; 
	echo "<p class = sa >न वृद्भ्यश्चतुर्भ्यः (७.२.५९) :</p>\n";
	display(0);
}



/* tiGzitsArvadhAtukam (3.4.113) */ 
// for tiG.
if (sub(array("+"),$tiG,blank(0),0) && in_array($lakAra,array("law","low","laN","viDiliN","sArvaDAtukalew")) )
{
    $sarvadhatuka=1;
    echo "<p class = pa >By tiGzitsArvadhAtukam (".link_sutra("3.4.113").") 1:</p>\n"; 
    echo "<p class = pa >तिङ्शित्सार्वधातुकम्‌ (३.४.११३) :</p>\n";
    display(0);    
}
/* ArdhadhAtukaM zeSaH (3.4.114) */ 
if (sub(array("+"),$tiG,blank(0),0) && in_array($lakAra,array("lfw","lfN","luw","ASIrliN","luN","ArDaDAtukalew")) )
{
    $ardhadhatuka=1;
    echo "<p class = pa >By ArdhadhAtukaM zeSaH (".link_sutra("3.4.114").") :</p>\n"; 
    echo "<p class = pa >आर्धधातुकं शेषः (३.४.११४) :</p>\n";
    display(0);    
}
/* liT ca (3.4.115) */ 
if (sub(array("+"),$tiG,blank(0),0) && in_array($lakAra,array("liw")) )
{
    $ardhadhatuka=1;
    echo "<p class = pa >By liT ca (".link_sutra("3.4.115").") :</p>\n"; 
    echo "<p class = pa >लिट्‌ च (३.४.११५) :</p>\n";
    display(0);    
}
/* dhAtvAdeza before ArdhadhAtuka pratyayas as per sahajabodha 2 p. 62 */
if (in_array($lakAra,$ArdhadhAtuka_lakAra))
{
    /* asterbhUH (2.4.52) */ 
    if (ends(array($fo),array("asa!"),4) && $verbset==="adAdi" )
    {
        $text=pr2(array("asa!"),array("+"),$tiG,array("BU"),array("+"),$tiG,$text);
        echo "<p class = sa >By asterbhUH (".link_sutra("2.4.52").") :</p>\n"; 
        echo "<p class = sa >अस्तेर्भूः (२.४.५२) :</p>\n";
        display(0);    
    }
    /* bruvo vaciH (2.4.53) */ 
    if (ends(array($fo),array("brUY"),4))
    {
        $text=pr2(array("brUY"),array("+"),$tiG,array("vaca!"),array("+"),$tiG,$text);
        echo "<p class = sa >By bruvo vaciH (".link_sutra("2.4.53").") :</p>\n"; 
        echo "<p class = sa >ब्रुवो वचिः (२.४.५३) :</p>\n";
        display(0);    
    }
    /* gAG liTi (2.4.49) */ 
    if (ends(array($fo),array("iN"),4) && $lakAra==="liw")
    {
        $text=three(array("iN"),array("+"),$tiG,array("gAN"),array("+"),$tiG,0);
        echo "<p class = sa >By gAG liTi (".link_sutra("2.4.49").") :</p>\n"; 
        echo "<p class = sa >गाङ्‌ लिटि (२.४.४९) :</p>\n";
        display(0);
    }
    /* liDyaGozca (6.1.29) */ 
    if (ends(array($fo),array("o!pyAyI!"),4) && ($lakAra==="liw"||$sanAdi==="yaG"))
    {
        $text=three(array("o!pyAyI!"),array("+"),$tiG,array("pI"),array("+"),$tiG,0);
        echo "<p class = sa >By liDyaGozca (".link_sutra("6.1.29").") :</p>\n"; 
        echo "<p class = sa >लिड्यङोश्च (६.१.२९) :</p>\n";
        display(0);
    }
    /* cAyaH kI (6.1.35) */ 
    if (ends(array($fo),array("cAyf!"),4) && ($lakAra==="liw"||$sanAdi==="yaG") && $veda===1)
    {
        $text=three(array("cAyf!"),array("+"),$tiG,array("kI"),array("+"),$tiG,0);
        echo "<p class = sa >By cAyaH kI (".link_sutra("6.1.35").") :</p>\n"; 
        echo "<p class = sa >चायः की (६.१.३५) :</p>\n";
        display(0);
    }
    /* vibhASA zveH (6.1.30) */ 
    if (ends(array($fo),array("wuo!Svi"),4) && ($lakAra==="liw"||$sanAdi==="yaG"))
    {
        $text=three(array("wuo!Svi"),array("+"),$tiG,array("Su"),array("+"),$tiG,1);
        echo "<p class = sa >By vibhASA zveH (".link_sutra("6.1.30").") :</p>\n"; 
        echo "<p class = sa >विभाषा श्वेः (६.१.३०) :</p>\n";
        display(0);
    }
    /* abhyastasya ca (6.1.33) */ 
	// Coded only for liT. Other cases if possible are pending.
    if (ends(array($fo),array("hveY"),4) && $lakAra==="liw")
    {
        $text=three(array("hveY"),array("+"),$tiG,array("hu"),array("+"),$tiG,0);
        echo "<p class = sa >By abhyastasya ca (".link_sutra("6.1.33").") :</p>\n"; 
        echo "<p class = sa >अभ्यस्तस्य च (६.१.३३) :</p>\n";
        display(0);
    }
    /* liTyanyatarasyAm (2.4.40) */ 
    if (ends(array($fo),array("ada!"),4) && $lakAra==="liw")
    {
        $text=three(array("ada!"),array("+"),$tiG,array("Gasx!"),array("+"),$tiG,1);
        echo "<p class = sa >By liTyanyatarasyAm (".link_sutra("2.4.40").") :</p>\n"; 
        echo "<p class = sa >लिट्यन्यतरस्याम्‌ (२.४.४०) :</p>\n";
        display(0);
    }
    /* luGsanorghaslR (2.4.37) */ 
    if (ends(array($fo),array("ada!"),4) && ($lakAra==="luN"||$san===1) )
    {
        $text=pr2(array("ada!"),array("+"),$tiG,array("Gasx!"),array("+"),$tiG,$text);
        echo "<p class = sa >By luGsanorghaslR (".link_sutra("2.4.37").") :</p>\n"; 
        echo "<p class = sa >लुङ्सनोर्घस्लृ (२.४.३७) :</p>\n";
        display(0);    
    }
    /* vA liTi (2.4.55) */ 
    if (ends(array($fo),array("cakzi!N"),4) && $lakAra==="liw")
    {
        $text=three(array("cakzi!N"),array("+"),$tiG,array("KyA"),array("+"),$tiG,1);
        echo "<p class = sa >By vA liTi (".link_sutra("2.4.55").") :</p>\n"; 
        echo "<p class = sa >वा लिटि (२.४.५५) :</p>\n";
        display(0);    
    }
    /* cakSiGaH khyAG (2.4.54) */ 
    elseif (ends(array($fo),array("cakzi!N"),4))
    {
        $text=pr2(array("cakzi!N"),array("+"),$tiG,array("KyA"),array("+"),$tiG,$text);
        echo "<p class = sa >By cakSiGaH khyAG (".link_sutra("2.4.54").") :</p>\n"; 
        echo "<p class = sa >चक्षिङः ख्याङ्‌ (२.४.५४) :</p>\n";
        display(0);    
    }
    /* ajervyaghaJapoH (2.4.56) */ 
    if (ends(array($fo),array("aja!"),4) && !sub(array("aja!"),array("+"),array("GaY","ap"),0))
    {
        $text=pr2(array("aja!"),array("+"),$tiG,array("vI"),array("+"),$tiG,$text);
        echo "<p class = sa >By ajervyaghaJapoH (".link_sutra("2.4.56)")." :</p>\n"; 
        echo "<p class = sa >अजेर्व्यघञपोः (२.४.५६) :</p>\n";
        display(0);    
    }
	/* iNo gA luGi (2.4.45) */ 
	if ( ends(array($fo),array("iR"),4) && in_array($lakAra,array("luN")) && !in_array($sanAdi,array("Ric","RiN")))
	{
		$text = two(array("iR"),array("+"),array("gA"),array("+"),0);
		echo "<p class = sa >By iNo gA luGi (".link_sutra("2.4.45").") :</p>\n"; 
		echo "<p class = sa >इणो गा लुङि (२.४.४५) :</p>\n";
		display(0);
	}
	/* luGi ca (2.4.43) */ 
	if ( ends(array($fo),array("hana!"),4) && in_array($lakAra,array("luN")) && in_array($so,$tis) && !in_array($sanAdi,array("Ric","RiN")) )
	{
		$text = two(array("hana!"),array("+"),array("vaDa"),array("+"),0);
		echo "<p class = sa >By luGi ca (".link_sutra("2.4.43").") :</p>\n"; 
		echo "<p class = sa >लुङि च (२.४.४३) :</p>\n";
		display(0);
		$id_dhAtu='sew'; // because vaDa is anekAc.
	}
	/* AtmanepadeSvanyatarasyAm (2.4.44) */ 
	if ( ends(array($fo),array("hana!"),4) && in_array($lakAra,array("luN")) && in_array($so,$taG) )
	{
		$text = two(array("hana!"),array("+"),array("vaDa!"),array("+"),1);
		echo "<p class = sa >By AtmanepadeSvanyatarasyAm (".link_sutra("2.4.44").") :</p>\n"; 
		echo "<p class = sa >आत्मनेपदेष्वन्यतरस्याम्‌ (२.४.४४) :</p>\n";
		display(0);
	}
	/* vibhASA luGlRGoH (2.4.50) */ 
	if ( ends(array($fo),array("iN"),4) && in_array($lakAra,array("luN","lfN")) && $Nit===0 && $Jit===0 && !in_array($sanAdi,array("Ric","RiN")))
	{
		$text = two(array("iN"),array("+"),array("gAN"),array("+"),1);
		echo "<p class = sa >By vibhASA luGlRGoH (".link_sutra("2.4.50").") :</p>\n"; 
		echo "<p class = sa >विभाषा लुङ्लृङोः (२.४.५०) :</p>\n";
		display(0);
	}
}
/* atideza sUtras for GidvadbhAva before ArdhadhAtuka. sahajabodha 2 p. 40 */
if (in_array($lakAra,$ArdhadhAtuka_lakAra))
{
    /* gAGkuTAdibhyo'JNinGit (1.2.1) */ 
    if ( ends(array($fo),array("iN"),4) && sub(array("gAN"),array("+"),blank(0),0) && in_array($lakAra,$ArdhadhAtuka_lakAra) && $Nit===0 && $Jit===0)
    {
        $itpratyaya=array_merge($itpratyaya,array("N"));
        $it=array_merge($it,array("N"));
        echo "<p class = pa >By gAGkuTAdibhyo'JNinGit (".link_sutra("1.2.1").") :</p>\n"; 
        echo "<p class = pa >गाङ्कुटादिभ्योऽञ्णिन्ङित्‌ (१.२.१) :</p>\n";
        display(0);    
    }
    /* gAGkuTAdibhyo'JNinGit (1.2.1) */ 
    if ( ((ends(array($fo),$tudAdi_kuTAdi,4) && ($verbset==="tudAdi" || $verbset==="none" )) || ends(array($fo),array("gAN"),4) ) && in_array($lakAra,$ArdhadhAtuka_lakAra) && $Nit===0 && $Jit===0)
    {
        $itpratyaya=array_merge($itpratyaya,array("N"));
        $it=array_merge($it,array("N"));
        echo "<p class = pa >By gAGkuTAdibhyo'JNinGit (".link_sutra("1.2.1").") :</p>\n"; 
        echo "<p class = pa >गाङ्कुटादिभ्योऽञ्णिन्ङित्‌ (१.२.१) :</p>\n";
        display(0);    
    }
    /* vyaceH kuTAditvamanasIti vaktavyam (vA) */ 
    if ( ends(array($fo),array("vyaca!"),4) && !sub(array("vyaca!"),array("+"),array("as"),0) && in_array($lakAra,$ArdhadhAtuka_lakAra) && $Nit===0 && $Jit===0)
    {
        $itpratyaya=array_merge($itpratyaya,array("N"));
        $it=array_merge($it,array("N"));
        echo "<p class = pa >By vyaceH kuTAditvamanasIti vaktavyam (vA) :</p>\n"; 
        echo "<p class = pa >वचेः कुटादित्वमनसीति वक्तव्यम्‌ (वा) :</p>\n";
        display(0);
    }
    /* vija iT (1.2.2) */ 
    if ( ends(array($fo),array("o!vijI!"),4) && $ardhadhatuka===1 && in_array($lakAra,$ArdhadhAtuka_lakAra) && $id_dhAtu==="sew" && $id_pratyaya==="sew" && $verbset==="tudAdi")
    {
        $itpratyaya=array_merge($itpratyaya,array("N"));
        $it=array_merge($it,array("N"));
        echo "<p class = pa >By vija iT (".link_sutra("1.2.2").") :</p>\n"; 
        echo "<p class = pa >विज इट्‌ (१.२.२) :</p>\n";
        display(0);    
		$vijait=1;
    } else {$vijait=0; }
}
/* defining the sUtras mandating sanAdis */
/* guptijkidbhyaH san (3.1.5) */
if ( $_GET['cond47']==="1" )
{
    $text=three(array("gupa!","tija!","kita!"),array("+"),$tiG,array("gupa!","tija!","kita!"),array("+san+"),$tiG,0);
    echo "<p class = sa >By guptijkidbhyaH san (".link_sutra("3.1.5").") :</p>\n"; 
    echo "<p class = sa >गुप्तिज्किद्भ्यः सन्‌ (३.१.५) :</p>\n";
    display(0);
    $sanAdi="san";
}
/* mAnbadhadAnzAnbhyo dIrghazcAbhyAsasya (3.1.6) */
// right now only coded for san pratyaya and not for dIrghatva of abhyAsa, because abhyAsa is not taught yet.
if ( $_GET['cond48']==="1" )
{
    $text=three(array("mAna!","baDa!","dAna!","SAna!"),array("+"),$tiG,array("mAna!","baDa!","dAna!","SAna!"),array("+san+"),$tiG,0);
    echo "<p class = sa >By mAnbadhadAnzAnbhyo dIrghazcAbhyAsasya (".link_sutra("3.1.6").") :</p>\n"; 
    echo "<p class = sa >मान्बधदान्शान्भ्यो दीर्घश्चाभ्यासस्य (३.१.६) :</p>\n";
    display(0);
    $sanAdi="san";
}
/* gupUdhUpavicCipaNipanibhya AyaH (3.1.28) */
if ( sub(array("gupU!","viCa!","pana!"),array("+"),blank(0),0) || (sub(array("DUpa!"),array("+"),$sArvadhAtuka_pratyayas,0) && $verbset==="BvAdi") || $_GET['cond49']==="1"  )
{
    $text=two(array("gupU!","DUpa!","viCa!","pana!","paRa!"),array("+"),array("gupU!","DUpa!","viCa!","pana!","paRa!"),array("+Aya+"),0);
    echo "<p class = sa >By gupUdhUpavicCipaNipanibhya AyaH (".link_sutra("3.1.28").") :</p>\n"; 
    echo "<p class = sa >गुपूधूपविच्छिपणिपनिभ्य आयः (३.१.२८) :</p>\n";
    display(0);
    $sanAdi="Aya";
}
/* RterIyaG (3.1.29) */
if ( ends(array($fo),array("fta!"),4) && $pada==="pratyaya" && $lakAra!=="")
{
    $text=three(array("fta!"),array("+"),$tiG,array("fta!"),array("+IyaN+"),$tiG,0);
    echo "<p class = sa >By RterIyaG (".link_sutra("3.1.29").") :</p>\n"; 
    echo "<p class = sa >ऋतेरीयङ्‌ (३.१.२९) :</p>\n";
    display(0);
    $sanAdi="IyaN";
    $atmanepada=1;
}
/* kamerNiG (3.1.30) */
if ( ends(array($fo),array("kamu!"),4) && $pada==="pratyaya" && $lakAra!=="")
{
    $text=three(array("kamu!"),array("+"),$tiG,array("kamu!"),array("+RiN+"),$tiG,0);
    echo "<p class = sa >By kamerNiG (".link_sutra("3.1.30").") :</p>\n"; 
    echo "<p class = sa >कमेर्णिङ्‌ (३.१.३०) :</p>\n";
    display(0);
    $sanAdi="RiN";
    $atmanepada=1;
}
/* AyAdaya ArdhadhAtuke vA (3.1.31) */
if ( in_array($sanAdi,array("Aya","IyaN","RiN")) && $lakAra!=="" && $ardhadhatuka===1)
{
    $text=two(array("+"),array("Aya+","IyaN+","RiN+"),array("+"),array("","",""),1);
    echo "<p class = sa >By AyAdaya ArdhadhAtuke vA (".link_sutra("3.1.31").") :</p>\n"; 
    echo "<p class = sa >आयादय आर्धधातुके वा (३.१.३१) :</p>\n";
    display(0);
    $atmanepada=1;
}
/* Che ca (6.1.73) */
if (sub($hrasva,array("C"),blank(0),0) && in_array($so,$tiG) )
{
$text = two(array("a","i","u","f","x"),array("C"),array("at","it","ut","ft","xt"),array("C"),0);
echo "<p class = sa >By Che ca (".link_sutra("6.1.73").") :</p>\n";
echo "<p class = sa >छे च (६.१.७३) :</p>\n";
display(0);
// separate patch for asthetic value.
$text = one(array("tC"),array("cC"),0);
echo "<p class = sa >By stoH zcunA zcuH (".link_sutra("8.4.40").") :</p>\n";
echo "<p class = sa >स्तोः श्चुना श्चुः (८.४.४०) :</p>\n";
display(0);
}
/* Displaying general information about lakAras */
/* laT vartamAne (3.2.123) */
if (in_array($so,$tiG) && $pada==="pratyaya" && $lakAra==="law")
{
    echo "<p class = pa >vartamAne laT (".link_sutra("3.2.123").") :</p>\n"; 
    echo "<p class = pa >वर्तमाने लट्‌ (३.२.१२३) :</p>\n";
    display(0);
}
/* parokSe liT (3.2.115) */
if (in_array($so,$tiG) && $pada==="pratyaya" && $lakAra==="liw")
{
    echo "<p class = pa >parokSe liT (".link_sutra("3.2.115").") :</p>\n"; 
    echo "<p class = pa >परोक्षे लिट्‌ (३.२.११५) :</p>\n";
    display(0);
}
/* anadyatane luT (3.3.15) */
if (in_array($so,$tiG) && $pada==="pratyaya" && $lakAra==="luw")
{
    echo "<p class = pa >By anadyatane luT (".link_sutra("3.3.15").") :</p>\n"; 
    echo "<p class = pa >अनद्यतने लुट्‌ (३.३.१५) :</p>\n";
    display(0);
}
/* AziSi liGlowau (3.3.173) */
if (in_array($so,$tiG) && $pada==="pratyaya" && $lakAra==="ASIrliN")
{
    echo "<p class = pa >AziSi liGlowau (".link_sutra("3.3.173").") :</p>\n"; 
    echo "<p class = pa >आशिषि लिङ्लोटौ (३.३.१७३) :</p>\n";
    display(0);
}
/* liGnimitte lRG kriyAtipattau (3.2.139) */
if (in_array($so,$tiG) && $pada==="pratyaya" && $lakAra==="lfN")
{
    echo "<p class = pa >liGnimitte lRG kriyAtipattau (".link_sutra("3.2.139").") :</p>\n"; 
    echo "<p class = pa >लिङ्निमित्ते लृङ्‌ क्रियातिपत्तौ (३.२.१२३) :</p>\n";
    display(0);
}
/* bhUvAdayo dhAtavaH (1.3.1) */
if (in_array($so,$tiG) && $pada==="pratyaya" && $lakAra!=="" && $sanAdi==="" && ends(array($fo),$allverbs,4))
{
    echo "<p class = pa >bhUvAdayo dhAtavaH (".link_sutra("1.3.1").") :</p>\n"; 
    echo "<p class = pa >भूवादयो धातवः (१.३.१) :</p>\n";
    display(0);
}
/* sanAdyantA dhAtavaH (3.1.32) */
elseif (in_array($so,$tiG) && $pada==="pratyaya" && $lakAra!=="" && $sanAdi!=="")
{
    echo "<p class = pa >sanAdyantA dhAtavaH (".link_sutra("3.1.32").") :</p>\n"; 
    echo "<p class = pa >सनाद्यन्ता धातवः (३.१.३२) :</p>\n";
    display(0);
}
/* laH karmaNi ca bhAve cAkarmakebhyaH (3.4.69), lasya (3.4.77) and tiptasjhisipthasthamibvasmastAtAMjhathAsAthAMdhvamiDvahimahiG (3.4.78) */
if (in_array($so,$tiG) && $pada==="pratyaya" && $lakAra!=="")
{
    echo "<p class = pa >laH karmaNi ca bhAve cAkarmakebhyaH (".link_sutra("3.4.69")."), lasya (".link_sutra("3.4.77").") and tiptasjhisipthasthamibvasmastAtAMjhathAsAthAMdhvamiDvahimahiG (".link_sutra("3.4.78").") :</p>\n"; 
    echo "<p class = pa >लः कर्मणि च भावे चाकर्मकेभ्यः (३.४.६९), लस्य (३.४.७७) तथा तिप्तस्झिसिप्थस्थमिब्वस्मस्तातांझथासाथांध्वमिड्वहिमहिङ्‌ (३.४.७८) :</p>\n";
    display(0);
}
/* laH parasmaipadam (1.4.99) */
if ( ($parasmaipada===1 || ($ubhayapada===1 && in_array($so,$tis))) && $lakAra!=="")
{
    echo "<p class = pa >laH parasmaipadam (".link_sutra("1.4.99").") :</p>\n"; 
    echo "<p class = pa >लः परस्मैपदम्‌ (१.४.९९) :</p>\n";
    display(0);
}
/* taGAnAvAtmanepadam (1.4.100) */
if ( ( $atmanepada===1 || ($ubhayapada===1 && in_array($so,$taG)) )&& $pada==="pratyaya" && $lakAra!=="")
{
    echo "<p class = pa >taGAnAvAtmanepadam (".link_sutra("1.4.100").") :</p>\n"; 
    echo "<p class = pa >तङानावात्मनेपदम्‌ (१.४.१००) :</p>\n";
    display(0);
}
/* tiGastrINi trINi prathamamadhyamottamAH (1.4.101) and tAnyekavacanadvivacanabahuvacanAnyekazaH (1.4.102) */
if (in_array($so,$tiG) && $pada==="pratyaya" && $lakAra!=="")
{
    echo "<p class = pa >tiGastrINi trINi prathamamadhyamottamAH (".link_sutra("1.4.101").") and tAnyekavacanadvivacanabahuvacanAnyekazaH (".link_sutra("1.4.102")."):</p>\n"; 
    echo "<p class = pa >तिङस्त्रीणि त्रीणि प्रथममध्यमोत्तमाः (१.४.१०१) तथा तान्येकवचनद्विवचनबहुवचनान्येकशः (१.४.१०२) :</p>\n";
    display(0);
}
/* yuzmadyupapade samAnAdhikaraNe sthAninyapi madhyamaH (1.4.105) */
if (in_array($so,$tiGmadhyama) && $pada==="pratyaya" && $lakAra!=="")
{
    echo "<p class = pa >yuzmadyupapade samAnAdhikaraNe sthAninyapi madhyamaH (".link_sutra("1.4.105").") :</p>\n"; 
    echo "<p class = pa >युष्मद्युपपदे समानाधिकरणे स्थानिन्यपि मध्यमः (१.४.१०५) :</p>\n";
    display(0);
}
/* prahAse ca manyopapade manyateruttama ekavacca (1.4.106) */
// Pending. Not clear. Wii code when its example comes.
/* yuzmadyupapade samAnAdhikaraNe sthAninyapi madhyamaH (1.4.105) */
if (in_array($so,$tiGuttama) && $pada==="pratyaya" && $lakAra!=="")
{
    echo "<p class = pa >asmadyuttamaH (".link_sutra("1.4.107").") :</p>\n"; 
    echo "<p class = pa >अस्मद्युत्तमः (१.४.१०७) :</p>\n";
    display(0);
}
/* zeSe prathamaH (1.4.108) */
if (in_array($so,$tiGprathama) && $pada==="pratyaya" && $lakAra!=="")
{
    echo "<p class = pa >zeSe prathamaH (".link_sutra("1.4.108").") :</p>\n"; 
    echo "<p class = pa >शेषे प्रथमः (१.४.१०८) :</p>\n";
    display(0);
}
/* jakSityAdayaH SaT (6.1.6) */
if (sub(array("jakza!","jAgf","daridrA","ASAsu!","cakAsf!","dIDIN","vevIN"),blank(0),blank(0),0))
{
    $abhyasta=1; 
    $jaksat=1; // 0 - doesn't belong to jakSityAdi. 1 - belongs to jakSityAdi.
    echo "<p class = pa >By jakSityAdayaH SaT (".link_sutra("6.1.6").") :</p>\n";
    echo "<p class = pa >जक्षित्यादयः षट्‍ (६.१.६) :</p>\n";
    display(0);
}
/* Adding vikaraNas */
$vik=array();
if ($lakAra==="luN")
{
	$luGset=1;
	// $luGset takes value 1 to 12 based on 12 types of luG lakAra pratyayas given on pages 154,155 of sahajabodha part 2.
	/* luG (3.2.110) */
		echo "<p class = pa >By luG (".link_sutra("3.2.110").") :</p>\n"; 
		echo "<p class = pa >लुङ्‌ (३.२.११०) :</p>\n";
		display(0);
	/* mAGi luG (3.3.175) */
	// pending.
	/* smottare laG ca (3.3.176) */
	// pending.
	/* cli luGi (3.1.43) */
		$text = pr2(array("+"),$tiG,blank(0),array("+cli+"),$tiG,blank(0),$text);
		echo "<p class = sa >By cli luGi (".link_sutra("3.1.43").") :</p>\n"; 
		echo "<p class = sa >च्लि लुङि (३.१.४३) :</p>\n";
		display(0);
	/* zala igupadhAdaniTaH ksaH (3.1.45) */
	if (ends(array($fo),array("kruSa!","diSa!","riSa!","ruSa!","liSa!","tviza!","dviza!","miha!","ruha!","liha!","duha!"),4) )
	{
		$text = one(array("+cli+"),array("+sa+"),0);
		echo "<p class = sa >By zala igupadhAdaniTaH ksaH (".link_sutra("3.1.45").") :</p>\n"; 
		echo "<p class = sa >शल इगुपधादनिटः क्सः (३.१.४५) :</p>\n";
		display(0);
		$ksa=1;
		$luGset=7;
		$it = array_merge($it,array("k"));
		$itpratyaya = array_merge($itpratyaya,array("k"));
	}
	/* zala igupadhAdaniTaH ksaH (3.1.45) */
	if (ends(array($fo),array("gfhU!","bfhU!","tfhU!","stfhU!","guhU!"),4) )
	{
		$text = one(array("+cli+"),array("+sa+"),1);
		echo "<p class = sa >By zala igupadhAdaniTaH ksaH (".link_sutra("3.1.45").") :</p>\n"; 
		echo "<p class = sa >शल इगुपधादनिटः क्सः (३.१.४५) :</p>\n";
		display(0);
		$ksa=1;
		$luGset=7;
		$it = array_merge($it,array("k"));
		$itpratyaya = array_merge($itpratyaya,array("k"));
	}
	/* asyativaktikhyAtibhyo'G (3.1.52) */
	if (ends(array($fo),array("asu!","vaca!","brUY","KyA","cakzi!N"),4) )
	{
		$text = one(array("+cli+"),array("+aN+"),0);
		echo "<p class = sa >By asyativaktikhyAtibhyo'G (".link_sutra("3.1.52").") :</p>\n"; 
		echo "<p class = sa >अस्यतिवक्तिख्यातिभ्योऽङ्‌ (३.१.५२) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* lipisicihvazca (3.1.53) */
	if (ends(array($fo),array("lipa!","zica!","hveY"),4) && in_array($so,$tis) )
	{
		$text = one(array("+cli+"),array("+aN+"),0);
		echo "<p class = sa >By lipisicihvazca (".link_sutra("3.1.53").") :</p>\n"; 
		echo "<p class = sa >लिपिसिचिह्वश्च (३.१.५३) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* AtmanepadeSvanyatarasyAm (3.1.54) */
	if (ends(array($fo),array("lipa!","zica!","hveY"),4) && in_array($so,$taG) )
	{
		$text = one(array("+cli+"),array("+aN+"),1);
		echo "<p class = sa >By AtmanepadeSvanyatarasyAm (".link_sutra("3.1.54").") :</p>\n"; 
		echo "<p class = sa >आत्मनेपदेष्वन्यतरस्याम्‌ (३.१.५४) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* spRzamRzakRSatRpadRpAM sijvA vaktavyaH (vA) */
	if ( ends(array($fo),array("spfSa!","mfSa!","kfza!","tfpa!","dfpa!"),4) && in_array($so,$tis) )
	{
		$text = one(array("+cli+"),array("+sa+"),1); // ?? is it optional aN or optional ksa?. Pending.
		echo "<p class = sa >By spRzamRzakRSatRpadRpAM sijvA vaktavyaH (vA) :</p>\n"; 
		echo "<p class = sa >स्पृशमृषकृषतृपदृपां सिज्वा वक्तव्यः (वा) :</p>\n";
		display(0);
		$ksa=1;
		$luGset=7;
		$it = array_merge($it,array("k"));
		$itpratyaya = array_merge($itpratyaya,array("k"));
	}
	/* zliSa AliGgane (??) see page 166 of sahajabodha part 2. */
	elseif ( ends(array($fo),array("Sliza!"),4) && in_array($so,$tis) && $_GET['cond54']==="2" )
	{
		$text = one(array("+cli+"),array("+aN+"),0);
		echo "<p class = sa >By zliSa AliGgane (??) and puSAdidyutAdilRditaH parasmaipadeSu (".link_sutra("3.1.55").") :</p>\n"; 
		echo "<p class = sa >श्लिष आलिङ्गने (??) तथा पुषादिद्युतादिलृदितः परस्मैपदेषु (३.१.५५) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* puSAdidyutAdilRditaH parasmaipadeSu (3.1.55) */
	elseif ( (ends(array($fo),$divAdi_puSAdi,4)||ends(array($fo),$bhvAdi_dyutAdi,4)||ends(array($fo),$lRdit,4)) && in_array($so,$tis) )
	{
		$text = one(array("+cli+"),array("+aN+"),0);
		echo "<p class = sa >By puSAdidyutAdilRditaH parasmaipadeSu (".link_sutra("3.1.55").") :</p>\n"; 
		echo "<p class = sa >पुषादिद्युतादिलृदितः परस्मैपदेषु (३.१.५५) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* sartizAstyartibhyazca (3.1.56) */
	if ( ends(array($fo),array("sf","SAsu!","f",),2) && in_array($so,$tis) && sub(array("sf","SAsu!","f",),array("+"),blank(0),0) )
	{
		$text = one(array("+cli+"),array("+aN+"),0);
		echo "<p class = sa >By sartizAstyartibhyazca (".link_sutra("3.1.56").") :</p>\n"; 
		echo "<p class = sa >सर्तिशास्त्यर्तिभ्यश्च (३.१.५६) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* na dRSaH (3.1.77) */
	if ( ends(array($fo),array("dfSi!r"),2) && in_array($so,$tis) )
	{
		$text = one(array("+cli+"),array("+aN+"),1);
		echo "<p class = sa >By na dRSaH (".link_sutra("3.1.77").") :</p>\n"; 
		echo "<p class = sa >न दृशः (३.१.७७) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* irito vA (3.1.57) */
	elseif ( ends(array($fo),$irendiditverbs,2) && in_array($so,$tis) )
	{
		$text = one(array("+cli+"),array("+aN+"),1);
		echo "<p class = sa >By irito vA (".link_sutra("3.1.57").") :</p>\n"; 
		echo "<p class = sa >इरितो वा (३.१.५७) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* jRRstanbhumrucumlucugrucuglucugluJcuzvibhyazca (3.1.58) */
	if ( ends(array($fo),array("jF","staMBu!","mrucu!","mlucu!","grucu!","glucu!","gluYcu!","wuo!Svi",),2) && in_array($so,$tis) )
	{
		$text = one(array("+cli+"),array("+aN+"),1);
		echo "<p class = sa >By jRRstanbhumrucumlucugrucuglucugluJcuzvibhyazca (".link_sutra("3.1.58").") :</p>\n"; 
		echo "<p class = sa >जॄस्तन्भुम्रुचुम्लुचुग्रुचुग्लुचुग्लुञ्चुश्विभ्यश्च (३.१.५८) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* kRmRdRruhibhyazCandasi (3.1.59) */
	if ( ends(array($fo),array("qukfY","mfN","df","ruha!"),2) && in_array($so,$tis) && $_GET['cond55']==="1")
	{
		$text = one(array("+cli+"),array("+aN+"),0);
		echo "<p class = sa >By kRmRdRruhibhyazCandasi (".link_sutra("3.1.59").") :</p>\n"; 
		echo "<p class = sa >कृमृदृरुहिभ्यश्छन्दसि (३.१.५९) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
	}
	/* nonayatidhvanayatyelayatyardayatibhyaH (3.1.51) */
	if ( ends(array($fo),array("Una","Dvana","ila!","arda!"),2) && in_array($so,$tiG) && $_GET['cond55']==="1")
	{
		$text = one(array("+cli+"),array("+sic+"),0);		
		echo "<p class = sa >By nonayatidhvanayatyelayatyardayatibhyaH (".link_sutra("3.1.51").") :</p>\n"; 
		echo "<p class = sa >नोनयतिध्वनयत्येलयत्यर्दयतिभ्यः (३.१.५१) :</p>\n";
		display(0);
		$sic=1; 
		$luGset=9;
		$text = one(array("Una+Ric+","Dvana+Ric+","ila!+Ric+","arda!+Ric+"),array("Uni+","Dvani+","li+","ardi+"),0);
	}
	/* NizridrusrubhyaH kartari caG (3.1.48) */
	elseif ( ( $sanAdi==="Ric" || $sanAdi==="RiN" || ends(array($fo),array("SriY","sru","dru"),2) ) && in_array($so,$tiG) )
	{
		$text = one(array("+cli+"),array("+caN+"),0);
		echo "<p class = sa >By NizridrusrubhyaH kartari caG (".link_sutra("3.1.48").") :</p>\n"; 
		echo "<p class = sa >णिश्रिद्रुस्रुभ्यः कर्तरि चङ्‌ (३.१.४८) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
		$luGset=5;
		$caG=1;
	}
	/* kamezclezcaG vAcyaH (vA) */
	if ( ends(array($fo),array("kamu!"),2) && in_array($so,$tiG) )
	{
		$text = one(array("+cli+"),array("+caN+"),0);
		echo "<p class = sa >By kamezclezcaG vAcyaH (vA) :</p>\n"; 
		echo "<p class = sa >कमेश्च्लेश्चङ्‌ वाच्यः (वा) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
		$luGset=5;
		$caG=1;
	}
	/* vibhASA dheTzvyoH (3.1.49) */
	if ( ends(array($fo),array("Dew","wuo!Svi"),2) && in_array($so,$tiG) )
	{
		$text = one(array("+cli+"),array("+caN+"),1);
		echo "<p class = sa >By vibhASA dheTzvyoH (".link_sutra("3.1.49").") :</p>\n"; 
		echo "<p class = sa >विभाषा धेट्श्व्योः (३.१.४९) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
		$luGset=5;
		$caG=1;
	}
	/* gupezCandasi (3.1.50) */
	if ( ends(array($fo),array("gupa!"),2) && in_array($so,$tiG) && $_GET['cond55']==="1")
	{
		$text = one(array("+cli+"),array("+caN+"),1);
		echo "<p class = sa >By gupezCandasi (".link_sutra("3.1.50").") :</p>\n"; 
		echo "<p class = sa >गुपेश्छन्दसि (३.१.५०) :</p>\n";
		display(0);
		$it = array_merge($it,array("N"));
		$itpratyaya = array_merge($itpratyaya,array("N"));
		$luGset=5;
		$caG=1;
	}
	/* cleH sic (3.1.44) */
	if (sub(array("+"),array("cli"),array("+"),0))
	{
		$text = one(array("+cli+"),array("+sic+"),0);
		echo "<p class = sa >By cleH sic (".link_sutra("3.1.44").") :</p>\n"; 
		echo "<p class = hn >ikAra and cakAra are anubandhas.</p>\n"; 
		echo "<p class = sa >च्लेः सिच्‌ (३.१.४४) :</p>\n";
		echo "<p class = hn >इचावितौ ।</p>\n";
		display(0);
		$sic=1; // 1 for sic vikaraNa. 0 for sijluk. 2 for vibhASA.
		$luGset=9;
	}
	/* gAtisthAghupAbhUbhyaH sicaH parasmaipadeSu (2.4.77) */
		if (ends(array($fo),array("iR","zWA","do","deN","qudAY","dAR","quDAY","pA","BU"),4) && !(ends(array($fo),array("pA"),4) && $verbset==="adAdi") && $sic===1 && in_array($so,$tis))
		{
			$text = one(array("+sic+"),array("+"),0);
			echo "<p class = sa >By gAtisthAghupAbhUbhyaH sicaH parasmaipadeSu (".link_sutra("2.4.77").") :</p>\n"; 
			echo "<p class = sa >गातिस्थाघुपाभूभ्यः सिचः परस्मैपदेषु (२.४.७७) :</p>\n";
			display(0);	
			$sic=0;
			$luGset=1;
		}
	/* vibhASA ghrAdheTzAcCAsaH (2.4.78) */
		if (ends(array($fo),array("GrA","Dew","So","Co","zo",),4) && $sic===1)
		{
			$text = one(array("+sic+"),array("+"),1);
			echo "<p class = sa >By vibhASA ghrAdheTzAcCAsaH (".link_sutra("2.4.78").") :</p>\n"; 
			echo "<p class = sa >विभाषा घ्राधेट्शाच्छासः (२.४.७८) :</p>\n";
			display(0);	
			$sic=2;
			$luGset=1;
		}
}
/* Deciding seTtva / veTtva / aniTtva of luG lakAra suffixes */

if ( !in_array($luGset,array(9)) && $lakAra==='luN')
{
	$id_dhAtu='aniw';
}
/* sijabhyastavidibhyazca (3.4.109) */
if ( $sic!==0 && $so==="Ji" )
{
	$text = two(array("+sic+"),array("Ji"),array("+sic+"),array("jus"),0);
	echo "<p class = sa >By sijabhyastavidibhyazca (".link_sutra("3.4.109").") :</p>\n"; 
	echo "<p class = sa >सिजभ्यस्तविदिभ्यश्च (३.४.१०९) :</p>\n";
	display(0);	
}
if ( $abhyasta===1 && $so==="Ji" )
{
	$text = two(array("+"),array("Ji"),array("+"),array("jus"),0);
	echo "<p class = sa >By sijabhyastavidibhyazca (".link_sutra("3.4.109").") :</p>\n"; 
	echo "<p class = sa >सिजभ्यस्तविदिभ्यश्च (३.४.१०९) :</p>\n";
	display(0);	
}
/* AtaH (3.4.110) */
if ( $sic!==0 && $so==="Ji" && sub(array("A"),array("+"),array("Ji"),0) )
{
	$text = three(array("A"),array("+"),array("Ji"),array("A"),array("+"),array("jus"),0);
	echo "<p class = sa >By AtaH (".link_sutra("3.4.110").") :</p>\n"; 
	echo "<p class = sa >आतः (३.४.११०) :</p>\n";
	display(0);	
}
/* zruvaH zR ca (3.1.74) */
if (sub(array("Sru"),array("+"),$tiG,0) && ends(array($fo),array("Sru"),4) && $sarvadhatuka===1)
{
    $text=three(array("Sru"),array("+"),$tiG,array("Sf"),array("+Snu+"),$tiG,0);
    $text=one(array("+Snu+Snu"),array("+Snu+"),0);
    echo "<p class = sa >By zruvaH zR ca (".link_sutra("3.1.74").") :</p>\n"; 
    echo "<p class = sa >श्रुवः शृ च (३.१.७४) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Snu"));
    $set=2;
}
// first treatment of curAdi, because, it will take Nic before vikaraNa pratyaya.
/* satyApapAzarUpavINAtUlazlokasenAlomatvacavarmacUrNacurAdibhyo Nic (3.1.25) */
elseif (sub($curAdi,array("+"),$tiG,0) && ends(array($fo),$curAdi,4) && $sarvadhatuka===1 && $verbset==="curAdi")
{
    $text=two(array("+"),$tiG,array("+Ric+"),$tiG,0);
    $text=one(array("+Ric+Ric"),array("+Ric+"),0);
    echo "<p class = sa >By satyApapAzarUpavINAtUlazlokasenAlomatvacavarmacUrNacurAdibhyo Nic (".link_sutra("3.1.25").") :</p>\n"; 
    echo "<p class = sa >सत्यापपाशरूपवीणातूलश्लोकसेनालोमत्वचवर्मचूर्णचुरादिभ्यो णिच्‌ (३.१.२५) :</p>\n";
    display(0);   
    if (sub(array("+"),$tiG,blank(0),0) && $sarvadhatuka===1)
    {
    $text=two(array("+"),$tiG,array("+Sap+"),$tiG,0);
    $text=one(array("+Sap+Sap"),array("+Sap+"),0);
    echo "<p class = sa >By kartari zap (".link_sutra("3.1.68").") :</p>\n"; 
    echo "<p class = sa >कर्तरि शप्‌ (३.१.६८) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Sap"));
    }
    $set=1; // defining set as per sahajabodha groups.
}
// For pratipadikas.
elseif (sub(array("satyApa","pASa","vIRA","tUla","Sloka","senA","loma","tvac","varRa","cUrRa"),array("+"),blank(0),0) )
{
    $text=two(array("satyApa","pASa","vIRA","tUla","Sloka","senA","loma","tvac","varRa","cUrRa"),array("+"),array("satyApa","pASa","vIRA","tUla","Sloka","senA","loma","tvac","varRa","cUrRa"),array("+Ric+"),0);
    $text=one(array("+Ric+Ric"),array("+Ric+"),0);
    echo "<p class = sa >By satyApapAzarUpavINAtUlazlokasenAlomatvacavarmacUrNacurAdibhyo Nic (".link_sutra("3.1.25").") :</p>\n"; 
    echo "<p class = sa >सत्यापपाशरूपवीणातूलश्लोकसेनालोमत्वचवर्मचूर्णचुरादिभ्यो णिच्‌ (३.१.२५) :</p>\n";
    display(0);   
}
/* for user input specified verbs */
/* divAdibhyaH zyan (3.1.69) */
elseif (sub($divAdi,array("+"),$tiG,0) && ends(array($fo),$divAdi,4) && $sarvadhatuka===1 && $verbset==="divAdi")
{
    $text=two(array("+"),$tiG,array("+Syan+"),$tiG,0);
    $text=one(array("+Syan+Syan"),array("+Syan+"),0);
    echo "<p class = sa >By divAdibhyaH Syan (".link_sutra("3.1.69").") :</p>\n"; 
    echo "<p class = sa >दिवादिभ्यः श्यन्‌ (३.१.६९) :</p>\n";
    display(0);   
    $vik=array_merge($vik,array("Syan"));
    $set=1;
}
/* svAdibhyaH znuH (3.1.73) */
elseif (sub($svAdi,array("+"),$tiG,0) && ends(array($fo),$svAdi,4) && $sarvadhatuka===1 && $verbset==="svAdi")
{
    $text=two(array("+"),$tiG,array("+Snu+"),$tiG,0);
    $text=one(array("+Snu+Snu"),array("+Snu+"),0);
    echo "<p class = sa >By svAdibhyaH znuH (".link_sutra("3.1.73").") :</p>\n"; 
    echo "<p class = sa >स्वादिभ्यः श्नुः (३.१.७३) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Snu"));
    $set=2;
}
/* tudAdibhyaH zaH (3.1.77) */
elseif (sub($tudAdi,array("+"),$tiG,0) && ends(array($fo),$tudAdi,4) && $sarvadhatuka===1 && $verbset==="tudAdi")
{
    $text=two(array("+"),$tiG,array("+Sa+"),$tiG,0);
    $text=one(array("+Sa+Sa"),array("+Sa+"),0);
    echo "<p class = sa >By tudAdibhyaH zaH (".link_sutra("3.1.77").") :</p>\n"; 
    echo "<p class = sa >तुदादिभ्यः शः (३.१.७७) :</p>\n";
    display(0);   
    $vik=array_merge($vik,array("Sa"));    
    $set=1;
}
/* rudhAdibhyaH znam (3.1.78) */
elseif (sub($rudhAdi,array("+"),$tiG,0) && ends(array($fo),$rudhAdi,4) && $sarvadhatuka===1 && $verbset==="ruDAdi")
{
    $rudhAdibhyaH=1; $set=2;
}
/* tanAdikRJbhyaH uH (3.1.79) */
elseif (sub($tanAdi,array("+"),$tiG,0) && ends(array($fo),$tanAdi,4) && $sarvadhatuka===1 && $verbset==="tanAdi")
{
    $text=two(array("+"),$tiG,array("+u+"),$tiG,0);
    $text=one(array("+u+u"),array("+u+"),0);
    echo "<p class = sa >By tanAdikRJbhyaH uH (".link_sutra("3.1.79").") :</p>\n"; 
    echo "<p class = sa >तनादिकृञ्भ्य उः (३.१.७९) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("u"));
    $set=2;
    
}
/* kryadibhyaH znA (3.1.81) */
elseif (sub($kryAdi,array("+"),$tiG,0) && ends(array($fo),$kryAdi,4) && $sarvadhatuka===1 && $verbset==="kryAdi")
{
    $text=two(array("+"),$tiG,array("+SnA+"),$tiG,0);
    $text=one(array("+SnA+SnA"),array("+SnA+"),0);
    echo "<p class = sa >By kryadibhyaH znA (".link_sutra("3.1.81").") :</p>\n"; 
    echo "<p class = sa >क्र्यादिभ्यः श्ना (३.१.८१) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("SnA"));
    $set=2;
}
/* kartari zap (3.1.68) */
elseif (sub(array("+"),$tiG,blank(0),0) && $sarvadhatuka===1 && in_array($verbset,array("BvAdi","adAdi","juhotyAdi","curAdi")))
{
    $text=two(array("+"),$tiG,array("+Sap+"),$tiG,0);
    $text=one(array("+Sap+Sap"),array("+Sap+"),0);
    echo "<p class = sa >By kartari zap (".link_sutra("3.1.68").") :</p>\n"; 
    echo "<p class = sa >कर्तरि शप्‌ (३.१.६८) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Sap"));
    $set=1;
}

/* for regular input without user selection */
/* satyApapAzarUpavINAtUlazlokasenAlomatvacavarmacUrNacurAdibhyo Nic (3.1.25) */
elseif (sub($curAdi,array("+"),$tiG,0) && ends(array($fo),$curAdi,4) && $sarvadhatuka===1 && $verbset==="none")
{
    $text=two(array("+"),$tiG,array("+Ric+"),$tiG,0);
    $text=one(array("+Ric+Ric"),array("+Ric+"),0);
    echo "<p class = sa >By satyApapAzarUpavINAtUlazlokasenAlomatvacavarmacUrNacurAdibhyo Nic (".link_sutra("3.1.25").") :</p>\n"; 
    echo "<p class = sa >सत्यापपाशरूपवीणातूलश्लोकसेनालोमत्वचवर्मचूर्णचुरादिभ्यो णिच्‌ (३.१.२५) 1:</p>\n";
    display(0); 
    if (sub(array("+"),$tiG,blank(0),0) && $sarvadhatuka===1)
    {
    $text=two(array("+"),$tiG,array("+Sap+"),$tiG,0);
    $text=one(array("+Sap+Sap"),array("+Sap+"),0);
    echo "<p class = sa >By kartari zap (".link_sutra("3.1.68").") :</p>\n"; 
    echo "<p class = sa >कर्तरि शप्‌ (३.१.६८) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Sap"));
    }
    $set=1;
}
/* divAdibhyaH zyan (3.1.69) */
elseif (sub($divAdi,array("+"),$tiG,0) && ends(array($fo),$divAdi,4) && $sarvadhatuka===1  && $verbset==="none")
{
    $text=two(array("+"),$tiG,array("+Syan+"),$tiG,0);
    $text=one(array("+Syan+Syan"),array("+Syan+"),0);
    echo "<p class = sa >By divAdibhyaH Syan (".link_sutra("3.1.69").") :</p>\n"; 
    echo "<p class = sa >दिवादिभ्यः श्यन्‌ (३.१.६९) :</p>\n";
    display(0);   
    $vik=array_merge($vik,array("Syan"));
    $set=1;
}
/* svAdibhyaH znuH (3.1.73) */
elseif (sub($svAdi,array("+"),$tiG,0) && ends(array($fo),$svAdi,4) && $sarvadhatuka===1  && $verbset==="none")
{
    $text=two(array("+"),$tiG,array("+Snu+"),$tiG,0);
    $text=one(array("+Snu+Snu"),array("+Snu+"),0);
    echo "<p class = sa >By svAdibhyaH znuH (".link_sutra("3.1.73").") :</p>\n"; 
    echo "<p class = sa >स्वादिभ्यः श्नुः (३.१.७३) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Snu"));
    $set=2;
}
/* tudAdibhyaH zaH (3.1.77) */
elseif (sub($tudAdi,array("+"),$tiG,0) && ends(array($fo),$tudAdi,4) && $sarvadhatuka===1  && $verbset==="none")
{
    $text=two(array("+"),$tiG,array("+Sa+"),$tiG,0);
    $text=one(array("+Sa+Sa"),array("+Sa+"),0);
    echo "<p class = sa >By tudAdibhyaH zaH (".link_sutra("3.1.77").") :</p>\n"; 
    echo "<p class = sa >तुदादिभ्यः शः (३.१.७७) :</p>\n";
    display(0);   
    $vik=array_merge($vik,array("Sa"));    
    $set=2;
}
/* rudAdibhyaH znam (3.1.78) */
elseif (sub($rudhAdi,array("+"),$tiG,0) && ends(array($fo),$rudhAdi,4) && $sarvadhatuka===1  && $verbset==="none")
{
    $rudhAdibhyaH = 1;
    $set=2;
}
/* dhivikRNvyora ca (3.1.80) */
elseif (sub(array("Divi!","kfvi!"),array("+"),$tiG,0) && ends(array($fo),array("Divi!","kfvi!"),4) && $sarvadhatuka===1)
{
    $text=three(array("Divi!","kfvi!"),array("+"),$tiG,array("Divi!","kfvi!"),array("+u+"),$tiG,0);
    $text=one(array("+u+u"),array("+u+"),0);
    echo "<p class = sa >By dhivikRNvyora ca (".link_sutra("3.1.80").") :</p>\n"; 
    echo "<p class = sa >धिविकृण्व्योर च (३.१.८०) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("u"));
    $set=2;
}
/* tanAdikRJbhyaH uH (3.1.79) */
elseif (sub($tanAdi,array("+"),$tiG,0) && ends(array($fo),$tanAdi,4) && $sarvadhatuka===1  && $verbset==="none")
{
    $text=two(array("+"),$tiG,array("+u+"),$tiG,0);
    $text=one(array("+u+u"),array("+u+"),0);
    echo "<p class = sa >By tanAdikRJbhyaH uH (".link_sutra("3.1.79").") :</p>\n"; 
    echo "<p class = sa >तनादिकृञ्भ्य उः (३.१.७९) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("u"));
    $set=2;
}
/* kryadibhyaH znA (3.1.81) */
elseif (sub($kryAdi,array("+"),$tiG,0) && ends(array($fo),$kryAdi,4) && $sarvadhatuka===1  && $verbset==="none")
{
    $text=two(array("+"),$tiG,array("+SnA+"),$tiG,0);
    $text=one(array("+SnA+SnA"),array("+SnA+"),0);
    echo "<p class = sa >By kryadibhyaH znA (".link_sutra("3.1.81").") :</p>\n"; 
    echo "<p class = sa >क्र्यादिभ्यः श्ना (३.१.८१) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("SnA"));
    $set=2;
}
/* zruvaH zR ca (3.1.74) */
elseif (sub(array("Sru"),array("+"),$tiG,0) && ends(array($fo),array("Sru"),4) && $sarvadhatuka===1  && $verbset==="none")
{
    $text=three(array("Sru"),array("+"),$tiG,array("Sf"),array("+Syan+"),$tiG,0);
    echo "<p class = sa >By zruvaH zR ca (".link_sutra("3.1.74").") :</p>\n"; 
    echo "<p class = sa >श्रुवः शृ च (३.१.७४) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Syan"));
    $set=1;
}
/* kartari zap (3.1.68) */
elseif (sub(array("+"),$tiG,blank(0),0) && $sarvadhatuka===1)
{
    $text=two(array("+"),$tiG,array("+Sap+"),$tiG,0);
    $text=one(array("+Sap+Sap"),array("+Sap+"),0);
    echo "<p class = sa >By kartari zap (".link_sutra("3.1.68").") :</p>\n"; 
    echo "<p class = sa >कर्तरि शप्‌ (३.१.६८) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Sap"));
    $set=1;
}
/* yaso'nupasargAt (3.1.71) */
if (sub(array("yasu!"),array("+Syan"),array("+"),0) && ends(array($fo),array("yasu!"),2))
{
    $text=three(array("yasu!"),array("+Syan"),array("+"),array("yasu!"),array("+Sap"),array("+"),1);
    echo "<p class = sa >By yaso'nupasargAt (".link_sutra("3.1.71").") :</p>\n"; 
    echo "<p class = sa >यसोऽनुपसर्गात्‌ (३.१.७१) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Sap"));
}
/* saMyasazca (3.1.72) */
if (sub(array("yasu!"),array("+Sap"),array("+"),0) && ends(array($fo),array("yasu!"),2) && $us==="sam")
{
    $text=three(array("yasu!"),array("+Sap"),array("+"),array("yasu!"),array("+Syan"),array("+"),1);
    echo "<p class = sa >By saMyasazca (".link_sutra("3.1.72").") :</p>\n"; 
    echo "<p class = sa >संयसश्च (३.१.७२) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Syan"));
}
/* stambhustumbhuskambhuskumbhuskuJbhyaH znuzca (3.1.82) */
if (sub(array("skuY"),array("+SnA"),array("+"),0) && ends(array($fo),array("skuY"),2) )
{
    $text=three(array("skuY"),array("+SnA"),array("+"),array("skuY"),array("+Snu"),array("+"),1);
    echo "<p class = sa >By stambhustumbhuskambhuskumbhuskuJbhyaH znuzca (".link_sutra("3.1.82").") :</p>\n"; 
    echo "<p class = sa >स्तम्भुस्तुम्भुस्कम्भुस्कुम्भुस्कुञ्भ्यः श्नुश्च (३.१.८२) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Snu"));
}
/* stambhustumbhuskambhuskumbhuskuJbhyaH znuzca (3.1.82) */
if (sub(array("stamBu!","stumBu!","skamBu!","skumBu!",),array("+SnA"),array("+"),0) && ends(array($fo),array("stamBu!","stumBu!","skamBu!","skumBu!",),2) )
{
    $text=three(array("stamBu!","stumBu!","skamBu!","skumBu!",),array("+SnA"),array("+"),array("stamBu!","stumBu!","skamBu!","skumBu!",),array("+Snu"),array("+"),1);
    echo "<p class = sa >By stambhustumbhuskambhuskumbhuskuJbhyaH znuzca (".link_sutra("3.1.82").") :</p>\n"; 
    echo "<p class = sa >स्तम्भुस्तुम्भुस्कम्भुस्कुम्भुस्कुञ्भ्यः श्नुश्च (३.१.८२) :</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Snu"));
}
/* juhotyAdibhyaH zluH (2.4.75), zlau (6.1.10) and kuhozcuH (7.4.62) */
if ( (sub($juhotyAdi,array("+Sap+"),$tiG,0) && ends(array($fo),$juhotyAdi,4) && $sarvadhatuka===1) || $zluvat===1)
{
    $text=three($juhotyAdi,array("+Sap+"),$tiG,$juhotyAdi,array("+"),$tiG,0);
    echo "<p class = sa >By juhotyAdibhyaH zluH (".link_sutra("2.4.75").") :</p>\n"; 
    echo "<p class = sa >जुहोत्यादिभ्यः श्लुः (२.४.७५) :</p>\n";
    display(0);
    $zlu=1;
    $vik=array("Slu");
    $set=2;
    $abhyasta=1;
	zlu();
}
/* adiprabhRtibhyaH zapaH (2.4.72) */
if (sub($adAdi,array("+Sap+"),$tiG,0) && $sarvadhatuka===1 && ends(array($fo),$adAdi,4) )
{
    $text=two(array("+Sap+"),$tiG,array("+"),$tiG,0);
    echo "<p class = sa >By adiprabhRtibhyaH zapaH (".link_sutra("2.4.72").") :</p>\n"; 
    echo "<p class = sa >अदिप्रभृतिभ्यः शपः (२.४.७२) :</p>\n";
    display(0);    
    $ad=1;
    $vik=array("Sapluk");
    $set=2;
}
/* Nau prAtipadikasya iSThavatkAryaM bhavatIti vaktavyam (vA) */
if ( !in_array($fo,$allverbs) && sub(array("+Ric+Sap+","+RiN+Sap+"),$tiG,blank(0),0) )
{
    echo "<p class = sa >By Nau prAtipadikasya iSThavatkAryaM bhavatIti vaktavyam (vA) :</p>\n"; 
    echo "<p class = sa >णौ प्रातिपदिकस्य इष्ठवत्कार्यं भवतीति वक्तव्यम्‌ (वा) :</p>\n";
    display(0);
    /* bhasyADhe taddhite puMvadbhAvaH (vA) */
    if (sub(array("enI","A","I"),array("+Ric+Sap+","+RiN+Sap+"),$tiG,0))
    {
        $text = three(array("enI","A","I"),array("+Ric+Sap+","+RiN+Sap+"),$tiG,array("eta","a","a"),array("+Ric+Sap+","+RiN+Sap+"),$tiG,0);
        echo "<p class = sa >By bhasyADhe taddhite puMvadbhAvaH (vA) :</p>\n"; 
        echo "<p class = sa >भस्याढे तद्धिते पुंवद्भावः (वा) :</p>\n";
        display(0);        
        $bhasyADhe=1;
    }
    /* TeH (6.4.155) */
    if (sub(array("+Ric+Sap+","+RiN+Sap+"),$tiG,blank(0),0) && anekAca($fo))
    {
        $text = three(array($first),array("+Ric+Sap+","+RiN+Sap+"),$tiG,array(Ti1($first)),array("+Ric+Sap+","+RiN+Sap+"),$tiG,0);
        echo "<p class = sa >By TeH (".link_sutra("6.4.155").") :</p>\n"; 
        echo "<p class = sa >टेः (६.४.१५५) :</p>\n";
        display(0);        
    }
    /* vinmatorluk (5.3.65) */
    if (sub(array("gvin","vin"),array("+Ric+Sap+","+RiN+Sap+"),$tiG,0) )
    {
        $text = three(array("gvin","vin"),array("+Ric+Sap+","+RiN+Sap+"),$tiG,array("j",""),array("+Ric+Sap+","+RiN+Sap+"),$tiG,0);
        echo "<p class = sa >By vinmatorluk (".link_sutra("5.3.65")."):</p>\n"; 
        echo "<p class = sa >विन्मतोर्लुक्‌ (५.३.६५) :</p>\n";
        display(0);        
    }
    /* avyayAnAM bhamAtre TilopaH (vA) */
    if (sub($avyaya,array("+Ric","+RiN"),blank(0),0) && in_array($fo,$avyaya) && !anekAca($fo))
    {
        $text = three(array($first),array("+Ric+Sap+","+RiN+Sap+"),$tiG,array(Ti1($first)),array("+Ric+Sap+","+RiN+Sap+"),$tiG,0);
        echo "<p class = sa >By avyayAnAM bhamAtre TilopaH (vA) :</p>\n"; 
        echo "<p class = sa >अव्ययानां भमात्रे टिलोपः (वा) :</p>\n";
        display(0);        
    }
    /* prakRtyaikAc (6.4.163) */
    elseif (sub(array("+Ric+Sap+","+RiN+Sap+"),$tiG,blank(0),0) && !anekAca($fo))
    {
        echo "<p class = sa >By prakRtyaikAc (".link_sutra("6.4.163").") :</p>\n"; 
        echo "<p class = sa >प्रकृत्यैकाच्‌ (६.४.१६३) :</p>\n";
        display(0);        
    }
}
/* pAghrAdhmAsthAmnAdANdRzyartizadasadAM pibajighradhamatiSThamanayacCapazyarcCadhaushIyasIdAH (7.3.78) */
if (sub(array("pA","GrA","DmA","sTA","mnA","dAR","dfzi!r","f","sf","Sadx!","zadx!"),array("+"),$shitpratyaya,0) && $lakAra!=="" && ends(array($fo),array("pA","GrA","DmA","sTA","mnA","dAR","dfzi!r","f","sf","Sadx!","zadx!","zWA"),4))
{
    $text=three(array("pA","GrA","DmA","sTA","mnA","dAR","dfzi!r","f","sf","Sadx!","zadx!"),array("+"),$shitpratyaya,array("pib","jiGr","Dam","tizW","man","yacC","paSy","rcC","DO","SIy","sId"),array("+"),$shitpratyaya,0);
    echo "<p class = sa >By pAghrAdhmAsthAmnAdANdRzyartizadasadAM pibajighradhamatiSThamanayacCapazyarcCadhaushIyasIdAH (".link_sutra("7.3.78").") :</p>\n"; 
    echo "<p class = sa >पाघ्राध्मास्थाम्नादाण्दृश्यर्तिसर्तिशदसदां पिबजिघ्रधमतिष्ठमनयच्छपश्यर्च्छधौशीयसीदाः (७.३.७८) :</p>\n";
    display(0);
    $nomidelision=1; // We will remember this while doing halantyam and prevent halantyam application, because these are not upadeza
}

/* AdirJiTuDavaH (1.3.5) */
if ((substr($first,0,2) === "Yi" || substr($first,0,2) === "wu" || substr($first,0,2) === "qu") && $pada=== "pratyaya" && in_array($so,$tiG))
{
    if(substr($first,0,2) === "Yi") { $itprakriti = array_merge($itprakriti,array("Yi")); }
    if(substr($first,0,2) === "wu") { $itprakriti = array_merge($itprakriti,array("wu")); }
    if(substr($first,0,2) === "qu") { $itprakriti = array_merge($itprakriti,array("qu")); }
    echo "<p class = pa >By AdirJiTuDavaH (".link_sutra("1.3.5").") :</p>\n";
    echo "<p class = pa >आदिर्ञिटुडवः (१.३.५) :</p>\n";
    display(0);
    $text = first(array("Yi","wu","qu"),array("","",""),0); // function first removes and replaces specific strings from the words. For details see function.php.
    echo "<p class = sa >tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);
}
/* satva vidhi, natva vidhi, numAgama vidhi, anusvArasandhi, parasavarNasandhi, upadhAdIrghavidhi on dhAtus (Acc to sahajabodha) */
// We are presuming that the verb entered is the verb with anusvAra and it markers, but without accent marks. I will have to revert back to handle without it markers and with accent marks specifically later.
/* subdhAtuSThivuSvakAdInAM satvapratiSedho vaktavyaH (vA 3499) */
if (in_array($fo,array("zWivu!","zvazk")) )
{
           echo "<p class = sa >By subdhAtuSThivuSvakAdInAM satvapratiSedho vaktavyaH (vA 3499) :</p>\n";
           echo "<p class = sa >सुब्धातुष्ठिवुष्वकादीनां सत्वप्रतिषेधो वक्तव्यः (व ३४९९) :</p>\n";
           display(0);            
}
/* dhAtvAdeH SaH saH (6.1.64), No naH (6.1.65) and upadhAyAm ca (8.2.78) */
elseif (arr($text,'/^[z]/') || arr($text,'/^[R]/') || arr($text,'/[iu][r][d]/')) 
{
           if (arr($text,'/^[z]/'))
           {
               $text = change('/^([z])/','s');
                echo "<p class = sa >By dhAtvAdeH SaH saH (".link_sutra("6.1.64").") :</p>\n";
                echo "<p class = sa >धात्वादेः षः सः (६.१.६४) :</p>\n";
                display(0);
                if (arr($text,'/^[s][wWqQR]/'))
                {
                    $text=two(array("s"),$Tu,array("s"),$tu,0);
                    echo "<p class = sa >By nimittApAye naimittikasyApyapAyaH (paribhASA) :</p>\n";
                    echo "<p class = sa >निमित्तापाये नैमित्तिकस्याप्यपायः (परिभाषा) :</p>\n";
                    display(0);                    
                }
           }
           if (arr($text,'/^[R]/'))
           {
               $text = change('/^([R])/','n');
                echo "<p class = sa >By No naH (".link_sutra("6.1.65").") :</p>\n";
                echo "<p class = sa >णो नः (६.१.६५) :</p>\n";
                display(0);                                      
                if (arr($text,'/^[n][wWqQR]/'))
                {
                    $text=two(array("n"),$Tu,array("n"),$tu,0);
                    echo "<p class = sa >By nimittApAye naimittikasyApyapAyaH (paribhASA) :</p>\n";
                    echo "<p class = sa >निमित्तापाये नैमित्तिकस्याप्यपायः (परिभाषा) :</p>\n";
                    display(0);                    
                }
           }
           if (arr($text,'/[iu][r][d]/'))
           {
               $text=three(array("ir","ur"),$hl,array("+"),array("Ir","Ur"),$hl,array("+"),0);
                echo "<p class = sa >By upadhAyAm ca (".link_sutra("8.2.78").") :</p>\n";
                echo "<p class = sa >उपधायां च (८.२.७८) :</p>\n";
                display(0);                                      
           }
           
}
/* Sopadeza dhAtu */
// This issue is pending. As per sahajabodha, this is needed. But I don't know where. So pending for now.
/* Nopadeza dhAtu */
// This issue is pending. As per sahajabodha, this is needed. But I don't know where. So pending for now.

/* daMzasaJjasvaJjAM zapi (6.4.25) */
if (sub(array("daMSa!","saYja!","svaYja!"),array("+Sap+"),$tiG,0) && ends(array($fo),array("daMSa!","zaYja!","zvaYja!"),4) )
{
    $text=two(array("daMSa!","saYja!","svaYja!"),array("+Sap+"),array("daSa!","saja!","svaja!"),array("+Sap+"),0);
    echo "<p class = sa >By daMzasaJjasvaJjAM zapi (".link_sutra("6.4.25").") :</p>\n"; 
    echo "<p class = sa >दंशसञ्जस्वञ्जां शपि (६.४.२५) :</p>\n";
    display(0);    
}
/* raJjezca (6.4.26) */
if (sub(array("raYja!"),array("+Sap+"),$tiG,0) && ends(array($fo),array("raYja!"),4) )
{
    $text=two(array("raYja!"),array("+Sap+"),array("raja!"),array("+Sap+"),0);
    echo "<p class = sa >By raJjezca (".link_sutra("6.4.26").") :</p>\n"; 
    echo "<p class = sa >रञ्जेश्च (६.४.२६) :</p>\n";
    display(0);    
}
/* numAgama as per sahajabodha */
// adding 'i' in it markers.
if (ends(array($fo),$iditverbs,4))
{
    $it=array_merge($it,array("i"));
}

/* iSugamiyamAM CaH (7.3.77) */
if (sub(array("iza!","gamx!","yama!"),array("+"),$shitpratyaya,0) && $lakAra!=="" && ends(array($fo),array("iza!","gamx!","yama!"),4))
{
    $text=three(array("iza!","gamx!","yama!"),array("+"),$shitpratyaya,array("iC","gaC","yaC"),array("+"),$shitpratyaya,0);
    echo "<p class = sa >By iSugamiyamAM CaH (".link_sutra("7.3.77").") :</p>\n"; 
    echo "<p class = sa >इषुगमियमां छः (७.३.७७) :</p>\n";
    display(0);
    $nomidelision=1; // We will remember this while doing halantyam and prevent halantyam application, because these are not upadeza
}
/* patch to stop elision of verbs ending with i!r */
if (arr(array($fo),'/[i][!][r]$/'))
{
    $nomidelision=1;
	echo "<p class = pa >By ira itsaJjJA vAcyA (vA) :</p>\n"; 
    echo "<p class = pa >इर इत्सञ्ज्ञा वाच्या (वा) :</p>\n";
    display(0);
}
/* vA bhrAzabhlAzabhramukramutrasitruTilaSaH (3.1.70) */
if (sub(array("wuBrASf!","wuBlASf!","BrASf!","BlASf!","Bramu!","kramu!","klamu!","trasI!","truwI!","laza!"),array("+"),array("Sap","Syan"),0) && $lakAra!=="" && ends(array($fo),array("wuBrASf!","wuBlASf!","BrASf!","BlASf!","Bramu!","kramu!","klamu!","trasI!","truwI!","laza!"),4) )
{
    $text=two(array("wuBrASf!","wuBlASf!","BrASf!","BlASf!","Bramu!","kramu!","klamu!","trasI!","truwI!","laza!"),array("+Sap"),array("wuBrASf!","wuBlASf!","BrASf!","BlASf!","Bramu!","kramu!","klamu!","trasI!","truwI!","laza!"),array("+Syan"),0);
    $text=two(array("wuBrASf!","wuBlASf!","BrASf!","BlASf!","Bramu!","kramu!","klamu!","trasI!","truwI!","laza!"),array("+Syan"),array("wuBrASf!","wuBlASf!","BrASf!","BlASf!","Bramu!","kramu!","klamu!","trasI!","truwI!","laza!"),array("+Sap"),1);
    echo "<p class = sa >By vA bhrAzabhlAzabhramukramutrasitruTilaSaH (".link_sutra("3.1.70").") :</p>\n"; 
    echo "<p class = sa >वा भ्राशभ्लाशभ्रमुक्रमुत्रसित्रुटिलषः (३.१.७०) :</p>\n";
    display(0);
}
/* kramaH parasmaipadeSu (7.3.76) */
if (sub(array("kramu!",),array("+"),$shitpratyaya,0) && $lakAra!=="" && ends(array($fo),array("kramu!"),4) && in_array($so,$tis) )
{
    $text=three(array("kramu!"),array("+"),$shitpratyaya,array("krAm"),array("+"),$shitpratyaya,0);
    echo "<p class = sa >By kramaH parasmaipadeSu (".link_sutra("7.3.76").") :</p>\n"; 
    echo "<p class = sa >क्रमः परस्मैपदेषु (७.३.७६) :</p>\n";
    display(0);
    $nomidelision=1; // We will remember this while doing halantyam and prevent halantyam application, because these are not upadeza
}
/* zamAmaSTAnAM dIrghaH zyani (7.3.74) */
if (sub(array("Samu!","tamu!","damu!","Sramu!","kzamu!","Bramu!","klamu!","madI!","mada!"),array("+"),array("Syan"),0) && $lakAra!=="" && ends(array($fo),array("Samu!","tamu!","damu!","Sramu!","kzamu!","Bramu!","klamu!","madI!","mada!"),4) )
{
    $text=two(array("Samu!","tamu!","damu!","Sramu!","kzamu!","Bramu!","klamu!","madI!","mada!"),array("+Syan"),array("SAm","tAm","dAm","SrAm","kzAm","BrAm","klAm","mAd","mAd"),array("+Syan"),0);
    echo "<p class = sa >By zamAmaSTAnAM dIrghaH zyani (".link_sutra("7.3.74").") :</p>\n"; 
    echo "<p class = sa >शमामष्टानां दीर्घः श्यनि (७.३.७४) :</p>\n";
    display(0);
    $nomidelision=1; // We will remember this while doing halantyam and prevent halantyam application, because these are not upadeza
}
/* SThivuklamyAcamAM ziti (7.3.75) */
if (sub(array("zWivu!","klamu!","Acamu!"),array("+"),$shitpratyaya,0) && $lakAra!=="" && ends(array($fo),array("zWivu!","klamu!","Acamu!"),4) )
{
    $text=three(array("zWivu!","klamu!","Acamu!"),array("+"),$shitpratyaya,array("zWIv","klAm","AcAm"),array("+"),$shitpratyaya,0);
    echo "<p class = sa >By SThivuklamyAcamAM ziti (".link_sutra("7.3.75").") :</p>\n"; 
    echo "<p class = sa >ष्ठिवुक्लम्याचमां शिति (७.३.७५) :</p>\n";
    display(0);
    $nomidelision=1; // We will remember this while doing halantyam and prevent halantyam application, because these are not upadeza
}
/* akSo'nyatarasyAm (3.1.75) */
if (sub(array("akzU!"),array("+"),array("Sap"),0) && $lakAra!=="" && ends(array($fo),array("akzU!"),4) )
{
    $text=three(array("akzU!"),array("+"),array("Sap"),array("akzU!"),array("+"),array("Snu"),1);
    echo "<p class = sa >By akSo'nyatarasyAm (".link_sutra("3.1.75").") :</p>\n"; 
    echo "<p class = sa >अक्षोऽन्यतरस्याम्‌ (३.१.७५) :</p>\n";
    display(0);
}
/* tanUkaraNe takSaH (3.1.76) */
if (sub(array("takzU!"),array("+"),array("Sap"),0) && $lakAra!=="" && ends(array($fo),array("takzU!"),4) && $_GET['cond45']==="1" )
{
    $text=three(array("takzU!"),array("+"),array("Sap"),array("takzU!"),array("+"),array("Snu"),1);
    echo "<p class = sa >By tanUkaraNe takSaH (".link_sutra("3.1.76").") :</p>\n"; 
    echo "<p class = sa >तनूकरणे तक्षः (३.१.७६) :</p>\n";
    display(0);
}
/* tiGzitsArvadhAtukam (3.4.113) */
// for zit pratyayas.
if (sub(array("+"),$shitpratyaya,blank(0),0) && $lakAra!=="")
{
    $sarvadhatuka=1;
    echo "<p class = pa >tiGzitsArvadhAtukam (".link_sutra("3.4.113").") :</p>\n"; 
    echo "<p class = pa >तिङ्शित्सार्वधातुकम्‌ (३.४.११३) :</p>\n";
    display(0);    
    $shit=1;
}
/* dAdhA ghvadAp (1.1.19) */
if ( in_array($fo,$ghuset) )
{
    echo "<p class = pa >dAdhA ghvadAp (".link_sutra("1.1.19").") :</p>\n"; 
    echo "<p class = pa >दाधा घ्वदाप्‌ (१.१.१९) :</p>\n";
    display(0);
    $ghu=1;
}
/* pvAdInAM hrasvaH (7.3.80) */
if (in_array($fo,$pvAdi) && sub(array("+"),$shitpratyaya,blank(0),0) && $fo!=="jyA" && ( $verbset==="curAdi" || ($verbset==="none" && ends(array($fo),$curAdi,4) ) ))
{
    $text=three(array("A","I","U","F",),array("+"),$shitpratyaya,array("a","i","u","f",),array("+"),$shitpratyaya,0);
    echo "<p class = sa >pvAdInAM hrasvaH (".link_sutra("7.3.80").") :</p>\n"; 
    echo "<p class = sa >प्वादीनां ह्रस्वः (७.३.८०) :</p>\n";
    display(0);        
}
/* pratyaya changes */
/* jherjus (3.4.108) */
if (in_array($so,array("Ji")) && ($lakAra==="ASIrliN"||$lakAra==="viDiliN") )
{
    $text=two(array("+"),array("Ji"),array("+"),array("jus"),0);
    echo "<p class = sa >jherjus (".link_sutra("3.4.108").") :</p>\n"; 
    echo "<p class = sa >झेर्जुस्‌ (३.४.१०८) :</p>\n";
    display(0);
    $jherjus=1;
}
/* jhasya ran (3.4.105) */
if (pr2(array("+"),array("Ja"),blank(0),array("+"),array("ran"),blank(0),$text)!==$text && in_array($so,array("Ja")) && in_array($lakAra,array("viDiliN","ASIrliN")))
{
    $text=pr2(array("+"),array("Ja"),blank(0),array("+"),array("ran"),blank(0),$text);
    echo "<p class = sa >By jhasya ran (".link_sutra("3.4.105").") :</p>\n"; 
    echo "<p class = sa >झस्य रन्‌ (३.४.१०५) :</p>\n";
    display(0);    
    $jhasyaran=1;
} else {$jhasyaran=0; }
/* liTastajhayorezirec (3.4.81) */
if (in_array($so,array("ta","Ja")) && $lakAra==="liw")
{
    $text=two(array("+"),array("ta","Ja"),array("+"),array("eS","irec"),0);
    echo "<p class = sa >By liTastajhayorezirec (".link_sutra("3.4.81").") :</p>\n"; 
    echo "<p class = sa >लिटस्तझयोरेशिरेच्‌ (३.४.८१) :</p>\n";
    display(0);
}
/* jho'ntaH (7.1.3) */
if (in_array($so,array("Ji")) && $lakAra!=="liw") // because liTastajhayorezirec.
{
    $text=two(array("+"),array("Ji"),array("+"),array("anti"),0);
    echo "<p class = sa >jho'ntaH (".link_sutra("7.1.3").") :</p>\n"; 
    echo "<p class = sa >झोऽन्तः (७.१.३) :</p>\n";
    display(0);    
}
/* AtmanepadeSvanataH (7.1.5) */
if (pr2(array("+"),array("Ja"),blank(0),array("+"),array("ata"),blank(0),$text)!==$text && in_array($so,array("Ja")) && $set===2)
{
    $text=pr2(array("+"),array("Ja"),blank(0),array("+"),array("ata"),blank(0),$text);
    echo "<p class = sa >By AtmanepadeSvanataH (".link_sutra("7.1.5").") :</p>\n"; 
    echo "<p class = sa >आत्मनेपदेष्वनतः (७.१.५) :</p>\n";
    display(0);    
}
/* jho'ntaH (7.1.3) */
elseif (in_array($so,array("Ja")) && $lakAra!=="liw") // because liTastajhayorezirec.
{
    $text=two(array("+"),array("Ja"),array("+"),array("anta"),0);
    echo "<p class = sa >jho'ntaH (".link_sutra("7.1.3").") :</p>\n"; 
    echo "<p class = sa >झोऽन्तः (७.१.३) :</p>\n";
    display(0);    
}
/* AtmanepadeSvanataH (7.1.5) */
elseif (!sub(array("a+"),array("Ja"),blank(0),0) && pr2(array("+"),array("Ja"),blank(0),array("+"),array("ata"),blank(0),$text)!==$text && in_array($so,array("Ja"))  )
{
    $text=pr2(array("+"),array("Ja"),blank(0),array("+"),array("ata"),blank(0),$text);
    echo "<p class = sa >By AtmanepadeSvanataH (".link_sutra("7.1.5").") :</p>\n"; 
    echo "<p class = sa >आत्मनेपदेष्वनतः (७.१.५) :</p>\n";
    display(0);    
}
/* UrNotezca pratiSedho vaktavyaH (vA) */
// Pending. Not giving proper results. Am pratyaya not functioning well.
/*if ($lakAra==="liw" && ends(array($fo),array("UrRuY"),4) )
{
    echo "<p class = sa >By UrNotezca pratiSedho vaktavyaH (vA) :</p>\n"; 
    echo "<p class = sa >ऊर्णोतेश्च प्रतिषेधो वक्तव्यः (वा) :</p>\n";
    display(0);
}*/
/* ijAdezca gurumato'nRcCaH (3.1.36) */
/*elseif ($lakAra==="liw" && arr(array($verb_without_anubandha),'/^[IUFXeEoO]/') && !ends(array($fo),array("fCa!"),4) )
{
    $text=two(array("+"),$tiG,array("+Am+"),$tiG,0);
    echo "<p class = sa >By ijAdezca gurumato'nRcCaH (".link_sutra("3.1.36").") :</p>\n";
	echo "<p class = sa >इजादेश्च गुरुमतोऽनृच्छः (३.१.३६) :</p>\n";
    display(0);
}*/
/* dayAyAysazca (3.1.37) */
/*elseif ($lakAra==="liw" && ends(array($fo),array("daya!","aya!","Asa!"),4) )
{
    $text=two(array("+"),$tiG,array("+Am+"),$tiG,0);
    echo "<p class = sa >By dayAyAysazca (".link_sutra("3.1.37").") :</p>\n";
	echo "<p class = sa >दयायासश्च (३.१.३७) :</p>\n";
    display(0);
}*/
/* uSavidajAgRbhyo'nyatarasyAm (3.1.38) */
/*elseif ($lakAra==="liw" && ends(array($fo),array("uza!","vida!","jAgf"),4) )
{
    $text=two(array("+"),$tiG,array("+Am+"),$tiG,1);
    echo "<p class = sa >By uSavidajAgRbhyo'nyatarasyAm (".link_sutra("3.1.38").") :</p>\n";
	echo "<p class = sa >उषविदजागृभ्योऽन्यतरस्याम्‌ (३.१.३८) :</p>\n";
    display(0);
}*/
/* bhIhrIbhRhuvAM zluvacca (3.1.39) */
/*elseif ($lakAra==="liw" && ends(array($fo),array("YiBI","hrI","quBfY","hu"),4) )
{
    $text=two(array("+"),$tiG,array("+Am+"),$tiG,1);
    echo "<p class = sa >By bhIhrIbhRhuvAM zluvacca (".link_sutra("3.1.39").") :</p>\n";
	echo "<p class = sa >भीह्रीभृवां श्लुवच्च (३.१.३९) :</p>\n";
    display(0);
	$zluvat=1;
	zlu();
}*/
/* kAspratyayAdAmamantre liTi (3.1.35) */
/*elseif ($lakAra==="liw" && $veda===0 && (anekAca($verb_without_anubandha) || $sanAdi!=='' || $verbset==="curAdi" || $fo==="kAsf!") )
{
    $text=two(array("+"),$tiG,array("+Am+"),$tiG,0);
    echo "<p class = sa >By kAspratyayAdAmamantre liTi (".link_sutra("3.1.35").") :</p>\n"; 
    echo "<p class = sa >कास्प्रत्ययादाममन्त्रे (३.१.३५) :</p>\n";
    display(0);
}*/
/* AmaH (2.4.81) and kRJcAnuprayujyate liTi (3.1.40) */
/*if ($lakAra==="liw" && sub(array("+Am+"),$tiG,blank(0),0) )
{
	$text=three(array("+Am+"),array("","",""),$tiG,array("+Am+"),array("kf+"),$tiG,0);
	$text=one(array("+Am+kf+"),array("+Am+BU+"),1);
	$text=one(array("+Am+kf+"),array("+Am+as+"),1);
    echo "<p class = sa >By AmaH (".link_sutra("2.4.81").") and kRJcAnuprayujyate liTi (".link_sutra("3.1.40").") :</p>\n"; 
    echo "<p class = sa >आमः (२.४.८१) तथा कृञ्चानुप्रयुज्यते लिटि (३.१.४०) :</p>\n";
    display(0);
}*/
/* parasmaipadAnAM NalatususthalthusaNalvamAH (3.4.82) */
if ($lakAra==="liw" && in_array($so,$tis) )
{
	$text = two(array("+"),array("Tas","Ta","tip","tas","Ji","sip","mip","vas","mas",),array("+"),array("aTus","a","Ral","atus","us","Tal","Ral","va","ma"),0);
    echo "<p class = sa >By parasmaipadAnAM NalatususthalthusaNalvamAH (".link_sutra("3.4.82").") :</p>\n";
    echo "<p class = sa >परस्मैपदानां णलतुसुस्थल्थुसणल्वमाः (३.४.८२) :</p>\n";
    display(0);		
}
if ($lakAra==="liw")
{
	if (in_array($so,array("tip","tas","Ji","Tas","Ta","mip","ta","AtAm","Ja","ATAm","iw"))) {$id_pratyaya="aniw";}
}
$svAdiajanta=array("zuY","ziY","SiY","qumiY","ciY","stfY","kfY","vfY","DuY","dUY","wudu","hi","pf","spf","df","ri","kzi","ciri","jiri");
$svAdihalanta=array_diff($svAdi,$svAdiajanta);
/* aniditAM hala upadhAyAH kGiti (6.4.24) */ 
if ( sub($aniditverbs,array("+"),array("Syan+","Sna+","SnA+","Snu+","Sa+"),0) )
{
    $text = three(array("N","Y","R","n","m","M"),$hl,array("+","a!+","i!r+","u!"),array("","","","","","",),$hl,array("+","a!+","i!r+","u!"),0);        
    echo "<p class = sa >aniditAM hala upadhAyAH kGiti (".link_sutra("6.4.24").") :</p>\n";
    echo "<p class = sa >अनिदितां हल उपधायाः क्ङिति (६.४.२४) :</p>\n";
    display(0); 
    $aniditAm = 1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
}
/* aniditAM hala upadhAyAH kGiti (6.4.24) */ 
if ( sub($aniditverbs,array("+Sap+",),$apit_sArvadhAtuka_pratyayas,0) )
{
    $text = three(array("N","Y","R","n","m","M"),$hl,array("+","a!+","i!r+","u!"),array("","","","","","",),$hl,array("+","a!+","i!r+","u!"),0);        
    echo "<p class = sa >aniditAM hala upadhAyAH kGiti (".link_sutra("6.4.24").") :</p>\n";
    echo "<p class = sa >अनिदितां हल उपधायाः क्ङिति (६.४.२४) :</p>\n";
    display(0);
    $aniditAm = 1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
}
/* ze tRmphAdInAM numvAcyaH (vA) */ 
if ( sub(array("tfPa!","tuPa!","dfPa!","fPa!","guPa!","uBa!","SuBa!","tupa!","tfha!"),array("Sa+"),blank(0),0) )
{
    $text = two(array("tfPa!","tuPa!","dfPa!","fPa!","guPa!","uBa!","SuBa!","tupa!","tfha!"),array("Sa+"),array("tfmPa!","tumPa!","dfmPa!","fmPa!","gumPa!","umBa!","SumBa!","tumpa!","tfnha!"),array("Sa+"),0);        
    echo "<p class = sa >ze tRmphAdInAM numvAcyaH (vA) :</p>\n";
    echo "<p class = sa >शे तृम्फादीनां नुम्वाच्यः (वा) :</p>\n";
    display(0);
}
/* sArvadhAtukamapit (1.1.7) */
if ( pr2(array("+"),$apit_sArvadhAtuka_pratyayas,blank(0),array("+fadfad"),$apit_sArvadhAtuka_pratyayas,blank(0),$text)!== $text && $sarvadhatuka===1 && !in_array("Sap",$vik) )
{
    $it=array_merge($it,array("N"));
    $itpratyaya=array_merge($itpratyaya,array("N"));
    echo "<p class = sa >sArvadhAtukamapit (".link_sutra("1.1.7").") :</p>\n"; 
    echo "<p class = sa >सार्वधातुकमपित्‌ (१.१.७) :</p>\n";
    display(0); 
	if (pr2(pc('ik'),array("+"),$apit_sArvadhAtuka_pratyayas,pc('ik'),array("+fadfad"),$apit_sArvadhAtuka_pratyayas,$text)!== $text)
	{
		echo "<p class = sa >By kGiti ca (".link_sutra("1.1.5").") :</p>\n"; 
		echo "<p class = hn >This prevents guNa. </p>\n"; 
		echo "<p class = sa >क्ङिति च (१.१.५) 1:</p>\n";
		echo "<p class = hn >अनेन सूत्रेण गुणनिषेधः विधीयते ।</p>\n";
		display(0);    
		$kGiti=1;	
	}
}
/* mInAternigame (7.3.81) */
if (sub(array("mIN",),$shitpratyaya,blank(0),0) && ends(array($fo),array("mIN"),4) && in_array($so,$tiG) && $veda===1)
{
    $text = two(array("mIN",),$shitpratyaya,array("miN",),$shitpratyaya,1);
    echo "<p class = sa >By mInAternigame (".link_sutra("7.3.81").") :</p>\n";
    echo "<p class = sa >मीनातेर्निगमे (७.३.८१) :</p>\n";  
    display(0);
}
/* separate itsaJjJAprakaraNam for tiGanta (According to sahajabodha text) */
/* lazakvataddhite (1.3.8) */
if (arr($text,'/[+][lSkKgGN]/') && $taddhita === 0  && in_array($so,$tiG) )
{
    it('/([+][lSkKgGN])/');
    echo "<p class = pa >By lazakvataddhite (".link_sutra("1.3.8").") :</p>\n";
    echo "<p class = pa >लशक्वतद्धिते (१.३.८) :</p>\n";
    display(0);
    $text = two(array("+"),array("Sap","Syan","SnA","SAnac","Satf","Snu","Sa"),array("+"),array("ap","yan","nA","Anac","atf","nu","a"),0);
    echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);
}
/* na vibhaktau tusmAH (1.3.4) */
//if (arr($text,'/[tTdDnsm]$/') && $pada=== "pratyaya" && sub(array("+"),$navibhaktau,blank(0),0) && in_array($so,$tiG))
if (arr($text,'/[tTdDnsm]$/') && $vibhakti===1)
{
    echo "<p class = pa >By na vibhaktau tusmAH (".link_sutra("1.3.4").")  :</p>\n";
    echo "<p class = pa >न विभक्तौ तुस्माः (१.३.४) :</p>\n";
    display(0);
    $tusma=1;
} else {$tusma=0; }
$inbetweenpratyaya=array("ap","yan","Ric","RiN","san","IyaN");
$inbetweeenreplace=array("a","ya","Ri","Ri","sa","Iya");
/* halantyam (1.3.3) and tasya lopaH (1.3.9) */
//if (in_array($so,$tiG) && ( sub(array("+"),$inbetweenpratyaya,array("+"),0) || (arr($text,'/['.flat($hl).']$/') && $tusma!==1) || sub($hl,array("+"),$vikaraNa,0) || $ad===1 || $rudhAdibhyaH===1 || in_array($lakAra,array("liw","luw","lfw","ASIrliN","luN","lfN","ArDaDAtukalew")) ))
if (in_array($so,$tiG) && ( sub(array("+"),$inbetweenpratyaya,array("+"),0) || (arr($text,'/['.flat($hl).']$/') && $tusma!==1) || sub($hl,array("+"),$vikaraNa,0) || $ad===1 || $rudhAdibhyaH===1))
{
    echo "<p class = pa >By halantyam (".link_sutra("1.3.3").") :</p>\n";
    echo "<p class = pa >हलन्त्यम्‌ (१.३.३) 1:</p>\n";
    display(0);
    if ( $rudhAdibhyaH===1)
    {
    $text=two($hl,array("+"),blank(count($hl)),array("+"),0);        
    }
    $text=three(array("+"),$inbetweenpratyaya,array("+"),array("+"),$inbetweeenreplace,array("+"),0);
	$text=three($hl,array("+"),array("jus"),blank(count($hl)),array("+"),array("jus"),0); // patch for jus, because it prevents application of halantyam artificially because of na vibhaktau tusmAH (s at end).
    if ($ad===1 || in_array($lakAra,array("liw","luw","lfw","ASIrliN","luN","lfN","ArDaDAtukalew")))
    {
        $text=three($hl,array("+"),$tiG1,blank(count($hl)),array("+"),$tiG1,0);
    }
    if ($tusma!==1)
    {
        itprat('/(['.flat($hl).']$)/');
        $text = last($hl,blank(count($hl)),0);   
    }
	$text = one(array("i!r"),array("i!"),0);
	$text = two($hl,array("+ran"),blank(count($hl)),array("+ran"),0);
    $text=three($hlplus,array("Ri"),array("+"),blank1("+",count($hlplus)),array("Ri"),array("+"),0);
    if ($nomidelision!==1 && ends(array($fo),$hl,0)) // Addition of ends function is to prevent application to kF -> kir converted halanta, which are not there in upadeza.
    {
    $text=three($hlplus,$vikaraNa,array("+"),blank1("+",count($hlplus)),$vikaraNa,array("+"),0);
    }
	$text=one(array("+si+"),array("+sic+"),0);
	$text=three($hl,array("+a+","+ca+"),$tiG1,blank(count($hl)),array("+a+","+ca+"),$tiG1,0);
	echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
}
/* cuTU (1.3.7) */
if (arr($text,'/[+][c][a][+]/') && in_array($so,$tiG)) // for caG
{
    it('/([+][c])/');
    echo "<p class = pa >By cuTU (".link_sutra("1.3.7").") :</p>\n";
    echo "<p class = pa >चुटू (१.३.७) :</p>\n";
    display(0);
    $text = one(array("+ca+"),array("+a+"),0);
    echo "<p class = sa >tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);
}
/* upadeze'janunAsika it (1.3.2)*/ 
// as idit is necessary for numAgama, it is placed here. otherwise with Sap, there is problem in mit function.
if (arr($text,'/['.flat($ac).'][!]/') && in_array($so,$tiG) )
{
    it('/(['.flat($ac).'][!])/');
    echo "<p class = pa >By upadeze'janunAsika it (".link_sutra("1.3.2").") :</p>\n";
    echo "<p class = pa >उपदेशेऽजनुनासिक इत्‌ (१.३.२) :</p>\n";
    display(0);
    $text = two($ac,array("!"),blank(count($ac)),array(""),0);
    echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);    
}
/* asyatesTuk (7.4.17) */
if (ends(array($fo),array("asu!"),4) && sub(array("as"),array("+a+"),blank(0),0) && $lakAra==="luN") 
{
	$text = one(array("as+a+"),array("asT+a+"),0);
	echo "<p class = sa >By asyativaktikhyAtibhyo'G (".link_sutra("3.1.52").") :</p>\n"; 
	echo "<p class = sa >अस्यतिवक्तिख्यातिभ्योऽङ्‌ (३.१.५२) :</p>\n";
	display(0);
}
/* vaca um (7.4.20) */
if (ends(array($fo),array("vaca!",),4) && sub(array("vac"),array("+"),array("a+"),0) && $lakAra==="luN")
{
	$text = one(array("vac+a+"),array("vauc+a+"),0);
	echo "<p class = sa >By vaca um (".link_sutra("7.4.20").") :</p>\n"; 
	echo "<p class = sa >वच उम्‌ (७.४.२०) :</p>\n";
	display(0);	
}	
/* asaMyogAlliT kit (1.2.5) */
if (!arr(array($verb_without_anubandha),'/['.pc('hl').']['.pc('hl').']$/') && $lakAra==="liw" && !in_array($so,array("tip","sip","mip")))
{
	echo "<p class = sa >By asaMyogAlliT kit (".link_sutra("1.2.25").") :</p>\n"; 
	echo "<p class = sa >असंयोगाल्लिट्‌ कित्‌ (१.२.५) :</p>\n";
	display(0);	
	$it = array_merge($it,array("k"));
	$itpratyaya = array_merge($itpratyaya,array("k"));
}
/* asaMyogAlliT kit (1.2.5) */
if ($verb_without_anubandha==="cakz" && $lakAra==="liw" && !in_array($so,array("tip","sip","mip")))
{
	echo "<p class = sa >By asaMyogAlliT kit (".link_sutra("1.2.25").") :</p>\n"; 
	echo "<p class = sa >असंयोगाल्लिट्‌ कित्‌ (१.२.५) :</p>\n";
	display(0);	
	$it = array_merge($it,array("k"));
	$itpratyaya = array_merge($itpratyaya,array("k"));
}	
/* zAsa idaGhaloH (6.4.34) */
if ( sub(array("SAs"),array("+a+"),blank(0),0) && ends(array($fo),array("SAsu!"),4) && $lakAra==="luN")
{
	$text = two(array("SAs"),array("+a+"),array("Sis"),array("+a+"),0);
	echo "<p class = sa >By zAsa idaGhaloH (".link_sutra("6.4.34").") :</p>\n";
	echo "<p class = sa >शास इदङ्हलोः (६.४.३४) :</p>\n";
	display(0);
}
/* nazimanyoraliTyetvaM vaktavyam (vA) */
if ( sub(array("naS","man"),array("+aN+"),blank(0),0) && ends(array($fo),array("RaSa!","mana!"),4) )
{
	$text = two(array("naS","man"),array("+a+"),array("neS","men"),array("+a+"),1);
	echo "<p class = sa >By nazimanyoraliTyetvaM vaktavyam (vA) :</p>\n";
	echo "<p class = sa >नशिमन्योरलिट्येत्वं वक्तव्यम्‌ (वा) :</p>\n";
	display(0);
}
/* Nau ca saMzcaGoH (6.1.31) */
if ( sub(array("Svi"),array("+Ri+"),array("a+"),0) && $lakAra==="luN" && $luGset===5) 
{
    $text = samprasarana(array("svi",),1);
	echo "<p class = sa >By Nau ca saMzcaGoH (".link_sutra("6.1.31").") :</p>\n"; 
	echo "<p class = sa >णौ च संश्चङोः (६.१.३१) :</p>\n";
	display(0);
}
/* hvaH samprasAraNam (6.1.32) */
if ( sub(array("hvA"),array("+Ri+"),array("a+"),0) && $lakAra==="luN" && $luGset===5) 
{
    $text = samprasarana(array("hvA",),1);
	echo "<p class = sa >By hvaH samprasAraNam (".link_sutra("6.1.32").") :</p>\n"; 
	echo "<p class = sa >ह्वः सम्प्रसारणम्‌ (६.१.३२) :</p>\n";
	display(0);
}
/* zvayateraH (7.4.18) */
if ( sub(array("Svi"),array("+a+"),blank(0),0) && ends(array($fo),array("wuo!Svi"),4) && $lakAra==="luN")
{
	$text = two(array("Svi"),array("+a+"),array("Sva"),array("+a+"),1); // 1 because of aG and caG both forms.
	echo "<p class = sa >By zvayateraH (".link_sutra("7.4.18").") :</p>\n";
	echo "<p class = sa >श्वयतेरः (७.४.१८) :</p>\n";
	display(0);
}
/* pataH pum (7.4.19) */
if (ends(array($fo),array("patx!"),4) && sub(array("pat"),array("+a+"),blank(0),0) && $lakAra==="luN") 
{
	$text = one(array("pat+a+"),array("papt+a+"),0);
	echo "<p class = sa >By pataH pum (".link_sutra("7.4.19").") :</p>\n"; 
	echo "<p class = sa >पतः पुम्‌ (७.४.१९) :</p>\n";
	display(0);
}
/* pataH pum (7.4.19) */
if (ends(array($fo),array("patx!"),4) && sub(array("pat"),array("+a+"),blank(0),0) && $lakAra==="luN") 
{
	$text = one(array("pat+a+"),array("papt+a+"),0);
	echo "<p class = sa >By pataH pum (".link_sutra("7.4.19").") :</p>\n"; 
	echo "<p class = sa >पतः पुम्‌ (७.४.१९) :</p>\n";
	display(0);
}
/* svApezcaGi (6.1.18) */
if ( sub(array("svap"),array("+Ri+"),array("a+"),0) && $lakAra==="luN" && $luGset===5) 
{
    $text = samprasarana(array("svap",),0);
	echo "<p class = sa >By svApezcaGi (".link_sutra("6.1.18").") :</p>\n"; 
	echo "<p class = sa >स्वापेश्चङि (६.१.१८) :</p>\n";
	display(0);
}
/* Adeca upadeze'ziti (6.1.45) */ 
// special patch for caG.
if (ends(array($verb_without_anubandha),array("e","o","E","O"),0) && in_array($lakAra,$ArdhadhAtuka_lakAra) && !sub(array("e","o","E","O"),array("+"),$shitpratyaya,0) && $luGset===5 && in_array($sanAdi,array("Ric","RiN")))
{
    $text=two(array("e","o","E","O"),array("+"),array("A","A","A","A"),array("+"),0);
	$text = two(array("e","o","E","O"),array("+Ri+"),array("A","A","A","A"),array("+Ri+"),0);
    echo "<p class = sa >By Adeca upadeze'ziti (".link_sutra("6.1.45").") :</p>\n"; 
    echo "<p class = sa >आदेच उपदेशेऽशिति (६.१.४५) :</p>\n";
    display(0);
}
/* hano vadha liGi (2.4.42) */ 
if (ends(array($fo),array("hana!"),4) && $lakAra==="ASIrliN")
{
	$text=two(array("han"),array("+"),array("vaD"),array("+"),0);
	echo "<p class = sa >By hano vadha liGi (".link_sutra("2.4.42)")." :</p>\n"; 
	echo "<p class = sa >हनो वध लिङि (२.४.४२) :</p>\n";
	display(0);    
}
$taGplus=array_merge($taG,array("anta","mahi","ata","i","ran")); // including the altered forms.
/* sArvadhAtuka leT - treatment of pratyayas */
$tiG_noanubandha=array("ti","tas","anti","si","Tas","Ta","mi","vas","mas","te","ite","ante","se","iTe","Dve","e","vahe","mahe","ta","AtAm","anta","TAs","ATAm","Dvam","i","vahi","mahi",);
if (in_array($lakAra,array("sArvaDAtukalew","ArDaDAtukalew")))
{
    /* leTo'DAtau (3.4.94) */
    $text = three(array("+"),blank(0),$tiG_noanubandha,array("+"),array("A"),$tiG_noanubandha,1);
    $text = three(array("+"),blank(0),$tiG_noanubandha,array("+"),array("a"),$tiG_noanubandha,0);
    $text = one(array("aa"),array("a"),0);
	$text = one(array("Aa"),array("A"),0);
    $text = one(array("aA"),array("A"),0);
    echo "<p class = sa >By leTo'DAtau (".link_sutra("3.4.94").") :</p>\n";
    echo "<p class = sa >लेटोऽडाटौ (३.४.९४) :</p>\n";  
    display(0);
    /* Ata ai (3.4.95) */
    if (pr2(array("+"),array("aite","aiTe","Aite","AiTe"),blank(0),array("+"),array("Ete","ETe","Ete","ETe"),blank(0),$text)!== $text)
    {
    $text = pr2(array("+"),array("aite","aiTe","Aite","AiTe"),blank(0),array("+"),array("Ete","ETe","Ete","ETe"),blank(0),$text);
    echo "<p class = sa >By Ata ai (".link_sutra("3.4.95").") :</p>\n";
    echo "<p class = sa >आत ऐ (३.४.९५) :</p>\n";  
    display(0);        
    }
    /* vaito'nyatra (3.4.96) */
    elseif (pr2(array("e"),blank(0),blank(0),array("E"),blank(0),blank(0),$text)!== $text)
    {
    $text = pr2(array("e"),blank(0),blank(0),array("E"),blank(0),blank(0),$text);
    echo "<p class = sa >By vaito'nyatra (".link_sutra("3.4.96").") :</p>\n";
    echo "<p class = sa >वैतोऽन्यत्र (३.४.९६) :</p>\n";  
    display(0);        
    }
    /* itazca lopaH parasmaipadeSu (3.4.97) */
    if (pr2(array("ati","anti","asi","ami","Ati","Anti","Asi","Ami"),blank(0),blank(0),array("at","an","as","am","At","An","As","Am"),blank(0),blank(0),$text)!== $text)
    {
    $text1 = pr2(array("ati","anti","asi","ami","Ati","Anti","Asi","Ami"),blank(0),blank(0),array("at","an","as","am","At","An","As","Am"),blank(0),blank(0),$text);
    $text = array_merge($text,$text1);
    echo "<p class = sa >By itazca lopaH parasmaipadeSu (".link_sutra("3.4.97").") :</p>\n";
    echo "<p class = sa >इतश्च लोपः परस्मैपदेषु (३.४.९७) :</p>\n";  
    display(0);        
    }
    /* sa uttamasya (3.4.98) */
    if (pr2(array("avas","amas","Avas","Amas"),blank(0),blank(0),array("ava","ama","Ava","Ama"),blank(0),blank(0),$text)!== $text)
    {
    $text1 = pr2(array("avas","amas","Avas","Amas"),blank(0),blank(0),array("ava","ama","Ava","Ama"),blank(0),blank(0),$text);
    $text = array_merge($text,$text1);
    echo "<p class = sa >By sa uttamasya (".link_sutra("3.4.98").") :</p>\n";
    echo "<p class = sa >स उत्तमस्य (३.४.९८) :</p>\n";  
    display(0);        
    }
    /* sibbahulaM leTi (3.1.34) */
    if ($lakAra==="ArDaDAtukalew" && $sanAdi==="")
    {
    $text = one(array("+"),array("+s"),0);
    echo "<p class = sa >By sibbahulaM leTi (".link_sutra("3.1.34").") :</p>\n";
    echo "<p class = sa >सिब्बहुलं लेटि (३.१.३४) :</p>\n";  
    display(0);
    /* sibbahulaM NidvadvaktavyaH (vA) */ 
        echo "<p class = sa >By sibbahulaM NidvadvaktavyaH (vA) :</p>\n";
        echo "<p class = sa >सिब्बहुलं णिद्वद्वक्तव्यः (वा) :</p>\n";
        $Nit = 1;
        display(0);
    }
}
/* loTo laGvat (3.4.85) */
if ( $lakAra==="low" && sub(array("+"),array("tas","Tas","Ta","mi","vas","mas"),blank(0),0))
{
    $GitlakAra=array_merge($GitlakAra,array("low"));
    $GitlakAra=array_unique($GitlakAra);
    echo "<p class = sa >By loTo laGvat (".link_sutra("3.4.85").") :</p>\n";
    echo "<p class = sa >लोटो लङ्वत्‌ (३.४.८५) :</p>\n";  
    display(0);
}
/* merniH (3.4.89) */
if ( pr2(array("+"),array("mi"),blank(0),array("+"),array("ni"),blank(0),$text)!==$text && $lakAra==="low")
{
    $text=pr2(array("+"),array("mi"),blank(0),array("+"),array("ni"),blank(0),$text);
    echo "<p class = sa >By merniH (".link_sutra("3.4.89").") :</p>\n";
    echo "<p class = sa >मेर्निः (३.४.८९) :</p>\n";  
    display(0);
}
/* tasthasthamipAM tAMtaMtAmaH (3.4.101) */
if ( pr2(array("+"),array("tas","Tas","Ta","mi"),blank(0),array("+"),array("tAm","tam","ta","am"),blank(0),$text)!==$text && in_array($lakAra,$GitlakAra) && in_array($so,$tiG) )
{
    $text=pr2(array("+"),array("tas","Tas","Ta","mi"),blank(0),array("+"),array("tAm","tam","ta","am"),blank(0),$text);
    echo "<p class = sa >By tasthasthamipAM tAMtaMtAmaH (".link_sutra("3.4.101").") :</p>\n";
    echo "<p class = sa >तस्थस्थमिपां तांतंतामः (३.४.१०१) :</p>\n";  
    display(0);
}
/* nityaM GitaH (3.4.99) */
if ( pr2(array("+"),array("vas","mas"),blank(0),array("+"),array("va","ma"),blank(0),$text)!==$text && in_array($lakAra,$GitlakAra) )
{
    $text=pr2(array("+"),array("vas","mas"),blank(0),array("+"),array("va","ma"),blank(0),$text);
    echo "<p class = sa >By nityaM GitaH (".link_sutra("3.4.99").") :</p>\n";
    echo "<p class = sa >नित्यं ङितः (३.४.९९) :</p>\n";  
    display(0);
}
/* liGaH sIyuT (3.4.102) */
if ( in_array($lakAra,array("viDiliN","ASIrliN")) && in_array($so,$taGplus) )
{
    $text=pr2(array("+"),$taGplus,blank(0),array("+sIy+"),$taGplus,blank(0),$text);
    echo "<p class = sa >By liGaH sIyuT (".link_sutra("3.4.102").") :</p>\n";
    echo "<p class = sa >लिङः सीयुट्‌ (३.४.१०२) :</p>\n";  
    display(0);
	$sIyuT=1;
} else { $siyuT=0; }
$tisremoved=array("ti","tas","anti","jus","si","Tas","Ta","mi","vas","mas","tAm","tam","ta","am","va","ma");
/* yAsuT parasmaipadeSUdAtto Gicca (3.4.103) */
if ( in_array($lakAra,array("viDiliN","ASIrliN")) && in_array($so,$tis) )
{
    $text=pr2(array("+"),$tisremoved,blank(0),array("+yAs+"),$tisremoved,blank(0),$text);
    echo "<p class = sa >By yAsuT parasmaipadeSUdAtto Gicca (".link_sutra("3.4.103").") :</p>\n";
    echo "<p class = sa >यासुट्‌ परस्मैपदेषूदात्तो ङिच्च (३.४.१०३) :</p>\n";  
    display(0);
	$Git=1;
	$yAsuT = 1;
	/* kidAziSi (3.4.104) */
	if ( in_array($lakAra,array("ASIrliN")) && in_array($so,$tis) )
	{
		echo "<p class = sa >By kidAziSi (".link_sutra("3.4.104").") :</p>\n";
		echo "<p class = sa >किदाशिषि (३.४.१०४) :</p>\n";  
		display(0);
		$Git=0;
		$kit = 1; $itpratyaya=array_merge($itpratyaya,array("k")); $it=array_merge($it,array("k")); 
	}
} else {$yAsuT=0;}
/* suT tithoH (3.4.107) */
if ( in_array($lakAra,array("viDiliN","ASIrliN")) && sub(array("+"),array("t","T","At","AT"),blank(0),0)  )
{
    $text=three(array("+"),array(""),array("t","T","At","AT"),array("+"),array(""),array("st","sT","Ast","AsT"),0);
    echo "<p class = sa >By suT tithoH (".link_sutra("3.4.107").") :</p>\n";
    echo "<p class = sa >सुट्‌ तिथोः (३.४.१०७) :</p>\n";  
    display(0);
    /* liGaH salopo'nantyasya (7.2.79) */
    if ( in_array($lakAra,array("viDiliN")) && sub(array("+yAs+"),array("s"),$al,0))
    {
        $text=three(array("+yAs"),array("s"),$al,array("+yA"),array(""),$al,0);
        echo "<p class = sa >By liGaH salopo'nantyasya (".link_sutra("7.2.79").") :</p>\n";
        echo "<p class = sa >लिङः सलोपोऽनन्त्यस्य (७.२.७९) :</p>\n";  
        display(0);
    }
    if (pr2(array("sIy+s"),$taG,blank(0),array("Iy+"),$taG,blank(0),$text)!== $text && in_array($lakAra,array("viDiliN")))
    {
        $text=pr2(array("sIy+s"),$taG,blank(0),array("Iy+"),$taG,blank(0),$text);
        echo "<p class = sa >By liGaH salopo'nantyasya (".link_sutra("7.2.79").") :</p>\n";
        echo "<p class = sa >लिङः सलोपोऽनन्त्यस्य (७.२.७९) :</p>\n";  
        display(0);
	}
}
/* liGaH salopo'nantyasya (7.2.79) */
if ( in_array($lakAra,array("viDiliN")) && sub(array("+yAs+"),blank(0),blank(0),0))
{
	$text=one(array("+yAs+"),array("+yA+"),0);
	echo "<p class = sa >By liGaH salopo'nantyasya (".link_sutra("7.2.79").") :</p>\n";
	echo "<p class = sa >लिङः सलोपोऽनन्त्यस्य (७.२.७९) :</p>\n";  
	display(0);
}
if (sub(array("sIy+As","sIy+"),blank(0),blank(0),0) && in_array($lakAra,array("viDiliN")))
{
	$text=one(array("sIy+As","sIy+"),array("Iy+A","Iy+"),0);
	echo "<p class = sa >By liGaH salopo'nantyasya (".link_sutra("7.2.79").") :</p>\n";
	echo "<p class = sa >लिङः सलोपोऽनन्त्यस्य (७.२.७९) :</p>\n";  
	display(0);
}
/* iTo't (3.4.106) */
if ( in_array($lakAra,array("viDiliN","ASIrliN")) && sub(array("+Iy+i","+sIy+i"),array(""),blank(0),0))
{
    $text=two(array("+Iy","+sIy"),array("+i"),array("+Iy","+sIy"),array("+a"),0);
    echo "<p class = sa >By iTo't (".link_sutra("3.4.106").") :</p>\n";
    echo "<p class = sa >इटोऽत्‌ (३.४.१०६) :</p>\n";  
    display(0);
}
/* ato yeyaH (7.2.80) */
if ( in_array($lakAra,array("viDiliN")) && sub(array("+yA"),array("+"),blank(0),0))
{
    $text=three(array("+yA"),array("+"),blank(0),array("+iy"),array("+"),blank(0),0);
    echo "<p class = sa >By ato yeyaH (".link_sutra("7.2.80").") :</p>\n";
    echo "<p class = sa >अतो येयः (७.२.८०) :</p>\n";  
    display(0);
}
$bhUsuvo=0;
/* itazca (3.4.100) */
$itazca=0; 
if ( pr2(array("+"),array("ti","anti","si","mi"),blank(0),array("+"),array("t","ant","s","m"),blank(0),$text)!==$text && in_array($lakAra,array("laN","viDiliN","ASIrliN","luN","lfN",)) )
{
    $text=pr2(array("+"),array("ti","anti","si","mi"),blank(0),array("+"),array("t","ant","s","m"),blank(0),$text);
    echo "<p class = sa >By itazca (".link_sutra("3.4.100").") :</p>\n";
    echo "<p class = sa >इतश्च (३.४.१००) 1:</p>\n";  
    display(0);
	$itazca=1;
}
/* liTastajhayorezirec (3.4.81) */
if ( pr2(array("+"),array("ta","Ja"),blank(0),array("+"),array("e","ire"),blank(0),$text)!==$text && $lakAra==="liw")
{
    $text=pr2(array("+"),array("ta","Ja"),blank(0),array("+"),array("e","ire"),blank(0),$text);
    echo "<p class = sa >By liTastajhayorezirec (".link_sutra("3.4.81").") :</p>\n";
    echo "<p class = sa >लिटस्तिझयोरेशिरेच्‌ (३.४.८१) :</p>\n";  
    display(0);
}
/* eruH (3.4.86) */
if ( pr2(array("+"),array("ti","anti"),blank(0),array("+"),array("tu","antu"),blank(0),$text)!==$text && $lakAra==="low")
{
    $text=pr2(array("+"),array("ti","anti"),blank(0),array("+"),array("tu","antu"),blank(0),$text);
    echo "<p class = sa >By eruH (".link_sutra("3.4.86").") :</p>\n";
    echo "<p class = sa >एरुः (३.४.८६) :</p>\n";  
    display(0);
}
/* serhyapicca (3.4.87) */
if ( pr2(array("+"),array("si"),blank(0),array("+"),array("hi"),blank(0),$text)!==$text && $lakAra==="low")
{
    $text=pr2(array("+"),array("si"),blank(0),array("+"),array("hi"),blank(0),$text);
    echo "<p class = sa >By serhyapicca (".link_sutra("3.4.87").") :</p>\n";
    echo "<p class = sa >सेर्ह्यपिच्च (३.४.८७) :</p>\n";  
    display(0);
}
/* thAsasse (3.4.80) */
if (sub(array("+"),array("TAs","saTAs","sATAs"),blank(0),0) && $pada === "pratyaya" && in_array($so,array("TAs")) && in_array($lakAra,array("law","liw","luw","lfw","sArvaDAtukalew","ArDaDAtukalew","low")))
{
    $text=one(array("+TAs","+saTAs","+sATAs"),array("+se","+sase","+sAse"),0);
    echo "<p class = sa >By thAsasse (".link_sutra("3.4.80").") :</p>\n";
    echo "<p class = sa >थासस्से (३.४.८०) :</p>\n";  
    display(0);
}
/* Tita AtmanepadAnAM Tere (3.4.79) */
//elseif (sub(array("+"),$taGplus,blank(0),0) && in_array($so,$taG) && in_array($lakAra,array("law","liw","luw","lfw","sArvaDAtukalew","ArDaDAtukalew","low")) && $jhasyaran!==1)
elseif ( in_array($so,$taG) && in_array($lakAra,array("law","liw","luw","lfw","sArvaDAtukalew","ArDaDAtukalew","low")) && $jhasyaran!==1)
{
    for($i=0;$i<count($text);$i++)
    {
        $text[$i]=Ti1($text[$i])."e";
    }
    echo "<p class = sa >By Tita AtmanepadAnAM Tere (".link_sutra("3.4.79").") :</p>\n";
    echo "<p class = sa >टित आत्मनेपदानां टेरे (३.४.७९) :</p>\n";  
    display(0);
}
/* vaito'nyatra (3.4.96) */
if (pr2(array("e"),blank(0),blank(0),array("E"),blank(0),blank(0),$text)!== $text && in_array($lakAra,array("sArvaDAtukalew","ArDaDAtukalew")))
{
	$text1 = pr2(array("e"),blank(0),blank(0),array("E"),blank(0),blank(0),$text);
	$text = array_merge($text,$text1);
	echo "<p class = sa >By vaito'nyatra (".link_sutra("3.4.96").") :</p>\n";
	echo "<p class = sa >वैतोऽन्यत्र (३.४.९६) :</p>\n";  
	display(0);        
}
/* eta ai (3.4.93) */
if ( pr2(array("+"),array("e","vahe","mahe"),blank(0),array("+"),array("E","vahE","mahE"),blank(0),$text)!==$text && $lakAra==="low")
{
    $text=pr2(array("+"),array("e","vahe","mahe"),blank(0),array("+"),array("E","vahE","mahE"),blank(0),$text);
    echo "<p class = sa >By eta ai (".link_sutra("3.4.93").") :</p>\n";
    echo "<p class = sa >एत ऐ (३.४.९३) :</p>\n";  
    display(0);
}
/* savAbhyAM vAmau (3.4.91) */
if ( pr2(array("se","ve"),array(""),blank(0),array("sva","vam"),array(""),blank(0),$text)!==$text && $lakAra==="low")
{
    $text=pr2(array("se","ve"),array(""),blank(0),array("sva","vam"),array(""),blank(0),$text);
    echo "<p class = sa >By savAbhyAM vAmau (".link_sutra("3.4.91").") :</p>\n";
    echo "<p class = sa >सवाभ्यां वामौ (३.४.९१) :</p>\n";  
    display(0);
}
/* itazca (3.4.100) */
if ( pr2(array("i"),array(""),blank(0),array(""),array(""),blank(0),$text)!==$text && in_array($lakAra,$GitlakAra) && in_array($so,$tis) && !sub(array("+"),array("hi","ni"),blank(0),0) )
{
    $text=pr2(array("i"),array(""),blank(0),array(""),array(""),blank(0),$text);
    echo "<p class = sa >By itazca (".link_sutra("3.4.100").") :</p>\n";
    echo "<p class = sa >इतश्च (३.४.१००) :</p>\n";  
    display(0);
    $itazca=1;
} else {$itazca=0; }

/* astisico'pRkte (7.3.96) */
// asti pending.
if ( ($sic!==0 && arr($text,'/[+][s][i][c][+]['.pc('hl').']$/')))
{
	$text = two(array("+sic+"),$hl,array("+sic+I"),$hl,0);
	echo "<p class = sa >By astisico'pRkte (".link_sutra("7.3.96").") :</p>\n"; 
	echo "<p class = sa >अस्तिसिचोऽपृक्ते (७.३.९६) :</p>\n";
	display(0);	
}
/* Am etaH (3.4.90) */
if ( pr2(array("e"),array(""),blank(0),array("Am"),array(""),blank(0),$text)!==$text && $lakAra==="low")
{
    $text=pr2(array("e"),array(""),blank(0),array("Am"),array(""),blank(0),$text);
    echo "<p class = sa >By Am etaH (".link_sutra("3.4.90").") :</p>\n";
    echo "<p class = sa >आमेतः (३.४.९०) :</p>\n";  
    display(0);
}
/* tuhyostAtaGGAziSyanyatarsyAm (7.1.35) */
if ( pr2(array("+"),array("tu","hi"),blank(0),array("+"),array("tAt","tAt"),blank(0),$text)!==$text && in_array($so,$tiG) )
{
    $text1=pr2(array("+"),array("tu","hi"),blank(0),array("+"),array("tAt","tAt"),blank(0),$text);
    $text = array_merge($text,$text1);
    echo "<p class = sa >By tuhyostAtaGGAziSyanyatarsyAm (".link_sutra("7.1.35").") :</p>\n"; 
    echo "<p class = sa >तुह्योस्तातङ्ङाशिष्यन्यतरस्याम्‌ (७.१.३५) :</p>\n";
    display(0);    
}
/* idito numdhAtoH (7.1.58) */
if ( in_array("i",$it) && $lakAra!=="" && ends(array($fo),array("cakzi!N"),4) )
{
    echo "<p class = sa >anuvRtti of 'padAnte' bars application of 'idito numdhAtoH' in case of cakSi!G. </p>\n";
    echo "<p class = sa >अन्ते इदित इति व्याख्यानात्‌ नुम्‌ तु न ।</p>\n";
    display(0);
}
/* idito numdhAtoH (7.1.58) */
elseif ( in_array("i",$it) && $lakAra!=="" && !ends(array($fo),$irendiditverbs,4) )
{
    $text = two($iditverbs2,array("+"),$iditverbs1,array("+"),0);
    echo "<p class = sa >By idito numdhAtoH (".link_sutra("7.1.58").") :</p>\n";
    echo "<p class = sa >इदितो नुम्धातोः (७.१.५८) 1:</p>\n";
    display(0);
}
/* aniditAM hala upadhAyAH kGiti (6.4.24) */ 
if ( ends(array($fo),$aniditverbs,4) && (in_array("N",$itpratyaya) || in_array("k",$itpratyaya)) && !in_array($sanAdi,array("Ric")) )
{
    $text = three(array("N","Y","R","n","m","M"),$hl,array("+"),array("","","","","","",),$hl,array("+"),0);        
    echo "<p class = sa >aniditAM hala upadhAyAH kGiti (".link_sutra("6.4.24").") :</p>\n";
    echo "<p class = sa >अनिदितां हल उपधायाः क्ङिति (६.४.२४) :</p>\n";
    display(0); 
    $aniditAm = 1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
}
/* syatAsI lRlutoH (3.1.33) */
if ( pr2(array("+"),blank(0),$tiG1,array("+"),array("sya"),$tiG1,$text)!==$text && in_array($lakAra,array("lfw","lfN")) )
{
    $text=pr2(array("+"),blank(0),$tiG1,array("+"),array("sya"),$tiG1,$text);
    echo "<p class = sa >By syatAsI lRlutoH (".link_sutra("3.1.33").") :</p>\n";
    echo "<p class = sa >स्यतासी लृलुटोः (३.१.३३) :</p>\n";  
    display(0);
	$syatAsI=1;
} else {$syatAsI = 0; }
/* syatAsI lRlutoH (3.1.33) */
if ( pr2(array("+"),blank(0),$tiG1,array("+"),array("tAs+"),$tiG1,$text)!==$text && in_array($lakAra,array("luw")) )
{
    $text=pr2(array("+"),blank(0),$tiG1,array("+"),array("tAs+"),$tiG1,$text);
    echo "<p class = sa >By syatAsI lRlutoH (".link_sutra("3.1.33").") :</p>\n";
    echo "<p class = sa >स्यतासी लृलुटोः (३.१.३३) :</p>\n";  
    display(0);
	$tAs=1;
} else { $tAs=0; }
/* luTaH prathamasya DAraurasaH (2.4.85) */
if ( pr2(array("+"),array("tAs+"),array("ti","tas","anti","te","Ate","ante"),array("+"),array("tAss+"),$tiG1,$text)!==$text && in_array($lakAra,array("luw")) && $tAs===1)
{
    $text=pr2(array("+"),array("tAs+"),array("ti","tas","anti","te","Ate","ante"),array("+"),array(""),array("tA","tAs+rO","tAs+ras","tA","tAs+rO","tAs+ras",),$text);
    echo "<p class = sa >By luTaH prathamasya DAraurasaH (".link_sutra("2.4.85").") :</p>\n";
    echo "<p class = sa >लुटः प्रथमस्य डारौरसः (२.४.८५) :</p>\n";  
    display(0);
	$syatAsI=1;
}
/* tAsastyorlopaH (7.4.50) */
// asti pending.
elseif (sub(array("tAs"),array("+"),array("s"),0) && $lakAra==="luw" && $tAs===1)
{
    $text=three(array("tAs"),array("+"),array("s"),array("tA"),array(""),array("s"),0);
    echo "<p class = sa >By tAsastyorlopaH (".link_sutra("7.4.50").") :</p>\n";
    echo "<p class = sa >तासस्त्योर्लोपः (७.४.५०) :</p>\n";  
    display(0);
}
/* dhi ca (8.2.25) */
elseif (sub(array("tAs"),array("+"),array("D"),0) && $lakAra==="luw" && $tAs===1)
{
    $text=three(array("tAs"),array("+"),array("D"),array("tA"),array(""),array("D"),0);
    echo "<p class = sa >By dhi ca (".link_sutra("8.2.25").") :</p>\n";
    echo "<p class = sa >धि च (८.२.२५) :</p>\n";  
    display(0);
}
/* ha eti (7.4.52) */
elseif (pr2(array("tAs"),array("+"),array("e"),array("tAh"),array(""),array("e"),$text)!==$text && $lakAra==="luw" && $tAs===1)
{
    $text=pr2(array("tAs"),array("+"),array("e"),array("tAh"),array(""),array("e"),$text);
    echo "<p class = sa >By ha eti (".link_sutra("7.4.52").") :</p>\n";
    echo "<p class = sa >ह एति (७.४.५२) :</p>\n";  
    display(0);
}
else // removing + after tAs pratyaya in other cases, because it leaves another + which interferes with the iDAgama prakriyA in our code.
{
    $text=two(array("tAs"),array("+"),array("tAs"),array(""),0);
}
/* ri ca (7.4.51) */
if (pr2(array("+"),array("tAs+rO","tAs+ras"),blank(0),array("+"),array("tArO","tAras"),blank(0),$text)!==$text && $lakAra==="luw" && $tAs===1)
{
    $text=pr2(array("+"),array("tAs+rO","tAs+ras"),blank(0),array("+"),array("tArO","tAras"),blank(0),$text);
    echo "<p class = sa >By ri ca (".link_sutra("7.4.51").") :</p>\n";
    echo "<p class = sa >रि च (७.४.५१) :</p>\n";  
    display(0);
}
/* ato guNe patch for syatAsI */
if (sub(array("+"),array("sya"),array("a","e","o"),0))
{
    $text=three(array("+"),array("sya"),array("a","e","o"),array("+"),array("sy"),array("a","e","o"),0);
    echo "<p class = sa >By ato guNe (".link_sutra("6.1.17").") :</p>\n";
    echo "<p class = sa >अतो गुणे (६.१.१७) :</p>\n";  
    display(0);    
}
/* anudAttasya cardupadhasyAnyatarasyAm (6.1.59) */ 
if ( sub(array("f"),$hlplus,prat('Jl'),0) && ends(array($fo),array("sfpx!","spfSa!","mfSa!","kfza!","tfpa!","dfpa!"),4)  )
{
    $text = three(array("sfp","spfS","mfS","kfz","tfp","dfp"),array("+"),prat('Jl'),array("sfap","spfaS","mfaS","kfaz","tfap","dfap"),array("+"),prat('Jl'),1);
    $text = three(array("sfap","spfaS","mfaS","kfaz","tfap","dfap"),array("+"),array("sa+"),array("sfp","spfS","mfS","kfz","tfp","dfp"),array("+"),array("sa+"),0);
    echo "<p class = sa >By anudAttasya cardupadhasyAnyatarasyAm (".link_sutra("6.1.59").") :</p>\n";
    echo "<p class = sa >अनुदात्तस्य चर्दुपधस्यान्यतरस्याम्‌ (६.१.५९) :</p>\n";
    display(0);
}
/* ksasyAci (7.3.72) */
if ( sub(array("+sa+"),$ac,blank(0),0) && in_array($so,$tiG) && $ksa===1) 
{
    $text=two(array("+sa+"),$ac,array("+s+"),$ac,0);
    echo "<p class = sa >By ksasyAci (".link_sutra("7.3.72").") :</p>\n"; 
    echo "<p class = sa >क्सस्याचि (७.३.७२) :</p>\n";
    display(0);
}
/* lugvA duhadihalihaguhAmAtmanepade dantye (7.3.73) */
if (sub(array("duh","dih","lih","guh",),array("+sa+"),array("t","T","d","D","n","v",),0) && ends(array($fo),array("duha!","diha!","liha!","guhU!"),4) && $ksa===1 && $luGset===7 && in_array($so,$taG) )
{
    $text = three(array("duh","dih","lih","guh",),array("+sa+"),array("t","T","d","D","n","v",),array("duh","dih","lih","guh",),array("+"),array("t","T","d","D","n","v",),1);
    echo "<p class = sa >By lugvA duhadihalihaguhAmAtmanepade dantye (".link_sutra("7.3.73").") :</p>\n";
    echo "<p class = sa >लुग्वा दुहदिहलिहगुहामात्मनेपदे दन्त्ये (७.३.७३) :</p>\n";
    display(0);
}
/* Ato GitaH (7.2.89) */
if ( arr($text,'/[a][+][A]/') && in_array($so,$tiG) ) // bad
{
    $text=two(array("a"),array("+A"),array("a"),array("+iy+"),0);
    echo "<p class = sa >By Ato GitaH (".link_sutra("7.2.89").") :</p>\n"; 
    echo "<p class = sa >आतो ङितः (७.२.८९) :</p>\n";
    display(0);    
}
/* Ato GitaH (7.2.89) */
if ( arr($text,'/[s][y][a][A]/') && in_array($so,$tiG) ) // bad
{
    $text=two(array("sya"),array("A"),array("sya"),array("+iy+"),0);
    echo "<p class = sa >By Ato GitaH (".link_sutra("7.2.89").") :</p>\n"; 
    echo "<p class = sa >आतो ङितः (७.२.८९) :</p>\n";
    display(0);   
}
/* ADuttamasya picca (3.4.92) */
if ( pr2(array("+"),array("ni","va","ma","E","vahE","mahE"),blank(0),array("+"),array("Ani","Ava","Ama","AE","AvahE","AmahE"),blank(0),$text)!==$text && $lakAra==="low")
{
    $text=pr2(array("+"),array("ni","va","ma","E","vahE","mahE"),blank(0),array("+"),array("Ani","Ava","Ama","AE","AvahE","AmahE"),blank(0),$text);
    echo "<p class = sa >By ADuttamasya picca (".link_sutra("3.4.92").") :</p>\n";
    echo "<p class = sa >आडुत्तमस्य पिच्च (३.४.९२) :</p>\n";  
    display(0);
    $Agama=array("Aw");   
}
/* akaH savarNe dIrghaH (6.1.101) patch for syatAsI */ 
if (sub(array("+sya"),array("A"),blank(0),0))
{
$text = two(array("+sya"),array("A"),array("+syA"),blank(1),0);
echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
display(0);
}    
/* ato dIrgho yaJi (7.3.101) patch for sya */
// for sya Agama.
if (sub(array("sya"),array("mi","v","ma"),blank(0),0) && $pada === "pratyaya" && in_array($so,$tiG) )
{
    $text = two(array("sya"),array("mi","v","ma"),array("syA"),array("mi","v","ma"),0);
    echo "<p class = sa >By ato dIrgho yaJi (".link_sutra("7.3.101").") :</p>\n";
    echo "<p class = sa >अतो दीर्घो यञि (७.३.१०१) :</p>\n";  
    display(3);
}
/* patch to append sIyuT if vowel follows it. */
$text = two(array("sIy+"),$ac,array("sIy"),$ac,0);
/* luGi vA daridrAterArdhadhAtuke vivakSite Alopo vAcyaH (vA) */
if ( sub(array("daridrA"),array("+"),blank(0),0) && ends(array($fo),array("daridrA"),4) && $ardhadhatuka===1 && $lakAra==="luN")
{
    $text=one(array("daridrA+"),array("daridr+"),1);
    echo "<p class = sa >By luGi vA daridrAterArdhadhAtuke vivakSite Alopo vAcyaH (vA) :</p>\n"; 
    echo "<p class = sa >लुङि वा दरिद्रातेरार्धधातुके विवक्षिते आलोपो वाच्यः (वा) :</p>\n";
    display(0);
}
/* dayaterdigi liTi (7.4.9) */ 
if (ends(array($fo),array("daya!"),4) && $lakAra==="liw")
{
	$text=three(array("day"),array("+"),$tiG1,array("digi"),array("+"),$tiG1,0);
	echo "<p class = sa >By dayaterdigi liTi (".link_sutra("7.4.9").") :</p>\n"; 
	echo "<p class = sa >दयतेर्दिगि लिटि (७.४.१) :</p>\n";
	display(0);
}
/* veJo vayiH (2.4.41) */ 
if (ends(array($fo),array("veY"),4) && $lakAra==="liw")
{
	$text=three(array("ve"),array("+"),$tiG1,array("vay"),array("+"),$tiG1,1);
	echo "<p class = sa >By veJo vayiH (".link_sutra("2.4.41").") :</p>\n"; 
	echo "<p class = sa >वेञो वयिः (२.४.४१) :</p>\n";
	display(0);
	if (in_array($so,array("TAs","Dvam","vahi","mahiN")) ) { $id_dhAtu="sew"; } // see p 335 sahajabodha part 2.
}
/* iDatyarttivyayatInAm (7.2.66) */ 
if (ends(array($fo),array("vyeY","f","iqa!"),4) && $lakAra==="liw" && $so==="sip")
{
	echo "<p class = sa >By iDatyarttivyayatInAm (".link_sutra("7.2.66").") :</p>\n"; 
	echo "<p class = sa >इडत्त्यर्तिव्ययतीनाम्‌ (७.२.६६) :</p>\n";
	display(0);
	$id_dhAtu="sew";
}
/* vibhASA sRjidRzoH (7.2.65) */ 
if (ends(array($fo),array("sfja!","dfSi!r"),4) && $lakAra==="liw" && $so==="sip")
{
	echo "<p class = sa >By vibhASA sRjidRzoH (".link_sutra("7.2.65").") :</p>\n"; 
	echo "<p class = sa >विभाषा सृजिदृशोः (७.२.६५) :</p>\n";
	display(0);
	$id_dhAtu="vew";
}
/* na vyo liTi (6.1.46) */ 
if (ends(array($fo),array("vyeY"),4) && $lakAra==="liw")
{
    echo "<p class = sa >By na vyo liTi (".link_sutra("6.1.46").") :</p>\n"; 
    echo "<p class = sa >न व्यो लिटि (६.१.४६) :</p>\n";
    display(0);
}
/* Adeca upadeze'ziti (6.1.45) */ 
elseif (ends(array($verb_without_anubandha),array("e","o","E","O"),0) && in_array($lakAra,$ArdhadhAtuka_lakAra) && !sub(array("e","o","E","O"),array("+"),$shitpratyaya,0))
{
    $text=two(array("e","o","E","O"),array("+"),array("A","A","A","A"),array("+"),0);
	$text = two(array("e","o","E","O"),array("+Ri+"),array("A","A","A","A"),array("+Ri+"),0);
    echo "<p class = sa >By Adeca upadeze'ziti (".link_sutra("6.1.45").") :</p>\n"; 
    echo "<p class = sa >आदेच उपदेशेऽशिति (६.१.४५) :</p>\n";
    display(0);
}
/* Ata au NalaH (7.1.34) */
if ($lakAra==="liw" && in_array($so,array("tip","mip")) )
{
	$text = two(array("A+"),array("Ra",),array("A+"),array("O"),0);
    echo "<p class = sa >By Ata au NalaH (".link_sutra("7.1.34").") :</p>\n";
    echo "<p class = sa >आत औ णलः (७.१.३४) :</p>\n";
    display(0);
}
/* kRJo'suTa iti vaktavyam (vA) */
if (ends(array($fo),array("qukfY"),4) && $lakAra==="liw" && in_array($so,array("sip","vas","mas","se","Dve","vahiN","mahiN")) && $us==="sam")
{
	$id_dhAtu="sew";
	echo "<p class = sa >By kRJo'suTa iti vaktavyam (vA)  :</p>\n"; 
	echo "<p class = hn >This sUtra makes this an seT dhAtu. </p>\n"; 
	echo "<p class = sa >कृञोऽसुट इति वक्तव्यम्‌ (वा) :</p>\n";
	echo "<p class = hn >अनेन सूत्रेण सेट्त्वं विधीयते ।</p>\n";
	display(0);
}
 /* kRsRbhRvRstudrusruzruvo liTi (7.2.13) */
elseif (ends(array($fo),array("qukfY","sf","quBfY","vfN","vfY","zwuY","dru","sru","Sru"),4) && $lakAra==="liw" && in_array($so,array("sip","vas","mas","TAs","Dvam","vahi","mahiN")))
{
	$id_dhAtu="aniw";
	echo "<p class = sa >By kRsRbhRvRstudrusruzruvo liTi (".link_sutra("7.2.13").") :</p>\n"; 
	echo "<p class = hn >This sUtra makes this an aniT dhAtu. </p>\n"; 
	echo "<p class = sa >कृसृभृवृस्तुद्रुस्रुश्रुवो लिटि (७.२.१३) :</p>\n";
	echo "<p class = hn >अनेन सूत्रेणानिट्त्वं विधीयते ।</p>\n";
	display(0);
}
/* kRsRbhRvRstudrusruzruvo liTi (7.2.13) */
elseif (!ends(array($fo),array("qukfY","sf","quBfY","vfN","vfY","zwuY","dru","sru","Sru"),4) && $lakAra==="liw" && in_array($so,array("sip","vas","mas","TAs","Dvam","vahi","mahiN")))
{
	if ($id_dhAtu!=="vew") { $id_dhAtu="sew"; }
	echo "<p class = sa >By kRsRbhRvRstudrusruzruvo liTi (".link_sutra("7.2.13").") :</p>\n"; 
	echo "<p class = hn >This sUtra makes a niyama that in liT pratyayas, only krAdi verbs are aniT, all others are seT. </p>\n"; 
	echo "<p class = sa >कृसृभृवृस्तुद्रुस्रुश्रुवो लिटि (७.२.१३) :</p>\n";
	echo "<p class = hn >क्रादय एव लिटि अनिटः, ततोऽन्ये सेटः इति नियम्यते ।</p>\n";
	display(0);
}
/* acastAsvatthalyaniTo nityam (7.2.61) and Rto bhAradvAjasya (".link_sutra("7.2.63").") */
elseif (arr(array($verb_without_anubandha),'/[aAiIuUFeEoO]$/') && $id_dhAtu==="aniw" && $lakAra==="liw" && in_array($so,array("sip")))
{
	$id_dhAtu="vew";
	echo "<p class = sa >By acastAsvatthalyaniTo nityam (".link_sutra("7.2.61").") and Rto bhAradvAjasya (".link_sutra("7.2.63").") :</p>\n"; 
	echo "<p class = hn >These two sUtras mandate veTtva when read together. </p>\n"; 
	echo "<p class = sa >अचस्तास्वत्थल्यनिटो नित्यम्‌ (७.२.६१) तथा ऋतो भारद्वाजस्य (७.२.६३) :</p>\n";
	echo "<p class = hn >वेट्त्वं विधीयते ।</p>\n";
	display(0);
}
/* upadeze'tvataH (7.2.62) and Rto bhAradvAjasya (7.2.63) */
elseif (arr(array($verb_without_anubandha),'/[a]/') && $id_dhAtu==="aniw" && $lakAra==="liw" && in_array($so,array("sip")))
{
	$id_dhAtu="vew";
	echo "<p class = sa >By upadeze'tvataH (".link_sutra("7.2.62").") and Rto bhAradvAjasya (".link_sutra("7.2.63").") :</p>\n"; 
	echo "<p class = hn >This sUtra mandates veTtva when read together. </p>\n"; 
	echo "<p class = sa >उपदेशेऽत्वतः (७.२.६२) तथा ऋतो भारद्वाजस्य (७.२.६३) :</p>\n";
	echo "<p class = hn >वेट्त्वं विधीयते ।</p>\n";
	display(0);
}
/* yamaramanamAtAM sakca (7.2.73) */ 
if ( sub(array("A","yam","ram","nam"),array("+sic+"),blank(0),0) && in_array($so,$tis) )
{
    $text=two(array("A","yam","ram","nam"),array("+sic+"),array("A","yam","ram","nam"),array("+sis+"),0);
    echo "<p class = sa >By yamaramanamAtAM sakca (".link_sutra("7.2.73").") :</p>\n"; 
    echo "<p class = sa >यमरमनमातां सक्च (७.२.७३) :</p>\n";
    display(0);
	$yamarama=1;
}
/* iTa ITi (8.2.28) */ 
if ( sub(array("+sis+"),array("I"),blank(0),0) && in_array($so,$tiG) )
{
    $text=two(array("+sis+"),array("I"),array("+si+"),array("I"),0);
    echo "<p class = sa >By iTa ITi (".link_sutra("8.2.28").") :</p>\n"; 
    echo "<p class = sa >इट ईटि (८.२.२८) :</p>\n";
    display(0);
    /* akaH savarNe dIrghaH (6.1.101) */ 
    if (sub(array("si"),array("+I"),blank(0),0))
    {
    $text = two(array("si"),array("+I"),array("sI"),blank(2),0);
    echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
    echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
    display(0);
    }
}
/* AtaH (3.4.110) */
if ( $sic!==0 && $so==="Ji" && sub(array("A"),array("+"),array("Ji"),0) )
{
	$text = three(array("A"),array("+"),array("Ji"),array("A"),array("+"),array("jus"),0);
	echo "<p class = sa >By AtaH (".link_sutra("3.4.110").") :</p>\n"; 
	echo "<p class = sa >आतः (३.४.११०) :</p>\n";
	display(0);	
}
/* rudhAdi gaNa is special in the sense that its vikaraNa is a mit pratyaya. So making a special provision for the same. */
if ($rudhAdibhyaH===1)
{
    $text = mit('/['.pc('hl').'][+]/','na',0);
    echo "<p class = sa >By rudhAdibhyaH znam (".link_sutra("3.1.78").") :</p>\n"; 
    echo "<p class = pa >By midaco'ntyAtparaH, lazakvataddhite, halantyam and tasya lopaH.</p>\n"; 
    echo "<p class = sa >रुधादिभ्यः श्नम्‌ (३.१.७८) :</p>\n";
    echo "<p class = pa >मिदचोऽन्त्यात्परः, लशक्वतद्धिते, हलन्त्यम्‌ तथा तस्य लोपः ।</p>\n";
    display(0);    
    $vik=array_merge($vik,array("Snam"));
    $set=2;
    if (arr($text,'/[n][a][n]/'))
    {
        $text = one(array("nan"),array("na"),0);
        echo "<p class = sa >By znAnnalopaH (".link_sutra("6.4.23").") :</p>\n";
        echo "<p class = sa >श्नान्नलोपः (६.४.२३) :</p>\n";
        display(0);            
    }
    if (pr2(array("na"),$hlplus,$apit_sArvadhAtuka_pratyayas,array("n"),$hlplus,$apit_sArvadhAtuka_pratyayas,$text)!==$text)
    {
        $text = pr2(array("na"),$hlplus,$apit_sArvadhAtuka_pratyayas,array("n"),$hlplus,$apit_sArvadhAtuka_pratyayas,$text);
        echo "<p class = sa >By znasorallopaH (".link_sutra("6.4.111").") :</p>\n";
        echo "<p class = sa >श्नसोरल्लोपः (६.४.१११) :</p>\n";
        display(0);            
    }
    if (sub(array("tfnah"),array("+"),blank(0),0))
    {
        $text = one(array("tfnah+"),array("tfRah+"),0);
        echo "<p class = sa >By RvarNAnnasya NatvaM vAcyam (vA 4969) :</p>\n";
        echo "<p class = sa >ऋवर्णान्नस्य णत्वं वाच्यम्‌ (वा ४९६९) :</p>\n";     
        display(0);
    }
    /* tRNaha im (7.3.92) */
    if (pr2(array("tfRah"),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,array("tfRaih"),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,$text)!==$text && in_array($so,$tiG))
    {
        $text = pr2(array("tfRah"),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,array("tfRaih"),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,$text);
        echo "<p class = sa >By tRNaha im (".link_sutra("7.3.92").") :</p>\n";
        echo "<p class = sa >तृणह इम्‌ (७.३.९२) :</p>\n";     
        display(0);
        $text = one(array("tfRaih"),array("tfReh"),0);
        echo "<p class = sa >By AdguNaH (".link_sutra("6.1.87").") :</p>\n";
        echo "<p class = sa >आद्गुणः (६.१.८७) :</p>\n";
        display(0);
    }
}
/* liTi dhAtoranabhyAsasya (6.1.8) */
if ($lakAra==="liw" && arr($text,'/^['.pc('ac').']/'))
{
	liT_ajAdi();
	$abhyAsa=1;
}
/* liTi dhAtoranabhyAsasya (6.1.8) */
if ($lakAra==="liw" && arr($text,'/^['.pc('hl').']/'))
{
	liT_halAdi();
	abhyAsa_halAdi();
	$abhyAsa=1;
}
/* liTi vayo yaH (6.1.38) and vazcAsyAnyatarasyAM kiti (6.1.39) */
if (ends(array($fo),array("veY"),4) && sub(array("vay"),array("+"),blank(0),0) && $lakAra==="liw" && in_array("k",$itpratyaya))
{
	$text = two(array("u+vay"),array("+"),array("Uy"),array("+"),0);
    echo "<p class = sa >By liTi vayo yaH (6.1.38)  (".link_sutra("6.1.38").") :</p>\n";
    echo "<p class = sa >लिटि वयो यः (६.१.३८) :</p>\n";
    display(0);    
	$text = two(array("Uy"),array("+"),array("Uv"),array("+"),1);
    echo "<p class = sa >By vazcAsyAnyatarasyAM kiti (6.1.38)  (".link_sutra("6.1.39").") :</p>\n";
    echo "<p class = sa >वश्चास्यान्यतरस्यां किति (६.१.३9) :</p>\n";
    display(0);    
}
/* vacisvapiyajAdInAM kiti (6.1.15) */
elseif (ends(array($fo),array("brUY","Yizvapa!","yaja!","quvapa!","vaha!","veY","vyeY","hveY","vada!","wuo!Svi!","vaca!"),4) && sub(array("vac","svap","yaj","vap","ve","vye","hve","vad","Sv"),array("+"),blank(0),0) && in_array("k",$itpratyaya))
{
    $text=two(array("vac","svap","yaj","vap","vah","ve","vye","hve","vad","Sv"),array("+"),array("uac","suap","iaj","uap","uah","ue","vie","hie","uad","Su"),array("+"),0);
    echo "<p class = sa >vacisvapiyajAdInAM kiti (6.1.15)  (".link_sutra("6.1.15").") :</p>\n"; 
    echo "<p class = sa >वचिस्वपियजादीनां किति (६.१.१५) :</p>\n";
    display(0);    
    $text = samprasarana(array("uac","suap","iaj","uap","ue","vie","hie","uad","Su"),0);
}
/* grahijyAvayivyadhivaSTivicativRzcatipRcCatibhRjjatInAM Giti ca (6.1.16) */
if (ends(array($fo),array("jyA","graha!","vaya!","vyaDa!","vaSa!","vyaca!","o!vraScU!","praCa!","Brasja!"),4) && ((sub(array("jyA","grah","vay","vyaD","vaS","vyac","vraSc","pracC","Brasj"),array("+"),$apit_sArvadhAtuka_pratyayas,0) && $sarvadhatuka===1) || in_array("N",$itpratyaya) || in_array("k",$itpratyaya)) && $so!=="mahiN")
{
    $text=two(array("jyA","grah","vay","vyaD","vaS","vyac","vraSc","pracC","Brasj"),array("+"),array("jiA","gfh","uy","viD","uS","vic","vfSc","pfcC","Bfsj"),array("+"),0);
    echo "<p class = sa >grahijyAvayivyadhivaSTivicativRzcatipRcCatibhRjjatInAM Giti ca (".link_sutra("6.1.16").") :</p>\n"; 
    echo "<p class = sa >ग्रहिज्यावयिव्यधिवष्टिविचतिवृश्चतिपृच्छतिभृज्जतीनां ङिति च (६.१.१६) :</p>\n";
    display(0);    
    $text = samprasarana(array("jyA","grah","vay","vyaD","vaS","vyac","vraSc","pracC","Brasj"),0);
	}
/* jAgro'viciNNalGitsu (7.3.85) */
// Not coded properly. Only for Ni coded. Rest Pending.
//if ( sub(array('jAgf'),array("+"),array("Ri+"),0) && $bhasyADhe!==1)
if ( sub(array('jAgf'),array("+"),blank(0),0) && $bhasyADhe!==1)
{
    $text = two(array('jAgf'),array("+"),array('jAgar'),array("+"),0);
    echo "<p class = sa >By jAgro'viciNNalGitsu (".link_sutra("7.3.85").") :</p>\n";
    echo "<p class = sa >जाग्रोऽविचिण्णल्ङित्सु (७.३.८५) :</p>\n";
    display(0);
	$jAgro=1;
} else {$jAgro=0;}
/* IzaH se (7.2.77) */
if (ends(array($fo),array("ISa!"),4) && sub(array("+"),array("s"),blank(0),0) && in_array($so,$tiG))
{
    $text=three(array("IS"),array("+"),array("s"),array("IS"),array("+"),array("s"),0);
    echo "<p class = sa >IzaH se (".link_sutra("7.2.77").") :</p>\n"; 
    echo "<p class = sa >ईषः से (७.२.७७) :</p>\n";
    display(0);    
}
/* dviSazca (3.4.112) */
if (ends(array($fo),array("dviza!"),4) && pr2(array("dviz"),array("+"),array("an"),array("dviz"),array("+"),array("us"),$text)!==$text && in_array($so,$tiG) && $lakAra==="laN")
{
    $text1=pr2(array("dviz"),array("+"),array("an"),array("dviz"),array("+"),array("us"),$text);
    $text = array_merge($text,$text1);
    echo "<p class = sa >By dviSazca (".link_sutra("3.4.112").") :</p>\n"; 
    echo "<p class = sa >द्विषश्च (३.४.११२) :</p>\n";
    display(0);    
}
/* zA hau (6.4.35) */
if ( pr2(array("SAs"),array("+"),array("hi"),array("SA"),array("+"),array("hi"),$text)!==$text && ends(array($fo),array("SAsu!"),4) && in_array($so,$tiG) && $sarvadhatuka===1)
{
    $text = pr2(array("SAs"),array("+"),array("hi"),array("SA"),array("+"),array("hi"),$text);
    echo "<p class = sa >By zA hau (".link_sutra("6.4.35").") :</p>\n";
    echo "<p class = sa >शा हौ (६.४.३५) :</p>\n";
    display(0);
        /* hujhalbhyo herdhiH (6.4.101) */
        $text=one(array("SA+hi"),array("SA+Di"),0);
        echo "<p class = sa >By hujhalbhyo herdhiH (".link_sutra("6.4.101").") :</p>\n"; 
        echo "<p class = sa >हुझल्भ्यो हेर्धिः (६.४.१०१) :</p>\n";
        display(0);
}
/* zAsa idaGhaloH (6.4.34) */
if ( pr2(array("SAs"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("Sis"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text)!==$text && ends(array($fo),array("SAsu!"),4) && in_array($so,$tiG) && $sarvadhatuka===1)
{
    $text = pr2(array("SAs"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("Sis"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >By zAsa idaGhaloH (".link_sutra("6.4.34").") :</p>\n";
    echo "<p class = sa >शास इदङ्हलोः (६.४.३४) :</p>\n";
    display(0);
}
/* zAsivasighasInAM ca (8.3.60) */
if ( sub(array("Sis","us"),array("+"),blank(0),0) && ends(array($fo),array("SAsu!","vasa!"),4) && in_array($so,$tiG) )
{
    $text = two(array("Sis","us"),array("+"),array("Siz","uz"),array("+"),0);
    echo "<p class = sa >By zAsivasighasInAM ca (".link_sutra("8.3.60").") :</p>\n";
    echo "<p class = sa >शासिवसिघसीनां च (८.३.६०) :</p>\n";
    display(0);
}
/* adaH sarveSAm (7.3.100) */
if ( pr2(array("ad"),array("+"),array("t","s"),array("ad"),array("+"),array("at","as"),$text)!==$text && ends(array($fo),array("ada!"),4) && in_array($so,$tiG) && $sarvadhatuka===1)
{
    $text = pr2(array("ad"),array("+"),array("t","s"),array("ad"),array("+"),array("at","as"),$text);
    echo "<p class = sa >By adaH sarveSAm (".link_sutra("7.3.100").") :</p>\n";
    echo "<p class = sa >अदः सर्वेषाम्‌ (७.३.१००) :</p>\n";
    display(0);
}
/* vido laTo vA (3.4.83) */
if ( pr2(array("vid"),array("+"),array("ti","tas","anti","si","Tas","Ta","mi","vas","mas"),array("vid"),array("+"),array("a","atus","us","Ta","aTus","a","a","va","ma"),$text)!==$text && ends(array($fo),array("vida!"),4) && in_array($so,$tiG) && $verbset==="adAdi")
{
    $text1 = pr2(array("vid"),array("+"),array("tas","anti","Tas","Ta","vas","mas"),array("vid"),array("+"),array("atus","us","aTus","a","va","ma"),$text);
    $text1 = pr2(array("vid"),array("+"),array("ti","si","mi",),array("ved"),array("+"),array("a","Ta","a"),$text1);
    $text = array_merge ($text,$text1);
    echo "<p class = sa >By vido laTo vA (".link_sutra("3.4.83").") :</p>\n";
    echo "<p class = sa >विदो लटो वा (३.४.८३) :</p>\n";
    display(0);
}
/* sijabhyastavidibhyazca (3.4.109) */ // for vid
if ( pr2(array("vid"),array("+"),array("an"),array("vid"),array("+"),array("us"),$text)!==$text && ends(array($fo),array("vida!"),4) && in_array($so,$tiG) && $verbset==="adAdi" && $lakAra==="laN")
{
    $text = pr2(array("vid"),array("+"),array("an"),array("vid"),array("+"),array("us"),$text);
    echo "<p class = sa >By sijabhyastavidibhyazca (".link_sutra("3.4.109").") :</p>\n";
    echo "<p class = sa >सिजभ्यस्तविदिभ्यश्च (३.४.1०९) :</p>\n";
    display(0);
}
/* sijabhyastavidibhyazca (3.4.109) */ // for abhyasta
elseif ( pr2(array("+"),array("an"),blank(0),array("+"),array("us"),blank(0),$text)!==$text && $abhyasta===1 && ends(array($fo),array("vida!"),4) && in_array($so,$tiG) && $verbset==="adAdi" && $lakAra==="laN")
{
    $text = pr2(array("vid"),array("+"),array("an"),array("vid"),array("+"),array("us"),$text);
    echo "<p class = sa >By sijabhyastavidibhyazca (".link_sutra("3.4.109").") :</p>\n";
    echo "<p class = sa >सिजभ्यस्तविदिभ्यश्च (३.४.1०९) :</p>\n";
    display(0);
}
/* mRjervRddhiH (7.2.114) */
if ( sub(array("mfj"),array("+"),blank(0),0) && in_array($so,$tiG)&& $fo==="mfjU!")
{
    $text=two(array("mfj"),array("+"),array("mArj"),array("+"),0);
    echo "<p class = sa >By mRjervRddhiH (".link_sutra("7.2.114").") :</p>\n"; 
    echo "<p class = sa >मृजेर्वृद्धिः (७.२.११४) :</p>\n";
    display(0);    
}
/* bruvaH paYcAnAmAdita Aho bruvaH (3.4.84) */
if ( sub(array("brU"),array("+"),array("ti","tas","anti","si","Tas"),0) && $lakAra==="law" )
{
    $text = three(array("brU"),array("+"),array("ti","tas","anti","si","Tas"),array("Ah"),array("+"),array("Ral","atus","us","Tal","aTus"),1);
    echo "<p class = sa >By bruvaH paYcAnAmAdita Aho bruvaH (".link_sutra("3.4.84").") :</p>\n";
    echo "<p class = sa >ब्रुवः पञ्चानामादित आहो ब्रुवः (३.४.८४) :</p>\n";  
    display(0);    
}
/* AhasthaH (8.2.35) */
if ( sub(array("Ah"),array("+"),array("Tal"),0) && $lakAra==="law" )
{
    $text = three(array("Ah"),array("+"),array("Tal"),array("AT"),array("+"),array("Tal"),0);
    echo "<p class = sa >By AhasthaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >आहस्थः (८.२.३५) :</p>\n";  
    display(0);    
}
/* bruva IT (7.3.93) */
if ( pr2(array("brU"),array("+"),$halAdi_pit_sArvadhAtuka_tiG_pratyayas,array("brU"),array("+I+"),$halAdi_pit_sArvadhAtuka_tiG_pratyayas,$text)!==$text && ends(array($fo),array("brUY"),4) && in_array($so,$tiG) )
{
    $text = pr2(array("brU"),array("+"),$halAdi_pit_sArvadhAtuka_tiG_pratyayas,array("brU"),array("+I+"),$halAdi_pit_sArvadhAtuka_tiG_pratyayas,$text);
    echo "<p class = sa >By bruva IT (".link_sutra("7.3.93").") :</p>\n";
    echo "<p class = sa >ब्रुव ईट्‌ (७.३.९३) :</p>\n";  
    display(0);    
}
/* turustuzamyamaH sArvadhAtuke (7.3.95) */
if (pr2(array("tu","ru","stu","Sam","am"),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,array("tu","ru","stu","Sam","am"),array("+I+"),$halAdi_pit_sArvadhAtuka_pratyayas,$text)!==$text && ends(array($fo),array("tu","ru","zwuY","Samu!","ama!"),4) && in_array($so,$tiG) )
{
    $text1 = pr2(array("tu","ru","stu","Sam","am"),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,array("tu","ru","stu","Sam","am"),array("+I+"),$halAdi_pit_sArvadhAtuka_pratyayas,$text);
    $text = array_merge($text,$text1);
    echo "<p class = sa >By turustuzamyamaH sArvadhAtuke (".link_sutra("7.3.95").") :</p>\n";
    echo "<p class = sa >तुरुस्तुशम्यमः सार्वधातुके (७.३.९५) :</p>\n";  
    display(0);    
}
/* guNo'pRkte (7.3.91) */
if ( sub(array("UrRu+"),$halAdi_pit_sArvadhAtuka_pratyayas,blank(0),0) && ends($vik,array("Slu","Sapluk"),1) && arr($text,'/[+][ts]$/') )
{
    echo "<p class = sa >By guNo'pRkte (".link_sutra("7.3.91").") :</p>\n"; 
    echo "<p class = sa >गुणोऽपृक्ते (७.३.९१) :</p>\n";
    display(0);    
}
/* UrNotervibhASA (7.3.90) */
elseif ( sub(array("UrRu+"),$halAdi_pit_sArvadhAtuka_pratyayas,blank(0),0) && ends($vik,array("Slu","Sapluk"),1))
{
    $textanother=pr2(array("UrRu+"),$halAdi_pit_sArvadhAtuka_pratyayas,blank(0),array("UrRO+"),$halAdi_pit_sArvadhAtuka_pratyayas,blank(0),$text);
    $text=array_merge($text,$textanother);
    echo "<p class = sa >By UrNotervibhASA (".link_sutra("7.3.90").") :</p>\n"; 
    echo "<p class = sa >ऊर्णोतेर्विभाषा (७.३.९०) :</p>\n";
    display(0);    
}
/* uto vRddhirluki hali (7.3.89) */
elseif ( pr2(array("u+"),$halAdi_pit_sArvadhAtuka_pratyayas,blank(0),array("O+"),$halAdi_pit_sArvadhAtuka_pratyayas,blank(0),$text)!==$text && ends($vik,array("Sapluk"),1))
{
    $text=pr2(array("u+"),$halAdi_pit_sArvadhAtuka_pratyayas,blank(0),array("O+"),$halAdi_pit_sArvadhAtuka_pratyayas,blank(0),$text);
    echo "<p class = sa >By uto vRddhirluki hali (".link_sutra("7.3.89").") :</p>\n"; 
    echo "<p class = sa >उतो वृद्धिर्लुकि हलि (७.३.८९) :</p>\n";
    display(0);    
}
/* adabhyastAt (7.1.4) */
if ($abhyasta===1 && pr2(array("+"),array("anti","antu","an"),blank(0),array("+"),array("ati","atu","us"),blank(0),$text)!==$text)
{
    $text = pr2(array("+"),array("anti","antu","an"),blank(0),array("+"),array("ati","atu","us"),blank(0),$text);
    echo "<p class = sa >By adabhyastAt (".link_sutra("7.1.4").") :</p>\n";
    echo "<p class = sa >अदभ्यस्तात्‌ (७.१.४) :</p>\n";
    display(0);
}
/* iddaridrasya (6.1.114) */
if (sub(array("daridrA"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,0) )
{
    $text = pr2(array("daridrA+"),$halAdi_apit_sArvadhAtuka_pratyayas,blank(0),array("daridri+"),$halAdi_apit_sArvadhAtuka_pratyayas,blank(0),$text);
    echo "<p class = sa >By iddaridrasya (".link_sutra("6.1.114").") :</p>\n";
    echo "<p class = sa >इद्दरिद्रस्य (६.४.११४) :</p>\n";
    display(0);
}

/* pvAdInAM hrasvaH (7.3.80) */
$shitpratyayareplace=array("a","ya","nu","na","nA","Aya","Ana");
if (in_array($fo,$pvAdi) && $shit===1 && $fo!=="jyA" && ( $verbset==="kryAdi" || ($verbset==="none" && ends(array($fo),$kryAdi,4) ) ))
{
    $text=three(array("A","I","U","F",),$shitpratyayareplace,array("+"),array("a","i","u","f",),$shitpratyayareplace,array("+"),0);
    echo "<p class = sa >pvAdInAM hrasvaH (".link_sutra("7.3.80").") :</p>\n"; 
    echo "<p class = sa >प्वादीनां ह्रस्वः (७.३.८०) :</p>\n";
    display(0);        
}
/* kRpo ro laH (8.2.18) */
if (sub(array("kfp"),array("+"),blank(0),0) && ends(array($fo),array("kfpa!","kfpU!"),4))
{
    $text=two(array("kfp"),array("+"),array("kxp"),array("+"),0);
    echo "<p class = sa >By kRpo ro laH (".link_sutra("8.2.18").") :</p>\n"; 
    echo "<p class = sa >कृपो रो लः (८.२.१८) :</p>\n";
    display(0);    
}
/* upadhAyAzca (7.1.101) */
if (sub(array("F"),$hl,array("+"),0) && in_array($so,$tiG)) 
{
    $text=three(array("F"),$hl,array("+"),array("ir"),$hl,array("+"),0);
    echo "<p class = sa >By upadhAyAzca (".link_sutra("7.1.101").") :</p>\n"; 
    echo "<p class = sa >उपधायाश्च (७.१.१०१) :</p>\n";
    display(0);    
}
/* upadhAyAM ca (8.2.78) */
if (arr($text,'/[iu][r]['.pc('hl').'][+]/'))
{
    $text=three(array("ir","ur"),$hl,array("+"),array("Ir","Ur"),$hl,array("+"),0);
     echo "<p class = sa >By upadhAyAm ca (".link_sutra("8.2.78").") :</p>\n";
     echo "<p class = sa >उपधायां च (८.२.७८) :</p>\n";
     display(0);                                      
}
/* prajane vIyateH (6.1.55) */
if (sub(array("vI",),array("+"),array("Ri"),0) && ends(array($fo),array("vI"),4) && in_array($so,$tiG) )
{
    $text=three(array("vI"),array("+"),array("Ri"),array("vA"),array("+"),array("Ri"),1);
    echo "<p class = sa >By prajane vIyateH (".link_sutra("6.1.55").") :</p>\n"; 
    echo "<p class = sa >प्रजने वीयतेः (६.१.५५) :</p>\n";
    display(0);    
}
/* Nau gamirabodhane (2.4.46) */
if (sub(array("i",),array("+"),array("Ri"),0) && $_GET['cond51']==='1' && in_array($so,$tiG) )
{
    $text=three(array("i"),array("+"),array("Ri"),array("gam"),array("+"),array("Ri"),0);
    echo "<p class = sa >By Nau gamirabodhane (".link_sutra("2.4.46").") :</p>\n"; 
    echo "<p class = sa >णौ गमिरबोधने (२.४.४६) :</p>\n";
    display(0);
	$Naugami = 1;
} else { $Naugami = 0; }
/* nityaM smayateH (6.1.57) */
if (sub(array("smi"),array("+"),array("Ri"),0) && ends(array($fo),array("zmiN"),4) && in_array($so,$tiG) )
{
    $text=three(array("smi"),array("+"),array("Ri"),array("smA"),array("+"),array("Ri"),0);
    echo "<p class = sa >By nityaM smayateH (".link_sutra("6.1.57").") :</p>\n"; 
    echo "<p class = sa >नित्यं स्मयतेः (६.१.५८) :</p>\n";
    display(0);    
}
/* bibheterhetubhaye (6.1.55) */
if (sub(array("BI",),array("+"),array("Ri"),0) && ends(array($fo),array("YiBI"),4) && in_array($so,$tiG) && $_GET['cond39']==='1')
{
    $text=three(array("BI"),array("+"),array("Ri"),array("BA"),array("+"),array("Ri"),1);
    echo "<p class = sa >By bibheterhetubhaye (".link_sutra("6.1.55").") :</p>\n"; 
    echo "<p class = sa >बिभेतेर्हेतुभये (६.१.५५) :</p>\n";
    display(0);    
}
/* krIGjInAM Nau (6.1.48) */
if (sub(array("krI","ji","i"),array("+"),array("Ri"),0) && ends(array($fo),array("qukrIY","ji","iN"),4) && in_array($so,$tiG) )
{
    $text=three(array("krI","ji","i"),array("+"),array("Ri"),array("krA","jA","A"),array("+"),array("Ri"),0);
    echo "<p class = sa >By krIGjInAM Nau (".link_sutra("6.1.48").") :</p>\n"; 
    echo "<p class = sa >क्रीङ्जीनां णौ (६.१.४८) :</p>\n";
    display(0);    
}
/* cisphurorNau (6.1.53) */
if (sub(array("ci","sPur"),array("+"),array("Ri+"),0) && ends(array($fo),array("ciY","sPura!"),4) && in_array($so,$tiG) )
{
    $text=three(array("ci","sPur"),array("+"),array("Ri+"),array("cA","sPAr"),array("+"),array("Ri+"),1);
    echo "<p class = sa >By cisphurorNau (".link_sutra("6.1.53").") :</p>\n"; 
    echo "<p class = sa >चिस्फुरोर्णौ (६.१.५३) :</p>\n";
    display(0);    
}
/* sphAyo vaH (7.3.41) */
if (sub(array("sPAy"),array("+"),array("Ri"),0) && ends(array($fo),array("sPAyI!"),4) )
{
    $text=three(array("sPAy"),array("+"),array("Ri"),array("sPAv"),array("+"),array("Ri"),0);    
    echo "<p class = sa >By sphAyo vaH (".link_sutra("7.3.41").") :</p>\n"; 
    echo "<p class = sa >स्फायो वः (७.३.४१) :</p>\n";
    display(0);
}
/* zaderagatau taH (7.3.42) */
if (sub(array("Sad"),array("+"),array("Ri"),0) && ends(array($fo),array("Sadx!"),4) )
{
    $text=three(array("Sad"),array("+"),array("Ri"),array("Sat"),array("+"),array("Ri"),0);    
    echo "<p class = sa >By zaderagatau taH (".link_sutra("7.3.42").") :</p>\n"; 
    echo "<p class = sa >शदेरगतौ तः (७.३.४२) :</p>\n";
    display(0);
}
/* ruhaH po'nyatarasyAm (7.3.43) */
if (sub(array("ruh"),array("+"),array("Ri"),0) && ends(array($fo),array("ruha!"),4) )
{
    $text=three(array("ruh"),array("+"),array("Ri"),array("rup"),array("+"),array("Ri"),1);    
    echo "<p class = sa >By ruhaH po'nyatarasyAm (".link_sutra("7.3.43").") :</p>\n"; 
    echo "<p class = sa >रुहो पोऽन्यतरस्याम्‌ (७.३.४३) :</p>\n";
    display(0);
}
/* radhijabhoraci (7.1.61) */
if (sub(array("raD","jaB"),array("+"),array("Ri"),0) && ends(array($fo),array("raDa!","jaBI!"),4) )
{
    $text=three(array("raD","jaB"),array("+"),array("Ri"),array("ranD","janB"),array("+"),array("Ri"),0);    
    echo "<p class = sa >By radhijabhoraci (".link_sutra("7.1.61").") :</p>\n"; 
    echo "<p class = sa >रधिजभोरचि (७.१.६१) :</p>\n";
    display(0);
}
/* labhezca (7.1.64) */
if (sub(array("laB"),array("+"),array("Ri"),0) && ends(array($fo),array("qulaBa!z"),4) )
{
    $text=three(array("laB"),array("+"),array("Ri"),array("lanB"),array("+"),array("Ri"),0);    
    echo "<p class = sa >By labhezca (".link_sutra("7.1.64").") :</p>\n"; 
    echo "<p class = sa >लभेश्च (७.१.६४) :</p>\n";
    display(0);
}
/* rabherazabliToH (7.1.63) */
if (sub(array("raB"),array("+"),array("Ri"),0) && ends(array($fo),array("raBa!"),4) )
{
    $text=three(array("raB"),array("+"),array("Ri"),array("ranB"),array("+"),array("Ri"),0);    
    echo "<p class = sa >By rabherazabliToH (".link_sutra("7.1.63").") :</p>\n"; 
    echo "<p class = sa >रभेरशब्लिटोः (७.१.६३) :</p>\n";
    display(0);
}
/* doSo Nau (6.4.90) */
if (sub(array("duz"),array("+"),array("Ri"),0) && ends(array($fo),array("duza!"),4) && $_GET['cond52']==='2')
{
    $text=three(array("duz"),array("+"),array("Ri"),array("dUz"),array("+"),array("Ri"),0);    
    echo "<p class = sa >By doSo Nau (".link_sutra("6.4.90").") :</p>\n"; 
    echo "<p class = sa >दोषो णौ (६.४.९०) :</p>\n";
    display(0);
}
/* vA cittavirAge (6.4.91) */
if (sub(array("duz"),array("+"),array("Ri"),0) && ends(array($fo),array("duza!"),4) && $_GET['cond52']==='1')
{
    $text=three(array("duz"),array("+"),array("Ri"),array("dUz"),array("+"),array("Ri"),1);    
    echo "<p class = sa >By vA cittavirAge (".link_sutra("6.4.91").") :</p>\n"; 
    echo "<p class = sa >वा चित्तविरागे (६.४.९१) :</p>\n";
    display(0);
}
/* Duplication because of caG */
if ($caG===1 && arr($text,'/^['.pc('hl').']/'))
{
	caG_halAdi();	
	abhyAsa_halAdi();
	$abhyAsa=1;
}

/* lIlornuglukAvanyatarsyAM snehanipAtane (7.3.39) */
if (sub(array("lA","lI"),array("+"),array("Ri"),0) && ends(array($fo),array("lA","lI","lIN"),4) && in_array($so,$tiG) )
{
    $text=three(array("lA","lI"),array("+"),array("Ri"),array("lAl","lIn"),array("+"),array("Ri"),1);
    echo "<p class = sa >By lIlornuglukAvanyatarsyAM snehanipAtane (".link_sutra("7.3.39").") :</p>\n"; 
    echo "<p class = sa >लीलोर्नुग्लुकावन्यतरस्यां स्नेहनिपातने (७.३.३९) :</p>\n";
    display(0);    
}
/* lugAgamastu tasya vaktavyaH (vA) */
if (sub(array("pA"),array("+"),array("Ri"),0) && ends(array($fo),array("pA"),4) && in_array($so,$tiG) && $verbset==="adAdi")
{
    $text=three(array("pA"),array("+"),array("Ri"),array("pAl"),array("+"),array("Ri"),0);
    echo "<p class = sa >By lugAgamastu tasya vaktavyaH (vA) :</p>\n"; 
    echo "<p class = sa >लुगागमस्तु तस्य वक्तव्यः (वा) :</p>\n";
    display(0);    
}
/* vo vidhUnane juk (7.3.38) */
elseif (sub(array("vA"),array("+"),array("Ri"),0) && ends(array($fo),array("vA"),4) && in_array($so,$tiG) )
{
    $text=three(array("vA"),array("+"),array("Ri"),array("vAj"),array("+"),array("Ri"),0);
    echo "<p class = sa >By vo vidhUnane juk (".link_sutra("7.3.38").") :</p>\n"; 
    echo "<p class = sa >वो विधूनने जुक्‌ (७.३.३८) :</p>\n";
    display(0);    
}
/* zAcCAsAhvAvyAvepAM yuk (7.3.37) */
elseif (sub(array("SA","CA","sA","hvA","vyA","ve","pA"),array("+"),array("Ri"),0) && ends(array($fo),array("So","Co","zo","hveY","vyeY","veY","pA","pE"),4) && in_array($so,$tiG) && !(ends(array($fo),array("pA"),2) && $verbset==="adAdi"))
{
    $text=three(array("SA","CA","sA","hvA","vyA","ve","pA"),array("+"),array("Ri"),array("SAy","CAy","sAy","hvAy","vyAy","vey","pAy"),array("+"),array("Ri"),0);
    echo "<p class = sa >By zAcCAsAhvAvyAvepAM yuk (".link_sutra("7.3.37").") :</p>\n"; 
    echo "<p class = sa >शाच्छासाह्वाव्यावेपां युक्‌ (७.३.३७) :</p>\n";
    display(0);    
}
/* arttihrIvlIrIknUyIkSmAyyAtAM puGNau (7.3.36) */
elseif (sub(array("f","hrI","vlI","rI","knUy","kzmAy","blI"),array("+"),array("Ri"),0) && ends(array($fo),array("f","hrI","vlI","rI","knUyI!","kzmAyI!","blI"),4) && in_array($so,$tiG))
{
    $text=two(array("f","hrI","vlI","rI","knUy","kzmAy","blI"),array("+"),array("fp","hrIp","vlIp","rIp","knUp","kzmAp","blIp"),array("+"),0);
    echo "<p class = sa >By arttihrIvlIrIknUyIkSmAyyAtAM puGNau (".link_sutra("7.3.36").") :</p>\n"; 
    echo "<p class = sa >अर्तिह्रीव्लीरीक्नूयीक्ष्माय्यातां पुङ्णौ (७.३.३६) :</p>\n";
    display(0);    
    echo "<p class = pa >sanAdyantA dhAtavaH (".link_sutra("3.1.32").") :</p>\n"; 
    echo "<p class = pa >सनाद्यन्ता धातवः (३.१.३२) :</p>\n";
    display(0);    
}
/* arttihrIvlIrIknUyIkSmAyyAtAM puGNau (7.3.36) */
elseif (sub(array("A"),array("+"),array("Ri"),0) && in_array($so,$tiG))
{
    $text=two(array("A"),array("+"),array("Ap"),array("+"),0);
    echo "<p class = sa >By arttihrIvlIrIknUyIkSmAyyAtAM puGNau (".link_sutra("7.3.36").") :</p>\n"; 
    echo "<p class = sa >अर्तिह्रीव्लीरीक्नूयीक्ष्माय्यातां पुङ्णौ (७.३.३६) :</p>\n";
    display(0);    
    echo "<p class = pa >sanAdyantA dhAtavaH (".link_sutra("3.1.32").") :</p>\n"; 
    echo "<p class = pa >सनाद्यन्ता धातवः (३.१.३२) :</p>\n";
    display(0);
}
/* bhiyo hetubhaye Suk (7.3.40) */
if (sub(array("BI",),array("+"),array("Ri"),0) && ends(array($fo),array("YiBI"),4) && in_array($so,$tiG) && $_GET['cond39']==='1')
{
    $text=three(array("BI"),array("+"),array("Ri"),array("BIz"),array("+"),array("Ri"),0);
    echo "<p class = sa >By bhiyo hetubhaye Suk (".link_sutra("7.3.40").") :</p>\n"; 
    echo "<p class = sa >भियो हेतुभये षुक्‌ (७.३.४०) :</p>\n";
    display(0);    
}
/* dhUJprIJornugvaktavyaH (vA) */
if (sub(array("prI","DU"),array("+"),array("Ri"),0) && ends(array($fo),array("prIY","DUY"),4) && in_array($so,$tiG) )
{
    $text=three(array("prI","DU"),array("+"),array("Ri"),array("prIn","DUn"),array("+"),array("Ri"),0);
    echo "<p class = sa >By dhUJprIJornugvaktavyaH (vA) :</p>\n"; 
    echo "<p class = sa >धूञ्प्रीञोर्नुग्वक्तव्यः (वा) :</p>\n";
    display(0);    
}
/* Ato lopa iTi ca (7.4.64) */
if ( (in_array("N",$itpratyaya)||in_array("k",$itpratyaya) ) && sub(array("A+"),array("a+"),blank(0),0) && $lakAra!=="" && $caG===1)
{
    $text=two(array("A"),array("+a+"),array(""),array("+a+"),0);
    echo "<p class = sa >By Ato lopa iTi ca (".link_sutra("7.4.64").") :</p>\n"; 
    echo "<p class = sa >आतो लोप इटि च (७.४.६४) :</p>\n";
    display(0);    
}
/* Ato lopa iTi ca (7.4.64) */
elseif ( (in_array("N",$itpratyaya)||in_array("k",$itpratyaya) ) && pr2(array("A"),array("+"),$tiG1,array("A"),array("+"),$tiG1,$text)!==$text && $lakAra!=="" )
{
    $text=pr2(array("A"),array("+"),$tiG1,array("A"),array("+"),$tiG1,$text);
    $text=two($hl,array("+sic+"),$hl,array("A+sic+"),0);
    echo "<p class = sa >By Ato lopa iTi ca (".link_sutra("7.4.64").") :</p>\n"; 
    echo "<p class = sa >आतो लोप इटि च (७.४.६४) :</p>\n";
    display(0);    
}
/* ze mucAdInAm (7.1.59) */
if ( $lakAra!=="" && sub($tudAdi_mucAdi,array("+"),array("a+"),0) && ($verbset==="tudAdi" || ($verbset==="none" && ends(array($fo),$tudAdi,4)) ) )
{
    $mucAdireplace=array("munc","lunp","vind","linp","zinc","Kind","kfnt","pinS");
    $text = two($tudAdi_mucAdi,array("+"),$mucAdireplace,array("+"),0);
    echo "<p class = sa >By ze mucAdInAm (".link_sutra("7.1.59").") :</p>\n";
    echo "<p class = sa >शे मुचादीनाम्‌ (७.१.५९) :</p>\n";
    display(0);
}
/* dhivikRNvyora ca (3.1.80) */
if (sub(array("Dinv","kfnv"),array("+"),array("u+"),0) && ends(array($fo),array("Divi!","kfvi!"),4) )
{
    $text=three(array("Dinv","kfnv"),array("+"),array("u+"),array("Dina","kfna"),array("+"),array("u+"),0);
    echo "<p class = sa >By dhivikRNvyora ca (".link_sutra("3.1.80").") :</p>\n"; 
    echo "<p class = sa >धिविकृण्व्योर च (३.१.८०) :</p>\n";
    display(0);    
}
/* radhijabhoraci (7.1.61) */
if (sub(array("raD","jaB"),array("+"),$ac,0) && ends(array($fo),array("raDa!","jaBa!"),4) )
{
    $text=three(array("raD","jaB"),array("+"),$ac,array("ranD","janB"),array("+"),$ac,0);
    echo "<p class = sa >By radhijabhoraci (".link_sutra("7.1.61").") :</p>\n"; 
    echo "<p class = sa >रधिजभोरचि (७.१.६१) :</p>\n";
    display(0);    
}
/* lopaH pibaterIccAbhyAsasya (7.4.4) */
if (sub(array("pa+pAy"),array("+"),array("Ri"),0) && ends(array($fo),array("pA"),4) && in_array($so,$tiG) && !(ends(array($fo),array("pA"),2) && $verbset==="adAdi") && $luGset===5)
{
    $text=three(array("pa+pAy"),array("+"),array("Ri"),array("pI+py"),array("+"),array("Ri"),0);
    echo "<p class = sa >By lopaH pibaterIccAbhyAsasya (".link_sutra("7.4.4").") :</p>\n"; 
    echo "<p class = sa >लोपः पिबतेरीच्चाभ्यासस्य (७.४.४) :</p>\n";
    display(0);    
}
/* tiSThaterit (7.4.5) */
if (sub(array("ta+sTAp"),array("+"),array("Ri"),0) && ends(array($fo),array("zWA"),4) && in_array($so,$tiG)  && $luGset===5)
{
    $text=three(array("ta+sTAp"),array("+"),array("Ri"),array("ta+sTip"),array("+"),array("Ri"),0);
    echo "<p class = sa >By tiSThaterit (".link_sutra("7.4.5").") :</p>\n"; 
    echo "<p class = sa >तिष्ठतेरित्‌ (७.४.५) :</p>\n";
    display(0);    
}
/* jighratervA (7.4.6) */
if (sub(array("ja+GrAp"),array("+"),array("Ri"),0) && ends(array($fo),array("GrA"),4) && in_array($so,$tiG)  && $luGset===5)
{
    $text=three(array("ja+GrAp"),array("+"),array("Ri"),array("ja+Grip"),array("+"),array("Ri"),1);
    echo "<p class = sa >By jighratervA (".link_sutra("7.4.6").") :</p>\n"; 
    echo "<p class = sa >जिघ्रतेर्वा (७.४.६) :</p>\n";
    display(0);    
}
/* yasya halaH (6.4.49) */
if ( sub($hl,array("ya+"),$ArdhadhAtuka_pratyayas,0) && $ardhadhatuka===1)
{ 
    $text = three($hl,array("ya+"),$ArdhadhAtuka_pratyayas,$hl,array("+"),$ArdhadhAtuka_pratyayas,0);
    echo "<p class = sa >By yasya halaH (".link_sutra("6.4.49").") :</p>\n";
    echo "<p class = sa >यस्य हलः (६.४.४९) :</p>\n";
    display(0);
	$atolopa=1;
}
/* patch for aG vikaraNa in luG lakAra */
if ($lakAra==='luN')
{
	/* ato guNe (6.1.17) */ // patch for aG+Ji.
	if (sub(array("a"),array("+a"),blank(0),0)  )
	{
		while(sub(array("a"),array("+a"),blank(0),0) !== false)
		{
				 $text = two(array("a"),array("+a"),blank(1),array("+a"),0);   
		}      
		echo "<p class = sa >By ato guNe (".link_sutra("6.1.17").") :</p>\n";
		echo "<p class = sa >अतो गुणे (६.१.१७) :</p>\n";  
		display(0);
	}
	$text = one(array("+a+"),array("+a"),0);
}
/* ato lopaH (6.4.48) */
//if ( sub(array("a+"),$vikaraNa_ArdhadhAtuka_pratyayas,$tiG1,0) || pr2(array("a+"),$ArdhadhAtuka_pratyayas,blank(0),array("+"),$ArdhadhAtuka_pratyayas,blank(0),$text)!==$text )
if ( in_array($fo,$curAdi_adanta) && $ardhadhatuka===1 && ($verbset==="none"||$verbset==="curAdi"))
{
	$text = one(array("+a+"),array("+a"),0);
    $text = three(array("a+"),$vikaraNa_ArdhadhAtuka_pratyayas,$tiG1,array(""),$vikaraNa_ArdhadhAtuka_pratyayas,$tiG1,0);
    $text = pr2(array("a+"),$ArdhadhAtuka_pratyayas,blank(0),array("+"),$ArdhadhAtuka_pratyayas,blank(0),$text);
	$text = one(array("Ay+sya",),array("Aya+sya"),0);
    echo "<p class = sa >By ato lopaH (".link_sutra("6.4.48").") :</p>\n";
    echo "<p class = sa >अतो लोपः (६.४.४८) 1:</p>\n";
    display(0);
    $atolopa=1;
}
/* ato lopaH (6.4.48) */
if ( sub(array("a"),array("+Ri"),blank(0),0) )
{ 
	$text = two(array("a"),array("+Ri"),array(""),array("+Ri"),0);
    echo "<p class = sa >By ato lopaH (".link_sutra("6.4.48").") :</p>\n";
    echo "<p class = sa >अतो लोपः (६.४.४८) :</p>\n";
    display(0);
    $atolopa=1;
}
/* aco JNiti (7.2.115) */ 
// more on enumeration kind. Not used regexes deliberately.
if ( sub($ac,array("+"),array("Ri+"),0) && $bhasyADhe!==1)
{ 
    $text = three($ac,array("+"),array("Ri+"),vriddhi($ac),array("+"),array("Ri+"),0);
    echo "<p class = sa >By aco JNiti (".link_sutra("7.2.115").") :</p>\n";
    echo "<p class = sa >अचो ञ्णिति (७.२.११५) :</p>\n";
    display(3);
}
// patch for ArdhadhAtuka leT.
if ( sub($ac,array("+"),blank(0),0) && $Nit===1 && $bhasyADhe!==1)
{
    $text = two($ac,array("+"),vriddhi($ac),array("+"),1);
    echo "<p class = sa >By aco JNiti (".link_sutra("7.2.115").") :</p>\n";
    echo "<p class = sa >अचो ञ्णिति (७.२.११५) :</p>\n";
    display(3);
}
/* patch for sautra dhAtu Rta */
$text=one(array("ft+Iya+"),array("ftIya+"),0);
/* ho hanterJNinneSu (7.3.54) */
if ( sub(array("han"),array("+"),blank(0),0) && arr(array($fo),'/[h][a][n]/') && !in_array($fo,array("ahan","dIrGAhan")) && (in_array("R",$it) || in_array("Y",$it) || in_array($sanAdi,array("Ric")) || in_array("R",$itpratyaya) || in_array("Y",$itpratyaya)) )
{
    $text = two(array("han"),array("+"),array("Gan"),array("+"),0);
    echo "<p class = sa >By ho hanterJNinneSu (".link_sutra("7.3.54").") :</p>\n";
    echo "<p class = sa >हो हन्तेर्ञ्णिन्नेषु (७.३.५४) :</p>\n";
    display(3); 
    $hohante=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else { $hohante=0; }
/* ata upadhAyAH (7.2.116) */ 
// more on enumeration kind. Not used regexes deliberately.
if ( arr($text,'/[a]['.pc('hl').'][+][R][i][+]/') && $atolopa!==1 && $Naugami!==1 && $jAgro!==1)
{
    $text = three(array("a"),$hl,array("+Ri+"),array("A"),$hl,array("+Ri+"),0);
    echo "<p class = sa >By ata upadhAyAH (".link_sutra("7.2.116").") :</p>\n";
    echo "<p class = sa >अत उपधायाः (७.२.११६) :</p>\n";
    display(0);
}
// patch for ArdhadhAtuka leT.
if ( sub(array("a"),$hl,blank(0),0) && $atolopa!==1 && $Naugami!==1 && $jAgro!==1 && $Nit===1)
{
    $text = three(array("a"),$hl,array("+"),array("A"),$hl,array("+"),1);
    echo "<p class = sa >By ata upadhAyAH (".link_sutra("7.2.116").") :</p>\n";
    echo "<p class = sa >अत उपधायाः (७.२.११६) :</p>\n";
    display(0);
}
/* hanasto'ciNNaloH (7.3.32) */ 
if ( sub(array("GAn"),array("+Ri+"),blank(0),0) )
{
    $text = two(array("GAn"),array("+Ri+"),array("GAt"),array("+Ri+"),0);
    echo "<p class = sa >By hanasto'ciNNaloH (".link_sutra("7.3.32").") :</p>\n";
    echo "<p class = sa >हनस्तोऽचिण्णलोः (७.३.३२) :</p>\n";
    display(0);
}
/* mitAM hrasvaH (6.4.92) */ 
if ( sub(array("A"),$hl,array("+Ri+"),0) && ends(array($fo),$mitcurAdiverbs,4) && ($verbset === "curAdi" || $verbset === "none" ) )
{
    $text = three(array("A"),$hl,array("+Ri+"),array("a"),$hl,array("+Ri+"),0);
    echo "<p class = sa >By mitAM hrasvaH (".link_sutra("6.4.92").") :</p>\n";
    echo "<p class = sa >मितां ह्रस्वः (६.४.९२) :</p>\n";
    display(0);
}
if ( sub(array("A"),$hl,array("+Ri+"),0) && ends(array($fo),$ghaTAdi_mit,4) && ($verbset === "BvAdi" || $verbset === "none" ) )
{
    $text = three(array("A"),$hl,array("+Ri+"),array("a"),$hl,array("+Ri+"),0);
    echo "<p class = sa >By mitAM hrasvaH (".link_sutra("6.4.92").") :</p>\n";
    echo "<p class = sa >मितां ह्रस्वः (६.४.९२) :</p>\n";
    display(0);
} 
/* cuTU (1.3.7) */
if (arr($text,'/[+][cjYwWqQR]/') && in_array($so,$tiG))
{
    it('/([+][cjYwWqQR])/');
    echo "<p class = pa >By cuTU (".link_sutra("1.3.7").") 1:</p>\n";
    echo "<p class = pa >चुटू (१.३.७) :</p>\n";
    display(0);
    $text = one(array("+jas","+wA","+jus","+Ri","+Ra"),array("+as","+A","+us","+i","+a"),0);
    echo "<p class = sa >tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);
}
/* lopo vyorvali (6.4.65) */
// patch for sIyuT
if ( sub(array("+Iy","+sIy"),array("+"),prat("vl"),0) && !in_array($sanAdi,array("Ric")))
{
    $text = three(array("+Iy","+sIy"),array("+"),prat("vl"),array("+I","+sI"),array(""),prat("vl"),0);    
	echo "<p class = sa >By lopo vyorvali (".link_sutra("6.4.65").") :</p>\n";
    echo "<p class = sa >लोपो व्योर्वलि (६.४.६५) :</p>\n";  
    display(0);
}
/* lopo vyorvali (6.4.65) */
// patch for Nijanta and ksa luG
if ( sub(array("+a+iy","+sa+iy","+sya+iy"),array("+"),prat("vl"),0) )
{
    $text = three(array("a+iy","+sa+iy"),array("+"),prat("vl"),array("a+i","+sa+i"),array("+"),prat("vl"),0);    
	echo "<p class = sa >By lopo vyorvali (".link_sutra("6.4.65").") :</p>\n";
    echo "<p class = sa >लोपो व्योर्वलि (६.४.६५) :</p>\n";  
    display(0);
}
/* AdguNaH (6.1.87) patch for sya Agama */
if (sub(array("sya+"),array("i+"),blank(0),0))
{
$text = two(array("sya+"),array("i+"),array("sy"),array("e"),0);
echo "<p class = sa >By AdguNaH (".link_sutra("6.1.87").") :</p>\n";
echo "<p class = sa >आद्गुणः (६.१.८७) :</p>\n";
display(0);
}
// patch to join sis with rest of pratyayas.
$text = one(array("sis+"),array("sis"),0);
/* halGyAbbhyo dIrghAtsutisyapRktaM hal (6.1.68) and apRkta ekAlpratyayaH (1.2.41) */
// GyAp pending. only hal handled now.
if (arr($text,'/['.pc('hl').'][+][sts]$/') && in_array($so,array("su!","tip","sip",)) && $rudAdibhyaH!==1 && $itazca!==1)
{
    echo "<p class = pa >By apRkta ekAlpratyayaH (".link_sutra("1.2.41").") :</p>\n";
    echo "<p class = pa >अपृक्त एकाल्प्रत्ययः (१.२.४१) :</p>\n";
    display(0);
}
if ((arr($text,'/['.pc('hl').'][+][sts]$/')  )&& in_array($so,array("su!","tip","sip")) && $rudAdibhyaH!==1 && $itazca!==1)
{
    $text = two($hl,array("+s","+t"),$hl,array("+","+"),0);
    echo "<p class = sa >By halGyAbbhyo dIrghAtsutisyapRktaM hal (".link_sutra("6.1.68").") :</p>\n";
    echo "<p class = sa >हल्ङ्‍याब्भ्यो दीर्घात्सुतिस्यपृक्तं हल्‌ (६.१.६८) :</p>\n";
    display(0); 
    $pada="pada"; // there is no pratyaya left now.
    $halGyAbbhyo=1;
}
/* eco'yavAyAvaH (7.1.78) */
// For Ni.
$ayavayavah = array("ay","av","Ay","Av");
if (sub(prat('ec'),array("+i+",),blank(0),0))
{
$text = two(prat('ec'),array("+i+",),$ayavayavah,array("+i+",),0);
echo "<p class = sa >By echo'yavAyAvaH (".link_sutra("7.1.78").") :</p>\n";
echo "<p class = sa >एचोऽयवायावः (७.१.७८) 1:</p>\n";
display(0);
}
/* ho hanterJNinneSu (7.3.54) */
if ( sub(array("han"),array("+"),blank(0),0) && arr(array($fo),'/[h][a][n]/') && !in_array($fo,array("ahan","dIrGAhan")) && (in_array("R",$it) || in_array("Y",$it) || in_array($sanAdi,array("Ric")) || in_array("R",$itpratyaya) || in_array("Y",$itpratyaya)) )
{
    $text = two(array("han"),array("+"),array("Gan"),array("+"),0);
    echo "<p class = sa >By ho hanterJNinneSu (".link_sutra("7.3.54").") :</p>\n";
    echo "<p class = sa >हो हन्तेर्ञ्णिन्नेषु (७.३.५४) :</p>\n";
    display(0); 
    $hohante=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else { $hohante=0; }
/* mitAM hrasvaH (6.4.92) */ 
if ( sub(array("A"),$hl,array("+i+"),0) && ends(array($fo),$mitcurAdiverbs,4) && ($verbset === "curAdi" || $verbset === "none" ) )
{ 
    $text = three(array("A"),$hl,array("+i+"),array("a"),$hl,array("+i+"),0);
    echo "<p class = sa >By mitAM hrasvaH (".link_sutra("6.4.92").") :</p>\n";
    echo "<p class = sa >मितां ह्रस्वः (६.४.९२) :</p>\n";
    display(0);
}
/* SaH pratyayasya (1.3.6) */
if (arr($text,'/[+][z]/') && $pada=== "pratyaya" && in_array($so,$tiG))
{
    it('/([+][z])/');
    echo "<p class = pa >By SaH pratyayasya (".link_sutra("1.3.6").") :</p>\n";
    echo "<p class = pa >षः प्रत्ययस्य (१.३.६) :</p>\n";
    display(0);
    $text = two(array("+"),array("z"),array("+"),array(""),0);
    echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);
}
/* jusi ca (7.3.83) */
if ( sub(array("i","I","u","U","f","F","x","X"),array("+"),array("us"),0) && $jherjus===1) // liT has 'us' which is not 'jus'
{
    $text=three(array("i","I","u","U","f","F","x","X"),array("+"),array("us"),array("e","e","o","o","ar","ar","al","al"),array("+"),array("us"),0);
    echo "<p class = sa >jusi ca (".link_sutra("7.3.83").") :</p>\n"; 
    echo "<p class = sa >जुसि च (७.३.८३) :</p>\n";
    display(0);        
}
/* riGzayagliGkSu (7.4.28) */
if (in_array("Sa",$vik) && (sub(array("f"),array("+a+"),blank(0),0) || (sub(array("f"),array("+y"),blank(0),0) && $lakAra!=="" )))
{
    $text=two(array("f"),array("+"),array("ri"),array("+"),0);
    echo "<p class = sa >By riGzayagliGkSu (".link_sutra("7.4.28").") :</p>\n"; 
    echo "<p class = sa >रिङ्शयग्लिङ्क्षु (७.४.२८) :</p>\n";
    display(0);    
}
/* ghasibhasorhali ca (6.4.100) */
if ( ends(array($fo),array("Gasa!","Basa!"),4) && pr2(array("Gas","Bas"),array("+"),$apit_sArvadhAtuka_pratyayas,array("Gs","Bs"),array("+"),$apit_sArvadhAtuka_pratyayas,$text)!==$text && $lakAra!=="" )
{
    $text=pr2(array("Gas","Bas"),array("+"),$apit_sArvadhAtuka_pratyayas,array("Gs","Bs"),array("+"),$apit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >By ghasibhasorhali ca (".link_sutra("6.4.100").") :</p>\n"; 
    echo "<p class = sa >घसिभसोर्हलि च (६.४.१००) :</p>\n";
    display(0);    
}
/* sArvadhAtukamapit (1.1.7) */
if (  !in_array("Sap",$vik) && sub(array("+"),$apit_sArvadhAtuka_pratyayas,blank(0),0) && $sarvadhatuka===1 )
{
    $it=array_merge($it,array("N"));
    $itpratyaya=array_merge($itpratyaya,array("N"));
    echo "<p class = sa >sArvadhAtukamapit (".link_sutra("1.1.7").") :</p>\n"; 
    echo "<p class = sa >सार्वधातुकमपित्‌ (१.१.७) :</p>\n";
    display(0);    
	if (pr2(pc('ik'),array("+"),$apit_sArvadhAtuka_pratyayas,pc('ik'),array("+fadfad"),$apit_sArvadhAtuka_pratyayas,$text)!== $text)
	{
		echo "<p class = sa >By kGiti ca (".link_sutra("1.1.5").") :</p>\n"; 
		echo "<p class = hn >This prevents guNa. </p>\n"; 
		echo "<p class = sa >क्ङिति च (१.१.५) 1:</p>\n";
		echo "<p class = hn >अनेन सूत्रेण गुणनिषेधः विधीयते ।</p>\n";
		display(0);    
		$kGiti=1;	
	}
}
/* jJAjanorjA (7.3.79) */
if ( ends(array($fo),array("janI!","jYA"),4) && in_array($so,$tiG) && sub(array("jan","jYA"),array("+"),$shitpratyayareplace,0) )
{
    $text=three(array("jan","jYA"),array("+"),$shitpratyayareplace,array("jA","jA"),array("+"),$shitpratyayareplace,0);
    echo "<p class = sa >By jJAjanorjA (".link_sutra("7.3.79").") :</p>\n"; 
    echo "<p class = sa >ज्ञाजनोर्जा (७.३.७९) :</p>\n";
    display(0);    
}
// jan is pending. jan is only in juhotyAdi in our database. So pending. Add it to divAdi.
/* dIdhIvevITAm (1.1.7) */
$didhI=0;
if ( in_array($fo,array("dIDIN","vevIN")) && ($sarvadhatuka===1 || $ardhadhatuka===1))
{
    echo "<p class = sa >dIdhIvevITAm (".link_sutra("1.1.7").") :</p>\n"; 
    echo "<p class = sa >दीधीवेवीटाम्‌ (१.१.७) :</p>\n";
    display(0);    
    $didhI=1;
}
/* miderguNaH (7.3.82) */
elseif ( sub(array("mid"),array("+"),blank(0),0) && ends(array($fo),array("mida!"),4))
{
    $text=two(array("mid"),array("+"),array("med"),array("+"),0);
    echo "<p class = sa >By miderguNaH (".link_sutra("7.3.82").") :</p>\n"; 
    echo "<p class = sa >मिदेर्गुणः (७.३.८२) :</p>\n";
    display(0);    
}
/* kGiti ca (1.1.5) */
elseif ( pr2(array("i","I","u","U","f","F","x","X"),array("nu+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("i","I","u","U","f","F","x","X"),array("inu+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text)!==$text && $sarvadhatuka===1 )
{
    echo "<p class = sa >By kGiti ca (".link_sutra("1.1.5").") :</p>\n"; 
    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >क्ङिति च (१.१.५) 3:</p>\n";
    echo "<p class = hn >अनेन सूत्रेण गुणनिषेधः विधीयते ।</p>\n";
    display(0);    
    $kGiti=1;
}
/* uzca (1.2.12) */
elseif (ends(array($verb_without_anubandha),array("f","F"),1) && in_array($so,$taG) && ($sic===1||$sIyuT===1) )
{
    echo "<p class = pa >By uzca (".link_sutra("1.2.12").") :</p>\n"; 
    echo "<p class = pa >उश्च (१.२.१२) :</p>\n";
    display(0);
	$kGiti=1;
	/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
	// patch because it applies only before aniT in this case. for iDAgama it is not applicable.
	if ( sub(array("f","F"),array("+"),array("isI"),0) && ($sarvadhatuka===1 || $ardhadhatuka===1) && !($ad===1 && sub(array("i","I","u","U","f","F","x","X"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,0) )  && $didhI!==1 && $bhUsuvo!==1)
	{
		$text=three(array("f","F"),array("+"),array("isI"),array("ar","ar"),array("+"),array("isI"),0);
		echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") :</p>\n"; 
		echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
		display(0);    
	}
}
/* yamo gandhane (1.2.15) */
elseif (ends(array($verb_without_anubandha),array("yam"),1) && in_array($so,$taG) && $sic===1 && $_GET['cond56']==='1')
{
    echo "<p class = pa >By yamo gandhane (".link_sutra("1.2.15").") :</p>\n"; 
    echo "<p class = pa >यमो गन्धने (१.२.१५) :</p>\n";
    display(0);
	$it = array_merge($it,array("k"));
}
/* hanaH sic (1.2.16) */
elseif (ends(array($verb_without_anubandha),array("han"),1) && in_array($so,$taG) && $sic===1)
{
    echo "<p class = pa >By hanaH sic (".link_sutra("1.2.16").") :</p>\n"; 
    echo "<p class = pa >हनः सिच्‌ (१.२.१६) :</p>\n";
    display(0);
	$it = array_merge($it,array("k"));
}
/* kGiti ca (1.1.5) */
elseif ( pr2(array("i","I","u","U","f","F","x","X"),array("+"),$ArdhadhAtuka_tiG_pratyayas,array("i","I","u","U","f","F","x","X"),array("+i"),$ArdhadhAtuka_tiG_pratyayas,$text)!==$text && $ardhadhatuka===1 && in_array("N",$itpratyaya) && !in_array($sanAdi,array("Ric")))
{
    echo "<p class = sa >By kGiti ca (".link_sutra("1.1.5").") :</p>\n"; 
    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >क्ङिति च (१.१.५) 4:</p>\n";
    echo "<p class = hn >अनेन सूत्रेण गुणनिषेधः विधीयते ।</p>\n";
    display(0);    
    $kGiti=1;
}
/* kGiti ca (1.1.5) */
elseif ( pr2(array("i","I","u","U","f","F","x","X"),$hlplus,$ArdhadhAtuka_tiG_pratyayas,array("i","I","u","U","f","F","x","X"),$hlplus,$ArdhadhAtuka_tiG_pratyayas,$text)!==$text && $ardhadhatuka===1 && in_array("N",$itpratyaya))
{
    echo "<p class = sa >By kGiti ca (".link_sutra("1.1.5").") :</p>\n"; 
    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >क्ङिति च (१.१.५) 5:</p>\n";
    echo "<p class = hn >अनेन सूत्रेण गुणनिषेधः विधीयते ।</p>\n";
    display(0);    
    $kGiti=1;
}
/* huznuvoH sArvadhAtuke (6.4.87) */
if ( sub($hl,array("+nu+","+u+",),$ajAdi_apit_sArvadhAtuka_tiG_pratyayas,0) && $sarvadhatuka===1 && $jherjus!==1)
{
}
elseif ( (sub($ac,array("+nu+"),$ajAdi_apit_sArvadhAtuka_tiG_pratyayas,0) || sub(array("juhu+"),$ajAdi_apit_sArvadhAtuka_tiG_pratyayas,blank(0),0))&& $sarvadhatuka===1 && $jherjus!==1)
{
    $text=three($ac,array("+nu+"),$ajAdi_apit_sArvadhAtuka_tiG_pratyayas,$ac,array("+nv+"),$ajAdi_apit_sArvadhAtuka_tiG_pratyayas,0);
    $text=two(array("hu+"),$ajAdi_apit_sArvadhAtuka_tiG_pratyayas,array("hv+"),$ajAdi_apit_sArvadhAtuka_tiG_pratyayas,0);
    echo "<p class = sa >By huznuvoH sArvadhAtuke (".link_sutra("6.4.87").") :</p>\n"; 
    echo "<p class = sa >हुश्नुवोः सार्वधातुके (६.४.८७) :</p>\n";
    display(0);    
}
/* zIGaH sArvadhAtuke guNaH (7.4.21) */
if ( sub(array("SI"),array("+"),$sArvadhAtuka_pratyayas,0) && in_array($so,$tiG) && $sarvadhatuka===1)
{
    $text=three(array("SI"),array("+"),$sArvadhAtuka_pratyayas,array("Se"),array("+"),$sArvadhAtuka_pratyayas,0);
    echo "<p class = sa >By zIGaH sArvadhAtuke guNaH (".link_sutra("7.4.21").") :</p>\n"; 
    echo "<p class = sa >शीङः सार्वधातुके गुणः (७.४.२१) :</p>\n";
    display(0);    
}
/* bahulaM Candasi */
// Adding ruT Agama in case of any pratyaya after any dhAtu in Candas. Pending.
/* zIGo ruT (7.1.6) */
if ( sub(array("Se"),array("+"),array("ate","ata","atAm"),0) && in_array($so,$tiG) )
{
    $text=three(array("Se"),array("+"),array("ate","ata","atAm"),array("Se"),array("+"),array("rate","rata","ratAm"),0);
    echo "<p class = sa >By zIGo ruT (".link_sutra("7.1.6").") :</p>\n"; 
    echo "<p class = sa >शीङो रुट्‌ (७.१.६) :</p>\n";
    display(0);    
}
/* sRjidRzorjhalyamakiti (6.1.58) */ 
if ( sub(array("sfj","dfS"),array("+"),prat('Jl'),0) && ends(array($fo),array("sfja!","dfSi!r"),4) && !in_array("k",$itpratyaya) )
{ 
    $text = three(array("sfj","dfS"),array("+"),prat('Jl'),array("sfaj","dfaS"),array("+"),prat('Jl'),0);
    echo "<p class = sa >By sRjidRzorjhalyamakiti (".link_sutra("6.1.58").") :</p>\n";
    echo "<p class = sa >सृजिदृशोर्झल्यकिति (६.१.५८) :</p>\n";
    display(0);
    $text = three(array("sfaj","dfaS"),array("+"),prat('Jl'),array("sraj","draS"),array("+"),prat('Jl'),0);
	echo "<p class = sa >By iko yaNaci (".link_sutra("6.1.77").") :</p>\n";
	echo "<p class = sa >इको यणचि (६.१.७७) :</p>\n";
	display(0);        
}
/* bhrasjo ropadhayoH ramanyatarasyAm (6.4.47) */ 
if ( sub(array("Brasj"),array("+"),blank(0),0) && ends(array($fo),array("Brasja!"),4) && $ardhadhatuka===1 )
{ 
    $text = three(array("Brasj"),array("+"),blank(0),array("Barj"),array("+"),blank(0),1);
    echo "<p class = sa >By bhrasjo ropadhayoH ramanyatarasyAm (".link_sutra("6.4.47").") :</p>\n";
    echo "<p class = sa >भ्रस्जो रोपधयोः रमन्यतरस्याम्‌ (६.४.४७) :</p>\n";
    display(0);
}
/* liGsicAvAtmanepadeSu (1.2.12) */
if ( sub($ik,$hlplus,array("s"),0) && in_array($so,$taG) && ($sic===1||$sIyuT===1) && ($id_dhAtu==="aniw" || $id_pratyaya==="aniw") )
{
    echo "<p class = pa >By liGsicAvAtmanepadeSu (".link_sutra("1.2.12").") :</p>\n"; 
    echo "<p class = pa >लिङ्सिचावात्मनेपदेषु (१.२.१२) :</p>\n";
    display(0);
	$itpratyaya = array_merge($itpratyaya,array("k"));
	$it = array_merge($it,array("k"));
	$kGiti=1;
}
/* vibhASorNoH (1.2.3) */ 
if ( ends(array($fo),array("UrRuY"),4) && sub(array("UrRu","orRu","UrRo","orRo","UrRunu","orRunu",),array("+"),blank(0),0) && in_array($lakAra,$ArdhadhAtuka_lakAra) && $id_dhAtu==="sew" && $id_pratyaya==="sew")
{
    $it=array_merge($it,array("N"));
    $itpratyaya=array_merge($itpratyaya,array("N"));
	echo "<p class = sa >By vibhASorNoH (".link_sutra("1.2.3").") :</p>\n"; 
	echo "<p class = sa >विभाषोर्णोः (१.२.३) :</p>\n";
	display(0);    
}    
/* eliding it markers from sic */ 
if (sub(array("+sic+",),blank(0),blank(0),0) && in_array($so,$tiG) )
{
    $text = one(array("+sic+",),array("+s",),0);
    echo "<p class = sa >By removing it markers from sic :</p>\n";
    echo "<p class = sa >इकारचकारयोर्लोपे कृते । :</p>\n";  
    display(0);
}
/* liGsicorAtmanepadeSu (7.2.42) */
if ((ends(array($fo),array("vfN","vfY"),4) || ends(array($fo),array("F"),1)) && in_array($so,$taG) && ($sic===1||$sIyuT===1) )
{
    $text=one(array("+"),array("+i"),1);
    echo "<p class = pa >By liGsicorAtmanepadeSu (".link_sutra("7.2.42").") :</p>\n"; 
    echo "<p class = pa >लिङ्सिचोरात्मनेपदेषु (७.२.४२) :</p>\n";
    display(0);
	$Agama=array_merge($Agama,array("iw"));
}
/* Rtazca saMyogAdeH (7.2.43) */
if (arr($text,'/['.pc('hl').']['.pc('hl').'][f][+]/') && in_array($so,$taG) && ($sic===1||$sIyuT===1) )
{
    $text=one(array("+"),array("+i"),1);
    echo "<p class = sa >By Rtazca saMyogAdeH (".link_sutra("7.2.43").") :</p>\n"; 
    echo "<p class = sa >ऋतश्च संयोगादेः (७.२.४३) :</p>\n";
    display(0);
	$Agama=array_merge($Agama,array("iw"));
}
/* vettervibhASA (7.1.7) */
if ( sub(array("vid"),array("+"),array("ate","ata","atAm"),0) && in_array($so,$tiG) && $verbset==="adAdi" )
{
    $text=three(array("vid"),array("+"),array("ate","ata","atAm"),array("vid"),array("+"),array("rate","rata","ratAm"),1);
    echo "<p class = sa >By vettervibhASA (".link_sutra("7.1.7").") :</p>\n"; 
    echo "<p class = sa >वेत्तेर्विभाषा (७.१.७) :</p>\n";
    display(0);    
}
/* kGiti ca (1.1.5) */
elseif ( pr2(array("i","I","u","U","f","F","x","X"),array("+"),$ArdhadhAtuka_tiG_pratyayas,array("i","I","u","U","f","F","x","X"),array("+i"),$ArdhadhAtuka_tiG_pratyayas,$text)!==$text && $ardhadhatuka===1 && in_array("N",$itpratyaya) && !in_array($sanAdi,array("Ric")))
{
    echo "<p class = sa >By kGiti ca (".link_sutra("1.1.5").") :</p>\n"; 
    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >क्ङिति च (१.१.५) 6:</p>\n";
    echo "<p class = hn >अनेन सूत्रेण गुणनिषेधः विधीयते ।</p>\n";
    display(0);    
    $kGiti=1;
}
/* heracaGi (7.3.56) */
if ( sub(array("ji+hi"),array("+"),array(""),0) && $caG!==1)
{
	$text = one(array("ji+hi+"),array("ji+Gi+"),0);
    echo "<p class = sa >By heracaGi (".link_sutra("7.3.56").") :</p>\n";    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >हेरचङि (७.३.५६) :</p>\n";
    display(0);
}
/* sanliTorjeH (7.3.57) */
if ( sub(array("ji+ji"),array("+"),array(""),0) && ($sanDai=="san" || $lakAra==="liw"))
{
	$text = one(array("ji+ji+"),array("ji+gi+"),0);
    echo "<p class = sa >By sanliTorjeH (".link_sutra("7.3.57").") :</p>\n";    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >सन्लिटोर्जेः (७.३.५७) :</p>\n";
    display(0);
}
/* vibhASA ceH (7.3.58) */
if ( sub(array("ci+ci"),array("+"),array(""),0) && ($sanDai=="san" || $lakAra==="liw"))
{
	$text = one(array("ci+ci+"),array("ci+ki+"),1);
    echo "<p class = sa >By vibhASA ceH (".link_sutra("7.3.58").") :</p>\n";    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >विभाषा चेः (७.३.५८) :</p>\n";
    display(0);
}
/* kGiti ca (1.1.5) */
if ( in_array("N",$itpratyaya) || in_array("k",$itpratyaya) )
{
    echo "<p class = sa >By kGiti ca (".link_sutra("1.1.5").") :</p>\n"; 
    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >क्ङिति च (१.१.५) 9:</p>\n";
    echo "<p class = hn >अनेन सूत्रेण गुणनिषेधः विधीयते ।</p>\n";
    display(0);
    $kGiti=1;
}
/* udoSThyapUrvasya (7.1.102) */
if ( sub(array("pF","PF","bF","BF","mF"),array("+"),$apit_sArvadhAtuka_pratyayas,0) && in_array($so,$tiG) )
{
    $text=three(array("F"),array("+"),$apit_sArvadhAtuka_pratyayas,array("ur"),array("+"),$apit_sArvadhAtuka_pratyayas,0);
    echo "<p class = sa >By udoSThyapUrvasya (".link_sutra("7.1.102").") :</p>\n"; 
    echo "<p class = sa >उदोष्ठ्यपूर्वस्य (७.१.१०२) :</p>\n";
    display(0);    
}
/* RRta iddhAtoH (7.1.100) */
elseif ( sub(array("F"),array("+"),array("nA+"),0) && ($sarvadhatuka===1 || $ardhadhatuka===1) )
{
    $text=three(array("F"),array("+"),array("nA+"),array("ir"),array("+"),array("nA+"),0);
    echo "<p class = sa >By RRta iddhAtoH (".link_sutra("7.1.100").") :</p>\n"; 
    echo "<p class = sa >ॠत इद्धातोः (७.१.१००) :</p>\n";
    display(0);    
}
/* RRta iddhAtoH (7.1.100) */
elseif ( pr2(array("F"),array("+"),$apit_sArvadhAtuka_pratyayas,array("ir"),array("+"),$apit_sArvadhAtuka_pratyayas,$text)!==$text && $sarvadhatuka===1 )
{
    $text=three(array("F"),array("+"),$apit_sArvadhAtuka_pratyayas,array("ir"),array("+"),$apit_sArvadhAtuka_pratyayas,0);
    echo "<p class = sa >By RRta iddhAtoH (".link_sutra("7.1.100").") :</p>\n"; 
    echo "<p class = sa >ॠत इद्धातोः (७.१.१००) :</p>\n";
    display(0);    
}
elseif ($vras===1) // escaping this - because vrazca... is tripAdI function.
{
}
elseif(sub(array("gup+Ay"),array(""),blank(0),0) )
{
	$text = one(array("gup+Ay",),array("gopAy",),0); // for accomodating Aya pratyaya
    echo "<p class = sa >pugantalaghUpadhasya ca (".link_sutra("7.3.86").") :</p>\n"; 
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 7:</p>\n";
    display(0);
}
elseif(sub(array("fp","hrIp","vlIp","rIp","knUp","kzmAp","blIp"),array("+"),array("i+"),0)  && $vijait!==1) // for puganta
{
	$text = three(array("fp","hrIp","vlIp","rIp","knUp","kzmAp","blIp"),array("+"),array("i+"),array("arp","hrep","vlep","rep","knop","kzmAp","blep"),array("+"),array("i+"),0); 
    echo "<p class = sa >pugantalaghUpadhasya ca (".link_sutra("7.3.86").") :</p>\n"; 
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 6:</p>\n";
    display(0);
}
/* pugantalaghUpadhasya ca (7.3.86) */
elseif ( sub(array("i","u","f","x"),$hl,array("+u+","+i+","+a+"),0) && $atolopa!==1 && ($sarvadhatuka===1 || $ardhadhatuka===1) && arr($text,'/[iufx]['.pc('hl').'][+]/')  && $didhI!==1 && $vijait!==1)
{
    $text=three(array("i","u","f","x"),$hl,array("+u+","+i+","+a+"),array("e","o","ar","al"),$hl,array("+u+","+i+","+a+"),0);
    echo "<p class = sa >pugantalaghUpadhasya ca (".link_sutra("7.3.86").") :</p>\n"; 
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 1:</p>\n";
    display(0);    
}
/* pugantalaghUpadhasya ca (7.3.86) */
elseif ( pr2(array("i","u","f","x"),$hlplus,$ArdhadhAtuka_tiG_pratyayas,array("e","o","ar","al"),$hlplus,$ArdhadhAtuka_tiG_pratyayas,$text)!==$text && $atolopa!==1 && $ardhadhatuka===1 && $kGiti!==1  && $didhI!==1 && $vijait!==1 && $sIyuT!==1 && $ksa!==1 && $vijait!==1 && !ends($itpratyaya,array("k","N"),2) )
{
    $text=pr2(array("i","u","f","x"),$hlplus,$ArdhadhAtuka_tiG_pratyayas,array("e","o","ar","al"),$hlplus,$ArdhadhAtuka_tiG_pratyayas,$text);
	$text = three(array("GarR","tarR","arR","kzeR",),array("+"),array("sta","sTAs"),array("GfR","tfR","fR","kziR"),array("+"),array("sta","sTAs"),1); // see sahajabodha part 2 page 250.
    echo "<p class = sa >pugantalaghUpadhasya ca (".link_sutra("7.3.86").") :</p>\n";
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 2:</p>\n";
    display(0);    
}
/* nAbhyastasyAci piti sArvadhAtuke (7.3.87) */
elseif ( ($sarvadhatuka===1 || $ardhadhatuka===1) && ends(array($fo),array("Riji!r","viji!r","vizx!"),4) && pr2(array("i","u","f","x"),$hlplus,$ajAdi_pit_sArvadhAtuka_pratyayas,array("e","o","ar","al"),$hlplus,$ajAdi_pit_sArvadhAtuka_pratyayas,$text)!==$text)
{
    echo "<p class = sa >By nAbhyastasyAci piti sArvadhAtuke (".link_sutra("7.3.87").") :</p>\n"; 
    echo "<p class = sa >नाभ्यस्तस्याचि पिति सार्वधातुके (७.३.८७) :</p>\n";
    display(0);    
}
/* pugantalaghUpadhasya ca (7.3.86) */
elseif ( $atolopa!==1 && ($sarvadhatuka===1 || $ardhadhatuka===1) && arr($text,'/[iufx]['.pc('hl').'][+]/') && pr2(array("i","u","f","x"),$hlplus,$pit_sArvadhAtuka_pratyayas,array("e","o","ar","al"),$hlplus,$pit_sArvadhAtuka_pratyayas,$text)!==$text && !arr($text,"/[+][i][y][+][t]$/")  && $didhI!==1 && $kGiti===0 && $ksa!==1 && $vijait!==1)
{
    $text=pr2(array("i","u","f","x"),$hlplus,$pit_sArvadhAtuka_pratyayas,array("e","o","ar","al"),$hlplus,$pit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >pugantalaghUpadhasya ca (".link_sutra("7.3.86").") :</p>\n"; 
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 8:</p>\n";
    display(0);    
}
/* pugantalaghUpadhasya ca (7.3.86) */
elseif ( $atolopa!==1 && $ardhadhatuka===1 && arr($text,'/[iufx]['.pc('hl').'][+]/') && pr2(array("i","u","f","x"),$hlplus,$ArdhadhAtuka_pratyayas,array("e","o","ar","al"),$hlplus,$ArdhadhAtuka_pratyayas,$text)!==$text && !arr($text,"/[+][i][y][+][t]$/")  && $didhI!==1 && $kGiti!==1 && $sIyuT!==1 && $ksa!==1 && $vijait!==1)
{
    $text=pr2(array("i","u","f","x"),$hlplus,$ArdhadhAtuka_pratyayas,array("e","o","ar","al"),$hlplus,$ArdhadhAtuka_pratyayas,$text);
    echo "<p class = sa >pugantalaghUpadhasya ca (7.3.86) :</p>\n"; 
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 3:</p>\n";
    display(0);    
}
/* RdRzo'Gi guNaH (7.4.16) */
elseif ( sub(array("f","dfS"),array("+"),array("a"),0) && $lakAra==="luN" && in_array("N",$itpratyaya) )
{
    $text=two(array("f","dfS"),array("+a"),array("ar","darS"),array("+a"),0);
    echo "<p class = sa >By RdRzo'Gi guNaH (".link_sutra("7.4.16").") :</p>\n"; 
    echo "<p class = sa >ऋदृशोऽङि (७.४.१६) :</p>\n";
    display(0);
}
/* kGiti ca (7.3.86) */
elseif ( $atolopa!==1 && $ardhadhatuka===1 && arr($text,'/[iufxIUFX]['.pc('hl').'][+][s][I][y][+]/') && !arr($text,"/[+][i][y][+][t]$/")  && $didhI!==1 && $sIyuT===1 && (in_array("N",$it)||in_array("k",$it)) )
{
    echo "<p class = sa >By kGiti ca (".link_sutra("1.1.5").") :</p>\n"; 
    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >क्ङिति च (१.१.५) 7:</p>\n";
    echo "<p class = hn >अनेन सूत्रेण गुणनिषेधः विधीयते ।</p>\n";
    display(0);    
    $kGiti=1;
} 
/* pugantalaghUpadhasya ca (7.3.86) */
// for pratyayas deleted by halGyAb..
elseif ( $atolopa!==1 && ($sarvadhatuka===1 || $ardhadhatuka===1) && arr($text,'/[iufx]['.pc('hl').'][+]$/') && pr2(array("i","u","f","x"),$hlplus,blank(0),array("e","o","ar","al"),$hlplus,blank(0),$text)!==$text  && $didhI!==1 && $ksa!==1 && $vijait!==1)
{
    $text=pr2(array("i","u","f","x"),$hlplus,blank(0),array("e","o","ar","al"),$hlplus,blank(0),$text);
    echo "<p class = sa >pugantalaghUpadhasya ca (".link_sutra("7.3.86").") :</p>\n"; 
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 4:</p>\n";
    display(0);    
}
/* pugantalaghUpadhasya ca (7.3.86) */
elseif ( (pr2(array("vid"),array("+"),$pit_sArvadhAtuka_pratyayas,array("ved"),array("+"),$pit_sArvadhAtuka_pratyayas,$text)!== $text || pr2(array("vid"),array("+"),blank(0),array("ved"),array("+"),blank(0),$text)!== $text) && ends(array($fo),array("vida!"),4)  && $didhI!==1 && $ksa!==1 && $vijait!==1)
{
    $text=pr2(array("vid"),array("+"),$pit_sArvadhAtuka_pratyayas,array("ved"),array("+"),$pit_sArvadhAtuka_pratyayas,$text);
    $text=pr2(array("vid"),array("+"),blank(0),array("ved"),array("+"),blank(0),$text);
    echo "<p class = sa >pugantalaghUpadhasya ca (".link_sutra("7.3.86").") :</p>\n"; 
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 5:</p>\n";
    display(0);    
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
elseif ( pr2(array("nu+"),$pit_sArvadhAtuka_pratyayas,blank(0),array("no+"),$pit_sArvadhAtuka_pratyayas,blank(0),$text) !== $text && $didhI!==1 && $bhUsuvo!==1  && $kGiti!==1)
{
    $text=pr2(array("nu+"),$pit_sArvadhAtuka_pratyayas,blank(0),array("no+"),$pit_sArvadhAtuka_pratyayas,blank(0),$text);    
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") 2:</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* akaH savarNe dIrghaH (6.1.101) */
if ( arr($text,'/^[iIuU]/') && sub(array("i+i","I+I","u+u","U+U"),blank(0),blank(0),0) && $abhyAsa===1)
{
    $text=one(array("i+i","I+I","u+u","U+U"),array("I","I","U","U"),0);
	echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
    echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
    display(0);
}
/* sidhyaterapAralaukike (6.1.49) */
if (sub(array("seD"),array("+"),array("i+"),0) && $_GET['cond53']==='2')
{
    $text=three(array("seD"),array("+"),array("i+"),array("sAD"),array("+"),array("i+"),0);    
    echo "<p class = sa >By sidhyaterapAralaukike (".link_sutra("6.1.49").") :</p>\n"; 
    echo "<p class = sa >सिध्यतेरपारलौकिके (६.१.४९) :</p>\n";
    display(0);
}
/* cisphurorNau (6.1.53) */
if (sub(array("ce","sPor"),array("+"),array("i+"),0) && !sub(array("cA","sPAr"),array("+"),array("i+"),0) && ends(array($fo),array("ciY","sPura!"),4) && in_array($so,$tiG) )
{
    $text=three(array("ce","sPor"),array("+"),array("i+"),array("cA","sPAr"),array("+"),array("i+"),1);
    echo "<p class = sa >By cisphurorNau (".link_sutra("6.1.53").") :</p>\n"; 
    echo "<p class = sa >चिस्फुरोर्णौ (६.१.५३) :</p>\n";
    display(0);    
}
// caG for ajAdi
/* bhrAjabhAsabhASadIpajIvamIlapIDamnyatarasyAm (7.4.3) */
if (sub(array("BrAj","BAs","BAS","dIp","jIv","mIl","pIq"),array("+"),array("i+a"),0) && in_array($so,$tiG) && $luGset===5 )
{
	$text = three(array("BrAj","BAs","BAS","dIp","jIv","mIl","pIq"),array("+"),array("i+a"),array("Braj","Bas","BaS","dip","jiv","mil","piq"),array("+"),array("i+a"),1);
    echo "<p class = sa >By bhrAjabhAsabhASadIpajIvamIlapIDamnyatarasyAm (".link_sutra("7.4.3").") :</p>\n"; 
    echo "<p class = sa >भ्राजभासभाषदीपजीवमीलपीडामन्यतरस्याम्‌ (७.४.३) :</p>\n";
    display(0);
}
/* nAglopizAsvRditAm (7.4.2) */
elseif (sub(array("A","I","U","F","X","e","E","o","O"),$hl,array("+i+a"),0) && in_array($so,$tiG) && (in_array($fo,$curAdi_adanta) || in_array($fo,array("SAsu!")) || in_array($fo,$Rditverbs)) && $luGset===5)
{
    echo "<p class = sa >By nAglopizAsvRditAm (".link_sutra("7.4.2").") :</p>\n"; 
    echo "<p class = sa >नाग्लोपिशास्वृदिताम्‌ (७.४.२) :</p>\n";
    display(0);
}
/* Nau caGi upadhAyA hrasvaH (7.4.1) */
elseif (sub(array("A","I","U","F","X","e","E","o","O"),$hl,array("i+a"),0) && in_array($so,$tiG)  && $luGset===5 && !arr($text,'/^['.pc('ac').']/'))
{
	$text = three(array("A","I","U","F","X","e","E","o","O"),$hl,array("i+a"),array("a","i","u","f","x","i","i","u","u"),$hl,array("i+a"),0);
    echo "<p class = sa >By Nau caGi upadhAyA hrasvaH (".link_sutra("7.4.1").") :</p>\n"; 
    echo "<p class = sa >णौ चङि उपधाया ह्रस्वः (७.४.१) :</p>\n";
    display(0);
}
/* urRt (7.4.7) */
if (sub(array("ar"),$hlplus,array("i+a"),0) && in_array($so,$tiG)  && $luGset===5 && !arr($text,'/^['.pc('ac').']/'))
{
    $text=three(array("ar"),$hlplus,array("i+a"),array("f"),$hlplus,array("i+a"),1);
    echo "<p class = sa >By urRt (".link_sutra("7.4.7").") :</p>\n"; 
    echo "<p class = sa >उरृत्‌ (७.४.७) :</p>\n";
    display(0);    
}
if ($caG===1 && arr($text,'/^['.pc('ac').']/') )
{
	$text = one(array("+i+"),array("i+"),0);
	caG_ajAdi();
}
// trial san function
if ($caG===1 && $sanAdi==="Ric")
{
	//san();
}
/* removing + from before Ni pratyayas */
if(sub($hl,array("+i+"),blank(0),0)) {$text = one(array("+i+"),array("i+"),0);}
/* guNo'rtisaMyogAdyoH (7.4.29) */
if ( (sub($hl,$hl,array("f+y"),0) || $fo==="f")&& (in_array($sanAdi,array("yak")) || $lakAra==="ASIrliN"))
{
	$text=one(array("f+y"),array("ar+y"),0);
    echo "<p class = sa >By guNo'rtisaMyogAdyoH (".link_sutra("7.4.29").") :</p>\n"; 
    echo "<p class = sa >गुणोऽर्तिसंयोगाद्योः (७.४.२९) :</p>\n";
    display(0);    
}
/* riGzayagliGkSu (7.4.28) */
elseif ( sub(array("f"),array("+y"),blank(0),0) && (in_array($sanAdi,array("yak")) || $lakAra==="ASIrliN"))
{
    $text=two(array("f"),array("+y"),array("ri"),array("+y"),0);
    echo "<p class = sa >By riGzayagliGkSu (".link_sutra("7.4.28").") :</p>\n"; 
    echo "<p class = sa >रिङ्शयग्लिङ्क्षु (७.४.२८) :</p>\n";
    display(0);    
}
/* akRtsArvadhAtukayordIrghaH (7.4.25) */
elseif ( sub($ac,array("+"),array("y"),0) && !sub($ac,array("+"),array("y+a"),0) && in_array($so,$tiG) && $sarvadhatuka!==1 && $kRt!==1 && (in_array("N",$itpratyaya) || in_array("k",$itpratyaya)) && $lakAra!=="liw" && !arr($text,'/^[y][a][+][y]/'))
{
    $text=three($ac,array("+"),array("y"),dirgha($ac),array("+"),array("y"),0);
    echo "<p class = sa >akRtsArvadhAtukayordIrghaH (".link_sutra("7.4.25").") :</p>\n"; 
    echo "<p class = sa >अकृत्सार्वधातुकयोर्दीर्घः (७.४.२५) :</p>\n";
    display(0);    
}

/* special treatment for qukfY (sahajabodha p. 301) */
/*if (ends(array($fo),array("qukfY","kfY"),4))
{
    if (ends($text,$apit_sArvadhAtuka_pratyayas,0))
    {
        $text=two(array("kf+u+"),$apit_sArvadhAtuka_pratyayas,array("kur+u+"),$apit_sArvadhAtuka_pratyayas,0);
    }
    if (ends($text,$pit_sArvadhAtuka_pratyayas,0))
    {
        $text=two(array("kf+u+"),$pit_sArvadhAtuka_pratyayas,array("kar+u+"),$pit_sArvadhAtuka_pratyayas,0);
    }
    echo "<p class = sa >special treatment for qukfY (sahajabodha p. 301) :</p>\n"; 
    echo "<p class = sa >डुकृञ्‌ के लिये विशिष्ट प्रक्रिया (सहजबोध पृ.३०१) :</p>\n";
    display(0);        
        $karoti=1; */
    /* nityaM karoteH (6.4.108) */
/*    if (sub(array("kur+u"),array("+"),array("v","m"),0))
    {
        $text=three(array("kur+u"),array("+"),array("v","m"),array("kur"),array("+"),array("v","m"),0);
        echo "<p class = sa >By nityaM karoteH (".link_sutra("6.4.108").") :</p>\n"; 
        echo "<p class = sa >डुकृञ्‌ के लिये विशिष्ट प्रक्रिया (६.४.१०८) :</p>\n";
        display(0);
    }*/
    /* ye ca (6.4.109) */
/*    if (sub(array("kur+u"),array("+"),array("y"),0))
    {
        $text=three(array("kur+u"),array("+"),array("y"),array("kur"),array("+"),array("y"),0);
        echo "<p class = sa >By ye ca (".link_sutra("6.4.109").") :</p>\n"; 
        echo "<p class = sa >ये च (६.४.१०९) :</p>\n";
        display(0);                
    }
}*/
/* kGiti ca (1.1.5) */
if ( sub(array("i","I","u","U","f","F","x","X"),array("+"),array("sI"),0) && ($sarvadhatuka===1 || $ardhadhatuka===1) &&  (in_array("N",$it)||in_array("k",$it)) && $didhI!==1)
{
    echo "<p class = sa >By kGiti ca (".link_sutra("1.1.5").") 2:</p>\n"; 
    echo "<p class = hn >This prevents guNa. </p>\n"; 
    echo "<p class = sa >क्ङिति च (१.१.५) 8:</p>\n";
    echo "<p class = hn >अनेन सूत्रेण गुणनिषेधः विधीयते ।</p>\n";
    display(0);
    $kGiti=1;
}
/* bhUsuvostiGi (7.3.88) */
if ( pr2(array("BU","sU"),array("+"),$sArvadhAtuka_tiG_pratyayas,array("Bu","su"),array("+"),$sArvadhAtuka_tiG_pratyayas,$text)!==$text && ($sarvadhatuka===1 || $ardhadhatuka===1) && ends(array($fo),array("BU","zUN"),4) )
{
    echo "<p class = sa >By bhUsuvostiGi (".link_sutra("7.3.88").") :</p>\n"; 
    echo "<p class = sa >भूसुवोस्तिङि (७.३.८८) :</p>\n";
    display(0);    
	$bhUsuvo=1;
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
if ( sub(array("i","I","u","U","f","F","x","X"),array("+"),array("u+","I+","a+",),0) && ($sarvadhatuka===1 || $ardhadhatuka===1) && !($ad===1 && sub(array("i","I","u","U","f","F","x","X"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,0) )  && $didhI!==1 && !($lakAra==="liw" && in_array($fo,array("uN"))) )
{
    $text=three(array("i","I","u","U","f","F","x","X",),array("+"),array("u+","I+","a+"),array("e","e","o","o","ar","ar","al","al",),array("+"),array("u+","I+","a+"),0);
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") 4:</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
// for curAdis.
if ( sub($hl,array("i+"),array("a+"),0) && $vik===array("Sap")  && $didhI!==1  && $bhUsuvo!==1 && $kGiti!==1)
{
    $text=three($hl,array("i+"),array("a+"),$hl,array("e+"),array("a+"),0);    
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") :</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
if ( pr2($hl,array("+u+"),$pit_sArvadhAtuka_pratyayas,$hl,array("+o+"),$pit_sArvadhAtuka_pratyayas,$text) !== $text  && $didhI!==1 && $bhUsuvo!==1  && $kGiti!==1)
{
    $text=pr2($hl,array("+u+"),$pit_sArvadhAtuka_pratyayas,$hl,array("+o+"),$pit_sArvadhAtuka_pratyayas,$text);    
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") 3:</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
if ( pr2(array("i","I","u","U","f","F","x","X",),array("+I+"),$halAdi_pit_sArvadhAtuka_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+I"),$halAdi_pit_sArvadhAtuka_pratyayas,$text)!==$text && ($sarvadhatuka===1 || $ardhadhatuka===1)   && $didhI!==1 && $bhUsuvo!==1)
{
    $text=pr2(array("i","I","u","U","f","F","x","X",),array("+I+"),$halAdi_pit_sArvadhAtuka_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+I"),$halAdi_pit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") 6:</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
if ( pr2(array("i","I","u","U","f","F","x","X",),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,$text)!==$text && ($sarvadhatuka===1 || $ardhadhatuka===1)   && $didhI!==1 && $bhUsuvo!==1 && $kGiti!==1)
{
    $text=pr2(array("i","I","u","U","f","F","x","X",),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+"),$halAdi_pit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") 1:</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
if ( pr2(array("i","I","u","U","f","F","x","X",),array("+"),$ajAdi_pit_sArvadhAtuka_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+"),$ajAdi_pit_sArvadhAtuka_pratyayas,$text)!==$text && $sarvadhatuka===1  && $didhI!==1 && $bhUsuvo!==1 && $kGiti!==1)
{
    $text=pr2(array("i","I","u","U","f","F","x","X",),array("+"),$ajAdi_pit_sArvadhAtuka_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+"),$ajAdi_pit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") 5:</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* vibhASA lIyateH (6.1.50) */
if (sub(array("le","lE"),array("+"),blank(0),0) && ((ends(array($fo),array("lI"),4) && $verbset==="kryAdi") || (ends(array($fo),array("lIN"),4) && $verbset==="none") ))
{
    $text=three(array("le","lE"),array("+"),blank(0),array("lA","lA"),array("+"),blank(0),1);    
    echo "<p class = sa >By vibhASA lIyateH (".link_sutra("6.1.50").") 2:</p>\n"; 
    echo "<p class = sa >विभाषा लीयतेः (६.१.५०) :</p>\n";
    display(0);
}
/* hali ca (8.2.77) */
if ( sub(array("i","u","f"),array("r+","v+"),$halAdi_apit_sArvadhAtuka_pratyayas,0) && in_array($so,$tiG) && $karoti!==1 )
{
    $text=three(array("i","u","f"),array("r+","v+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("I","U","F"),array("r+","v+"),$halAdi_apit_sArvadhAtuka_pratyayas,0);
    echo "<p class = sa >By hali ca (".link_sutra("8.2.77").") 2:</p>\n"; 
    echo "<p class = sa >हलि च (८.२.७७) :</p>\n";
    display(0);    
}
/* otaH zyani (7.3.71) */
if ( sub(array("o"),array("+ya+"),$sArvadhAtuka_pratyayas,0) && in_array($so,$tiG) )
{
    $text=three(array("o"),array("+ya+"),$sArvadhAtuka_pratyayas,array(""),array("ya+"),$sArvadhAtuka_pratyayas,0);
    echo "<p class = sa >By otaH zyani (".link_sutra("7.3.71").") :</p>\n"; 
    echo "<p class = sa >ओतः श्यनि (७.३.७१) :</p>\n";
    display(0);    
}
/* dadhastathozca (8.2.38) */
if ( sub(array("daDA"),array("+"),array("tas","Tas","Ta","te","se","Dve","tAt","tAm","tam","ta","sva","Dvam","TAs",),0) && in_array($so,$tiG) )
{
    $text=three(array("daDA"),array("+"),array("tas","Tas","Ta","te","se","Dve","tAt","tAm","tam","ta","sva","Dvam","TAs",),array("DaD"),array("+"),array("tas","Tas","Ta","te","se","Dve","tAt","tAm","tam","ta","sva","Dvam","TAs",),0);
    echo "<p class = sa >By dadhastathozca (".link_sutra("8.2.38").") :</p>\n"; 
    echo "<p class = sa >दधस्तथोश्च (८.२.३८) :</p>\n";
    display(0);    
}
/* bhiyo'nyatarasyAm (6.4.115) */
if ( sub(array("biBI"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,0) && in_array($so,$tiG) )
{
    $text=three(array("biBI"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("biBi"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,1);
    echo "<p class = sa >By bhiyo'nyatarasyAm (".link_sutra("6.4.115").") :</p>\n"; 
    echo "<p class = sa >भियोऽन्यतस्याम्‌ (६.४.११५) :</p>\n";
    display(0);    
}
/* znA'bhyastayorAtaH (6.4.112) */
if ( sub(array("dadA","daDA"),array("+"),$apit_sArvadhAtuka_pratyayas,0) && (in_array("N",$it)||in_array("k",$it)))
{
    $text=three(array("dadA","daDA"),array("+"),$apit_sArvadhAtuka_pratyayas,array("dad","daD"),array("+"),$apit_sArvadhAtuka_pratyayas,0);
    echo "<p class = sa >By znA'bhyastayorAtaH (".link_sutra("6.4.112").") :</p>\n"; 
    echo "<p class = sa >श्नाऽभ्यस्तयोरातः (६.४.११२) :</p>\n";
    display(0);    
}
/* znA'bhyastayorAtaH (6.4.112) */
if ( $abhyasta===1 && sub(array("A"),array("+"),$apit_sArvadhAtuka_pratyayas,0) && (in_array("N",$it)||in_array("k",$it)))
{
    $text=three(array("A"),array("+"),$apit_sArvadhAtuka_pratyayas,array(""),array("+"),$apit_sArvadhAtuka_pratyayas,0);
    echo "<p class = sa >By znA'bhyastayorAtaH (".link_sutra("6.4.112").") :</p>\n"; 
    echo "<p class = sa >श्नाऽभ्यस्तयोरातः (६.४.११२) :</p>\n";
    display(0);    
}
/* gamahanajanakhanaghasAM lopaH kGiti (6.4.98) */
if ( sub(array("gam","Gan","jan","Gas","Kan"),array("+"),blank(0),0) && in_array($so,$tiG) && ends(array($fo),array("gamx!","hana!","jana!","Gasa!","Kanu!","janI!"),4) && ends($itpratyaya,array("k","N"),2) )
{
    $text=two(array("gam","Gan","jan","Gas","Kan"),array("+"),array("gm","Gn","jn","Gs","Kn"),array("+"),0);
    echo "<p class = sa >By gamahanajanaghasAM lopaH kGiti (".link_sutra("6.4.98").") :</p>\n"; 
    echo "<p class = sa >गमहनजनघसां लोपः क्ङिति (६.४.९८) :</p>\n";
    
    display(0);
}
/* aDgArgyagAlavayoH (7.3.99) */
if ( pr2(array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("t","s"),array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("at","as"),$text)!==$text && in_array($so,$tiG) && ends(array($fo),array("rudi!r","Yizvapa!","zvapa!","Svasa!","prARa!","ana!","jakza!"),4))
{
    $text1=pr2(array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("t","s"),array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("at","as"),$text);
    $text = array_merge($text,$text1);
    echo "<p class = sa >By aDgArgyagAlavayoH (".link_sutra("7.3.99").") :</p>\n"; 
    echo "<p class = sa >अड्गार्ग्यगालवयोः (७.३.९९) :</p>\n";
    display(0);
}
/* rudazca paJcabhyaH (7.3.98) */
if ( pr2(array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("t","s"),array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("It","Is"),$text)!==$text && in_array($so,$tiG) && ends(array($fo),array("rudi!r","Yizvapa!","zvapa!","Svasa!","prARa!","ana!","jakza!"),4))
{
    $text=pr2(array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("t","s"),array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("It","Is"),$text);
    echo "<p class = sa >By rudazca paJcabhyaH (".link_sutra("7.3.98").") :</p>\n"; 
    echo "<p class = sa >रुदादिभ्यः पञ्चभ्यः (७.३.९८) :</p>\n";
    display(0);
}
/* rudAdibhyaH sArvadhAtuke (7.2.76) */
if ( sub(array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),prat('vl'),0) && in_array($so,$tiG) && ends(array($fo),array("rudi!r","Yizvapa!","zvapa!","Svasa!","prARa!","ana!","jakza!"),4))
{
    $text=three(array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),prat('vl'),array("rod","svap","Svas","prAR","an","jakz","rud"),array("+i"),prat('vl'),0);
    echo "<p class = sa >By rudAdibhyaH sArvadhAtuke (".link_sutra("7.2.76").") :</p>\n"; 
    echo "<p class = sa >रुदादिभ्यः सार्वधातुके (७.२.७६) :</p>\n";
    display(0);
}
/* hujhalbhyo herdhiH (6.4.101) */
if ( (sub(array("juhu"),array("+"),array("hi"),0) || sub(prat('Jl'),array("+"),array("hi"),0) ) && $so==="sip" && $lakAra==="low" )
{
    $text=three(array("juhu"),array("+"),array("hi"),array("juhu"),array("+"),array("Di"),0);
    $text=three(prat('Jl'),array("+"),array("hi"),prat('Jl'),array("+"),array("Di"),0);
    echo "<p class = sa >By hujhalbhyo herdhiH (".link_sutra("6.4.101").") :</p>\n"; 
    echo "<p class = sa >हुझल्भ्यो हेर्धिः (६.४.१०१) :</p>\n";
    display(0);    
}
/* znasorallopaH (6.4.111) */
// znam pending.
if ( pr2(array("as"),array("+"),$apit_sArvadhAtuka_pratyayas,array("s"),array("+"),$apit_sArvadhAtuka_pratyayas,$text)!==$text && (ends(array($fo),array("asa!"),4) && $verbset==="adAdi" ) && (in_array("N",$it)||in_array("k",$it)))
{
    $text=pr2(array("as"),array("+"),$apit_sArvadhAtuka_pratyayas,array("s"),array("+"),$apit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >By znasorallopaH (".link_sutra("6.4.111").") :</p>\n"; 
    echo "<p class = sa >श्नसोरल्लोपः (६.४.१११) :</p>\n";
    display(0);    
}
/* tAsastyorlopaH (7.4.50) */
// tAsi pending.
if ( sub(array("as"),array("+"),array("s"),0) && (ends(array($fo),array("asa!"),4) && $verbset==="adAdi" ))
{
    $text=three(array("as"),array("+"),array("s"),array("a"),array("+"),array("s"),0);
    echo "<p class = sa >By tAsastyorlopaH (".link_sutra("7.4.50").") :</p>\n"; 
    echo "<p class = sa >तासस्त्योर्लोपः (७.४.५०) :</p>\n";
    display(0);    
}
/* hanterjaH (6.4.36) */
if ( sub(array("han"),array("+"),array("hi"),0) && in_array($so,$tiG) )
{
    $text = three(array("han"),array("+"),array("hi"),array("ja"),array("+"),array("hi"),0);
    echo "<p class = sa >By hanterjaH (".link_sutra("6.4.36").") 1:</p>\n";
    echo "<p class = sa >हन्तेर्जः (६.४.३६) :</p>\n";  
    display(0);    
    $hanterjaH=1;
}
/* gamahanajanakhanaghasAM kGityanaGi (6.4.98) */
// anaGi is not clear. Pending.
if ( pr2(array("han","gam","jan","Kan","Gas"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,array("hn","gm","jn","Kn","Gs"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,$text)!==$text && in_array($so,$tiG) )
{
    $text = pr2(array("han","gam","jan","Kan","Gas"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,array("hn","gm","jn","Kn","Gs"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >By gamahanajanakhanaghasAM kGityanaGi (".link_sutra("6.4.98").") :</p>\n";
    echo "<p class = sa >गमहनजनखनघसां क्ङित्यनङि (६.४.९८) :</p>\n";  
    display(0);
}
/* vA gamaH (1.2.13) */
if ($fo==="gamx!" && in_array($so,$taG) && ($sic===1||$sIyuT===1) )
{
    echo "<p class = pa >By vA gamaH (".link_sutra("1.2.13").") :</p>\n"; 
    echo "<p class = pa >वा गमः (१.२.१३) :</p>\n";
    display(0);
	$vAgamaH=1;
} else {$vAgamaH=0;}
/* vibhASopayamane (1.2.16) */
if ($fo==="yama!" && in_array($so,$taG) && ($sic===1||$sIyuT===1) && $_GET['cond56']==='2')
{
    echo "<p class = pa >By vibhASopayamane (".link_sutra("1.2.16").") :</p>\n"; 
    echo "<p class = pa >विभाषोपयमने (१.२.१६) :</p>\n";
    display(0);
	$vAgamaH=1;
}
/* ghvasoreddhAvabhyAsalopazca (6.4.119) */
if ( sub(array("dadA","daDA"),array("+"),array("hi"),0) && in_array($so,$tiG) && ends(array($fo),array("qudAY","quDAY","dAY","DAY"),4) ) 
{
    $text=three(array("dadA","daDA"),array("+"),array("hi"),array("de","De",),array("+"),array("hi"),0);
    echo "<p class = sa >By ghvasoreddhAvabhyAsalopazca (".link_sutra("6.4.119").") :</p>\n"; 
    echo "<p class = sa >घ्वसोरेद्धावभ्यासलोपश्च (६.४.११९) :</p>\n";
    display(0);    
}
/* ghvasoreddhAvabhyAsalopazca (6.4.119) */
if ( sub(array("as"),array("+"),array("Di"),0) && in_array($so,$tiG) && ends(array($fo),array("asa!",),4) && $verbset==="adAdi") 
{
    $text=three(array("as"),array("+"),array("Di"),array("e"),array("+"),array("Di"),0);
    echo "<p class = sa >By ghvasoreddhAvabhyAsalopazca (".link_sutra("6.4.119").") :</p>\n"; 
    echo "<p class = sa >घ्वसोरेद्धावभ्यासलोपश्च (६.४.११९) :</p>\n";
    display(0);    
}
/* dhi ca (8.2.25) */
if ( $lakAra!=="" && sub(array("s"),array("+"),array("D"),0) && in_array($so,$tiG))
{
    $text = three(array("s"),array("+"),array("D"),array(""),array("+"),array("D"),0);
    echo "<p class = sa >By dhi ca (".link_sutra("8.2.25").") :</p>\n"; 
    echo "<p class = sa >धि च (८.२.२५) :</p>\n";
    display(0);     
}
/* erliGi (6.4.67) */
if ( (ends(array($fo),array("mA","zWA","gE","pA","o!hAk","zo"),4) || $ghu===1) && in_array($so,$tiG) && in_array($lakAra,array("ASIrliN")) && sub(array("A"),array("+"),array("y"),0) && $verbset!=="adAdi" ) // not applicable to pA of adAdi. Not applicable to sIyuT.
{
$text = one(array("A+y"),array("e+y"),0);
echo "<p class = sa >By erliGi (".link_sutra("6.4.67").") :</p>\n";
echo "<p class = sa >एर्लिङि (६.४.६७) :</p>\n";
display(0);
}
/* vA'nyasya saMyogAdeH (6.4.68) */
if ( (arr($text,'/['.pc('hl').']['.pc('hl').'][A][+][y]/')) && in_array($so,$tiG) && in_array($lakAra,array("ASIrliN")) ) // Not applicable to sIyuT.
{
$text = one(array("A+y"),array("e+y"),1);
echo "<p class = sa >By vA'nyasya saMyogAdeH (".link_sutra("6.4.68").") :</p>\n";
echo "<p class = sa >वाऽन्यस्य संयोगादेः (६.४.६८) :</p>\n";
display(0);
}
/* skoH saMyogAdyorante ca (8.2.29) */
if ( (arr($text,'/[s]['.flat($hl).']$/') || sub(array("vraSc","vrASc"),array("+"),blank(0),0) ) && in_array($so,$tiG) && in_array($lakAra,array("ASIrliN"))) // for ASIrliN
{
foreach ($hl as $value) { $ska[] = "s".$value; }
$text = last($ska,$hl,0);
$text = one(array("+yAs+","vraSc","vrASc"),array("+yA+","vrac","vrAc"),0);
echo "<p class = sa >By skoH saMyogAdyorante ca (".link_sutra("8.2.29").") :</p>\n";
echo "<p class = sa >स्कोः संयोगाद्योरन्ते च (८.२.२९) :</p>\n";
display(0);
}
/* grahijyAvayivyadhivaSTivicativRzcatipRcCatibhRjjatInAM Giti ca (6.1.16) */
if (ends(array($fo),array("jyA"),4) && in_array("N",$it) && $so!=="mahiN")
{
    $text=two(array("jyA"),array("+"),array("jiA"),array("+"),0);
    echo "<p class = sa >grahijyAvayivyadhivaSTivicativRzcatipRcCatibhRjjatInAM Giti ca (".link_sutra("6.1.16").") :</p>\n"; 
    echo "<p class = sa >ग्रहिज्यावयिव्यधिवष्टिविचतिवृश्चतिपृच्छतिभृज्जतीनां ङिति (६.१.१६) :</p>\n";
    display(0);    
/*    $text=two(array("jiA"),array("+"),array("ji"),array("+"),0);
    echo "<p class = sa >samprasAraNAcca (".link_sutra("6.1.104").") :</p>\n"; 
    echo "<p class = sa >सम्प्रसारणाच्च (६.१.१०४) :</p>\n";
    display(0);    
 */
    $text = samprasarana(array("jyA"),0);
    $text=two(array("ji"),array("+"),array("jI"),array("+"),0);
    echo "<p class = sa >halaH (".link_sutra("6.4.2").") :</p>\n"; 
    echo "<p class = sa >हलः (६.४.२) :</p>\n";
    display(0);    
    $text=two(array("jI"),array("+"),array("ji"),array("+"),0);
    echo "<p class = sa >pvAdInAM hrasvaH (".link_sutra("7.3.80").") :</p>\n"; 
    echo "<p class = sa >प्वादीनां ह्रस्वः (७.३.८०) :</p>\n";
    display(0);    
}
/* na mAGyoge (6.4.74) */
if ( in_array($lakAra,array("luN","laN","lfN",)) && $_GET['cond50']==='1')
{
    echo "<p class = sa >By na mAGyoge (".link_sutra("6.4.74").") :</p>\n"; 
    echo "<p class = hn >This prevents application of aDAgama or ADagama. </p>\n"; 
    echo "<p class = sa >न माङ्‍योगे (६.४.७४) :</p>\n";
    echo "<p class = hn >अनेन सूत्रेण अडागमाडागमौ निषिध्येते ।</p>\n";
    display(0);    
}
/* ADajAdInAm (6.4.72) */
// udAttatva pending.
elseif ( in_array($lakAra,array("luN","laN","lfN",)) && arr($text,'/^['.pc('ac').']/') )
{
    $text=Adyanta($text,"A",1);
    echo "<p class = sa >By ADajAdInAm (".link_sutra("6.4.72").") :</p>\n"; 
    echo "<p class = sa >आडजादीनाम्‌ (६.४.७२) :</p>\n";
    display(0);    
    $Agama=array("Aw");
}
/* luGlaGlRGkSvaDudAttaH (6.4.71) */
// udAttatva pending.
elseif ( in_array($lakAra,array("luN","laN","lfN",)) )
{
    $text=Adyanta($text,"a",1);
    echo "<p class = sa >By luGlaGlRGkSvaDudAttaH (".link_sutra("6.4.71").") :</p>\n"; 
    echo "<p class = sa >लुङ्लङ्लृङ्क्ष्वडुदात्तः (६.४.७१) :</p>\n";
    display(0);    
}
/* iNvadika iti vaktavyam (vA) */
if ( ends(array($fo),array("ik"),4))
{
    echo "<p class = sa >By iNvadika iti vaktavyam (vA) :</p>\n";
    echo "<p class = sa >इण्वदिक इति वक्तव्यम्‌ (वा) :</p>\n";
    display(0);
}
/* uzca (1.2.12) */
if (ends(array($verb_without_anubandha),array("f","F"),1) && in_array($so,$taG) && ($sic===1||$sIyuT===1) )
{
    echo "<p class = pa >By uzca (".link_sutra("1.2.12").") :</p>\n"; 
    echo "<p class = pa >उश्च (१.२.१२) :</p>\n";
    display(0);
	$kGiti=1;
	/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
	// patch because it applies only before aniT in this case. for iDAgama it is not applicable.
	if ( sub(array("f","F"),array("+"),array("isI"),0) && ($sarvadhatuka===1 || $ardhadhatuka===1) && !($ad===1 && sub(array("i","I","u","U","f","F","x","X"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,0) )  && $didhI!==1 && $bhUsuvo!==1)
	{
		$text=three(array("f","F"),array("+"),array("isI"),array("ar","ar"),array("+"),array("isI"),0);
		echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") :</p>\n"; 
		echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
		display(0);    
	}
}
/* tAsi ca klRpaH (7.2.60) */
// tAsi done here. sakArAdi elsewhere.
if (sub(array("kalp"),array("+tA"),blank(0),0) && $id_dhAtu==="sew" && $id_pratyaya==="sew" && $tAs===1  && !in_array("iw",$Agama)) // for seT dhAtus
{
    $text=one(array("+"),array("+i"),1);
    echo "<p class = sa >By tAsi ca klRpaH (".link_sutra("7.2.60").") :</p>\n"; 
    echo "<p class = sa >तासि च क्लृपः (७.२.६०) :</p>\n";
    display(0);    
	$Agama=array_merge($Agama,array("iw"));
}
/* tISasahalubharuSariSaH (7.2.48) */
if (sub(array("ez","sah","loB","roz","rez"),array("+tA"),blank(0),0) && $tAs===1  && !in_array("iw",$Agama) && (in_array($fo,array("saha!","luBa!","ruza!","riza!"))|| (in_array($fo,array("iza!",)) && $verbset==="divAdi") )) 
{
    $text=one(array("+"),array("+i"),1);
    echo "<p class = sa >By  tISasahalubharuSariSaH (".link_sutra("7.2.48").") :</p>\n"; 
    echo "<p class = sa >तीषसहलुभरुषरिषः (७.२.४८) :</p>\n";
    display(0);    
	$Agama=array_merge($Agama,array("iw"));
}
/* sthAdhvoricca (??) */
if ( ends(array($fo),array("do","deN","qudAY","dAR","Dew","quDAY","zWA"),4) && sub(array("A+"),array("s"),blank(0),0) && $lakAra!=="" && $sic!==0 )
{
    $text=two(array("A+"),array("s"),array("i+"),array("s"),0);
    echo "<p class = sa >By sthAdhvoricca ( ) :</p>\n"; 
    echo "<p class = sa >स्थाध्वोरिच्च ( ) :</p>\n";
    display(0);    
}
/* ghumAsthAgApAjahAtisAM hali (6.4.66) */
if ( ends(array($fo),array("do","deN","qudAY","dAR","Dew","quDAY","mA","zWA","iN","pA","hA","sA","gAN"),4) && ends($it,array("N","k"),2) && sub(array("A+"),$ArdhadhAtuka_pratyayas,blank(0),0) && $lakAra!=="" )
{
    $text=two(array("A+"),$ArdhadhAtuka_pratyayas,array("I+"),$ArdhadhAtuka_pratyayas,0);
    echo "<p class = sa >By ghumAsthAgApAjahAtisAM hali (".link_sutra("6.4.66").") :</p>\n"; 
    echo "<p class = sa >घुमास्थागापाजहातिसां हलि (७.४.६६) :</p>\n";
    display(0);    
}
/* vibhASA lIyateH (6.1.50) */
if (sub(array("le","lE"),array("+"),blank(0),0) && ((ends(array($fo),array("lI"),4) && $verbset==="kryAdi") || (ends(array($fo),array("lIN"),4) && $verbset==="divAdi") ))
{
    $text=three(array("le","lE"),array("+"),blank(0),array("lA","lA"),array("+"),blank(0),1);    
    echo "<p class = sa >By vibhASA lIyateH (".link_sutra("6.1.50").") 2:</p>\n"; 
    echo "<p class = sa >विभाषा लीयतेः (६.१.५०) :</p>\n";
    display(0);
	if (in_array($sanAdi,array("Ric","RiN")))
	{
		/* arttihrIvlIrIknUyIkSmAyyAtAM puGNau (7.3.36) */
		$text=two(array("A"),array("+"),array("Ap"),array("+"),0);
		echo "<p class = sa >By arttihrIvlIrIknUyIkSmAyyAtAM puGNau (".link_sutra("7.3.36").") :</p>\n"; 
		echo "<p class = sa >अर्तिह्रीव्लीरीक्नूयीक्ष्माय्यातां पुङ्णौ (७.३.३६) :</p>\n";
		display(0);    
		echo "<p class = pa >sanAdyantA dhAtavaH (".link_sutra("3.1.32").") :</p>\n"; 
		echo "<p class = pa >सनाद्यन्ता धातवः (३.१.३२) :</p>\n";
		display(0);		
	}
}
/* patch for yAsuT Agama to combine it with the next pratyaya. because it would not be getting iDAgama. */
$text = one(array("+yA+"),array("+yA"),0);
/* Adding iDAgama actually */
if ($id_dhAtu==="sew" && $id_pratyaya==="sew" && !($yAsuT===1 && $lakAra==="ASIrliN")  && !in_array("iw",$Agama) && $caG!==1 && $ksa!==1) // for seT dhAtus
{
	$text1= array();
	foreach ($text as $value)
	{
		$parts = explode('+',$value);
		if (preg_match('/^['.pc('vl').']/',$parts[count($parts)-1]))
		{
		$parts[count($parts)-1]="i".$parts[count($parts)-1];
		}
		$text1[]=implode('+',$parts);
	}
	$text = $text1;
	// Removing unwanted iDAgama which happenned accidentally
	$text = one(array("+iyAs+","Ay+t","daridrA+is"),array("+yAs+","Ay+it","daridrA+s"),0);
	$text = two(array("sfap","spfaS","mfaS","kfaz","tfap","dfap"),array("+i"),array("sarp","sparS","marS","karz","tarp","darp"),array("+i"),0); // not before iDAgama.
	echo "<p class = sa >By ArdhadhAtukasyeDvalAdeH (".link_sutra("7.2.35").") :</p>\n"; 
	echo "<p class = sa >आर्धधातुकस्येड्वलादेः (७.२.३५) 1:</p>\n";
    display(0);
	$Agama=array_merge($Agama,array("iw"));
}
if ($id_dhAtu==="vew" && $id_pratyaya==="sew" && !($yAsuT===1 && $lakAra==="ASIrliN")  && !in_array("iw",$Agama) && !in_array($sanAdi,array("Ric","RiN")) && $caG!==1 && $ksa!==1) // for veT dhAtus optional one.
{
	$text2= array();
	foreach ($text as $value)
	{
		$parts = explode('+',$value);
		if (preg_match('/^['.pc('vl').']/',$parts[count($parts)-1]))
		{
		$parts[count($parts)-1]="i".$parts[count($parts)-1];
		}
		$text2[]=implode('+',$parts);
	}
	$text = array_merge($text,$text2);
	$text = one(array("tfap+isya","dfap+isya","gopAya+sya","u+vay+Ta"),array("tfap+sya","dfap+sya","gopAya+isya","u+vay+iTa"),0); // for veT dhAtus tRp and dRp - iDAgama doesn't apply after amAgama.
	// Removing unwanted iDAgama which happenned accidentally
	$text = one(array("+iyAs+","Ay+t","vaD+s","gopAya+s","gopAya+s","gup+is"),array("+yAs+","Ay+it","vaD+is","gopAya+is","gopAya+is","gup+s"),0);
	$text = two(array("sfap","spfaS","mfaS","kfaz","tfap","dfap"),array("+i"),array("sarp","sparS","marS","karz","tarp","darp"),array("+i"),0); // not before iDAgama.
	echo "<p class = sa >By ArdhadhAtukasyeDvalAdeH (".link_sutra("7.2.35").") :</p>\n"; 
	echo "<p class = sa >आर्धधातुकस्येड्वलादेः (७.२.३५) 2:</p>\n";
    display(0);
	$Agama=array_merge($Agama,array("iw"));
}
// patch for Svi dhAtu
if (ends(array($fo),array("wuo!Svi"),4) && sub(array("Svi"),array("+"),prat('vl'),0) )
{
	$text = three(array("Svi","Su+Su"),array("+"),prat('vl'),array("Svi","Su+Su"),array("+i"),prat('vl'),0);
	echo "<p class = sa >By ArdhadhAtukasyeDvalAdeH (".link_sutra("7.2.35").") :</p>\n"; 
	echo "<p class = sa >आर्धधातुकस्येड्वलादेः (७.२.३५) 3:</p>\n";
    display(0);
}
/* vadavrajahalantasya acaH (7.2.3) */ 
if ( arr($text,'/['.pc('ac').'](['.pc('hl').'M]*)[+][st]/')  && $lakAra==="luN"  && $sic!==0 && in_array($so,$tis) && $yamarama!==1)
{
	foreach ($text as $value)
		{
		$value = preg_replace('/(['.pc('ac').'])(['.pc('hl').'M]*)([+])([st])/','$1?$2$3$4',$value);
		$aca[] = str_replace(array("a?","A?","i?","I?","u?","U?","f?","F?","x?","X?","e?","o?","E?","O?",),array("A","A","E","E","O","O","Ar","Ar","Al","Al","E","O","E","O",),$value);
	}
	$text = $aca; $aca=array();
	$text = one(array("+sA+t",),array("+sa+t"),0);
    echo "<p class = sa >By vadavrajahalantasya acaH (".link_sutra("7.2.3").") :</p>\n";
    echo "<p class = sa >वदव्रजहलन्ताच्च अचः (७.२.३) 2:</p>\n";
    display(0);
}
/* pugantalaghUpadhasya ca (7.3.86) */
if ( $atolopa!==1 && $ardhadhatuka===1 && sub(array("i","u","f","x"),$hl,array("+sI","+isI"),0) && !arr($text,"/[+][i][y][+][t]$/")  && $didhI!==1 && ($sIyuT===1||$sic===1) && $vijait!==1 )
{
	$text=three(array("i","u","f","x"),$hl,array("+sI","+isI"),array("e","o","ar","al"),$hl,array("+sI","+isI"),0);
	if ($id_dhAtu==="vew") // e.g. gfhU! - garhizIzwa ang GfzIzwa are expected outcome.
	{
		$text=three(array("e","o","ar","al"),$hl,array("+sI"),array("i","u","f","x"),$hl,array("+sI"),0);	
	}
    echo "<p class = sa >pugantalaghUpadhasya ca (7.3.86) :</p>\n"; 
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 10:</p>\n";
    display(0);    
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) patch for UrNu */
if ( sub(array("UrRunu","UrRu"),array("+"),array("i"),0) )
{
    $text=three(array("UrRunu","UrRu"),array("+"),array("i"),array("UrRuno","UrRo"),array("+"),array("i"),1);
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") :</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* bhuvo vugluGliToH (6.4.88) */
if ( arr($text,'/[B][U][+]['.pc('ac').']/') && in_array($lakAra,array("luN","liw")))
{
    $text = three(array("BU"),array("+"),$ac,array("BUv"),array("+"),$ac,0);
    echo "<p class = sa >By bhuvo vugluGliToH (".link_sutra("6.4.88").") :</p>\n"; 
    echo "<p class = hn >Because of asiddhavadatrAbhAt (6.4.22), vuk is asiddha to uvaG. Therefore, uvaG may be applicable, but according to vugyuTAvuvaGyaNoH siddhau vaktavyau (vA), it is made siddha.</p>\n"; 
    echo "<p class = sa >भुवो वुग्लुङ्लिटोः (६.४.८८) :</p>\n";
    echo "<p class = hn >असिद्धवदत्राभात्‌ (६.४.२२) इति वुकोऽसिद्धत्वादुवङि प्राप्ते वुग्युटावुवङ्‍यणोः सिद्धौ वक्तव्यौ (वा) इत्यनेन उवङ्‌ निषिध्यते ।</p>\n";
    display(0);
}
/* bhavateraH (7.4.73) */
if ( sub(array("bu+BUv"),array("+"),blank(0),0) && $lakAra==="liw")
{
	$text = two(array("bu+BUv"),array("+"),array("ba+BUv"),array("+"),0);
    echo "<p class = sa >By bhavateraH (".link_sutra("7.4.73").") :</p>\n";
    echo "<p class = sa >भवतेरः (७.४.७३) :</p>\n";
    display(0);
}
/* zRRdRRprAM hrasvo vA (7.4.12) */
if ( sub(array("Sa+SF","da+dF","pa+pF"),array("+"),blank(0),0) && $lakAra==="liw" && in_array("k",$itpratyaya))
{
	$text = two(array("Sa+SF","da+dF","pa+pF"),array("+"),array("Sa+Sf","da+df","pa+pf"),array("+"),1);
    echo "<p class = sa >By zRRdRRprAM hrasvo vA (".link_sutra("7.4.12").") :</p>\n";
    echo "<p class = sa >शॄदॄप्रां ह्रस्वो वा(७.४.१२) :</p>\n";
    display(0);
}
/* RcCatyRRtAm (7.4.11) */
if ( sub(array("A+f","A+fcC","A+nf","A+nfcC","F"),array("+"),blank(0),0) && $lakAra==="liw" && (in_array($fo,array("f","fCa!")) || ends(array($fo),array("F"),1) ) && $lakAra==="liw")
{
	$text = two(array("A+f","A+fcC","A+nf","A+nfcC","F"),array("+"),array("A+ar","A+arcC","A+nar","A+narcC","ar"),array("+"),0);
    echo "<p class = sa >By RcCatyRRtAm (".link_sutra("7.4.11").") :</p>\n";
    echo "<p class = sa >ऋच्छत्यॄताम्‌(७.४.११) :</p>\n";
    display(0);
}
foreach ($tiG1 as $value) {$iDtiG = "i".$value;} // defining iDtiG i.e. iDAgama+tiG1.
/* aco JNiti patch for liT. */
if ( (sub($ac,array("+"),$tiG1,0)||sub($ac,array("+"),$iDtiG,0)) && in_array($so,array("tip")) && $lakAra==="liw")
{
    $text = pr2($ac,array("+"),$tiG1,vriddhi($ac),array("+"),$tiG1,$text);
    $text = pr2($ac,array("+"),$iDtiG,vriddhi($ac),array("+"),$iDtiG,$text);
    echo "<p class = sa >By aco JNiti (".link_sutra("7.2.115").") :</p>\n";
    echo "<p class = sa >अचो ञ्णिति (७.२.११५) :</p>\n";
    display(0);
}
/* aco JNiti patch for liT mip. */
if ( (sub($ac,array("+"),$tiG1,0)||sub($ac,array("+"),$iDtiG,0)) && in_array($so,array("mip")) && $lakAra==="liw")
{
    $text1 = pr2($ac,array("+"),$tiG1,vriddhi($ac),array("+"),$tiG1,$text);
    $text2 = pr2($ac,array("+"),$iDtiG,vriddhi($ac),array("+"),$iDtiG,$text);
	$text = array_merge($text,$text1,$text2);
    echo "<p class = sa >By aco JNiti (".link_sutra("7.2.115").") :</p>\n";
    echo "<p class = sa >अचो ञ्णिति (७.२.११५) :</p>\n";
    display(0);
}
/* dAderdhAtorghaH (8.2.33) */
if (sub($dade,blank(0),blank(0),0) && arr($text,'/['.pc('hl').'][+]['.pc("Jl").']/') )
{
    $text = three(array("dah","dAh","dih","duh","dfh","drAh","druh"),array("+"),prat('Jl'),array("daG","dAG","diG","duG","dfG","drAG","druG"),array("+"),prat('Jl'),0);
    echo "<p class = sa >By dAderdhAtorghaH (".link_sutra("8.2.33").") :</p>\n";
    echo "<p class = sa >दादेर्धातोर्घः (८.२.३३) :</p>\n";
    display(0); 
    $hodha1 = 1; // 0 - doesn't prevent ho DhaH. 1 - prevents ho DhaH.
} else { $hodha1 = 0; } 
/* anudAttopadezavanatitanotyAdInAmanunAsikalopo jhali kGiti (6.4.37) */
if ( pr2(array("man","han","gam","ram","nam","yam","van","tan","san","kzaR","kziR","fR","tfR","GfR","man",),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("ma","ha","ga","ra","na","ya","va","ta","sa","kza","kzi","f","tf","Gf","ma",),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text)!==$text && in_array($so,$tiG) && (in_array("N",$it)||in_array("k",$it)) && !sub(array("+"),prat("hm"),blank(0),0))
{
    $text = pr2(array("man","han","gam","ram","nam","yam","van","tan","san","kzaR","kziR","fR","tfR","GfR","man",),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("ma","ha","ga","ra","na","ya","va","ta","sa","kza","kzi","f","tf","Gf","ma",),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >By anudAttopadezavanatitanotyAdInAmanunAsikalopo jhali kGiti (".link_sutra("6.4.37").") :</p>\n";
    echo "<p class = sa >अनुदात्तोपदेशवनतितनोत्यादीनामनुनासिकलोपो झलि क्ङिति (६.४.३७) :</p>\n";  
    display(0);    
}
/* Ato lopa iTi ca (7.4.64) */
if ( sub(array("A+"),array("i"),$tiG1,0) && $lakAra!=="" && in_array("iw",$Agama) )
{
    $text=three(array("A"),array("+"),array("iTa"),array(""),array("+"),array("iTa"),0);
    echo "<p class = sa >By Ato lopa iTi ca (".link_sutra("7.4.64").") :</p>\n"; 
    echo "<p class = sa >आतो लोप इटि च (७.४.६४) :</p>\n";
    display(0);    
}
/* iDattyartivyayatInAm (7.2.66) */
if ( sub(array("ad","f","vyay"),array("+"),$tiG1,0) && in_array($fo,array("adx!","ada!","f","vyeY")) && $lakAra==="liw" && $so==="sip" && !in_array("iw",$Agama) )
{
    $text=two(array("ad","f","vyay"),array("+"),array("ad","f","vyay"),array("+i"),0);
    echo "<p class = sa >By iDattyartivyayatInAm (".link_sutra("7.4.66").") :</p>\n"; 
    echo "<p class = sa >इडत्त्यर्तिव्ययतीनाम्‌ (७.४.६६) :</p>\n";
    display(0);    
}
/* Rtazca saMyogAderguNaH (7.2.42) */
if ( arr($text,'/['.pc('hl').']['.pc('hl').'][f][+]/') && $lakAra==="liw" && in_array("k",$itpratyaya) )
{
    $text=three($hl,$hl,array("f+"),$hl,$hl,array("ar+"),0);
    echo "<p class = sa >By Rtazca saMyogAderguNaH (".link_sutra("7.4.42").") :</p>\n"; 
    echo "<p class = sa >ऋतश्च संयोगादेर्गुणः (७.४.४२) :</p>\n";
    display(0);    
}
/* masjinazorjhali (7.1.60) */ 
if ( sub(array("masj","naS"),array("+"),prat('Jl'),0) && ends(array($fo),array("wumasjo!","RaSa!"),4) )
{ 
    $text = three(array("masj","naS"),array("+"),prat('Jl'),array("mansj","nanS"),array("+"),prat('Jl'),0);
    echo "<p class = sa >By masjinazorjhali (".link_sutra("7.1.60").") :</p>\n";
    echo "<p class = sa >मस्जिनशोर्झलि (७.१.६०) :</p>\n";
    display(0);
}
// ata upadhAyAH patch for liT.
if ( sub(array("a"),$hl,array("+"),0) && in_array($so,array("tip")) && $lakAra==="liw" )
{
    $text = three(array("a"),$hl,array("+"),array("A"),$hl,array("+"),0);
    echo "<p class = sa >By ata upadhAyAH (".link_sutra("7.2.116").") :</p>\n";
    echo "<p class = sa >अत उपधायाः (७.२.११६) :</p>\n";
    display(0);
}
// ata upadhAyAH patch for liT.
if ( sub(array("a"),$hl,array("+"),0) && in_array($so,array("mip")) && $lakAra==="liw" )
{
    $text = three(array("a"),$hl,array("+"),array("A"),$hl,array("+"),1);
    echo "<p class = sa >By ata upadhAyAH (".link_sutra("7.2.116").") :</p>\n";
    echo "<p class = sa >अत उपधायाः (७.२.११६) :</p>\n";
    display(0);
}
/* dIGo yuDaci kGiti (6.4.64) */
if ( sub(array("dI"),array("+"),$ac,0) && $kGiti===1)
{
    $text=three(array("dI"),array("+"),$ac,array("dI"),array("+y"),$ac,0);
    echo "<p class = sa >By dIGo yuDaci kGiti (".link_sutra("6.4.64").") :</p>\n"; 
    echo "<p class = sa >दीङो युडचि क्ङिति (६.४.६४) :</p>\n";
    display(0);
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
if ( pr2(array("i","I","u","U","f","F","x","X",),array("+"),$ArdhadhAtuka_tiG_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+"),$ArdhadhAtuka_tiG_pratyayas,$text)!==$text && $ardhadhatuka===1  && $didhI!==1  && $kGiti!==1 && $sIyuT!==1 && $bhUsuvo!==1)
{
    $text=pr2(array("i","I","u","U","f","F","x","X",),array("+"),$ArdhadhAtuka_tiG_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+"),$ArdhadhAtuka_tiG_pratyayas,$text);
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") :</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
if ( (    pr2(array("i","I","u","U","f","F","x","X",),array("+i"),$ArdhadhAtuka_tiG_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+i"),$ArdhadhAtuka_tiG_pratyayas,$text)!==$text || pr2(array("i","I","u","U","f","F","x","X",),array("+"),$tiG1,array("e","e","o","o","ar","ar","al","al",),array("+"),$tiG1,$text)!==$text) && ($sarvadhatuka===1 || $ardhadhatuka===1) && $didhI!==1 && $kGiti!==1 && $bhUsuvo!==1)
{
    $text=pr2(array("i","I","u","U","f","F","x","X",),array("+i"),$ArdhadhAtuka_tiG_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+i"),$ArdhadhAtuka_tiG_pratyayas,$text);
    $text=pr2(array("i","I","u","U","f","F","x","X",),array("+"),$ArdhadhAtuka_tiG_pratyayas,array("e","e","o","o","ar","ar","al","al",),array("+"),$ArdhadhAtuka_tiG_pratyayas,$text);
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") :</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* pugantalaghUpadhasya ca (7.3.86) */
if ( $ardhadhatuka===1 && (sub(array("i","u","f","x"),$hlplus,$tiG1,0) || sub(array("i","u","f","x"),$hlplus,$iDtiG,0) ) && $didhI!==1 && $kGiti!==1 && $vijait!==1 && $lakAra==="liw")
{
	$text=three(array("i","u","f","x"),$hlplus,$iDtiG,array("e","o","ar","al"),$hlplus,$iDtiG,0);
	$text=three(array("i","u","f","x"),$hlplus,$tiG1,array("e","o","ar","al"),$hlplus,$tiG1,0);
	if ($id_dhAtu==="vew") // e.g. gfhU! - garhizIzwa ang GfzIzwa are expected outcome.
	{
		$text=three(array("e","o","ar","al"),$hl,array("+sI"),array("i","u","f","x"),$hl,array("+sI"),0);	
	}
    echo "<p class = sa >pugantalaghUpadhasya ca (7.3.86) :</p>\n"; 
    echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 11:</p>\n";
    display(0);    
}
/* abhyAsasyAsavarNe (6.4.78) */
if ( arr($text,'/^[iIuU][+][eoEO]/') && $abhyAsa===1)
{
    $text=change('/^([iI])([+][eoEO])/','$1y$2');
    $text=change('/^([uU])([+][eoEO])/','$1v$2');
    echo "<p class = sa >By abhyAsasyAsavarNe (".link_sutra("6.4.78").") :</p>\n";
    echo "<p class = sa >अभ्यासस्यासवर्णे(६.४.७८) :</p>\n";
    display(0);
}
if ($lakAra==="liw" && arr($text,'/['.pc('hl').'][iI][+][aAiIuUfFxXeEoO]/') && !arr($text,'/['.pc('hl').']['.pc('hl').'][iI][+][aAiIuUfFxXeEoO]/') )
{
	$text=three($hl,array("i+","I+"),$ac,$hl,array("y+","y+"),$ac,0);
	echo "<p class = sa >By eranekAco'saMyogapUrvasya (".link_sutra("6.4.82").") :</p>\n";
	echo "<p class = sa >एरनेकाचोऽसंयोगपूर्वस्य (६.४.८२) :</p>\n";
	display(0);	
}
/* vA jRRbhramutrasAm (6.4.124) */
if ( sub(array("ja+jar","ba+Bram","ta+tras"),array("+"),blank(0),0) && $lakAra==="liw" && !ends(array($so),array("tip","mip"),2) )
{
    $text=two(array("ja+jar","ba+Bram","ta+tras"),array("+"),array("jer","Brem","tres"),array("+"),1);
    echo "<p class = sa >By vA jRRbhramutrasAm (".link_sutra("6.4.124").") :</p>\n"; 
    echo "<p class = sa >वा जॄभ्रमुत्रसाम्‌ (६.४.१२४) :</p>\n";
    display(0);
}
/* phaNAM ca saptAnAm (6.4.125) */elseif ( sub(array("pa+PaR","ra+rAj","ba+BrAj","ba+BrAS","ba+BlAS","sa+syam","sa+svan","da+Dvan"),array("+"),blank(0),0) && $lakAra==="liw" && !ends(array($so),array("tip","mip"),2) )
{
    $text=two(array("pa+PaR","ra+rAj","ba+BrAj","ba+BrAS","ba+BlAS","sa+syam","sa+svan","da+Dvan"),array("+"),array("PeR","rej","Brej","BreS","BleS","syem","sven","Dven"),array("+"),1);
    echo "<p class = sa >By phaNAM ca saptAnAm (".link_sutra("6.4.125").") :</p>\n"; 
    echo "<p class = sa >फणां च सप्तानाम्‌ (६.४.१२५) :</p>\n";
    display(0);
}
/* tRRphalabhajatrapazca (6.4.122) */
elseif ( sub(array("ta+tar","pa+Pal","ta+trap"),array("+"),blank(0),0) && $lakAra==="liw" && !ends(array($so),array("tip","mip"),2) )
{
    $text=two(array("ta+tar","pa+Pal","ta+trap"),array("+"),array("ter","Pel","trep"),array("+"),0);
    echo "<p class = sa >By tRRphalabhajatrapazca (".link_sutra("6.4.122").") :</p>\n"; 
    echo "<p class = sa >तॄफलभजत्रपश्च (६.४.१२२) :</p>\n";
    display(0);
}
/* rAdho hiMsAyAm (6.4.123) */
elseif ( sub(array("ra+rAD"),array("+"),blank(0),0) && $lakAra==="liw" && !ends(array($so),array("tip","mip"),2) && $verbset==="svAdi")
{
    $text=two(array("ra+rAD"),array("+"),array("reD"),array("+"),0);
    echo "<p class = sa >By rAdho hiMsAyAm (".link_sutra("6.4.123").") :</p>\n"; 
    echo "<p class = sa >राधो हिंसायाम्‌ (६.४.१२३) :</p>\n";
    display(0);
}
/* na zasadadavAdiguNAnAm (6.4.126) */
elseif ( sub(array("Sa+Sas","da+dad","ar"),array("+"),blank(0),0) && $lakAra==="liw" && !ends(array($so),array("tip","mip"),2) )
{
    echo "<p class = sa >By na zasadadavAdiguNAnAm (".link_sutra("6.4.126").") :</p>\n"; 
    echo "<p class = sa >न शसददवादिगुणानाम्‌ (६.४.१२६) :</p>\n";
    display(0);
}
/* na zasadadavAdiguNAnAm (6.4.126) */
elseif ( sub(array("va+vaj","va+vaK","va+vaw","va+vaW","va+vaR","va+van","va+val","va+vaz","va+vam"),array("+"),blank(0),0) && $lakAra==="liw" && !ends(array($so),array("tip","mip"),2) )
{
    echo "<p class = sa >By na zasadadavAdiguNAnAm (".link_sutra("6.4.126").") :</p>\n"; 
    echo "<p class = sa >न शसददवादिगुणानाम्‌ (६.४.१२६) :</p>\n";
    display(0);
}
/* gamahanajanakhanaghasAM lopaH kGiti (6.4.98) */
elseif ( sub(array("gam","Gan","jan","Gas","Kan"),array("+"),blank(0),0) && in_array($so,$tiG) && ends(array($fo),array("gamx!","hana!","jana!","Gasa!","Kanu!","janI!"),4) )
{
}
/* ata ekahalmadhye'nAdezAderliTi (6.4.120) */
elseif ( arr($text,'/^['.pc('hl').'][a][+]['.pc('hl').'][a]['.pc('hl').'][+]/') && $lakAra==="liw" && !ends(array($so),array("tip","mip"),2) )
{
    $text=change('/^(['.pc('hl').'][a][+])(['.pc('hl').'])([a])(['.pc('hl').'][+])/','$2e$4');
	$text=change('/^(['.pc('hl').'])([e])(['.pc('hl').'][+][T][a]$)/','$1a+$1a$3'); // for thal.
    echo "<p class = sa >By ata ekahalmadhye'nAdezAderliTi (".link_sutra("6.4.120").") :</p>\n"; 
    echo "<p class = sa >अत एकहल्मध्येऽनादेशादेर्लिटि (६.४.१२०) :</p>\n";
    display(0);
}
/* zAsivasighasInAM ca (8.3.60) */
if ( arr($text,'/[G][s][+]/') && ends(array($fo),array("Gasa!","ada!"),4) && in_array($so,$tiG) )
{
    $text = two(array("Gs"),array("+"),array("Gz"),array("+"),0);
    echo "<p class = sa >By zAsivasighasInAM ca (".link_sutra("8.3.60").") :</p>\n";
    echo "<p class = sa >शासिवसिघसीनां च (८.३.६०) :</p>\n";
    display(0);
}
$novrddhi=0;
/* ato lrAntasya (7.2.2) */
if ( sub(array("ar","al"),array("+"),array("s","is"),0) && $sic===1 && in_array($so,$tis) )
{
    $text=three(array("ar","al"),array("+"),array("s","is"),array("Ar","Al"),array("+"),array("s","is"),0);
    echo "<p class = sa >By ato lrAntasya (".link_sutra("7.2.2").") :</p>\n"; 
    echo "<p class = sa >अतो ल्रान्तस्य (७.२.२) :</p>\n";
    display(0);
}
/* vadavrajahalantasya acaH (7.2.3) */ 
elseif ( sub(array("vad","vraj"),array("+"),array("is"),0) && $lakAra==="luN" && $sic!==0 && in_array($so,$tis) )
{ 
    $text = three(array("vad","vraj"),array("+"),array("is"),array("vAd","vrAj"),array("+"),array("is"),0);
    echo "<p class = sa >By vadavrajahalantasya acaH (".link_sutra("7.2.3").") :</p>\n";
    echo "<p class = sa >वदव्रजहलन्ताच्च अचः (७.२.३) 1:</p>\n";
    display(0);
}
/* hmyantakSaNazvasajAgRNizvyeditAm (7.2.5) */
elseif ( ( sub($ac,array("h","m","y"),array("+is"),0) || sub(array("kzaR","Svas","jAgf","Svi"),array("+is"),blank(0),0) || in_array($sanAdi,array("Ric","RiN")) || ends(array($fo),$editverbs,4) ) && $sic===1 && in_array($so,$tis) )
{
    echo "<p class = pa >By hmyantakSaNazvasajAgRNizvyeditAm (".link_sutra("7.2.5").") :</p>\n"; 
    echo "<p class = pa >ह्म्यन्तक्षणश्वसजागृणिश्व्येदिताम्‌ (७.२.५) :</p>\n";
    display(0);
	$novrddhi=1;
}
/* ato halAderlaghoH (7.2.7) */
elseif ( sub(array("a"),$hl,array("+is"),0)  && $sic===1 && in_array($so,$tis) )
{
	$text = three(array("a"),$hl,array("+is"),array("A"),$hl,array("+is"),1);
    echo "<p class = sa >By ato halAderlaghoH (".link_sutra("7.2.7").") :</p>\n"; 
    echo "<p class = sa >अतो हलादेर्लघोः (७.२.७) :</p>\n";
    display(0);
}
/* UrNotervibhASA (7.2.6) */
elseif ( sub(array("UrRu"),array("+"),array("is"),0) && $sic===1 && in_array($so,$tis) )
{
    $text=three(array("UrRu"),array("+"),array("is"),array("UrRO"),array("+"),array("is"),1);
    $text=three(array("UrRu"),array("+"),array("is"),array("UrRo"),array("+"),array("is"),1);
    echo "<p class = sa >By UrNotervibhASA (".link_sutra("7.2.6").") :</p>\n"; 
    echo "<p class = sa >ऊर्णोतेर्विभाषा (७.२.६) :</p>\n";
    display(0);
}
/* neTi (7.2.4) */
elseif ( arr($text,'/['.pc('ac').'](['.pc('hl').'M]*)[+][i][s]/') && $sic===1 && in_array($so,$tis) )
{
    echo "<p class = pa >By neTi (".link_sutra("7.2.4").") :</p>\n"; 
    echo "<p class = pa >नेटि (७.२.४) :</p>\n";
    display(0);
	$novrddhi=1;
}
/* sici vRddhiH parasmaipadeSu (7.2.1) */
elseif ( sub($ik,array("+"),array("s","is"),0) && $sic===1 && in_array($so,$tis) )
{
    $text=three($ik,array("+"),array("s","is"),vriddhi($ik),array("+"),array("s","is"),0);
    echo "<p class = sa >By sici vRddhiH parasmaipadeSu (".link_sutra("7.2.1").") :</p>\n"; 
    echo "<p class = sa >सिचि वृद्धिः परस्मैपदेषु (७.२.१) :</p>\n";
    display(0);
}
/* ato lopaH (6.4.48) */
// patch for han -> vaDa
if ( sub(array("vaDa"),array("+i"),blank(0),0) )
{ 
	$text = two(array("vaDa"),array("+i"),array("vaD"),array("+i"),0);
    echo "<p class = sa >By ato lopaH (".link_sutra("6.4.48").") :</p>\n";
    echo "<p class = sa >अतो लोपः (६.४.४८) :</p>\n";
    display(0);
    $atolopa=1;
}
/* skoH saMyogAdyorante ca (8.2.29) */
if ( (arr($text,'/[s]['.flat($hl).'][+]/') || sub(array("vrASc"),array("+"),blank(0),0) ) && in_array($so,$tiG) && in_array($lakAra,array("luN"))) // for luN
{
foreach ($hl as $value) { $ska[] = "s".$value."+"; }
$text = one($ska,$hlplus,0);
$text = one(array("vrASc"),array("vrAc"),0);
echo "<p class = sa >By skoH saMyogAdyorante ca (".link_sutra("8.2.29").") :</p>\n";
echo "<p class = sa >स्कोः संयोगाद्योरन्ते च (८.२.२९) :</p>\n";
display(0);
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
// patch for zvi, jAgf etc.
if ( sub(array("Svi","jAgf"),array("+"),array("is"),0) )
{
    $text=three(array("Svi","jAgf"),array("+"),array("is"),array("Sve","jAgar"),array("+"),array("is"),0);
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") :</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* iTa ITi (8.2.28) */ 
if ( sub(array("+isI"),array(""),blank(0),0) && in_array($so,$tiG) && $lakAra==="luN" )
{
    $text=one(array("+isI"),array("+iI"),0);
    echo "<p class = sa >By iTa ITi (".link_sutra("8.2.28").") :</p>\n"; 
    echo "<p class = sa >इट ईटि (८.२.२८) :</p>\n";
    display(0);
    /* akaH savarNe dIrghaH (6.1.101) */ 
    if (sub(array("i"),array("I"),blank(0),0))
    {
    $text = two(array("i"),array("I"),array("I"),blank(2),0);
	echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
    echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
    display(0);
    }
}
/* mRjervRddhiH (7.2.114) */
if ( sub(array("mfj","marj"),array("+","+i"),$ac,0) && in_array($so,$tiG) && $ardhadhatuka===1 && ends($itpratyaya,array("k","N"),2))
{
    $text=three(array("mfj","marj"),array("+"),$ac,array("mArj","mArj"),array("+"),$ac,1);
    echo "<p class = sa >By mRjervRddhiH (".link_sutra("7.2.114").") and kGityajAdau veSyate :</p>\n"; 
    echo "<p class = sa >मृजेर्वृद्धिः (७.२.११४) तथा क्ङित्यजादौ वेष्यते । :</p>\n";
    display(0);    
}
/* mRjervRddhiH (7.2.114) */
elseif ( pr2(array("mfj","marj"),array("+","+i"),$ArdhadhAtuka_tiG_pratyayas,array("mArj"),array("+","+i"),$ArdhadhAtuka_tiG_pratyayas,$text)!==$text && in_array($so,$tiG))
{
    $text=pr2(array("mfj","marj"),array("+","+i"),$ArdhadhAtuka_tiG_pratyayas,array("mArj","mArj"),array("+","+i"),$ArdhadhAtuka_tiG_pratyayas,$text);
    echo "<p class = sa >By mRjervRddhiH (".link_sutra("7.2.114").") :</p>\n"; 
    echo "<p class = sa >मृजेर्वृद्धिः (७.२.११४) :</p>\n";
    display(0);    
}

/* tanAdibhyastathAsoH (2.4.79) */
if ( ends(array($fo),$tanAdi,4) && sub($hl,array("+"),array("ista","isTAs"),0) && in_array($so,$taG) )
{
	$text = two(array("ArR","kziR","tfR","GfR"),array("+ista","+isTAs"),array("ArR","kziR","tfR","GfR"),array("+ta","+TAs"),0);
	$text = three($hl,array("+"),array("ista","isTAs"),$hl,array("+"),array("ista","isTAs"),0);
	echo "<p class = sa >By tanAdibhyastathAsoH (".link_sutra("2.4.79").") :</p>\n";
	echo "<p class = sa >तनादिभ्यस्तथासोः (२.४.७९) :</p>\n";
	display(0);
// Right now placed it here. There is some problem with the next entry.
	if (sub(array("jan","san","Kan"),array("+"),prat("Jl"),0))
	{
	$text=three(array("jan","san","Kan"),array("+"),prat("Jl"),array("jaA","saA","KaA"),array("+"),prat("Jl"),0);
    echo "<p class = sa >By janasanakhanAM saJjhaloH (".link_sutra("6.4.42").") :</p>\n"; 
    echo "<p class = sa >जनसनखनां सञ्झलोः (६.४.४२) :</p>\n";
    display(0);
	}
	else
	{
	$text = three(array("man","han","gam","ram","nam","yam","van","tan","san","kzaR","kziR","ArR","tfR","GfR","man",),array("+"),prat('Jl'),array("ma","ha","ga","ra","na","ya","va","ta","sa","kza","kzi","Ar","tf","Gf","ma",),array("+"),prat('Jl'),0);
    echo "<p class = sa >By anudAttopadezavanatitanotyAdInAmanunAsikalopo jhali kGiti (".link_sutra("6.4.37").") :</p>\n";
    echo "<p class = sa >अनुदात्तोपदेशवनतितनोत्यादीनामनुनासिकलोपो झलि क्ङिति (६.४.३७) :</p>\n";  
    display(0);		
	}
}
/* anudAttopadezavanatitanotyAdInAmanunAsikalopo jhali kGiti (6.4.37) */
if ( (sub(array("man","han","gam","ram","nam","yam","van","tan","san","kzaR","kziR","fR","tfR","GfR","man",),array("+"),prat('Jl'),0) && in_array($so,$tiG) && ($sIyuT===1||$sic!==0) && (in_array("N",$it)||in_array("k",$it))) || $vAgamaH===1 ) 
{
	if ($vAgamaH===1) // vAgamaH is optional kit.
	{
	$text = three(array("gam","yam"),array("+"),prat('Jl'),array("ga","ya"),array("+"),prat('Jl'),1);	
	}
    else
	{
	$text = three(array("man","han","gam","ram","nam","yam","van","tan","san","kzaR","kziR","fR","tfR","GfR","man",),array("+"),prat('Jl'),array("ma","ha","ga","ra","na","ya","va","ta","sa","kza","kzi","f","tf","Gf","ma",),array("+"),prat('Jl'),0);
	}
    echo "<p class = sa >By anudAttopadezavanatitanotyAdInAmanunAsikalopo jhali kGiti (".link_sutra("6.4.37").") :</p>\n";
    echo "<p class = sa >अनुदात्तोपदेशवनतितनोत्यादीनामनुनासिकलोपो झलि क्ङिति (६.४.३७) :</p>\n";  
    display(0);    
}
/* dIpajanabudhapUritAyipyAyibhyo'nyatarasyAm (3.1.61) */ 
if ( ends(array($fo),array("dIpI!","janI!","buDa!","pUrI!","tAyf!","o!pyAyI!"),4) && $lakAra==="luN" && $so==="ta" )
{ 
    $text = three(array("dIp","jan","buD","pUr","tAy","pyAy"),array("+"),array("ista","ta"),array("dIp","jan","buD","pUr","tAy","pyAy"),array("+ciR+"),array("ista","ta"),1);
    echo "<p class = sa >By dIpajanabudhapUritAyipyAyibhyo'nyatarasyAm (".link_sutra("3.1.61").") :</p>\n";
    echo "<p class = sa >दीपजनबुधपूरीतायिप्यायिभ्योऽन्यतरस्याम्‌ (३.१.६१) :</p>\n";
    display(0);
	$ciN=1;
} else { $ciN=0; }
/* ciN te padaH (3.1.60) */ 
if ( ends(array($fo),array("pada!"),4) && $lakAra==="luN" && $so==="ta" )
{ 
    $text = three(array("pad"),array("+"),array("ta"),array("pad"),array("+ciR+"),array("ta"),0);
    echo "<p class = sa >By ciN te padaH (".link_sutra("3.1.60").") :</p>\n";
    echo "<p class = sa >चिण्‌ ते पदः (३.१.६०) :</p>\n";
    display(0);
	$ciN=1;
}
/* ciNo luk (6.4.104) */ 
if ( $ciN===1 )
{ 
    $text = two(array("+ciR+"),array("ista","ta"),array("+i"),array("",""),0);
    echo "<p class = sa >By ciNo luk (".link_sutra("6.4.104").") :</p>\n";
    echo "<p class = sa >चिणो लुक्‌ (६.४.१०४) :</p>\n";
    display(0);
	/* ata upadhAyAH (7.2.116) */ 
	if ( arr($text,'/[a]['.pc('hl').'][+][i]$/') )
	{
		$text = three(array("a"),$hl,array("+i"),array("A"),$hl,array("+i"),0);
		echo "<p class = sa >By ata upadhAyAH (".link_sutra("7.2.116").") :</p>\n";
		echo "<p class = sa >अत उपधायाः (७.२.११६) :</p>\n";
		display(0);
	}
	/* pugantalaghUpadhasya ca (7.3.86) */
	if ( arr($text,'/[iufx]['.pc('hl').'][+][i]$/') )
	{
		$text=three(array("i","u","f","x"),$hl,array("+i"),array("e","o","ar","al"),$hl,array("+i"),0);
		echo "<p class = sa >pugantalaghUpadhasya ca (7.3.86) :</p>\n"; 
		echo "<p class = sa >पुगन्तलघूपधस्य च (७.३.८६) 9:</p>\n";
		display(0);    		
	}
}
/* yIvarNayordIdhIvevyoH (7.4.53) */
if ( sub(array("dIDI","vevI"),array("+"),array("i","I","y"),0) )
{
    $text=three(array("dIDI","vevI"),array("+"),array("i","I","y"),array("dID","vev"),array("+"),array("i","I","y"),0);
    echo "<p class = sa >By yIvarNayordIdhIvevyoH (".link_sutra("7.4.53").") :</p>\n"; 
    echo "<p class = sa >यीवर्णयोर्दीधीवेव्योः (७.४.५३) :</p>\n";
    display(0);    
}
/* sArvadhAtukArdhadhAtukayoH (7.3.84) */
if ( sub(array("i","I","u","U","f","F","x","X"),array("+"),array("sI","isI"),0) && ($sarvadhatuka===1 || $ardhadhatuka===1) && !($ad===1 && sub(array("i","I","u","U","f","F","x","X"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,0) )  && $didhI!==1 && $kGiti!==1 && $bhUsuvo!==1 )
{
    $text=three(array("i","I","u","U","f","F","x","X",),array("+"),array("sI","isI"),array("e","e","o","o","ar","ar","al","al",),array("+"),array("sI","isI"),0);
    echo "<p class = sa >sArvadhAtukArdhadhAtukayoH (".link_sutra("7.3.84").") :</p>\n"; 
    echo "<p class = sa >सार्वधातुकार्धधातुकयोः (७.३.८४) :</p>\n";
    display(0);    
}
/* ato lopaH (6.4.48) */
//if ( sub(array("Aya"),array("+is","+It","+Is"),blank(0),0) || sub(array("a"),array("+i+"),blank(0),0) )
if ( sub(array("Aya"),array("+is","+It","+Is"),blank(0),0)  )
{ 	$text = two(array("Aya"),array("+is","+It","+Is"),array("Ay"),array("+is","+It","+Is"),0);
//	$text = two(array("a"),array("+i+"),array(""),array("+i+"),0);
    echo "<p class = sa >By ato lopaH (".link_sutra("6.4.48").") :</p>\n";
    echo "<p class = sa >अतो लोपः (६.४.४८) :</p>\n";
    display(0);
    $atolopa=1;
}
/* graho'liTi dIrghaH (7.2.37) */ 
if ( sub(array("grah"),array("+"),array("i"),0) && ends(array($fo),array("graha!"),4) && $lakAra!=="liw")
{ 
    $text = three(array("grah"),array("+"),array("i"),array("grah"),array("+"),array("I"),0);
    echo "<p class = sa >By graho'liTi dIrghaH (".link_sutra("7.2.37").") :</p>\n";
    echo "<p class = sa >ग्रहोऽलिटि दीर्घः (७.२.३७) :</p>\n";
    display(0);
}
/* UdupadhAyA gohaH (6.4.89) */ 
if ( sub(array("goh","guh"),array("+"),$ac,0) && ends(array($fo),array("guhU!"),4) )
{
    $text = three(array("goh","guh"),array("+"),$ac,array("gUh","gUh"),array("+"),$ac,0);
    echo "<p class = sa >By UdupadhAyA gohaH (".link_sutra("6.4.89").") :</p>\n";
    echo "<p class = sa >ऊदुपधाया गोहः (६.४.८९) :</p>\n";
    display(0);
}
/* vrazcabhrasjamRjayajarAjabhrAjacChazAM ca (8.2.35) */
// TubhrAjR dIptau and ejR bhejR bhrAjR dIptau are different. This is pending to code.
// parau vrajeH SaH padAnte (u 217) pending. 
$vras=0;
$vrasca = array("vfSc","sfj","mfj","yaj","rAj","BrAj","devej","parivrAj","Bfj","ftvij","mArj","vraSc","Brasj","sraj","sfaj","Barj","vrASc","vrAc","vrac","BrAj","BArj");
$vrashca = array("vfSz","sfz","mfz","yaz","rAz","BrAz","devez","parivrAz","Bfz","ftviz","mArz","vraSz","Brasz","sraz","sfaz","Barz","vrAz","vrAz","vraz","BrAz","BArz");
if ( sub($vrasca,array("+"),prat("Jl"),0)  && in_array($so,$tiG) && ends(array($fo),array("o!vraScU!","sfja!","mfja!","yaja!","rAjf!","wuBrAjf!","Bfja!","mfjU!","Brasja!"),4))
{
    if (sub($vrasca,prat('Jl'),blank(0),0))
    {
    $text = two($vrasca,prat('Jl'),$vrashca,prat("Jl"),0);
    }
    else 
    {
    $text = one($vrasca,$vrashca,0);    
    }
    echo "<p class = sa >By vrazcabhrasjasRjamRjayajarAjabhrAjacChazAM SaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >व्रश्चभ्रस्जसृजमृजयजराजभ्राजच्छशां षः (८.२.३५) :</p>\n";
    display(0); 
    $vras = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
}
if (arr($text,'/[CS][+]/') && in_array($so,$tiG) )
{
    $text = three(array("C","S"),array("+"),prat('Jl'),array("z","z"),array("+"),prat('Jl'),0);
    echo "<p class = sa >By vrazcabhrasjasRjamRjayajarAjabhrAjacChazAM SaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >व्रश्चभ्रस्जसृजमृजयजराजभ्राजच्छशां षः (८.२.३५) :</p>\n";
    display(0);
    $vras=1;
}
/* nimittApAye naimittikasyApyapAyaH (paribhASA) */ 
if (($vras===1 && sub(array("vfSz","vraSz"),blank(0),blank(0),0))  || sub(array("cz"),blank(0),blank(0),0))
{
    $text = one(array("vfSz","vraSz"),array("vfsz","vrasz"),0);
    $text = one(array("cz"),array("z"),0);
    echo "<p class = sa >By nimittApAye naimittikasyApyapAyaH (paribhASA) :</p>\n";
    echo "<p class = sa >निमित्तापाये नैमित्तिकस्याप्यपायः (परिभाषा) :</p>\n";
    display(0);
}
/* vivikz patch for overcoming skoH saMyogAdyorante ca */
if (sub(array("vivikz",),array("+"),blank(0),0) && in_array($so,$tiG))
{
    $text = two(array("vivikz",),array("+"),array("viviS"),array("+"),0);
    echo "<p class = sa >As katva is asiddha to skoH saMyogAdyorante ca, saMyogAntalopa happens. 'S' is changed to 's' by nimittApAye naimittikasyApyapAyaH. :</p>\n";
    echo "<p class = sa >स्कोः संयोगाद्योरन्ते च इति कलोपे प्राप्ते कत्वस्य असिद्धत्वात्‌ संयोगान्तलोपः । सकारस्य लोपे 'निमित्ताभावे नैमित्तिकस्याप्यपायः' इति षत्वमपि निवर्तते । :</p>\n";
    display(0);    
    $text = two(array("viviS"),array("+"),array("viviz"),array("+"),0);
    echo "<p class = sa >By vrazcabhrasjasRjamRjayajarAjabhrAjacChazAM SaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >व्रश्चभ्रस्जसृजमृजयजराजभ्राजच्छशां षः (८.२.३५) :</p>\n";
    display(0);    
}
/* rakS, takS patch to bar application of skoH saMyogAdyorante ca */
if (sub(array("takz","rakz"),array("+"),blank(0),0) && in_array($so,$tiG) && ends(array($sanAdi,array("Ric","RiN"))))
{
    echo "<p class = pa >skoH saMyogAdyorante ca doesn't apply here because of sthAnivadbhAva of Nilopa.</p>\n";
    echo "<p class = hn >'pUrvatrAsiddhe na sthAnivat' (vA 433) doesn't apply here, because it is overruled by 'tasya doSaH saMyogAdilopalatvaNatveSu (vA 440).</p>\n";
    echo "<p class = pa >तक्षिरक्षिभ्यां ण्यन्ताभ्यां क्विपि तु 'स्कोः..' इति न प्रवर्तते । णिलोपस्य स्थानिवद्भावात्‌ ।</p>\n";
    echo "<p class = hn >'पूर्वत्रासिद्धे न स्थानिवत्‌' (वा ४४३) इह नास्ति । 'तस्य दोषः संयोगादिलोपलत्वणत्वेषु (वा ४४०) इति निषेधात्‌ ।</p>\n";
    display(0); 
    $rakS=1; // 0 - doesn't prevent skoH saMyogAdyorante ca. 1 - prevents skoH saMyogAdyorante ca.
} else { $rakS=0; }
/* pipak, vivak, didhak patch to bar application of skoH saMyogAdyorante ca */
//if (sub(array("vivakz","diDakz","pipakz"),array("+"),blank(0),0) && $pada === "pada" && $san===1) // removed for checking whether $san makes any difference.
if (sub(array("vivakz","diDakz","pipakz"),array("+"),blank(0),0) && in_array($so,$tiG))
{
    echo "<p class = pa >skoH saMyogAdyorante ca doesn't apply here because kutva is asiddha to it.</p>\n";
    echo "<p class = pa >'स्कोः...' इति कलोपं प्रति कुत्वस्य असिद्धत्वात्‌ संयोगान्तलोपः ।</p>\n";
    display(0); 
    $pipakS=1; // 0 - doesn't prevent skoH saMyogAdyorante ca. 1 - prevents skoH saMyogAdyorante ca.
} else { $pipakS=0; }
if ( (pr2(array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("t","s"),array("rod","svap","Svas","prAR","an","jakz","rud"),array("+"),array("at","as"),$text)!==$text || ends(array($fo),array("jakza!"),4) ) && in_array($so,$tiG) && ends(array($fo),array("rudi!r","Yizvapa!","zvapa!","Svasa!","prARa!","ana!","jakza!"),4) )
{
    $rudAdibhyaH = 1;
}
/* skoH saMyogAdyorante ca (8.2.29) */
if ((sub(array("s","k"),$hlplus,prat("Jl"),0) || arr($text,'/[sk]['.flat($hl).'][+]$/')) && $rakS===0 && $pipakS===0 && $rudAdibhyaH!==1)
{
    $text = three(array("s","k"),$hlplus,prat("Jl"),array("",""),$hlplus,prat("Jl"),0);
    //$text = three($ac,array("s","k"),$hlplus,$ac,array("",""),$hlplus,0);
    echo "<p class = sa >By skoH saMyogAdyorante ca (".link_sutra("8.2.29").") :</p>\n";
    echo "<p class = sa >स्कोः संयोगाद्योरन्ते च (८.२.२९) :</p>\n";
    display(0);
}
/* saH syArdhadhAtuke (7.4.49) */
if (sub(array("s",),array("+"),array("s"),0) && !sub(array("yAs",),array("+"),array("s"),0) && $ardhadhatuka===1)
{
    $text = three(array("s",),array("+"),array("s"),array("t",),array("+"),array("s"),0);
    echo "<p class = sa >By saH syArdhadhAtuke (".link_sutra("7.4.49").") :</p>\n";
    echo "<p class = sa >सः स्यार्धधातुके (७.४.४९) :</p>\n";
    display(0);
}
/* jhalo jhali (8.2.26) */
if ( sub(prat("Jl"),array("s"),prat("Jl"),0) && in_array($so,$tiG))
{
$text = three(prat("Jl"),array("s"),prat("Jl"),prat("Jl"),array(""),prat("Jl"),0); 
echo "<p class = sa >By jhalo jhali (".link_sutra("8.2.26").") :</p>\n";
echo "<p class = sa >झलो झलि (८.२.२६) :</p>\n";
display(0);
}
/* patch for cikIrz */
if (sub(array("cikIrz"),array("+"),blank(0),0) && in_array($so,$tiG) )
{
    $text = two(array("cikIrz"),array("+"),array("cikIr"),array("+"),0);
    echo "<p class = sa >By rAtsasya (".link_sutra("8.2.24").") :</p>\n"; 
    echo "<p class = sa >रात्सस्य (८.२.२४) :</p>\n";
    display(0); 
}
/* vA druhamuhaSNuhaSNihAm (8.2.34) */
$druh = array("druh","muh","snuh","snih","droh","moh","snoh","sneh");
if (sub($druh,blank(0),blank(0),0) && (arr($text,'/[+]$/') || arr($text,'/[+]['.pc("Jl").']/')) )
{ 
    $text = two(array("druh","muh","snuh","snih","droh","moh","snoh","sneh"),prat('Jl'),array("druG","muG","snuG","sniG","droG","moG","snoG","sneG"),prat('Jl'),1);
    echo "<p class = sa >By vA druhamuhaSNuhaSNihAm (".link_sutra("8.2.34").") :</p>\n";
    echo "<p class = sa >वा द्रुहमुहष्णुहष्णिहाम्‌ (८.२.३४) :</p>\n"; 
    display(0);
}
/* dAderdhAtorghaH (8.2.33) */
if (sub($dade,blank(0),blank(0),0) && (arr($text,'/[+]$/') || arr($text,'/[+]['.pc("Jl").']/')) )
{
    $text = two(array("dah","dAh","dih","duh","dfh","drAh","druh"),prat('Jl'),array("daG","dAG","diG","duG","dfG","drAG","druG"),prat('Jl'),0);
    echo "<p class = sa >By dAderdhAtorghaH (".link_sutra("8.2.33").") :</p>\n";
    echo "<p class = sa >दादेर्धातोर्घः (८.२.३३) :</p>\n";
    display(0); 
    $hodha1 = 1; // 0 - doesn't prevent ho DhaH. 1 - prevents ho DhaH.
}
/* naho dhaH (8.2.35) */
if (sub(array("nah","nAh"),blank(0),blank(0),0) && ends(array($fo),array("Raha!"),2) && (arr($text,'/[+]$/') || arr($text,'/[+]['.pc("Jl").']/')) )
{
    $text = one(array("nah","nAh"),array("naD","nAD"),0);
    echo "<p class = sa >By naho dhaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >नहो धः (८.२.३५) :</p>\n";
    display(0); 
    $hodha2 = 1; // 0 - doesn't prevent ho DhaH. 1 - prevents ho DhaH.  
} else { $hodha2 = 0; } 
/* AhasthaH (8.2.36) */
if (in_array($first,array("Ah")) && (arr($text,'/[+]['.pc("Jl").']/')) )
{
    $text = one(array("Ah",),array("AT"),0);
    echo "<p class = sa >By AhasthaH (".link_sutra("8.2.36").") :</p>\n";
    echo "<p class = sa >आहस्थः (८.२.३६) :</p>\n";
    display(0); 
    $hodha3=1; // 0 - doesn't prevent ho DhaH. 1 - prevents ho DhaH.
} else { $hodha3 = 0; } 
/* ho DhaH (8.2.32) */ 
if (arr($text,'/[h][+]/') && sub(array("h"),prat("Jl"),blank(0),0) && $hodha1===0 && $hodha2 === 0 && $hodha3 === 0 )
{
    $text = two(array("h"),prat('Jl'),array("Q"),prat('Jl'),0);
    echo "<p class = sa >ho DhaH (".link_sutra("8.2.32").")  :</p>\n";
    echo "<p class = sa >हो ढः (८.२.३२)  :</p>\n";
    display(0);    
} 
if (arr($text,'/[h][+]$/') && $pada ==="pada" && $hodha1===0 && $hodha2 === 0 && $hodha3 === 0 )
{ 
    $text = two(array("h"),array("+"),array("Q"),array("+"),0);
    echo "<p class = sa >ho DhaH (".link_sutra("8.2.32").") :</p>\n";
    echo "<p class = sa >हो ढः (८.२.३२) :</p>\n";
    display(0);    
}
if (arr($text,'/[h]$/')  && $hodha1===0 && $hodha2 === 0 && $hodha3 === 0 )
{
    $text = last(array("h"),array("Q"),0);
    echo "<p class = sa >ho DhaH (".link_sutra("8.2.32").")  :</p>\n";
    echo "<p class = sa >हो ढः (८.२.३२)  :</p>\n";
    display(0);    
}
/* ekAco bazo bhaS jhaSantasya sdhvoH (8.2.37) */  
if ( /*anekAca($verb_without_anubandha)==false &&*/ ( arr($text,'/[bgqd](['.pc('al').']*)[JBGQD][+][sD]/') || arr($text,'/[JBGQD][+]$/') || $pada==="pada") )
{
	ekAcobazo(); // created a new function 19/12/2014.
}

/* SaDhoH kassi (8.2.41) */
if (sub(array("z","Q"),array("s"),blank(0),0) && !in_array("Sapluk",$vik))
{
    $text = two(array("z","Q"),array("s"),array("k","k"),array("s"),0);
    echo "<p class = sa >By SaDhoH kassi (".link_sutra("8.2.41").") :</p>\n";
    echo "<p class = sa >षढोः कस्सि (८.२.४१) :</p>\n";
    display(0);    
}
/* coH kuH (8.2.30) */
if ((arr($text,'/['.flat($cu).'][+]['.pc('Jl').']/')) && !in_array($fo,$noco) && in_array($so,$tiG) && ($syatAsI!==1 || $id_dhAtu !== "sew" )) // need to test for veT dhAtus. Pending.
{
$text = three($cu,array("+"),prat('Jl'),$ku,array("+"),prat('Jl'),0); 
echo "<p class = sa >By coH kuH (".link_sutra("8.2.30").") :</p>\n";
echo "<p class = sa >चोः कुः (८.२.३०) :</p>\n";
display(0); $coku=1; // 0 - doesn't prevent kvinpratyayasya kuH. 1 - prevents kvinpratyayasya kuH.
}
elseif (!in_array($so,$noco) && arr($text,'/['.flat($cu).'][+]$/') && in_array($so,$tiG) && $syatAsI!==1)
{
    $text = two($cu,array("+"),$ku,array("+"),0);
    echo "<p class = sa >By coH kuH (".link_sutra("8.2.30").") :</p>\n";
    echo "<p class = sa >चोः कुः (८.२.३०) :</p>\n";
    display(0);   $coku=1;
}
/* patch for nimittApAye naimittikasyApAyaH in coH kuH */
if ($coku === 1 && sub(array("Y"),$ku,blank(0),0))
{
	$text = two(array("Y"),$ku,array("n"),$ku,0);
    echo "<p class = sa >By nimittApAye naimittikasyApyapAyaH (pa) :</p>\n";
    echo "<p class = sa >निमित्तापाये नैमित्तिकस्याप्यपायः (प) :</p>\n";
    display(0);
}
/* vRRto vA (7.2.38) */ 
if ( (ends(array($fo),array("vfN","vfY"),4) || ends(array($verb_without_anubandha),array("F",),1)) && sub(array("ar"),array("+"),blank(0),0) && in_array($lakAra,$ArdhadhAtuka_lakAra) && in_array("iw",$Agama))
{
	$text=two(array("ar",),array("+i"),array("ar"),array("+I"),1);
	echo "<p class = sa >By vRRto vA (".link_sutra("7.2.38").") :</p>\n"; 
	echo "<p class = sa >वॄतो वा (७.२.३८) :</p>\n";
	display(0);    
}
/* RRta iddhAtoH (7.1.100) */
if ( sub(array("F"),array("+"),array("sI"),0) && ($sarvadhatuka===1 || $ardhadhatuka===1) )
{
    $text=three(array("F"),array("+"),array("sI"),array("ir"),array("+"),array("sI"),0);
    echo "<p class = sa >By RRta iddhAtoH (".link_sutra("7.1.100").") :</p>\n"; 
    echo "<p class = sa >ॠत इद्धातोः (७.१.१००) :</p>\n";
    display(0);    
	/* hali ca (8.2.77) */
	if ( sub(array("ir+"),array("sI"),blank(0),0) && in_array($so,$tiG) )
	{
		$text=two(array("ir+"),array("sI"),array("Ir+"),array("sI"),0);
		echo "<p class = sa >By hali ca (".link_sutra("8.2.77").") 2:</p>\n"; 
		echo "<p class = sa >हलि च (८.२.७७) :</p>\n";
		display(0);    
	}
}
/* vibhASorNoH (1.2.3) */ 
if ( ends(array($fo),array("UrRuY"),4) && sub(array("UrRu","orRu","UrRo","orRo"),array("+"),blank(0),0) && in_array($lakAra,$ArdhadhAtuka_lakAra) && $id_dhAtu==="sew" && $id_pratyaya==="sew")
{
	$text=two(array("UrRu","orRu","UrRo","orRo"),array("+i"),array("UrRo","orRo","UrRu","orRu"),array("+i"),1);
        $it=array_merge($it,array("N"));
        $itpratyaya=array_merge($itpratyaya,array("N"));
	echo "<p class = sa >By vibhASorNoH (".link_sutra("1.2.3").") :</p>\n"; 
	echo "<p class = sa >विभाषोर्णोः (१.२.३) :</p>\n";
	display(0);    
}    
/* jhaSastathordho'dhaH (8.2.40) */
if ( sub(prat('Jz'),array("+"),array("t","T"),0) && !sub(array("daD"),array("+"),array("t","T"),0) && in_array($so,$tiG))
{
    $text = three(prat('Jz'),array("+"),array("t","T"),prat('Jz'),array("+"),array("D","D"),0);
    echo "<p class = sa >By jhaSastathordho'dhaH (".link_sutra("8.2.40").") :</p>\n";
    echo "<p class = sa >झषस्तथोर्धोऽधः (८.२.४०) :</p>\n";  
    display(0);    
}
/* iNo yaN (6.4.81) */ 
if ( $fo==="iR" && sub(array("i+i"),array("+"),$ac,0) )
{ 
    $text = three(array("i+i"),array("+"),$ac,array("i+y"),array("+"),$ac,0);
    echo "<p class = sa >By iNo yaN (".link_sutra("6.4.81").") :</p>\n";
    echo "<p class = sa >इणो यण्‌(६.४.८१) :</p>\n";
    display(0);
}
/* dIrgha iNaH kiti (6.4.79) */ 
if ( $fo==="iR" && sub(array("i+y"),array("+"),$ac,0) )
{ 
    $text = three(array("i+y"),array("+"),$ac,array("I+y"),array("+"),$ac,0);
    echo "<p class = sa >By dIrgha iNaH kiti (".link_sutra("6.4.79").") :</p>\n";
    echo "<p class = sa >दीर्घ इणः किति (६.४.७९) :</p>\n";
    display(0);
}
/* abhyAsasyAsavarNe (6.4.78) */ 
if ( arr($text,'/^[iIuU][+][eoEO]/') && $abhyAsa===1)
{
    $text=change('/^([iI])([+][eoEO])/','$1y$2');
    $text=change('/^([uU])([+][eoEO])/','$1v$2');
    echo "<p class = sa >By abhyAsasyAsavarNe (".link_sutra("6.4.78").") :</p>\n";
    echo "<p class = sa >अभ्यासस्यासवर्णे(६.४.७८) :</p>\n";
    display(0);
}
/* NeraniTi (6.4.51) */
if (sub(array("i"),array("+"),array("a"),0) && in_array($so,$tiG) && $lakAra==="luN" && $caG===1 && (in_array("iw",$Agama)||ends(array($sanAdi),array("Ric","RiN"),2)) )
{
	$text = three(array("i"),array("+"),array("a"),array(""),array("+"),array("a"),0);
    echo "<p class = sa >By NeraniTi (".link_sutra("6.4.51").") :</p>\n"; 
    echo "<p class = sa >णेरनिटि (६.१.५१) :</p>\n";
    display(0);    
}
/* NeraniTi (6.4.51) */
elseif (sub(array("+"),array("i"),array("+"),0) && in_array($so,$tiG) && $ardhadhatuka===1 && in_array("iw",$Agama))
{
	$text = one(array("+i+"),array("+"),0);
    echo "<p class = sa >By NeraniTi (".link_sutra("6.4.51").") :</p>\n"; 
    echo "<p class = sa >णेरनिटि (६.१.५१) :</p>\n";
    display(0);    
}
/* intermediate sandhis for vikaraNa pratyayas */
if ($lakAra!=="")
{
    $dhatu=1;
    /* iNo yaN (6.4.81) */
    if (sub(array("i"),array("+a"),blank(0),0) && $pada === "pratyaya" && in_array($so,$tiG) && in_array($fo,array("iR","ik")) )
    {
        $text = two(array("i"),array("+a"),array("y"),array("+a"),0);
        echo "<p class = sa >By iNo yaN (".link_sutra("6.4.81").") :</p>\n";
        echo "<p class = sa >इणो यण्‌ (६.४.८१) :</p>\n";  
        display(3);
    }
    /* eco'yavAyAvaH (7.1.78) */
    $ayavayavah = array("ay","av","Ay","Av");
    if (sub(prat('ec'),array("+i+","+a+","+Aya+","+Ana+","+e+"),blank(0),0))
    {
    $text = two(prat('ec'),array("+i+","+a+","+Aya+","+Ana+","+e+"),$ayavayavah,array("i+","a+","Aya+","Ana+","e+"),0);
    echo "<p class = sa >By echo'yavAyAvaH (".link_sutra("7.1.78").") :</p>\n";
    echo "<p class = sa >एचोऽयवायावः (७.१.७८) 2:</p>\n";
    display(0);
    }
    /* dRnkarapunaHpUrvasya bhuvo yaN vaktavyaH (vA 4118) */ 
    if (in_array($fo,array("dfnBU","karaBU","kAraBU","punarBU")) && sub(array($fo),array("+"),$ac,0) )
    {
        $text = three(array("dfnBU","punarBU"),array("+"),$ac,array("dfnBv","punarBv"),array("+"),$ac,0);
        $text = three(array("karaBU","kAraBU"),array("+"),$ac,array("karaBv","kAraBv",),array("+"),$ac,1);
        echo "<p class = sa >By dRnkarapunaHpUrvasya bhuvo yaN vaktavyaH (vA 4118) :</p>\n";
        if (in_array($fo,array("karaBU","kAraBU")))
        {
        $text=one(array("karaBv+e","karaBU+A+e","karaBv+as","karaBU+A+as","karaBv+i","karaBU+Am","kAraBv+e","kAraBU+A+e","kAraBv+as","kAraBU+A+as","kAraBv+i","kAraBU+Am"),array("karaBU+e","karaBv+A+e","karaBU+as","karaBv+A+as","karaBU+i","karaBv+Am","kAraBU+e","kAraBv+A+e","kAraBU+as","kAraBv+A+as","kAraBU+i","kAraBv+Am",),0);
        echo "<p class = hn >There is pAThabheda here. Some hold that there is dIrgha kAra word here. SK has adopted both the readings, therefore we have kept them optional.</p>\n";        
        }
        echo "<p class = sa >दृन्करपुनःपूर्वस्य भुवो यण्‌ वक्तव्यः (वा ४११८) :</p>\n";
        if (in_array($fo,array("karaBU","kAraBU")))
        {
        echo "<p class = hn >दीर्घपाठे करपूर्वस्य उवङेव । ह्रस्वपाठे करपूर्वस्य यणेव इति विवेकः ।</p>\n";                
        }
        display(0); 
    }   
    /* varSAbhvazca (6.8.84) */ 
    if ($first==="varzABU"  && sub(array($fo),array("+"),$ac,0) )
    {
        $text = two(array("varzABU"),array("+"),array("varzABv"),array("+"),0);
        echo "<p class = sa >By varSAbhvazca (".link_sutra("6.8.84").") :</p>\n";
        echo "<p class = sa >वर्षाभ्वश्च (६.४.८४) :</p>\n";
        display(0);
    }

    if ($fo==="dfmBU")
    {
        $dfmBU=1; // 0 - the word is not dRmbhU. 1 - the word is dRmbhU.
    } else { $dfmBU=0; }
    /* na bhUsudhiyoH (6.4.85) */
    if (in_array($fo,array("suDI","praDI")) && sub(array($fo),array("+"),$ac,0) )
    {
        $dhatu=1;
    }
    if (in_array($fo,array("praDI"))  && sub(array($fo),array("+"),$ac,0) )
    {
       $eranekaca=1;
    }
    /* kvau luptaM na sthAnivat (vA 431) */
    // Not displayed because it is difficult to teach sthnanivadbhav to machine now. Will come back to it if I can teach it some day.
    /* aci znudhAtubhruvAM yvoriyaGuvaGau (6.4.77) */
    if (sub(array("kur+u"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,0) )
    {
        $text=three(array("kur+u"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,array("kurv"),array("+"),$ajAdi_apit_sArvadhAtuka_pratyayas,0);
        echo "<p class = sa >By iko yaNaci (".link_sutra("6.1.77").") :</p>\n";
        echo "<p class = sa >इको यणचि (६.१.७७) :</p>\n";
        display(0);        
    }
    // special patch for IDAgama of turustu... to make it amenable to aci znudhAtu...
    $text=one(array("+I+"),array("+I"),0);
    if (($dhatu===1||$fo==="BrU") && arr($text,'/[iuIU][+]['.flat($ac).']/') && $pada==="pratyaya" && ( anekAca($fo)===false  || in_array($so,$tiG) || (arr($text,'/[B][U][+]/')||$fo==="suDI") ) && $dfmBU===0 && $didhI!==1 && $nabhusu!==1 && !sub(array("+"),array("I"),array("+"),0) && ($abhyasta!==1 || in_array($fo,array("hrI"))))
        {
        $text = three(array("i","I","u","U"),array("+"),$ac,array("iy","iy","uv","uv"),array("+"),$ac,0);
		$text = one(array("uv+uv+"),array("u+uv+"),0);
        echo "<p class = sa >By aci znudhAtubhruvAM yvoriyaGuvaGau (".link_sutra("6.4.77").") :</p>\n";
        echo "<p class = hn >gatikAraketarapUrvapadasya yaN neSyate (vA 5034) mandates that eranekAco.../oH supi rule doesn't apply in cases where the pUrvapada is neither gati nor kAraka. iyaG or uvaG is applied in that case. :</p>\n";
        echo "<p class = sa >अचि श्नुधातुभ्रुवां य्वोरियङुवङौ (६.४.७७) 1:</p>\n";
        echo "<p class = hn >गतिकारकेतरपूर्वपदस्य यण्‌ नेष्यते (वा ५०३४) से गति / कारक से भिन्न पूर्वपद होने पर एरनेकाचो.../ओः सुपि सूत्र नहीं लागू होता । इयङ्‌ या उवङ्‌ की प्रवृत्ति होती है । :</p>\n";
        display(3);
    }
    if ( sub(array("+"),array("nu+"),$ac,0)  )
    {
        $text = three(array("+"),array("nu+"),$ac,array("+"),array("nuv+"),$ac,0);
        echo "<p class = sa >By aci znudhAtubhruvAM yvoriyaGuvaGau (".link_sutra("6.4.77").") :</p>\n";
        echo "<p class = sa >अचि श्नुधातुभ्रुवां य्वोरियङुवङौ (६.४.७७) :</p>\n";
        display(0);
    }
    /* ATazca (6.1.90) */
    if (sub(array("A"),$ac,blank(0),0) && in_array("Aw",$Agama) )
    {
        $text = two(array("A"),$ac,array(""),vriddhi($ac),0);
        echo "<p class = sa >By ATazca (".link_sutra("6.1.90").") :</p>\n";
        echo "<p class = sa >आटश्च (६.१.९०) :</p>\n";
        display(0);
    }
    /* gatikAraketarapUrvapadasya yaN neSyate (vA 5034) */
    // This is attached with eranekAco... So, trying to put a note and making the iyaG and yaN optional.
    /* eranekAco'saMyogapUrvasya (6.4.82) */
    if ($dhatu===1 && arr($text,'/[iI][+]['.pc('ac').']/') && in_array($fo,array("unnI")) && $pada==="pratyaya" && anekAca($fo) && $nabhusu===0)
    {
        echo "<p class = pa >As the vizeSaNa 'dhAtunA saMyogasya' mandates that the saMyoga has to belong to dhAtu only for prohibiting 'eranekAco..', the prohibition doesn't apply here.</p>\n";
        echo "<p class = pa >धातुना संयोगस्य विशेषणादिह स्यादेव यण्‌ (एरनेकाचो इत्यनेन सूत्रेण) </p>\n";
        display(0); 
        $unni=1; // 0 - the word is not unnI. 1 - the word is unnI
    } else { $unni=0; } 
    if ($dhatu===1 && (arr($text,'/['.flat($ac).']['.flat($hl).'][iI][+]['.flat($ac).']/')||$unni===1) && $pada==="pratyaya" && (anekAca($fo) || $abhyasta===1 )&& $nabhusu===0 )
    {
        $text = three(array("i","I"),array("+"),$ac,array("y","y"),array("+"),$ac,0);                
        echo "<p class = sa >By eranekAco'saMyogapUrvasya (".link_sutra("6.4.82").") :</p>\n";
        echo "<p class = hn >gatikAraketarapUrvapadasya yaN neSyate (vA 5034) mandates that this rule doesn't apply in cases where the pUrvapada is neither gati nor kAraka. iyaG or uvaG is applied in that case. :</p>\n";
        echo "<p class = sa >एरनेकाचोऽसंयोगपूर्वस्य (६.४.८२) :</p>\n";
        echo "<p class = hn >गतिकारकेतरपूर्वपदस्य यण्‌ नेष्यते (वा ५०३४) से गति / कारक से भिन्न पूर्वपद होने पर यह सूत्र नहीं लागू होता । इयङ्‌ या उवङ्‌ की प्रवृत्ति होती है । :</p>\n";
        display(3);
    }
    /* oH supi (6.4.83) */ 
    if ($dhatu===1 && in_array($fo,array("ullU")) && $pada==="pratyaya" && anekAca($fo) && $nabhusu===0 && $dfmBU===0)
    {
        echo "<p class = pa >As the vizeSaNa 'dhAtunA saMyogasya' mandates that the saMyoga has to belong to dhAtu only for prohibiting 'oH supi', the prohibition doesn't apply here.</p>\n";
        echo "<p class = pa >धातुना संयोगस्य विशेषणादिह स्यादेव यण्‌ (ओः सुपि इत्यनेन सूत्रेण) </p>\n";
        display(0); 
        $ullU=1; // 0 - word is not ullU. 1 - word is ullU.
    } else { $ullU=0; }
    if ($dhatu===1 && ( arr($text,'/[uU][+]['.flat($ac).']/') || $ullU===1 )&& !sub(array("+"),array("u+"),blank(0),0) && $pada==="pratyaya" && anekAca($fo) && $nabhusu===0 && $dfmBU===0)
    {
        $text = three(array("u","U"),array("+"),$ac,array("v","v"),array("+"),$ac,0);
        echo "<p class = sa >By oH supi (".link_sutra("6.4.83").") :</p>\n";
        echo "<p class = hn >gatikAraketarapUrvapadasya yaN neSyate (vA 5034) mandates that this rule doesn't apply in cases where the pUrvapada is neither gati nor kAraka. iyaG or uvaG is applied in that case. :</p>\n";
        echo "<p class = sa >ओः सुपि (६.४.८३) :</p>\n";
        echo "<p class = hn >गतिकारकेतरपूर्वपदस्य यण्‌ नेष्यते (वा ५०३४) से गति / कारक से भिन्न पूर्वपद होने पर यह सूत्र नहीं लागू होता । इयङ्‌ या उवङ्‌ की प्रवृत्ति होती है । :</p>\n";
        display(3);
    }    
}

/* Adding aDAgama and other pratyaya works which are done after aGgakArya */
/* Candasyapi dRzyate */
// Pending. Page 249 of Sahajabodha part 1. Vedic in nature.
/* bahulaM CandasyamAGyoge'pi */
// Pending. Page 249 of Sahajabodha part 1. Vedic in nature.
/* utazca pratyayAdasaMyogapUrvAt (6.4.106) */
if ( sub($ac,array("+"),array("nu+hi"),0) && in_array($so,$tiG) )
{
    $text=three($ac,array("+"),array("nu+hi"),$ac,array("+"),array("nu"),0);
    echo "<p class = sa >By utazca pratyayAdasaMyogapUrvAt (".link_sutra("6.4.106").") :</p>\n"; 
    echo "<p class = sa >उतश्च प्रत्ययादसंयोगपूर्वात्‌ (६.४.१०६) :</p>\n";
    display(0);    
}
if ( sub($ac,$hl,array("+u+hi"),0) && in_array($so,$tiG) )
{
    $text=three($ac,$hl,array("+u+hi"),$ac,$hl,array("+u"),0);
    echo "<p class = sa >By utazca pratyayAdasaMyogapUrvAt (".link_sutra("6.4.106").") :</p>\n"; 
    echo "<p class = sa >उतश्च प्रत्ययादसंयोगपूर्वात्‌ (६.४.१०६) :</p>\n";
    display(0);    
}
/* laGaH zAkaTAyanasyaiva (3.4.111) */
if ( sub(array("A"),array("+"),array("an"),0) && in_array($so,array("Ji")) && $lakAra==="laN")
{
    $text=three(array("A"),array("+"),array("an"),array("A"),array("+"),array("us"),1);
    echo "<p class = sa >By laGaH zAkaTAyanasyaiva (".link_sutra("3.4.111").") :</p>\n"; 
    echo "<p class = sa >लङः शाकटायनस्यैव (३.४.१११) :</p>\n";
    display(0);
}
/* usyapadAntasya (6.1.96) */
if ( sub(array("a","A"),array("+"),array("us"),0))
{
    $text=three(array("a","A"),array("+"),array("us"),array("",""),array("+"),array("us"),0);
	$text=one(array("+s+us+","+us"),array("+sus","us"),0);
    echo "<p class = sa >usyapadAntasya (".link_sutra("6.1.96").") :</p>\n"; 
    echo "<p class = sa >उस्यपदान्तस्य (६.१.९६) :</p>\n";
    display(0);    
}
/* lopazcAsyAnyatarasyAM mvoH (6.4.107) */
// u pratyaya pending.
if ( sub($ac,array("+nu+"),array("vas","mas","vahe","mahe","va","ma","vahi","mahi",),0) && in_array($so,$tiG) )
{
    $text=three($ac,array("+nu+"),array("vas","mas","vahe","mahe","va","ma","vahi","mahi",),$ac,array("+n+"),array("vas","mas","vahe","mahe","va","ma","vahi","mahi",),1);
    echo "<p class = sa >By lopazcAsyAnyatarasyAM mvoH (".link_sutra("6.4.107").") :</p>\n"; 
    echo "<p class = sa >लोपश्चास्यान्यतरस्यां म्वोः (६.४.१०७) :</p>\n";
    display(0);    
}
if ( sub($hl,array("+u+"),array("vas","mas","vahe","mahe","va","ma","vahi","mahi",),0) && !sub($hl,$hl,array("+u+"),0) && in_array($so,$tiG) )
{
    $text=three($hl,array("+u+"),array("vas","mas","vahe","mahe","va","ma","vahi","mahi",),$hl,array("+"),array("vas","mas","vahe","mahe","va","ma","vahi","mahi",),1);
    echo "<p class = sa >By lopazcAsyAnyatarasyAM mvoH (".link_sutra("6.4.107").") :</p>\n"; 
    echo "<p class = sa >लोपश्चास्यान्यतरस्यां म्वोः (६.४.१०७) :</p>\n";
    display(0);    
}

/* finally adding halanta+a into single aGga */
$text=two($hl,array("+a+"),$hl,array("a+"),0);


/* halaH znaH zAnajJau (3.1.83) */
if ( sub($hl,array("+"),array("nA+hi"),0) && in_array($so,$tiG) )
{
    $text=three($hl,array("+"),array("nA+hi"),$hl,array("+"),array("Ana+hi"),0);
    echo "<p class = sa >By halaH znaH zAnajJau (".link_sutra("3.1.83").") :</p>\n"; 
    echo "<p class = sa >हलः श्नः शानज्झौ (३.१.८३) :</p>\n";
    display(0);    
}
/* Chandasi zAnajapi (3.1.81) */
if ( sub($hl,array("+"),array("Ana+hi"),0) && in_array($so,$tiG) && $veda===1)
{
    $text=three($hl,array("+"),array("Ana+hi"),$hl,array("+"),array("Aya+hi"),1);
    echo "<p class = sa >By Chandasi zAnajapi (".link_sutra("3.1.81").") :</p>\n"; 
    echo "<p class = sa >छन्दसि शायजपि (३.१.८१) :</p>\n";
    display(0);    
}
/* ato heH (6.4.105) */
if ( sub(array("a+"),array("hi"),blank(0),0) && in_array($so,$tiG) && $hanterjaH!==1 )
{
    $text=two(array("a+"),array("hi"),array("a+"),array(""),0);
    echo "<p class = sa >By ato heH (".link_sutra("6.4.105").") :</p>\n"; 
    echo "<p class = sa >अतो हेः (६.४.१०५) :</p>\n";
    display(0);    
}
/* lopo yi (6.4.118) */
if ( sub(array("jahA"),array("+"),array("y"),0) && (in_array("N",$it)||in_array("k",$it)) && $sarvadhatuka===1 )
{
    $text=three(array("jahA"),array("+"),array("y"),array("jah"),array("+"),array("y"),0);
    echo "<p class = sa >By lopo yi (".link_sutra("6.4.118").") :</p>\n"; 
    echo "<p class = sa >लोपो यि (६.४.११८) :</p>\n";
    display(0);    
}
/* jahAtezca (6.4.116) */
if ( sub(array("jahA"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,0) && (in_array("N",$it)||in_array("k",$it)) && $abhyasta===1)
{
    $text=three(array("jahA"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("jahi"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,1);
    echo "<p class = sa >By jahAtezca (".link_sutra("6.4.116").") :</p>\n"; 
    echo "<p class = sa >जहातेश्च (६.४.११६) :</p>\n";
    display(0);    
}
/* I halyaghoH (6.4.113) */
if ( sub(array("+"),array("nA+"),$halAdi_apit_sArvadhAtuka_pratyayas,0) && (in_array("N",$it)||in_array("N",$it)))
{
    $text=three(array("+"),array("nA+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("+"),array("nI+"),$halAdi_apit_sArvadhAtuka_pratyayas,0);
    echo "<p class = sa >By I halyaghoH (".link_sutra("6.4.113").") :</p>\n"; 
    echo "<p class = sa >ई हल्यघोः (६.४.११३) :</p>\n";
    display(0);    
}
if ( sub(array("A"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,0) && (in_array("N",$it)||in_array("k",$it)) && $abhyasta===1)
{
    $text=three(array("A"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("I"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,0);
    echo "<p class = sa >By I halyaghoH (".link_sutra("6.4.113").") :</p>\n"; 
    echo "<p class = sa >ई हल्यघोः (६.४.११३) :</p>\n";
    display(0);    
}
/* janasanakhanAM saJjhaloH (6.4.42) */
// sannanta pending.
//if ( sub(array("jan","san","Kan"),array("+"),prat("Jl"),0) && pr2(array("jan","san","Kan"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("jaA","saA","KaA"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text)!==$text && (in_array("N",$it)||in_array("k",$it)) && in_array($so,$tiG) ) // kGiti is temporarily deactivated.
if ( sub(array("jan","san","Kan"),array("+"),prat("Jl"),0)  && in_array($so,$tiG) ) 
{
//    $text=pr2(array("jan","san","Kan"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("jaA","saA","KaA"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text);
	$text=three(array("jan","san","Kan"),array("+"),prat("Jl"),array("jaA","saA","KaA"),array("+"),prat("Jl"),0);
    echo "<p class = sa >By janasanakhanAM saJjhaloH (".link_sutra("6.4.42").") :</p>\n"; 
    echo "<p class = sa >जनसनखनां सञ्झलोः (६.४.४२) :</p>\n";
    display(0);    
}
/* ye vibhASA (6.4.43) */
if ( sub(array("jan","san","Kan"),array("+"),array("y"),0) && pr2(array("jan","san","Kan"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("jaA","saA","KaA"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text)!==$text && (in_array("N",$it)||in_array("k",$it)) && in_array($so,$tiG) )
{
    $text1=pr2(array("jan","san","Kan"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("jaA","saA","KaA"),array("+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text);
    $text = array_merge($text,$text1);
    echo "<p class = sa >By ye vibhASA (".link_sutra("6.4.43").") :</p>\n"; 
    echo "<p class = sa >ये विभाषा (६.४.४३) :</p>\n";
    display(0);    
}
/* anunAsikasya kvijhaloH kGiti (6.4.15) */
if ( pr2(array("a","i","u","f","x"),array("N+","Y+","R+","n+","m+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("A","I","U","F","F"),array("N+","Y+","R+","n+","m+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text)!==$text && !in_array($so,$tiG) && (in_array("N",$it)||in_array("k",$it))  && !sub(array("+"),prat("hm"),blank(0),0)) // see https://github.com/drdhaval2785/SanskritVerb/issues/74.
{
    $text = pr2(array("a","i","u","f","x"),array("N+","Y+","R+","n+","m+"),$halAdi_apit_sArvadhAtuka_pratyayas,array("A","I","U","F","F"),array("N+","Y+","R+","n+","m+"),$halAdi_apit_sArvadhAtuka_pratyayas,$text);
    echo "<p class = sa >By anunAsikasya kvijhaloH kGiti (".link_sutra("6.4.15").") :</p>\n";
    echo "<p class = sa >अनुनासिकस्य क्विझलोः क्ङिति (६.४.१५) :</p>\n";  
    display(0);    
}
/* A ca hau (6.4.115) */
if ( sub(array("jahI"),array("+"),array("hi"),0) && (in_array("N",$it)||in_array("k",$it)) && $abhyasta===1)
{
    $text=three(array("jahI"),array("+"),array("hi"),array("jahi"),array("+"),array("hi"),1);
    $text=three(array("jahI"),array("+"),array("hi"),array("jahA"),array("+"),array("hi"),1);
    echo "<p class = sa >By A ca hau (".link_sutra("6.4.115").") :</p>\n"; 
    echo "<p class = sa >आ च हौ (६.४.११५) :</p>\n";
    display(0);    
}
/* Joining aGga and pratyayas */
/* ato dIrgho yaJi (7.3.101) */
if ( pr2(array("a"),array("+mi","+mas","+vas","+ma","+va","+mahi","+vahi","+mahe","+vahe"),blank(0),array("A"),array("+mi","+mas","+vas","+ma","+va","+mahi","+vahi","+mahe","+vahe"),blank(0),$text)!==$text  )
{
    $text = pr2(array("a"),array("+mi","+mas","+vas","+ma","+va","+mahi","+vahi","+mahe","+vahe"),blank(0),array("A"),array("+mi","+mas","+vas","+ma","+va","+mahi","+vahi","+mahe","+vahe"),blank(0),$text);
    echo "<p class = sa >By ato dIrgho yaJi (".link_sutra("7.3.101").") :</p>\n";
    echo "<p class = sa >अतो दीर्घो यञि (७.३.१०१) :</p>\n";  
    display(3);
}
/* ato dIrgho yaJi (7.3.101) */
if (sub(array("+"),array("ava","ama"),blank(0),0) && in_array($so,$tiG) )
{
    $text = two(array("+"),array("ava","ama"),array("+"),array("+Ava","+Ama"),0);
    echo "<p class = sa >By ato dIrgho yaJi (".link_sutra("7.3.101").") :</p>\n";
    echo "<p class = sa >अतो दीर्घो यञि (७.३.१०१) :</p>\n";  
    display(3);
}
/* ato guNe (6.1.17) */
if (sub(array("a"),array("+a","+e","+o"),blank(0),0) && $pada === "pratyaya" && in_array($so,$tiG) )
{
    while(sub(array("a"),array("+a","+e","+o"),blank(0),0) !== false)
    {
             $text = two(array("a"),array("+a","+e","+o"),blank(1),array("+a","+e","+o"),0);   
    }      
    echo "<p class = sa >By ato guNe (".link_sutra("6.1.17").") :</p>\n";
    echo "<p class = sa >अतो गुणे (६.१.१७) :</p>\n";  
    display(0);
}
/* bahulaM Candasi (2.4.73) */
if (sub(array("SAm","ama"),array("+"),blank(0),0) && ends(array($fo),array("Samu!","ama!"),4) && in_array($so,$tiG) && $veda===1)
{
    $text=two(array("SAm+ya","ama"),array("+"),array("Sam","am"),array("+"),1);
    echo "<p class = sa >By bahulaM Candasi (".link_sutra("2.4.73").") :</p>\n";
    echo "<p class = hn >By this, vikaraNa may get elided in Candas.</p>\n";
    echo "<p class = sa >बहुलं छन्दसि (२.४.७३) :</p>\n";  
    echo "<p class = sa >इस सूत्र से छन्दस्‌ में विकरण का लोप हो सकता है ।</p>\n";  
    display(0);
}
/* IDajanordhve ca (7.2.78) */
if((sub(array("Iq"),array("+si","+se","+sva","+Dve","+Dvam"),blank(0),0)) && in_array($so,$tiG))
{
$text = two(array("Iq"),array("+si","+se","+sva","+Dve","+Dvam"),array("Iqi"),array("+si","+se","+sva","+Dve","+Dvam"),0);
echo "<p class = sa >By IDajanordhve ca (".link_sutra("7.2.78").") :</p>\n";
echo "<p class = sa >ईडजनोर्ध्वे च (७.२.७८) :</p>\n";
display(0);
}
/* dhi ca (8.2.25) */
// only Dv is placed to make it specific to pratyayas. Others may be added. Pending.
if ( $lakAra!=="" && sub(array("s"),array("Dv"),blank(0),0) && in_array($so,$tiG))
{
    $text = two(array("s"),array("Dv"),array(""),array("Dv"),0);
    echo "<p class = sa >By dhi ca (".link_sutra("8.2.25").") :</p>\n"; 
    echo "<p class = sa >धि च (८.२.२५) :</p>\n";
    display(0);     
}
/* hrasvAdaGgAt (8.2.27) */
if ( sub(array("a","i","u","f","x"),array("+s"),prat("Jl"),0) && in_array($so,$tiG) && $sic===1)
{
$text = three(array("a","i","u","f","x"),array("+s"),prat("Jl"),array("a","i","u","f","x"),array("+"),prat("Jl"),0); 
echo "<p class = sa >By hrasvAdaGgAt (".link_sutra("8.2.27").") :</p>\n";
echo "<p class = sa >ह्रस्वादङ्गात् (८.२.२७) :</p>\n";
display(0);
}
/* AdezapratyayayoH (8.3.59) */
if( in_array($so,$tiG) && !arr($text,'/[+][s]$/') && !sub(array("+"),array("yAs"),array("+"),0) && Adezapratyaya($text)!==$text )
{
    $text = Adezapratyaya($text); // created a new function on 15/12/2014
	echo "<p class = sa >By AdezapratyayayoH (".link_sutra("8.3.59").") :</p>\n";
	echo "<p class = sa >आदेशप्रत्यययोः (८.३.५९) :</p>\n";
	display(0);
}
/* vibhASeTaH (8.3.80) */
if( in_array($so,$tiG) && arr(array($verb_without_anubandha,'/[iIuUfFxXeEoOhyvrl]$/')) && (sub($iN2,array("+izIDv"),blank(0),0) || (sub($iN2,array("+iDv"),blank(0),0) && in_array($lakAra,array("luN","liw")) )) )
{
	$text = two($iN2,array("+izIDv","+iDv"),$iN2,array("+izIQv","+iQv"),1);
	echo "<p class = sa >By vibhASeTaH (".link_sutra("8.3.80").") :</p>\n";
	echo "<p class = sa >विभाषेटः (८.३.८०) :</p>\n";
	display(0);
}
/* iNaH SIdhvaMluGliTAM dho'GgAt (8.3.79) */
elseif( in_array($so,$tiG) && arr(array($verb_without_anubandha,'/[iIuUfFxXeEoOhyvrl]$/')) && (sub($iN2,array("zIDv"),blank(0),0) || (sub($iN2,array("Dv"),blank(0),0) && in_array($lakAra,array("luN","liw")) )) )
{
	$text = two($iN2,array("zIDv","Dv"),$iN2,array("zIQv","Qv"),0);
	echo "<p class = sa >By iNaH SIdhvaMluGliTAM dho'GgAt (".link_sutra("8.3.79").") :</p>\n";
	echo "<p class = sa >इणः षीध्वंलुङ्लिटां धोऽङ्गात्‌ (८.३.७९) :</p>\n";
	display(0);
}
/* halGyAbbhyo dIrghAtsutisyapRktaM hal (6.1.68) and apRkta ekAlpratyayaH (1.2.41) */
// GyAp pending. only hal handled now.
if (arr($text,'/['.pc('hl').'][+][sts]$/') && in_array($so,array("su!","tip","sip",)))
{
    echo "<p class = pa >By apRkta ekAlpratyayaH (".link_sutra("1.2.41").") :</p>\n";
    echo "<p class = pa >अपृक्त एकाल्प्रत्ययः (१.२.४१) :</p>\n";
    display(0);
}
if ((arr($text,'/['.pc('hl').'][+][sts]$/')  )&& in_array($so,array("su!","tip","sip")))
{
    $text = two($hl,array("+s","+t"),$hl,array("+","+"),0);
    echo "<p class = sa >By halGyAbbhyo dIrghAtsutisyapRktaM hal (".link_sutra("6.1.68").") :</p>\n";
    echo "<p class = sa >हल्ङ्‍याब्भ्यो दीर्घात्सुतिस्यपृक्तं हल्‌ (६.१.६८) :</p>\n";
    display(0); 
    $pada="pada"; // there is no pratyaya left now.
    $halGyAbbhyo=1;
}
/* rAtsasya (8.2.24) */
if ((arr($text,('/[r][+][s]$/')) && $pada === "pratyaya") || (arr($text,('/[r][s][+]/')) && $pada === "pada") )
{
    $text = one(array("r+s"),array("r"),0);
    $text = two(array("rs"),array("+"),array("r"),array("+"),0);
    echo "<p class = sa >By rAtsasya (".link_sutra("8.2.24").") :</p>\n"; 
    echo "<p class = sa >रात्सस्य (८.२.२४) :</p>\n";
    display(0); 
}
if ((arr($text,('/[r][+][hyvrlYmGRnJBGQDjbgqdKPCWTcwtkpzS]$/')) && $pada === "pratyaya") || (arr($text,('/[r][hyvrlYmGRnJBGQDjbgqdKPCWTcwtkpzS][+]/')) && $pada === "pada") )
{
    echo "<p class = pa >rAtsasya (".link_sutra("8.2.24").") prevents application of saMyogAntasya lopaH.</p>\n"; 
    echo "<p class = pa >रात्सस्य (८.२.२४) से संयोगान्तस्य लोपः का प्रतिषेध होता है ।</p>\n";
    display(0); 
    $ratsasya=1; // 0 - doesn't prevent saMyogAntasya lopaH. 1 - prevents saMyogAntasya lopaH.
} else { $ratsasya=0; }
/* saMyogAntasya lopaH (8.2.23) */
if ( ( sub(array("N"),$ku,array("+"),0) || sub(array("Y"),$cu,array("+"),0) || sub(array("R"),$Tu,array("+"),0) ||sub(array("m"),$pu,array("+"),0) ) && $ratsasya===0 && $pada==="pada" && in_array($so,$tiG) && !sub(array("+"),array("A"),blank(0),0) ) // patch for nimittApAye naimittikasyApAyaH.
{
    $text = three(array("N"),$ku,array("+"),array("n"),blank(count($ku)),array("+"),0); 
    $text = three(array("Y"),$cu,array("+"),array("n"),blank(count($cu)),array("+"),0); 
    $text = three(array("R"),$Tu,array("+"),array("n"),blank(count($Tu)),array("+"),0); 
    $text = three(array("m"),$pu,array("+"),array("n"),blank(count($pu)),array("+"),0); 
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") and nimittApAye naimittikasyApyapAyaH (pa) :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) तथा निमित्तापाये नैमित्तिकस्याप्यपायः (प) :</p>\n";
    display(0);            
}
/*elseif ( sub($hl,$hl,array("+"),0) && in_array($so,$tiG) && $ratsasya===0 && !arr($text,'/['.pc('hl').']['.pc('hl').'][+]['.pc('ac').']/') && $pada==="pada" )
{
    $text = three($hl,$hl,array("+"),$hl,blank(count($hl)),array("+"),0);
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) :</p>\n";
    display(0);
}*/ // bracketed because pfcC+yAs gave pfcyAs.
elseif (arr($text,'/['.pc('hl').']['.pc('hl').']$/') && in_array($so,$tiG) && $ratsasya===0)
{
    $text = pr2($hl,$hl,blank(0),$hl,blank(count($hl)),blank(0),$text);
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) :</p>\n";
    display(0);
} 
elseif ( sub($hl,array("+"),$hl,0) && arr($text,'/['.pc('hl').'][+]['.pc('hl').']$/') && $ratsasya===0  && in_array($so,$tiG) && $pada==="pada" )
{
    $text = three($hl,array("+"),$hl,$hl,array("+"),blank(count($hl)),0);
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) :</p>\n";
    display(0);
} 
elseif ( (sub(array("M"),array("s"),array("+"),0)  && in_array($so,$tiG)) && $pada==="pada" ) // patch for mAMsa. mAMspacanyA UkhAyAH is pending. ayasmayAdIni etc pending.
{
    $text = three(array("M"),array("s+"),$ku,array("N+"),blank(count($hl)),$ku,0); 
    $text = three(array("M"),array("s+"),$cu,array("Y+"),blank(count($hl)),$cu,0); 
    $text = three(array("M"),array("s+"),$Tu,array("R+"),blank(count($hl)),$Tu,0); 
    $text = three(array("M"),array("s+"),$tu,array("n+"),blank(count($hl)),$tu,0); 
    $text = three(array("M"),array("s+"),$pu,array("m+"),blank(count($hl)),$pu,0); 
    $text = three(array("M"),array("s"),array("+"),array("m"),blank(count($hl)),array("+"),0); 
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") and nimittApAye naimittikasyApyapAyaH (pa) :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) तथा निमित्तापाये नैमित्तिकस्याप्यपायः (प) :</p>\n";
    display(0);            
}
elseif ( (sub(array("M"),$hl,array("+"),0) && in_array($so,$tiG)) && $pada==="pada" )
{
    $text = three(array("M"),$hl,array("+"),array("M"),blank(count($hl)),array("+"),0);
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) :</p>\n";
    display(0);            
    if (sub(array("M"),array("+"),blank(0),0))
    {
    $text = three(array("M"),array("+"),$ku,array("N"),array("+"),$ku,0);
    $text = three(array("M"),array("+"),$cu,array("Y"),array("+"),$cu,0);
    $text = three(array("M"),array("+"),$Tu,array("R"),array("+"),$Tu,0);
    $text = three(array("M"),array("+"),$tu,array("n"),array("+"),$tu,0);
    $text = three(array("M"),array("+"),$pu,array("m"),array("+"),$pu,0);
    $text = two(array("M"),array("+"),array("m"),array("+"),0);
    echo "<p class = sa >By nimittApAye naimittikasyApyapAyaH (pa) :</p>\n";
    echo "<p class = sa >निमित्तापाये नैमित्तिकस्याप्यपायः (प) :</p>\n";
    display(0);
    }
}
/* daridrAterArdhadhAtuke vivakSite Alopo vAcyaH (vA) */
if ( sub(array("daridrA"),array("+"),blank(0),0) && ends(array($fo),array("daridrA"),4) && $ardhadhatuka===1)
{
    $text=one(array("daridrA+"),array("daridr+"),0);
    $text=one(array("daridr+s"),array("daridrA+s"),0);
    echo "<p class = sa >By daridrAterArdhadhAtuke vivakSite Alopo vAcyaH (vA) :</p>\n"; 
    echo "<p class = sa >दरिद्रातेरार्धधातुके विवक्षिते आलोपो वाच्यः (वा) :</p>\n";
    display(0);
}

/* For verbs - remove all + marks. */
if(in_array($so,$tiG))
{
    $text=one(array("+"),array(""),0);
}
/* tipyanasteH (8.2.73) */
if ( arr($text,'/[s]$/') && $so==='tip' && $lakAra==="laN")
{
    $text = pr2(array("s"),blank(0),blank(0),array("d"),blank(0),blank(0),$text);
    echo "<p class = sa >By tipyanasteH (".link_sutra("8.2.73").") :</p>\n";
    echo "<p class = sa >तिप्यनस्तेः (८.२.७३) :</p>\n";  
    display(0);    
}
/* dazca (8.2.75) */
if ( arr($text,'/[d]$/') && $so==='sip' && $lakAra==="laN")
{
    $text2= $text;
    $text1 = pr2(array("d"),blank(0),blank(0),array("r@"),blank(0),blank(0),$text);
    $text = array_merge($text2,$text1);
    echo "<p class = sa >By dazca (".link_sutra("8.2.75").") :</p>\n";
    echo "<p class = sa >दश्च (८.२.७५) :</p>\n";  
    display(0);    
}
/* sipi dhAto rurvA (8.2.74) */
if ( arr($text,'/[s]$/') && $so==='sip' && $lakAra==="laN" && $halGyAbbhyo===1)
{
    $text1 = pr2(array("s"),blank(0),blank(0),array("d"),blank(0),blank(0),$text);
    $text2 = pr2(array("s"),blank(0),blank(0),array("r@"),blank(0),blank(0),$text);
    $text = array_merge($text2,$text1);
    echo "<p class = sa >By sipi dhAto rurvA (".link_sutra("8.2.74").") :</p>\n";
    echo "<p class = sa >सिपि धातो रुर्वा (८.२.७४) :</p>\n";  
    display(0);    
}

/* Adding upasargas to the input verb. */
if ($us!=="" && in_array($so,$tiG) && $upasarga_joined!==1)
{
    $usplus=$us."+";
    $text=Adyanta($text,$usplus,1);
    $upasarga_joined=1;
    $pada="pada";
}
/* This ends the tiGanta specific part. The next are common subanta and tiGanta parts. */

/* displaying general information about the sup vibhaktis */
/* arthavadadhAturapratyayaH prAtipadikam (1.2.45), kRttaddhitasamAsAzca (1.2.46), pratyayaH (3.1.1), parazca (3.1.2), GyAppradipadikAt (4.1.1), svaujasamauTCaSTAbhyAmbhisGebhyAmbhyasGasibhyAmbhyasGasosAmGyossup (4.1.2), vibhaktizca (1.4.104), supaH (1.4.103) */
// For future sUtras also, code for each sUtra is separated by /* xyz.... */.
if (in_array($so,$sup) && $pada==="pratyaya") // Here conditions for application of sUtra is mentioned.
{
    echo "<p class = pa >arthavadadhAturapratyayaH prAtipadikam (".link_sutra("1.2.45")."), kRttaddhitasamAsAzca (".link_sutra("1.2.46")."), pratyayaH (".link_sutra("3.1.1")."), parazca (".link_sutra("3.1.2")."), GyAppradipadikAt (".link_sutra("4.1.1")."), svaujasamauTCaSTAbhyAmbhisGebhyAmbhyasGasibhyAmbhyasGasosAmGyossup (".link_sutra("4.1.2")."), vibhaktizca (".link_sutra("1.4.104").") and supaH (".link_sutra("1.4.103").") :</p>\n"; // the class 'pa' is used for sUtras, which are not vidhisUtras.
    echo "<p class = pa >अर्थवदधातुरप्रत्ययः प्रातिपदिकम्‌ (१.२.४५), कृत्तद्धितसमासाश्च (१.२.४६), प्रत्ययः (३.१.१), परश्च (३.१.२), ङ्‍याप्प्रातिपदिकात्‌ (४.१.१), स्वौजसमौट्छष्टाभ्याम्भिस्ङेभ्याम्भ्यस्ङसिभ्याम्भ्यस्ङसोसाम्ङ्‍योस्सुप्‌ (४.१.२), विभक्तिश्च (१.४.१०४) तथा सुपः (१.४.१०३) :</p>\n";
    display(0); // see function.php for documentation for display function.
}
/* dvyekayordvivacanaikavacane (1.4.22) */
if ( (in_array($so,$eksup)||in_array($so,$dvisup) ) && $pada==="pratyaya") // For $eksup, $dvisup etc. arrays which are not mentioned in subanta.php, please see function.php.
{
    echo "<p class = pa >dvyekayordvivacanaikavacane (".link_sutra("1.4.22").") :</p>\n";
    echo "<p class = pa >द्व्येकयोर्द्विवचनैकवचने (१.४.२२) :</p>\n";
    display(0);
}
/* bahuSu bahuvacanam (1.4.21) */
if (in_array($so,$bahusup) && $pada==="pratyaya")
{
    echo "<p class = pa >bahuSu bahuvacanam (".link_sutra("1.4.21").") :</p>\n";
    echo "<p class = pa >बहुषु बहुवचनम्‌ (१.४.२१) :</p>\n";
    display(0);
}
/* sarUpANAmekazeSa ekavibhaktau (1.2.64) */
if ( (in_array($so,$bahusup)||in_array($so,$dvisup) ) && $pada==="pratyaya")
{
    echo "<p class = pa >sarUpANAmekazeSa ekavibhaktau (".link_sutra("1.2.64").") :</p>\n";
    echo "<p class = pa >सरूपाणामेकशेष एकविभक्तौ (१.२.६४) :</p>\n";
    display(0);
}
/* ekavacanaM sambuddhiH (2.3.49) */
if ( $sambuddhi === 1 && $so==="su!" && $pada==="pratyaya")
{
    echo "<p class = pa >ekavacanaM sambuddhiH (".link_sutra("2.3.49").") :</p>\n";
    echo "<p class = pa >एकवचनं सम्बुद्धिः (२.३.४९) :</p>\n";
    display(0);
}
/* nityabahuvacanAnta special messages */
$nityabahuvacana = array("kati","tri","catur","paYcan","saptan","azwan","navan","daSan","ap"); // list of words which are used in bahuvacanam always. New can be added.
if (in_array($fo,$nityabahuvacana) && !in_array($so,$bahusup) && in_array($so,$sup))
{
    echo "<p class = red >the word you entered is a nitya bahuvacanAnta word. Please check again.</p>\n"; // class red is for potential error messages.
    echo "<p class = red >आपने जो शब्द दिया है, वह नित्य बहुवचनान्त है । कृपया जाँच कीजिए ।</p>\n";
    display(0);
}
/* nityadvivacanAnta special messages */
$nityadvivacana = array("dvi"); // list of words which are used in dvivacanam always.
if (in_array($fo,$nityadvivacana) && !in_array($so,$dvisup) && in_array($so,$sup))
{
    echo "<p class = red >the word you entered is a nitya dvivacanAnta word. Please check again.</p>\n";
    echo "<p class = red >आपने जो शब्द दिया है, वह नित्य द्विवचनान्त है । कृपया जाँच कीजिए ।</p>\n";
    display(0);
}
/* tyadAdi sambodhana special messages */
if (in_array($fo,$tyadadi) && $so==="su!" && $sambuddhi===1)
{
    echo "<p class = red >tyadAdi don't have sambodhana.</p>\n";
    echo "<p class = red >त्यदादि का संबोधन नहीं होता है ।</p>\n";
    display(0);
}
/* defining sarvanama status */
// $sarvafinal. 1 - obligatory. 2 - optional. 0 - no sarvanamasaJjJA.
/* vibhASAprakaraNe tIyasya GitsUpasaMkhyAnam (vA 242) */ 
if (in_array($fo,array("dvitIyA","tftIyA")) && in_array($so,array("Ne","Nasi!","Nas","Ni")))
{
    $sarvafinal = 2;
    echo "<p class = sa >By vibhASA dvitIyAtRtIyAbhyAm (".link_sutra("7.3.115").") :</p>\n"; // class sa is for vidhisUtras.
    echo "<p class = hn >According to siddhAntakaumudI, this sUtra can be done away with. </p>\n"; // class hn is for some detailed note on the sUtra under question.
    echo "<p class = sa >विभाषा द्वितीयातृतीयाभ्याम्‌ (७.३.११५) :</p>\n";  
    echo "<p class = hn >कौमुदी के मत में यह सूत्र त्याग करना शक्य है, तीयस्य ङित्सूपसङ्ख्यानात्‌  । </p>\n";  
    display(0);
}
elseif (in_array($fo,array("dvitIya","tftIya")) && in_array($so,array("Ne","Nasi!","Nas","Ni")))
{
    $sarvafinal = 2;
    echo "<p class = pa >By vibhASAprakaraNe tIyasya GitsUpasaMkhyAnam (vA 242) :</p>\n";
    echo "<p class = pa >विभाषाप्रकरणे तीयस्य ङित्सूपसङ्ख्यानम्‌ (वा २४२) :</p>\n";        
    display(0);
}
elseif ( in_array($_GET['cond1_1_1'],array("1","2")) || in_array($_GET['cond2_1_2_1'],array("1","2"))) // For these $_GET, pleas see ajax.php and ajax requirements.docx. the numbers refer to their documentation in docx file.
{
    $sarvafinal = 0;
    echo "<p class = pa >saJjJopasarjanIbhUtAstu na sarvAdayaH (vA 225) :</p>\n";
    echo "<p class = pa >सञ्ज्ञोपसर्जनीभूतास्तु न सर्वादयः (वा २२५) :</p>\n";   
    display(0);
}
elseif ( $_GET['cond1_1_1']==="3" || $_GET['cond2_1_2_1']==="3")
{
    $sarvafinal = 0;
    echo "<p class = pa >tRtIyAsamAse (".link_sutra("1.1.30").") :</p>\n";
    echo "<p class = hn >This prevents application of sarvAdIni sarvanAmAni. Therefore, there is no sarvanAmasaJjJA applicable here.</p>\n";
    echo "<p class = pa >तृतीयासमासे (१.१.३०) :</p>\n";        
    echo "<p class = hn >अत्र प्रथमं सर्वादीनि सर्वनामानि इति सर्वनामसंज्ञायां प्राप्तायां तृतीयासमासे इति सर्वनामसंज्ञानिषेधः ।</p>\n";        
    display(0);
}
elseif ( $_GET['cond1_1_1']==="4" || $_GET['cond2_1_2_1']==="4")
{
    $sarvafinal = 0;
    echo "<p class = pa >dvandve ca (".link_sutra("1.1.31").") :</p>\n";
    echo "<p class = hn >This prohibits application of sarvAdIni sarvanAmAni. </p>\n";
    echo "<p class = pa >द्वन्द्वे च (१.१.३१) :</p>\n";        
    echo "<p class = hn >सर्वादीनि सर्वनामानि इति सर्वनामसज्ञायां प्राप्तायाम् अनेन निषेधः ।</p>\n";        
    display(0); $dvandveca=1; // 0 - dvandve ca will not apply. 1 - dvandve ca will apply.
}
elseif ( $_GET['cond1_1_1_5']==="1" || $_GET['cond2_1_2_1_5']==="1" )
{
    $sarvafinal = 2;
    echo "<p class = pa >vibhASA diksamAse bahuvrIhau (".link_sutra("1.1.28").") :</p>\n";
    echo "<p class = pa >विभाषा दिक्समासे बहुव्रीहौ (१.१.२८) :</p>\n";            
    display(0);
}
elseif ( $_GET['cond1_1_1_5']==="2" || $_GET['cond2_1_2_1_5']==="2" )
{
    $sarvafinal = 0;
    echo "<p class = pa >na bahuvrIhau (".link_sutra("1.1.29").") :</p>\n";
    echo "<p class = pa >न बहुव्रीहौ (१.१.२९) :</p>\n";            
    display(0);
}
elseif ( $_GET['cond1_1_1_6_1']==="2" || $_GET['cond2_1_2_1_6_1']==="2" )
{
    $sarvafinal = 1;
    echo "<p class = pa >pUrvaparAvaradakSiNottarAparAdharANi vyavasthAyAmasaJjJAyAm (ga sU 1) :</p>\n";
    echo "<p class = pa >पूर्वपरावरदक्षिणोत्तरापराधराणि व्यवस्थायामसञ्ज्ञायाम्‌ (ग सू १) :</p>\n"; 
    display(0); 
    $purvapara=1; // 0 - pUrvaparA... will not apply. 1 - will apply
    $sarvanama=array_merge($sarvanama,array("pUrva","para","avara","dakziRa","uttara","apara","aDara","pUrvA","parA","avarA","dakziRA","uttarA","aparA","aDarA"));
}
elseif ( $_GET['cond1_1_1_6_1']==="1" || $_GET['cond2_1_2_1_6_1']==="1" )
{
    $sarvafinal = 0;
}
elseif ( $_GET['cond1_1_1_6_2']==="2" || $_GET['cond2_1_2_1_6_2']==="2" )
{
    $sarvafinal = 1;
    echo "<p class = pa >svamajJAtidhanAkhyAyAm (ga sU 2) :</p>\n";
    echo "<p class = pa >स्वमज्ञातिधनाख्यायाम्‌ (ग सू २) :</p>\n"; 
    $sva=1; // 0 - svamajJAtidhanAkhyAyAm (1.1.35) doesn't apply. 1 - svamajJAtidhanAkhyAyAm (1.1.35) applies.           
    display(0);
    $sarvanama=array_merge($sarvanama,array("sva","svA")); // For most of the cases, sva / svA are not sarvanAma. In this particular case, they become sarvanAma.
}
elseif ( $_GET['cond1_1_1_6_2']==="1" || $_GET['cond2_1_2_1_6_2']==="1" )
{
    $sarvafinal = 0;
    $sva=0;           
}
elseif ( $_GET['cond1_1_1_6_3_1']==="1" || $_GET['cond2_1_2_1_6_3_1']==="1" )
{
    $sarvafinal = 0;
    echo "<p class = pa >'antaraM bahiryogopasaMvyanayoH' (ga sU 3) iti gaNasUtre 'apuri' iti vaktavyam (vA 240) :</p>\n";
    echo "<p class = pa >'अन्तरं बहिर्योगोपसंव्यानयोः' (ग सू ३) इति गणसूत्रे 'अपुरि' इति वक्तव्यम्‌ (वा २४०) :</p>\n"; 
    $antara=0;           // 0 - antaraM bahiryogopasaMvyAnayoH (1.1.36) doesn't apply. 1 - antaraM bahiryogopasaMvyAnayoH (1.1.36) applies.
    display(0);
}
elseif ( $_GET['cond1_1_1_6_3_1']==="2" || $_GET['cond2_1_2_1_6_3_1']==="2" )
{
    $sarvafinal = 1;
    echo "<p class = pa >antaraM bahiryogopasaMvyAnayoH (ga sU 3) :</p>\n";
    echo "<p class = pa >अन्तरं बहिर्योगोपसंव्यानयोः (ग सू ३) :</p>\n";
    $antara=1;     
    display(0);
    $sarvanama=array_merge($sarvanama,array("antara","antarA"));
}
elseif ( $_GET['cond1_1_1_6_3']==="2" || $_GET['cond2_1_2_1_6_3']==="2" )
{
    $sarvafinal = 0;
    $antara=0;           
}
elseif ( $_GET['cond1_1_1_6_4']==="1" || $_GET['cond2_1_2_1_6_4']==="1" )
{
    $sarvafinal = 1;
}
elseif ( $_GET['cond1_1_1_6_4']==="2" && !in_array($fo,array("anyatara","anyatarA")) && $_GET['cond2_1_2_1_6_4']==="2")
{
    $sarvafinal = 0; 
    $sarvanama=array_diff($sarvanama,array("atara","atama","atarA","atamA")); echo "hi";
}
elseif (ends(array($fo),array("anyatama","anyatamA"),1))
{
    $sarvafinal = 0;
    echo "<p class = pa >As anyatama is not enumerated in sarvAdi and anyatara is specifically enumerated, anyatama doesn't have sarvanAma saJjJA. </p>\n";
    echo "<p class = pa >सर्वादिगण में अन्यतर का गणन हुआ है और अन्यतम का नहीं है, इसलिए अन्यतम की सर्वनाम सञ्ज्ञा नहीं है ।</p>\n";        
    display(0);   $sarvanama=array_diff($sarvanama,array("atama","atamA"));
}
elseif ( $_GET['cond1_1_1_6_5']==="1" || $_GET['cond2_1_2_1_6_5']==="1" )
{
    $sarvafinal = 1;
    echo "<p class = pa >samaH sarvaparyAyaH.</p>\n";
    echo "<p class = pa >समः सर्वपर्यायः ।</p>\n";        
    display(0);   
    $sarvanama=array_merge($sarvanama,array("sama","samA"));
}
elseif ( $_GET['cond1_1_1_6_5']==="2" || $_GET['cond2_1_2_1_6_5']==="2" )
{
    $sarvafinal = 0;
    echo "<p class = pa >tulyaparyAyastu neha gRhyate.</p>\n";
    echo "<p class = pa >तुल्यपर्यायस्तु नेह गृह्यते ।</p>\n";        
    display(0);   
}
elseif ( $_GET['cond1_1_1']==="6" || $_GET['cond2_1_2_1']==="6" || in_array($fo,array("idam","tyad")) )
{
    $sarvafinal = 1;
}
elseif ( ($_GET['cond1_16']==="2" || $_GET['cond1_7']==="2") && in_array($fo,$sarvanama) )
{
    $sarvafinal = 1;
}
elseif ( $_GET['cond1_10']==="2"  )
{
    $sarvafinal = 1;
}
elseif ( ends(array($fo),array("tvat","tyad","tad","yad","etad","idam","adas","yuzmad","asmad","kim","idakam"),1)  ) // prAdhAnya and gauNatva pending.
{
    $sarvafinal = 1;
}
else
{
    $sarvafinal = 0;
}

/* sarvAdIni sarvanAmAni (1.1.27) */
if ($sarvafinal !==0)
{
    if (in_array($fo,$sarvanama)||in_array($fo,$sarvanamastri))
    {
        echo "<p class = pa >sarvAdIni sarvanAmAni (".link_sutra("1.1.27").") :</p>\n";
        echo "<p class = pa >सर्वादीनि सर्वनामानि (१.१.२७) :</p>\n";
        display(0);
    }
    /* tadantasyApIyaM saJjJA | dvandve ca iti jJApakAt */
    if ( ends(array($fo),$sarvanama,0)||ends(array($fo),$sarvanamastri,0))
    {
        echo "<p class = pa >sarvAdIni sarvanAmAni (".link_sutra("1.1.27").") and tadantasyApIyaM saJjJA. dvandve ca iti jJApakAt :</p>\n";
        echo "<p class = pa >सर्वादीनि सर्वनामानि (१.१.२७) तथा तदन्तस्यापीयं सञ्ज्ञा । द्वन्द्वे च इति ज्ञापकात्‌ :</p>\n";
        display(0); 
    }
}
/* defininig eranekAca */
if ($_GET['cond1_4'] === "1") // for conditions, please see ajax requirements.docx
{
    $nadi = 0;
    $eranekaca=0;
}
elseif ($_GET['cond1_4_2'] === "1")
{
    $eranekaca=0;
    $nadi=1;
    $GI=1;
}
elseif ($_GET['cond1_4_2'] === "2")
{
    $eranekaca=0;
    $nadi=1;
    $GI=0;
}
elseif ($_GET['cond1_4_3'] === "1")
{
    $eranekaca=1;
    $nadi=0;
    $nI=1;
    $dhatu=1;
}
elseif ($_GET['cond1_4_3'] === "2")
{
    $eranekaca=1;
    $nadi=0;
    $dhatu=1;
    // anaGNitva, uttvam extra.
}
elseif ($_GET['cond1_4_3'] === "3")
{
    $eranekaca=1;
    $nadi=0;
    $dhatu=1;
    // khyatyAtparasya extra.
}
elseif ($_GET['cond1_4_3'] === "4")
{
    $eranekaca=1;
    $nadi=0;
    $dhatu=1;
}
elseif ($_GET['cond1_4_4'] === "1")
{
    $eranekaca=1;
    $nadi=1;
    $GI=1;
    $dhatu=1;
}
elseif ($_GET['cond1_4_4'] === "2")
{
    $eranekaca=1;
    $nadi=1;
    $GI=0;
    $dhatu=1;
}
elseif ($_GET['cond1_4_5'] === "1")
{
    $eranekaca=0;
    $nadi=0;
    $nI=1;
    $dhatu=1;
}
elseif ($_GET['cond1_4_5'] === "2")
{
    $eranekaca=0;
    $nadi=0;
    $nI=0;
    $dhatu=1;
    $GI=0;
}
elseif ($_GET['cond1_4'] === "5")
{
    $eranekaca=0;
    $nadi=0;
    $dhatu=1;
    $GI=0;
}
elseif ($_GET['cond1_4'] === "6")
{
    $eranekaca=0;
    $nadi=1;
    $dhatu=1;
}

/* defining oH supi variable */
if ($_GET['cond1_5'] === "1")
{
    $nadi = 0;
    $eranekaca=0;
}
elseif ($_GET['cond1_5'] === "2")
{
    $eranekaca=0;
    $nadi=1;
}
elseif ($_GET['cond1_5'] === "3")
{
    $eranekaca=1;
    $dhatu=1;
}
elseif ($_GET['cond1_5'] === "4")
{
    $eranekaca=0;
    $dhatu=1;
}

/* GI definition */
if ($_GET['cond2_3']==="1")
{
    $GI=1;
    $nadi=1;
}
elseif ($_GET['cond2_3']==="2")
{
    $GI=0;
    $nadi=1;
}
elseif ($_GET['cond2_3']==="3")
{
    $GI=1;
    $eranekaca=0;
    $nadi=1;
}
elseif ($_GET['cond2_3']==="4")
{
    $GI=0;
    $eranekaca=0;
    //$nadi=1; // Not sure
    $dhatu=1; // for zrI.
}

/* makArAnta pulliGga dhAtu definition */
if (ends(array($fo),array("m"),1) && $_GET['cond1_16']==="1")
{
    $dhatu=1;
}
/* idam, idakam anvAdeza definition */
if ( $_GET['cond1_7']==="1" || $_GET['cond1_10_2']==="1" || $_GET['cond2_5']==="1" || $_GET['cond3_2']==="1" )
{
    $sarvafinal=1;
    $anvadesha=1;
}
/* nakArAnta pulliGga ShanvAat definition */ 
if ( $_GET['cond1_8']==="1")
{
    $samasa=1;
    $pradhana=0;
}
elseif ( $_GET['cond1_8']==="2")
{
    $samasa=1;
    $pradhana=1;
}
/* jakArAnta pulliGga kvin pratyaya definition */
if ( $_GET['cond1_9']==="1" && $_GET['cond1_9_1']==="1" )
{
    $dhatu=1;
    $kvin=1;
    $samasa=0;
    $yuj=1; // 0 - yujerasamAse (7.1.71) won't apply. 1 - yujerasamAse (7.1.71) will apply.
}
elseif ( $_GET['cond1_9']==="1" || $_GET['cond1_9_1']==="2" )
{
    $dhatu=1;
    $kvin=1;
    $samasa=0;
    $yuj=0;
}
else
{
    $yuj=0;
}
/* tyadAdi gauNatva definition */
if ( $_GET['cond1_10']==="1"  )
{
    $noatvasatva=1; // 0 - tyadAdi are upasarjanIbhUta / saJjJA. 1 - tyadAdi are not upasarjanIbhUta / saJjJA i.e. prAdhAnya.
}
else
{
    $noatvasatva=0;
}
/* asmad / yuSmad -> nau / naH etc definition */
if ($_GET['cond1_12']==="1" && in_array($w,array(3,4,5,9,10,11,15,16,17)) )
{
    $asmadpada=0;    
    echo "<p class = pa >Because the words asmad / yuSmad are not after a 'pada' , there won't be tvA/mA, nau/vAm, nas/vas, te/me Adezas:</p>\n";
    echo "<p class = pa >'पदात्‌' नहीं होने से अस्मद्‌ / युष्मद्‌ के त्वा/मा, नौ/वाम्‌, नस्‌/वस्‌, ते/मे आदेश नहीं होंगे ।:</p>\n";
    display(0);
}
elseif($_GET['cond1_12']==="2" && in_array($w,array(3,4,5,9,10,11,15,16,17)) )
{
    $asmadpada=0;    
    echo "<p class = pa >Because the words asmad / yuSmad are used in the start of a 'pAda', there won't be tvA/mA, nau/vAm, nas/vas, te/me Adezas:</p>\n";
    echo "<p class = pa >पाद के प्रारम्भ में प्रयुक्त होने से अस्मद्‌ / युष्मद्‌ के त्वा/मा, नौ/वाम्‌, नस्‌/वस्‌, ते/मे आदेश नहीं होंगे ।:</p>\n";
    display(0);
}
elseif($_GET['cond1_12']==="3" && in_array($w,array(3,4,5,9,10,11,15,16,17)))
{
    $asmadpada=0;    
    echo "<p class = pa >By na cavAhAhaivayukte (".link_sutra("8.1.24")."):</p>\n";
    echo "<p class = hn >This prevents tvA/mA, nau/vAm, nas/vas, te/me Adezas.</p>\n";    
    echo "<p class = pa >न चवाहाहैवयुक्ते (८.१.२४):</p>\n";
    echo "<p class = hn >इससे त्वा/मा, नौ/वाम्‌, नस्‌/वस्‌, ते/मे आदेश नहीं होंगे ।</p>\n";
    display(0);
}
elseif($_GET['cond1_12']==="4" && in_array($w,array(3,4,5,9,10,11,15,16,17)))
{
    $asmadpada=0;    
    echo "<p class = pa >By pazyArthaizcAnAlocane (".link_sutra("8.1.25")."):</p>\n";
    echo "<p class = hn >This prevents tvA/mA, nau/vAm, nas/vas, te/me Adezas.</p>\n";    
    echo "<p class = pa >पश्यार्थैश्चानालोचने (८.१.२५):</p>\n";
    echo "<p class = hn >इससे त्वा/मा, नौ/वाम्‌, नस्‌/वस्‌, ते/मे आदेश नहीं होंगे ।</p>\n";
    display(0);
}
elseif($_GET['cond1_12_5_1_1_1_1']==="1" && in_array($w,array(3,4,5,9,10,11,15,16,17)))
{
    $asmadpada=2;    
    echo "<p class = pa >By vibhASitaM vizeSavacane (".link_sutra("8.1.74").") :</p>\n";
    echo "<p class = hn >bhASya makes tvA/mA, nau/vAm, nas/vas, te/me Adezas optional by 'bahuvacanamiti vakSyAmi'.</p>\n";    
    echo "<p class = pa >विभाषितं विशेषवचने (८.१.७४) :</p>\n";
    echo "<p class = hn >भाष्य 'बहुवचनमिति वक्ष्यामि' से त्वा/मा, नौ/वाम्‌, नस्‌/वस्‌, ते/मे विभाषा आदेश बताता है ।</p>\n";
    display(0);
}
elseif($_GET['cond1_12_5_1_1_1_1']==="2" && in_array($w,array(3,4,5,9,10,11,15,16,17)))
{
    $asmadpada=1;    
    echo "<p class = pa >By nAmantrite samAnAdhikaraNe sAmAnyavacanam (".link_sutra("8.1.73").") :</p>\n";
    echo "<p class = pa >नामन्त्रिते समानाधिकरणे सामान्यवचनम्‌ (८.१.७३) :</p>\n";
    display(0);
}
elseif($_GET['cond1_12_5_1_1_1']==="2" && in_array($w,array(3,4,5,9,10,11,15,16,17)))
{
    $asmadpada=0;    
    echo "<p class = pa >By AmantritaM pUrvamavidyamAnavat (".link_sutra("8.1.72").") :</p>\n";
    echo "<p class = hn >By sAmantritam (".link_sutra("2.3.48").") :</p>\n";
    echo "<p class = pa >आमन्त्रितं पूर्वमविद्यमानवत्‌ (८.१.७२) :</p>\n";
    echo "<p class = hn >सामन्त्रितम्‌ (२.३.४८) :</p>\n";
    display(0);
}
elseif($_GET['cond1_12_5_1_1']==="2" && in_array($w,array(3,4,5,9,10,11,15,16,17)))
{
    $asmadpada=2;    
    echo "<p class = pa >By sapUrvAyAH prathamAyA vibhASA (".link_sutra("8.1.26").") :</p>\n";
    echo "<p class = pa >सपूर्वायाः प्रथमाया विभाषा (८.१.२६) :</p>\n";
    display(0);
}
elseif($_GET['cond1_12_5_1']==="2" && in_array($w,array(3,4,5,9,10,11,15,16,17)))
{
    $asmadpada=1;    
    echo "<p class = pa >By ete vAMnAvAdaya AdezA ananvAdeze vA vaktavyAH (vA 4717). anvAdeze tu nityaM syuH. :</p>\n";
    echo "<p class = pa >एते वांनावादय आदेशा अनन्वादेशे वा वक्तव्याः (वा ४७१७). अन्वादेशे तु नित्यं स्युः । :</p>\n";
    display(0);
}
elseif($_GET['cond1_12_5']==="2" && in_array($w,array(3,4,5,9,10,11,15,16,17)))
{
    $asmadpada=2;    
    echo "<p class = pa >By ete vAMnAvAdaya AdezA ananvAdeze vA vaktavyAH (vA 4717) :</p>\n";
    echo "<p class = pa >एते वांनावादय आदेशा अनन्वादेशे वा वक्तव्याः (वा ४७१७) :</p>\n";
    display(0);
}
/* ancu definitions */
if ($_GET['cond1_13_1']==="1" )
{
    $kvin=1;
    $nance=1; // 0 - nAJceH pUjAyAm (6.4.30) will not apply. 1 - nAJceH pUjAyAm (6.4.30) will apply.
    $ancu=1; 
}
elseif ($_GET['cond1_13_1']==="2" )
{
    $kvin=1;
    $nance=0;
    $ancu=1; // 0 - no ancu verb. 1 - ancu verb.
}
elseif ($_GET['cond1_13']==="2")
{
    $kvin=0;
    $ancu=0;
}
/* defining goanc */
if (in_array($fo,array("goanc","goaYc")) )
{
    $kvin=1;
    $nance=1; 
    $goanc=1; // 0 - no goanc word. 1 - the word is goanc.
}
else
{
    $goanc=0;
}

/* defining Nyanta */
if ($_GET['cond1_15']==="1")
{
    $Nyanta=1; 
}
/* defining dhatus */
// This is the crude way in which dhAtu saJjJA is given without user interface. In practice it is difficult to decide whether the word ends with a dhAtu or not. So made enumeration.
if (sub(array("supis","sutus","suhiMs","gir","pur","sajuz","ASiz"),array("+"),blank(0),0))
{
    $dhatu=1;
}
/* defining kvip */
// It is difficult to know whether kvip pratyay has happened or not, because it is elided completely. so manually enumerated the instances where SK uses kvip pratyayAnta words.
if (sub(array("DvaMs","sraMs"),blank(0),blank(0),0))
{
    $kvip=1;
    $dhatu=1;
}
if (sub(array("beBid","cecCid","pipaWiz"),blank(0),blank(0),0))
{
    $kvip=1;
}
/* Defining ugit */
if ( sub(array("DvaMs","sraMs"),array("+"),blank(0),0) )
{
    $text = two(array("DvaMs","sraMs"),array("+"),array("DvaMsu!","sraMsu!"),array("+"),0);
    echo "<p class = pa >This is an udit dhAtu.</p>\n";
    echo "<p class = pa >यह एक उदित्‌ धातु है ।</p>\n";
    display(0);
}
/* strIliGga definitions */
/* defining Abantatva */
if (ends(array($fo),array("A"),1) && $gender==="f" && $_GET['cond2_1']==="1")
{
    $Ap=0;
    $dhatu=1;
}
elseif (ends(array($fo),array("A"),1) && $gender==="f")
{
    $Ap=1;
}
else
{
     $Ap=0;
}
     
/* nityastrIliGga definition */
$strilist = array("strI","SrI"); // list of nityastrIliGga words. Add other words to make it exhaustive.
if (ends(array($fo),$strilist,1))
{
    $nityastri=1;
}

/* praDI definition */
if ($_GET['cond2_3_5']==='1')
{
    $dhatu=1;
    $eranekaca=2;
    $GI=0;
    $nadi=2;
    echo "<p class = pa >This word is declined like 'lakSmI' according to vRttikAra, and like its musculine counterpart according to kaiyaTa.</p>\n";
    echo "<p class = pa >प्रधीशब्दस्य तु वृत्तिकारानां मते लक्ष्मीवद्रूपम्‌ । कैयटमते तु पुंवद्रूपम्‌ । :</p>\n";
    display(0);
}
if ($_GET['cond2_3_5']==='2')
{
    $dhatu=1;
    $eranekaca=1; 
}
/* sulU definition */
if (in_array($fo,array("sulU")) && $gender==="n")
{
    $dhatu=1;
    $eranekaca=1;
}
/* suDI definition */
if ($_GET['cond2_3_6']==='1')
{
    $dhatu=1;
    $GI=0;
    $nityastri=1;
    echo "<p class = pa >This word is declined like 'zrI' according to vRttikAra, and like its musculine counterpart according to kaiyaTa.</p>\n";
    echo "<p class = pa >सुष्ठु धीर्यस्याः, सुष्ठु ध्यायतीति वेति विग्रहे तु वृत्तिकारमते सुधीः श्रीवत्‍ । मतान्तरे तु पुंवत्‌ ।</p>\n";
    display(0);
}
if ($_GET['cond2_3_5']==='2')
{
    $dhatu=1;
    $GI=0;
    $nityastri=1;
    echo "<p class = pa >This word is declined like 'zrI'.</p>\n";
    echo "<p class = pa >'सुष्ठु धीः' इति विग्रहे तु श्रीवदेव ।।</p>\n";
    display(0);
}

/* UkArAnta strIliGga - defining */
if ($_GET['cond2_4']==='1')
{
    $nityastri=1;
}
elseif ($_GET['cond2_4']==='2')
{
    $dhatu=1;
    $eranekaca=0;
    $nityastri=1;
}
elseif ($_GET['cond2_4']==='3')
{
    $dhatu=1;
    $eranekaca=1;
    $nityastri=0;
}

if ($gender==="f" && ends(array($fo),array("varzABU","svayamBU"),1))
{
    $dhatu=1;
}
/* punarBU definition */
if ($_GET['cond2_4_4_1']==='1')
{

}
elseif ($_GET['cond2_4_4_1']==='2')
{
    $dhatu=1;
    $eranekaca=0;
}
/* bhASitapuMska definition */
if ($_GET['cond3']==='1')
{
    $bhashitapumska=1;
}
/* tRtIyAdiSu bhASitapuMskaM puMvadgAlavasya (7.1.74) */
if ($bhashitapumska===1 && in_array($so,$tRtIyAdiSvaci) ) // see function.php for $tRtIyAdiSvaci.
{
    echo "<p class = pa >By tRtIyAdiSu bhASitapuMskaM puMvadgAlavasya (".link_sutra("7.1.74").") :</p>\n";
    echo "<p class = pa >तृतीयादिषु भाषितपुंस्कं पुंवद्गालवस्य (७.१.७४):</p>\n";
    display(0);
}


/* defining kRt pratyayas */
/* kRdatiG (3.1.93) */
if ($pada === "pratyaya" && !in_array($so,$tiG) && $dhatu===1)
{
    echo "<p class = pa >By kRdatiG (".link_sutra("3.1.93").") :</p>\n";
    echo "<p class = pa >कृदतिङ्‌ (३.१.९३) :</p>\n";
    display(0);    
}
/* Defining pada and bham */
/* suDanapuMsakasya (1.1.43) */ 
if ($gender !== "n" && in_array($so,$sarvanamasthana))
{
    echo "<p class = pa >By suDanapuMsakasya (".link_sutra("1.1.43").") :</p>\n";
    echo "<p class = pa >सुडनपुंसकस्य (१.१.४३) :</p>\n";
    display(0);    
}
/* bahuguNavatuDati saGkhyA (1.1.28) */
/* Dati ca (1.1.25) */
if (in_array($fo,array("bahu","guRa")))
{
    $sankhya = 1; // 0 - no saGkhyA saJjJA. 1 - saGkhyA saJjJA.
    echo "<p class = pa >By bahuguNavatuDati saGkhyA (".link_sutra("1.1.28").") :</p>\n";
    echo "<p class = pa >बहुगुणवतुडति सङ्ख्या (१.१.२८) :</p>\n";
    display(0);
}
else 
{
    $sankhya = 0;
}
if (in_array($fo,array("kati")))
{
    $sankhya = 1;
    $shat = 1; // 0 - no SaT saJjJA. 1 - SaT saJjJA.
    echo "<p class = pa >By bahuguNavatuDati saGkhyA (".link_sutra("1.1.28").") and Dati ca (".link_sutra("1.1.25").") :</p>\n";
    echo "<p class = pa >बहुगुणवतुडति सङ्ख्या (१.१.२८) तथा डति च (१.१.२५) :</p>\n";
    display(0);
}
else 
{
    $sankhya = 0;
    $shat = 0;
}
/* SNAntA Sat (1.1.24) */
if (arr($text,'/[zn][+]/') && ($sankhya===1 || ends(array($fo),array("paYcan","zaz","saptan","zwan","navan","daSan"),1)) && ($samasa===0 || $samasa===1 && $pradhana===1) && $_GET['cond1_18']!=="2")
// for function arr see function.php.        
{
    $shat = 1;
    echo "<p class = pa >By SNAntA Sat (".link_sutra("1.1.24").") :</p>\n";
    echo "<p class = pa >ष्णान्ता षट्‌ (१.१.२४) :</p>\n";
    display(0);
}
/* defining "SaT" */
if ($_GET['cond1_18']===2)
{
    $shat=0;
}
/* same in all lingas - special messages */
$samaliGga = array("asmad","asmat","yuzmad","yuzmat"); // list of words which are same in all three genders.
if (in_array($fo,$samaliGga) || $shat===1)
{
    echo "<p class = pa >asmad, yuSmad and words having SaT saJjJA have same forms in all three genders.</p>\n";
    echo "<p class = pa >अस्मद्युष्मद्‍षट्सञ्ज्ञकाः त्रिषु सरूपाः ।</p>\n";
    display(0);
}
/* checking for presence of aJcu verb. */
if (sub(array("aYc","AYc","anc","Anc"),blank(0),blank(0),0))
{
    $ancu=1;
}
else
{
    $ancu=0;
}
/* zatRvat finder */
if(sub(array("pfzad","bfhat","mahat","jagat",),blank(0),blank(0),0) || $_GET['cond1_14']==="2" )
{
    $shatR = 1;
    $it = array_merge($it,array("S","f")); // adding two it markers 'z' and 'R', because zatR pratyaya has z and R as it marker. Similarly for next cases also.
    $itprakriti = array_merge($itprakriti,array("S","f"));
}
elseif ($_GET['cond3_3']==="1" )
{
    $shatR = 1;
    $it = array_merge($it,array("S","f"));
    $itprakriti = array_merge($itprakriti,array("S","f"));
    $abhyasta=1;
}
elseif ($_GET['cond3_3_2_1']==="2")
{
    $shatR = 1; $dhatu=1;
    $it = array_merge($it,array("S","f"));
    $itprakriti = array_merge($itprakriti,array("S","f"));
    $AcCInadyo = 1;
}
elseif ($_GET['cond3_3_2_1']==="1")
{
    $shatR = 1; $dhatu=1;
    $it = array_merge($it,array("S","f"));
    $itprakriti = array_merge($itprakriti,array("S","f"));
    $shap = 1;
    $shyan = 1;
}


/* atvanta finder */
if ($_GET['cond1_17']==="1" || $_GET['cond1_14']==="1" ) // atvanta and ugit.
{
    $atu = 1;
    $it = array_merge($it,array("u"));        
    $itprakriti = array_merge($itprakriti,array("u"));
}
elseif( in_array($fo,array("veDas",)) ) // This is atvanta, but not ugit.
{
    $atu=1;
}
elseif( in_array($fo,array("suvas","gras","glas")))
{
    $dhatu=1;
    $atu=1;
}
else
{
    $atu=0;
}
/*if(sub(array("atu!+"),blank(0),blank(0),0) || ( $bhavat===0 && sub(array("Bavat"),blank(0),blank(0),0)) )
{
    $atu = 1;
    if (sub(array("atu!+"),blank(0),blank(0),0)|| ( $bhavat===0 && sub(array("Bavat"),blank(0),blank(0),0)))
    {
    $it = array_merge($it,array("u"));        
    $itprakriti = array_merge($itprakriti,array("u"));
    }
}
else
{
    $atu = 0;
}*/
/* ugitazca (4.1.6) */
$ugitverbwords=array("Bavat","pacat","kurvat");
$ugitprAtipadika = array("praanc","praaYc","prAnc","prAYc","pratianc","pratiaYc","pratyanc","pratyaYc","udanc","udaYc",);
if (sub(array("Bavat"),array("+"),blank(0),0))
{
    $it=array_merge($it,array("u"));
    $itprakriti = array_merge($itpratyaya,array("u"));
}
if (sub(array("pacat"),array("+"),blank(0),0))
{
    $it=array_merge($it,array("f"));
    $itprakriti = array_merge($itpratyaya,array("f"));
    $shap = 1;
    $shatR = 1;
    $dhatu = 1;
} 
if (sub(array("praanc","praaYc","prAnc","prAYc","pratianc","pratiaYc","pratyanc","pratyaYc","udanc","udaYc",),array("+"),blank(0),0))
{
    $it=array_merge($it,array("u"));
    $itprakriti = array_merge($itpratyaya,array("u"));
    //$dhatu = 1;
} 
if ($gender === "f" && ( sub($ugitprAtipadika,array("+"),blank(count($ugitprAtipadika)),0) || sub($ugitverbwords,array("+"),blank(0),0)) )
{
    $text = one(array("+"),array("+NIp+"),0);
    echo "<p class = st >By ugitazca (".link_sutra("4.1.6").") :</p>\n";
    echo "<p class = st >उगितश्च (४.१.६) :</p>\n";        
    display(0);
    $GIp=1;
}
if ($gender==="f" && sub(array("DvaMsu!","sraMsu!"),array("+"),blank(0),0))
{
    echo "<p class = pa >dhAtozcedugitkAryaM tarhyaJcatereva (vA) :</p>\n";
    echo "<p class = pa >धातोश्चेदुगित्कार्यं तर्ह्यञ्चतेरेव (वा) इति नियम्यते । :</p>\n";        
    display(0);    
}
/* antarvatpativatornuk (4.1.32) */
if ($gender === "f" && sub(array("antarvat","pativat"),array("+"),blank(0),0) )
{
    $text = two(array("antarvat","pativat"),array("+"),array("antarvatn","pativatn"),array("+"),0);
    echo "<p class = st >By antarvatpativatornuk (".link_sutra("4.1.32").") :</p>\n";
    echo "<p class = st >अन्तर्वत्पतिवतोर्नुक्‌ (४.१.३२) :</p>\n";        
    display(8);
}
/* patyurno yajJAsaMyoge (4.1.33) */
elseif ($gender === "f" && $fo==="pati" )
{
    $text = two(array("pati"),array("+"),array("patn"),array("+"),0);
    echo "<p class = st >By patyurno yajJAsaMyoge (".link_sutra("4.1.33").") :</p>\n";
    echo "<p class = st >पत्युर्न यज्ञसंयोगे (४.१.३३) :</p>\n";        
    display(8);
}
/* nityaM sapatnyAdiSu (4.1.35) */
elseif ($gender === "f" && $fo!=="pati" && sub(array("samAnapati","ekapati","vIrapati","piRqapati","BrAtfpati","putrapati","dAsapati"),array("+"),blank(0),0) )
{
    $text = two(array("samAnapati","ekapati","vIrapati","piRqapati","BrAtfpati","putrapati","dAsapati"),array("+"),array("sapatn","ekapatn","vIrapatn","piRqapatn","BrAtfpatn","putrapatn","dAsapatn"),array("+"),0);
    echo "<p class = st >By nityaM sapatnyAdiSu (".link_sutra("4.1.35").") :</p>\n";
    echo "<p class = st >नित्यं सपत्न्यादिषु (४.१.३५) :</p>\n";        
    display(8);
}
/* vibhASA sapUrvasya (4.1.34) */
elseif ($gender === "f" && $fo!=="pati" && sub(array("pati"),array("+"),blank(0),0) )
{
    $text = two(array("pati"),array("+"),array("patn"),array("+"),1);
    echo "<p class = st >By vibhASA sapUrvasya (".link_sutra("4.1.34").") :</p>\n";
    echo "<p class = st >विभाषा सपूर्वस्य (४.१.३४) :</p>\n";        
    display(0);
}
/* Udhaso'naG (5.4.131) */
if ($gender==="f" && sub(array("UDas","oDas"),array("+"),blank(0),0) && $_GET['cond2_17']==="1")
{
    $text = two(array("UDas","oDas"),array("+"),array("UDan","oDan"),array("+"),0); // for function two - please see function.php.
    echo "<p class = sa >By Udhaso'naG (".link_sutra("5.4.131").") :</p>\n";
    echo "<p class = sa >ऊधसोऽनङ्‌ (५.४.१३१) :</p>\n";        
    display(0);
    /* saGkhyAvyayAderGIp (4.1.26) */
    if ($gender==="f" && sub(array("UDan","oDan"),array("+"),blank(0),0) && $_GET['cond2_17_1']==="1")
    {
        $text = two(array("UDan","oDan"),array("+"),array("UDan","oDan"),array("+NIp+"),0); 
        echo "<p class = st >By saGkhyAvyayAderGIp (".link_sutra("4.1.26").") :</p>\n";
        echo "<p class = st >सङ्ख्याव्ययादेर्ङीप्‌ (४.१.२६) :</p>\n";        
        display(8);    
        $GIp=1;
        $it = array_merge($it,array("N","p"));
        $itprakriti = array_merge($itprakriti,array("N","p"));
        $text = one(array("an+"),array("n+"),0);
        echo "<p class = sa >By allopo'naH (".link_sutra("6.4.134").") :</p>\n";
        echo "<p class = sa >अल्लोपोऽनः (६.४.१३४) :</p>\n";
        display(6);    
        $allopo=1; // 0 - allopa has not happened. 1 - allopa has happened.
    }
    /* bahuvrIherUdhaso NIz (4.1.25) */
    if ($gender==="f" && sub(array("UDan","oDan"),array("+"),blank(0),0) && $_GET['cond2_17_1']==="2")
    {
        $text = two(array("UDan","oDan"),array("+"),array("UDan","oDan"),array("+NIz+"),0); 
        echo "<p class = st >By bahuvrIherUdhaso NIz (".link_sutra("4.1.25").") :</p>\n";
        echo "<p class = st >बहुव्रीहेरूधसो ङीष्‌ (४.१.२५) :</p>\n";        
        display(8);    
        $GIS=1;
        $it = array_merge($it,array("N","z"));
        $itprakriti = array_merge($itprakriti,array("N","z"));
        $text = one(array("an+"),array("n+"),0);
        echo "<p class = sa >By allopo'naH (".link_sutra("6.4.134").") :</p>\n";
        echo "<p class = sa >अल्लोपोऽनः (६.४.१३४) :</p>\n";
        display(6);    
        $allopo=1; // 0 - allopa has not happened. 1 - allopa has happened.
    }
}
/* striyAm (vA 3367) */
elseif ($gender!=="f" && sub(array("UDas","oDas"),array("+"),blank(0),0) )
{
    echo "<p class = sa >By striyAm (vA 3367) :</p>\n";
    echo "<p class = hn >This restricts application of Udhaso'naG in strI only :</p>\n";
    echo "<p class = sa >स्त्रियाम्‌ (वा ३३६७) :</p>\n";        
    echo "<p class = hn >इससे ऊधसोऽनङ्‌ का अनङ्ङादेश मात्र स्त्री विवक्षा में होता है । :</p>\n";        
    display(0);
}
/* ajAdyataSTAp (4.1.4) with explanation for inclusion in ajAdi gaNa */
// should we consider changing sub function to check exact word? Does this rule hold good in bahuvrIhi or not ?
//elseif ( (sub($ajAdi,array("+"),blank(0),0) || $ajAdyataSTAp===1) && $kevala!==1) 
elseif ( (in_array($fo,$ajAdi) || $ajAdyataSTAp===1) && $kevala!==1) 
{
    echo "<p class = st >ajAdyataSTAp (".link_sutra("4.1.4").") :</p>\n";
    echo "<p class = st >अजाद्यतष्टाप्‌ (४.१.४) :</p>\n";
/* displaying various sub part of ajAdi gaNa and relevant explanation for introduction of word in ajAdi gaNa */
    if( sub(array("aja","eqaka","aSva","cawaka","mUzika"),array("+"),blank(0),0))    
    {
    echo "<p class = hn >jAtilakSaNa GIS pratyaya is barred by this sUtra. </p>\n";
    echo "<p class = hn >जातिलक्षण ङीष्‌ प्रत्यय का बाध करने के लिए अजादि गण में इनका समावेश किया गया है ।</p>\n";
    }
    if( sub(array("triPala","tryanIka"),array("+"),blank(0),0) && $_GET['cond2_15']==="1")
    {
    echo "<p class = hn >This sUtra prevents application of NIp pratyaya by 'dvigoH'.</p>\n";
    echo "<p class = hn >'द्विगोः' से प्राप्त ङीप्‌ प्रत्यय का बाध करने के लिए अजादि गण में इनका समावेश किया गया है ।</p>\n";
    }
    if( sub(array("bAla","vatsa","hoQa","manda","bilAta","kanya"),array("+"),blank(0),0))
    {
    echo "<p class = hn >These have been enumerated in ajAdi gaNa to bar application of GIp pratyaya by 'vayasi prathame'.</p>\n";
    echo "<p class = hn >'वयसि प्रथमे' से प्राप्त ङीप्‌ प्रत्यय का बाध करने के लिए अजादि गण में इनका समावेश किया गया है ।</p>\n";        
    }    
    if( sub(array("kruYc","uzRih","devaviS","diS","dfS","kzuD","vAc","gir",),array("+"),blank(0),0))
    {
    echo "<p class = hn >These are not ending with akAra. Therefore included in ajAdi class for TAp pratyaya.</p>\n";
    echo "<p class = hn >अदन्तत्व नहीं होने के कारण टाप्‌ प्रत्यय के लिए समर्थ बनाने के लिए अजादि गण में इनका समावेश किया गया है ।</p>\n";        
    }    
    if( sub(array("jyezWa","kanizWa","maDyama"),array("+"),blank(0),0))
    {
    echo "<p class = hn >Even in puMyoga, this word takes TAp pratyaya.</p>\n";
    echo "<p class = hn >पुंयोग में भी टाप्‌ प्रत्यय के लिए समर्थ बनाने के लिए अजादि गण में इनका समावेश किया गया है ।</p>\n";        
    }    
    if( sub(array("kokila"),array("+"),blank(0),0))
    {
    echo "<p class = hn >Even in jAti, this word takes TAp pratyaya.</p>\n";
    echo "<p class = hn >जाति में भी टाप्‌ प्रत्यय के लिए समर्थ बनाने के लिए अजादि गण में इनका समावेश किया गया है ।</p>\n";        
    }    
    $text = last(array($so),array("+wAp+".$so),0);
    display(0); 
    $TAp = 1;
}

/* na SaTsvasrAdibhyaH (4.1.10) */
// bracketed because deals with strIpratyayas.
elseif ($shat===1 || in_array($fo,$svasrAdi))
{
    echo "<p class = st >By na SaTsvasrAdibhyaH (".link_sutra("4.1.10").") :</p>\n";
    echo "<p class = st >न षट्स्वस्रादिभ्यः (४.१.१०) :</p>\n";
    display(0); $Ap = 0; $GI = 0; $SaTsvasrAdi=1;
} 
/* striyAM ca (7.1.96) */
elseif ($gender === "f" && sub(array("krozwu"),array("+"),blank(0),0))
{
    $text = two(array("krozwu"),array("+"),array("krozwf"),array("+"),0); // for function two - please see function.php.
    echo "<p class = sa >By striyAM ca (".link_sutra("7.1.96").") :</p>\n";
    echo "<p class = sa >स्त्रियां च (७.१.९७) :</p>\n";        
    display(3);
}
/* pUtakratorai ca (4.1.36) */
elseif ($gender === "f" && $fo==="pUtakratu" && $_GET['cond2_22']==="1")
{
    $text = two(array("pUtakratu"),array("+"),array("pUtakratE"),array("+NIp+"),0);
    echo "<p class = st >By pUtakratorai ca (".link_sutra("4.1.36").") :</p>\n";
    echo "<p class = st >पूतक्रतोरै च (४.१.३६) :</p>\n";        
    display(8);
    $GIp=1;
}
/* vRSAkapyagnikusitakusidAnAmudAttaH (4.1.37) */
elseif ($gender === "f" && in_array($fo,array("vfzAkapi","agni","kusita","kusida")) && $_GET['cond2_22']==="1")
{
    $text = two(array("vfzAkapi","agni","kusita","kusida"),array("+"),array("vfzAkapE","agnE","kusitE","kusidE"),array("+NIp+"),0);
    echo "<p class = st >By vRSAkapyagnikusitakusidAnAmudAttaH (".link_sutra("4.1.37").") :</p>\n";
    echo "<p class = st >वृषाकप्यग्निकुसितकुसिदानामुदात्तः (४.१.३७) :</p>\n";        
    display(8);
    $GIp=1;
}
/* mano rau vA (4.1.38) */
elseif ($gender === "f" &&  $fo==="manu" && $_GET['cond2_22']==="1")
{
    $text = two(array("manu"),array("+"),array("manO"),array("+NIp+"),1);
    $text = two(array("manu"),array("+"),array("manE"),array("+NIp+"),1);
    echo "<p class = st >By mano rau vA (".link_sutra("4.1.38").") :</p>\n";
    echo "<p class = st >मनो रौ वा (४.१.३८) :</p>\n";        
    display(8);
    $GIp=1;
}
/* iyaM trisUtrI puMyoga eveSyate (vA 2449) */
elseif ($gender === "f" && in_array($fo,array("pUtakratu","vfzAkapi","agni","kusita","kusida","manu")) && $_GET['cond2_22']!=="1")
{
    echo "<p class = pa >By iyaM trisUtrI puMyoga eveSyate (vA 2449) :</p>\n";
    echo "<p class = hn >This vArtika prevents application of sUtras 4.1.36, 4.1.37 and 4.1.38.</p>\n";
    echo "<p class = pa >इयं त्रिसूत्री पुंयोग एवेष्यते (वा ४,१,३६) :</p>\n";        
    echo "<p class = hn >पुंयोगेतरे विवक्षिते अनेन वार्तिकेन त्रिसूत्रीबाधः । :</p>\n";        
    display(0);
}
/* varNAdanudAttAttopadhAtto naH (4.1.39) */
elseif ($gender === "f" && in_array($fo,array("eta","rohita",)) )
{
    $text = two(array("eta","rohita",),array("+"),array("ena","rohiRa",),array("+NIp+"),1);
    echo "<p class = st >By varNAdanudAttAttopadhAtto naH (".link_sutra("4.1.39").") :</p>\n";
    echo "<p class = st >वर्णादनुदात्तात्तोपधात्तो नः (४.१.३९) :</p>\n";        
    display(8);
    $GIp=1;
}
/* pizaGgAdupasaGkhyAnam (vA 2455) */
elseif ($gender === "f" && in_array($fo,array("piSaNga")) )
{
    $text = two(array("piSaNga",),array("+"),array("piSaNga",),array("+NIp+"),1);
    echo "<p class = st >By pizaGgAdupasaGkhyAnam (vA 2455) :</p>\n";
    echo "<p class = st >पिशङ्गादुपसङ्ख्यानम्‌ (वा २४५५) :</p>\n";        
    display(8);
    $GIp=1;
}
/* asitapalitayorna (vA 2453) and Candasi knameke (vA 2454) */
elseif ($gender === "f" && in_array($fo,array("asita","palita")) )
{
    $text = two(array("asita","palita"),array("+"),array("asikna","palikna"),array("+NIp+"),1);
    echo "<p class = st >By asitapalitayorna (vA 2453) and Candasi knameke (vA 2454) :</p>\n";
    echo "<p class = st >असितपलितयोर्न (वा २४५३) और छन्दसि क्नमेके (वा २४५४) :</p>\n";        
    display(8);
    $GIp=1;
}
/* anyato GIS (4.1.40) */
elseif ($gender === "f" && in_array($fo,array("kalmAza","sAraNga")) )
{
    $text = two(array("kalmAza","sAraNga"),array("+"),array("kalmAza","sAraNga"),array("+NIz+"),0);
    echo "<p class = st >By anyato GIS (".link_sutra("4.1.40").") :</p>\n";
    echo "<p class = st >अन्यतो ङीष्‌ (४.१.४०) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* jAnapadakuNDagoNasthalabhAjanAgakAlanIlakuzakAmukakabarAdvRttyamatrAvapanAkRtrimAzrANAsthaulyavarNAcCAdanAyovikAramaithunecCAkezavezeSu (4.1.42) */
elseif ($gender === "f" && sub(array("jAnapada","kuRqa","goRa","sTala","BAja","nAga","kAla","nIla","kuSa","kAmuka","kabara",),array("+"),blank(0),0) )
{
    $text = two(array("jAnapada","kuRqa","goRa","sTala","BAja","nAga","kAla","nIla","kuSa","kAmuka","kabara",),array("+"),array("jAnapada","kuRqa","goRa","sTala","BAja","nAga","kAla","nIla","kuSa","kAmuka","kabara",),array("+NIz+"),1);
    echo "<p class = st >By jAnapadakuNDagoNasthalabhAjanAgakAlanIlakuzakAmukakabarAdvRttyamatrAvapanAkRtrimAzrANAsthaulyavarNAcCAdanAyovikAramaithunecCAkezavezeSu (".link_sutra("4.1.42").") :</p>\n";
    echo "<p class = st >जानपदकुण्डगोलस्थलभाजनागकालनीलकुशकामुककबराद्वृत्यमत्रावपनाकृत्रिमाश्राणास्थौल्यवर्णानाच्छादनायोविकारमैथुनेच्छाकेशवेशेषु (४.१.४२) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* nIlAdauSadhau (vA 2456) and prANini ca (vA 2458) */
elseif ($gender === "f" && sub(array("nIla",),array("+NIz"),blank(0),0) )
{
    echo "<p class = st >By nIlAdauSadhau (vA 2456) and prANini ca (vA 2458) :</p>\n";
    echo "<p class = st >नीलादौषधौ (वा २४५६) और प्राणिनि च (वा २४५८) :</p>\n";        
    display(8);
}
/* zoNAtprAcAm (4.1.43) */
elseif ($gender === "f" && sub(array("SoRa"),array("+"),blank(0),0) )
{
    $text = two(array("SoRa"),array("+"),array("SoRa"),array("+NIz+"),1);
    echo "<p class = st >By zoNAtprAcAm (".link_sutra("4.1.43").") :</p>\n";
    echo "<p class = st >शोणात्प्राचाम्‌ (४.१.४३) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* sakhyazizvIti bhASAyAm (4.1.62) */
elseif ($gender === "f" && in_array($fo,array("saKi","aSiSu")) )
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By sakhyazizvIti bhASAyAm (".link_sutra("4.1.62").") :</p>\n";
    echo "<p class = st >सख्यशिश्वीति भाषायाम्‌ (४.१.६२) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* ito manuSyajAteH (4.1.65) */
elseif ($gender === "f" && sub(array("i"),array("+"),blank(0),0) && AdivRddhi($text) )
{
    $text = two($itmanuSyajAti,array("+"),$itmanuSyajAti,array("+NIz+"),0);
    $text = two($itmanuSyajAti,array("+NIz+NIz+"),$itmanuSyajAti,array("+NIz+"),0);
    echo "<p class = st >By ito manuSyajAteH (".link_sutra("4.1.65").") :</p>\n";
    echo "<p class = st >इतो मनुष्यजातेः (४.१.६५) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* bahvAdibhyazca (4.1.45), kRdikArAdaktinaH (ga 50) and sarvato'ktinnarthAdityeke (ga 51) */
elseif ($gender === "f" && $_GET['cond2_21']==="2" && !sub(array("patn"),array("+"),blank(0),0) )
{
    $text = two(array("i"),array("+"),array("i"),array("+NIz+"),1);
    echo "<p class = st >By bahvAdibhyazca (".link_sutra("4.1.45")."), kRdikArAdaktinaH (ga 50) :</p>\n";
    echo "<p class = st >बह्वादिभ्यश्च (४.१.४५), कृदकारादक्तिनः (ग ५०) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* bahvAdibhyazca (4.1.45), kRdikArAdaktinaH (ga 50) and sarvato'ktinnarthAdityeke (ga 51) */
elseif ($gender === "f" && $_GET['cond2_21']==="2" && sub(array("Sakawi","aSani","AtmamBari"),array("+"),blank(0),0) )
{
    $text = two(array("i"),array("+"),array("i"),array("+NIz+"),1);
    echo "<p class = st >By bahvAdibhyazca (".link_sutra("4.1.45")."), kRdikArAdaktinaH (ga 50) and sarvato'ktinnarthAdityeke (ga 51) :</p>\n";
    echo "<p class = st >बह्वादिभ्यश्च (४.१.४५), कृदकारादक्तिनः (ग ५०) तथा सर्वतोऽक्तिन्नर्थादित्येके (ग ५१) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* bahvAdibhyazca (4.1.45) */
elseif ($gender === "f" && sub($bahvAdi,array("+"),blank(0),0) )
{
    $text = two($bahvAdi,array("+"),$bahvAdi,array("+NIz+"),1);
    echo "<p class = st >By bahvAdibhyazca (".link_sutra("4.1.45").") :</p>\n";
    echo "<p class = st >बह्वादिभ्यश्च (४.१.४५) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* pAlakAntAnna (vA 2461) */
elseif ($gender === "f" && $_GET['cond2_22']==="1" && sub(array("pAlaka"),array("+"),blank(0),0) )
{
    echo "<p class = sa >By pAlakAntAnna (vA 2461) :</p>\n";
    echo "<p class = sa >पालकान्तान्न (वा २४६१) :</p>\n";        
    display(0);
}
/* paGgozca (4.1.67) */
elseif ($gender === "f" && sub(array("paNgu"),array("+"),blank(0),0) )
{
    $text = two(array("paNgu"),array("+"),array("paNgU"),array("+"),0);
    echo "<p class = st >By paGgozca (".link_sutra("4.1.67").") :</p>\n";
    echo "<p class = st >पङ्गोश्च (४.१.६७) :</p>\n";        
    display(8);
    $ajAdyataSTAp=0;
}
/* aprANijAtezcArajjvAdInAmupasaGkhyAnam (vA 2502) */
elseif ($gender === "f" && sub(array("alAbU","karkanDU"),array("+"),blank(0),0) && !sub(array("yu"),array("+"),blank(0),0) && $_GET['cond2_22']==="2")
{
    echo "<p class = st >By aprANijAtezcArajjvAdInAmupasaGkhyAnam (vA 2502) :</p>\n";
    echo "<p class = st >अप्राणिजातेश्चारज्ज्वादीनामुपसङ्ख्यानम्‌ (वा २५०२) :</p>\n";        
    display(8);
    $ajAdyataSTAp=0;
}
/* saJjJAyAm (4.1.72) */
elseif ($gender === "f" && sub(array("kadru","kamaRqalu"),array("+"),blank(0),0) )
{
    $text = two(array("kadru","kamaRqalu"),array("+"),array("kadrU","kamaRqalU"),array("+"),1);
    echo "<p class = st >By saJjJAyAm (".link_sutra("4.1.72").") :</p>\n";
    echo "<p class = st >सञ्ज्ञायाम्‌ (४.१.७२) :</p>\n";        
    display(8);
    $ajAdyataSTAp=0;
}
/* kharusaMyogopadhAnna (vA 2460) */
elseif ($gender == "f" && (sub(array("Karu"),array("+"),blank(0),0) || sub($hl,$hl,array("u+"),0) ) )
{
    echo "<p class = st >By kharusaMyogopadhAnna (vA 2460) :</p>\n";
    echo "<p class = st >खरुसंयोगोपधान्न (वा २४६०) :</p>\n";        
    display(8);
}
/* voto guNavacanAt (4.1.44) */
elseif ($gender === "f" && $_GET['cond2_20']==="1" )
{
    $text = two(array("u"),array("+"),array("u"),array("+NIz+"),1);
    echo "<p class = st >By voto guNavacanAt (".link_sutra("4.1.44").") :</p>\n";
    echo "<p class = st >वोतो गुणवचनात्‌ (४.१.४४) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* sUryAddevatAyAM cAbvAcyaH (vA 2471) */
elseif ($gender === "f" && $_GET['cond2_22_1_1']==="1" )
{
    $text = one(array("sUrya+"),array("sUrya+cAp+"),0);
    echo "<p class = st >By sUryAddevatAyAM cAbvAcyaH (vA 2471) :</p>\n";
    echo "<p class = st >सूर्याद्देवतायां चाब्वाच्यः (वा २४७१) :</p>\n";        
    display(8);
    $cAp=1;
    $ajAdyataSTAp=0;
}
/* mAtulopAdDyAyayorAnugvA (vA) and yA tu svayamevAddhyApikA tatra vA NIz vAcyaH (vA) */
elseif ($gender === "f" && sub(array("mAtula",),array("+"),blank(0),0) )
{
    $text = two(array("mAtula",),array("+"),array("mAtulAn",),array("+NIz+"),1);
    $text = two(array("mAtula",),array("+"),array("mAtula",),array("+NIz+"),0);
    echo "<p class = st >By indravaruNabhavazarvarudramRDahimAraNyayavayavanamAtulAcAryANAmAnuk (".link_sutra("4.1.41").") :</p>\n";
    echo "<p class = pa >By mAtulopAdDyAyayorAnugvA (vA) :</p>\n";
    echo "<p class = st >इन्द्रवरुणभवशर्वरुद्रमृडहिमारण्ययवयवनमातुलाचार्याणामानुक्‌ (४.१.४९) :</p>\n";        
    echo "<p class = pa >मातुलोपाद्ध्याययोरानुग्वा (वा) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* mAtulopAdDyAyayorAnugvA (vA) and yA tu svayamevAddhyApikA tatra vA NIz vAcyaH (vA) */
elseif ($gender === "f" && sub(array("upAdDyAya","upADyAya"),array("+"),blank(0),0) )
{
    $text = two(array("upAdDyAya","upADyAya"),array("+"),array("upAdDyAyAn","upADyAyAn"),array("+NIz+"),1);
    $text = two(array("upAdDyAya","upADyAya"),array("+"),array("upAdDyAya","upADyAya"),array("+NIz+"),0);
    $text = two(array("upAdDyAya","upADyAya"),array("+NIz+"),array("upAdDyAya","upADyAya"),array("+"),1);
    echo "<p class = st >By indravaruNabhavazarvarudramRDahimAraNyayavayavanamAtulAcAryANAmAnuk (".link_sutra("4.1.41").") :</p>\n";
    echo "<p class = pa >By mAtulopAdDyAyayorAnugvA (vA) and yA tu svayamevAddhyApikA tatra vA NIz vAcyaH (vA) :</p>\n";
    echo "<p class = st >इन्द्रवरुणभवशर्वरुद्रमृडहिमारण्ययवयवनमातुलाचार्याणामानुक्‌ (४.१.४९) :</p>\n";        
    echo "<p class = pa >मातुलोपाद्ध्याययोरानुग्वा (वा) और या तु स्वयमेवाद्ध्यापिका तत्र वा ङीष्‌ वाच्यः (वा) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* aryakSatriyAbhyAM vA (vA 2478) */
elseif ($gender === "f" && sub(array("arya","kzatriya"),array("+"),blank(0),0) && $_GET['cond2_22']!=="1" )
{
    $text = two(array("arya","kzatriya"),array("+"),array("aryAn","kzatriyAn"),array("+NIz+"),1);
    echo "<p class = st >By aryakSatriyAbhyAM vA (vA 2478) :</p>\n";
    echo "<p class = st >अर्यक्षत्रियाभ्यां वा (वा २४७८) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* indravaruNabhavazarvarudramRDahimAraNyayavayavanamAtulAcAryANAmAnuk (4.1.41) */
elseif ($gender === "f" && sub(array("indra","varuRa","Bava","Sarva","rudra","mfqa","mAtula","AcArya"),array("+"),blank(0),0) && $_GET['cond2_22']==="1")
{
    $text = two(array("indra","varuRa","Bava","Sarva","rudra","mfqa","mAtula","AcArya"),array("+"),array("indrAn","varuRAn","BavAn","SarvAn","rudrAn","mfqAn","mAtulAn","AcAryAn"),array("+NIz+"),0);
    echo "<p class = st >By indravaruNabhavazarvarudramRDahimAraNyayavayavanamAtulAcAryANAmAnuk (".link_sutra("4.1.41").") :</p>\n";
    echo "<p class = st >इन्द्रवरुणभवशर्वरुद्रमृडहिमारण्ययवयवनमातुलाचार्याणामानुक्‌ (४.१.४९) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* himAraNyayormahattve (vA 2472) */
elseif ($gender === "f" && sub(array("hima","araRya",),array("+"),blank(0),0) )
{
    $text = two(array("hima","araRya",),array("+"),array("himAn","araRyAn",),array("+NIz+"),1);
    echo "<p class = st >By himAraNyayormahattve (vA 2472) :</p>\n";
    echo "<p class = st >हिमारण्ययोर्महत्त्वे (वा २४७२) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* yavAddoSe (vA 2473) */
elseif ($gender === "f" && sub(array("yava",),array("+"),blank(0),0) )
{
    $text = two(array("yava",),array("+"),array("yavAn",),array("+NIz+"),1);
    echo "<p class = st >By yavAddoSe (vA 2473) :</p>\n";
    echo "<p class = st >यवाद्दोषे (वा २४७३) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* yavanAllipyAm (vA 2474) */
elseif ($gender === "f" && sub(array("yavana",),array("+"),blank(0),0) )
{
    $text = two(array("yavana",),array("+"),array("yavanAn",),array("+NIz+"),1);
    echo "<p class = st >By yavanAllipyAm (vA 2474) :</p>\n";
    echo "<p class = st >यवनाल्लिप्याम्‌ (वा २४७४) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* zvazurasyokArAkAralopazca (vA 5039) */
elseif ($gender === "f" && sub(array("SvaSura"),array("+"),blank(0),0) )
{
    $text = two(array("SvaSura"),array("+"),array("SvaSrU"),array("+"),0);
    echo "<p class = st >By zvazurasyokArAkAralopazca (vA 5039) :</p>\n";
    echo "<p class = st >श्वशुरस्योकाराकारलोपश्च (वा ५०३९) :</p>\n";        
    display(8);
    $ajAdyataSTAp=0;
}
/* urUttarapadAdaupamye (4.1.69) */
elseif ($gender === "f" && sub(array("karaBoru"),array("+"),blank(0),0) )
{
    $text = two(array("karaBoru"),array("+"),array("karaBorU"),array("+"),0);
    echo "<p class = st >By urUttarapadAdaupamye (".link_sutra("4.1.69").") :</p>\n";
    echo "<p class = st >उरूत्तरपदादौपम्ये (४.१.६९) :</p>\n";        
    display(8);
    $ajAdyataSTAp=0;
}
/* saMhitazaphalakSaNavAmAdezca (4.1.70) */
elseif ($gender === "f" && sub(array("saMhitoru","SaPoru","lakzaRoru","vAmoru"),array("+"),blank(0),0) )
{
    $text = two(array("saMhitoru","SaPoru","lakzaRoru","vAmoru"),array("+"),array("saMhitorU","SaPorU","lakzaRorU","vAmorU"),array("+"),0);
    echo "<p class = st >By saMhitazaphalakSaNavAmAdezca (".link_sutra("4.1.70").") :</p>\n";
    echo "<p class = st >संहितशफलक्षणवामादेश्च (४.१.७०) :</p>\n";        
    display(8);
    $ajAdyataSTAp=0;
}
/* sahitasahAbhyAM ceti vaktavyam (vA 2503) */
elseif ($gender === "f" && sub(array("sahitoru","sahoru",),array("+"),blank(0),0) )
{
    $text = two(array("sahitoru","sahoru",),array("+"),array("sahitorU","sahorU",),array("+"),0);
    echo "<p class = st >By sahitasahAbhyAM ceti vaktavyam (vA 2503) :</p>\n";
    echo "<p class = st >सहितसहाभ्यां एति वक्तव्यम्‌ (वा २५०३) :</p>\n";        
    display(8);
    $ajAdyataSTAp=0;
}
/* puMyogAdAkhyAyAm (4.1.48) */
elseif ($gender === "f" && ($_GET['cond2_22']==="1" || sub(array("mahASUdra"),array("+"),blank(0),0) ))
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By puMyogAdAkhyAyAm (".link_sutra("4.1.48").") :</p>\n";
    echo "<p class = st >पुंयोगादाख्यायाम्‌ (४.१.४८) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* kvacinna (vA) */
elseif ($gender === "f" && sub(array("DanakrIta"),array("+"),blank(0),0) )
{
    echo "<p class = st >By kvacinna (vA) :</p>\n";
    echo "<p class = st >क्वचिन्न (वा) :</p>\n";        
    display(8);
}
/* krItAtkaraNapUrvAt (4.1.50) */
elseif ($gender === "f" && sub(array("akrIta"),array("+"),blank(0),0) )
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By krItAtkaraNapUrvAt (".link_sutra("4.1.50").") :</p>\n";
    echo "<p class = st >क्रीतात्करणपूर्वात्‌ (४.१.५०) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* ktAdalpAkhyAyAm (4.1.51) */
elseif ($gender === "f" && $_GET['cond2_23_1']==="1" )
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By ktAdalpAkhyAyAm (".link_sutra("4.1.51").") :</p>\n";
    echo "<p class = st >क्तादल्पाख्यायाम्‌ :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* jAtipUrvAditi vaktavyam (vA 2484) */
elseif ($gender === "f" && $_GET['cond2_23_1_2']==="1" && ends(array($fo),$bahvAdi,3))
{
    echo "<p class = st >By jAtipUrvAditi vaktavyam (vA 2484) :</p>\n";
    echo "<p class = hn >This vArtika prevents application of bahuvrIhezcAntodAttAt (".link_sutra("4.1.52")."). :</p>\n";
    echo "<p class = st >जातिपूर्वादिति वक्तव्यम्‌ (वा २४८४) :</p>\n";        
    echo "<p class = hn >बहुव्रीहेश्चान्तोदात्तादित्यस्य अपवादः ।</p>\n";
    display(8);
}
/* jAtAntAnna (vA 2479) */
elseif ($gender === "f" && $_GET['cond2_23_1_2']==="1" && sub(array("jAta"),array("+"),blank(0),0))
{
    echo "<p class = st >By jAtAntAnna (vA 2479) :</p>\n";
    echo "<p class = st >जातान्तान्न (वा २४७९) :</p>\n";        
    display(8);
}
/* pANigRhitI bhAryAyAm (vA 2480) */
elseif ($gender === "f" && $_GET['cond2_23_1_2']==="1" && sub(array("pARigfhita"),array("+"),blank(0),0))
{
    $text = one(array("+"),array("+NIz+"),1);
    echo "<p class = st >By pANigRhitI bhAryAyAm (vA 2480) :</p>\n";
    echo "<p class = st >पाणिगृहिती भार्यायाम्‌ (वा २४८०) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=1;
}
/* asvAGgapUrvapadAdvA (4.1.53) */
elseif ($gender === "f" && $_GET['cond2_23_1_2']==="1" && !ends(array($fo),$svAGga,3))
{
    $text = one(array("+"),array("+NIz+"),1);
    echo "<p class = st >By asvAGgapUrvapadAdvA (".link_sutra("4.1.53").") :</p>\n";
    echo "<p class = st >अस्वाङ्गपूर्वपदाद्वा (४.१.५३) :</p>\n";        
    display(8);
    $GIS=1;
}
/* bahuvrIhezcAntodAttAt (4.1.52) */
elseif ($gender === "f" && $_GET['cond2_23_1_2']==="1" )
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By bahuvrIhezcAntodAttAt (".link_sutra("4.1.52").") :</p>\n";
    echo "<p class = st >बहुव्रीहेश्चान्तोदात्तात्‌ (४.१.५२) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* kabaramaNiviSazarebhyo nityam (vA 2490) */
elseif ($gender === "f" && ends(array($fo),array("kabarapucCa","maRipucCa","vizapucCa","SarapucCa"),1))
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By kabaramaNiviSazarebhyo nityam (vA 2490) :</p>\n";
    echo "<p class = st >कबरमणिविषशरेभ्यो नित्यम्‌ (वा २४९०) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* upamAnAtpakSAcca pucCAcca (vA 2491) */
elseif ($gender === "f" && ends(array($fo),array("ulUkapucCa","ulUkapakza",),1))
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By upamAnAtpakSAcca pucCAcca (vA 2491) :</p>\n";
    echo "<p class = st >उपमानात्पक्षाच्च पुच्छाच्च (वा २४९१) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* pucCAcca (vA 2489) */
elseif ($gender === "f" && ends(array($fo),array("pucCa"),0))
{
    $text = one(array("+"),array("+NIz+"),1);
    echo "<p class = st >By pucCAcca (vA 2489) :</p>\n";
    echo "<p class = st >पुच्छाच्च (वा २४८९) :</p>\n";        
    display(8);
    $GIS=1;
}
/* vArtikas to ajAdyataSTAp */
/* sambhastrAjinaSaNapiNDebhyaH phalAt (vA 2497) */
elseif( sub(array("samPala","BastraPala","ajinaPala","SaRaPala","piRqaPala"),array("+"),blank(0),0))
{
    $text = one(array("+"),array("+wAp+"),0);
    echo "<p class = st >By sambhastrAjinaSaNapiNDebhyaH phalAt (vA 2497) :</p>\n";
    echo "<p class = hn >This prevents GIS pratyaya of 'pAkakarNa...'</p>\n";
    echo "<p class = st >सम्भस्राजिनशणपिण्डेभ्यः फलात्‌ (वा २४९७) :</p>\n";        
    echo "<p class = hn >'पाककर्ण' इति ङीष्‌ न भवति, किन्तु टाबेव ।</p>\n";        
    display(0); $ajAdyataSTAp = 1;
    $TAp=1;
}
/* sadackARqaprAntaSataikebhyaH puSpAt (vA 1496) */
elseif( sub(array("satpuzpa","prAkpuzpa","pratyakpuzpa","kARqapuzpa","prAntapuzpa","Satapuzpa","ekapuzpa"),array("+"),blank(0),0))
{
    $text = one(array("+"),array("+wAp+"),0);
    echo "<p class = st >By sadackARqaprAntaSataikebhyaH puSpAt (vA 1496) :</p>\n";
    echo "<p class = hn >This prevents GIS pratyaya of 'pAkakarNa...'</p>\n";
    echo "<p class = st >सदच्काण्डप्रान्तशतैकेभ्यः पुष्पात्‌ (वा १४९६) :</p>\n";        
    echo "<p class = hn >'पाककर्ण' इति ङीष्‌ न भवति, किन्तु टाबेव ।</p>\n";        
    display(0); $ajAdyataSTAp = 1;
    $TAp=1;
}
/* zUdrA cAmahatpUrvA jAtiH (vA 2400-2401) */
elseif ($_GET['cond2_22']==="2" && sub(array("SUdra"),array("+"),blank(0),0) && !sub(array("mahASUdra","mahacCUdra"),array("+"),blank(0),0))
{
    $text = one(array("+"),array("+wAp+"),0);
    echo "<p class = st >By zUdrA cAmahatpUrvA jAtiH (vA 2400-2401) :</p>\n";
    echo "<p class = st >शूद्रा चामहत्पूर्वा जातिः (वा २४००-२४०१) :</p>\n";        
    display(0); $ajAdyataSTAp = 1;    
    $TAp=1;
}
/* sadackARqaprAntaSataikebhyaH puSpAt (vA 1496) */
elseif( sub(array("satpuzpa","prAkpuzpa","pratyakpuzpa","kARqapuzpa","prAntapuzpa","Satapuzpa","ekapuzpa"),array("+"),blank(0),0))
{
    $text = one(array("+"),array("+wAp+"),0);
    echo "<p class = st >sadackARqaprAntaSataikebhyaH puSpAt (vA 1496) :</p>\n";
    echo "<p class = st >सदच्काण्डप्रान्तशतैकेभ्यः पुष्पात्‌ (वा १४९६) :</p>\n";        
    display(0); $ajAdyataSTAp = 1;
    $TAp=1;
}
/* mUlAnnaJaH (vA 2500) */
elseif( sub(array("amUla"),array("+"),blank(0),0) && $fo==="amUla")
{
    $text = one(array("+"),array("+wAp+"),0);
    echo "<p class = st >mUlAnnaJaH (vA 2500) :</p>\n";
    echo "<p class = st >मूलान्नञः (वा २५००) :</p>\n";        
    display(0); $ajAdyataSTAp = 1;
    $TAp=1;
}
/* pAkakarNaparNapuSpaphalamUlavAlottarapadAcca (4.1.64) */
elseif ($gender === "f" && ends(array($fo),array("pAka","karRa","parRa","puzpa","Pala","mUla","vAla"),0))
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By pAkakarNaparNapuSpaphalamUlavAlottarapadAcca (".link_sutra("4.1.64").") :</p>\n";
    echo "<p class = st >पाककर्णपर्णपुष्पमूलवालोत्तरपदाच्च (४.१.६४) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* nAsikodaroSThajaGghAdantakarNazRGgAcca (4.1.55) */
elseif ($gender === "f" && ends(array($fo),array("nAsika","nAsikA","udara","ozWa","jaNGa","jaNGA","danta","karRa","SfNga","odara","OzWa"),0))
{
    $text = one(array("+"),array("+NIz+"),1);
    echo "<p class = st >By nAsikodaroSThajaGghAdantakarNazRGgAcca (".link_sutra("4.1.55").") :</p>\n";
    echo "<p class = st >नासिकोदरोष्ठजङ्घादन्तकर्णशृङ्गाच्च (४.१.५५) :</p>\n";        
    display(8);
    $GIS=1;
}
/* na kroDAdibahvacaH (4.1.56) */
elseif ($gender === "f" && ends(array($fo),$kroDAdi,1))
{
    echo "<p class = st >By na kroDAdibahvacaH (".link_sutra("4.1.56").") :</p>\n";
    echo "<p class = st >न क्रोडादिबह्वचः (४.१.५६) :</p>\n";        
    display(8);
}
/* na kroDAdibahvacaH (4.1.56) */
elseif ($gender === "f" && ends(array($fo),$bahvacasvAGga,0))
{
    echo "<p class = st >By na kroDAdibahvacaH (".link_sutra("4.1.56").") :</p>\n";
    echo "<p class = st >न क्रोडादिबह्वचः (४.१.५६) :</p>\n";        
    display(8);
}
/* nakhamukhAtsaJjJAyAm (4.1.58) */
elseif ($gender === "f" && $_GET['cond2_24']==="1")
{
    echo "<p class = st >By nakhamukhAtsaJjJAyAm (".link_sutra("4.1.58").") :</p>\n";
    echo "<p class = st >नखमुखात्सञ्ज्ञायाम्‌ (४.१.५८) :</p>\n";        
    display(8);
}
/* vAhaH (4.1.61) */
elseif ($gender === "f" && sub(array("vAh"),array("+"),blank(0),0) )
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By vAhaH (".link_sutra("4.1.61").") :</p>\n";
    echo "<p class = st >वाहः (४.१.६१) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* sahanaJvidyamAnapUrvAcca (4.1.57) */
elseif ($gender === "f" && sub(array("saha","sa","a","an","vidyamAna"),$svAGga,array("+"),0) && arr($text,'/^[sanv]/'))
{
    echo "<p class = st >By na sahanaJvidyamAnapUrvAcca (".link_sutra("4.1.57").") :</p>\n";
    echo "<p class = st >न सहनञ्विद्यमानपूर्वाच्च (४.१.५७) :</p>\n";        
    display(8);
}
/* dikpUrvapadAnGIp (4.1.60) */
elseif ($gender === "f" && sub(array("prAk","pratyak","udIk","prAN","pratyaN","udIN","prAg","pratyag","udIg","pUrva","paScima","uttara","dakziRa","aDara","avara"),$svAGga,array("+"),0) )
{
    $text = one(array("+"),array("+NIp+"),0);
    echo "<p class = st >By dikpUrvapadAnGIp (".link_sutra("4.1.60").") :</p>\n";
    echo "<p class = st >दिक्पूर्वपदान्ङीप्‌ (४.१.६०) :</p>\n";        
    display(8);
    $GIp=1;
    $GIS=0;
    $ajAdyataSTAp=0;
}
/* svAGgAccopasarjanAdasaMyogopadhAt (4.1.54) */
// In this section, we enter only those words which qualify the above criteria to svAGga array.
elseif ($gender === "f" && ends(array($fo),$svAGga,0))
{
    $text = one(array("+"),array("+NIz+"),1);
    echo "<p class = st >By svAGgAccopasarjanAdasaMyogopadhAt (".link_sutra("4.1.54").") :</p>\n";
    echo "<p class = pa >adravaM mUrtimatsvAGgaM prANisthamavikArajam | atatsthaM tatra dRSTaM ca tena cettattathAyutam ||</p>\n";
    echo "<p class = st >स्वाङ्गाच्चोपसर्जनादसंयोगोपधात्‌ (४.१.५४) :</p>\n";        
    echo "<p class = pa >अद्रवं मूर्तिमत्स्वाङ्गं प्राणिस्थमविकारजम्‌ । अतत्स्थं तत्र दृष्टं च तेन चेत्तत्तथायुतम्‌ ॥</p>\n";        
    display(8);
    $GIS=1;
}
/* UGutaH (4.1.66) */
elseif ($gender === "f" && sub(array("u"),array("+"),blank(0),0) && !sub(array("yu"),array("+"),blank(0),0) && $_GET['cond2_22']==="2")
{
    $text = two(array("u"),array("+"),array("U"),array("+"),0);
    echo "<p class = st >By UGutaH (".link_sutra("4.1.66").") :</p>\n";
    echo "<p class = st >ऊङुतः (४.१.६६) :</p>\n";        
    display(8);
    $ajAdyataSTAp=0;
}
/* bAhvantAtsaJjJAyAm (4.1.67) */
elseif ($gender === "f" && $_GET['cond2_26']==="1")
{
    $text = two(array("bAhu"),array("+"),array("bAhU"),array("+"),0);
    echo "<p class = st >By bAhvantAtsaJjJAyAm (".link_sutra("4.1.67").") :</p>\n";
    echo "<p class = st >बाह्वन्तात्सञ्ज्ञायाम्‌ (४.१.६७) :</p>\n";        
    display(8);
    $ajAdyataSTAp=0;
}
/* zArGgaravAdyaJo GIn (4.1.73) */
// zARGgaravAdi is properly treated. aJ-ending words are still to be incorporated.
elseif ($gender === "f" && $_GET['cond2_22']==="2" && in_array($fo,$zArGgaravAdi) )
{
    $text = one(array("+"),array("+NIn+"),0);
    echo "<p class = st >By zArGgaravAdyaJo GIn (".link_sutra("4.1.73").") :</p>\n";
    echo "<p class = st >शार्ङ्गरवाद्यञो ङीन्‌ (४.१.७३) :</p>\n";        
    display(8);
    $GIn=1;
    $ajAdyataSTAp=0;
}
/* nRnarayorvRddhizca (ga 54) */
elseif ($gender === "f" && $_GET['cond2_22']==="2" && in_array($fo,array("nf","nara")) )
{
    $text = one(array("nf+","nara+"),array("nAr+NIn+","nAra+NIn+"),0);
    echo "<p class = st >By nRnarayorvRddhizca (ga 54) :</p>\n";
    echo "<p class = st >नृनरयोर्वृद्धिश्च (ग ५४) :</p>\n";        
    display(8);
    $GIn=1;
    $ajAdyataSTAp=0;
}
/* SAdyaJazcAbvAcyaH (vA 2505) */
elseif ($gender === "f" && $_GET['cond2_22']==="2" && sub(array("zya"),array("+"),blank(0),0) && AdivRddhi($text))
{
    $text = one(array("+"),array("+cAp+"),0);
    echo "<p class = st >By SAdyaJazcAbvAcyaH (vA 2505) :</p>\n";
    echo "<p class = st >षाद्ययञश्चाब्वाच्यः (वा २५०५) :</p>\n";        
    display(8);
    $cAp=1;
    $ajAdyataSTAp=0;
}
/* AvaTyAcca (4.1.75) */ 
elseif ( $gender==="f" && $_GET['cond2_14']==='2' && sub(array("Avawya",),array("+"),$sup,0) )
{
    $text = two(array("ya"),array("+"),array("ya"),array("+cAp+"),0);
    echo "<p class = st >By AvaTyAcca (".link_sutra("4.1.75").") :</p>\n";
    echo "<p class = st >आवट्याच्च (४.१.७५) :</p>\n";        
    display(8);
    $cAp=1; $ajAdyataSTAp = 0;
}
/* yUnastiH (4.1.77) */ 
elseif ($gender==="f" && sub(array("yuvan",),array("+"),$sup,0) )
{
    $text = two(array("yuvan"),array("+"),array("yuvati"),array("+"),0);
    echo "<p class = ta >By yUnastiH (".link_sutra("4.1.77").") :</p>\n";
    echo "<p class = st >By striyAm (".link_sutra("4.1.3").") :</p>\n";
    echo "<p class = ta >यूनस्तिः (४.१.७७) :</p>\n";        
    echo "<p class = st >स्त्रियाम्‌ (४.१.३) :</p>\n";        
    display(8);
    $tiH=1; $ajAdyataSTAp = 0;
    $taddhita=1;
}
/* yaGazcAp (4.1.74) */
// bad patch.
elseif ($gender === "f" && $_GET['cond2_22']==="2" && sub(array("ya"),array("+"),blank(0),0) && AdivRddhi($text))
{
    $text = one(array("+"),array("+cAp+"),0);
    echo "<p class = st >By yaGazcAp (".link_sutra("4.1.74").") :</p>\n";
    echo "<p class = st >यङश्चाप्‌ (४.१.७४) :</p>\n";        
    display(8);
    $cAp=1;
    $ajAdyataSTAp=0;
}
/* aparimANabistAcitakambaLyebhyo na taddhitaluki (4.1.22) */
elseif ($gender==="f" && sub(array("a"),array("+"),blank(0),0) && dvigu(array($fo))  && $_GET['cond2_16_1_1']==="1" && !sub(array("taya"),array("+"),blank(0),0))
{
    echo "<p class = st >By aparimANabistAcitakambaLyebhyo na taddhitaluki (".link_sutra("4.1.22").") :</p>\n";
    echo "<p class = st >अपरिमाणबिस्ताचितकम्बळ्येभ्यो न तद्धितलुकि (४.१.२२) :</p>\n";        
    display(8);
}
/* kANDAtkSetre (4.1.23) */
elseif ($gender==="f" &&  $_GET['cond2_16_1_2']==="1")
{
    echo "<p class = st >By kANDAtkSetre (".link_sutra("4.1.23").") :</p>\n";
    echo "<p class = st >काण्डात्क्षेत्रे (४.१.२३) :</p>\n";        
    display(8); $ajAdyataSTAp = 0;
    $GIp=1;
}
/* puruSAtpramANe'nyatarasyAm (4.1.24) */
elseif ($gender==="f" &&  $_GET['cond2_16_1_3']==="1")
{
    $text = two(array("a"),array("+"),array("a"),array("+NIp+"),1);
    echo "<p class = st >By puruSAtpramANe'nyatarasyAm (".link_sutra("4.1.24").") :</p>\n";
    echo "<p class = st >पुरुषात्प्रमाणेऽन्यतरस्याम्‌ (४.१.२४) :</p>\n";        
    display(8); $ajAdyataSTAp = 0;
    $GIp=1;
}
/* dvigoH (4.1.21) */
elseif ($gender==="f" && sub(array("a"),array("+"),blank(0),0) && dvigu(array($fo))  && $_GET['cond2_15']!=="1" && $_GET['cond2_16']!=="2" && !sub(array("taya"),array("+"),blank(0),0) && $_GET['cond2_16_1_2']!=="1" && $_GET['cond2_16_1_3']!=="1")
{
    $text = two(array("a"),array("+"),array("a"),array("+NIp+"),0);
    echo "<p class = st >By dvigoH (".link_sutra("4.1.21").") :</p>\n";
    echo "<p class = st >द्विगोः (४.१.२१) :</p>\n";        
    display(8); $ajAdyataSTAp = 0;
    $GIp=1;
}
/* jAterastrIviSayAdayopadhAt (4.1.63) and yopadhapratiSedhe hayagavayamukayamanuSyamatsyAnAmapratiSedhaH (vA 2495) */
elseif ($gender === "f" && in_array($fo,array("haya","gavaya","mukaya","manuzya","matsya")))
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By jAterastrIviSayAdayopadhAt (".link_sutra("4.1.63").") and yopadhapratiSedhe hayagavayamukayamanuSyamatsyAnAmapratiSedhaH (vA 2495) :</p>\n";
    echo "<p class = st >जातेरस्त्रीविषयात्‌ (४.१.६३) और योपधप्रतिषेधे हयगवयमुकयमनुष्यमत्स्यानामप्रतिषेधः (वा २४९५) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
    if(in_array($fo,array("manuzya","matsya")))
    {
        $taddhita=1;
    }
}
/* jAterastrIviSayAdayopadhAt (4.1.63) */
elseif ($gender === "f" && $_GET['cond2_22']==="2" && !sub(array("y"),$ac,array("+"),0) )
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By jAterastrIviSayAdayopadhAt (".link_sutra("4.1.63").") :</p>\n";
    echo "<p class = st >जातेरस्त्रीविषयात्‌ (४.१.६३) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* jAterastrIviSayAdayopadhAt (4.1.63) */
elseif ($gender === "f" && in_array($fo,$aupagava) )
{
    $text = one(array("+"),array("+NIz+"),0);
    echo "<p class = st >By jAterastrIviSayAdayopadhAt (".link_sutra("4.1.63").") :</p>\n";
    echo "<p class = st >जातेरस्त्रीविषयात्‌ (४.१.६३) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* SidgaurAdibhyazca (4.1.41) */
elseif ($gender === "f" && sub($Sidwords,array("+"),blank(0),0) && $_GET['cond2_22']!=="2")
{
    $text = two($Sidwords,array("+"),$Sidwords,array("+NIz+"),0);
    echo "<p class = st >By SidgaurAdibhyazca (".link_sutra("4.1.41").") :</p>\n";
    echo "<p class = st >षिद्बौरादिभ्यश्च (४.१.४१) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* anaDuhaH striyAM Am vA (vA 4378) */
elseif ($gender === "f" && sub(array("anaquh"),array("+"),blank(0),0) && in_array($fo,array("anaquh")) && $_GET['cond2_22']!=="2")
{
    $text = two(array("anaquh"),array("+"),array("anaquh"),array("+NIz+"),0);
    echo "<p class = st >By SidgaurAdibhyazca (".link_sutra("4.1.41").") :</p>\n";
    echo "<p class = st >षिद्बौरादिभ्यश्च (४.१.४१) :</p>\n";        
    display(8);
    $text = two(array("anaquh"),array("+NIz"),array("anaqvAh"),array("+NIz+"),1);
    echo "<p class = st >By anaDuhaH striyAM Am vA (vA 4378) :</p>\n";
    echo "<p class = st >अनडुहः स्त्रियां आम्‌ वा (वा ४३७८) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* SidgaurAdibhyazca (4.1.41) */
elseif ($gender === "f" && sub($gaurAdi,array("+"),blank(0),0) && $_GET['cond2_22']!=="2")
{
    $text = two($gaurAdi,array("+"),$gaurAdi,array("+NIz+"),0);
    echo "<p class = st >By SidgaurAdibhyazca (".link_sutra("4.1.41").") :</p>\n";
    echo "<p class = st >षिद्बौरादिभ्यश्च (४.१.४१) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* dAmahAyanAntAcca (4.1.27) */
elseif ($gender === "f" && dvigu($text) && sub(array("dAman",),array("+"),blank(0),0))
{
    $text = two(array("dAman",),array("+"),array("dAman",),array("+NIp+"),0);
    echo "<p class = st >By dAmahAyanAntAcca (".link_sutra("4.1.27").") :</p>\n";
    echo "<p class = st >दामहायनान्ताच्च (४.१.२७) :</p>\n";        
    display(0);
    $GIp=1;
    $it = array_merge($it,array("N","p"));
    $itprakriti = array_merge($itprakriti,array("N","p"));
    $text = one(array("an+"),array("n+"),0);
    echo "<p class = sa >By allopo'naH (".link_sutra("6.4.134").") :</p>\n";
    echo "<p class = sa >अल्लोपोऽनः (६.४.१३४) :</p>\n";
    display(6);    
    $allopo=1;
    $ajAdyataSTAp=0;
}
/* dAmahAyanAntAcca (4.1.27) */
elseif ($gender === "f" && dvigu($text) && sub(array("hAyana","hAyaRa"),array("+"),blank(0),0) && $_GET['cond2_16_2_1']==="1")
{
    $text = two(array("hAyana","hAyaRa"),array("+"),array("hAyana","hAyaRa"),array("+NIp+"),0);
    echo "<p class = st >By dAmahAyanAntAcca (".link_sutra("4.1.27").") and vayovAcakasyaiva hAyanasya GIbNatvaM ceSyate (vA 2441) :</p>\n";
    echo "<p class = st >दामहायनान्ताच्च (४.१.२७) और वयोवाचकस्यैव हायनस्य ङीब्णत्वं चेष्यते (वा २४४१) :</p>\n";        
    display(0);
    $GIp=1;
    $it = array_merge($it,array("N","p"));
    $itprakriti = array_merge($itprakriti,array("N","p"));
    $ajAdyataSTAp=0;
}
/* nityaM saJjJACandasoH (4.1.29) */
elseif ($gender === "f" && $_GET['cond2_10_1']==="1" )
{
    $text = one(array("An+","an+"),array("n+NIp+","n+NIp+"),0);
    echo "<p class = st >By nityaM saJjJACandasoH (".link_sutra("4.1.29").") :</p>\n";
    echo "<p class = st >नित्यं सञ्ज्ञाछन्दसोः (४.१.२९) :</p>\n";        
    display(8);
    $GIp=1;
    $ajAdyataSTAp=0;
}
/* kevalamAmakabhAgadheyapApAparasamAnAryakRtasumaGgalabheSajAcca (4.1.30) */
elseif ($gender === "f" && $_GET['cond2_18']==="1" )
{
    $text = two(array("kevala","mAmaka","BAgaDeya","pApa","apara","samAna","Aryakfta","sumaNgala","Bezaja",),array("+"),array("kevala","mAmaka","BAgaDeya","pApa","apara","samAna","Aryakfta","sumaNgala","Bezaja",),array("+NIp+"),0);
    echo "<p class = st >By kevalamAmakabhAgadheyapApAparasamAnAryakRtasumaGgalabheSajAcca (".link_sutra("4.1.30").") :</p>\n";
    echo "<p class = st >केवलमामकभागधेयपापापरसमानार्यकृतसुमङ्गलभेषजाच्च (४.१.३०) :</p>\n";        
    display(8);
    $GIp=1;
    $ajAdyataSTAp=0;
    $kevala=1;
}
/* ano bahuvrIheH (4.1.12) */
// This is tough. Please enumerate such words. Seems like they are very few.
elseif ($gender === "f" && ($_GET['cond2_8_1_1']==="1" || $_GET['cond2_8_2']==="1" || $_GET['cond2_10']==="1" ))
{
    echo "<p class = st >By ano bahuvrIheH (".link_sutra("4.1.12").") :</p>\n";
    echo "<p class = st >अनो बहुव्रीहेः (४.१.१२) :</p>\n";        
    display(0); $anobahuvrihe=1;
}
/* vano na haza iti vaktavyam (vA 2405) */
// This is tough. Please enumerate such words. Seems like they are very few.
elseif ($gender === "f" && $_GET['cond2_8_1_1']==="2" )
{
    echo "<p class = st >By vano na haza iti vaktavyam (vA 2405) :</p>\n";
    echo "<p class = st >वनो न हश इति वक्तव्यम्‌ (वा २४०५) :</p>\n";        
    display(0);
}
/* vano ra ca (4.1.7) and bahuvrIhau vA (vA 2407) */
// put here because it is always nakArAnta. So, it should get precedence over Rnnebhyo GIp.
elseif ($gender === "f" && ($_GET['cond2_8_1']==="2" || sub($vanoracawords,array("+"),blank(0),0) || sub($hrasva,array("tvan"),array("+"),0)  ))
{
    if ($_GET['cond2_8_1_2']==="1")
    {
    $text = two(array("van"),array("+"),array("var"),array("+NIp+"),1);        
    echo "<p class = st >By vano ra ca (".link_sutra("4.1.7").") and bahuvrIhau vA (vA 2407) :</p>\n";
    echo "<p class = pa >By pratyayagrahaNe yasmAtsa vihitastadAdestadantasya grahaNam (pa 24) :</p>\n";
    echo "<p class = st >वनो र च (४.१.७) और बहुव्रीहौ वा (वा २४०७) :</p>\n";        
    echo "<p class = pa >प्रत्ययग्रहणे यस्मात्स विहितस्तदादेस्तदन्तस्य ग्रहणम्‌ (प २४) :</p>\n"; $bahuvrihauva=1;       
    $bahuvrihauva=1;
    }
    elseif ($_GET['cond2_8_1_2']==="2" || (sub($kvanipwords,array("+"),blank(0),0)||sub($Gvanipwords,array("+"),blank(0),0)||sub($vanipwords,array("+"),blank(0),0) ) )
    {
    $text = two(array("van"),array("+"),array("var"),array("+NIp+"),0);        
    echo "<p class = st >By vano ra ca (".link_sutra("4.1.7").") :</p>\n";
    echo "<p class = pa >By pratyayagrahaNe yasmAtsa vihitastadAdestadantasya grahaNam (pa 24) :</p>\n";
    echo "<p class = st >वनो र च (४.१.७) :</p>\n";        
    echo "<p class = pa >प्रत्ययग्रहणे यस्मात्स विहितस्तदादेस्तदन्तस्य ग्रहणम्‌ (प २४) :</p>\n";        
    }
    display(3);
    $GIp=1;
}
/* manaH (4.1.11) */
elseif ($gender === "f" && sub(array("man"),array("+"),blank(0),0) )
{
    echo "<p class = st >By manaH (".link_sutra("4.1.11").") :</p>\n";
    echo "<p class = st >मनः (४.१.११) :</p>\n";        
    display(0); $manah=1;
}
/* Rnnebhyo GIp (4.1.5) */
elseif ($gender === "f" && sub(array("f","n"),array("+"),blank(0),0) && $SaTsvasrAdi!==1 && $allopo!==1 && $GIS!==1 && $GIn!==1 && $tiH!==1)
{
    $text = two(array("f","n"),array("+"),array("f+NIp","n+NIp"),array("+"),0);
    echo "<p class = st >By Rnnebhyo Gip (".link_sutra("4.1.5").") :</p>\n";
    echo "<p class = st >ऋन्नेभ्यो ङीप्‌ (४.१.५) :</p>\n";        
    display(8);
    $GIp=1;
}
/* TAbRci (4.1.9) */
elseif ($gender === "f" && $_GET['cond2_9'] === "1" )
{
    $text = two(array("pAd"),array("+"),array("pAd+wAp"),array("+"),0);
    echo "<p class = st >By TAbRci (".link_sutra("4.1.9").") :</p>\n";
    echo "<p class = st >टाबृचि (४.१.९) :</p>\n";        
    display(0);
    $text = two(array("pAd+wAp"),array("+"),array("pad+wAp"),array("+"),0);
    echo "<p class = sa >By pAdaH pat (".link_sutra("6.4.130").") :</p>\n";
    echo "<p class = sa >पादः पत्‌ (६.४.१३०):</p>\n";
    display(0);
    $TAp=1;
}
/* pAdo'nyatarasyAm (4.1.8) */
elseif ($gender === "f" && $_GET['cond2_9'] == "2" )
{
    $text = two(array("pAd"),array("+"),array("pAd+NIp"),array("+"),1);
    echo "<p class = st >By pAdo'nyatarasyAm (".link_sutra("4.1.8").") :</p>\n";
    echo "<p class = st >पादोऽन्यतरस्याम्‌ (४.१.८) :</p>\n";        
    display(0);
    $text = two(array("pAd+NIp"),array("+"),array("pad+NIp"),array("+"),0);
    echo "<p class = sa >By pAdaH pat (".link_sutra("6.4.130").") :</p>\n";
    echo "<p class = sa >पादः पत्‌ (६.४.१३०):</p>\n";
    display(0);
    $GIp=1;
}
/* anupasarjanAt adhikAra */
// anupasarjanAt (4.1.14) is implicitly mentioned in display function.
/* tAcCIlike Ne'pi (pa 68) */
elseif ($_GET['cond2_14']==='2' && sub(array("CAtra","bOBukza","SEkza","pOroha","sAMsTa","AvasTa","cOra","OpasTa","ArSa","kArma","vESvaDa","TApasa","sAtya","Anarta","SEbika",),array("+"),blank(0),0) )
{
    $text = two(array("a"),array("+"),array("a"),array("+NIp+"),0);
    echo "<p class = st >tAcCIlike Ne'pi (pa 68) :</p>\n";
    echo "<p class = st >ताच्छीलिके णेऽपि (प ६८) :</p>\n";        
    display(8);
    $GIp=1; $ajAdyataSTAp = 0;
}
/* naJsnaJIkakkhyuMstaruNatalunAnAmupasaGkhyAnam (vA 2425) */
elseif ( ($_GET['cond2_14']==='2' && sub(array("strERa","pOMsna","SAktIka","yAzTIka","kArkIka","lOhitIka","NkaraRa","taruRa","taluna"),array("+"),blank(0),0) ) )
{
    $text = two(array("a"),array("+"),array("a"),array("+NIp+"),0);
    echo "<p class = st >By naJsnaJIkakkhyuMstaruNatalunAnAmupasaGkhyAnam (vA 2425) :</p>\n";
    echo "<p class = st >नञ्स्नञीकक्ख्युंस्तरुणतलुनानामुपसङ्ख्यानम्‌ (वा २४२५) :</p>\n";        
    display(8);
    $GIp=1; $ajAdyataSTAp = 0;
}
/* anapatyAdhikArasthAnna GIp (vA 2426) */
elseif ( ($_GET['cond2_14']==='2' && sub(array("kEdArya","kESya","ASvya","dvEpya","kAMsya","ABijitya","vEdaBftya","SAlAvatya","SEKAvatya","SAmIvatya","OrRAvatya","SrOmatya",),array("+"),blank(0),0) ) )
{
    echo "<p class = sa >By anapatyAdhikArasthAnna GIp (vA 2426) :</p>\n";
    echo "<p class = sa >अनपत्याधिकारस्थान्न ङीप्‌ (वा २४२६) :</p>\n";        
    display(8);
    $taddhita=1;
    $anapatya=1;
     $ajAdyataSTAp = 0;
}
/* sarvatra lohitAdikatantebhyaH (4.1.18) */
elseif ($gender==="f" && sub(array("lOhitya","nElya","hEritya","pEtya","mAdrya","PEnya","mAndya","kAtya"),array("+"),blank(0),0) )
{
    $text = two(array("a"),array("+"),array("a"),array("+zPa+"),0);
    echo "<p class = st >By sarvatra lohitAdikatantebhyaH (".link_sutra("4.1.18").") :</p>\n";
    echo "<p class = st >सर्वत्र लोहितादिकतन्तेभ्यः (४.१.१८) :</p>\n";        
    display(8);
     $ajAdyataSTAp = 0;
}
/* kauravyamANDukAbhyaM ca (4.1.19) */
elseif ($gender==="f" && sub(array("kOravya","mARqUka"),array("+"),blank(0),0) )
{
    $text = two(array("a"),array("+"),array("a"),array("+zPa+"),0);
    echo "<p class = st >By kauravyamANDukAbhyaM ca (".link_sutra("4.1.19").") :</p>\n";
    echo "<p class = st >कौरव्यमाण्डूकाभ्यां च (४.१.१९) :</p>\n";        
    display(8);
     $ajAdyataSTAp = 0;
}
/* AsurerupasaMkhyAnam (vA 2433) */
elseif ($gender==="f" && sub(array("Asura"),array("+"),blank(0),0) )
{
    $text = two(array("Asura"),array("+"),array("Asura"),array("+zPa+"),0);
    echo "<p class = st >By AsurerupasaMkhyAnam (vA 2433) :</p>\n";
    echo "<p class = st >आसुरेरुपसङ्ख्यानम्‌ (वा २४३३) :</p>\n";        
    display(8);
     $ajAdyataSTAp = 0;
}
/* yaJazca (4.1.16) */ 
elseif ( ($_GET['cond2_14']==='2' && sub(array("gArgya","vAtsya","vAjya","sAMkftya","Ajya","vEyAGrapAda","vEdaBfta","prAcInayogya","Agastya","pOlastya","rEBya","AgniveSya","SANKya","SAWya","GOmya","Avawya","GOmya","Avawya","cAmasa","DAnaYjayya","mAnasya","vESvAvasvya","vArkzya","jAnamAnya","lOhitya","SAMsitya","bABravya","mARqavya","mAkzavya","Aligavya","SANkavya","lEgavya","gOlavya","mAntavya","jEgizavya","mAnavya","tAntavya","mAnAyavya","BOtya","kATakya","kAzya","tARqya","vAtaRqya","kApyya","kAtya","kOrukatya","Anaqavya","kARvya","gOkakzya","Agastyya","kORqinya","yAjYavalkya","OBayya","jAtya","vErohitya","vArzagaRya","rAhUgaRya","SARqilya","vaRya","kAculukya","mOdgalya","mOsalya","pArASarya","jAtUkarRya","mAntritya","sAMhitya","ASmaraTya","SArkarAkzya","pOtimAzya","sTORya","Ararakya","pENgalya","kArzRya","gOlundya","OlUkya","tEtikzya","BEzajya","BAqitya","BARqitya","dAlBya","cEkitya","dEvahavya","Endrahavya","Ekalavya","pEppalavya","vARdagnya","jAmadagnya","sOloBinya","OkTya","kOwigavya","mADavya","bABravya","vAtaRqya","kApeya","bODya",),array("+"),$sup,0) ) )
{
    /* prAcAM Spha taddhitaH (4.1.17) */
    $text = two(array("ya"),array("+"),array("ya"),array("+zPa+"),1);
    echo "<p class = sa >By prAcAM Spha taddhitaH (".link_sutra("4.1.17").") :</p>\n";
    echo "<p class = sa >प्राचां ष्फ तद्धितः (४.१.१७) :</p>\n";        
    display(8);
    $text = three(array("ya"),array("+"),$sup,array("ya"),array("+NIp+"),$sup,0);
    echo "<p class = st >By yaJazca (".link_sutra("4.1.16").")  :</p>\n";
    echo "<p class = st >यञश्च (४.१.१६) :</p>\n";        
    display(8);
    $GIp=1; $ajAdyataSTAp = 0;
    $taddhita=1;
}
/* TiDDhANaJdvayasajdadhnaJmAtractayapThakThaJkaJkvarapaH (4.1.15) exeption in upasarjanatva */
elseif ($_GET['cond2_14']==='1' && $anapatya===0 && $gender==="f" && $fo!=="mAmaka" && $kevala!==1 && $GIS!==1 && $GIp!==1 && $GIn!==1 && $cAp!==1 && $_GET['cond2_11']!=="1" && !sub(array("nOka","AcArya"),array("+"),blank(0),0) && $_GET['cond2_12']!=="1" && $_GET['cond2_13']!=="2")
{
    echo "<p class = pa >TiDDhANaJdvayasajdadhnaJmAtractayapThakThaJkaJkvarapaH (".link_sutra("4.1.15").") is not applied because of upasarjanatva. :</p>\n";
    echo "<p class = pa >टिड्ढाणञ्द्वयसज्दध्नञ्मात्रच्तयप्ठक्ठञ्कञ्क्वरपः (४.१.१५) उपसर्जनत्व से बाधित है ।</p>\n";        
    display(0);
}
/* TiDDhANaJdvayasajdadhnaJmAtractayapThakThaJkaJkvarapaH (4.1.15) exeption in zAnac */
elseif (sub(array("syamARa","zyamARa"),array("+"),blank(0),0))
{
    echo "<p class = pa >lAzrayamanubandhakAryaM nAdezAnAm (pa 70) prevents application of TiDDhANaJdvayasajdadhnaJmAtractayapThakThaJkaJkvarapaH (".link_sutra("4.1.15").") :</p>\n";
    echo "<p class = pa >लाश्रयमनुबन्धकार्यं नादेशानाम्‌ (प ७०) से टिड्ढाणञ्द्वयसज्दध्नञ्मात्रच्तयप्ठक्ठञ्कञ्क्वरपः (४.१.१५) बाधित होता है ।</p>\n";        
    display(0);
}
/* TiDDhANaJdvayasajdadhnaJmAtractayapThakThaJkaJkvarapaH (4.1.15) */
// TitkRdanta pending.
elseif ($_GET['cond2_14']==='2' && $anapatya===0 && $gender==="f" && $fo!=="mAmaka" && $kevala!==1 && $GIS!==1 && $GIp!==1 && $GIn!==1 && $cAp!==1 && $_GET['cond2_11']!=="1" && !sub(array("nOka","AcArya"),array("+"),blank(0),0) && $_GET['cond2_12']!=="1" && $_GET['cond2_13']!=="2")
{
    $text = two(array("a"),array("+"),array("a"),array("+NIp+"),0);
    echo "<p class = st >By TiDDhANaJdvayasajdadhnaJmAtractayapThakThaJkaJkvarapaH (".link_sutra("4.1.15").") :</p>\n";
    echo "<p class = st >टिड्ढाणञ्द्वयसज्दध्नञ्मात्रच्तयप्ठक्ठञ्कञ्क्वरपः (४.१.१५) 1:</p>\n";        
    display(8);
    $GIp=1; $ajAdyataSTAp = 0;
}
/* vayasi prathame (4.1.20) */
elseif ($gender==="f" && sub(array("kumAra"),array("+"),blank(0),0) )
{
    $text = two(array("kumAra"),array("+"),array("kumAra"),array("+NIp+"),0);
    echo "<p class = st >By vayasi prathame (".link_sutra("4.1.20").") :</p>\n";
    echo "<p class = st >वयसि प्रथमे (४.१.२०) :</p>\n";        
    display(8);
    $GIp=1;  $ajAdyataSTAp = 0;
}
/* vayasyacarama iti vAcyam (vA 2435) */
elseif ($gender==="f" && sub(array("vaDUwa","ciraRwa"),array("+"),blank(0),0) )
{
    $text = two(array("vaDUwa","ciraRwa"),array("+"),array("vaDUwa","ciraRwa"),array("+NIp+"),0);
    echo "<p class = st >By vayasyacarama iti vAcyam (vA 2435) :</p>\n";
    echo "<p class = st >वयस्यचरम इति वाच्यम्‌ (वा २४३५) :</p>\n";        
    display(8);
    $GIp=1; $ajAdyataSTAp = 0;
}
/* common patch for Spha pratyaya */
if (sub(array("+"),array("zPa"),array("+"),0))
{
    /* SaH pratyayasya (1.3.6) and tasya lopaH (1.3.9) */
    // list of such pratyayas in SLP1 - za,zavan,zikan, zivan, zeRyaR, zkan, zWac, zWan, zWarac, zWal, zwran, zPa, zPak, zyaN, zyaY, zvarac, zvun
    // right now only coded for zPa.
    $text = two(array("a"),array("+zPa"),array("a"),array("+Pa+"),0);
    echo "<p class = sa >By SaH pratyayasya (".link_sutra("1.3.6").") and tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >षः प्रत्ययस्य (१.३.६) और तस्य लोपः (१.३.९) :</p>\n";        
    display(0);
    $it = array_merge($it,array("z"));
    $itprakriti = array_merge($itprakriti,array("z"));
    /* AyaneyInIyiyaH phaDhakhaCaghAM pratyayAdInAm (7.1.2) */
    if(sub(array("+"),array("Pa","Qa","Ka","Ca","Ga"),array("+"),0))
    {
        $text = three(array("+"),array("Pa","Qa","Ka","Ca","Ga"),array("+"),array("+"),array("Ayana","eya","Ina","Iya","iya"),array("+"),0);
        echo "<p class = sa >By AyaneyInIyiyaH phaDhakhaCaghAM pratyayAdInAm (".link_sutra("7.1.2").") :</p>\n";
        echo "<p class = sa >आयनेयीनीयियः फढखछघां प्रत्ययादीनाम्‌ (७.१.२) :</p>\n";
        display(0);
    }    
}
/* SidgaurAdibhyazca (4.1.41) */
// right now coded only for Spha. Remaining have to be coded at appropriate stage.
if ($gender === "f" && in_array("z",$itprakriti) && $_GET['cond2_22']!=="2" && $GIS!==1)
{
    $text = two(array("Ayana"),array("+"),array("Ayana"),array("+NIz+"),0);
    $text = two(array("anaquh"),array("+NIz"),array("anaqvAh"),array("+NIz+"),1);
    echo "<p class = st >By SidgaurAdibhyazca (".link_sutra("4.1.41").") :</p>\n";
    echo "<p class = st >षिद्बौरादिभ्यश्च (४.१.४१) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}
/* ajAdyataSTAp (4.1.4) for adanta words */
if (arr($text,'/[a][+]/') && $gender==="f" && $_GET['cond2_7']!=="2" && $ajAdyataSTAp!==0 && $TAp!==1) 
{
    /* kanyAyA na. 'kanyAyAH kanIna ca' iti nirdezAt. */
    if ($gender==="f" && sub(array("kanya"),array("+"),blank(0),0) )
    {
        echo "<p class = pa >By kanyAyA na. 'kanyAyAH kanIna ca' iti nirdezAt. :</p>\n";
        echo "<p class = pa >'कन्याया न' । 'कन्यायाः कनीन च' इति निर्देशात्‌ ।</p>\n";        
        display(0);
    }
    $text = last(array($so),array("+wAp+".$so),0);
    $text = two(array("NIp","NIz","NIn"),array("+wAp"),array("NIp","NIz","NIn"),array(""),0);
    echo "<p class = st >ajAdyataSTAp (".link_sutra("4.1.4").") :</p>\n";
    echo "<p class = st >अजाद्यतष्टाप्‌ (४.१.४) :</p>\n";
    display(0); 
    $TAp=1;
}
/* DAbubhAbhyAmanyatarsyAm (4.1.13) */
if ($anobahuvrihe===1 || $manah===1 || $bahuvrihauva===1)
{
    $text = two(array("an"),array("+"),array("an"),array("+qAp+"),1);
    echo "<p class = st >By DAbubhAbhyAmanyatarsyAm (".link_sutra("4.1.13").") :</p>\n";
    echo "<p class = st >डाबुभाभ्यामन्यतरस्याम्‌ (४.१.१३) :</p>\n";
    display(0);
    $text = two(array("an"),array("+qAp+"),array(""),array("+qAp+"),0);    
    echo "<p class = sa >By TeH (".link_sutra("6.4.143").") :</p>\n";
    echo "<p class = sa >टेः (६.४.१४३) :</p>\n";
    display(3);
    $DAp=1;
}
/* sUryatiSyAgastyamatsyAnAM ya upadhAyAH (6.4.141) */
if ($gender === "f" && sub(array("sUrya","tizya","agastya","matsya"),array("+"),blank(0),0) )
{
    $text = two(array("sUrya","tizya","agastya","matsya"),array("+NIz+"),array("sUra","tiza","agasta","matsa"),array("+NIz+"),0);
    echo "<p class = sa >By sUryatiSyAgastyamatsyAnM ya upadhAyAH (".link_sutra("6.4.141").") :</p>\n";
    echo "<p class = sa >सूर्यतिष्यागस्त्यमत्स्यानां य उपधायाः (६.४.१४१) :</p>\n";        
    display(8);
    $GIS=1;
    $ajAdyataSTAp=0;
}

/* common patch for TAp pratyaya to remove the it markers. */
if ($TAp===1)
{
    $Ap=1;
    $text = two(array("+wAp"),array("+"),array("A"),array("+"),0); 
    $it = array_merge($it,array("p","w"));
    $itprakriti = array_merge($itprakriti,array("p","w"));
    echo "<p class = sa >TakAra and pakAra are 'it'. They are elided by cuTU(1.3.7), halantyam (".link_sutra("1.3.3").") and tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >टपावितौ । चुटू (१.३.७), हलन्त्यम्‌ (१.३.३) और तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
}
/* common patch for DAp pratyaya to remove the it markers. */
if ($DAp===1)
{
    $Ap=1;
    $text = two(array("+qAp"),array("+"),array("A"),array("+"),0); 
    $it = array_merge($it,array("p","q"));
    $itprakriti = array_merge($itprakriti,array("p","q"));
    echo "<p class = sa >DakAra and pakAra are 'it'. They are elided by cuTU(1.3.7), halantyam (".link_sutra("1.3.3").") and tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >डपावितौ । चुटू (१.३.७), हलन्त्यम्‌ (१.३.३) और तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
}
/* common patch for cAp pratyaya to remove the it markers. */
if ($cAp===1)
{
    $Ap=1;
    $text = two(array("+cAp"),array("+"),array("A"),array("+"),0); 
    $it = array_merge($it,array("p","c"));
    $itprakriti = array_merge($itprakriti,array("p","c"));
    echo "<p class = sa >cakAra and pakAra are 'it'. They are elided by cuTU(1.3.7), halantyam (".link_sutra("1.3.3").") and tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >चपावितौ । चुटू (१.३.७), हलन्त्यम्‌ (१.३.३) और तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
}
/* common removal of G,n and application of 'yasyeti ca' for NIn pratyaya */
if ($GIn===1 )
{
    $text = two(array("NIn"),array("+"),array("I"),array("+"),0); 
    $it = array_merge($it,array("N","n"));
    $itprakriti = array_merge($itprakriti,array("N","n"));
    echo "<p class = sa >GakAra and nakAra are 'it'. They are elided by lazakvataddhite (".link_sutra("1.3.8")."), halantyam (".link_sutra("1.3.3").") and tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >ङनावितौ । लशक्वतद्धिते (१.३.८), हलन्त्यम्‌ (१.३.३) और तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
    $nadi=1;
    $GI=1;
    $noAp=1;    
/* halastaddhitasya (6.4.150) */
// patch made for gArgI etc.
    if ( sub($hl,array("ya"),array("+I+"),0) && $taddhita===1)
    {
        $text = three($hl,array("ya",),array("+I+"),$hl,array("a"),array("+I+"),0);
        echo "<p class = sa >By halastaddhitasya (".link_sutra("6.4.150").") :</p>\n";
        echo "<p class = sa >हलस्तद्धितस्य (६.४.१५०) :</p>\n";
        display(0);
        $taddita=0;
    }
/* yasyeti ca (6.4.148) */
// patch made because before GIp, it becomes bham.
    if (arr($text,'/[aI][+][I]/') && $gender==="f" && sub(array("I","a"),array("+I"),array("+"),0))
    {
        $text = two(array("a","I"),array("I"),array("",""),array("I"),0);
        echo "<p class = sa >By yasyeti ca (".link_sutra("6.4.148").") :</p>\n";
        echo "<p class = sa >यस्येति च (६.१.१४८) :</p>\n";
        display(3); 
        $taddhita=0;
        $text = two(array("+"),array("I+"),array(""),array("I+"),0);
    }
/* vAha UTh (6.4.132) */
    if (  sub(array("vAh"),array("+","+I+"),blank(0),0) && !sub(array("anaqvAh"),array("+","+I+"),blank(0),0) )
    {   
        $text = one(array("vAh+"),array("U+Ah+"),0);
        echo "<p class = sa >By vAha UTh (".link_sutra("6.4.132").") :</p>\n";
        echo "<p class = sa>वाह ऊठ्‌ (६.४.१३२) :</p>\n";
        display(3); $samp=array_merge($samp,array(1));
    }
/* eco'yavAyAvaH (7.1.78) */
    $ayavayavah = array("ay","av","Ay","Av");
    if (sub(prat('ec'),prat('ac'),blank(0),0))
    {
    $text = two(prat('ec'),array("I"),$ayavayavah,array("I"),0);
    echo "<p class = sa >By echo'yavAyAvaH (".link_sutra("7.1.78").") :</p>\n";
    echo "<p class = sa >एचोऽयवायावः (७.१.७८) 3:</p>\n";
    display(0);
    }
    /* akaH savarNe dIrghaH (6.1.101) */ 
    if (sub(array("i","I"),array("+I+"),blank(0),0))
    {
    $text = two(array("i","I"),array("+I+"),array("I+","I+"),blank(2),0);
    echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
    echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
    display(0);
    }    
    /* aniditAM hala upadhAyAH kGiti (6.4.24) */ 
    if ( !itcheck(array("i"),1) && arr($text,'/[nY]['.pc('hl').'][+][I][+]/')  )
    {
        $text = three(array("n","Y"),$hl,array("+"),array("",""),$hl,array("+"),0);        
        echo "<p class = sa >aniditAM hala upadhAyAH kGiti (".link_sutra("6.4.24").") :</p>\n";
        echo "<p class = sa >अनिदितां हल उपधायाः क्ङिति (६.४.२४) :</p>\n";
        display(0); 
        $aniditAm = 1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
    }
    /* acaH (6.4.138) */ 
    if ( preg_match('/[aA][nMY][c]/',$fo) && $aniditAm === 1 && sub(array("ac","Ac"),array("+"),blank(0),0) && ($bham===1 || sub(array("ac","Ac"),array("+"),array("I+"),0)) && $ancu===1)
    {
        if (sub(array("i","I","u","U","f","F","x","X","y","v"),prat('ac'),array("c"),0))
        {
        echo "<p class = pa >Though iko yaNaci is antaraGga than lopa by acaH, its application is barred by 'akRtavyUhAH pANinIyAH (pa 57).</p>\n";
        echo "<p class = pa >इको यणचि से प्राप्त यण्‌ अन्तरङ्ग होने पर भी अकृतव्यूहाः पाणिनीयाः (प ५७) से वह अचः का बाध नहीं करता ।</p>\n";
        display(0);        
        }
        $text = two(array("yac","ac","Ac"),array("+"),array("ic","c","ac"),array("+"),0);
        echo "<p class = sa >acaH (".link_sutra("6.4.138").") :</p>\n";
        echo "<p class = sa >अचः (६.४.१३८) :</p>\n";
        if ($nance===1)
        {
        echo "<p class = hn >As there is no lopa of nakAra in nAJceH pUjAyAm, there is not akAralopa.</p>\n";
        echo "<p class = hn >नलोपाभावादकारलोपो न ।</p>\n";        
        }
        display(3); 
        $acaH=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
    } else { $acaH=0; }
    /* cau (6.3.138) */ 
    if ( $acaH===1)
    {
        $text = three($ac,array("c","c"),array("+"),$acdir,array("c","c"),array("+"),0);
        echo "<p class = sa >cau (".link_sutra("6.3.138").") :</p>\n";
        echo "<p class = sa >चौ (६.३.१३८) :</p>\n";
        display(3);
    }
    $text = one(array("+I+"),array("I+"),0);
    $dhatu=0;
}
/* common removal of G,p and application of 'yasyeti ca' for NIp pratyaya */
if ($GIp===1 )
{
    $text = one(array("+NIp+"),array("+I+"),0); 
    $it = array_merge($it,array("N","p"));
    $itprakriti = array_merge($itprakriti,array("p","N"));
    echo "<p class = sa >GakAra and pakAra are 'it'. They are elided by lazakvataddhite (".link_sutra("1.3.8")."), halantyam (".link_sutra("1.3.3").") and tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >ङपावितौ । लशक्वतद्धिते (१.३.८), हलन्त्यम्‌ (१.३.३) और तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
    $GI=1;
    $nadi=1; 
    $noAp=1;    
/* halastaddhitasya (6.4.150) */
// patch made for gArgI etc.
    if ( sub($hl,array("ya"),array("+I+"),0) && $taddhita===1 )
    {
        $text = three($hl,array("ya",),array("+I+"),$hl,array("a"),array("+I+"),0);
        echo "<p class = sa >By halastaddhitasya (".link_sutra("6.4.150").") :</p>\n";
        echo "<p class = sa >हलस्तद्धितस्य (६.४.१५०) :</p>\n";
        display(0);
        $taddita=0;
    }
/* yasyeti ca (6.4.148) */
// patch made because before GIp, it becomes bham.
    if (arr($text,'/[aI][+][I]/') && $gender==="f" && sub(array("I","a"),array("+I"),array("+"),0))
    {
        $text = two(array("a","I"),array("I"),array("",""),array("I"),0);
        echo "<p class = sa >By yasyeti ca (".link_sutra("6.4.148").") :</p>\n";
        echo "<p class = sa >यस्येति च (६.१.१४८) :</p>\n";
        display(3); 
        $taddhita=0;
        $text = two(array("+"),array("I+"),array(""),array("I+"),0);
    }
/* eco'yavAyAvaH (7.1.78) */
    $ayavayavah = array("ay","av","Ay","Av");
    if (sub(prat('ec'),prat('ac'),blank(0),0))
    {
    $text = two(prat('ec'),array("I"),$ayavayavah,array("I"),0);
    echo "<p class = sa >By echo'yavAyAvaH (".link_sutra("7.1.78").") :</p>\n";
    echo "<p class = sa >एचोऽयवायावः (७.१.७८) 4:</p>\n";
    display(0);
    }
    /* akaH savarNe dIrghaH (6.1.101) */ 
    if (sub(array("i","I"),array("+I+"),blank(0),0))
    {
    $text = two(array("i","I"),array("+I+"),array("I+","I+"),blank(2),0);
    echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
    echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
    display(0);
    }    
    /* aniditAM hala upadhAyAH kGiti (6.4.24) */ 
    if ( !itcheck(array("i"),1) && arr($text,'/[nY]['.pc('hl').'][+][I][+]/')  )
    {
        $text = three(array("n","Y"),$hl,array("+"),array("",""),$hl,array("+"),0);        
        echo "<p class = sa >aniditAM hala upadhAyAH kGiti (".link_sutra("6.4.24").") :</p>\n";
        echo "<p class = sa >अनिदितां हल उपधायाः क्ङिति (६.४.२४) :</p>\n";
        display(0); 
        $aniditAm = 1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
    }
    /* acaH (6.4.138) */ 
    if ( preg_match('/[aA][nMY][c]/',$fo) && $aniditAm === 1 && sub(array("ac","Ac"),array("+"),blank(0),0) && ($bham===1 || sub(array("ac","Ac"),array("+"),array("I+"),0)) && $ancu===1)
    {
        if (sub(array("i","I","u","U","f","F","x","X","y","v"),prat('ac'),array("c"),0))
        {
        echo "<p class = pa >Though iko yaNaci is antaraGga than lopa by acaH, its application is barred by 'akRtavyUhAH pANinIyAH (pa 57).</p>\n";
        echo "<p class = pa >इको यणचि से प्राप्त यण्‌ अन्तरङ्ग होने पर भी अकृतव्यूहाः पाणिनीयाः (प ५७) से वह अचः का बाध नहीं करता ।</p>\n";
        display(0);        
        }
        $text = two(array("yac","ac","Ac"),array("+"),array("ic","c","ac"),array("+"),0);
        echo "<p class = sa >acaH (".link_sutra("6.4.138").") :</p>\n";
        echo "<p class = sa >अचः (६.४.१३८) :</p>\n";
        if ($nance===1)
        {
        echo "<p class = hn >As there is no lopa of nakAra in nAJceH pUjAyAm, there is not akAralopa.</p>\n";
        echo "<p class = hn >नलोपाभावादकारलोपो न ।</p>\n";        
        }
        display(3); 
        $acaH=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
    } else { $acaH=0; }
    /* cau (6.3.138) */ 
    if ( $acaH===1)
    {
        $text = three($ac,array("c","c"),array("+"),$acdir,array("c","c"),array("+"),0);
        echo "<p class = sa >cau (".link_sutra("6.3.138").") :</p>\n";
        echo "<p class = sa >चौ (६.३.१३८) :</p>\n";
        display(3);
    }
    $text = one(array("+I+"),array("I+"),0);
    $dhatu=0;
}
/* common removal of G,S and application of 'yasyeti ca' for NIS pratyaya */
if ($GIS===1 )
{
    $text = two(array("NIz"),array("+"),array("I"),array("+"),0); 
    $it = array_merge($it,array("N","z"));
    $itprakriti = array_merge($itprakriti,array("N","z"));
    echo "<p class = sa >GakAra and SakAra are 'it'. They are elided by lazakvataddhite (".link_sutra("1.3.8")."), halantyam (".link_sutra("1.3.3").") and tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >ङषावितौ । लशक्वतद्धिते (१.३.८), हलन्त्यम्‌ (१.३.३) और तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
    $nadi=1;
    $GI=1;
    $noAp=1;    
/* halastaddhitasya (6.4.150) */
// patch made for gArgI etc.
    if ( sub($hl,array("ya"),array("+I+"),0) && $taddhita===1)
    {
        $text = three($hl,array("ya",),array("+I+"),$hl,array("a"),array("+I+"),0);
        echo "<p class = sa >By halastaddhitasya (".link_sutra("6.4.150").") :</p>\n";
        echo "<p class = sa >हलस्तद्धितस्य (६.४.१५०) :</p>\n";
        display(0);
        $taddita=0;
    }
/* yasyeti ca (6.4.148) */
// patch made because before GIp, it becomes bham.
    if (arr($text,'/[aI][+][I]/') && $gender==="f" && sub(array("I","a"),array("+I"),array("+"),0))
    {
        $text = two(array("a","I"),array("I"),array("",""),array("I"),0);
        echo "<p class = sa >By yasyeti ca (".link_sutra("6.4.148").") :</p>\n";
        echo "<p class = sa >यस्येति च (६.१.१४८) :</p>\n";
        display(3); 
        $taddhita=0;
        $text = two(array("+"),array("I+"),array(""),array("I+"),0);
    }
/* vAha UTh (6.4.132) */
    if (  sub(array("vAh"),array("+","+I+"),blank(0),0) && !sub(array("anaqvAh"),array("+","+I+"),blank(0),0) )
    {   
        $text = one(array("vAh+"),array("U+Ah+"),0);
        echo "<p class = sa >By vAha UTh (".link_sutra("6.4.132").") :</p>\n";
        echo "<p class = sa>वाह ऊठ्‌ (६.४.१३२) :</p>\n";
        display(3); $samp=array_merge($samp,array(1));
    }
/* eco'yavAyAvaH (7.1.78) */
    $ayavayavah = array("ay","av","Ay","Av");
    if (sub(prat('ec'),prat('ac'),blank(0),0))
    {
    $text = two(prat('ec'),array("I"),$ayavayavah,array("I"),0);
    echo "<p class = sa >By echo'yavAyAvaH (".link_sutra("7.1.78").") :</p>\n";
    echo "<p class = sa >एचोऽयवायावः (७.१.७८) 5:</p>\n";
    display(0);
    }
    /* akaH savarNe dIrghaH (6.1.101) */ 
    if (sub(array("i","I"),array("+I+"),blank(0),0))
    {
    $text = two(array("i","I"),array("+I+"),array("I+","I+"),blank(2),0);
    echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
    echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
    display(0);
    }    
    
    /* aniditAM hala upadhAyAH kGiti (6.4.24) */ 
    if ( !itcheck(array("i"),1) && arr($text,'/[nY]['.pc('hl').'][+][I][+]/')  )
    {
        $text = three(array("n","Y"),$hl,array("+"),array("",""),$hl,array("+"),0);        
        echo "<p class = sa >aniditAM hala upadhAyAH kGiti (".link_sutra("6.4.24").") :</p>\n";
        echo "<p class = sa >अनिदितां हल उपधायाः क्ङिति (६.४.२४) :</p>\n";
        display(0); 
        $aniditAm = 1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
    }
    /* acaH (6.4.138) */ 
    if ( preg_match('/[aA][nMY][c]/',$fo) && $aniditAm === 1 && sub(array("ac","Ac"),array("+"),blank(0),0) && ($bham===1 || sub(array("ac","Ac"),array("+"),array("I+"),0)) && $ancu===1)
    {
        if (sub(array("i","I","u","U","f","F","x","X","y","v"),prat('ac'),array("c"),0))
        {
        echo "<p class = pa >Though iko yaNaci is antaraGga than lopa by acaH, its application is barred by 'akRtavyUhAH pANinIyAH (pa 57).</p>\n";
        echo "<p class = pa >इको यणचि से प्राप्त यण्‌ अन्तरङ्ग होने पर भी अकृतव्यूहाः पाणिनीयाः (प ५७) से वह अचः का बाध नहीं करता ।</p>\n";
        display(0);        
        }
        $text = two(array("yac","ac","Ac"),array("+"),array("ic","c","ac"),array("+"),0);
        echo "<p class = sa >acaH (".link_sutra("6.4.138").") :</p>\n";
        echo "<p class = sa >अचः (६.४.१३८) :</p>\n";
        if ($nance===1)
        {
        echo "<p class = hn >As there is no lopa of nakAra in nAJceH pUjAyAm, there is not akAralopa.</p>\n";
        echo "<p class = hn >नलोपाभावादकारलोपो न ।</p>\n";        
        }
        display(3); 
        $acaH=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
    } else { $acaH=0; }
    /* cau (6.3.138) */ 
    if ( $acaH===1)
    {
        $text = three($ac,array("c","c"),array("+"),$acdir,array("c","c"),array("+"),0);
        echo "<p class = sa >cau (".link_sutra("6.3.138").") :</p>\n";
        echo "<p class = sa >चौ (६.३.१३८) :</p>\n";
        display(3);
    }
    $text = one(array("+I+"),array("I+"),0);
    $dhatu=0;
}
/* kSipakAdInAM ca na (vA 4530) */
// This is an AkRtigaNa. Therefore, the occurence from grammar have to be identified. Right now the list in gaNapATha nad gaNaratnamahodadhi is given.
if ($gender==="f" && $Ap===1 && sub(array("kzipaka","Druvaka","kanyaka","cawaka","alaka","izwaka","Duvaka","caraka","sevaka","karaka","avaka","lahaka",),array("A"),blank(0),0) )
{
    echo "<p class = sa >By kSipakAdInAM ca na (vA 4530) :</p>\n";
    echo "<p class = sa >क्षिपकादीनां च न (वा ४५३०) :</p>\n";
    display(0);
}
/* abhASitapuMskAcca (7.3.48) and AdAcAryANAm (7.3.49) */
elseif ($gender==="f" && $Ap===1 && $_GET['cond2_11_1']==="3" )
{
    $text = two(array("aka","ak"),array("A"),array("ika","ik"),array("A"),1);
    echo "<p class = sa >By abhASitapuMskAcca (".link_sutra("7.3.48").") :</p>\n";
    echo "<p class = sa >अभाषितपुंस्काच्च (७.३.४८) :</p>\n";
    display(0);
    $text = two(array("aka","ak"),array("A"),array("Aka","Ak"),array("A"),1);
    echo "<p class = sa >By AdAcAryANAm (".link_sutra("7.3.49").") :</p>\n";
    echo "<p class = sa >आदाचार्याणाम्‌ (७.३.४९) :</p>\n";
    display(0);
}
/* udIcAmAtaH sthAne yakapUrvAyAH (7.3.46) */
elseif ($gender==="f" && $Ap===1 && $_GET['cond2_13_2']==="1" )
{
    $text = two(array("yaka","kaka","yak","kak"),array("A"),array("yika","kika","yik","kik"),array("A"),1);
    echo "<p class = sa >By udIcAmAtaH sthAne yakapUrvAyAH (".link_sutra("7.3.46").") :</p>\n";
    echo "<p class = sa >उदीचामातः स्थाने यकपूर्वायाः (७.३.४६) :</p>\n";
    display(0);
}
/* dhAtvantayakostu nityam (vA 4536) */
elseif ($gender==="f" && $Ap===1 && $_GET['cond2_13']==="1" )
{
    $text = two(array("yaka","kaka","yak","kak"),array("A"),array("yika","kika","yik","kik"),array("A"),0);
    echo "<p class = sa >By dhAtvantayakostu nityam (vA 4536) :</p>\n";
    echo "<p class = sa >धात्वन्तयकोस्तु नित्यम्‌ (वा ४५३६) :</p>\n";
    display(0);
}
/* tyakanazca niSedhaH (vA 4526) */
elseif ($gender==="f" && $Ap===1 && $_GET['cond2_12']==="2" )
{
    echo "<p class = st >By tyakanazca niSedhaH (vA 4526) :</p>\n";
    echo "<p class = st >त्यकनश्च निषेधः (वा ४५२६) :</p>\n";
    display(0);
}
/* AziSi vunazca (vA 4528) */
elseif ($gender==="f" && $Ap===1 && $_GET['cond2_11_1']==="1" )
{
    echo "<p class = st >By AziSi vunazca (vA 4528) :</p>\n";
    echo "<p class = st >आशिषि वुनश्च (वा ४५२८) :</p>\n";
    display(0);
}
/* uttarapadalope na (vA 4529) */
elseif ($gender==="f" && $Ap===1 && $_GET['cond2_11_1']==="2" )
{
    echo "<p class = st >By uttarapadalope na (vA 4529) :</p>\n";
    echo "<p class = st >उत्तरपदलोपे न (वा ४५२९) :</p>\n";
    display(0);
}
/* tArakA jyotiSi (vA 4531) */
elseif ($gender==="f" && $Ap===1 && sub(array("tAraka","tArak"),array("A"),blank(0),0) )
{
    $text = one(array("tArakaA","tArakA"),array("tArikaA","tArikA"),1);
    echo "<p class = st >By tArakA jyotiSi (vA 4531) :</p>\n";
    echo "<p class = st >तारका ज्योतिषि (वा ४५३१) :</p>\n";
    display(0);
}
/* varNakA tAntave (vA 4532) */
elseif ($gender==="f" && $Ap===1 && sub(array("varRaka","varRak"),array("A"),blank(0),0) )
{
    $text = one(array("varRakaA","varRakA"),array("varRikaA","varRikA"),1);
    echo "<p class = st >By varNakA tAntave (vA 4532) :</p>\n";
    echo "<p class = st >वर्णका तान्तवे (वा ४५३२) :</p>\n";
    display(0);
}
/* vartakA zakunau prAcAm (vA 4533) */
elseif ($gender==="f" && $Ap===1 && sub(array("vartaka","vartak"),array("A"),blank(0),0) )
{
    $text = one(array("vartakaA","vartakA"),array("vartikaA","vartikA"),1);
    echo "<p class = st >By vartakA zakunau prAcAm (vA 4533) :</p>\n";
    echo "<p class = st >वर्तका शकुनौ प्राचाम्‌ (वा ४५३३) :</p>\n";
    display(0);
}
/* aSTakA pitRdevatye (vA 4534) */
elseif ($gender==="f" && $Ap===1 && sub(array("azwaka","azwak"),array("A"),blank(0),0) )
{
    $text = one(array("azwakaA","azwakA"),array("azwikaA","azwikA"),1);
    echo "<p class = st >By aSTakA pitRdevatye (vA 4534) :</p>\n";
    echo "<p class = st >अष्टका पितृदेवत्ये (अा ४५३४) :</p>\n";
    display(0);
}
/* sUtakAputrikAvRndArakANAM veti vaktavyam (vA 4535) */
elseif ($gender==="f" && $Ap===1 && sub(array("sUtaka","sUtak","vfndAraka","vfndArak","putrika","putrik"),array("A"),blank(0),0) )
{
    $text = two(array("sUtaka","sUtak","vfndAraka","vfndArak","putrika","putrik"),array("A"),array("sUtika","sUtik","vfndArika","vfndArik","putraka","putrak"),array("A"),1);
    echo "<p class = st >By sUtakAputrikAvRndArakANAM veti vaktavyam (vA 4535) :</p>\n";
    echo "<p class = st >सूतकापुत्रिकावृन्दारकाणां वेति वक्तव्यम्‌ (वा ४५३५) :</p>\n";
    display(0);
}
/* bhastraiSAjAjJAdvAsvA naJpUrvANAmapi (7.3.47) */
// in these words, words ending with these words can also apply this rule.
elseif ($gender==="f" && $Ap===1 && sub(array("Bastraka","Bastrak","ajaka","ajak","jYaka","jYak","svaka","svak"),array("A"),blank(0),0) )
{
    $text = two(array("Bastraka","Bastrak","ajaka","ajak","jYaka","jYak","svaka","svak"),array("A"),array("Bastrika","Bastrik","ajika","ajik","jYika","jYik","svika","svik"),array("A"),1);
    echo "<p class = sa >By bhastraiSAjAjJAdvAsvA naJpUrvANAmapi (".link_sutra("7.3.47").") :</p>\n";
    echo "<p class = sa >भस्रैषाजाज्ञाद्वास्वा नञ्पूर्वाणामपि (७.३.४७) :</p>\n";
    display(0);
}
/* bhastraiSAjAjJAdvAsvA naJpUrvANAmapi (7.3.47) */
// in these words, the word itself only has to be checked.
elseif ($gender==="f" && $Ap===1 && in_array($fo,array("ezaka","ezak","dvaka","dvak")))
{
    $text = two(array("ezaka","ezak","ajaka","ajak","jJaka","jJak","dvaka","dvak"),array("A"),array("ezika","ezik","ajika","ajik","jJika","jJik","dvika","dvik"),array("A"),1);
    echo "<p class = sa >By bhastraiSAjAjJAdvAsvA naJpUrvANAmapi (".link_sutra("7.3.47").") :</p>\n";
    echo "<p class = sa >भस्रैषाजाज्ञाद्वास्वा नञ्पूर्वाणामपि (७.३.४७) :</p>\n";
    display(0);
}
/* bhastraiSAjAjJAdvAsvA naJpUrvANAmapi (7.3.47) */
// patch to prevent application to words ending with eSaka and dvaka.
elseif ($gender==="f" && $Ap===1 && ends(array($fo),array("ezaka","ezak","dvaka","dvak"),0))
{
}
/* mAmakanarakayorupasaGkhyAnam (vA 4524) */
elseif ($gender==="f" && $Ap===1 && sub(array("mAmaka","naraka"),array("A"),blank(0),0) && $_GET['cond2_11']==="1" )
{
    $text = one(array("mAmakaA","narakaA"),array("mAmikaA","narikaA"),0);
    echo "<p class = st >By mAmakanarakayorupasaGkhyAnam (vA 4524) :</p>\n";
    echo "<p class = st >मामकनरकयोरुपसङ्ख्यानम्‌ (वा ४५२४) :</p>\n";
    display(0);
}
/* tyaktyapozca (vA 4525) */
elseif ($gender==="f" && $Ap===1 && sub(array("dAkziRAtyaka","pAzcAtyaka","pOrastyaka","amAtyaka","ihatyaka","kvatyaka","itastyaka","tatastyaka","kutastyaka","bahutastyaka","tatratyaka","atratyaka","yatratyaka","bahutratyaka","nityaka","nizwyaka","Avizwyaka",),array("A"),blank(0),0) && $_GET['cond2_12']==="1" )
{
    $text = one(array("tyakaA"),array("tyikaA",),0);
    echo "<p class = st >By tyaktyapozca (vA 4525) :</p>\n";
    echo "<p class = st >त्यक्त्यपोश्च (वा ४५२५) :</p>\n";
    display(0);
}
/* na yAsayoH (7.3.45) */
elseif ($gender==="f" && $Ap===1 && sub(array("yaka","saka","taka"),array("A"),blank(0),0) && in_array($fo,array("yaka","saka","yak","sak","taka","tak")))
{
    echo "<p class = sa >By na yAsayoH (".link_sutra("7.3.45").") :</p>\n";
    echo "<p class = sa >न यासयोः (७.३.४५) :</p>\n";
    display(0);
}
/* pratyayasthAtkAtpUrvasyAta idApyasupaH (7.3.44) */
elseif ($gender==="f" && $Ap===1 && sub(array("aka","ak"),array("A"),blank(0),0) && ($_GET['cond2_11']==="1"||$_GET['cond2_13_2']==="2") )
{
    $text = one(array("akaA","akA"),array("ikaA","ikA"),0);
    echo "<p class = sa >By pratyayasthAtkAtpUrvasyAta idApyasupaH (".link_sutra("7.3.44").") :</p>\n";
    echo "<p class = sa >प्रत्ययस्थात्कात्पूर्वस्यात इदाप्यसुपः (७.३.४४) :</p>\n";
    display(0);
}

/* AyaneyInIyiyaH phaDhakhaCaghAM pratyayAdInAm (7.1.2) */
if(sub(array("+"),array("Pa","Qa","Ka","Ca","Ga"),array("+"),0))
{
    $text = three(array("+"),array("Pa","Qa","Ka","Ca","Ga"),array("+"),array("+"),array("Ayana","eya","Ina","Iya","iya"),array("+"),0);
    echo "<p class = sa >By AyaneyInIyiyaH phaDhakhaCaghAM pratyayAdInAm (".link_sutra("7.1.2").") :</p>\n";
    echo "<p class = sa >आयनेयीनीयियः फढखछघां प्रत्ययादीनाम्‌ (७.१.२) :</p>\n";
    display(0);
}
/* patch for akaH savarNe dIrghaH in TAp, DAp, cAp */
if ($TAp===1 || $DAp===1 || $cAp===1 )
{
    /* akaH savarNe dIrghaH (6.1.101) */ 
    if (sub($ak1,$ak2,blank(28),1))
    {
    $text = two(array("a","A"),array("a","A"),array("A","A"),blank(2),0);
    echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
    echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
    display(0);
    }    
}

/* dRnkarapunaHpUrvasya bhuvo yaN vaktavyaH (vA 4118) */ 
if ($dhatu===1 && in_array($fo,array("dfnBU","karaBU","kAraBU","punarBU")) && in_array($so,$sup))
{
 $dRnkar=1; // 0 - the word doesn't belong to dRnbhU, karabhU etc. 1 - the word belongs to dRnbhU, karabhU etc.
} else {$dRnkar=0; } 
/* yU stryAkhyau nadI (1.4.3) and prathamaliGgagrahaNaJca (vA 1036) */
// the vArtika is not coded perfectly. Also stryAkhyo is pending.
if ($gender === "f" && !in_array($fo,array("stri")) && arr($text,'/[iu][+][N]/') && in_array($so,array("Ne","Nasi!","Nas","Ni")) && $dRnkar===0) // this was commented because it gave nadisaJjJA to priyatri - priyAH tisraH yasya saH.
{   
    echo "<p class = pa >By Giti hrasvazca (".link_sutra("1.4.6").") :</p>\n";
    echo "<p class = pa >ङिति ह्रस्वश्च (१.४.६) :</p>\n";
    display(0);
    $nadi = 2; // Giti vA.
}
if ($gender === "f" && !in_array($fo,array("strI")) && arr($text,'/[IU][+][N]/') && $nityastri===1 && in_array($so,array("Ne","Nasi!","Nas","Ni")) && $dRnkar===0 && $dhatu===1 && ( $eranekaca===0 && anekAca($fo) || ends(array($fo),array("BrU"),1)) ) // this was commented because it gave nadisaJjJA to priyatri - priyAH tisraH yasya saH.
//if ($gender === "f" && !in_array($fo,array("strI")) && (arr($text,'/[iuIU][+][N]/')  && $nityastri===1) && in_array($so,array("Ne","Nasi!","Nas","Ni")) && $dRnkar===0)
{   
    echo "<p class = pa >By Giti hrasvazca (".link_sutra("1.4.6").") :</p>\n";
    echo "<p class = pa >ङिति ह्रस्वश्च (१.४.६) :</p>\n";
    display(0);
    $nadi = 2; // Giti vA.
}
elseif ( $gender==="f" && !in_array($fo,array("stri","strI")) && $dhatu===1 && arr($text,'/[IU][+][A][m]$/') && $nityastri===1 && $dRnkar===0 && $dhatu===1 && ( $eranekaca===0 && anekAca($fo) || ends(array($fo),array("BrU"),1)))
{
    echo "<p class = pa >By vA'mi (".link_sutra("1.4.5").") :</p>\n";
    echo "<p class = pa >वाऽऽमि (१.४.५) :</p>\n";
    display(0);
    $nadi = 2;
}
//elseif ( $gender==="f" && !in_array($fo,array("stri","strI")) && $dhatu===1 && arr($text,'/[IU][+]/') && $dRnkar===0 && $eranekaca!==2 && !in_array($so,array("su!","ByAm","Bis","sup")))
elseif ( $gender==="f" && !in_array($fo,array("stri","strI")) && $dhatu===1 && arr($text,'/[IU][+]/') && $dRnkar===0 && $eranekaca!==2 )
{
    echo "<p class = pa >By neyaGuvaGsthAnAvastrI (".link_sutra("1.4.4").") :</p>\n";
    echo "<p class = pa >नेयङुवङ्स्थानावस्त्री (१.४.४) :</p>\n";
    display(0);
    $nadi = 0;
}
elseif ( (( arr($text,'/[IU][+]/') && $nityastri===1) || in_array($fo,array("bahuSreyasI","aticamU"))))
{
    if (in_array($fo,array("bahuSreyasI","aticamU")))
    {
    echo "<p class = pa >By yU stryAkhyau nadI (".link_sutra("1.4.3").") and prathamaliGgagrahaNaJca (vA 1036) :</p>\n";
    echo "<p class = pa >यू स्त्र्याख्यौ नदी (१.४.३) तथा प्रथमलिङ्गग्रहणञ्च (वा १०३६) :</p>\n";
    display(0);
    }
    else
    {
    echo "<p class = pa >By yU stryAkhyau nadI (".link_sutra("1.4.3").") :</p>\n";
    echo "<p class = pa >यू स्त्र्याख्यौ नदी (१.४.३) :</p>\n";
    display(0);
    }
    $nadi = 1;
}
/* abhyasta definition */
if ($_GET['cond1_17_2']==="1")
{
    $abhyasta=1;
    $shatR=1;
}
/* jakSityAdayaH SaT (6.1.6) */
if (sub(array("jakzat","jAgrat","daridrat","SAsat","cakAsat","dIDyat","vevyat"),blank(0),blank(0),0))
{
    $abhyasta=1; 
    $jaksat=1; // 0 - doesn't belong to jakSityAdi. 1 - belongs to jakSityAdi.
    echo "<p class = pa >By jakSityAdayaH SaT (".link_sutra("6.1.6").") :</p>\n";
    echo "<p class = pa >जक्षित्यादयः षट्‍ (६.१.६) :</p>\n";
    display(0);
} else { $jaksat=0; } // when the variables which are not initialised are used, we keep this else box. Otherwise for future uses, $jaksat will be null and PHP will send notices to browser.
/* ubhe abhyastam (6.1.5) */
if ($abhyasta===1 && $jaksat===0)
{
    echo "<p class = pa >By ubhe abhyastam (".link_sutra("6.1.5").") :</p>\n";
    echo "<p class = pa >उभे अभ्यस्तम्‌ (६.१.५) :</p>\n";
    display(0);
}
/* tyadAdiSu dRSo'nAlocane kaJca (3.2.60) */
if ( sub($tyadadi,array("dfS"),blank(0),0) )
{
    echo "<p class = pa >By tyadAdiSu dRSo'nAlocane kaJca (".link_sutra("3.2.60").") :</p>\n";
    echo "<p class = pa >त्यदादिषु दृशोऽनालोचने कञ्च (३.२.६०) :</p>\n";
    display(0); $kvin=1;
}
/* kvin pratyaya from asRj */
if ( $fo==="asfj"  && in_array($so,$sup) )
{
    echo "<p class = pa >asRj has kutva at padAnta, because of mandate of kvin pratyaya after sRj. </p>\n";
    echo "<p class = pa >असृजः पदान्ते कुत्वम्‌, सृजेः क्विनो विधानात्‌ ।</p>\n";
    display(0); $kvin=1;
}
/* no kvin pratyaya from viSvasRj */
if ( $fo==="viSvasfj"  && in_array($so,$sup) )
{
    echo "<p class = pa >'rajjusRDbhyAm' usage in bhASya under 'sRjidRSoH' sUtra mandates that there will be no kutva here.</p>\n";
    echo "<p class = pa >विश्वसृड्‌ इत्यादौ तु कुत्वं न । 'सृजिदृशोः' इति सूत्रे 'रज्जुसृड्भ्याम्‌' इति भाष्यप्रयोगात्‌ ।</p>\n";
    display(0); $kvin=0;
}
/* spRSo'nudake kvin (3.2.58) */
if ( sub(array("spfS"),array("+"),blank(0),0) )
{
    echo "<p class = pa >By spRSo'nudake kvin (".link_sutra("3.2.58").") :</p>\n";
    echo "<p class = pa >स्पृशोऽनुदके क्विन्‌ (३.२.५८) :</p>\n";
    display(0); $kvin=1;
}
/* Defining $vasu */
if ( sub(array("vidvas","sedivas","uzivas","Suzruvas","upeyivas","anASvas"),array("+"),blank(0),0) )
{
    $text = two(array("vidvas","sedivas","uzivas","Suzruvas","upeyivas","anASvas"),array("+"),array("vidvasu!","sedivasu!","uzivasu!","Suzruvasu!","upeyivasu!","anASvasu!"),array("+"),0);
    echo "<p class = pa >This is a vasvanta word.</p>\n";
    echo "<p class = pa >यह एक वस्वन्त शब्द है ।</p>\n";
    display(0); $vasu=1;
}
/* dRnkarapunaHpUrvasya bhuvo yaN vaktavyaH (vA 4118) */ 
if ($dhatu===1 && in_array($fo,array("dfnBU","karaBU","kAraBU","punarBU")) && (in_array($so,array("Ne","Nasi!","Nas","Ni")) || ($sambuddhi===1 && $so==="su!") ) && arr($text,'/[U][+]['.pc('ac').']/'))
{
    echo "<p class = pa >By dRnkarapunaHpUrvasya bhuvo yaN vaktavyaH (vA 4118), yaN bars application of iyaG,uvaG. Therefore, nadIkAryas will happen. :</p>\n";
    $nadi=1;
    if (in_array($fo,array("karaBU","kAraBU")))
    {
    echo "<p class = hn >There is pAThabheda here. Some hold that there is dIrgha kAra word here. SK has adopted both the readings, therefore we have kept them optional.</p>\n";        
    $nadi=2;
    }
    echo "<p class = pa >दृन्करपुनःपूर्वस्य भुवो यण्‌ वक्तव्यः (वा ४११८) द्वारा प्राप्त यण्‌ - इयङ्‌/उवङ्‌ का बाधन करता है । अतः नदीकार्य होंगे ।</p>\n";
    if (in_array($fo,array("karaBU","kAraBU")))
    {
    echo "<p class = hn >दीर्घपाठे करपूर्वस्य उवङेव । ह्रस्वपाठे करपूर्वस्य यणेव इति विवेकः ।</p>\n";        
    }
    display(0); 
}
/* adDDatarAdibhyaH paJcabhyaH (7.1.25) and ekatarAtpratiSedho vaktavyaH (vA 4287) */
if (sub(array("ekatara"),array("+"),array("su!","am"),0) && $gender==="n")
{
    echo "<p class = pa >ekatarAtpratiSedho vaktavyaH (vA 4287) :</p>\n";
    echo "<p class = pa >एकतरात्प्रतिषेधो वक्तव्यः (वा ४२८७) :</p>\n";        
    display(0); $ekatara=1; // 0 - the word is not ekatara. 1 - the word is ekatara. This will be useful in adDDatarAdibhyaH paJcabhyaH (7.1.25).
} else {$ekatara=0; }
if (sub(array("atara","atama","anya","anyatara","itara"),array("+"),array("su!","am"),0) && $gender==="n" && $ekatara===0)
{
    $text = two(array("a+",),array("su!","am"),array("a+"),array("adq","adq"),0);
    echo "<p class = sa >By adDDatarAdibhyaH paJcabhyaH (".link_sutra("7.1.25").") :</p>\n";
    echo "<p class = sa >अद्ड्डतरादिभ्यः पञ्चभ्यः (७.१.२५) :</p>\n";
    display(3); 
    $Dit = 1; // 0 - 'D' is not it. 1 - 'D' is it.
    $adD=1; // 0 - adD suffix has not been applied. 1 - adD suffix is applied.
} else {$adD = 0; $Dit =0;}
/* maghavA bahulam (6.4.128) */
if (sub(array("maGavan"),array("+"),blank(0),0) && in_array($so,$sup))
{
    $text = two(array("maGavan"),array("+"),array("maGavatf!"),array("+"),1);
    echo "<p class = sa >By maghavA bahulam (".link_sutra("6.4.128").") :</p>\n";
    echo "<p class = sa >मघवा बहुलम्‌ (६.४.१२८) :</p>\n";
    display(3); 
    $it = array_merge($it,array("f")); 
    $itprakriti = array_merge($itprakriti,array("f")); 
}
/* arvaNastrasAvanaJa (6.4.127) */
if (sub(array("arvan"),array("+"),blank(0),0) && in_array($so,$sup) && $fo!=="anarvan" && $so!=="su!")
{
    $text = two(array("arvan"),array("+"),array("arvatf!"),array("+"),0);
    echo "<p class = sa >By arvaNastrasAvanaJa (".link_sutra("6.4.127").") :</p>\n";
    echo "<p class = sa >अर्वणस्त्रसावनञः (६.४.१२७) :</p>\n";
    display(3); 
    $it = array_merge($it,array("f")); 
    $itprakriti = array_merge($itprakriti,array("f")); 
}
if (sub(array("arvan"),array("+"),blank(0),0) && in_array($so,$sup) && ($fo=="anarvan" || $so!=="su!"))
{
    echo "<p class = pa >'tR' Adeza of arvaNastrasAvanaJa (".link_sutra("6.4.127").") doesnt apply here. </p>\n";
    echo "<p class = pa >अर्वणस्त्रसावनञः (६.४.१२७) का 'तृ' आदेश यहाँ नहीं होता है ।</p>\n";
    display(0); 
}
/* patch for dRz */
if (sub(array("dfS"),array("+"),blank(0),0))
{
    $kvip=1;
    $kvin=1;
}
/* RtvigdadhRksragdiguSNigaJcuyujikruJcAM ca (3.2.59) */
if (sub(array("ftvij","daDfz","sraj","diS","zRih","aYcu","yuj","kruYc","anc","Anc","aYc","AYc","krunc"),array("+"),blank(0),0))
{
    $kvin=1;
}
if (sub(array("ftvij","daDfz","sraj","diS","zRih","aYcu","yuj","kruYc","anc","Anc","aYc","AYc","krunc"),array("+"),blank(0),0) && $kvin===1 )
{
    echo "<p class = sa >By RtvigdadhRksragdiguSNigaJcuyujikruJcAM ca (".link_sutra("3.2.59").") :</p>\n";
    echo "<p class = sa >ऋत्विग्दधृक्स्रग्दिगुष्णिगञ्चुयुजिक्रुञ्चां च (३.२.५९) :</p>\n";
    display(3);
}
/* rAyo hali (7.2.85) */
if (sub(array("rE"),array("+"),$hlsup,0) && in_array($so,$hlsup) && !($gender==="n" && $so==="su!") )
        
{
    $text = two(array("rE"),array("+"),array("rA"),array("+"),0);
    echo "<p class = sa >By rAyo hali (".link_sutra("7.2.85").") :</p>\n";
    echo "<p class = sa >रायो हलि (७.२.८५) :</p>\n";
    display(3); 
    $rayo=1; // 0 - rAyo hali has not applied. 1 - rAyo hali has applied. Useful in hrasvo napuMsake prAtipadikasya (1.2.47).
} else {$rayo = 0; }
/* hrasvo napuMsake prAtipadikasya (1.2.47) */
$achrasva= array("a","a","i","i","u","u","f","f","x","x","i","u","i","u",);
if (sub($ac,array("+"),blank(0),0) && $gender==="n" && in_array($so,$sup)  && $rayo===0)
{
    if (sub(array("e","o","E","O"),array("+"),blank(0),0))
    {
    $text = two($ac,array("+"),$achrasva,array("+"),0);        
    echo "<p class = sa >By hrasvo napuMsake prAtipadikasya (".link_sutra("1.2.47").") :</p>\n";
    echo "<p class = pa >By eca igghrasvAdeze (".link_sutra("1.1.47").") :</p>\n";
    echo "<p class = sa >ह्रस्वो नपुंसके प्रातिपदिकस्य (१.२.४७) :</p>\n";
    echo "<p class = pa >एच इग्घ्रस्वादेशे (१.२.४७) :</p>\n";
    display(0);       
    echo "<p class = pa >This word is not bhASitapuMska. </p>\n";
    echo "<p class = pa >यह शब्द भाषितपुंस्क नहीं है ।</p>\n";
    display(0);       
    }
    elseif($bhashitapumska===1 && in_array($so,$tRtIyAdiSvaci))
    {
    $text = two($ac,array("+"),$achrasva,array("+"),1);        
    echo "<p class = sa >By hrasvo napuMsake prAtipadikasya (".link_sutra("1.2.47").") :</p>\n";
    echo "<p class = sa >ह्रस्वो नपुंसके प्रातिपदिकस्य (१.२.४७) :</p>\n";
    display(0);       
    }
    else
    {
    $text = two($ac,array("+"),$achrasva,array("+"),0);        
    echo "<p class = sa >By hrasvo napuMsake prAtipadikasya (".link_sutra("1.2.47").") :</p>\n";
    echo "<p class = sa >ह्रस्वो नपुंसके प्रातिपदिकस्य (१.२.४७) :</p>\n";
    display(0);         
    }
}

/* ato'm (7.1.24) */
if (sub(array("a"),array("+"),array("su!","am"),0) && $gender==="n" && $adD ===0)
{
    $text = two(array("a+",),array("su!","am"),array("a+"),array("am","am"),0);
    echo "<p class = sa >By ato'm (".link_sutra("7.1.24").") :</p>\n";
    echo "<p class = sa >अतोऽम्‌ (७.१.२४) :</p>\n";
    display(3); $atom=1;
} else { $atom =0; }
/* defining whether the first word is asmad / yuzmad */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,$sup))
{
    $asmad=1; // 0 - the word doesn't end with asmad / yuSmad. 1 - the word ends with asmad / yuSmad.
}
else
{
    $asmad=0;
}
/* Whole replacements for asmad / yuSmad */
/* yuSmadasmadoH SaSThIcaturthIdvitIyAsthayorvAMnAvau (8.1.20) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($w,array(4,10,16)) && $asmadpada>0 )
{
    if($asmadpada===2)
    {
    $text = two(array("yasmad+","asmad+","yuzmad+"),array("Ow","ByAm","os"),array("inO","nO","vAm"),array("","",""),1);        
    }
    else
    {
    $text = two(array("yasmad+","asmad+","yuzmad+"),array("Ow","ByAm","os"),array("inO","nO","vAm"),array("","",""),0);        
    }
    echo "<p class = sa >By yuSmadasmadoH SaSThIcaturthIdvitIyAsthayorvAMnAvau (".link_sutra("8.1.20").") :</p>\n";
    echo "<p class = sa >युष्मदस्मदोः षष्ठीचतुर्थीद्वितीयास्थयोर्वांनावौ (८.१.२०) :</p>\n";
    display(7);
}
/* bahuvacanasya vasnasau (8.1.21) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && (in_array($so,array("Sas","Am")) || (in_array($so,array("Byas")) && $w===11)) && $asmadpada>0 )
{
    if ($asmadpada===2)
    {
    $text = two(array("yasmad+","asmad+","yuzmad+"),array("Sas","Byas","Am"),array("inas","nas","vas"),array("","",""),1);        
    }
    else
    {
    $text = two(array("yasmad+","asmad+","yuzmad+"),array("Sas","Byas","Am"),array("inas","nas","vas"),array("","",""),0);        
    }
    echo "<p class = sa >By bahuvacanasya vasnasau (".link_sutra("8.1.21").") :</p>\n";
    echo "<p class = sa >बहुवचनस्य वस्नसौ (८.१.२१) :</p>\n";
    display(7);
}
/* temayAvekavacanasya (8.1.22) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("Ne","Nas")) && $asmadpada>0 )
{
    if ($asmadpada===2)
    {
    $text = two(array("yasmad+","asmad+","yuzmad+"),array("Ne","Nas"),array("ime","me","te"),array("",""),1);        
    }
    else
    {
    $text = two(array("yasmad+","asmad+","yuzmad+"),array("Ne","Nas"),array("ime","me","te"),array("",""),0);        
    }
    echo "<p class = sa >By temayAvekavacanasya (".link_sutra("8.1.22").") :</p>\n";
    echo "<p class = sa >तेमयावेकवचनस्य (८.१.२२) :</p>\n";
    display(7);
}
/* tvAmau dvitIyAyAH (8.1.23) */ 
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("am")) && $asmadpada>0 )
{
    if ($asmadpada===2)
    {
    $text = two(array("yasmad+","asmad+","yuzmad+"),array("am"),array("imA","mA","tvA"),array(""),1);        
    }
    else
    {
    $text = two(array("yasmad+","asmad+","yuzmad+"),array("am"),array("imA","mA","tvA"),array(""),0);        
    }
    echo "<p class = sa >By tvAmau dvitIyAyAH (".link_sutra("8.1.23").") :</p>\n";
    echo "<p class = sa >त्वामौ द्वितीयायाः (८.१.२३) :</p>\n";
    display(7);
}
/* pratyaya Adeza for yuSmad / asmad */
/* yuSmadasmadbhyAM Gaso'z (7.1.27) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("Nas")))
{
    $text = two(array("+"),array("Nas"),array("+"),array("a"),0);
    echo "<p class = sa >By yuSmadasmadbhyAM Gaso'z (".link_sutra("7.1.27").") :</p>\n";
    echo "<p class = sa >युष्मदस्मद्भ्यां ङसोऽश्‍ (७.१.२७) :</p>\n";
    display(3);
}
/* Geprathamayoram (7.1.28) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("Ne","su!","Ow","O","jas","am")))
{
    $text = two(array("+"),array("Ne","su!","Ow","O","jas","am"),array("+"),array("am","am","am","am","am","am"),0);
    echo "<p class = sa >By Geprathamayoram (".link_sutra("7.1.28").") :</p>\n";
    echo "<p class = sa >ङेप्रथमयोरम्‌ (७.१.२८) :</p>\n";
    display(3);
}
/* zaso na (7.1.29) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("Sas")))
{
    $text = two(array("+"),array("Sas"),array("+"),array("ns"),0);
    echo "<p class = sa >By zaso na (".link_sutra("7.1.29").") :</p>\n";
    echo "<p class = hn >This sUtra prevents application of Geprathamayoram. Also the Adeza is of the first letter by AdeH parasya.</p>\n";
    echo "<p class = sa >शसो न (७.१.२९) :</p>\n";
    echo "<p class = hn >यह सूत्र ङेप्रथमयोरम्‌ का बाध करता है । आदेश आदेः परस्य सूत्र से अकार का है ।</p>\n";
    display(3);
}
/* bhyaso bhyam (7.1.30) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("Byas")) && $w===11)
{
    $text = two(array("+"),array("Byas"),array("+"),array("aByam"),0);
    echo "<p class = sa >By bhyaso bhyam (".link_sutra("7.1.30").") :</p>\n";
    echo "<p class = sa >भ्यसो भ्यम्‌ (७.१.३०) :</p>\n";
    display(3);
}
/* paJcamyA at (7.1.32) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("Byas")) && $w===14)
{
    $text = two(array("+"),array("Byas"),array("+"),array("at"),0);
    echo "<p class = sa >By paJcamyA at (".link_sutra("7.1.32").") :</p>\n";
    echo "<p class = sa >पञ्चम्या अत्‌ (७.१.३२) :</p>\n";
    display(3);
}
/* ekavacanasya ca (7.1.32) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("Nasi!")))
{
    $text = two(array("+"),array("Nasi!"),array("+"),array("at"),0);
    echo "<p class = sa >By ekavacanasya ca (".link_sutra("7.1.32").") :</p>\n";
    echo "<p class = sa >एकवचनस्य च (७.१.३२) :</p>\n";
    display(3);
}
/* prakRti Adezas for asmad / yuSmad */
/* yo'ci (7.2.89) */
if (sub(array("asmad","yuzmad",),array("+"),blank(0),0) && in_array($so,array("wA","Ni","os")) )
{
    $text = two(array("asmad","yuzmad",),array("+"),array("asmay","yuzmay",),array("+"),0);
    echo "<p class = sa >By yo'ci (".link_sutra("7.2.89").") :</p>\n";
    echo "<p class = sa >योऽचि (७.२.८९) :</p>\n";
    display(3);    
}
/* yuSmadasmadoranAdeze (7.2.86) */
if (sub(array("asmad","yuzmad"),array("+"),$tRtIyAdi,0) && in_array($so,array("ByAm","Bis","sup")) )
{
    $text = two(array("asmad","yuzmad",),array("+"),array("asmaA","yuzmaA"),array("+"),0);
    echo "<p class = sa >By yuSmadasmadoranAdeze (".link_sutra("7.2.86").") :</p>\n";
    echo "<p class = sa >युष्मदस्मदोरनादेशे (७.२.८६) :</p>\n";
    display(3);    
}
/* dvitIyAyAJca (7.2.87) */
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("am","Ow","Sas")))
{
    $text = two(array("asmad","yuzmad",),array("+"),array("asmaA","yuzmaA"),array("+"),0);
    echo "<p class = sa >By dvitIyAyAJca (".link_sutra("7.2.87").") :</p>\n";
    echo "<p class = pa >maparyantasya (".link_sutra("7.2.91").") :</p>\n";
    echo "<p class = sa >द्वितीयायाञ्च (७.२.८७) :</p>\n";
    display(3);
}
/* zeSe lopaH (7.2.90) */
// strIliGga of asmad and yuSmad still pending to be clarified.
if (sub(array("asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("su!","jas","Ne","Byas","Nasi!","Nas","Am")))
{
    $text = two(array("asmad","yuzmad"),array("+"),array("asma","yuzma"),array("+"),0);  
    echo "<p class = sa >By zeSe lopaH (".link_sutra("7.2.90").") :</p>\n";
    echo "<p class = sa >शेषे लोपः (७.२.९०) :</p>\n";
    display(3);
}
/* maparyantasya Adezas for asmad / yuSmad */
/* yUyavayau jasi (7.2.93) */
if (sub(array("asm","yuzm"),array(""),blank(0),0) && $asmad===1 && in_array($so,array("jas")))
{
    $text = one(array("yasm","asm","yuzm"),array("ivaya","vaya","yUya"),0); // for function one please see function.php.
    echo "<p class = sa >By yUyavayau jasi (".link_sutra("7.2.93").") :</p>\n";
    echo "<p class = pa >maparyantasya (".link_sutra("7.2.91").") :</p>\n";
    echo "<p class = sa >यूयवयौ जसि (७.२.९३) :</p>\n";
    echo "<p class = pa >मपर्यन्तस्य (७.२.९१) :</p>\n";
    display(3);
}
/* tvAhau sau (7.2.94) */
if (sub(array("asm","yuzm"),array(""),blank(0),0) && $asmad===1 && in_array($so,array("su!")))
{
    $text = one(array("asm","yuzm"),array("aha","tva"),0);
    echo "<p class = sa >By tvAhau sau (".link_sutra("7.2.94").") :</p>\n";
    echo "<p class = pa >maparyantasya (".link_sutra("7.2.91").") :</p>\n";
    echo "<p class = sa >त्वाहौ सौ (७.२.९४) :</p>\n";
    echo "<p class = pa >मपर्यन्तस्य (७.२.९१) :</p>\n";
    display(3);
}
/* tubhyamahyau Gayi (7.2.95) */
if (sub(array("asm","yuzm"),array(""),blank(0),0) && $asmad===1 && in_array($so,array("Ne")))
{
    $text = one(array("yasm","asm","yuzm"),array("imahya","mahya","tuBya"),0);
    echo "<p class = sa >By tubhyamahyau Gayi (".link_sutra("7.2.95").") :</p>\n";
    echo "<p class = pa >maparyantasya (".link_sutra("7.2.91").") :</p>\n";
    echo "<p class = sa >तुभ्यमह्यौ ङयि (७.२०९५) :</p>\n";
    echo "<p class = pa >मपर्यन्तस्य (७.२.९१) :</p>\n";
    display(3);
}
/* tavamamau Gasi (7.2.96) */
if (sub(array("asm","yuzm"),array(""),blank(0),0) && $asmad===1 && in_array($so,array("Nas")))
{
    $text = one(array("yasm","asm","yuzm"),array("imama","mama","tava"),0);
    echo "<p class = sa >By tavamamau Gasi (".link_sutra("7.2.96").") :</p>\n";
    echo "<p class = pa >maparyantasya (".link_sutra("7.2.91").") :</p>\n";
    echo "<p class = sa >तवममौ ङसि (७.२.९६) :</p>\n";
    echo "<p class = pa >मपर्यन्तस्य (७.२.९१) :</p>\n";
    display(3);
}
/* tvamAvekavacane (7.2.97) */
if (sub(array("asm","yuzm"),array(""),blank(0),0) && $asmad===1 && (in_array($so,array("am","wA","Nasi!","Ni")) && (in_array($fo,array("asmad","yuzmad"))  ) || $_GET['cond1_11']==="1") )
{
    $text = one(array("yasm","yuzm","asm"),array("ima","tva","ma"),0);
    echo "<p class = sa >By tvamAvekavacane (".link_sutra("7.2.97").") :</p>\n";
    echo "<p class = pa >maparyantasya (".link_sutra("7.2.91").") :</p>\n";
    echo "<p class = sa >त्वमावेकवचने (७.२.९७) :</p>\n";
    echo "<p class = pa >मपर्यन्तस्य (७.२.९१) :</p>\n";
    display(3);
}
/* yuvAvau dvivacane (7.2.92) */
if (sub(array("asm","yuzm"),array(""),blank(0),0) && $asmad===1 && ((in_array($so,$dvisup) && in_array($fo,array("asmad","yuzmad")))  || $_GET['cond1_11']==="2") )
{
    $text = one(array("asm","yuzm"),array("Ava","yuva"),0);
    echo "<p class = sa >By yuvAvau dvivacane (".link_sutra("7.2.92").") :</p>\n";
    echo "<p class = pa >maparyantasya (".link_sutra("7.2.91").") :</p>\n";
    echo "<p class = sa >युवावौ द्विवचने (७.२.९२) :</p>\n";
    echo "<p class = pa >मपर्यन्तस्य (७.२.९१) :</p>\n";
    display(3);
}
/* ato guNe patch */
// Because ato guNe sometimes interferes with akaH savarNe dIrghaH, it is treated specially wherever it is applicable.
if ($asmad===1 && sub(array("aa","a+aByam","a+at","tava+a","mama+a","Ava+aByam","yuva+aByam","Ava+at","yuva+at"),blank(0),blank(0),0))
{
    $text = one(array("aa",),array("a"),0);
    $text = two(array("asma+aByam","yuzma+aByam","ma+aByam","tva+aByam","Ava+aByam","yuva+aByam"),array(""),array("asm+aByam","yuzm+aByam","m+aByam","tv+aByam","Av+aByam","yuv+aByam"),array(""),0);
    $text = two(array("asma+at","yuzma+at","tva+at","ma+at","tava+a","mama+a","Ava+at","yuva+at"),array(""),array("asm+at","yuzm+at","tvat","mat","tava","mama","Av+at","yuv+at"),array(""),0);
    echo "<p class = sa >By ato guNe (".link_sutra("6.1.96").") :</p>\n";
    echo "<p class = sa >अतो गुणे (६.१.९६) :</p>\n";
    display(3);    
}
/* prathamAyAzca dvivacane bhASAyAm (7.2.88) */
if (sub(array("Avad","yuvad","imad","itvad","asmad","yuzmad"),array("+"),blank(0),0) && in_array($so,array("O")) && $asmad===1)
{
    $text = two(array("Avad","yuvad","imad","itvad","asmad","yuzmad"),array("+"),array("AvaA","yuvaA","imaA","itvaA","asmaA","yuzmaA"),array("+"),0);
    echo "<p class = sa >By prathamAyAzca dvivacane bhASAyAm (".link_sutra("7.2.88").") :</p>\n";
    echo "<p class = sa >प्रथमायाश्च द्विवचने भाषायाम्‌ (७.२.९१) :</p>\n";
    display(3);
}
/* aGgakArye kRte punarnAGgakAryam (pa 93) */
if (sub(array("vaya","yUya"),array("+"),array("am"),0) && $so==="jas" )
{
    echo "<p class = pa >aGgakArye kRte punarnAGgakAryam (pa 93) prevents application of jasaH zI.</p>\n";
    echo "<p class = pa >अङ्गकार्ये कृते पुनर्नाङ्गकार्यम्‌ (प ९३) से जसः शी का प्रतिषेध होता है ।</p>\n";
    display(0); $nojas=1;   
} else { $nojas=0; }
/* idamo maH (7.2.108) */
if (sub(array("idam","idakam"),array("+"),blank(0),0) && $so==="su!")
{
    echo "<p class = sa >By idamo maH (".link_sutra("7.2.108").") :</p>\n";
    echo "<p class = sa >इदमो मः (७.२.१०८) :</p>\n";
    display(3); 
    /* ido'y puMsi (7.2.111) */
    if (sub(array("idam","idakam"),array("+"),blank(0),0) && $so==="su!" && $gender==="m")
    {
        $text = two(array("idam","idakam"),array("+"),array("ayam","ayakam"),array("+"),0);
        echo "<p class = sa >By ido'y puMsi (".link_sutra("7.2.111").") :</p>\n";
        echo "<p class = sa >इदोऽय्‌ पुंसि (७.२.१११) :</p>\n";
        display(3); 
    } 
    $idamoma=1; // 0 - idamo maH has not applied. 1 - idamo maH has applied. useful in tyadAdInAmaH.
} else { $idamoma=0; }
/* yaH sau (7.2.110) */
if (sub(array("idam","idakam"),array("+"),blank(0),0) && $so==="su!" && $gender==="f")
{
    $text = two(array("idam"),array("+"),array("iyam"),array("+"),0);
    echo "<p class = sa >By yaH sau (".link_sutra("7.2.110").") :</p>\n";
    echo "<p class = sa >यः सौ (७.२.११०) :</p>\n";
    display(3);
}
/* anvAdeze napuMsake enadvaktavyaH (vA 1569) */
if ($gender==="n" && sub(array("idam+","etad+","idakam+",),blank(0),blank(0),0) && in_array($fo,array("idam","etad","idakam")) && $anvadesha===1 && in_array($so,array("am")))
{
    $text = one(array("idam+","etad+","idakam+",),array("enad+","enad+","enad",),0);
    echo "<p class = sa >By anvAdeze napuMsake enadvaktavyaH (vA 1569) :</p>\n";
    echo "<p class = sa >अन्वादेशे नपुंसके एनद्वक्तव्यः (वा १५६९) :</p>\n";
    display(0);
}
/* svamornapuMsakAt (7.1.23) */
if ( $gender==="n" && in_array($so,array("su!","am")) && $atom===0 && $adD ===0 )
{
    $text = two(array("+"),array("su!","am"),array("+"),array("",""),0);
    echo "<p class = sa >By svamornapuMsakAt (".link_sutra("7.1.23").") :</p>\n";
    echo "<p class = sa >स्वमोर्नपुंसकात्‌ (७.१.२३) :</p>\n";
    display(3); 
    $svamo = 1; // 0 - su / am have not been elided. 1 - su / am have been elided. Useful in many places like na lumatAGgasya etc.
    $pada="pada"; // when these su / am are elided, the word gets pada saJjJA, because there is no pratyaya remaining after that.
} else { $svamo = 0; }
/* kimaH kaH (7.2.103) */
if (sub(array("kim","kaka"),array("+"),blank(0),0) && !arr($text,'/[k][i][m][+]$/') && in_array($so,$sup) && in_array($fo,array("kim")))
{
    $text = two(array("kim","kaka"),array("+"),array("ka","ka"),array("+"),0);
    echo "<p class = sa >By kimaH kaH (".link_sutra("7.2.103").") :</p>\n";
    echo "<p class = sa >किमः कः (७.२.१०३) :</p>\n";
    display(3); $sarvafinal=1;
}
if (sub(array("kim","kaka"),array("+"),blank(0),0) && arr($text,'/[k][i][m][+]$/') && in_array($so,$sup))
    {
    echo "<p class = pa >na lumatA'Ggasya prevents application of kimaH kaH (".link_sutra("7.2.103").") :</p>\n";
    echo "<p class = pa >न लुमताऽङ्गस्य से किमः कः (७.२.१०३) का बाध होता है ।</p>\n";
    display(0);
}

/* aSTana A vibhaktau (7.2.84) */ 
if ( sub(array("zwan"),array("su!"),blank(0),0) && $_GET['cond1_8']==="1")
{
    $text = two(array("zwan"),array("su!"),array("zwaA"),array("su!"),1);
    echo "<p class = sa >By aSTana A vibhaktau (".link_sutra("7.2.84").") :</p>\n";
    echo "<p class = hn >Atva of aSTana A vibhaktau is optional, as shown by 'aSTano dIrghAt'.</p>\n";
    echo "<p class = sa >अष्टन आ विभक्तौ (७.२.८४)</p>\n";
    echo "<p class = hn >अष्टन आ विभक्तौ से हुआ आत्व वैकल्पिक है । अष्टनो दीर्घात्‌ इस सूत्र में दीर्घग्रहण के ज्ञापक से । </p>\n";    
    display(3); 
    $astana=1; // 0 - this sUtra has not applied. 1 - aSTana A vibhaktau has applied. useful in na lopaH prAtipadikAntasya.
}
elseif ( sub(array("zwan"),$hlsup,blank(0),0) || (sub(array("zwan"),array("jas","Sas"),blank(0),0) && $_GET['cond1_8']==="2") )
{
    $text = two(array("zwan"),$hlsup,array("zwaA"),$hlsup,1);
    $text = two(array("zwan"),array("jas","Sas"),array("zwaA"),array("jas","Sas"),1);
    echo "<p class = sa >By aSTana A vibhaktau (".link_sutra("7.2.84").") :</p>\n";
    echo "<p class = hn >Atva of aSTana A vibhaktau is optional, as shown by 'aSTano dIrghAt'.</p>\n";
    echo "<p class = sa >अष्टन आ विभक्तौ (७.२.८४)</p>\n";
    echo "<p class = hn >अष्टन आ विभक्तौ से हुआ आत्व वैकल्पिक है । अष्टनो दीर्घात्‌ इस सूत्र में दीर्घग्रहण के ज्ञापक से । </p>\n";    
    display(3); 
    $astana=1; 
} else { $astana=0; }
/* aSTAbhya auz (7.1.21) */
if ( sub(array("zwaA"),array("jas","Sas"),blank(0),0) && ( ($samasa ===1 && $pradhana===1) || $samasa===0 ))
{
    $text = two(array("zwaA"),array("jas","Sas"),array("zwaA"),array("O","O"),0);
    echo "<p class = sa >By aSTAbhya auz (".link_sutra("7.1.21").") :</p>\n";
    echo "<p class = sa >अष्टाभ्य औश्‌ (७.१.२१) :</p>\n";
    display(3);
}

/* pratyayasya lukzlulupaH (1.1.61) */
/* SaDbhyo luk (7.1.22) */
if ( $shat === 1 && in_array($so,array("jas","Sas")) && ( ($samasa ===1 && $pradhana===1) || $samasa===0 ))
{
    $text = two(array("+"),array("jas","Sas"),array("+"),blank(2),0);
    echo "<p class = sa >By SaDbhyo luk (".link_sutra("7.1.22").") :</p>\n";
    echo "<p class = sa >षड्भ्यो लुक्‌ (७.१.२२) :</p>\n";
    echo "<p class = pa >pratyayasya lukzlulupaH (".link_sutra("1.1.61").") </p>\n";
    echo "<p class = pa >प्रत्ययस्य लुक्श्लुलुपः (१.१.६१) </p>\n";
    display(3); 
    $luk = 1; // 0 - luk has not happened. 1 - luk has happened.
    $pada="pada"; // when pratyayas are elided, the word gets pada saJjJA, because there is no pratyaya remaining after that.
} else { $luk = 0; }
/* pratyayalope pratyayalakSaNam (1.1.62) and na lumatAGgasya (1.1.63) */
if ($luk === 1 )
{
    echo "<p class = hn >pratyayalope pratyayalakSaNam (".link_sutra("1.1.62").") is overridden by na lumatAGgasya (".link_sutra("1.1.63").") :</p>\n";
    echo "<p class = hn >प्रत्ययलोपे प्रत्ययलक्षणम्‌ (१.१.६२) का न लुमताङ्गस्य (१.१.६३) से बाध हुआ है । :</p>\n";
    display(0);
}
if ($svamo===1 && $gender==="n" && $so==="su!" && $sambuddhi===1)
{
    echo "<p class = hn >pratyayalope pratyayalakSaNam (".link_sutra("1.1.62").") is overridden by na lumatAGgasya (".link_sutra("1.1.63").") optionally.</p>\n";
    echo "<p class = hn >प्रत्ययलोपे प्रत्ययलक्षणम्‌ (१.१.६२) का न लुमताङ्गस्य (१.१.६३) से बाध हुआ है । यह बाध अनित्य है । :</p>\n";
    display(0);
}
/* jazzasoH ziH (7.1.20) */
if ($gender === "n" && $pada=== "pratyaya" && in_array($so,array("jas","Sas")) && $luk===0)
{
    $text = last(array("jas","Sas"),array("Si","Si"),0);
    echo "<p class = sa >By jazzasoH ziH (".link_sutra("7.1.20").") :</p>\n";
    echo "<p class = sa >जश्शसोः शिः (७.१.२०) :</p>\n";
    echo "<p class = hn >N.B. anekAlzitsarvasya mandates sarvAdeza :</p>\n";
    echo "<p class = hn >अनेकाल्शित्सर्वस्य से सर्वादेश होता है । :</p>\n";
    display(3); 
    $shi = 1; // 0 - zi Adeza has not happened. 1 - zi Adeza has happened.
} else { $shi = 0; }
/* zi sarvanAmasthAnam (1.1.42) */
if ($shi===1 )
{
    echo "<p class = pa >zi sarvanAmasthAnam (".link_sutra("1.1.42").") :</p>\n";
    echo "<p class = pa >शि सर्वनामस्थानम्‌ (१.१.४२) :</p>\n";
    display(0);
}
/* declaring sarvanamasthana1 variable */
if ( ( in_array($so,$sarvanamasthana) && $gender!=="n") || $shi===1)
{
    $sarvanamasthana1 = 1; 
}
else
{
    $sarvanamasthana1 = 0;
}
/* am sambuddhau (7.1.99) */
if ($so==="su!" && sub(array("catur","anaquh"),blank(0),blank(0),0) && $sambuddhi===1)
{
    $text = one(array("catur+","anaquh+"),array("catuar+","anaquah+"),0);
    echo "<p class = sa >By am sambuddhau (".link_sutra("7.1.99").") :</p>\n";
    echo "<p class = sa >अम्‌ सम्बुद्धौ (७.१.९९) :</p>\n";
    display(3);        
}
/* puMso'suG (7.1.89) */
if ($sarvanamasthana1===1 && sub(array("puMs"),blank(0),blank(0),0))
{
    $text = one(array("puMs+"),array("pumas+"),0);
    echo "<p class = sa >By puMso'suG (".link_sutra("7.1.89").") :</p>\n";
    echo "<p class = sa >पुंसोऽसुङ्‌ (७.१.८९) :</p>\n";
    display(3); 
    $it=array_merge($it,array("u")); // puMs is an ugit word.
    $itprakriti=array_merge($itprakriti,array("u"));       
}
/* yujerasamAse (7.1.71) */
if ( sub(array("yuj"),$sup,blank(0),0) && $fo==="yuj" && $sarvanamasthana1===1 && $samasa===0 && $yuj===1)
{
    $text = one(array("yuj",),array("yunj"),0);
    echo "<p class = sa >By yujerasamAse (".link_sutra("7.1.71").") :</p>\n";
    echo "<p class = sa >युजेरसमासे (७.१.७१) :</p>\n";
    display(3);        
}
/* A sarvanAmnaH (6.3.91) */
if ( sub($sarvanama,array("dfg","dfS","avatu"),blank(0),0) )
{
    $text = two($tyadadi,array("dfS"),antya($tyadadi,"A"),array("dfS"),0);
    echo "<p class = sa >By A sarvanAmnaH (".link_sutra("6.3.91").") :</p>\n";
    echo "<p class = sa >आ सर्वनाम्नः (६.३.९१) :</p>\n";
    display(0);  $Asarva=1;     // 0 - A sarvanAmnaH has not applied. 1 - A sarvanAmnaH has applied.
} else { $Asarva=0; }
/* defining tri variable for tisRcatasR */
if ($_GET["cond2_2_1"]==="1"  )
{
    $tri="f";
}
else 
{
    $tri="m";
} 
/* tricaturoH striyAM tisRcatasR (7.2.99) */
if ( (($tri === "f" && ends(array($fo),array("tri"),1)) || (ends(array($fo),array("catur"),1) && $gender==="f" ) ) && !ends(array($fo),array("stri"),1) && in_array($so,$sup))
{
    echo "<p class = sa >By tricaturoH striyAM tisRcatasR (".link_sutra("7.2.99").") :</p>\n";
    if ($svamo===1)
    {
        echo "<p class = hn >By anityatva of na lumatAGgasya paribhASA.</p>\n";
        $text = one(array("tri+","catur+"),array("tisf+","catasf+"),1);
    }
    else
    {
        $text = one(array("tri+","catur+"),array("tisf+","catasf+"),0);
    }
    
    echo "<p class = sa >त्रिचतुरोः स्त्रियां तिसृचतस्रू (७.२.९९) :</p>\n";
    if ($svamo===1)
    {
        echo "<p class = hn >न लुमताऽङ्गस्य इति निषेधस्य अनित्यत्वम्‌ ।</p>\n";
    }    
    display(3);
}
if ($gender==="f" && $tri === "f" && ends(array($fo),array("tri","catur"),1) && !ends(array($fo),array("stri"),1) && $svamo===1)
{
    $text = one(array("tri+","catur+"),array("tisf+","catasf+"),1);
    echo "<p class = sa >By tricaturoH striyAM tisRcatasR (".link_sutra("7.2.99").") and the optional nature of prohibition of 'na lumatA...'  :</p>\n";
    echo "<p class = sa >त्रिचतुरोः स्त्रियां तिसृचतस्रू (७.२.९९) तथा 'न लुमता..' इत्यस्य निषेधस्य अनित्यत्वम्‌ :</p>\n";
    display(3);
} 
/* caturanaDuhorAmudAttaH (7.1.98) */
if ($sarvanamasthana1 === 1 && sub(array("catur","anaquh"),blank(0),blank(0),0))
{
    $text = one(array("catur+","anaquh+"),array("catuAr+","anaquAh+"),0);
    echo "<p class = sa >By caturanaDuhorAmudAttaH (".link_sutra("7.1.98").") :</p>\n";
    echo "<p class = sa >चतुरनडुहोरामुदात्तः (७.१.९८) :</p>\n";
    display(3);        
}
/* sAvanaDuhaH (7.1.82) */ 
if (sub(array("anaquAh+","anaquah+"),blank(0),blank(0),0) && $so==="su!" )
{
    $text = one(array("anaquAh+","anaquah+"),array("anaquAnh+","anaquanh+"),0);
    echo "<p class = sa >By sAvanaDuhaH (".link_sutra("7.1.82").") :</p>\n";
    echo "<p class = sa >सावनडुहः (७.१.८२) :</p>\n";
    display(3);        
}
/* jarAyA jarasanyatarasyAm (7.2.101) */
if (arr($text,'/(jar)([aA])[+][a][m]/') && $pada=== "pratyaya"  && $so==="su!" && $gender === "n" )
    {
    echo "<p class = pa >sannipAtaparibhASA prevents application of jarAyA jarasanyatarasyAm.</p>\n";
    echo "<p class = pa >सन्निपातपरिभाषा से जराया जरसन्यतरस्याम्‌ (७.२.१०१) का बाध होता है ।</p>\n";
    display(3);
    }
if (arr($text,'/(jar)([aA])[+]/') && in_array($so,$acsup) )
    {
    $text = one(array("jara+","jarA+"),array("jaras+","jaras+"),1);
    echo "<p class = sa >By jarAyA jarasanyatarasyAm (".link_sutra("7.2.101").") :</p>\n";
    echo "<p class = hn >By padAGgAdhikAre tasya ca tadantasya ca (pa 30) and anekAltvAtsarvAdeze prApte nirdizyamAnasyAdezA bhavanti (pa 13) :</p>\n";
    echo "<p class = sa >जराया जरसन्यतरस्याम्‌ (७.२.१०१) :</p>\n";
    echo "<p class = hn >पदाङ्गाधिकारे तस्य च तदन्तस्य च (प ३०) तथा अनेकाल्त्वात्सर्वादेशे प्राप्ते निर्दिश्यमानस्यादेशा भवन्ति (प १३) :</p>\n";
    display(3);
    }
/* paddannomAshRnnizasanyUSandoSanyakaJChakannudannAsaJChasprabhRtiSu (6.1.63) */
// The random examples given under vibhASA GizyoH on page 147 are pending. Will do them after understanding fully. 
    // kakuddoSaNI etc are pending. 
$paddanno = array("pAda","danta","nAsikA","mAsa","hfdaya","niSA","asfj","yUza","doz","yakft","Sakft","udaka","Asya");
$paddanno1 = array("pad","dat","nas","mAs","hfd","niS","asan","yUzan","dozan","yakan","Sakan","udan","Asan");
if (sub($paddanno,array("+"),blank(0),0) && in_array($so,$zasadi) && in_array($fo,$paddanno))
{
    $text = two($paddanno,array("+"),$paddanno1,array("+"),1);
    echo "<p class = sa >By paddannomAshRnnizasanyUSandoSanyakaJChakannudannAsaJChasprabhRtiSu (".link_sutra("6.1.63").") :</p>\n";
    echo "<p class = hn >prabhRtigrahaNaM prakArArtham. tena 'padaGghrIcaraNo'striyAm', 'svAntaM hRnmAnasaM manaH' etc are valid.</p>\n";
    echo "<p class = sa >पद्दन्नोमास्‍हृन्निशसन्यूषन्दोषन्यकञ्छकन्नुदन्नासञ्छस्प्रभृतिषु (६.१.६३) :</p>\n";
    echo "<p class = hn >प्रभृतिग्रहणं प्रकारार्थम्‌ । तेन 'पदङ्घ्रिचरणोऽस्त्रियाम्‌', 'स्वान्तं हृन्मानसं मनः' इत्यादि च सङ्गच्छते ।</p>\n";
    display(0);
}
/* mAMsapRtanAsAnUnAM mAMspRtsnavo vAcyAH SasAdau vA (vA 3416) */
$mAMsa = array("mAMsa","pftanA","sAnu");
$mAMsa1 = array("mAMs","pft","snu");
if (sub($mAMsa,array("+"),array_merge($zasadi,array("Si")),0) && in_array($so,$zasadi) && in_array($fo,$mAMsa))
{
    $text = two($mAMsa,array_merge($zasadi,array("Si")),$mAMsa1,array_merge($zasadi,array("Si")),1);
    echo "<p class = sa >By mAMsapRtanAsAnUnAM mAMspRtsnavo vAcyAH SasAdau vA (vA 3416) :</p>\n";
    echo "<p class = sa >मांसपृतनासानूनां मांस्पृत्स्नवो वाच्याः शसादौ वा (वा ३४९६) :</p>\n";
    display(0);
}
/* asthidadhisakthyakSNAmanaGudAttaH (7.1.75) */
$asthi = array("asTi","daDi","sakTi","akzi"); // defining an array of asthi, dadhi etc.
$asthi1 = array("asTan","daDan","sakTan","akzan");
if (sub($asthi,array("+"),$tRtIyAdiSvaci,0) && in_array($so,$tRtIyAdiSvaci))
{
    $text = two($asthi,$tRtIyAdiSvaci,$asthi1,$tRtIyAdiSvaci,0);
    echo "<p class = sa >By asthidadhisakthyakSNAmanaGudAttaH (".link_sutra("7.1.75").") :</p>\n";
    echo "<p class = sa >अस्थिदधिसक्थ्यक्ष्णामनङुदात्तः (७.१.७५) :</p>\n";
    display(0);
}
/* saGkhyAvisAyapUrvasyAhnasyAhannanyatarasyAM Gau (6.3.110) */
$sankhyahan = array("ekAhna","dvyahna","tryahna","caturahna","paJcAhna","zaDAhna","saptAhna","azwAhna","navAhna","daSAhna","vyahna","sAyAhna"); // defining an array which satisfies needs of this sUtra.
if (sub($sankhyahan,array("+"),array("Ni"),0) && $so==="Ni")
{
    $text = two(array("hna"),array("+"),array("han"),array("+"),1);
    echo "<p class = sa >By saGkhyAvisAyapUrvasyAhnasyAhannanyatarasyAM Gau (".link_sutra("6.3.110").") :</p>\n";
    echo "<p class = sa >सङ्ख्याविसायपूर्वस्याह्नस्याहन्नन्यतरस्याम्‌ (६.३.११०) :</p>\n";
    display(0);
}
/* sakhyurasambuddhau (7.1.92) */
if (sub(array("saKi","saKI"),array("+"),array("O","jas","am","Ow"),0) && $sambuddhi===0 && $gender==="f")
{
    echo "<p class = pa >vibhaktau liGgaviziSTasyAgrahaNam (pa) overrules prAtipadikagrahaNe liGgaviziSTasyApi grahaNam.  :</p>\n";
    echo "<p class = hn >This bars application of sakhyurasambuddhau. </p>\n";
    echo "<p class = pa >विभक्तौ लिङ्गविशिष्टस्याग्रहणम्‌ (प) से प्रातिपदिकग्रहणे लिङ्गविशिष्टस्यापि ग्रहणम्‌ का निषेध होता है ।</p>\n";
    echo "<p class = hn >इस से सख्युरसम्बुद्धौ का प्रतिषेध होता है ।</p>\n";
    display(0);
}            
if (sub(array("saKi","saKI"),$sarvanamasthana,blank(0),0) && $_GET['cond1_4_3']!=="3" && $sambuddhi===0 && $_GET['cond1_3_1'] !== "3" && $gender!=="f")
{   
    $Nidvat = 1; // 0 - no NidvadbhAva. 1 - NidvadbhAva.
    echo "<p class = pa >By sakhyurasambuddhau (".link_sutra("7.1.92").") :</p>\n";
    echo "<p class = pa >सख्युरसम्बुद्धौ (७.१.९२) :</p>\n";
    if (!in_array($fo,array("saKi","saKI")))
    {
    echo "<p class = hn >anaG and NidvadbhAva are done because of aGga. Therefore, they apply also with tadanta words. </p>\n";
    echo "<p class = hn >अनङ्णिद्वद्भावयोराङ्गत्वात्तदन्तेऽपि प्रवृत्तिः । </p>\n";    
    }
    display(3);
} else {$Nidvat=0; }
/* anaG sau (7.1.93) and Gicca (1.1.53) */
if (sub(array("saKi","saKI"),array("+"),array("su!"),0) && $so==="su!" && $sambuddhi===0 && $gender==="f")
{
    echo "<p class = pa >vibhaktau liGgaviziSTasyAgrahaNam (pa) overrules prAtipadikagrahaNe liGgaviziSTasyApi grahaNam.  :</p>\n";
    echo "<p class = hn >This bars application of anaG sau. </p>\n";
    echo "<p class = pa >विभक्तौ लिङ्गविशिष्टस्याग्रहणम्‌ (प) से प्रातिपदिकग्रहणे लिङ्गविशिष्टस्यापि ग्रहणम्‌ का निषेध होता है ।</p>\n";
    echo "<p class = hn >इस से अनङ्‌ सौ का प्रतिषेध होता है ।</p>\n";
    display(0);
}            
if (sub(array("saKi","saKI"),array("+"),array("su!"),0) && $_GET['cond1_4_3']!=="3" && $so==="su!" && $_GET['cond1_3_1'] !== "3" && $sambuddhi===0 && $gender!=="f")
{
    $text = two(array("saKi","saKI"),array("+"),array("saKan","saKan"),array("+"),0);
    echo "<p class = sa >By anaG sau (".link_sutra("7.1.93").") and Gicca (".link_sutra("1.1.53").")  :</p>\n";
    echo "<p class = sa >अनङ्‌ सौ (७.१.९३) तथा ङिच्च (१.१.५३) :</p>\n";
    if (!in_array($fo,array("saKi","saKI")))
    {
    echo "<p class = hn >anaG and NidvadbhAva are done because of aGga. Therefore, they apply also with tadanta words. </p>\n";
    echo "<p class = hn >अनङ्णिद्वद्भावयोराङ्गत्वात्तदन्तेऽपि प्रवृत्तिः । </p>\n";    
    }
    display(3);
}
/* alo'ntyAtpUrva upadhA (1.1.65) */ 
// saJjJA sUtra. Nothing to code here.
/* diva aut (7.1.84) */
if (sub(array("div+"),array("su!"),blank(0),0))
{
    $text = one(array("div+"),array("diO+"),0);
    echo "<p class = sa >By diva aut (".link_sutra("7.1.84").") :</p>\n";
    echo "<p class = sa >दिव औत्‌ (७.१.८४) :</p>\n";
    display(3);        
    echo "<p class = pa >Because it is alvidhi, sthAnivadbhAva doesn't happen and 'halGyAp..' doesn't apply. </p>\n";
    echo "<p class = pa >अल्विधि होने के कारण स्थानिवद्भाव नहीं है । अतः हल्ङ्‍याप्‌.. की प्रवृत्ति नहीं है ।</p>\n";
    display(0);        
}
/* diva ut (6.1.131) */
// uttarapadatve cApadAdividhau pratiSedhaH (vA) pending.
// dadhisecau example is also pending.
if (sub(array("div+"),$hlsup,blank(0),0) || arr($text,'/[d][i][v][+]$/') )
{
    $text = one(array("div+"),array("diu+"),0);
    echo "<p class = sa >By diva ut (".link_sutra("6.1.131").") :</p>\n";
    echo "<p class = sa >दिव उत्‌ (६.१.१३१) :</p>\n";
    display(0);        
}
/* zeSo ghyasakhi (1.4.7) */
if ($nadi!==1 && arr($text,'/[iu][+]/') && $fo!=="saKi" && !arr($text,'/[iu][+]$/'))
{
    if (arr(array($fo),'/[p][a][t][i]$/') && $fo==='pati')
    {
        $ghi = 0; // 0 - no ghi saJjJA. 1 - ghi saJjJA.
        echo "<p class = pa >By patiH samAsa eva (".link_sutra("1.4.8")."), the ghi saJjJA is not ascribed. </p>\n";
        echo "<p class = pa >पतिः समास एव (१.४.८) से घिसञ्ज्ञा नहीं है । </p>\n";
        display(0);        
    }
    else
    {
    $ghi = 1;
    echo "<p class = pa >By zeSo ghyasakhi (".link_sutra("1.4.7").") :</p>\n";
    echo "<p class = pa >शेषो घ्यसखि (१.४.७) :</p>\n";
    display(0);        
    }
}
else
{
    $ghi = 0;
}
/* na mu ne (8.2.3) */ 
//adas + wA -> nAbhAva has to happen. Special case.
if ( $fo==="adas" && $so==="wA" && $gender!=="f")
{
    $text = one(array("adas"),array("amu"),0);
    echo "<p class = sa >By adaso'serdAdu do maH (".link_sutra("8.2.80").") :</p>\n";
    echo "<p class = sa >अदसोऽसेर्दादु दो मः (८.२.८०) :</p>\n";
    display(0);
    echo "<p class = pa >By na mu ne (".link_sutra("8.2.3").") :</p>\n";
    echo "<p class = pa >न मु ने (८.२.३) :</p>\n";
    $ghi=1;
    display(0);
}

/* yaci bham (1.4.14) and A kaDArAdekA saJjJA (1.4.1) */
// Not coded perfectly. Only for sup pratyayas.
if ($sarvanamasthana1 === 0 && in_array($so,$acsup) && $luk===0 && $svamo===0 )
{
    echo "<p class = pa >By yaci bham (".link_sutra("1.4.14").") and A kaDArAdekA saJjJA (".link_sutra("1.4.1").") :</p>\n";
    echo "<p class = pa >यचि भम्‌ (१.४.१४) तथा आ कडारादेका सञ्ज्ञा (१.४.१) :</p>\n";
    display(0); 
    $bham=1; // 0 - no bha saJjJA. 1 - bha saJjJA.
} else {$bham = 0; }  
/* svAdiSvasarvanAmasthAne (1.4.17) */
if ($sarvanamasthana1 ===0 && in_array($so,$sup) && $bham!==1 )
{
    $pada="pada"; // this sUtra mandates pada saJjJA in these cases.
    echo "<p class = pa >By svAdiSvasarvanAmasthAne (".link_sutra("1.4.17").") :</p>\n";
    echo "<p class = pa >स्वादिष्वसर्वनामस्थाने (१.४.१७) :</p>\n";
    display(0);    
}
/* SaTcaturbhyazca (7.1.55) */
if (  $_GET['cond1_6_1']==="1")
{
    echo "<p class = pa >'nuT' Agama doesn't happen in case of gauNatva.</p>\n";
    echo "<p class = pa >गौणत्वे तु नुट्‌ नेष्यते ।</p>\n";
    display(0); 
}
elseif ( ($shat===1 || arr(array($fo),'/[c][a][t][u][r]$/')) && $so === "Am" && !in_array($fo,$sarvanama) && ($samasa===0 || $samasa===1 && $pradhana===1) && $_GET['cond1_6_1']!=="1")
{
    $text = one(array("+Am"),array("+nAm"),0);
    echo "<p class = sa >By SaTcaturbhyazca (".link_sutra("7.1.55").") :</p>\n";
    echo "<p class = sa >षट्चतुर्भ्यश्च (७.१.५५) :</p>\n";
    display(3); 
    $Satcatur=1; // 0 - SaTcaturbhyazca has not applied. 1 - SaTcaturbhyazca has applied.
    $pada="pada"; // word gets pada saJjJA.
} else { $Satcatur=0; }
/* patch for aSTana A vibhaktau */
if ( sub(array("azwan"),array("nAm"),blank(0),1) && $so === "Am" && ($samasa===0 || ($samasa===1 && $pradhana===1)))
{
    $text = two(array("azwan"),array("+nAm"),array("azwaA"),array("+nAm"),0);
    echo "<p class = sa >By aSTana A vibhaktau (".link_sutra("7.2.84").") :</p>\n";
    echo "<p class = hn >Atva of aSTana A vibhaktau is optional, as shown by 'aSTano dIrghAt'.</p>\n";
    echo "<p class = sa >अष्टन आ विभक्तौ (७.२.८४)</p>\n";
    echo "<p class = hn >अष्टन आ विभक्तौ से हुआ आत्व वैकल्पिक है । अष्टनो दीर्घात्‌ इस सूत्र में दीर्घग्रहण के ज्ञापक से । </p>\n";    
    display(3); 
}
/* nopadhAyAH (6.4.7) */
if ( arr($text,'/[n][+]/') && $so === "Am" && !in_array($fo,$sarvanama) && ($samasa===0 || ($samasa===1 && $pradhana===1)))
{
    $text = three($ac,array("n"),array("+nAm"),$acdir,array("n"),array("+nAm"),0); // for function three, please see function.php.
    echo "<p class = sa >By nopadhAyAH (".link_sutra("6.4.7").") :</p>\n";
    echo "<p class = sa >नोपधायाः (६.४.७) :</p>\n";
    display(3); 
    $nopadha=1; // 0 - word doesn't have 'n' as upadhA. 1 - word has 'n' as upadhA.
} else { $nopadha=0; }

/* pAdaH pat (6.4.130) */
if (sub(array("pAd"),array("+"),$sup,0) && $bham===1)
{
    $text = two(array("pAd"),$sup,array("pad"),$sup,0);
    echo "<p class = sa >By pAdaH pat (".link_sutra("6.4.130").") :</p>\n";
    echo "<p class = sa >पादः पत्‌ (६.४.१३०):</p>\n";
    display(3);
}
/* TeH (6.4.143) */
if ($Dit===1 && $bham===1 )
{   
    $text = Ti(0); // function Ti removes Ti. see function.php for details.
    echo "<p class = sa >By TeH (".link_sutra("6.4.143").") :</p>\n";
    echo "<p class = sa >टेः (६.४.१४३) :</p>\n";
    display(3); 
}
/* bhasya TerlopaH (7.1.88) */
if (sub(array("paTin","maTin","fBukzin"),blank(0),blank(0),0) && $bham===1 && !arr($text,'/[i][n][+]$/'))
{
    $text = one(array("paTin","maTin","fBukzin"),array("paT","maT","fBukz"),0);
    echo "<p class = sa >By bhasya TerlopaH (".link_sutra("7.1.88").") :</p>\n";
    echo "<p class = sa >भस्य टेर्लोपः (७.१.८८) :</p>\n";
    display(3); 
}
if (sub(array("paTin+I","maTin+I","fBukzin+I",),blank(0),blank(0),1) && $gender==="f" && $bham===1) // patch for supathI.
{
    $text = one(array("paTin+I","maTin+I","fBukzin+I",),array("paTI","maTI","fBukzI"),0);
    echo "<p class = sa >By bhasya TerlopaH (".link_sutra("7.1.88").") :</p>\n";
    echo "<p class = sa >भस्य टेर्लोपः (७.१.८८) :</p>\n";
    display(3); 
}

/* pathimathyRbhukSAmAt (7.1.85) */
if (sub(array("paTin","maTin","fBukzin"),array("+"),blank(0),0) && $so==="su!" && $gender==="m")
{
    $text = two(array("paTin","maTin","fBukzin"),array("+"),array("paTiA","maTiA","fBukziA"),array("+"),0);
    echo "<p class = sa >By pathimathyRbhukSAmAt (".link_sutra("7.1.85").") :</p>\n";
    echo "<p class = sa >पथिमथ्यृभुक्षामात्‌ (७.१.८५) :</p>\n";
    display(3); 
    $pathi=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied. Useful for tho'nthaH (7.1.87).
} else {$pathi=0; } 
/* ito'tsarvanAmasthAne (7.1.85) */
if (sub(array("paTi","maTi","fBukzi"),blank(0),blank(0),0) && $sarvanamasthana1===1 )
{
    $text = one(array("paTi","maTi","fBukzi"),array("paTa","maTa","fBukza"),0);
    echo "<p class = sa >By ito'tsarvanAmasthAne (".link_sutra("7.1.85").") :</p>\n";
    echo "<p class = sa >इतोऽत्सर्वनामस्थाने (७.१.८५) :</p>\n";
    display(3); 
    $pathi1=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied. Useful for tho'nthaH (7.1.87).
} else {$pathi=0;}
/* tho'nthaH (7.1.87) */
if (sub(array("paTa","maTa"),blank(0),blank(0),0) && ($pathi===1 || $pathi1===1))
{
    $text = one(array("paTa","maTa",),array("panTa","manTa",),0);
    echo "<p class = sa >By tho'nthaH (".link_sutra("7.1.87").") :</p>\n";
    echo "<p class = sa >थोऽन्थः (७.१.८७) :</p>\n";
    display(3);
}
/* sambuddhau napuMsakAnAM nalopo vA vAcyaH (vA) */
// Pending. Not clear to me.
/* sau ca (6.4.13) */
$noin=array("ahan","Ahan","bahuvftrahan","bahupUzan","bahvaryaman"); // words where inhan.. doesn't apply.
$acdir = array("A","A","I","I","U","U","F","F","F","F","e","o","E","O",);
if (sub(array("in","han","pUzan","aryaman"),array("+"),array("su!"),1) && !in_array($fo,$noin) && in_array($so,array("su!")) && $sambuddhi===0)
{
    $text = two($ac,array("n+"),$acdir,array("n+"),0);
    echo "<p class = sa >By sau ca (".link_sutra("6.4.13").") :</p>\n";
    echo "<p class = sa >सौ च (६.४.१३) :</p>\n";        
    if (sub(array("vIn+"),blank(0),blank(0),0))
    {
    echo "<p class = pa >By 'aninasmangrahaNAni arthavatA cAnarthakena ca tadantavidhiM prayojayanti' (pa) :</p>\n";
    echo "<p class = pa >अनिनस्मन्ग्रहणानि अर्थवता चानर्थकेन च तदन्तविधिं प्रयोजयन्ति (प) :</p>\n";                
    }
    display(3); 
    $inhan=1; // 0 - the word is  not eligible for inhanpU... 1 - the word is eligible for inhanpU.. 
} else {$inhan=0; }
/* inhanpUSAryamNAM zau (6.4.12) */
if (sub(array("in","han","pUzan","aryaman"),array("+"),$sarvanamasthana,0) && !sub(array("ahan","Ahan",),array("+"),array("su!"),0) && $sambuddhi===0 && $inhan===0 && !in_array($fo,array("ahan","dIrGAhan")))
{
    $text = two(array("in","han","pUzan","aryaman"),array("Si"),array("In","hAn","pUzAn","aryamAn"),array("Si"),0);
    echo "<p class = sa >By inhanpUSAryamNAM zau (".link_sutra("6.4.12").") :</p>\n";
    echo "<p class = sa >इन्हन्पूषार्यम्णां शौ (६.४.१२) :</p>\n";        
    if (sub(array("vin+"),blank(0),blank(0),0))
    {
    echo "<p class = pa >By 'aninasmangrahaNAni arthavatA cAnarthakena ca tadantavidhiM prayojayanti' (pa) :</p>\n";
    echo "<p class = pa >अनिनस्मन्ग्रहणानि अर्थवता चानर्थकेन च तदन्तविधिं प्रयोजयन्ति (प) :</p>\n";                
    }
    display(3); $inhan1=1; // 0 - inhan.. has not applied. 1 - inhan.. has appied.
} else { $inhan1=0; }
/* na saMyogAdvamantAt (6.4.137) */
if ($bham === 1 && arr($text,'/['.pc('hl').'][vm][a][n][+]/'))
{
    echo "<p class = sa >By na saMyogAdvamantAt (".link_sutra("6.4.137").") :</p>\n";
    echo "<p class = sa >न संयोगाद्वमन्तात्‌ (६.४.१३७) :</p>\n";
    display(3);  $vamanta=1;   // 0 - the word is not vamanta. 1 - the word is vamanta.
} else {$vamanta=0; }
/* ana upadhAlopino'nyatarasyAm (4.1.28) */
if ($gender==="f" && $vamanta===0 && $anobahuvrihe===1 && arr($text,'/[Aa][n][+]/') && !(sub(array("udan+","Asan"),blank(0),blank(0),0) ) ) 
{
    $text = one(array("An+","an+"),array("n+NIp+","n+NIp+"),1);
    echo "<p class = st >By ana upadhAlopino'nyatarasyAm (".link_sutra("4.1.28").") :</p>\n";
    echo "<p class = st >अन उपधालोपिनोऽन्यतरस्याम्‌ (४.१.२८) :</p>\n";
    display(8); 
        $text = two(array("NIp"),array("+"),array("I"),array("+"),0); 
    $it = array_merge($it,array("N","p"));
    $itprakriti = array_merge($itprakriti,array("p","N"));
    echo "<p class = sa >GakAra and pakAra are 'it'. They are elided by lazakvataddhite (".link_sutra("1.3.8")."), halantyam (".link_sutra("1.3.3").") and tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >ङपावितौ । लशक्वतद्धिते (१.३.८), हलन्त्यम्‌ (१.३.३) और तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
    $GIp=1;
    $GI=1;
    $nadi=1;
}
/* sarvanAmasthAne cAsambuddhau (6.4.8) */  
//if ( arr($text,'/['.flat($ac).'][n][+]/')  && !arr($text,'/['.flat($ac).'][n][+]$/') && !sub(array("Ahan"),blank(0),blank(0),0) && $sarvanamasthana1===1 && $sambuddhi===0 && $inhan===0 && $inhan1===0 && !in_array($fo,array("maGavan")) ) // To check for dIrghAhan 
if ( arr($text,'/['.flat($ac).'][n][+]/')  && !arr($text,'/['.flat($ac).'][n][+]$/') && !in_array(1,$num) && $sarvanamasthana1===1 && $sambuddhi===0 && $inhan===0 && $inhan1===0 && !in_array($fo,array("maGavan")) )
{
    $text = two($ac,array("n+"),$acdir,array("n+"),0);
    echo "<p class = sa >By sarvanAmasthAne cAsambuddhau (".link_sutra("6.4.8").") :</p>\n";
    echo "<p class = sa >सर्वनामस्थाने चासम्बुद्धौ (६.४.८) :</p>\n";
    echo "<p class = pa >alo'ntyAtpUrva upadhA (".link_sutra("1.1.65").") </p>\n";
    echo "<p class = pa >अलोऽन्त्यात्पूर्व उपधा (१.१.६५) </p>\n";   
    display(3); 
    $casambu=1; // 0 - This sUtra has not applied. 1 - This sUtra has applied. Useful for preventing repeat application.
} else {$casambu=0; }
/* tRjvatkroSTuH (7.1.95), vibhASA tRtIyAdiSvaci (7.1.97) and numaciratRjvadbhAvebhyo nuT pUrvavipratiSedhena (vA 4374) */
if ((sub(array("krozwu"),array("+"),$sarvanamasthana,0)||sub(array("krozwu"),array("+"),$tRtIyAdiSvaci,0) ) && $sarvanamasthana1===1 && $pada==="pratyaya" && ($sambuddhi===0 || ($sambuddhi===1 && $so!=="su!")))
{
    if ( (in_array($so,$sarvanamasthana)|| in_array($so,$tRtIyAdiSvaci) || arr($text,'/[+][S][i]$/')) ) 
    {
    echo "<p class = pa >tRjvadbhAva of tRjvatkroSTuH is overruled by 'num' by vRddhyauttvatRjvadbhAvaguNebhyo num pUrvavipratiSedhena (vA 4373). </p>\n";
    echo "<p class = pa >तृज्वत्क्रोष्टुः से प्राप्त तृज्वद्भाव का वृद्ध्यौत्त्वतृज्वद्भावगुणेभ्यो नुम्‌ पूर्वविप्रतिषेधेन (वा ४३७३) से बाध होता है ।</p>\n";        
    display(0);
    }
    elseif (sub(array("krozwu"),array("+"),$sarvanamasthana,0))
    {
    $text = two(array("krozwu"),array("+"),array("krozwf"),array("+"),0);
    echo "<p class = sa >By tRjvatkroSTuH (".link_sutra("7.1.95").") :</p>\n";
    echo "<p class = sa >तृज्वत्क्रोष्टुः (७.१.९५) :</p>\n";
    display(3);        
    $tRcvat=1;
    }
}
if (sub(array("krozwu"),array("+"),$tRtIyAdiSvaci,0) && $so!=="Am")
{
    $text = two(array("krozwu"),array("+"),array("krozwf"),array("+"),1);
    echo "<p class = sa >vibhASA tRtIyAdiSvaci (".link_sutra("7.1.97").") :</p>\n";
    echo "<p class = sa >विभाषा तृतीयादिष्वचि (७.१.९७) :</p>\n";
    display(3);      
    $tRcvat=1;  
}
if (sub(array("krozwu",),array("+"),array("Am"),0) && $so==="Am")
{ 
    $text = two(array("krozwu"),array("+Am"),array("krozwu"),array("+nAm"),0);
    echo "<p class = sa >numaciratRjvadbhAvebhyo nuT pUrvavipratiSedhena (vA 4374) :</p>\n";
    echo "<p class = hn >This vArttika mandates a 'nuT' Agama. :</p>\n";
    echo "<p class = sa >नुमचिरतृज्वद्भावेभ्यो नुट्‌ पूर्वविप्रतिषेधेन (वा ४३७४) :</p>\n";
    echo "<p class = hn >यह वार्तिक नुट्‌ आगम का विधान करता है ।</p>\n";
    display(0);  
    $numacira=1;     // 0 - numacira.. has not applied. 1 - numacira.. has applied.  
} else { $numacira = 0;}

// atisakhi is pending to code. page 158. Understand and then code.
/* AGo nA'striyAm (7.3.120) */ 
if ($ghi===1 && $so==="wA" && $gender !== "f" && arr($text,'/[iu][+]/') && !(in_array($fo,array("suDI","praDI")) && $gender==="n") )
{
    if ($bhashitapumska===0 )
    {
        $text = two(array("i+","u+"),array("wA"),array("i+","u+"),array("nA"),0);    
    }
    else
    {
        $text = two(array("i+","u+"),array("wA"),array("i+","u+"),array("nA"),1);     
        $text = two(array("krozwu+",),array("wA"),array("krozwu+",),array("nA"),0);             
    }
    echo "<p class = sa >By AGo nA'striyAm (".link_sutra("7.3.120").") :</p>\n";
    echo "<p class = sa >आङो नाऽस्त्रियाम्‌ (७.३.१२०) :</p>\n";
    display(3); 
    $AGo = 1; // 0 - no nAbhAva. 1 - nAbhAva.
} else {$AGo = 0; }

/* defining some sets */
$acsup = array_merge($acsup,array("SI","Si")); // adding Si of jas/Sas as ajAdivibhakti.
/* iko'ci vibhaktau (7.1.73) */
if ( $gender==="n" && arr($text,'/['.flat($ik).'][+]/') && in_array($so,$acsup) && sub($ik,array("+"),$acsup,0))
{
    if ($ghi===1 && in_array($so,array("Ne","Nasi!","Nas","Ni")) && $bhashitapumska===0)
    {
    echo "<p class = pa >guNa of gherGiti is overruled by 'num' by vRddhyauttvatRjvadbhAvaguNebhyo num pUrvavipratiSedhena (vA 4373). </p>\n";
    echo "<p class = pa >घेर्ङिति से प्राप्त गुण का वृद्ध्यौत्त्वतृज्वद्भावगुणेभ्यो नुम्‌ पूर्वविप्रतिषेधेन (वा ४३७३) से बाध होता है ।</p>\n";        
    display(0); 
    $noghe=1; // 0 - gherGiti will not apply. 1 - gherGiti will apply.
    } else {$noghe=0; }
    if (in_array($so,array("Am")))
    {
    echo "<p class = pa >'num' of 'iko'ci vibhaktau' is barred by 'nut' because of 'numaciratRjvadbhAvebhyo nuT pURvavipratiSedhena (vA 4374). </p>\n";
    echo "<p class = pa >इकोऽचि विभक्तौ से प्राप्त नुमागम का 'नुमचिरतृज्वद्भावेभ्यो नुट्‌ पूर्वविप्रतिषेधेन (वा ४३७४) से बाध होता है ।</p>\n";        
    display(0);
    }
    elseif ($ghi===1 && $AGo===1 && $bhashitapumska===0)
    {
    echo "<p class = pa >AGo nA'striyAm has barred application of iko'ci vibhaktau. </p>\n";
    echo "<p class = pa >आङो नाऽस्त्रियाम्‌ से इकोऽचि विभक्तौ का बाध हुआ है ।</p>\n"; 
    display(0);
    }
    elseif ( $bhashitapumska === 1 && in_array($so,array("wA","Ne","Nasi!","Nas","Ni","os",)))
    {
    $text = three(array("i","u","f","x"),array("+"),$acsup,array("i","u","f","x"),array("n+"),$acsup,1);            
    // patch for sudhI
    $text = three(array("suDi","praDi","krozwfn","sulUn"),array("+"),$acsup,array("suDI","praDI","krozwf","sulun"),array("+"),$acsup,0);
    $text = three(array("sulu",),array("+"),array("wA"),array("sulun",),array("+"),array("wA"),0);
    echo "<p class = sa >By iko'ci vibhaktau (".link_sutra("7.1.73").") :</p>\n";
    echo "<p class = sa >इकोऽचि विभक्तौ (७.१.७३) :</p>\n";
    display(3);        
    }
    else
    {
    $text = three(array("i","u","f","x"),array("+"),$acsup,array("i","u","f","x"),array("n+"),$acsup,0);            
    echo "<p class = sa >By iko'ci vibhaktau (".link_sutra("7.1.73").") :</p>\n";
    echo "<p class = sa >इकोऽचि विभक्तौ (७.१.७३) :</p>\n";
    display(3);        
    }
 $ikoci=1; // 0 - iko'ci vibhaktau has not applied. 1 - iko'ci vibhaktau has applied.
} else { $ikoci = 0; $noghe=0;}
/* vA'mzasoH (6.4.80) */
if (ends(array($fo),array("strI","stri"),1) && in_array($so,array("am","Sas")) && sub(array("strI","stri"),array("am","Sas"),blank(0),0))
{
    $text = one(array("strI+","stri+"),array("striy+","striy+"),1);
    echo "<p class = sa >By vA'mzasoH (".link_sutra("6.4.80").") :</p>\n";
    echo "<p class = sa >वाऽम्शसोः (६.४.८०) :</p>\n";
    display(0);
}

/* lomno'patyeSu bahuSvakAro vaktavyaH (vA 2560) */
// Pending, because it is for taddhita derivation. Right now made a patch.
$bahusup = array("jas","Sas","Bis","Byas","Am","sup");
if (sub(array("Oqulomi"),array("+"),$bahusup,0) && in_array($so,$bahusup))
{
    $text = two(array("Oqulomi"),$bahusup,array("uquloma"),$bahusup,0);
    echo "<p class = sa >By lomno'patyeSu bahuSvakAro vaktavyaH (vA 2560) :</p>\n";
    echo "<p class = sa >लोम्नोऽपत्येषु बहुष्वकारो वक्तव्यः (वा २५६०) :</p>\n";
    display(0);
}
/* aci ra RtaH (7.2.100) */
if (sub(array("tisf","catasf"),array("+"),$acsup,0))
{
    if ($so==="Am" )
    {
    $text = two(array("tisf","catasf"),array("+Am"),array("tisf","catasf"),array("+nAm"),0);
    echo "<p class = sa >numaciratRjvadbhAvebhyo nuT pUrvavipratiSedhena (vA 4374) :</p>\n";
    echo "<p class = hn >This vArttika mandates a 'nuT' Agama. :</p>\n";
    echo "<p class = sa >नुमचिरतृज्वद्भावेभ्यो नुट्‌ पूर्वविप्रतिषेधेन (वा ४३७४) :</p>\n";
    echo "<p class = hn >यह वार्तिक नुट्‌ आगम का विधान करता है ।</p>\n";        
    $numacira=1;
    }
    else
    {
    $text = two(array("tisf","catasf"),$acsup,array("tisr","catasr"),$acsup,0);
    echo "<p class = sa >By aci ra RtaH (".link_sutra("7.2.100").") :</p>\n";
    echo "<p class = sa >अचि र ऋतः (७.२.१००) :</p>\n";        
    }
    display(0);
}
/* RduzanaspurudaMso&nehasAM ca (7.1.94) */
// displaying the output for dhAtR napuMsaka
if (arr($text,'/[fx][+]$/') && $svamo===1)
{
    echo "<p class = pa >By na lumatAGgasya, anaG is prevented.</p>\n";
    echo "<p class = pa >न लुमताङ्गस्य से अनङ्‌ का प्रतिषेध होता है ।</p>\n";
    display(0);     
}
if ( (arr($text,'/[fx][+]/')|| $fo==="uSanas"|| $fo==="purudaMsas" || $fo==="anehas" ) &&  $so==="su!" && $pada==="pratyaya" && $sambuddhi===0 && $svamo===0)
{
    $text = two(array("f","x","uSanas","purudaMsas","anehas"),array("+"),array("an","an","uSanan","purudaMsan","anehan"),array("+"),0);
    echo "<p class = sa >By RduzanaspurudaMso'nehasAM ca (".link_sutra("7.1.94").") :</p>\n";
    echo "<p class = sa >ऋदुशनस्पुरुदंसोऽनेहसां च (७.१.९४) :</p>\n";
    display(3); 
} 
if (  $fo==="uSanas" &&  $so==="su!" && $sambuddhi===1)
{
    $text = two(array("uSanas"),array("+"),array("uSanan"),array("+"),1);
    $text = two(array("uSanas"),array("+"),array("uSana"),array("+"),1);
    echo "<p class = sa >By asa sambuddhau vA'naG nalopazca vA vAcyaH (vA 5037) :</p>\n";
    echo "<p class = sa >अस्य सम्बुद्धौ वाऽनङ्‌ नलोपश्च वा वाच्यः (वा ५०३७) :</p>\n";
    display(3);
}
/* Rto GitsarvanAmasthAnayoH (7.3.110) */
if (arr($text,'/[fx][+]/') && ( $sarvanamasthana1===1 || $so==="Ni") && $pada==="pratyaya" && $svamo===0)
{
    $text = two(array("f","x"),array("+"),array("ar","al"),array("+"),0);
    echo "<p class = sa >By Rto GisarvanAmasthAnayoH (".link_sutra("7.3.110").") and uraNraparaH (".link_sutra("1.1.51").") :</p>\n";
    echo "<p class = sa >ऋतो ङिसर्वनामस्थानयोः (७.३.११०) तथा उरण्रपरः (१.१.५१) :</p>\n";
    display(3);
}
/* aptRntRcsvasRnaptRneSTRtvaSTRkSattRhotRpotRprazAstRRNAm (6.4.11) */
// Not coded perfectly for tRn and tRc. naptrAdigrahaNaM vyutpattipakSe niyamArtham is pending.
$svasR = array("svasf","naptf","nezwf","tvazwf","kzattf","hotf","potf","praSAstf");
$excludesvasR = array("pitf","BrAtf","jAmAtf","mantf","hantf","nanAndf","duhitf","yAtf","mAtf","katara","itara","anyatara");
if (((sub(array("tar+","war+"),blank(0),blank(0),0)  )|| ($tRcvat===1&& $gender!=="n" )||in_array($fo,$svasR) || in_array($fo,$svasrAdi)) && $sarvanamasthana1===1 && $pada==="pratyaya" && !in_array($fo,$excludesvasR) && (($sambuddhi===1 && $so!=="su!" ) || $sambuddhi===0)  )
{ 
    $text = three($ac,array("r"),array("+"),dirgha($ac),array("r"),array("+"),0);
    $text = one(array("svasan","naptan","nezwan","tvazwan","kzattan","hotan","potan","praSAstan"),array("svasAn","naptAn","nezwAn","tvazwAn","kzattAn","hotAn","potAn","praSAstAn"),0);
    echo "<p class = sa >By aptRntRcsvasRnaptRneSTRtvaSTRkSattRhotRpotRprazAstRRNAm (".link_sutra("6.4.11").") :</p>\n";
    echo "<p class = sa >अप्तृन्तृच्स्वसृनप्तृनेष्टृत्वष्टृक्षत्तृहोतृपोतृप्रशास्तॄणाम्‌ (६.४.११) :</p>\n";
    display(3); 
    $aptRn=1; // 0 - aptRn.. has not appied. 1 - aptRn.. has applied.
} else { $aptRn=0; }
if (arr(array($fo),'/[a][p]$/') && $sarvanamasthana1===1 && $pada==="pratyaya" && (($sambuddhi===1 && $so!=="su!" ) || $sambuddhi===0))
{
    $text = two(array("ap"),array("+"),array("Ap"),array("+"),0);
    echo "<p class = sa >By aptRntRcsvasRnaptRneSTRtvaSTRkSattRhotRpotRprazAstRRNAm (".link_sutra("6.4.11").") :</p>\n";
    echo "<p class = sa >अप्तृन्तृच्स्वसृनप्तृनेष्टृत्वष्टृक्षत्तृहोतृपोतृप्रशास्तॄणाम्‌ (६.४.११) :</p>\n";
    display(3);
}
/* apo bhi (7.4.48) */
if (arr(array($fo),'/[a][p]$/') && arr($text,'/[a][p][+][B]/') && in_array($so,array("Bis","Byas","ByAm")))
{
    $text = two(array("ap"),array("+B"),array("at"),array("+B"),0);
    echo "<p class = sa >By apo bhi (".link_sutra("7.4.48").") :</p>\n";
    echo "<p class = sa >अपो भि (७.४.४८) :</p>\n";
    display(3);
}
/* Ato dhAtoH (6.4.140) */
$haha = array("hAhA");
if ($bham === 1 && arr($text,'/[A][+]/') && !in_array($fo,$haha) && ($_GET['cond1_2']==="1" || $_GET['cond2_1']==="1" ) && $Ap===0)
{
    $text = two(array("A"),array("+"),array(""),array("+"),0);
    echo "<p class = sa >By Ato dhAtoH (".link_sutra("6.4.140").") :</p>\n";
    echo "<p class = sa >आतो धातोः (६.४.१४०) :</p>\n";
    display(6);
}
if ($bham === 1 && arr($text,'/[A][+]/') &&  in_array($fo,array('ktvA','SnA')) && $Ap===0)
{
    $text = two(array("A"),array("+"),array(""),array("+"),1);
    echo "<p class = sa >By Ato dhAtoH (".link_sutra("6.4.140").") :</p>\n";
    echo "<p class = hn >AtaH iti yogavibhAgAdadhAtorapi AkAralopaH kvacit |</p>\n";       
    echo "<p class = sa >आतो धातोः (६.४.१४०) :</p>\n";
    echo "<p class = hn >आतः इति योगविभागादधातोरपि आकारलोपः क्वचित्‌ ।</p>\n";       
    display(6);
}
/* zvayuvamaghonAmataddhite (6.4.133) */
if ($bham === 1 && $taddhita===0 && sub(array("Svan","yuvan","maGavan"),array("+"),blank(0),0))
{
    $text= two(array("Svan","yuvan","maGavan"),array("+"),array("S+u+an","yu+u+an","maGa+u+an"),array("+"),0);
    echo "<p class = sa >By zvayuvamaghonAmataddhite (".link_sutra("6.4.133").") :</p>\n";
    echo "<p class = sa >श्वयुवमघोनामतद्धिते (६.४.१३३) :</p>\n";
    display(3); 
    $samp=array_merge($samp,array(1)); // add 1 to array if samprasAraNa happens.
}
/* vAha UTh (6.4.132) */
if ($bham===1 && sub(array("vAh"),array("+","+I+"),blank(0),0) )
{   
    $text = one(array("vAh+"),array("U+Ah+"),0);
    echo "<p class = sa >By vAha UTh (".link_sutra("6.4.132").") :</p>\n";
    echo "<p class = sa>वाह ऊठ्‌ (६.४.१३२) :</p>\n";
    display(3); $samp=array_merge($samp,array(1));
}
/* vasoH samprasAraNam (6.4.131) */
if ($bham===1 && sub(array("sedivasu!","vasu!"),array("+"),blank(0),0) && $vasu===1)
{   
    $text = one(array("sedivasu!+","vasu!+"),array("sed+u+asu!+","+u+asu!+"),0);
    echo "<p class = sa >By vasoH samprasAraNam (".link_sutra("6.4.131").") :</p>\n";
    if (sub(array("sed+u+asu!+"),blank(0),blank(0),0))
    {
    echo "<p class = hn >Even though the iDAgama is antaraGga, it doesnt hold in samprasAraNa by 'akRtavyUhAH pANinIyAH'.</p>\n";
    }
    echo "<p class = sa>वसोः सम्प्रसारणम्‌ (६.४.१३१) :</p>\n";
    if (sub(array("sed+u+asu!+"),blank(0),blank(0),0))
    {
    echo "<p class = hn >अन्तरङ्गोऽपि इडागमः सम्प्रसारणविषये न प्रवर्तते, 'अकृतव्यूहाः' इति परिभाषया ।</p>\n";
    }
    display(3); $samp=array_merge($samp,array(1));
}
/* samprasAraNAcca (6.1.108) */
if ( in_array(1,$samp) && sub(array("+i+","+u+","U+"),$ac,blank(0),0) )
{   
    $text = two(array("+i+","+u+","U+"),$ac,array("+i+","+u+","U"),blank(count($ac)),0);
    echo "<p class = sa >By samprasAraNAcca (".link_sutra("6.1.108").") :</p>\n";
    echo "<p class = sa>सम्प्रसारणाच्च (६.१.१०८) :</p>\n";
    display(0); 
} 
/* na samprasAraNe samprasAraNam (6.2.37) */
if ( in_array(1,$samp) && arr($text,'/[yv][uU][+]/') )
{   
    echo "<p class = sa >By na samprasAraNe samprasAraNam (".link_sutra("6.2.37").") :</p>\n";
    echo "<p class = sa>न सम्प्रसारणे सम्प्रसारणम्‌ :</p>\n";
    display(0); 
}
/* napuMsakAcca (7.1.19) */
if ( $gender==="n" && in_array($so,array("O","Ow"))) 
{
    $text = two(array("+"),array("Ow","O",),array("+"),array("SI","SI"),0);
    echo "<p class = sa >By napuMsakAcca (".link_sutra("7.1.19").") :</p>\n";
    echo "<p class = sa >नपुंसकाच्च (७.१.१९) :</p>\n";
    display(3); 
    $napuMsakAcca=1; // 0 - napuMsakAcca has not applied. 1 - napuMsakAcca has applied.
} else { $napuMsakAcca=0; }
/* auGaH zyAM pratiSedho vAcyaH (vA) */
if (arr($text,'/[+][S][I]/') && in_array($so,array("O","Ow")))
{
    echo "<p class = pa >By auGaH zyAM pratiSedho vAcyaH (vA) :</p>\n";
    echo "<p class = pa >औङः श्यां प्रतिषेधो वाच्यः (वा) :</p>\n";
    display(0); 
    $auGazyA = 1; // 0 - doesn't prevent application of yasyeti ca. 1 - prevents application of yasyeti ca.
} else { $auGazyA = 0; }
/* paddannomAshRnnizasanyUSandoSanyakaJChakannudannAsaJChasprabhRtiSu (6.1.63) special case kakuddoSaNI */
if (sub(array("doz"),array("+"),array("SI"),0) && in_array($so,array("O","Ow")))
{
    $text = two(array("doz"),array("+"),array("dozan"),array("+"),0);
    echo "<p class = sa >By paddannomAshRnnizasanyUSandoSanyakaJChakannudannAsaJChasprabhRtiSu (".link_sutra("6.1.63").") :</p>\n";
    echo "<p class = hn >prabhRtigrahaNaM prakArArtham. tathA ca auGaH zyAmapi dozannAdezaH. </p>\n";
    echo "<p class = sa >पद्दन्नोमास्‍हृन्निशसन्यूषन्दोषन्यकञ्छकन्नुदन्नासञ्छस्प्रभृतिषु (६.१.६३) :</p>\n";
    echo "<p class = hn >प्रभृतिग्रहणं प्रकारार्थम्‌ । तथा च औङः श्यामपि दोषन्नादेशः ।</p>\n";
    display(0);
}
/* bhasya (6.4.129) and allopo'naH (6.4.134) and vibhASA GizyoH (6.4.236) and na saMyogAdvamantAt (6.4.137) */ 
if ( ($bham === 1 || ($gender==="f" && sub(array("an"),array("+"),array("I"),0))) && $vamanta===0 && arr($text,'/[a][n][+]/') && !(sub(array("udan+","Asan"),blank(0),blank(0),0) && !in_array($so,$yacibham)) ) 
{
    if ( $so==="Ni" || $napuMsakAcca===1 )
    {
    $text = one(array("an+"),array("n+"),1);
    echo "<p class = sa >By allopo'naH (".link_sutra("6.4.134").") and vibhASA GizyoH (".link_sutra("6.4.236").") :</p>\n";
    echo "<p class = sa >अल्लोपोऽनः (६.४.१३४) तथा विभाषा ङिश्योः (६.४.२३६) :</p>\n";
    display(6);    
    }
    else
    {
    $text = one(array("an+"),array("n+"),0);
    echo "<p class = sa >By allopo'naH (".link_sutra("6.4.134").") :</p>\n";
    echo "<p class = sa >अल्लोपोऽनः (६.४.१३४) :</p>\n";
    display(6);    
    } 
    $allopo=1; // 0 - allopa has not happened. 1 - allopa has happened.
} else {$allopo=0; }
/* ho hanterJNinneSu (7.3.54) */
if ( sub(array("h"),array("n"),blank(0),0) && arr(array($fo),'/[h][a][n]/') && !in_array($fo,array("ahan","dIrGAhan")))
{
    $text = two(array("h"),array("n"),array("G"),array("n"),0);
    echo "<p class = sa >By ho hanterJNinneSu (".link_sutra("7.3.54").") :</p>\n";
    echo "<p class = sa >हो हन्तेर्ञ्णिन्नेषु (७.३.५४) :</p>\n";
    display(3); 
    $hohante=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
}
/* jasi ca (7.3.109) */
/* trestrayaH (7.1.53) */
if (arr($text,'/[t][r][i][+][A][m]$/') && !sub(array("stri"),array("+"),blank(0),0) && $so==="Am" )
{
    $text = two(array("tri"),array("Am"),array("traya"),array("Am"),0);
    echo "<p class = sa >By trestrayaH (".link_sutra("7.1.53").") :</p>\n";
    echo "<p class = sa >त्रेस्त्रयः (७.१.५३) :</p>\n";
    display(3);
}
if (arr($text,'/[t][r][i][+][A][m]$/') && !sub(array("stri"),array("+"),blank(0),0) && $so==="Am" && $fo!=="tri")
{
    $text = two(array("tri"),array("Am"),array("traya"),array("Am"),1);
    echo "<p class = sa >By trestrayaH (".link_sutra("7.1.53").") and 'gauNatve tu neti kecit' :</p>\n";
    echo "<p class = sa >त्रेस्त्रयः (७.१.५३) तथा 'गौणत्वे तु नेति केचित्‌' :</p>\n";
    display(3);
}
/* viSvagdevayozca TeradryaJcatAvapratyaye (6.3.92) */
$sarvanamaadri = array("sarvadri","viSvadri","uBadri","uBayadri","ataradri","atamadri","anyadri","anyataradri","itaradri","tvadri","tvadri","nemadri","simadri","tyadri","tadri","yadri","etadri","idadri","adadri","ekadi","dvadri","yuzmadri","asmadri","Bavadri","kadri","idakadri");
if ($ancu===1 && ( sub(array("vizvak","deva"),array("anc"),blank(0),0)||sub($sarvanama,array("anc"),blank(0),0) ) )
{
    $text = three($sarvanama,array("anc"),array("+"),$sarvanamaadri,array("anc"),array("+"),0);
    $text = three(array("vizvak","deva"),array("anc"),array("+"),array("vizvadri","devadri"),array("anc"),array("+"),0);
    echo "<p class = sa >viSvagdevayozca TeradryaJcatAvapratyaye (".link_sutra("6.3.92").") :</p>\n";
    echo "<p class = sa >विश्वग्देवयोश्च टेरद्र्याञ्चतावप्रत्यये (६.३.९२) :</p>\n";
    display(0);
    /* patch for iko yaNaci */
        if(sub(array('i','I','u','U'),prat('ac'),blank(0),0) && $bham===0) // for function prat, see function.php.
            {
            $text = two(array('i','I','u','U'),prat('ac'),array('y','y','v','v'),prat('ac'),0);
            echo "<p class = sa >By iko yaNaci (".link_sutra("6.1.77").") :</p>\n";
            echo "<p class = sa >इको यणचि (६.१.७७) :</p>\n";
            display(0);
            }
        if(sub(array("f","F","x","X"),prat('ac'),blank(0),0) && $bham===0)
            {
            $text = two(array("f","F","x","X"),prat('ac'),array("r","r","l","l"),prat('ac'),0);
            echo "<p class = sa >By iko yaNaci (".link_sutra("6.1.77").") :</p>\n";
            echo "<p class = sa >इको यणचि (५.१.७७) :</p>\n";
            display(0); 
            }
}
/* adasa au sulopazca (7.2.107) */
if (sub(array("adas"),array("+"),array("su!"),0) && $so==="su!" && $fo==="adas")
{
    $text = two(array("adas"),array("su!"),array("adaO"),array(""),0);
    echo "<p class = sa >By adasa au sulopazca (".link_sutra("7.2.107").") :</p>\n";
    echo "<p class = sa >अदस औ सुलोपश्च (७.२.१०७) :</p>\n";
    display(3);
}
/* tyadAdInAmaH (7.2.102) */
if (sub(array("dvi"),array("+"),blank(0),0) && in_array($so,$sup) && ends(array($fo),array("dvi"),1) && $_GET['cond1_3_2']==="2" && $idamoma===0 && $svamo===0 && $noatvasatva===0)
{
    $text = one(array("dvi"),array("dv+a"),0);
    echo "<p class = sa >By tyadAdInAmaH (".link_sutra("7.2.102").") :</p>\n";
    echo "<p class = hn >Only the words till 'dvi' are included. (vA 4468) :</p>\n";
    echo "<p class = sa >त्यदादीनामः (७.२.१०२) :</p>\n";
    echo "<p class = hn >द्विपर्यन्तानामेवेष्टिः (वा ४४६८) :</p>\n";
    display(3);
}
$tyadadinamah = array("dv+a","tya+a","ta+a","ya+a","eta+a","ida+a","ada+a","eka+a","idaka+a"); // creating a set of words eligible for tyadAdInAmaH
$tyadadinamah1 = array("dva","tya","ta","ya","eta","ida","ada","eka","idaka"); // creating a list of converted words after tyadAdInAmaH.
if (sub($tyadadi,array("+"),blank(0),1) && !sub(array("dvi"),array("+"),blank(0),1) && in_array($so,$sup) && $idamoma===0 && $svamo===0 && $noatvasatva===0 )
{
    $text = one($tyadadi,$tyadadinamah,0);
    echo "<p class = sa >By tyadAdInAmaH (".link_sutra("7.2.102").") :</p>\n";
    echo "<p class = hn >Only the words till 'dvi' are included. (vA 4468) :</p>\n";
    echo "<p class = sa >त्यदादीनामः (७.२.१०२) :</p>\n";
    echo "<p class = hn >द्विपर्यन्तानामेवेष्टिः (वा ४४६८) :</p>\n";
    display(3);
    $text = one($tyadadinamah,$tyadadinamah1,0);
    $text=one(array("etam"),array("eta+am"),0);
    echo "<p class = sa >By ato guNe (".link_sutra("6.1.96").") :</p>\n";
    echo "<p class = sa >अतो गुणे (६.१.९६) :</p>\n";
    display(0);    

}
/* ajAdyataSTAp (4.1.4) */
if ($gender==="f" && sub(array("a+"),$sup,blank(0),0) && in_array($so,$sup) && ($SaTsvasrAdi=0 || sub(array("a+"),$sup,blank(0),0)) && $ajAdyataSTAp===0)
{
    $text = two(array("a+"),$sup,array("A+"),$sup,0);
    echo "<p class = st >By ajAdyataSTAp (".link_sutra("4.1.4").") :</p>\n";
    echo "<p class = st >अजाद्यतष्टाप्‌ (४.१.४) :</p>\n";
    display(3); 
    $Ap=1;
}
/* akaH savarNe dIrghaH (6.1.101) */
if ($gender==="f" && $Ap===1 && sub(array("idaA","tyaA","taA","yaA","etaA","adaA"),array("+"),blank(0),0) && in_array($fo,array("idam","idakam","tyad","tad","yad","etad","adas")) && in_array($so,$sup))
{
    $text = one(array("idaA","tyaA","taA","yaA","etaA","adaA"),array("idA+","tyA","tA","yA","etA","adA"),0);
    echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
    echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
    display(3);
}
/* adasa au sulopazca (7.2.107) */
if (sub(array("adakas"),array("+"),array("su!"),0) && $so==="su!" && $fo==="adakas")
{
    $text = two(array("adakas"),array("su!"),array("adakaO"),array(""),1);
    $text = two(array("adakas"),array("su!"),array("asukas"),array(""),0);
    echo "<p class = sa >By adasa au sulopazca (".link_sutra("7.2.107").") and autvapratiSedhaH sAkackasya vA vaktavyaH sAdutvaM ca (vA 4482) :</p>\n";
    echo "<p class = sa >अदस औ सुलोपश्च (७.२.१०७) तथा औत्वप्रतिषेधः साकच्कस्य वा वक्तव्यः सादुत्वं च (वा ४४८२) :</p>\n";
    display(3);
}
/* tadoH saH sAvanantyayoH (7.2.106) */ 
$tyadadinamah3 = array("dva","tya","eta","ta","ida","ada","eka","idaka","tyA","tA","etA"); // eligible for application of this sUtra.
$tyadadinamah2 = array("dva","sya","eza","sa","isa","asa","eka","isaka","syA","sA","esA"); // conversion after application of this sUtra.
if (sub($tyadadinamah3,array("su!"),blank(0),0) && $sarvafinal!==0 && $noatvasatva===0)
{
    $text = two($tyadadinamah3,array("su!"),$tyadadinamah2,array("su!"),0);
    echo "<p class = sa >By tadoH saH sAvanantyayoH (".link_sutra("7.2.106").") :</p>\n";
    echo "<p class = sa >तदोः सः सावनन्त्ययोः (७.२.१०६) :</p>\n";
    display(3);
}
if (sub(array("adaO","adakaO"),blank(0),blank(0),0))
{
    $text = one(array("adaO","adakaO"),array("asaO","asakaO"),0);
    $text = one(array("adakas"),array("asukas"),0);
    echo "<p class = sa >By tadoH saH sAvanantyayoH (".link_sutra("7.2.106").") :</p>\n";
    echo "<p class = sa >तदोः सः सावनन्त्ययोः (७.२.१०६) :</p>\n";
    display(3);
}
/* sarvanAmnaH smai (7.1.14) */  
if (arr($text,'/[a][+][N][e]/') && $so === "Ne" && $sarvafinal!==0)
{
    if ($sarvafinal===2)
    {
    $text = last(array("Ne"),array("smE"),1);        
    }
    else
    {
    $text = last(array("Ne"),array("smE"),0);        
    }
    echo "<p class = sa >By sarvanAmnaH smai (".link_sutra("7.1.14").") :</p>\n";
    echo "<p class = sa >सर्वनाम्नः स्मै (७.१.१४) :</p>\n";
    display(3); 
    $sarva =1; // 0 - sarvanAmnaH smai has not applied. 1 - sarvanAmnaH smai has applied.
} else { $sarva = 0; }
/* GasiGyoH smAtsminau (7.1.15) */ 
if (arr($text,'/[a][+][N]/') && $pada=== "pratyaya" && in_array($so,array("Nasi!","Ni")) && $sarvafinal!==0)
{
    if ($sarvafinal===2)
    {
    $text = last(array("Ni","Nasi!"),array("smin","smAt"),1);        
    }
    else
    {
    $text = last(array("Ni","Nasi!"),array("smin","smAt"),0);        
    }
    echo "<p class = sa >By GasiGyoH smAtsminau (".link_sutra("7.1.15").") :</p>\n";
    echo "<p class = sa >ङसिङ्योः स्मात्स्मिनौ (७.१.१५) :</p>\n";
    display(3);
    $sarva1 =1; // 0 - GasiGyoH smAtsminau has not applied. 1 - GasiGyoH smAtsminau has applied.
} else { $sarva1 = 0; }
/* pUrvAdibhyo navabhyo vA (7.1.16) */ 
if (ends(array($fo),array("pUrva","para","avara","dakziRa","uttara","apara","aDara","sva","antara"),1) && $pada=== "pratyaya" && in_array($so,array("Nasi!","Ni")) && $sarvafinal!==0)
{
    $text = last(array("smin","smAt"),array("Ni","Nasi!"),1);
    echo "<p class = sa >By pUrvAdibhyo navabhyo vA (".link_sutra("7.1.16").") :</p>\n";
    echo "<p class = sa >पूर्वादिभ्यो नवभ्यो वा (७.१.१६) :</p>\n";
    display(3);
    $sarva1 =1; 
} elseif ($sarva1 ===1) 
    { $sarva1 = 1; }
    else
    {
        $sarva1 = 0;
    }
/* TAGasiGasAminAtsyAH (7.1.12) */
if ( in_array($so,array("wA","Nas")) && arr($text,'/[a][+][wN]/'))
{
    $text = one(array("a+wA","a+Nas"),array("a+ina","a+sya"),0);
    $text = two(array("jaras"),array("ina","sya"),array("jaras"),array("wA","Nas"),0);
    echo "<p class = sa >By TAGasiGasAminAtsyAH (".link_sutra("7.1.12").") :</p>\n";
    echo "<p class = sa >टाङसिङसामिनात्स्याः (७.१.१२) :</p>\n";
    display(3);
    $wa = 1; // 0 - wA.. has not applied. 1 - wA.. has applied.
} else { $wa =0; }
if (arr($text,'/[a][+][N]/') && in_array($so,array("Nasi!")))
{
    $text = one(array("a+Nasi!"),array("a+At"),0);
    $text = two(array("jaras"),array("At"),array("jaras"),array("Nasi!"),0);
    echo "<p class = sa >By TAGasiGasAminAtsyAH (".link_sutra("7.1.12").") :</p>\n";
    echo "<p class = sa >टाङसिङसामिनात्स्याः (७.१.१२) :</p>\n";
    display(3);
    $wa1 = 1; // 0 - wA.. has not applied. 1 - wA.. has applied.
} else { $wa1 =0; }
/* Ami sarvanAmnaH suT (7.1.15) */
if ( $so === "Am" && $sarvafinal !== 0)
{
    if ( $sarvafinal === 2)
    {
    $text = last(array("Am"),array("sAm"),1);      
    }
    else
    {
    $text = last(array("Am"),array("sAm"),0);        
    }
    echo "<p class = sa >By Ami sarvanAmnaH suT (".link_sutra("7.1.15").") :</p>\n";
    echo "<p class = sa >आमि सर्वनाम्नः सुट्‌ (७.१.१५) :</p>\n";
    display(3); 
    $sut=1; // 0 - Ami sarvanAmnaH sUT has not applied. 1 - Ami sarvanAmnaH suT has applied. 
} else { $sut=0;}
/* sAma Akam (7.1.33) */
if (sub(array("asma","yuzma","ima","itva","Ava","yuva"),array("+"),array("sAm"),0) && in_array($so,array("Am")))
{
    $text = one(array("asma+sAm","yuzma+sAm","ima+sAm","itva+sAm","Ava+sAm","yuva+sAm"),array("asma+Akam","yuzma+Akam","ima+Akam","itva+Akam","Ava+Akam","yuva+Akam"),0);
    echo "<p class = sa >By sAma Akam (".link_sutra("7.1.33").") :</p>\n";
    echo "<p class = sa >साम आकम्‌ (७.१.३३) :</p>\n";
    display(3); 
    $sAmaAkam=1; // 0 - sAma Akam has not applied. 1 - sAma Akam has applied.
} else { $sAmaAkam=0; }
/* dvitIyATaussvenaH (2.4.34) */ 
if (sub(array("ida+","eta+","idaka+","idA+",),blank(0),blank(0),0) && in_array($fo,array("idam","etad","idakam")) && $anvadesha===1 && in_array($so,array("am","Ow","Sas","wA","os")))
{
    $text = one(array("ida+","eta+","idaka+","idA+",),array("ena+","ena+","ena+","enA+"),0);
    echo "<p class = sa >By dvitIyATaussvenaH (".link_sutra("2.4.34").") :</p>\n";
    echo "<p class = sa >द्वितीयाटौस्स्वेनः (२.४.३४) :</p>\n";
    display(0);
}
/* idamo'nvAdeze'zanudAttastRtIyAdau (2.4.32) */
if (sub(array("idaka+"),blank(0),blank(0),0) && $fo==="idakam" && $anvadesha===1 && in_array($so,$tRtIyAdi))
{
    $text = one(array("idaka+"),array("a+"),0);
    echo "<p class = sa >By idamo'nvAdeze'zanudAttastRtIyAdau (".link_sutra("2.4.32").") :</p>\n";
    echo "<p class = sa >इदमोऽन्वादेशेऽशनुदात्तस्तृतीयादौ (२.४.३२) :</p>\n";
    display(0);
}
/* nAnarthake'lo'ntyavidhiranabhyAsavikAre (vA 490) */
// Pending. Not clear to me.
/* Adyantavadekasmin */
// paribhASA. Difficult to code.

/* goto Nit (7.1.90) and oto Niditi vAcyam (vA 5035) */
if (sub(array("o+"),$sarvanamasthana,blank(0),0) && !in_array($so,array("am","Sas")))
{   
    if (sub(array("go+"),$sarvanamasthana,blank(0),0))
    {$Nidvat1 = 1;
    echo "<p class = pa >By goto Nit (".link_sutra("7.1.90").") :</p>\n";
    echo "<p class = pa >गोतो णित्‌ (७.१.९०) :</p>\n";        
    display(3);
    }
    elseif (!preg_match('/[o]$/',$fo))
    {$Nidvat1 = 0;
    echo "<p class = pa >oto Niditi vAcyam (vA 5035) is overruled by 'okArAntAdvihitaM sarvanAmasthAnam'.</p>\n";
    echo "<p class = pa >ओकारान्ताद्विहितं सर्वनामस्थानम्‌ इति व्याख्यानात्‌ ओतो णिदिति वाच्यमित्यस्य प्रवृत्तिर्नास्ति ।</p>\n";
    display(0);
    }
    else
    {$Nidvat1 = 1;
    echo "<p class = pa >By oto Niditi vAcyam (vA 5035) :</p>\n";
    echo "<p class = pa >ओतो णिदिति वाच्यम्‌ (वा ५०३५) :</p>\n";
    display(3);
    }
} else {$Nidvat1 =0; }
/* aco JNiti (7.2.115) */ 
if ((arr($text,'/['.flat($ac).'][+][YR]/')||arr($text,'/[a][+][*][YR]$/')||$Nidvat===1||$Nidvat1===1) && arr($text,'/['.pc('ac').'][+]/') )
{ 
    $text = two($ac,array("+"),vriddhi($ac),array("+"),0);
    echo "<p class = sa >By aco JNiti (".link_sutra("7.2.115").") :</p>\n";
    echo "<p class = sa >अचो ञ्णिति (७.२.११५) :</p>\n";
    display(3);
}
if (arr($text,'/[iufx][+][j]/') && $so==="jas")
{
    $text = two(array("i","u","f","x"),array("+"),array("e","o","ar","al"),array("+"),0);
    echo "<p class = sa >By jasi ca (".link_sutra("7.3.109").") :</p>\n";
    echo "<p class = sa >जसि च (७.३.१०९) :</p>\n";
    display(3);
}
/* RRta iddhAtoH (7.1.100) */
$kRR = array("kF","tF","gF"); // Creating a list of RR-anta words. They are anukaraNas of dhAtus mostly. If additional forms are found, list them here.
if (arr($text,'/[ktg][F][+]/'))
{
    $dhatu = 1;
}
if (arr($text,'/[ktg][F][+]/'))
{
    $text = two(array("F"),array("+"),array("ir"),array("+"),1);
    echo "<p class = sa >By RRta iddhAtoH (".link_sutra("7.1.100").") :</p>\n";
    echo "<p class = hn >'prakRtivadanukaraNam' (pa 37) has vaikalpika atideza, which mandates itva here. As this is optional, itvAbhAva is also seen. </p>\n";
    echo "<p class = sa >ॠत इद्धातोः (७.१.१००) :</p>\n";
    echo "<p class = hn >'कॄ' 'तॄ' अनयोरनुकरणे 'प्रकृतिवदनुकरणम्‌-' (प ३७) इति वैकल्पिकातिदेशादित्त्वं रपरत्वं च । इत्त्वाभावपक्षे 'ऋदुशनस्‌-' इति 'ऋतो ङि-' इति च तपरकरणादनङ्गुणौ न ।</p>\n";
    display(3);    
}
if ( $dhatu === 1 && sub(array("F"),array("+"),blank(0),0) && !arr($text,'/[ktg][F][+]/') )
{
    $text = two(array("F"),array("+"),array("ir"),array("+"),0);
    echo "<p class = sa >By RRta iddhAtoH (".link_sutra("7.1.100").") :</p>\n";
    echo "<p class = sa >ॠत इद्धातोः (७.१.१००) :</p>\n";
    display(3);
}
/* zapzyanornityam (7.1.81) */ 
if ( arr($text,'/[aA][t][+][S][I]/') && ($shap===1|| $shyan===1 ) )
{
    $text = mit('/['.pc('hl').'][+]/','n',0); $num=array_merge($num,array(1)); // function mit adds a mit Agama according to midaco'ntyAtparaH. See function.php
    $text = one(array("annc"),array("anc"),0);
    $text = one(array("+In+","+An+"),array("+I+","+A+"),0);
    echo "<p class = sa >By zapzyanornityam (".link_sutra("7.1.81").") :</p>\n";
    echo "<p class = sa >शप्श्यनोर्नित्यम्‌ (७.१.८१) :</p>\n";
    display(0); $zapzyan=1;
}
if ( arr($text,'/[a][t][I][+]/') && ($shap===1|| $shyan===1 ) )
{
    $text = two(array("atI"),array("+"),array("antI"),array("+"),0); $num=array_merge($num,array(1)); // function mit adds a mit Agama according to midaco'ntyAtparaH. See function.php
    $text = one(array("annc"),array("anc"),0);
    $text = one(array("+In+","+An+"),array("+I+","+A+"),0);
    echo "<p class = sa >By zapzyanornityam (".link_sutra("7.1.81").") :</p>\n";
    echo "<p class = sa >शप्श्यनोर्नित्यम्‌ (७.१.८१) :</p>\n";
    display(0); $zapzyan=1;
}
/* AcChInadyornum (7.1.80) */ 
if (arr($text,'/[aA][t][+[S][I]/') && $shatR===1 && itcheck(array("f"),0) && $AcCInadyo===1 )
{
    $text = mit('/['.pc('hl').'][+]/','n',1); $num=array_merge($num,array(1));
    $text = one(array("annc"),array("anc"),0);
    echo "<p class = sa >By AcChInadyornum (".link_sutra("7.1.80").") :</p>\n";
    echo "<p class = sa >आच्छीनद्योर्नुम्‌ (७.१.८०) :</p>\n";
    display(0);
}
if (sub(array("atI"),array("+"),blank(0),0) && $shatR===1 && itcheck(array("f"),0) && $AcCInadyo===1 )
{
    $text = two(array("atI"),array("+"),array("antI"),array("+"),0); $num=array_merge($num,array(1));
    $text = one(array("annc"),array("anc"),0);
    echo "<p class = sa >By AcChInadyornum (".link_sutra("7.1.80").") :</p>\n";
    echo "<p class = sa >आच्छीनद्योर्नुम्‌ (७.१.८०) :</p>\n";
    display(0);
}
/* idudbhAym (7.3.117) */
if ($nadi!==0 && arr($text,'/[iu][+][N][i]$/'))
{
    $text = two(array("i","u"),array("+Ni"),array("i","u"),array("+Am"),1);
    echo "<p class = sa >By idudbhAym (".link_sutra("7.3.117").") :</p>\n";
    echo "<p class = sa >इदुद्भ्याम्‌ (७.३.११७) :</p>\n";
    display(3);    
}
/* GerAmnadyAmnIbhyaH (7.3.116) */
if (arr($text,'/[n][I][+]/') && $pada=== "pratyaya" && $so==="Ni")
{
    $text = two(array("+"),array("Ni"),array("+"),array("Am"),0);
    echo "<p class = sa >By GerAmnadyAmnIbhyaH (".link_sutra("7.3.116").") :</p>\n";
    echo "<p class = sa >ङेराम्नद्याम्नीभ्यः (७.३.११६) :</p>\n";
    display(3);
} 
if ($nadi!==0 && $pada=== "pratyaya" && $so==="Ni")
{
    if ($nadi===1)
    {
    $text = two(array("I","U"),array("+Ni"),array("I","U"),array("+Am"),0);        
    }
    else
    {
    $text = two(array("I","U"),array("+Ni"),array("I","U"),array("+Am"),1);        
    }
    echo "<p class = sa >By GerAmnadyAmnIbhyaH (".link_sutra("7.3.116").") :</p>\n";
    echo "<p class = sa >ङेराम्नद्याम्नीभ्यः (७.३.११६) :</p>\n";
    display(3);
}
if ($Ap===1 && $pada=== "pratyaya" && $so==="Ni")
{
    $text = two(array("A"),array("+Ni"),array("A"),array("+Am"),0);
    echo "<p class = sa >By GerAmnadyAmnIbhyaH (".link_sutra("7.3.116").") :</p>\n";
    echo "<p class = sa >ङेराम्नद्याम्नीभ्यः (७.३.११६) :</p>\n";
    display(3);
}
/* aut (7.3.118) */
if (arr($text,'/[iu][+][N][i]$/') && $so==="Ni" && $pada==="pratyaya")
{
    $text = two(array("i","u",),array("Ni"),array("i","u"),array("O"),0);
    echo "<p class = sa >By aut (".link_sutra("7.3.118").") :</p>\n";
    echo "<p class = sa >औत्‌ (७.३.११८) :</p>\n";
    display(3);
}
/* ANnadyAH (7.3.112)  */ 
// The method for finding Git is coarse. needs fine tuning. 
if ($nadi!==0 && arr($text,'/[+][N]/') && in_array($so,array("Ne","Nasi!","Nas",)))
{
    if ($nadi===1)
    {
    $text = two(array("+"),array("N"),array("+"),array("A+N"),0);        
    }
    else
    {
    $text = two(array("+"),array("N"),array("+"),array("A+N"),1);        
    }
    echo "<p class = sa >By ANnadyAH (".link_sutra("7.3.112").") :</p>\n";
    echo "<p class = sa >आण्नद्याः (७.३.११२) :</p>\n";
    display(3); 
    $Agama=array("Aw");
}
/* acca gheH (7.3.119) */ 
if ($ghi===1 && in_array($so,array("Ni")))
{
    $text = two(array("i","u"),array("O"),array("a","a"),array("O"),0);
    echo "<p class = sa >By acca gheH (".link_sutra("7.3.119").") :</p>\n";
    echo "<p class = sa >अच्च घेः (७.३.११९) :</p>\n";
    display(3);
}
/* gherGiti (7.3.111) */
if ($ghi===1 && $noghe===0 && arr($text,'/[iu][+]/') && in_array($so,array("Ne","Nasi!","Nas")))
{
    $text = two(array("i","u"),array("Ne","Nasi!","Nas"),array("e","o"),array("Ne","Nasi!","Nas"),0);
    echo "<p class = sa >By gherGiti (".link_sutra("7.3.111").") :</p>\n";
    echo "<p class = sa >घेर्ङिति (७.३.१११) :</p>\n";
    display(3);
}
/* auGaH ApaH (7.1.18) */
if ($Ap===1 && arr($text,'/[A][+][O]$/') && in_array($so,array("O")))
{
    $text = two(array("A+"),array("O"),array("A+"),array("SI",),0);
    echo "<p class = sa >By auGa ApaH (".link_sutra("7.1.18").") :</p>\n";
    echo "<p class = sa >औङ आपः (७.१.१८) :</p>\n";
    display(3);
}
if ($Ap===1 && arr($text,'/[A][+][O][w]$/') && in_array($so,array("Ow")))
{
    $text = two(array("A+"),array("Ow"),array("A+"),array("SI"),0);
    echo "<p class = sa >By auGaH ApaH (".link_sutra("7.1.18").") :</p>\n";
    echo "<p class = sa >औङ आपः (७.१.१८) :</p>\n";
    display(3);
}
/* sambuddhau ca (7.3.106) */
if ($Ap===1 && $sambuddhi===1 && in_array($so,array("su!")) && arr($text,'/[A][+]/'))
{
    $text = two(array("A+"),array("su!"),array("e+"),array("su!"),0);
    echo "<p class = sa >By sambuddhau ca (".link_sutra("7.3.106").") :</p>\n";
    echo "<p class = sa >सम्बुद्धौ च (७.३.१०६) :</p>\n";
    display(3);
}
/* sarvanAmnaH syADDhrasvazca (7.3.114) */ 
$sarvanamastri = array("sarvA","viSvA","uBA","uBayA","atarA","atamA","anyA","anyatarA","itarA","tvA","nemA","simA","pUrvA","parA","avarA","dakziRA","uttarA","aparA","aDarA","svA","antarA","tyA","tA","yA","etA","idA","adA","ekA","dvA","kA","idakA");
$sarvanamastri1 = array("sarva","viSva","uBa","uBaya","atara","atama","anya","anyatara","itara","tva","nema","sima","pUrva","para","avara","dakziRa","uttara","apara","aDara","sva","antara","tya","ta","ya","eta","ida","ada","eka","dva","ka","idaka");
if ($Ap===1 && $sarvafinal !==0 && in_array($so,array("Ne","Nas","Ni")) && ( sub($sarvanamastri,blank(0),blank(0),0) ||in_array($fo,array("idam")) ))
{ 
    if ( $sarvafinal === 2)
    {
    $text = one(array("A+Ne","A+Nas","A+Am"),array("a+syA+Ne","a+syA+Nas","a+syA+Am"),1);        
    }
    else
    {
    $text = one(array("A+Ne","A+Nas","A+Am"),array("a+syA+Ne","a+syA+Nas","a+syA+Am"),0);        
    }
    echo "<p class = sa >By sarvanAmnaH syADDhrasvazca (".link_sutra("7.3.114").") :</p>\n";
    echo "<p class = sa >सर्वनाम्नः स्याड्ढ्रस्वश्च (७.३.११४) :</p>\n";
    display(3); 
    $syaddhrasva = 1; // 0 - sarvanAmnaH syADDhrasvazca has not applied. 1 - sarvanAmnaH syADDhrasvazca has applied.
} else { $syaddhrasva = 0; }
if ($Ap===1  && $sarvafinal !==0 && in_array($so,array("Nasi!")) && ( sub($sarvanamastri,blank(0),blank(0),0) ||in_array($fo,array("idam"))) )
{
    if ( $sarvafinal === 2)
    {
    $text = one(array("A+Nasi!"),array("a+syA+Nasi!"),1);        
    }
    else
    {
    $text = one(array("A+Nasi!"),array("a+syA+Nasi!"),0);        
    }
    echo "<p class = sa >By sarvanAmnaH syADDhrasvazca (".link_sutra("7.3.114").") :</p>\n";
    echo "<p class = sa >सर्वनाम्नः स्याड्ढ्रस्वश्च (७.३.११४) :</p>\n";
    display(3); 
    $syaddhrasva1 = 1; // 0 - sarvanAmnaH syADDhrasvazca has not applied. 1 - sarvanAmnaH syADDhrasvazca has applied.
} else {$syaddhrasva1=0; }
/* hali lopaH (7.2.113) */
if (sub(array("ida+","idA+"),$hl,blank(0),0) && $fo==="idam" && !in_array($so,array("jas","Ow","O","Sas","wA","os")))
{
    $text = one(array("ida+","idA+",),array("a+","A+",),0);
    echo "<p class = sa >By hali lopaH (".link_sutra("7.2.113").") :</p>\n";
    echo "<p class = sa >हलि लोपः (७.२.११३) :</p>\n";
    display(3);
}
/* anApyakaH (7.2.112) */
if (sub(array("ida+","idA+"),blank(0),blank(0),0) && $fo==="idam" && in_array($so,$tRtIyAdiSvaci))
{
    $text = one(array("ida+","idA+"),array("ana+","anA+"),0);
    echo "<p class = sa >By anApyakaH (".link_sutra("7.2.112").") :</p>\n";
    echo "<p class = sa >अनाप्यकः (७.२.११२) :</p>\n";
    display(3);
}
/* AGi cApaH (7.3.105) */
if ($Ap===1  && in_array($so,array("wA","os")))
{
    $text = two(array("A+"),array("wA","os"),array("e+"),array("wA","os"),0);
    echo "<p class = sa >By AGi cApaH (".link_sutra("7.3.105").") :</p>\n";
    echo "<p class = sa >आङि चापः (७.३.१०५) :</p>\n";
    display(3);
}
/* vibhASA diksamAse bahuvrIhau (1.1.28) */
if ($Ap===1  && in_array($so,array("Ne","Nas","Nasi!","Ni")) && in_array(array($fo),$diksamAsa))
{
    echo "<p class = pa >vibhASA diksamAse bahuvrIhau (".link_sutra("1.1.28").") </p>\n";
    echo "<p class = pa >विभाषा दिक्समासे बहुव्रीहौ (१.१.२८) </p>\n";
    display(0); 
    $sarvafinal = 2;
}
/* yADApaH (7.3.113) */
if ($Ap===1 && (( in_array($so,array("Ne","Nas","Ni")) && $syaddhrasva===0 ) || ( in_array($so,array("Ne","Nas","Ni")) && $sarvafinal===2 ) ))
{
    $text = three(array("A+"),array(""),array("Ne","Nas","Ni","Am"),array("A+"),array("yA+"),array("Ne","Nas","Ni","Am"),0);
    $text = one(array("syA+yA+"),array("syA+"),0);
    echo "<p class = sa >By yADApaH (".link_sutra("7.3.113").") :</p>\n";
    echo "<p class = sa >याडापः (७.३.११३) :</p>\n";
    display(3);
}
if ($Ap===1 && (( in_array($so,array("Nasi!")) && $syaddhrasva===0 ) || ( in_array($so,array("Nasi!")) && $sarvafinal===2 ) ))
{
    $text = three(array("A+"),array(""),array("Nasi!"),array("A+"),array("yA+"),array("Nasi!"),0);
    $text = one(array("syA+yA+"),array("syA+"),0);
    echo "<p class = sa >By yADApaH (".link_sutra("7.3.113").") :</p>\n";
    echo "<p class = sa >याडापः (७.३.११३) :</p>\n";
    display(3);
}
/* nedamadasorakoH (7.1.11) */
if (arr($text,'/^[a][+]/') && $so==="Bis" && in_array($fo,array("idam","idakam","adas")))
{
    echo "<p class = sa >By nedamadasorakoH (".link_sutra("7.1.11").") :</p>\n";
    echo "<p class = sa >नेदमदसोरकोः (७.२.११) :</p>\n";
    display(3); 
    $nedamadas=1; // 0 - nedamadasorakoH doesn't prevent application of ato bhisa ais. 1 - nedamadasorakoH prevents application of ato bhisa ais. 
} else { $nedamadas=0; }
/* jasaH zI (7.1.17) */ 
if (arr($text,'/[a][+]/') && $pada=== "pratyaya" && $so === "jas" && ends(array($fo),$sarvanama,1) && $nojas===0  && $sarvafinal!==0 )
    {
    if ($sarvafinal===2)
    {
    $text = last(array("jas"),array("SI"),1);        
    }
    else
    {
    $text = last(array("jas"),array("SI"),0);        
    }
    echo "<p class = sa >By jasaH zI (".link_sutra("7.1.17").") :</p>\n";
    echo "<p class = sa >जसः शी (७.१.१७) :</p>\n";
    echo "<p class = hn >N.B. anekAlzitsarvasya mandates sarvAdeza :</p>\n";
    echo "<p class = hn >अनेकाल्शित्सर्वस्य से सर्वादेश होता है । :</p>\n";
    display(3);
    $sarva2 =1; // 0 - jasaH zI has not happened. 1 - jasaH zI has happened. 
} else { $sarva2 = 0; }
/* pUrvaparAvaradakSiNottarAparAdharANi vyavasthAyAmasaJjJAyAm (1.1.34) */
if ($so === "jas" && $purvapara===1 && in_array($fo,array("pUrva","para","avara","dakziRa","uttara","apara","aDara",)))
{
    $text = last(array("SI"),array("jas"),1);
    echo "<p class = sa >By pUrvaparAvaradakSiNottarAparAdharANi vyavasthAyAmasaJjJAyAm (".link_sutra("1.1.34").") :</p>\n";
    echo "<p class = sa >पूर्वपरावरदक्षिणोत्तरापराधराणि व्यवस्थायामसंज्ञायाम्‌ (१.१.३४) :</p>\n";
    display(0); 
    $purva=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else {$purva=0;}
/* svamajJAtidhanAkhyAyAm (1.1.35) */
if ($so === "jas" && $sva===1 &&  in_array($fo,array("sva",)))
{
    $text = last(array("SI"),array("jas"),1);
    echo "<p class = sa >By svamajJAtidhanAkhyAyAm (".link_sutra("1.1.35").") :</p>\n";
    echo "<p class = sa >स्वमज्ञातिधनाख्यायाम्‌ (१.१.३५) :</p>\n";
    display(0); 
}
/* antaraM bahiryogopasaMvyAnayoH (1.1.36) */
if ($so === "jas" && $antara===1 && in_array($fo,array("antara",)))
{
    $text = last(array("SI"),array("jas"),1);
    echo "<p class = sa >By antaraM bahiryogopasaMvyAnayoH (".link_sutra("1.1.36").") :</p>\n";
    echo "<p class = sa >अन्तरं बहिर्योगोपसंव्यानयोः (१.१.३६) :</p>\n";
    display(0); 
}
/* vibhASA jasi */
/* na bahuvrIhau */
/* tRtIyAsamAse */
/* dvandve ca */
// These have been taken care of in user feedback.
/* prathamacaramatayAlpArdhakatipayanemAzca (1.1.33) */
if ($so === "jas" && in_array($fo,array("praTama","carama","alpa","arDa","katipaya")))
{
    $text = last(array("jas"),array("SI"),1); // function last removes and replaces a specified string from the end of word. for details, see function.php.
    echo "<p class = sa >By prathamacaramatayAlpArdhakatipayanemAzca (".link_sutra("1.1.33").") :</p>\n";
    echo "<p class = sa >प्रथमचरमतयाल्पार्धकतिपयनेमाश्च (१.१.३३) :</p>\n";
    display(0); 
} 
if ($so === "jas" && in_array($fo,array("nema")))
{
    $text = last(array("SI"),array("jas"),1);
    echo "<p class = sa >By prathamacaramatayAlpArdhakatipayanemAzca (".link_sutra("1.1.33").") :</p>\n";
    echo "<p class = sa >प्रथमचरमतयाल्पार्धकतिपयनेमाश्च (१.१.३३) :</p>\n";
    display(0);
}     
/* dvandve ca (1.1.31) */
if ($so === "jas" && $dvandveca===1 )
{
    $text = last(array("jas"),array("SI"),1);
    echo "<p class = sa >By vibhASA jasi (".link_sutra("1.1.32").") :</p>\n";
    echo "<p class = sa >विभाषा जसि (१.१.३२) :</p>\n";
    display(0);
}    
if ($so === "jas" && arr(array($fo),'/(taya)$/'))
{
    $text = last(array("jas"),array("SI"),1);
    echo "<p class = sa >By prathamacaramatayAlpArdhakatipayanemAzca (".link_sutra("1.1.33").") :</p>\n";
    echo "<p class = sa >प्रथमचरमतयाल्पार्धकतिपयनेमाश्च (१.१.३३) :</p>\n";
    display(0); 
}    

/* upadeze'janunAsika it (1.3.2)*/ // Temporary patch. Not coded perfectly.
if (arr($text,'/['.flat($ac).'][!]/'))
{
    it('/(['.flat($ac).'][!])/');
    echo "<p class = pa >By upadeze'janunAsika it (".link_sutra("1.3.2").") :</p>\n";
    echo "<p class = pa >उपदेशेऽजनुनासिक इत्‌ (१.३.२) :</p>\n";
    display(0);
    $text = two($ac,array("!"),blank(count($ac)),array(""),0);
    echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);    
}
/* patch for saMyogAntalopa of maghavan */
if (sub(array("maGavant",),blank(0),blank(0),0) && in_array($so,array("su!")) && $sambuddhi===0)
{
    $text = two(array("maGavant",),array("+"),array("maGavan",),array("+"),0);
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") :</p>\n";
    echo "<p class = hn >Because of bahulagrahaNa in maghavan, saMyogAntasya lopaH is not asiddha here. :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) :</p>\n";
    echo "<p class = hn >मघवा बहुलं के बहुलग्रहण के कारण, संयोगान्तलोप असिद्ध नहीं है । </p>\n";
    display(0);        
}

/* AdirJiTuDavaH (1.3.5) */
if ((substr($first,0,2) === "Yi" || substr($first,0,2) === "wu" || substr($first,0,2) === "qu") && $pada=== "pratyaya" && !in_array($so,$tiG))
{
    if(substr($first,0,2) === "Yi") { $itprakriti = array_merge($itprakriti,array("Yi")); }
    if(substr($first,0,2) === "wu") { $itprakriti = array_merge($itprakriti,array("wu")); }
    if(substr($first,0,2) === "qu") { $itprakriti = array_merge($itprakriti,array("qu")); }
    echo "<p class = pa >By AdirJiTuDavaH (".link_sutra("1.3.5").") :</p>\n";
    echo "<p class = pa >आदिर्ञिटुडवः (१.३.५) :</p>\n";
    display(0);
    $text = first(array("Yi","wu","qu"),array("","",""),0); // function first removes and replaces specific strings from the words. For details see function.php.
    echo "<p class = sa >tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);
}
/* cuTU (1.3.7) */
if (arr($text,'/[+][cjYwWqQR]/') && $wa1 === 0 && ($sarva2 ===0 || $purva=1))
{
    it('/([+][cjYwWqQR])/');
    echo "<p class = pa >By cuTU (".link_sutra("1.3.7").") :</p>\n";
    echo "<p class = pa >चुटू (१.३.७) :</p>\n";
    display(0);
    $text = last(array("jas","wA"),array("as","A"),0);
    echo "<p class = sa >tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);
}
/* SaH pratyayasya (1.3.6) */
if (arr($text,'/[+][z]/') && $pada=== "pratyaya")
{
    it('/([+][z])/');
    echo "<p class = pa >By SaH pratyayasya (".link_sutra("1.3.6").") :</p>\n";
    echo "<p class = pa >षः प्रत्ययस्य (१.३.६) :</p>\n";
    display(0);
    $text = two(array("+"),array("z"),array("+"),array(""),0);
    echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);
}
/* GeryaH (7.1.13) */
if (arr($text,'/[a][+][N][e]/') && $pada=== "pratyaya" && $so === "Ne" )
{
    $text = one(array("a+Ne"),array("a+ya"),0);
    $text = two(array("jaras"),array("ya"),array("jaras"),array("Ne"),0);
    echo "<p class = sa >By GeryaH (".link_sutra("7.1.13").") :</p>\n";
    echo "<p class = sa >ङेर्यः (७.१.१३) :</p>\n";
    display(3); 
    $Ne=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else { $Ne = 0; }
if (in_array($fo,array("SrIpA")) && $gender==="n" && $so==="Ne")
{
    echo "<p class = pa >By sannipAtaparibhASA prevents application of 'Ato dhAtoH'.</p>\n";
    echo "<p class = pa >सन्निपातपरिभाषा आतो धातोः का प्रयोग निषेध करती है ।</p>\n";
    display(0);    
}
/* lazakvataddhite (1.3.8) */
if (((arr($text,'/[+][lSkKgGN]/'))||$sarva2===1||$purva===1) && $taddhita === 0  && $sarva === 0)
{
    it('/([+][lSkKgGN])/');
    echo "<p class = pa >By lazakvataddhite (".link_sutra("1.3.8").") :</p>\n";
    echo "<p class = pa >लशक्वतद्धिते (१.३.८) :</p>\n";
    display(0);
    $text = two(array("+"),array("Sas","Ni","SI","Nas","Ne","Si","kvin","Sap"),array("+"),array("as","i","I","as","e","i","vin","ap"),0);
    echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);
}
/* na vibhaktau tusmAH (1.3.4) */
//if (arr($text,'/[tTdDnsm]$/') && $pada=== "pratyaya" && ( in_array($so,$navibhaktau) || sub(array("+"),$navibhaktau,blank(0),0) ) && $wa === 0 && $wa1 === 0)
if (arr($text,'/[tTdDnsm]$/') && $vibhakti===1)
{
    echo "<p class = pa >By na vibhaktau tusmAH (".link_sutra("1.3.4").")  :</p>\n";
    echo "<p class = pa >न विभक्तौ तुस्माः (१.३.४) :</p>\n";
    display(0);
    $tusma=1;
}
/* halantyam (1.3.3) ant tasya lopaH (1.3.9) */
elseif (arr($text,'/['.flat($hl).']$/') && $halGyAbbhyo!==1)
{
    itprat('/(['.flat($hl).']$)/');
    echo "<p class = pa >By halantyam (".link_sutra("1.3.3").") 3:</p>\n";
    echo "<p class = pa >हलन्त्यम्‌ (१.३.३) :</p>\n";
    display(0);
    $text = last(prat('hl'),blank(count(prat('hl'))),0);
    echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
}
/* halantyam (1.3.3) and tasya lopaH */
if (sub(array("+"),$inbetweenpratyaya,array("+"),0))
{
    itprat('/['.flat($hl).'][+]/');
    echo "<p class = pa >By halantyam (".link_sutra("1.3.3").") 4:</p>\n";
    echo "<p class = pa >हलन्त्यम्‌ (१.३.३) :</p>\n";
    display(0);
    $text=two(prat('hl'),array("+"),blank(count(prat('hl'))),array("+"),0);
    echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0); 
}
/* it additions in case of kvin and kvip */
if ($kvin===1)
{
    $itprakriti=array_merge($itprakriti,array("k","n",)); // still ambiguous whether these should be added to pratyayas only or sometimes to prakRtis also.
    $it=array_merge($it,array("k","n",));
}
if ($kvip===1)
{
    $itprakriti=array_merge($itprakriti,array("k","p"));    
    $it=array_merge($it,array("k","p"));    
}
/* nAJceH pUjAyAm (6.4.30) */ 
//if ( !itcheck(array("i"),1) && arr($text,'/[n]['.pc('hl').'][+]/') && ( itcheck(array("k","G"),2)|| $ancu===1 )) // blocked because gives wrong result in 'yuj'->'yunj'. 
if ( !itcheck(array("i"),1) && arr($text,'/[n][c][+]/') && $nance===1 ) // for function itcheck please see function.php.
{
    echo "<p class = sa >nAJceH pUjAyAm (".link_sutra("6.4.30").") :</p>\n";
    echo "<p class = sa >नाञ्चेः पूजायाम्‌ (६.४.३०) :</p>\n";
    display(0); 
}
/* kruJca */ 
if ( sub(array("kruYc","krunc"),array("+"),blank(0),0) )
{
    echo "<p class = pa >'RtvigdadhRksragdigaJcuyujikruJcAM ca' prohibits application of nalopa.</p>\n";
    echo "<p class = pa >'ऋत्विग्दधृक्स्रग्दिगञ्चुयुजिक्रुञ्चां च' इत्यनेन नलोपाभावोऽपि निपात्यते ।</p>\n";
    display(0); $kruJca=1;
} else { $kruJca = 0; }
/* defining dhatus with 'i' as it */
if (sub(array("hiMs"),array("+"),blank(0),0))
{
    $it=array_merge($it,array("i"));
    $itprakriti=array_merge($it,array("i"));
}
/* aniditAM hala upadhAyAH kGiti (6.4.24) */ 
if ( !itcheck(array("i"),1) && arr($text,'/[nM]['.pc('hl').'][+]/') && ( itcheck(array("k","N"),1)|| $ancu===1 ) && !($kruJca===1 && sub(array("krunc","kruYc"),blank(0),blank(0),0) ) && $nance===0 )
{
    $text = three(array("n","M"),$hl,array("+"),array("",""),$hl,array("+"),0);        
    echo "<p class = sa >aniditAM hala upadhAyAH kGiti (".link_sutra("6.4.24").") :</p>\n";
    echo "<p class = sa >अनिदितां हल उपधायाः क्ङिति (६.४.२४) :</p>\n";
    display(0); 
    $aniditAm = 1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
}
/* samaH sami (6.3.93) */ 
if (preg_match('/[s][a][m][a][n][c]/',$fo) && sub(array("anc","Anc"),array("+"),blank(0),0) && $ancu===1)
{
    $text = two(array("samac"),array("+"),array("samiac"),array("+"),0);
    echo "<p class = sa >samaH sami (".link_sutra("6.3.93").") :</p>\n";
    echo "<p class = sa >समः समि (६.३.९३) :</p>\n";
    display(0);
}
/* sahasya sadhriH (6.3.95) */ 
if (preg_match('/[s][a][h][a][a][n][c]/',$fo) && sub(array("anc","Anc"),array("+"),blank(0),0) && $ancu===1)
{
    $text = two(array("sahaac"),array("+"),array("saDriac"),array("+"),0);
    echo "<p class = sa >sahasya sadhriH (".link_sutra("6.3.95").") :</p>\n";
    echo "<p class = sa >सहस्य सध्रिः (६.३.९५) :</p>\n";
    display(0);
}
/* tirasastiryalope (6.3.94) */
if (preg_match('/[t][i][r][a][s][a][n][c]/',$fo) && sub(array("anc","Anc","ac","Ac"),array("+"),blank(0),0) && $bham===0 && $ancu===1)
{
    $text = two(array("tirasac","tirasanc"),array("+"),array("tiriac","tirianc"),array("+"),0);
    echo "<p class = sa >tirasastiryalope (".link_sutra("6.3.94").") :</p>\n";
    echo "<p class = sa >तिरसस्तिर्यलोपे (६.३.९४) :</p>\n";
    display(0);
}
/* atvasantasya cAdhAtoH (6.4.14) */
if ($atu==1 && $dhatu===1 )
{
    echo "<p class = pa >cAdhAtoH means that atvasantasya cAdhAtoH (".link_sutra("6.4.14").") will not apply. :</p>\n";
    echo "<p class = pa >चाधातोः से अत्वसन्तस्य चाधातोः (६.४.१४) का प्रयोग बाधित होता है । :</p>\n";
    display(0);
}
elseif ( ( $atu===1  )  && $so==="su!" && $sambuddhi===0 )
{
    $text = two($ac,array("t+"),$acdir,array("t+"),0);
   $text = one(array("as+"),array("As+"),0);
    echo "<p class = sa >atvasantasya cAdhAtoH (".link_sutra("6.4.14").") :</p>\n";
    echo "<p class = sa >अत्वसन्तस्य चाधातोः (६.४.१४) :</p>\n";
    display(3);
}
/* ugidacAM sarvanAmasthAne'dhAtoH (7.1.70) */ 
if (sub(array("BavantI","BavatI"),array("+"),blank(0),0))
{
    $sarvanamasthana1 = 0;
}
if ($atu===1 && !itcheck(array("u"),1) && $sarvanamasthana1===1 )
    {
    echo "<p class = pa >Because the word is not ugit; ugidacAM doesn't apply. </p>\n";
    echo "<p class = pa >उगितत्त्वाभावान्न नुम्‌ ।</p>\n";      
    display(0);
    }
if ($sarvanamasthana1===1 && ( ($ancu===0 && $dhatu===1)  ) && $kruJca===0 && arr(array($fo),'/[a][t]$/'))
    {
    echo "<p class = pa >'ac' is for restricting the application of 'ugidacAm...' to aYcu if the word is a dhAtu. </p>\n";
    echo "<p class = pa >धातोश्चेदुगित्कार्यं तर्ह्यञ्चतेरेव ।</p>\n";      
    display(0);
    }
//if ($sarvanamasthana1===1 && $nAbhyasta===0 &&  (( $dhatu===0 && arr($text,'/[uUfFxX][!][+]/') ) ||  ($ancu===1 && $dhatu===1) ||  ( ($kvip===1 || $kvin===1)&& $dhatu===1 && arr(array($fo),'/[a][t]$/')) || $bhavat===1 ) && $kruJca===0 && $vAnapuMsaka===0)
if ($sarvanamasthana1===1 && !in_array(1,$num) && (( $dhatu===0 && itcheck(array("u","U","f","F","x","X"),1))  ||  ($ancu===1 && $kvin===1) ||  ( ($kvip===1 || $kvin===1)&& $dhatu===1 && arr(array($fo),'/[a][t]$/')) || $bhavat===1 ) && $aniditAm===0 && $GIS!==1 && $GIn!==1 && $GIp!==1) // added aniditAM for prAcI.
{
    $text = one(array("ac+","Ac+"),array("anc+","Anc+"),0);
    $text = three(blank(1),$hl,array("u!+","U!+","f!+","F!+","x!+"),array("n"),$hl,array("u!+","U!+","f!+","F!+","x!+"),0); $num=array_merge($num,array(1));
    $text = mit('/['.pc('hl').']/',"n",0);
     $text = one(array("annc"),array("anc"),0);
    $text = one(array("+In+"),array("+I+"),0);
    echo "<p class = sa >By ugidacAM sarvanAmasthAne'dhAtoH (".link_sutra("7.1.70").") :</p>\n";
    echo "<p class = sa >उगिदचां सर्वनामस्थानेऽधातोः (७.१.७०) :</p>\n";
    if ( ($kvip===1 || $kvin===1)&& $dhatu===1)
    {
    echo "<p class = hn >'adhAtoH' extends the application of this rule to the words which had adhAtu in the first place.</p>\n";
    echo "<p class = hn >'अधातोः' इति त्वधातुभूतपूर्वस्यापि नुमर्थम्‌ ।</p>\n";                
    }
    if ($nance===1)
    {
        $text = one(array("goannc"),array("goanc"),0); // patch to remove duplication of nakAra.
        echo "<p class = hn >As there is no lopa of nakAra, 'ugidacAm..' doesn't apply.</p>\n";
        echo "<p class = hn >अलुप्तनकारत्वात्‌ न नुम्‌ ।</p>\n";
    }
    display(3);      
    $ugidacAm=1;  // 0 - this sUtra has not applied. 1 - this sUtra has applied.
}  else {$ugidacAm=0; }
/* uda It (6.4.139) */ 
if (preg_match('/[u][d][a][n][c]/',$fo) && $aniditAm === 1 && sub(array("ac","Ac"),array("+"),blank(0),0) && ($bham===1 || sub(array("ac","Ac"),array("+"),array("I+"),0)) && $ancu===1)
{
    $text = two(array("ac","Ac"),array("+"),array("Ic","Ic"),array("+"),0);
    echo "<p class = sa >uda It (".link_sutra("6.4.139").") :</p>\n";
    echo "<p class = sa >उद इत्‌ (६.४.१३९) :</p>\n";
    display(0);
}
/* acaH (6.4.138) */ 
if ( preg_match('/[aA][n][c]/',$fo) && $aniditAm === 1 && sub(array("ac","Ac"),array("+"),blank(0),0) && ($bham===1 || sub(array("ac","Ac"),array("+"),array("I+"),0)) && $ancu===1)
{
    if (sub(array("i","I","u","U","f","F","x","X","y","v"),prat('ac'),array("c"),0))
    {
    echo "<p class = pa >Though iko yaNaci is antaraGga than lopa by acaH, its application is barred by 'akRtavyUhAH pANinIyAH (pa 57).</p>\n";
    echo "<p class = pa >इको यणचि से प्राप्त यण्‌ अन्तरङ्ग होने पर भी अकृतव्यूहाः पाणिनीयाः (प ५७) से वह अचः का बाध नहीं करता ।</p>\n";
    display(0);        
    }
    $text = two(array("yac","ac","Ac"),array("+"),array("ic","c","ac"),array("+"),0);
    echo "<p class = sa >acaH (".link_sutra("6.4.138").") :</p>\n";
    echo "<p class = sa >अचः (६.४.१३८) :</p>\n";
    if ($nance===1)
    {
    echo "<p class = hn >As there is no lopa of nakAra in nAJceH pUjAyAm, there is not akAralopa.</p>\n";
    echo "<p class = hn >नलोपाभावादकारलोपो न ।</p>\n";        
    }
    display(3); 
    $acaH=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else { $acaH=0; }
/* cau (6.3.138) */ 
if ( $acaH===1)
{
    $text = three($ac,array("c","c"),array("+"),$acdir,array("c","c"),array("+"),0);
    echo "<p class = sa >cau (".link_sutra("6.3.138").") :</p>\n";
    echo "<p class = sa >चौ (६.३.१३८) :</p>\n";
    display(3);
}
/* ambArthanadyorhrasvaH (7.3.103) */
if (sub(array("ambAqA","ambAlA","ambikA"),array("+"),blank(0),0) && $sambuddhi===1 && $so==="su!")
{
    echo "<p class = pa >asaMyuktA ye DalakAstadvatAM hrasvo na (vA 4592) :</p>\n";
    echo "<p class = pa >असंयुक्ता ये डलकास्तद्वतां ह्रस्वो न (वा ४५९२) :</p>\n";
    display(0);
}
if ($sambuddhi===1 &&  ($nadi!==0 || ends(array($fo),array("ambA","akkA","allA"),1)) && $so==="su!")
{
    if ($nadi===1 || ends(array($fo),array("ambA","akkA","allA"),1))
    {
    $text = two(array("A","I","U"),array("+"),array("a","i","u"),array("+"),0);        
    }
    else
    {
    $text = two(array("A","I","U"),array("+"),array("a","i","u"),array("+"),1);        
    }
    echo "<p class = sa >By ambArthanadyorhrasvaH (".link_sutra("7.3.103").") :</p>\n";
    echo "<p class = sa >अम्बार्थनद्योर्ह्रस्वः :</p>\n";
    display(3); 
    $amba = 1; // 0 - ambArthanadyorhrasva has not applied. 1 - ambArthanadyorhrasva has applied.
} else { $amba = 0; }
/* shatR */
if ($shatR===1)
{
    $it=array_merge($it,array("f"));
    $itprakriti=array_merge($itprakriti,array("f"));
}
/* nAbhyAsAcChatuH (7.1.78) */
if ($abhyasta===1 && $shatR===1 && itcheck(array("f"),1) && $gender!=="n")
{
    echo "<p class = sa >By nAbhyastAcChatuH (".link_sutra("7.1.78").") :</p>\n";
    echo "<p class = sa >नाभ्यास्ताच्छतुः (".link_sutra("7.1.78").") :</p>\n";
    display(0); 
    $nAbhyasta=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else { $nAbhyasta=0; }
/* vA napuMsakasya (7.1.79) */  
if ($abhyasta===1 && $shatR===1 && itcheck(array("f"),1) && $gender==="n")
{
    $text = mit('/['.pc('hl').'][+]/','n',1); $num=array_merge($num,array(1));
    $text = one(array("annc"),array("anc"),0);
    echo "<p class = sa >By vA napuMsakasya (".link_sutra("7.1.79").") :</p>\n";
    echo "<p class = sa >वा नपुंसकस्य (७.१.७९) :</p>\n";
    display(0); 
    $vAnapuMsaka=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else { $vAnapuMsaka=0; }
/* special message for ikAra of kvip, kvin etc. */
if (arr($text,'/[+][v][i]$/')&& in_array($so,array("kvip","kvin")) && $taddhita === 0  && $sarva === 0 )
{
    $text = last(array("vi"),array("v"),0);
    echo "<p class = sa >ikAra after 'v' in kvip, kvin etc are for fecility of pronounciation only.</p>\n";
    echo "<p class = sa >क्विप्‌, क्विन्‌ इत्यादि में वकार के बाद का इकार उच्चारणार्थ ही होता है ।</p>\n";
    display(0);
}
/* verapRktasya (3.1.67) */
if (arr($text,'/[+][v]$/')&& in_array($so,array("kvip","kvin")) && $taddhita === 0  && $sarva === 0 )
{
    $text = last(array("v"),array(""),0);
    echo "<p class = sa >verapRktasya (".link_sutra("3.1.67").") :</p>\n";
    echo "<p class = sa >वेरपृक्तस्य (३.१.६७) :</p>\n";
    display(0);
}
/* GasiGasozca (6.1.110) */
if (arr($text,'/[eo][+]/') && in_array($so,array("Nasi!","Nas")))
{
    $text = two(array("e+","o+"),array("a"),array("e+","o+"),array(""),0);
    echo "<p class = sa >By GasiGasozca (".link_sutra("6.1.110").") :</p>\n";
    echo "<p class = sa >ङसिङसोश्च (६.१.११०) :</p>\n";
    display(0);
}
/* hrasvanadyApo nuT (7.1.54) */
if ( $so === "Am" && $numacira===0 && arr($text,'/[aiufx][+][A][m]/') )
{
    $text = two($hrasva,array("+Am"),$hrasva,array("+nAm"),0);
    echo "<p class = sa >By hrasvanadyApo nuT (".link_sutra("7.1.54").") :</p>\n";
    echo "<p class = sa >ह्रस्वनद्यापो नुट्‌ (७.१.५४) :</p>\n";
    display(3);
} 
if ( $so === "Am" && $numacira===0 && $nadi!==0 )//&& arr($text,'/[IU][+][A][m]/') )
{
    if ($nadi===2)
    {
    $text = two(array("I","U"),array("+Am"),array("I","U"),array("+nAm"),1);        
    }
    else
    {
    $text = two(array("I","U"),array("+Am"),array("I","U"),array("+nAm"),0);        
    }
    echo "<p class = sa >By hrasvanadyApo nuT (".link_sutra("7.1.54").") :</p>\n";
    echo "<p class = sa >ह्रस्वनद्यापो नुट्‌ (७.१.५४) :</p>\n";
    display(3);
}
if ( $so === "Am" && $numacira===0 && $Ap===1 && arr($text,'/[A][+][A][m]/'))
{
    $text = two(array("A"),array("+Am"),array("A"),array("+nAm"),0);
    echo "<p class = sa >By hrasvanadyApo nuT (".link_sutra("7.1.54").") :</p>\n";
    echo "<p class = sa >ह्रस्वनद्यापो नुट्‌ (७.१.५४) :</p>\n";
    display(3);
}
/* bahUrji exception to napuMsakasya jhalacaH (7.1.72) */ 
if ( $gender==="n" && $sarvanamasthana1===1 && sub(array("bahUrj"),blank(0),blank(0),0) && arr($text,'/['.pc('Jl').'][+]/') && $amipUrva === 0)
{
    $text = two(array("bahUrj"),array("+"),array("bahUnrj"),array("+"),1);
    echo "<p class = sa >By bahUrji numpratiSedhaH (vA 4331) and antyAtpUrvo vA num (vA 4332) :</p>\n";
    echo "<p class = sa >बहूर्जि नुम्प्रतिषेधः (वा ४३३१) तथा अन्त्यात्पूर्वो वा नुम् (वा ४३३२) :</p>\n";
    display(3); 
    $bahurj=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else { $bahurj=0; }
/* beBid, cecCid exception to napuMsakasya jhalacaH (7.1.72) */ 
if ( $gender==="n" && $sarvanamasthana1===1 && $kvip===1 && sub(prat('Jl'),array("+"),blank(0),0) && in_array($so,array("jas","Sas")) && arr($text,'/['.pc('Jl').'][+]/') && $amipUrva === 0)
{
    echo "<p class = pa >allopa behaves like sthAnivad, therefore num (because of jhalantatva) doesn't happen. sthAnivadbhAva doesn't happen in svavidhi. Therefore num (because of ajantatva) doesn't happen. </p>\n";
    echo "<p class = pa >शावल्लोपस्य स्थानिवत्त्वादझलन्तत्वान्न नुम्‌ । अजन्तलक्षणस्तु नुम्‌ न । स्वविधौ स्थानिवत्त्वाभावात्‌ । </p>\n";
    display(0); $bebhid=1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else { $bebhid=0; } 
/* svap patch */
if ( $gender==="n" && $sarvanamasthana1===1 && arr($text,'/[A][n][p][+]/') && arr(array($fo),'/[a][p]$/') && $amipUrva === 0 && $nAbhyasta===0)
{
    $text = two(array("Anp"),array("+"),array("anp"),array("+"),1);
    echo "<p class = sa >'niravakAzatvaM pratipadoktatvam' mandates non application of dIrghatva here.  :</p>\n";
    echo "<p class = sa >'निरवकाशं प्रतिपदोक्तत्वम्‌' इति पक्षे तु प्रकृते तद्विरहात्‌ नुमेव । :</p>\n";
    display(3);
} 
/* napuMsakasya jhalacaH (7.1.72) */ 
// right now supuMs is giving ugidacAM and not napuMsakasya jhalacaH. Pending to correct.
if ( $gender==="n" && $sarvanamasthana1===1 && arr($text,'/['.pc('ac').'][+]/') && $amipUrva === 0 && $nAbhyasta===0)
{
    $text = mit('/['.pc('ac').'][+]/','n',0);
    echo "<p class = sa >By napuMsakasya jhalacaH (".link_sutra("7.1.72").") :</p>\n";
    echo "<p class = sa >नपुंसकस्य झलचः (७.१.७२) :</p>\n";
    display(3);
}
if ( $gender==="n" && $sarvanamasthana1===1 && arr($text,'/['.pc('Jl').'][+]/') && $amipUrva === 0 && $bahurj===0 && $bebhid===0 && $ugidacAm===0 && $nAbhyasta===0 && $vAnapuMsaka===0)
{
    $text = mit('/['.pc('Jl').'][+]/','n',0);
    echo "<p class = sa >By napuMsakasya jhalacaH (".link_sutra("7.1.72").") :</p>\n";
    echo "<p class = sa >नपुंसकस्य झलचः (७.१.७२) :</p>\n";
    display(3);
}
/* sarvanAmasthAne cAsambuddhau (6.4.8) */
$acdir = array("A","A","I","I","U","U","F","F","F","F","e","o","E","O",);
if (arr($text,'/['.flat($ac).'][n][+]/') && !arr($text,'/['.flat($ac).'][n][+]$/') && !in_array(1,$num) && !sub(array("Ahan"),blank(0),blank(0),0) && $sarvanamasthana1===1 && $sambuddhi===0 && $inhan===0 && $inhan1===0 && $aptRn===0 && $casambu!==1)
{
    $text = two($ac,array("n+"),$acdir,array("n+"),0);
    echo "<p class = sa >By sarvanAmasthAne cAsambuddhau (".link_sutra("6.4.8").") :</p>\n";
    echo "<p class = sa >सर्वनामस्थाने चासम्बुद्धौ (६.४.८) :</p>\n";
    echo "<p class = pa >alo'ntyAtpUrva upadhA (".link_sutra("1.1.65").") </p>\n";
    echo "<p class = pa >अलोऽन्त्यात्पूर्व उपधा (१.१.६५) </p>\n";   
    display(3);
}
/* dRnkarapunaHpUrvasya bhuvo yaN vaktavyaH (vA 4118) */ 
if ($dhatu===1 && in_array($fo,array("dfnBU","karaBU","kAraBU","punarBU"))  && in_array($so,$acsup))
{
    $text = three(array("dfnBU","punarBU"),array("+"),$ac,array("dfnBv","punarBv"),array("+"),$ac,0);
    $text = three(array("karaBU","kAraBU"),array("+"),$ac,array("karaBv","kAraBv",),array("+"),$ac,1);
    echo "<p class = sa >By dRnkarapunaHpUrvasya bhuvo yaN vaktavyaH (vA 4118) :</p>\n";
    if (in_array($fo,array("karaBU","kAraBU")))
    {
    $text=one(array("karaBv+e","karaBU+A+e","karaBv+as","karaBU+A+as","karaBv+i","karaBU+Am","kAraBv+e","kAraBU+A+e","kAraBv+as","kAraBU+A+as","kAraBv+i","kAraBU+Am"),array("karaBU+e","karaBv+A+e","karaBU+as","karaBv+A+as","karaBU+i","karaBv+Am","kAraBU+e","kAraBv+A+e","kAraBU+as","kAraBv+A+as","kAraBU+i","kAraBv+Am",),0);
    echo "<p class = hn >There is pAThabheda here. Some hold that there is dIrgha kAra word here. SK has adopted both the readings, therefore we have kept them optional.</p>\n";        
    }
    echo "<p class = sa >दृन्करपुनःपूर्वस्य भुवो यण्‌ वक्तव्यः (वा ४११८) :</p>\n";
    if (in_array($fo,array("karaBU","kAraBU")))
    {
    echo "<p class = hn >दीर्घपाठे करपूर्वस्य उवङेव । ह्रस्वपाठे करपूर्वस्य यणेव इति विवेकः ।</p>\n";                
    }
    display(0); 
}   
/* varSAbhvazca (6.8.84) */ 
if ($dhatu===1 && $first==="varzABU" && in_array($so,$acsup))
{
    $text = two(array("varzABU"),array("+"),array("varzABv"),array("+"),0);
    echo "<p class = sa >By varSAbhvazca (".link_sutra("6.8.84").") :</p>\n";
    echo "<p class = sa >वर्षाभ्वश्च (६.४.८४) :</p>\n";
    display(0);
}

if ($fo==="dfmBU")
{
    $dfmBU=1; // 0 - the word is not dRmbhU. 1 - the word is dRmbhU.
} else { $dfmBU=0; }
/* na bhUsudhiyoH (6.4.85) */
if (in_array($fo,array("suDI","praDI")) && $gender==="n")
{
    $dhatu=1;
}
if (in_array($fo,array("praDI")) && $gender==="n")
{
   $eranekaca=1;
}
if ( $dhatu===1 && (arr($text,'/[B][U][+]/')||$fo==="suDI") && arr($text,'/[iuIU][+]['.flat($ac).']/') && in_array($so,$sup) && $dfmBU===0)
{
    echo "<p class = sa >By na bhUsudhiyoH (".link_sutra("6.4.85").") :</p>\n";
    echo "<p class = sa >न भूसुधियोः (६.४.८५) :</p>\n";
    display(3);
    $nabhusu = 1; // 0 - the word neither ends with bhU, nor is it sudhI. 1 - The word ends with bhU or is sudhI.
} else { $nabhusu = 0; }
/* kvau luptaM na sthAnivat (vA 431) */
// Not displayed because it is difficult to teach sthnanivadbhav to machine now. Will come back to it if I can teach it some day.
/* aci znudhAtubhruvAM yvoriyaGuvaGau (6.4.77) */
// znu pending. 
if (($dhatu===1||$fo==="BrU") && arr($text,'/[iuIU][+]['.flat($ac).']/') && $pada==="pratyaya" && ($eranekaca===0 || ($eranekaca===1 && anekAca($fo)===false ) || (arr($text,'/[B][U][+]/')||$fo==="suDI") ) && $dfmBU===0 && !sub(array("+"),array("I"),array("+"),0) )
{
    $text = three(array("i","I","u","U"),array("+"),$ac,array("iy","iy","uv","uv"),array("+"),$ac,0);
    echo "<p class = sa >By aci znudhAtubhruvAM yvoriyaGuvaGau (".link_sutra("6.4.77").") :</p>\n";
    echo "<p class = hn >gatikAraketarapUrvapadasya yaN neSyate (vA 5034) mandates that eranekAco.../oH supi rule doesn't apply in cases where the pUrvapada is neither gati nor kAraka. iyaG or uvaG is applied in that case. :</p>\n";
    echo "<p class = sa >अचि श्नुधातुभ्रुवां य्वोरियङुवङौ (६.४.७७) :</p>\n";
    echo "<p class = hn >गतिकारकेतरपूर्वपदस्य यण्‌ नेष्यते (वा ५०३४) से गति / कारक से भिन्न पूर्वपद होने पर एरनेकाचो.../ओः सुपि सूत्र नहीं लागू होता । इयङ्‌ या उवङ्‌ की प्रवृत्ति होती है । :</p>\n";
    display(3);
}
/* gatikAraketarapUrvapadasya yaN neSyate (vA 5034) */
// This is attached with eranekAco... So, trying to put a note and making the iyaG and yaN optional.
/* eranekAco'saMyogapUrvasya (6.4.82) */
if ($dhatu===1 && arr($text,'/[iI][+]['.pc('ac').']/') && in_array($fo,array("unnI")) && $pada==="pratyaya" && anekAca($fo) && $eranekaca===1 && $nabhusu===0)
{
    echo "<p class = pa >As the vizeSaNa 'dhAtunA saMyogasya' mandates that the saMyoga has to belong to dhAtu only for prohibiting 'eranekAco..', the prohibition doesn't apply here.</p>\n";
    echo "<p class = pa >धातुना संयोगस्य विशेषणादिह स्यादेव यण्‌ (एरनेकाचो इत्यनेन सूत्रेण) </p>\n";
    display(0); 
    $unni=1; // 0 - the word is not unnI. 1 - the word is unnI
} else { $unni=0; } 
if ($dhatu===1 && (arr($text,'/['.flat($ac).']['.flat($hl).'][iI][+]['.flat($ac).']/')||$unni===1) && $pada==="pratyaya" && anekAca($fo) && $eranekaca!==0 && $nabhusu===0)
{
    $text = three(array("i","I"),array("+"),$ac,array("y","y"),array("+"),$ac,0);                
    echo "<p class = sa >By eranekAco'saMyogapUrvasya (".link_sutra("6.4.82").") :</p>\n";
    echo "<p class = hn >gatikAraketarapUrvapadasya yaN neSyate (vA 5034) mandates that this rule doesn't apply in cases where the pUrvapada is neither gati nor kAraka. iyaG or uvaG is applied in that case. :</p>\n";
    echo "<p class = sa >एरनेकाचोऽसंयोगपूर्वस्य (६.४.८२) :</p>\n";
    echo "<p class = hn >गतिकारकेतरपूर्वपदस्य यण्‌ नेष्यते (वा ५०३४) से गति / कारक से भिन्न पूर्वपद होने पर यह सूत्र नहीं लागू होता । इयङ्‌ या उवङ्‌ की प्रवृत्ति होती है । :</p>\n";
    display(3);
}
/* oH supi (6.4.83) */ 
if ($dhatu===1 && in_array($fo,array("ullU")) && $pada==="pratyaya" && anekAca($fo) && $$eranekaca===1 && $nabhusu===0 && in_array($so,$sup) && $dfmBU===0)
{
    echo "<p class = pa >As the vizeSaNa 'dhAtunA saMyogasya' mandates that the saMyoga has to belong to dhAtu only for prohibiting 'oH supi', the prohibition doesn't apply here.</p>\n";
    echo "<p class = pa >धातुना संयोगस्य विशेषणादिह स्यादेव यण्‌ (ओः सुपि इत्यनेन सूत्रेण) </p>\n";
    display(0); 
    $ullU=1; // 0 - word is not ullU. 1 - word is ullU.
} else { $ullU=0; }
if ($dhatu===1 && ( arr($text,'/[uU][+]['.flat($ac).']/') || $ullU===1 )&& $pada==="pratyaya" && anekAca($fo) && $eranekaca===1 && $nabhusu===0 && in_array($so,$sup) && $dfmBU===0)
{
    $text = three(array("u","U"),array("+"),$ac,array("v","v"),array("+"),$ac,0);
    echo "<p class = sa >By oH supi (".link_sutra("6.4.83").") :</p>\n";
    echo "<p class = hn >gatikAraketarapUrvapadasya yaN neSyate (vA 5034) mandates that this rule doesn't apply in cases where the pUrvapada is neither gati nor kAraka. iyaG or uvaG is applied in that case. :</p>\n";
    echo "<p class = sa >ओः सुपि (६.४.८३) :</p>\n";
    echo "<p class = hn >गतिकारकेतरपूर्वपदस्य यण्‌ नेष्यते (वा ५०३४) से गति / कारक से भिन्न पूर्वपद होने पर यह सूत्र नहीं लागू होता । इयङ्‌ या उवङ्‌ की प्रवृत्ति होती है । :</p>\n";
    display(3);
}
/* patch to remove application of jhalAM jazo'nte in case wAp in kruJcA and NIp in bhavantI */
if ($gender==="f")
{
    $text = three($hl,array("+A","+I"),array("+"),$hl,array("A","I"),array("+"),0);    
}
/* ami pUrvaH (6.1.107) */
if ( sub(array("a","A","i","I","u","U","f","F","x"),array("+am"),blank(0),0))
{
    $text = two(array("a","A","i","I","u","U","f","F","x"),array("am"),array("a","A","i","I","u","U","f","F","x"),array("m"),0);
    echo "<p class = sa >By ami pUrvaH (".link_sutra("6.1.107").") :</p>\n";
    echo "<p class = sa >अमि पूर्वः (६.१.१०७) :</p>\n"; 
    display(0); 
    $amipUrva = 1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
}   else { $amipUrva = 0; } 
/* sAntamahataH saMyogasya (6.4.10) */
if ( ( (arr($text,'/['.flat($ac).'][nM][s][+]/') && $dhatu===1)|| $fo==="mahat" )&& $sarvanamasthana1===1 && $sambuddhi===0)
{
    echo "<p class = pa >sAntamahataH saMyogasya (".link_sutra("6.4.10").") doesn't apply here. </p>\n";
    echo "<p class = pa >सान्तमहतः संयोगस्य (६.४.१०) इत्यत्र सान्तसंयोगोऽपि प्रातिपदिकस्यैव गृह्यते, न तु धातोः, महच्छब्दसाहचर्यात्‌ ।</p>\n";    
    display(0);
} 
if ( ( (arr($text,'/['.flat($ac).'][nM][s][+]/') && $dhatu===0)|| $fo==="mahat" )&& $sarvanamasthana1===1 && $sambuddhi===0)
{
    $text = two($ac,array("ns+","Ms+"),$acdir,array("ns+","Ms+"),0);
    $text = one(array("mahant"),array("mahAnt"),0);
    echo "<p class = sa >By sAntamahataH saMyogasya (".link_sutra("6.4.10").") :</p>\n";
    echo "<p class = sa >सान्तमहतः संयोगस्य (६.४.१०) :</p>\n";
    echo "<p class = pa >alo'ntyAtpUrva upadhA (".link_sutra("1.1.65").") </p>\n";
    echo "<p class = pa >अलोऽन्त्यात्पूर्व उपधा (१.१.६५) </p>\n";   
    display(0);
}
if (arr($text,'/['.flat($ac).'][nM][s][+]/') && ends(array($fo),array("mahat"),1) && $sarvanamasthana1===1 && $sambuddhi===0)
{
    $text = two($ac,array("ns+"),$acdir,array("ns+"),0);
    echo "<p class = sa >By sAntamahataH saMyogasya (".link_sutra("6.4.10").") :</p>\n";
    echo "<p class = sa >सान्तमहतः संयोगस्य (६.४.१०) :</p>\n";
    echo "<p class = pa >alo'ntyAtpUrva upadhA (".link_sutra("1.1.65").") </p>\n";
    echo "<p class = pa >अलोऽन्त्यात्पूर्व उपधा (१.१.६५) </p>\n";   
    display(0);
}
if ( ends(array($fo),array("Danuz"),1) && $sarvanamasthana1===1 && $sambuddhi===0)
{
    $text = two($ac,array("nz+"),$acdir,array("nz+"),0);
    echo "<p class = sa >By sAntamahataH saMyogasya (".link_sutra("6.4.10").") :</p>\n";
    echo "<p class = sa >सान्तमहतः संयोगस्य (६.४.१०) :</p>\n";
    echo "<p class = pa >Because Satva is asiddha. </p>\n";
    echo "<p class = pa >षत्व के असिद्ध होने के कारण । </p>\n";   
    display(0);
}

/* Rta ut (6.1.111) */
if ( (arr($text,'/[fx][+][a]/')) && in_array($so,array("Nasi!","Nas")) && $pada==="pratyaya")
{
    $text = two(array("f","x"),array("+a"),array("ur","ul"),array("+"),0);
    echo "<p class = sa >By Rta ut (".link_sutra("6.1.111").") :</p>\n";
    echo "<p class = sa >ऋत उत्‌ (६.१.१११) :</p>\n";
    display(0);
    $text = one(array("r+s"),array("r"),0);
    echo "<p class = sa >By rAtsasya (".link_sutra("8.2.24").") :</p>\n"; 
    echo "<p class = sa >रात्सस्य (८.२.२४) :</p>\n";
    display(0); 
}
/* auto'mzasoH (6.1.93) */
if (sub(array("o"),array("+"),array("a"),0) && in_array($so,array("am","Sas")))
{  
    $text = two(array("o"),array("+a"),array(""),array("+A"),0);
    echo "<p class = sa >By auto'mzasoH (".link_sutra("6.1.93").") :</p>\n";
    echo "<p class = sa >औतोऽम्शसोः (६.१.९३) :</p>\n";
    display(0);
}
/* ato bhisa ais (7.1.9) */
if (arr($text,'/[a][+]/') && $so === "Bis" && $nedamadas===0 && $fo!=="adas" )
{
    $text = two(array("a"),array("Bis"),array("a"),array("Es"),0);
    echo "<p class = sa >By ato bhisa ais (".link_sutra("7.1.9").") :</p>\n";
    echo "<p class = sa >अतो भिस ऐस्‌ (७.१.९) :</p>\n";
    display(5); 
    $atobhis = 1; // 0 - this sUtra has not applied. 1 - this sUtra has applied.
} else { $atobhis = 0; }
/* yasyeti ca (6.4.148) */
if (arr($text,'/[aI][+][I]/') && $bham===1 && ($auGazyA===0))
{
    $text = two(array("a","I"),array("I"),array("",""),array("I"),0);
    echo "<p class = sa >By yasyeti ca (".link_sutra("6.4.148").") :</p>\n";
    echo "<p class = sa >यस्येति च (६.१.१४८) :</p>\n";
    display(3); 
}

/* do-while loop for sapAdasaptAdhyAyI */
// Most of alterations in prakRti and pratyayas are over before this stage. Now in next step we keep all rules of sapAdasaptAdhyAyI, and create a do-while loop.
// This do - while loop is necessary, because there is no order in which rules of sapAdasaptAdhyAyI will apply. This do - while loop will continue till the $text which is input is the same as output. 
// Therefore the cause for application may arise after application of any rule. So created a do-while loop which will check till the input and output are the same i.e. there is no difference after the application of all the sUtras.
// This will ensure that there is no rule of sapAdasaptAdhyAyI which would have applied, but didn't apply.

$start = 1; // remembering that this is the first round of application. After one loop, it will become 2. We have to stop application of some sUtras twice. There this is useful.
do  // starting the loop.
{
$original = $text ; // remembering the member entering the loop. We will have to compare it with the output.

/* osi ca (7.3.104) */
if ($so === "os" && arr($text,'/[a][+]/') && $start ===1)
{
    $text = two(array("a"),array($second),array("e"),array($second),0);
    echo "<p class = sa >By osi ca (".link_sutra("7.3.104").") :</p>\n";
    echo "<p class = sa >ओसि च (७.३.१०४) :</p>\n";
    display(3);
}
/* na tisR.4.4) */
if ($so === "Am" && sub(array("tisf","catasf"),array("+"),blank(0),0))
{
    echo "<p class = sa >By na tisRcatasR (".link_sutra("6.4.4").") :</p>\n";
    echo "<p class = hn >This prevents application of 'nAmi'.</p>\n";
    echo "<p class = sa >न तिसृचतसृ (६.४.४) :</p>\n";
    echo "<p class = hn >यह सूत्र नामि की प्रवृत्ति को निषिद्ध करता है ।</p>\n";
    display(3); 
    $natisf=1; // 0 - no prevention of nAmi. 1 - prevention of nAmi.
} else { $natisf = 0; }
/* explanation of prarINAm */
if (sub(array("rI"),array("+"),array("nAm"),0) && arr(array($fo),'/[r][E]$/'))
{
    echo "<p class = hn >According to mAdhava, rAyo hali applies here to convert it to prarANAm. But it is wrong according to Siddhantakaumudi.</p>\n";
    echo "<p class = hn >माधव के अनुसार यहाँ रायो हलि से आत्त्व होता है । परन्तु सिद्धान्तकौमुदी के अनुसार यह गलत है ।</p>\n";    
    display(0);
}   
/* nAmi (6.4.3) and nR ca (6.4.6) */
if (arr($text,'/['.flat($ac).'][+][n][A][m]$/') && $start ===1 && !in_array($fo,$sarvanama) && $natisf === 0)
{
    if(arr($text,'/[n][f][+][n][A][m]$/'))
    {
    $text = two(array("nf"),array("nAm"),array("nF"),array("nAm"),1);
    echo "<p class = sa >By nR ca (".link_sutra("6.4.6").") :</p>\n";
    echo "<p class = sa >नृ च (६.४.६) :</p>\n";
    display(3);        
    }
    elseif (sub(array("a+"),array("nAm"),blank(0),0))
        {
        echo "<p class = pa >Even though, supi ca (".link_sutra("7.3.102").") is a parasUtra, it doesn't operate here. The causes are: (1) violation of sannipAtaparibhASA and (2) ArambhasAmarthya of 'nAmi'. :</p>\n";
        echo "<p class = pa >यद्यपि सुपि च (७.३.१०२) परसूत्र है, फिर भी उसकी प्रवृत्ति यहाँ नहीं होती है । सन्निपातपरिभाषा के विरोध से और नामि च सूत्र के प्रारम्भ के सामर्थ्य से । </p>\n";
        display(0);
        echo "<p class = sa >By nAmi (".link_sutra("6.4.3").") :</p>\n";
        echo "<p class = sa >नामि (६.४.३) :</p>\n";        
        $text = two($ac,array("nAm"),array("A","A","I","I","U","U","F","F","F","F","e","E","o","O"),array("nAm"),0);
        }
    else
        {
        echo "<p class = sa >By nAmi (".link_sutra("6.4.3").") :</p>\n";
        echo "<p class = sa >नामि (६.४.३) :</p>\n";        
        $text = two($ac,array("nAm"),array("A","A","I","I","U","U","F","F","F","F","e","E","o","O"),array("nAm"),0);
        }
    display(3);        
 $nami = 1;  // 0 - doesn't prevent application of supi ca. 1 - prevents application of supi ca.
} 
else 
    {
    $nami = 0; 
    }
/* bahuvacane jhalyet (7.3.103) */
if ((in_array($so,array("Byas","sup","Bis")) || ($sut===1 && $sAmaAkam===0)) && arr($text,'/[a][+][Bs]/') && $start ===1)
{
    $text = two(array("a"),array("Byas","su","sAm","Bis"),array("e"),array("Byas","su","sAm","Bis"),0);
    echo "<p class = sa >By bahuvacane jhalyet (".link_sutra("7.3.103").") :</p>\n";
    if (in_array($so,array("Byas","Bis")) && arr($text,'/[e][+][B]/'))
    {
    echo "<p class = hn >This rule 'overrides supi ca'.</p>\n";        
    }
    echo "<p class = sa >बहुवचने झल्येत्‌ (७.३.१०३) :</p>\n";
    if (in_array($so,array("Byas","Bis")) && arr($text,'/[e][+][B]/'))
    {
    echo "<p class = hn >'सुपि च' इति दीर्घे प्राप्ते परत्वादस्य सूत्रस्य प्रवृत्तिः ।</p>\n";        
    }
    display(3); 
    $bahuvacane = 1; // 0 - doesn't prevent application of supi ca. 1 - prevents application of supi ca.
} else { $bahuvacane = 0; }
/* supi ca (7.3.102) */
if (in_array($so,$sup) && arr($text,'/[a][+]['.pc('yY').']/') && $amipUrva===0 && $start === 1 && $bahuvacane === 0 && $nami === 0 && $Ne!==1)
{
    if (arr($text,'/[a][+]['.pc('yY').']/') && in_array($fo,array("idam","idakam")) )
    {
    echo "<p class = pa >Adyantavadekasmin (".link_sutra("1.1.21").") :</p>\n";
    echo "<p class = pa >आद्यन्तवदेकस्मिन्‌ (१.१.२१) :</p>\n";
    display(0);
    }
    $text = two(array("a"),array("+"),array("A"),array("+"),0);
    echo "<p class = sa >By supi ca (".link_sutra("7.3.102").") :</p>\n";
    echo "<p class = sa >सुपि च (७.३.१०२) :</p>\n";
    display(3);
}
if ($Ne===1 && $start === 1)
{
    $text = one(array("a+ya"),array("A+ya"),0);
    echo "<p class = sa >By supi ca (".link_sutra("7.3.102").") :</p>\n";
    echo "<p class = hn >'sannipAtalakSaNo vidhiranimittaM tadvighAtasya (pa 86) doesn't apply here. Its anityatva has been shown by kaSTAya kramaNe. </p>\n";
    echo "<p class = sa >सुपि च (७.३.१०२) :</p>\n";
    echo "<p class = hn >'सन्निपातलक्षणो विधिरनिमित्तं तद्विघातस्य (प ८६) यहाँ लागू नहीं होता है । कष्टाय क्रमणे से उसका अनित्यत्व ज्ञापित होता है ।</p>\n";
    display(3);
}
/* pragRhya section */
/* plutapragRhyA aci nityam (6.1.125) */
// There is no definition of pluta / pragRhya here. So we will code that as and when case arises.
/* iko'savarNe zAkalyasya hrasvazca (6.1.127) */ // Right now coded for only dIrgha. Clarify wheter the hrasva preceding also included?
$ik = array("i","I","u","U","f","F","x","X");
$nonik = array("a","A","e","E","o","O");
if (sub($ik,array("+"),$nonik,0) && $pada==="pada" && !in_array($so,$tiG) )
{
// for pragRhya, it is difficult to tell the machine that it is not to be combined. So we have added one additional space. e.g. "a" -> "a ".
$text = two(array("i+","I+"),array("a","A","u","U","f","F","x","X","e","o","E","O"),array("i +","i +"),array("a","A","u","U","f","F","x","X","e","o","E","O"),1);
$text = two(array("u+","U+"),array("a","A","i","I","f","F","x","X","e","o","E","O"),array("u +","u +"),array("a","A","i","I","f","F","x","X","e","o","E","O"),1);
$text = two(array("f+","F+"),array("a","A","u","U","i","I","e","o","E","O"),array("f +","f +"),array("a","A","u","U","i","I","e","o","E","O"),1);
$text = two(array("x+","X+"),array("a","A","u","U","i","I","e","o","E","O"),array("x +","x +"),array("a","A","u","U","i","I","e","o","E","O"),1);
echo "<p class = sa >By iko'savarNe zAkalyasya hrasvazca (".link_sutra("6.1.127").") :</p>
    <p class = hn >Note that this will not apply in samAsa. Also when followed by a 'sit' pratyaya, it will not apply. e.g. pArzva</p>\n";
echo "<p class = sa >इकोऽसवर्णे शाकल्यस्य ह्रस्वश्च (६.१.१२७) :</p>
    <p class = hn >समास व सित्‌ प्रत्यय परे होने पर यह लागू नहीं होता । जैसे कि पार्श्व ।</p>\n";
display(0);
}
/* upasargAdRti dhAtau (6.1.11) and vA supyApizaleH (6.1.12) */
$akarantaupasarga = array("pra","apa","ava","upa",);
$changedupasarga = array("prAr","apAr","avAr","upAr");
$changedupasarga1 = array("prar","apar","avar","upar");
$changedupasarga2 = array("prAl","apAl","avAl","upAl");
$changedupasarga3 = array("pral","apal","aval","upal");
// for $verbs_ru and $verbs_changed, please see function.php.
if ((sub($akarantaupasarga,$verbs_ru,blank(0),0) && !sub(array("prafRa"),blank(0),blank(0),0))||sub($akarantaupasarga,array("xkArIy"),blank(0),0))
{
    if (arr($text,'/[I][y]/'))
    {
            $text = two($akarantaupasarga,$verbs_ru,$changedupasarga,$verbs_changed,1);
    }
    else
    {
            $text = two($akarantaupasarga,$verbs_ru,$changedupasarga,$verbs_changed,0);
    }
$text = two($akarantaupasarga,array("xkArIy"),$changedupasarga2,array("kArIy"),1);
$text = two($akarantaupasarga,array("xkArIy"),$changedupasarga3,array("kArIy"),0);
echo "<p class = sa >By upasargAdRti dhAtau (".link_sutra("6.1.11").") and vA supyApizaleH (".link_sutra("6.1.12").") :</p>\n";
echo "<p class = hn >In case akArAnta upasarga is followed by RkArAdi nAmadhAtu, there is optional vRddhi ekAdeza. If there is dIrgha RUkAra at the start of dhAtu or nAmadhAtu, upasargAdRti dhAtau and vA supyApizaleH don't apply. iko yaNaci and uraNraparaH apply.</p>\n";
echo "<p class = sa >उपसर्गादृति धातौ (६.१.११) तथा वा सुप्यापिशलेः (६.१.१२) :</p>\n";
echo "<p class = hn >अकारान्त उपसर्ग से ऋकारादि नामधातु परे होने पर विकल्प से वृद्धि एकादेश होता है । यदि धातु या नामधातु ॠकार से आरंभ होता है, तब उपसर्गादृति धातौ तथा वा सुप्यापिशलेः लागू नहीं होते हैं । अतः इको यणचि व उरण्रपरः ही लागू होते हैं ।</p>\n";
display(0); 
$upas = 1; // 0 - doesn't prevent application of RtyakaH. 1 - prevents application of RtyakaH.
} else { $upas = 0; }
/* RtyakaH (6.1.128) */
$ak = array("a","A","i","I","u","U","f","F","x","X"); 
$akrt = array("a ","A ","i ","I ","u ","U ","f ","F ","x ","X "); 
if (arr($text,'/['.flat($ak).'][+][fx]/') && $start===1 && $pada ==="pada" && $upas ===0 )
{
if (sub($ak,array("f","x"),blank(0),0))
{
$text = two ($ak,array("f","x"),$akrt,array("f","x"),1);
echo "<p class = sa >By RtyakaH (".link_sutra("6.1.128").") :</p>
    <p class = hn >Note: This applies only to padAnta. </p>\n";
echo "<p class = sa >ऋत्यकः (६.१.१२८) :</p>
    <p class = hn >Note: This applies only to padAnta. </p>\n";
display(0);
}
}
if ($upas === 1)
{
    echo "<p class = hn >RtyakaH is barred by upasargAdRti dhAtau. </p>
    <p class = hn >ऋत्यकः उपसर्गादृति धातौ से बाधित हुआ है ।</p><hr>\n";
}
/* vAkyasya TeH pluta udAttaH (8.2.82) */
// This is adhikArasutra. Nothing to code here.
/* pratyabhivAde'zUdre (8.2.83) */
/* dUrAddhUte ca (8.2.84) */
/* haihe prayoge haihayoH (8.2.85) */
/* guronanRto'nantyasyApyekaikasya prAcAm (8.2.86) */
/* aplutavadupasthite (6.1.129) */
/* I3 cAkravarmaNasya (6.1.130) */
// These two are not possible to code, because it will depend on the speaker's choice. Maybe I will add some ajax for user feedback. Pending.
/* IdUdeddvivacanaM pragRhyam (1.1.11) */
// not possible to code till we get the word forms of all words and check whether it is dvivacana or not. Pending
/* adaso mAt (1.1.12) */
if (sub(array("amI"),blank(0),blank(0),0) && $fo === "amI" && $start===1)
{
$text = two (array("amI"),$ac,array("amI "),$ac,1);
echo "<p class = sa >By adaso mAt (".link_sutra("1.1.12").") :</p>\n";
echo "<p class = sa >अदसो मात्‌ (१.१.१२) :</p>\n";
display(0);
}
if (sub(array("amU"),blank(0),blank(0),0)&& $fo === "amU" &&$start===1)
{
$text = two (array("amU"),$ac,array("amU "),$ac,1);
echo "<p class = sa >By adaso mAt (".link_sutra("1.1.12").") :</p>\n";
echo "<p class = sa >अदसो मात्‌ (१.१.१२) :</p>\n";
display(0);
}
/* ze (1.1.13) */
// Not possible to know whether one form has ze or not. ajax for feedback pending.
/* nipAta ekAjanAG (1.1.14) */
$nipata = array("a","A","i","I","u","U","e","E","o","O"); // list of ekAc nipAta.
$nipata1 = array("a ","A ","i ","I ","u ","U ","e ","E ","o ","O "); // adding a space for pragRhya.
if (in_array($first,$nipata) && sub(array($first),$ac,blank(0),0) && $start===1)
{
$text = two ($nipata,$ac,$nipata1,$ac,0);
echo "<p class = sa >By nipAta ekAjanAG (".link_sutra("1.1.14").") :</p>\n";
echo "<p class = sa >निपात एकाजनाङ्‌ (१.१.१४) :</p>\n";
display(0);
}
/* ot (1.1.15) */
$ot = array("o","aho","ho","utAho","aTo");
$ot1 = array("o ","aho ","ho ","utAho ","aTo ");
if (in_array($first,$ot) && sub(array($first),$ac,blank(0),0) && $start===1)
{
$text = two ($ot,$ac,$ot1,$ac,0);
echo "<p class = sa >By ot (".link_sutra("1.1.15").") :</p>\n";
echo "<p class = sa >ओत्‌ (१.१.१५) :</p>\n";
display(0);
}
/* sambuddhau zAkalyasyetAvanArSe (1.1.16) */
if (arr($text,'/[o][+]/') && $second === "iti" && $start===1)
{
$text = two(array($first),$ac,array($first." "),$ac,1);
echo "<p class = sa >By sambuddhau zAkalyasyetAvanArSe (".link_sutra("1.1.16").") :</p>
    <p class = hn >Note: This rule will apply only in case the 'o'kAra at the end of the first word is for sambuddhi and the 'iti' is anArSa (of non-vedic origin).</p>\n";
"<p class = sa >सम्बुद्धौ शाकल्यस्येतावनार्षे (१.१.१६) :</p>
    <p class = hn >यह नियम तभी लागू होगा जब प्रथम शब्द के अन्त का ओकार सम्बुद्धि के लिए हो और अनार्ष 'इति' शब्द उसके परे हो ।</p>\n";
display(0);
}
/* UYaH (1.1.17) */
if ($first === "u" && $second === "iti" && $start===1)
{
$text = two(array("u"),array("iti"),array("u "),array("iti"),1);
echo "<p class = sa >By uYaH (".link_sutra("1.1.17").") :</p>\n";
echo "<p class = sa >उञः (१.१.१७) :</p>\n";
display(0);
}
/* U! (1.1.18) */ // Here ! has been used for anunAsika.
if ($first === "u" && $second === "iti" && $start===1)
{
$text = two(array("u"),array("iti"),array("U! "),array("iti"),1);
echo "<p class = sa >By U! (".link_sutra("1.1.17").") :</p>\n";
echo "<p class = sa >ऊँ (१.१.१७) :</p>\n";
display(0);
}
/* maya uYo vo vA (8.3.33) */
if (sub(array("Sam","kim","tvam","tad"),array("u"),$ac,0))
{
$text = three(array("Sam","kim","tvam","tad"),array("u"),$ac,array("Sam","kim","tvam","tad"),array("v"),$ac,1);
echo "<p class = sa >By maya uYo vo vA (".link_sutra("8.3.33").") :</p>\n";
echo "<p class = sa >मय उञो वो वा (८.३.३३) :</p>\n";
display(0);
}
/* IdUtau ca saptamyarthe (1.1.19) */
/*$idut = array("I","U"); $idut1 = array("I ","U ");
if (preg_match('/[IU]$/',$first) && sub(array("I","U"),$ac,blank(0),0) && $pada ==="pada")
{
$text = two($idut,$ac,$idut1,$ac,1);
echo "<p class = sa >By IdUtau ca saptamyarthe (".link_sutra("1.1.19").") :</p>\n";
echo "<p class = hn >N.B.: This will apply only in case the I/U at the end of the first word have been used in sense of saptamI vibhakti. Otherwise this pragRhyatva will not be there.</p>\n";
echo "<p class = sa >ईदूतौ च सप्तम्यर्थे (१.१.१९) :</p>\n";
echo "<p class = hn >यदि प्रथम पद के अन्त में ई / ऊ सप्तमी के अर्थ में प्रयुक्त हुए हों तभी यह नियम लागू होगा ।</p>\n";
display(0);
}*/ // vedic in nature. gives too many wrong results. commented right now.
/* zakandhvAdiSu pararUpaM vAcyam (vA 3632) */
$shakandhu1 = array("Saka","karka","kula","manas","hala","lANgala","patan","mfta"); // first word of zakandhvAdi
$shakandhu2 = array("anDu","anDu","awA","IzA","IzA","IzA","aYjali","aRqa"); // second word of zakandhvAdi
$shakandhu = array("Sak","kark","kul","man","hal","lANgal","pat","mArt"); // replacement of first word.
if (sub($shakandhu1,$shakandhu2,blank(0),0))
{
$text = two($shakandhu1,$shakandhu2,$shakandhu,$shakandhu2,0);
echo "<p class = sa >By zakandhvAdiSu pararUpaM vAcyam (vA 3632) :</p>\n";
echo "<p class = sa >शकन्ध्वादिषु पररूपं वाच्यम्‌ (वा ३६३२) :</p>\n";
display(0);
}
$shakandhu1 = array("sIman","sAra"); // ajax possible. pending.
$shakandhu2 = array("anta","aNga");
$shakandhu = array("sIm","sAr");
if (sub($shakandhu1,$shakandhu2,blank(0),0))
{
$text = two($shakandhu1,$shakandhu2,$shakandhu,$shakandhu2,1);
$text = one(array("sIman+ant","sAra+aNg"),array("sIm+Ant","sAr+ANg"),0);
echo "<p class = sa >By zakandhvAdiSu pararUpaM vAcyam (vA 3632) :</p>\n";
echo  "<p class = hn >Note: the sImanta - kezaveSa and sAraGga - pazu/pakSI - Then only this will apply.</p>\n";
echo "<p class = sa >शकन्ध्वादिषु पररूपं वाच्यम्‌ (वा ३६३२) :</p>\n";
echo  "<p class = hn >यदि सीमन्त केशवेश के अर्थ में और सारङ्ग पशु-पक्षी के अर्थ में प्रयुक्त हुए हो, तभी यह नियम लागू होता है ।</p>\n";
display(0);
}
/* omAGozca (6.1.95) */ 
$aag = array("om","OM","Aj","Acy","AYc","Anakt","Att","As","er","Eray","okz","ArcC","Arpit","Ohyat","oQ","arSyAt"); // list for OM and AG.
if (sub(array("a","A"),$aag,blank(0),0))
{ 
$text = two(array("a","A"),array("om","OM"),blank(2),array("om","om"),0);
$text = two(array("a","A"),$aag,blank(2),$aag,0);
echo "<p class = sa >By omAGozca (".link_sutra("6.1.95").") :</p>
    <p class = hn >The om or AG following the a,A gets converted to pararUpa. </p>\n";
echo "<p class = sa >ओमाङोश्च (६.१.९५) :</p>
    <p class = hn >अ/आ के परे यदि ओम्‌ या आङ्‌ हो तो पररूप होता है । </p>\n";
display(0);
}
/* ATazca (6.1.90) */
if (sub(array("A"),$ac,blank(0),0) && in_array("Aw",$Agama) )
{
$text = two(array("A"),$ac,array(""),vriddhi($ac),0);
echo "<p class = sa >By ATazca (".link_sutra("6.1.90").") :</p>\n";
echo "<p class = sa >आटश्च :</p>\n";
display(0);
}
/* striyAH (6.4.79) */
if (ends(array($fo),array("strI","stri"),1) && arr($text,'/[s][t][r][Ii][+]['.pc('ac').']/') && in_array($so,$acsup) && !in_array($so,array("am","Sas","Am")))
{
    $text = one(array("strI+","stri+"),array("striy+","striy+"),0);
    echo "<p class = sa >By striyAH (".link_sutra("6.4.79").") :</p>\n";
    echo "<p class = sa >स्त्रियाः (६.४.७९) :</p>\n";
    display(3);
}
/* nAdici (6.1.104) */
$ic = array("i","I","u","U","f","F","x","X","e","o","E","O");
if (arr($text,'/[a][+]['.flat($ic).']/') && !arr($text,'/[a][+][aA]/') && (in_array($so,$prathama)))
{
    echo "<p class = pa >By nAdici (".link_sutra("6.1.104").") :</p>
        <p class = hn >N.B. : This is exception to prathamayoH pUrvasavarNaH. </p>\n";
      echo "<p class = pa >नादिचि (६.१.१०४) :</p>
        <p class = hn >यह नियम प्रथमयोः पूर्वसवर्णः का अपवाद है ।</p>\n";
    display (0); 
    $nadici = 1; // 0 - this rule has not applied. 1 - this rule has applied.
} else { $nadici = 0; }
/* dIrghAjjasi ca (6.1.105) */
if ((arr($text,'/[AIUFeEoO][+]['.flat($ic).']/')||((sub($dirgha,array("+"),array("as"),0)) && $so==="jas")) && (in_array($so,$prathama)))
{
    echo "<p class = pa >By dIrghAjjasi ca (".link_sutra("6.1.105").") :</p>
        <p class = hn >N.B. : This is exception to prathamayoH pUrvasavarNaH. </p>\n";
      echo "<p class = pa >दीर्घाज्जसि च (६.१.१०५) :</p>
        <p class = hn >यह नियम प्रथमयोः पूर्वसवर्णः का अपवाद है ।</p>\n";
    display (0); 
    $nadici1 = 1; // 0 - this rule has not applied. 1 - this rule has applied.
} else { $nadici1 = 0; } 
/* prathamayoH pUrvasavarNaH (6.1.102) */ 
// Not coded well. Please revisit.
$ak = array("a","A","i","I","u","U","f","F","x","X"); 
$akreplace = array("A","A","I","I","U","U","F","F","F","X");
if (sub($ak,array("+"),array("a","O"),0) && in_array($so,$prathama)  && $nadici === 0 && $nadici1 === 0)
{
    if (sub(array("a"),array("a","O"),blank(0),0)&& in_array($so,$prathama)  && $nadici === 0 && $nadici1 === 0)
    {
        echo "<p class = hn >ato guNe (".link_sutra("6.1.96").") is exception to only akaH savarNe dIrghaH (".link_sutra("6.1.101").") and not to prathamayoH pUrvasavarNaH (".link_sutra("6.1.102")."), because of the paribhASA 'purastAdapavAdA anantarAnvidhInbAdhante nottarAn (pa 60). Therefore pURvasavarNadIrgha can occur. :</p>\n";
        echo "<p class = hn >'पुरस्तादपवादा अनन्तरान्विधीन्बाधन्ते नोत्तरान्‌ (प ६०) परिभाषा के कारण अतो गुणे (६.१.९६) सिर्फ अकः सवर्णे दीर्घः (६.१.१०१) का अपवाद है, प्रथमयोः पूर्वसवर्णः (६.१.१०२) का नहीं । अतः पूर्वसवर्णदीर्घ हो सकता है ।</p>\n";
        display (0);
    }
    $text = two($ak,array("a","O"),$akreplace,array("",""),0);
    echo "<p class = sa >By prathamayoH pUrvasavarNaH (".link_sutra("6.1.102").") :</p>
        <p class = hn >N.B. : This applies to only in prathamA and dvitIyA vibhakti, and not in other cases. </p>\n";
    echo "<p class = sa >प्रथमयोः पूर्वसवर्णः (६.१.१०२) :</p>
        <p class = hn >यह प्रथमा और द्वितीया विभक्तियों में लागू होता है ।</p>\n";
    display (0); 
    $prathamayoh = 1; // 0 - this rule has not applied. 1 - this rule has applied. Useful in tasmAcCaso naH puMsi.
} else { $prathamayoh = 0; }
/* tasmAcChaso naH puMsi (6.1.103) */
if ($prathamayoh ===1 && $so === "Sas" && $gender==="m")
{
    $text = one(array("+s"),array("+n"),0);
    echo "<p class = sa >By tasmAcCaso naH puMsi (".link_sutra("6.1.103").") :</p>\n";
    echo "<p class = sa >तस्माच्छसो नः पुंसि (६.१.१०३) :</p>\n";  
    display(0); 
}
/* Rti savarNe R vA (vA 3640) and lRti savarNe lR vA (vA 3641) */
$ruti1 = array("f","F","x","X");
$ruti2 = array("f");
$lruti2 = array("x");
if (sub($ruti1,array("f","x"),blank(0),0))
{
$text = two($ruti1,array("f"),blank(count($ruti1)),$ruti2,1);
$text = two($ruti1,array("x"),blank(count($ruti1)),$lruti2,1);
echo "<p class = sa >By Rti savarNe R vA (vA 3640) and lRti savarNe lR vA (vA 3641) :</p>\n";
echo "<p class = sa >ऋति सवर्णे ऋ वा (वा ३६४०) तथा लृति सवर्णे लृ वा (वा ३६४१) :</p>\n";
display(0); 
}
/* akaH savarNe dIrghaH (6.1.101) */ 
$ak1 = array("a","a","A","A","i","i","I","I","u","u","U","U","f","f","F","F","f","f","F","F","x","x","X","X","x","x","X","X");
$ak2 = array("a","A","a","A","i","I","i","I","u","U","u","U","f","F","f","F","x","X","x","X","f","F","f","F","x","X","x","X");
if (sub($ak1,$ak2,blank(28),1))
{
$text = two(array("a","A"),array("a","A"),array("A","A"),blank(2),0);
$text = two(array("i","I"),array("i","I"),array("I","I"),blank(2),0);
$text = two(array("u","U"),array("u","U"),array("U","U"),blank(2),0);
$text = two(array("f","F","x","X"),array("f","F","x","X"),array("F","F","F","F"),blank(4),0);
$text = two(array("x","X"),array("x","X"),array("F","F"),blank(2),0);
echo "<p class = sa >By akaH savarNe dIrghaH (".link_sutra("6.1.101").") :</p>\n";
echo "<p class = sa >अकः सवर्णे दीर्घः (६.१.१०१) :</p>\n";
display(0);
}
/* ato guNe (6.1.17) */
if (sub(array("a"),array("a","e","o"),blank(0),0) && !sub(array("pra","upa"),array("a","e","o"),blank(0),0) && $pada === "pratyaya" )
{
    $text = two(array("a"),array("a","e","o"),blank(1),array("a","e","o"),0);
    echo "<p class = sa >By ato guNe (".link_sutra("6.1.17").") :</p>\n";
    echo "<p class = sa >अतो गुणे (६.१.१७) :</p>\n";  
    display(0);
}
/* hrasvasya guNaH (7.3.108) */ 
if (arr($text,'/[iufx][+][s]/') && $so==="su!" && $sambuddhi===1 && $amba===0)
{
    $text = two(array("i","u","f","x"),array("+"),array("e","o","ar","al"),array("+"),0);
    echo "<p class = sa >By hrasvasya guNaH (".link_sutra("7.3.108").") :</p>\n";
    echo "<p class = sa >ह्रस्वस्य गुणः (७.३.१०८) :</p>\n";
    display(3);
}
if (arr($text,'/[iufx][+]$/') && $so==="su!" && $sambuddhi===1 && $svamo===1)
{
    $text = two(array("i","u","f","x"),array("+"),array("e","o","ar","al"),array("+"),1);
    echo "<p class = sa >By hrasvasya guNaH (".link_sutra("7.3.108").") :</p>\n";
    echo "<p class = sa >ह्रस्वस्य गुणः (७.३.१०८) :</p>\n";
    display(3);
}
/* halGyAbbhyo dIrghAtsutisyapRktaM hal (6.1.68) and apRkta ekAlpratyayaH (1.2.41) */
// GyAp pending. only hal handled now.
if ((arr($text,'/['.pc('hl').'][+][sts]$/') || $GI===1 || $Ap===1 )&& in_array($so,array("su!","ti","si",)))
{
    echo "<p class = pa >By apRkta ekAlpratyayaH (".link_sutra("1.2.41").") :</p>\n";
    echo "<p class = pa >अपृक्त एकाल्प्रत्ययः (१.२.४१) :</p>\n";
    display(0);
}
if ((arr($text,'/['.pc('hl').'][+][sts]$/')  )&& in_array($so,array("su!","ti","si")))
{
    $text = two($hl,array("+s","+t"),$hl,array("+","+"),0);
    echo "<p class = sa >By halGyAbbhyo dIrghAtsutisyapRktaM hal (".link_sutra("6.1.68").") :</p>\n";
    echo "<p class = sa >हल्ङ्‍याब्भ्यो दीर्घात्सुतिस्यपृक्तं हल्‌ (६.१.६८) :</p>\n";
    display(0); 
    $pada="pada"; // there is no pratyaya left now.
}
if ($GI===1 && arr($text,'/[I][+][st]$/') && in_array($so,array("su!","ti","si")))
{
    $text = two(array("I"),array("+s","+t"),array("I"),array("+","+"),0);
    echo "<p class = sa >By halGyAbbhyo dIrghAtsutisyapRktaM hal (".link_sutra("6.1.68").") :</p>\n";
    echo "<p class = sa >हल्ङ्‍याब्भ्यो दीर्घात्सुतिस्यपृक्तं हल्‌ (६.१.६८) :</p>\n";
    display(0); 
    $pada="pada"; // there is no pratyaya left now.
}
if ( $Ap===1 && sub(array("A"),array("+"),array("s","t"),0) && in_array($so,array("su!","ti","si")))
{
    $text = two(array("A"),array("+s","+t"),array("A"),array("+","+"),0);
    echo "<p class = sa >By halGyAbbhyo dIrghAtsutisyapRktaM hal (".link_sutra("6.1.68").") :</p>\n";
    echo "<p class = sa >हल्ङ्‍याब्भ्यो दीर्घात्सुतिस्यपृक्तं हल्‌ (६.१.६८) :</p>\n";
    display(0); 
    $pada="pada"; // there is no pratyaya left now.
}
/* patch for varShABU sambodhana */
if ($dhatu===1 && $first==="varzABU" && sub(array("varzABU"),array("+"),array("s"),1) && $pada==="pratyaya" )
{
    $text = two(array("varzABU"),array("+"),array("varzABu"),array("+"),1);
    echo "<p class = sa >kaiyaTa believes it to be 'he varSAbhUH'. The alternative belief is 'he varSAbhu'.</p>\n";
    echo "<p class = sa >भेकजातौ नित्यस्त्रीत्वाभावाद्‌ हे वर्षाभूः कैयटमते । मतान्तरे तु हे वर्षाभु ।</p>\n";
    display(0);
}

/* eGhrasvAtsambuddheH (6.1.69) and ekavacanaM sambuddhiH (2.3.49) */ // removed the last letter, not as in sutra. Look out for issues if any crops up.
if ($sambuddhi === 1 && $so === "su!" && (sub($hrasva,array("+"),array("s","m"),0)||sub(array("e","o"),array("+"),array("s","m"),0)) && arr($text,'/[+][sm]$/'))
{
   $text = one(array("a+s","i+s","u+s","f+s","x+s","e+s","o+s","a+m"),array("a+","i+","u+","f+","x+","e+","o+","a+"),0); // this was tried. But it gives wrong results in kroSTrI sambuddhi.
   /* foreach ($text as $value)
    {
        if(substr($value,-1)!=="+")
        {
            $value1[] = substr($value,0,strlen($value)-1);
        }
        else
        {
            $value1[] = $value;
        }
    }
    $text = $value1;
    $value1 = array();*/
    echo "<p class = sa >By eGhrasvAtsambuddheH (".link_sutra("6.1.69").") :</p>\n";
    echo "<p class = sa >एङ्ह्रस्वात्सम्बुद्धेः (६.१.६९) :</p>\n";
    display(0); 
}
/* dazca (7.2.109) */
if (sub(array("ida","idak","idA","idAn",),blank(0),blank(0),0) && in_array($fo,array("idam","idakam")) && in_array($so,$sup))
{
    $text = one(array("ida+","idak","idA+","idAn+","idakA+"),array("ima+","imak","imA+","imAn+","imakA+"),0);
    echo "<p class = sa >By dazca (".link_sutra("7.2.109").") :</p>\n";
    echo "<p class = sa >दश्च (७.२.१०९) :</p>\n";
    display(3);
}
/* iko yaNaci (6.1.77) */
if(sub(array('i','I','u','U'),prat('ac'),blank(0),0))
{
$text = two(array('i','I','u','U'),prat('ac'),array('y','y','v','v'),prat('ac'),0);
echo "<p class = sa >By iko yaNaci (".link_sutra("6.1.77").") :</p>\n";
echo "<p class = sa >इको यणचि (६.१.७७) y:</p>\n";
display(0);
}
if(sub(array("f","F","x","X"),prat('ac'),blank(0),0))
{
$text = two(array("f","F","x","X"),prat('ac'),array("r","r","l","l"),prat('ac'),0);
echo "<p class = sa >By iko yaNaci (".link_sutra("6.1.77").") :</p>\n";
echo "<p class = sa >इको यणचि (५.१.७७) :</p>\n";
$sthanivadbhav = 1; // 0 - no sthAnivadbhAva. 1 - sthAnivadbhAva.
display(0); 
}
else
{
$sthanivadbhav = 0; 
}
/* sarvatra vibhASA goH (6.1.122) */ 
$go = array("go"); $aonly = array("a");
if(sub($go,$aonly,blank(0),0) && ( $pada==="pada" || $bham===1 || sub(array("goanc"),blank(0),blank(0),0)))
{
$text = two($go,$aonly,array("go "),$aonly,1);
echo "<p class = sa >By sarvatra vibhASA goH (".link_sutra("6.1.122").")</p>
    <p class = hn >it is optionally kept as prakRtibhAva :</p>\n";
echo "<p class = sa >सर्वत्र विभाषा गोः (६.१.१२२)</p>
    <p class = hn >पाक्षिक रूप से प्रकृतिभाव भी होता है ।</p>\n";
display(0); 
} 
/* avaG sphoTAyanasya (6.1.123) */ 
if (sub($go,prat('ac'),blank(0),0) && ( $pada==="pada" || $bham===0 || sub(array("goanc"),blank(0),blank(0),0))  )
{
$text = two($go,prat('ac'),array('gava'),prat('ac'),1);
echo "<p class = sa >By avaG sphoTAyanasya (".link_sutra("6.1.123").") </p>
    <p class = hn >it is optionally converted to avaG :</p>\n";
echo "<p class = sa >अवङ्‌ स्फोटायनस्य (६.१.१२३) </p>
    <p class = hn >पाक्षिक रूप से अवङ्‌ भी होता है ।</p>\n";
display(0);
} 
/* indre ca (6.1.124) */
if (sub($go,array("indra"),blank(0),0)  && ( $pada==="pada" || $bham===1 || sub(array("goanc"),blank(0),blank(0),0)))
{
$text = two($go,array("indra"),array('gava'),array("indra"),0);
echo "<p class = sa >by indre ca (".link_sutra("6.1.124").") :</p>\n";
echo "<p class = sa >इन्द्रे च (६.१.१२४) :</p>\n";
display(0); 
} 
/* eGaH padAntAdati (6.1.109) */ 
if (sub(array("e","o"),array("a"),blank(0),0)  && ( $pada==="pada" || sub(array("goanc"),blank(0),blank(0),0)))
{
    $text = two(prat('eN'),array("a"),prat('eN'),array("'"),0);
    echo "<p class = sa >By eGaH padAntAdati (".link_sutra("6.1.109").") : </p>\n";
    echo "<p class = sa >एङः पदान्तादति (६.१.१०९) : </p>\n";
    display(0);
}
/* eco'yavAyAvaH (7.1.78) */
$ayavayavah = array("ay","av","Ay","Av");
if (sub(prat('ec'),prat('ac'),blank(0),0))
{
$text = two(prat('ec'),prat('ac'),$ayavayavah,prat('ac'),0);
echo "<p class = sa >By echo'yavAyAvaH (".link_sutra("7.1.78").") :</p>\n";
echo "<p class = sa >एचोऽयवायावः (७.१.७८) 6:</p>\n";
display(0);
}
/* dIdhIvevITAm (1.1.7) */
if (arr($text,'/[a][yv][i][t][A]$/') && in_array("iw",$Agama) && $ardhadhatuka===1 && in_array($so,$tiG) && $start===1)
{
    echo "<p class = sa >dIdhIvevITAm (".link_sutra("1.1.7").") :</p>\n"; 
    echo "<p class = hn >This rule prevents guNa which is mandated by pugantalaghUpadhasya ca.</p>\n"; 
    echo "<p class = sa >दीधीवेवीटाम्‌ (१.१.७) :</p>\n";
    echo "<p class = hn >अनेन पुगन्तलघूपधस्य चेत्यनेन विहितो गुणो बाध्यते ।</p>\n";
    display(0);    
}
/* vAnto yi pratyaye (6.1.71), goryutau CandasyupasaMkhyAnam (vA 3543), adhvaparimANe ca (vA 3544) dhAtostannimittasyaiva (6.1.80) */
$o = array("o","O"); 
$oo = 'oO'; 
$y = array("y"); 
$ab = array("av","Av");
$dhato = array("urRo","ro","no","sno","kzo","kzRo","Do","Dro","do","dro","sro","so","ko","Go","qo","cyo","jyo","pro","plo","ro","ho","Sro","hno","dyo","sko","po","lo","kno","mo","Bo","urRO","rO","snO","kzO","kzRO","DO","DrO","dO","drO","srO","sO","kO","GO","qO","cyO","jyO","prO","plO","rO","hO","SrO","hnO","dyO","skO","pO","lO","knO","mO","BO","Co","zo","So");
$dhato1 = array("urRa","ra","na","sna","kza","kzRa","Da","Dra","da","dra","sra","sa","ka","Ga","qa","cya","jya","pra","pla","ra","ha","Sra","hna","dya","ska","pa","la","kna","ma","Ba","urRA","rA","snA","kzA","kzRA","DA","DrA","dA","drA","srA","sA","kA","GA","qA","cyA","jyA","prA","plA","rA","hA","SrA","hnA","dyA","skA","pA","lA","knA","mA","BA","Ca","za","Sa");
if (sub($dhato,$y,blank(0),0) && $pada ==="pratyaya" && $start ===1)
{
$text = two($dhato,$y,$dhato1,array("vy"),0);
    echo "<p class = sa >By dhAtostannimittasyaiva (".link_sutra("6.1.77").")  : </p>\n";
    echo "<p class = sa >धातोस्तन्निमित्तस्यैव (६.१.७७) : </p>\n";
    display(0);
} 
if (sub($o,$y,blank(0),0) && $pada ==="pratyaya" && !sub($dhato,$y,blank(0),0))
{
    if (sub(array("gav"),$y,blank(0),0))
    {
    $text = two($o,$y,$ab,$y,0);
    echo "<p class = sa >By vAnto yi pratyaye (".link_sutra("6.1.71")."), goryutau CandasyupasaMkhyAnam (vA 3543), adhvaparimANe ca (vA 3544)  : </p>
        <p class = hn > If the 'y' following 'o/O' belongs to a pratyaya or the word 'go' is followed by 'yuti' in Chandas/ as a measure of distance (vA 3543, 3544).</p>\n";
    echo "<p class = sa >वान्तो यि प्रत्यये (६.१.७१), गोर्यूतौ छन्दस्युपसंख्यानम्‌ (वा ३५४३), अध्वपरिमाणे च (वा ३५४४)  : </p>
        <p class = hn > यकारादि प्रत्यय के परे रहते या वैदिक भाषा / अध्वपरिमाण के अर्थ में यूति शब्द परे हो तब यह नियम लागू होता है ।</p>\n";        
    }
    elseif (sub(array("o","O"),array("yat"),blank(0),0))
    {
    echo "<p class = sa >By dhAtostannimittasyaiva (".link_sutra("6.1.77").")  : </p>\n";
    echo "<p class = hn >Here the 'o' is because of 'y'. Therefore This doesn't apply. </p>\n";
    echo "<p class = sa >धातोस्तन्निमित्तस्यैव (६.१.७७) :</p>\n";                
    echo "<p class = hn >ओयते में ओकार यकारनिमित्तक नहीं होने के कारण इस सूत्र से अवादेश नहीं हुआ ।</p>\n";                
    }
    else
    {
    $text = two($o,$y,$ab,$y,0);
    echo "<p class = sa >By vAnto yi pratyaye (".link_sutra("6.1.71").") : </p>\n";
    echo "<p class = sa >वान्तो यि प्रत्यये (६.१.७१) :</p>\n";        
    }
    display(0);
}
/* kSayyajayyau zakyArthe (6.1.81)*/
if (sub(array("kze"),array("ya"),blank(0),0)  && $pada ==="pratyaya" && $start === 1)
{
    $text = two(array("kze"),array("ya"),array("kzay"),array("ya"),1);
    echo "<p class = sa >By kSayyajayyau zakyArthe (".link_sutra("6.1.81").") :</p>
        <p class = hn >If the word is to be used in the meaning of 'being capable of', then only it will be क्षय्य.</p>\n";
    echo "<p class = sa >क्षय्यजय्यौ शक्यार्थे (६.१.८१) :</p>
        <p class = hn >यदि क्षय कर सकने के  अर्थ में प्रयोग होता है तभी क्षय्य होगा ।</p>\n";
    display(0);    
}
if (sub(array("je"),array("ya"),blank(0),0) && $pada ==="pratyaya" && $start === 1)
{
        $text = two(array("je"),array("ya"),array("jay"),array("ya"),1);
    echo "<p class = sa >By kSayyajayyau zakyArthe (".link_sutra("6.1.81").") :</p>
        <p class = hn >If the word is to be used in the meaning of 'being capable of', then only it will be जय्य.</p>\n";
    echo "<p class = sa >क्षय्यजय्यौ शक्यार्थे (६.१.८१) :</p>
        <p class = hn >यदि जय कर सकने के  अर्थ में प्रयोग होता है तभी जय्य होगा ।</p>\n";
    display(0);
}
/* krayyastadarthe (6.1.82) */
if (sub(array("kre"),array("ya"),blank(0),0)  && $pada ==="pratyaya" && $start ===1)
{
    
    $text = two(array("kre"),array("ya"),array("kray"),array("ya"),1);
    echo "<p class = sa >By krayyastadarthe (".link_sutra("6.1.82").") :</p>
        <p class = hn >If the word is to be used in the meaning of 'for sale', then only it will be क्रय्य.</p>\n";
     echo "<p class = sa >क्रय्यस्तदर्थे (६.१.८२) :</p>
        <p class = hn >यदि बेचने के लिये रखा हुआ इस अर्थ में प्रयोग हो तभी क्रय्य शब्द बनता है </p>\n";
    display(0);
}
/* khyatyAtparasya (6.1.112) */
if (arr($text,'/[Kt][y][+][a]/')  && arr(array($fo),'/[Kt][iI]$/') && in_array($so,array("Nasi!","Nas")) && $pada==="pratyaya")
{
    $text = three(array("Ky","ty"),array("+"),array("a"),array("Ky","ty"),array("+"),array("u"),0);
    echo "<p class = sa >By khyatyAtparasya (".link_sutra("6.1.112").") :</p>\n";
    echo "<p class = sa >ख्यत्यात्परस्य (६.१.११२) :</p>\n";
    display(0);
}
if (sub(array("lUny","kzAmy","prastImy"),array("+"),blank(0),0) && in_array($so,array("Nasi!","Nas")) && $pada==="pratyaya")
{
    $text = three(array("lUny","kzAmy","prastImy"),array("+"),array("a"),array("lUny","kzAmy","prastImy"),array("+"),array("u"),0);
    echo "<p class = sa >By khyatyAtparasya (".link_sutra("6.1.112").") :</p>\n";
    echo "<p class = sa >ख्यत्यात्परस्य (६.१.११२) :</p>\n";
    display(0);
}

/* Exceptions to sasajuSo ruH */
/* etattadoH sulopo'konaJsamAse hali (6.1.132) */
if (sub(array("sas","ezas"),$hl,blank(0),0)  && !sub(array("asa","anEza"),array("s"),$hl,0) && in_array($fo,array("sas","ezas")))
{
    $text = two(array("sas","ezas"),$hl,array("sa ","eza "),$hl,1);
    echo "<p class = sa >By etattadoH sulopo&konaJsamAse hali (".link_sutra("6.1.132").") :</p>\n";
    echo "<p class = sa >एतत्तदोः सुलोपोऽकोऽनञ्समासे हलि (६.१.१३२)) :</p>\n";
    display(0);
}
/* so'ci lope cetpAdapUraNam (6.1.134) */
//if (sub(array("sa"),array("s"),$ac,0))
if (sub(array("sas"),array("+"),$ac,0))
{
    $text = three(array("sa"),array("s"),$ac,array("sa"),array(""),$ac,1);
    echo "<p class = sa >so'ci lope cetpAdapUraNam (".link_sutra("6.1.134").") :</p>
        <p class = hn >N.B. : There is difference of opinion here. vAmana thinks that it applies only to RkpAda. Others think that it applies to zlokapAda also e.g. 'saiSa dAzarathI rAmaH'.</p>\n";
    echo "<p class = sa >सोऽचि लोपे चेत्पादपूरणम्‌ (६.१.१३४) :</p>
        <p class = hn >यहाँ मतान्तर है । वामन के मत में यह केवल ऋक्पाद में लागू होता है । अन्यों के मत में यह श्लोकपाद में भी लागू होता है । जैसे कि सैष दाशरथी रामः ।</p>\n";
    display(0);
}
/* aniditAM patch for sraMs and dhvaMs */
// so that vasusraMsu.. may apply
if (sub(array("sraMs","DvaMs"),blank(0),blank(0),0))
{
        $text = one(array("sraMs","DvaMs"),array("sras","Dvas"),0);
        echo "<p class = sa >aniditAM hala upadhAyAH kGiti (".link_sutra("6.4.24").") :</p>\n";
        echo "<p class = sa >अनिदितां हल उपधायाः क्ङिति (६.४.२४) :</p>\n";
        display(0); $aniditAm = 1;
}
/* vasusraMsudhvaMsvanaDuhAM daH (8.2.72) */
$vasu1 = array("sras","Dvas","anaquh");
if (sub($vasu1,array("+"),blank(0),0)  && $pada ==="pada")
{
    $text = two(array("sras","Dvas","anaquh"),array("+"),array("srad","Dvad","anaqud"),array("+"),0);
    echo "<p class = sa >By vasusraMsudhvaMsvanaDuhAM daH (".link_sutra("8.2.72").") :</p>\n";
     echo "<p class = sa >वसुस्रंसुध्वंस्वनडुहां दः (८.२.७२) :</p>\n";
    display(0); 
}
if ((sub(array("vidvas","sedivas","uzivas","Suzruvas","upeyivas","anASvas"),array("+"),blank(0),0))  && $pada ==="pada" && !arr($text,'/[s][+]$/'))
{
    $text = one(array("vidvas","sedivas","uzivas","Suzruvas","upeyivas","anASvas"),array("vidvad","sedivad","uzivad","Suzruvad","upeyivad","anASvad"),0);
    echo "<p class = sa >By vasusraMsudhvaMsvanaDuhAM daH (".link_sutra("8.2.72").") :</p>
        <p class = hn >N.B. : If 'vas' is used in sense of vasupratyayAnta as in 'vidvas', then only this conversion takes place. Not in cases like 'zivas'.</p>\n";
     echo "<p class = sa >वसुस्रंसुध्वंस्वनडुहाः दः (८.२.७२) :</p>
        <p class = hn >यदि वसुप्रत्ययान्त शब्द जैसे कि विद्वस्‌ इत्यादि में यह नियम लागू होता है । शिवस्‌ जैसे शब्दों में नहीं ।</p>\n";
   display(0); 
}
/* saMyogAnta patch for asmad / yuSmad */
// make it more specific to asmad / yuSmad. Right now there is nothing in the condition which restricts its application to asmad / yuSmad.
if (arr($text,'/['.pc('hl').'][s]$/') )
{
    $text = last(array("nas","vas"),array("nass","vass"),0);
   $text = last(array("s"),array(""),0);
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) :</p>\n";
   display(0);
}
/* patch for asiddhatva of SakAra of pratyaya as in pipaThiS */
// SakARa of pipaThiS is because of a sUtra which is in tripAdi. so asiddhatva has to apply. We have not come to that sUtra. We take pipaThiS as the base word.
if (arr($text,'/[z][+]/') && $pada==="pada" && sub(array("pipaWiz","ASiz"),array("+"),blank(0),0) )
{
   $text = two(array("z"),array("+"),array("r@"),array("+"),0); $R=array_merge($R,array(1));
    echo "<p class = sa >Satva is asiddha to rutva. Therefore sasajuSo ruH applies. </p>\n";
    echo "<p class = sa >रुत्वं प्रति षत्वस्यासिद्धत्वात्‌ ससजुषो रुः इति रुत्वम्‌ ।</p>\n";
   display(0); 
   $dhatu=1; // This patch is for dhAtus. So $dhatu is made 1.
}
/* patch for asiddhatva of SakAra of doS */
if (arr($text,'/[z][+]/') && $pada==="pada" && sub(array("doz","Danuz"),array("+"),blank(0),0) )
{
   $text = two(array("z"),array("+"),array("r@"),array("+"),0); $R=array_merge($R,array(1));
    echo "<p class = sa >Satva is asiddha to rutva. Therefore sasajuSo ruH applies. </p>\n";
    echo "<p class = sa >रुत्वं प्रति षत्वस्यासिद्धत्वात्‌ ससजुषो रुः इति रुत्वम्‌ ।</p>\n";
   display(0); // this patch is for non dhAtus. therefore $dhatu is not altered.
}
/* sasajuSo ruH (8.2.66) */
if ((arr($text,'/[s][a][j][u][z][+]/') && $start===1  && $pada ==="pada" ))
{
    $text = one(array("z+",),array("r@+",),0); $R=array_merge($R,array(1));
    echo " <p class = sa >By sasajuSo ruH (".link_sutra("8.2.66").") :</p>\n";
    echo " <p class = sa >ससजुषो रुः (८.२.६६) :</p>\n";
       display(0);
}
if ((arr($text,'/[H][+]/') && $start===1  && $pada ==="pada" )) // for neutralising users' tendency to enter visarga.
{
    $text = one(array("H+",),array("r@+",),0); $R=array_merge($R,array(1));
    echo " <p class = hn >You have entered a visarga at the end of the first word. Usually it is derived from a sakAra at the end of the word.</p>\n";
    echo " <p class = hn >आपने प्रथम शब्द के अन्त में विसर्ग का प्रयोग किया है । सामान्यतः यह सकारान्त शब्द से उद्भव होता है ।</p>\n";
       display(0);
}
if (arr($text,'/[aAiIuUfFxXeEoO][s][+]/') && $start===1  && ( $pada ==="pada" || $so==="su!" ))
{
    $text = one(array("s+"),array("r@+"),0); $R=array_merge($R,array(1));
    echo " <p class = sa >By sasajuSo ruH (".link_sutra("8.2.66").") :</p>\n";
    echo " <p class = sa >ससजुषो रुः (८.२.६६) :</p>\n";
       display(0); $r1=1; // 0 - first word doesn't have sasajuSo ruH. 1 - first owrd has sasajuSo ruH.
}
elseif ($start>1 && $r1!==0) { $r1 = 1; } else {$r1=0; }
if ( arr($text,'/['.pc('ac').'][s]$/') && $start===1 )
{
    $text = last(array("s"),array("r@"),0);
    echo "<p class = sa >By sasajuSo ruH (".link_sutra("8.2.66").") :</p>\n"; 
     echo "<p class = sa >ससजुषो रुः (८.२.६६) :</p>\n";$r2 = 1;
     display(0);
}
elseif ( arr($text,'/['.pc('ac').'][+][s]$/') && $start===1 )
{
    $text=last(array("s"),array("r@"),0);
//    $text = three($ac,array("+"),array("s"),$ac,array("+"),array("r@"),0); $R=array_merge($R,array(1));
     echo "<p class = sa >By sasajuSo ruH (".link_sutra("8.2.66").") :</p>\n"; 
     echo "<p class = sa >ससजुषो रुः (८.२.६६) :</p>\n";
     $r2 = 1; // 0 - second word doesn't have sasajuSo ruH. 1 -second word has sasajuSo ruH.
     display(0);
}
elseif ($start>1 && $r2!==0) 
    {
    $r2 = 1; 
    } 
else 
    {
    $r2=0; 
    }
if (arr($text,'/[H]$/') && $start===1  && $pada ==="pada" )
{
     $text = last(array("H"),array("r@"),0); $R=array_merge($R,array(1));
      echo " <p class = hn >You have entered a visarga at the end of the second word. Usually it is derived from a sakAra at the end of the word.</p>\n";
    echo " <p class = hn >आपने द्वितीय शब्द के अन्त में विसर्ग का प्रयोग किया है । सामान्यतः यह सकारान्त शब्द से उद्भव होता है ।</p>\n";
     echo "<p class = sa >By sasajuSo ruH (".link_sutra("8.2.66").") :</p>\n"; 
      echo "<p class = sa >ससजुषो रुः (८.२.६६) :</p>\n";
      $r1= 1;
     display(0);
}
/* ahan(8.2.68) and ro'supi (8.2.69) and rUparAtrirathantareSu vAcyam (vA 4847) */ 
$noahan = array("vftrahan","bahuvftrahan");
if (sub(array("ahan","Ahan","AhAn"),array("+"),blank(0),0) && !sub($noahan,blank(0),blank(0),0) && !(in_array($so,$sup)) && $pada==="pada")
{ 
    if ((strpos($so,"rUp")===0)||(strpos($so,"rAtr")===0)||(strpos($so,"raTantar")===0))
    {
    $text = one(array("ahan","Ahan","AhAn"),array("ahar@","Ahar@","AhAr@"),0); $R=array_merge($R,array(1));
    echo "<p class = sa >By ahan (".link_sutra("8.2.68").") and rUparAtrirathantareSu vAcyam (vA 4847).</p>\n";
     echo "<p class = sa >अहन्‌ (८.२.६८) तथा रूपरात्रिरथन्तरेषु वाच्यम्‌ (वा ४८४७) ।</p>\n";
     display(0);
    }
    else 
    {
    $text = one(array("ahan","Ahan","AhAn"),array("ahar","Ahan","AhAr@"),0);
        echo "<p class = sa >ro'supi (".link_sutra("8.2.69").") :</p>\n";
     echo "<p class = sa >रोऽसुपि (८.२.६९) :</p>\n";
     display(0);
    }
}
if ( (arr($text,'/[aA][h][aA][n]$/')||arr($text,'/[aA][h][aA][n][+]/') )&& !sub($noahan,blank(0),blank(0),0) && (in_array($so,$sup)) && $pada === "pada")
{
     echo "<p class = sa >By ahan (".link_sutra("8.2.68").") :</p>\n";
     echo "<p class = hn >This creates a nipAta for ahan, and shows that the na lopaH prAtipadikAntasya doesn't apply here.</p>\n";
     echo "<p class = sa >अहन्‌ (८.२.६८) :</p>\n";
     echo "<p class = hn >अनेन नलोपाभावं निपात्यते ।</p>\n";
     display(0);
    $text = one(array("ahan","Ahan","AhAn"),array("ahar@","Ahar@","AhAr@"),0); $R=array_merge($R,array(1));
     echo "<p class = sa >By ahan (".link_sutra("8.2.68").") :</p>\n";
     echo "<p class = hn >This mandates rutva here.</p>\n";
     echo "<p class = sa >अहन्‌ (८.२.६८) :</p>\n";
     echo "<p class = hn >अनेन रुर्विधेयः ।</p>\n";
   display(0); 
}
/* samaH suTi (8.3.5) */ // have used @ as mark of anunAsika u of ru. 
if (sub(array("sam"),array("s"),array("k"),0))
{
$text = three(array("sam"),array("s"),array("k"),array("saMr@"),array("s"),array("k"),0);
$text = one(array("Mr@"),array("!r@"),1); $R=array_merge($R,array(1));
echo "<p class = sa >By samaH suTi (".link_sutra("8.3.5")."), atrAnunAsikaH pUrvasya tu vA (".link_sutra("8.3.2").") and anunAsikAtparo'nusvAraH (".link_sutra("8.3.4").") :</p>\n";
echo "<p class = sa >समः सुटि (८.३.५), अत्रानुनासिकः पूर्वस्य तु वा (८.३.२) तथा अनुनासिकात्परोऽनुस्वारः (८.३.४) :</p>\n";
display(0); 
}
/* khyAYAdeze na (vA 1591) */
if (sub(array("pum"),array("Ky"),blank(0),0))
{
echo "<p class = sa >By khyAYAdeze na (vA 1591) :</p>\n";
echo "<p class = sa >ख्याञादेशे न (वा १५९१) :</p>\n";
display(0); $pum = 1; // 0 - pumaH khayyampare is mandatory. 1 - pumaH khayyampare is optional.
} else { $pum = 0; }
/* pumaH khayyampare (8.3.6) */
$am = array("a","A","i","I","u","U","f","F","x","X","e","o","E","O","h","y","v","r","l","Y","m","N","R","n");
if(sub(array("pum"),prat('Ky'),$am,0) && $pum === 0)
{
$text = three(array("pum"),prat('Ky'),$am,array("puMr@"),prat('Ky'),$am,0);
$text = one(array("Mr@"),array("!r@"),1);
echo "<p class = sa >By pumaH khayyampare (".link_sutra("8.3.6")."), atrAnunAsikaH pUrvasya tu vA (".link_sutra("8.3.2").") and anunAsikAtparo'nusvAraH (".link_sutra("8.3.4").") :</p>\n";
echo "<p class = sa >पुमः खय्यम्परे (८.३.६), अत्रानुनासिकः पूर्वस्य तु वा (८.३.२) तथा अनुनासिकात्परोऽनुस्वारः (८.३.४) :</p>\n";
display(0);
} 
/* nazChavyaprazAn (8.3.7) */
if (sub(array("n"),prat('Cv'),$am,0) && arr($text,'/[n][+]['.pc('Cv').']/') && $pada ==="pada")
{
$text = three(array("n"),prat('Cv'),$am,array("Mr@"),prat('Cv'),$am,0); $R=array_merge($R,array(1));
$text = one(array("praSAMr@"),array("praSAn"),0);
$text = one(array("Mr@"),array("!r@"),1);
echo "<p class = sa >By nazChavyaprazAn (".link_sutra("8.3.7")."), atrAnunAsikaH pUrvasya tu vA (".link_sutra("8.3.2").") and anunAsikAtparo'nusvAraH (".link_sutra("8.3.4").") :</p>\n";
echo "<p class = sa >नश्छव्यप्रशान्‌ (८.३.७), अत्रानुनासिकः पूर्वस्य तु वा (८.३.२) तथा अनुनासिकात्परोऽनुस्वारः (८.३.४)  :</p>\n";
display(0);
} 
/* nRUnpe (8.3.10) */
if (sub(array("nFn"),array("p"),blank(0),0)  && $pada ==="pada")
{
$text = two(array("nFn"),array("p"),array("nFMr@"),array("p"),0);
$text = one(array("Mr@"),array("!r@"),1);
echo "<p class = sa >By nRUnpe (".link_sutra("8.3.10")."), atrAnunAsikaH pUrvasya tu vA (".link_sutra("8.3.2").") and anunAsikAtparo'nusvAraH (".link_sutra("8.3.4").") </p>\n";
echo "<p class = sa >नॄन्पे (८.३.१०), अत्रानुनासिकः पूर्वस्य तु वा (८.३.२) तथा अनुनासिकात्परोऽनुस्वारः (८.३.४) : </p>\n";
display(0);
}
/* svatavAn pAyau (8.3.11) */
if (sub(array("svatavAn"),array("pAyu"),blank(0),0)  && $pada ==="pada")
{
$text = two(array("svatavAn"),array("pAyu"),array("svatavA! "),array("pAyu"),0);
echo "<p class = sa >By svatavAn pAyau (".link_sutra("8.3.11")."), atrAnunAsikaH pUrvasya tu vA (".link_sutra("8.3.2").")</p>\n";
echo "<p class = sa >स्वतवान्पायौ (८.३.११), अत्रानुनासिकः पूर्वस्य तु वा (८.३.२) : </p>\n";
display(0);
}
/* kAnAmreDite (8.3.12) */ 
if (sub(array("kAn"),array("kAn"),blank(0),0))
{
$text = two(array("kAn"),array("kAn"),array("kAMr@"),array("kAn"),0); $R=array_merge($R,array(1));
$text = one(array("Mr@"),array("!r@"),1);
echo "<p class = sa >By kAnAmreDite (".link_sutra("8.3.12")."), atrAnunAsikaH pUrvasya tu vA (".link_sutra("8.3.2").") and anunAsikAtparo'nusvAraH (".link_sutra("8.3.4").") :</p>\n";
echo "<p class = sa >कानाम्रेडिते (८.३.१२), अत्रानुनासिकः पूर्वस्य तु वा (८.३.२) तथा अनुनासिकात्परोऽनुस्वारः (८.३.४)  :</p>\n";
display(0);
}
/* ato roraplutAdaplute (6.1.113) */
if (sub(array("ar@"),array("a"),blank(0),0))
{
    $text = two(array("ar@"),array("a"),array("au"),array("a"),0);
    echo "<p class = sa >By ato roraplutAdaplute (".link_sutra("6.1.113").") :</p>\n";
    echo "<p class = sa >अतो रोरप्लुतादप्लुते (६.१.११३) :</p>\n";
    display (0);
}
/* hazi ca (6.1.114) */
if (sub(array("a"),array("r@"),prat('hS'),0))
{
    $text = three(array("a"),array("r@"),prat('hS'),array("a"),array("u"),prat('hS'),0);
    echo "<p class = sa >By hazi ca (".link_sutra("6.1.114").") :</p>\n";
    echo "<p class = sa >हशि च (६.१.११४) :</p>\n";
    display (0);
}
/* ekaH pUrvaparayoH (6.1.84) */ // This is the adhikArasUtra. No vidhi mentioned.
// The following vArtikas are exception to AdguNaH. Otherwise after joining, it will be difficult to identify. So coded here.
/* akSAdUhinyAmupasaMkhyAnam (vA 3604) */
/* svAdireriNoH (vA 3606) */
/*prAdUhoDhoDyeSaiSyeSu (vA 3605) */
/* Rte ca tRtIyAsamAse (vA 3607) */
/* pravatsatarakambalavasanadazArNAnAmRNe (vA 3608-9) */
$v1 = array('akza','sva','pra','pra','pra','pra','suKa','pra','vatsatara','kambala','vasana','daSa','fRa','sva');
$v2 = array('Uhin','ir','Uh','UQ','ez','ezy','ft','fR','fR','fR','fR','fR','fR','Ir');
$v3 = array('akz','sv','pr','pr','pr','pr','suK','pr','vatsatar','kambal','vasan','daS','fR','sv');
$v4 = array('OhiR','Er','Oh','OQ','Ez','Ezy','Art','ArR','ArR','ArR','ArR','ArR','ArR','Er');
if (sub($v1,$v2,blank(0),0) && $pada === "pada")
{
$text = two($v1,$v2,$v3,$v4,0);
echo "<p class = sa >Applying the following vArtikas : akSAdUhinyAmupasaMkhyAnam (vA 3604), svAdireriNoH (vA 3606), prAdUhoDhoDyeSaiSyeSu (vA 3605), Rte ca tRtIyAsamAse (vA 3607), pravatsatarakambalavasanadazArNAnAmRNe (vA 3608-9)</p>\n";
echo "<p class = sa >अक्षादूहिन्यामुपसंख्यानम्‌ (वा ३६०४), स्वादेरेरिणोः (वा ३६०६), प्रादूहोढोढ्येषैष्येषु (वा ३६०५), ऋते च तृतीयासमासे (वा ३६०७), प्रवत्सतरकम्बलवसनदशार्णानामृणे (वा ३६०८-०९)</p>\n";
display(0);
}
/* etyedhatyuThsu (6.1.89) */ 
if (sub(array("a","A"),array("eti","ezi","emi","etu","Et","EtAm","EH","Es","Etam","Eta","Eva","Ema","ezyati","Ezyati","etA","eD","ED","Uh"),blank(0),0))
{
    $text = two (array("a","A"),array("eti","ezi","emi","etu","Et","EtAm","EH","Es","Etam","Eta","Eva","Ema","ezyati","Ezyati","etA","eD","ED","Uh"),blank(2),array("Eti","Ezi","Emi","Etu","Et","EtAm","EH","Es","Etam","Eta","Eva","Ema","Ezyati","Ezyati","EtA","ED","ED","Oh"),0);
    echo "<p class = sa >By etyedhatyuThsu (".link_sutra("6.1.89").") :</p>\n";
    echo "<p class = sa >एत्येधत्यूठ्सु (६.१.८९) :</p>\n";
    display(0);
}
/* AdguNaH (6.1.87) */
$forguna = array("i","I","u","U"); // the vowels which take guNa prakriyA. iko guNavRddhi.
$rep = array("e","e","o","o"); // replacement of guNa.
if (sub($aa,$forguna,blank(0),0))
{
$text = two($aa,$forguna,blank(2),$rep,0);
echo "<p class = sa >By AdguNaH (".link_sutra("6.1.87").") :</p>\n";
echo "<p class = sa >आद्गुणः (६.१.८७) :</p>\n";
display(0);
}
/* uraNraparaH (1.1.51) */ 
$forguna = array("f","F","x","X");
$rep = array("ar","ar","al","al");
if (sub($aa,$forguna,blank(0),0))
{
$text = two($aa,$forguna,blank(2),$rep,0);
echo "<p class = sa >By AdguNaH (".link_sutra("6.1.87").") and uraNraparaH (".link_sutra("1.1.51").") :</p>\n";
echo "<p class = sa >आद्गुणः (६.१.८७) तथा उरण्रपरः (१.१.५१) :</p>\n";
display(0);
}
/* eGi pararUpam (6.1.94) */ // Added it here because it is exception to vRddhireci.
for($i=0;$i<count($akarantaupasarga);$i++) // $akarantaupasarga - see function.php
{
    $a_upa_without_a[$i] = substr($akarantaupasarga[$i],0,count(str_split($akarantaupasarga[$i]))-1); 
}
if (sub($akarantaupasarga,prat('eN'),blank(0),0) && arr($text,'/[I][y]/') && in_array($first,$akarantaupasarga))
{
$text = two($akarantaupasarga,prat('eN'),$a_upa_without_a,prat('eN'),1);
echo "<p class = sa >By eGi pararUpam (".link_sutra("6.1.94").") and anuvRtti of vA supi :</p>\n";
echo "<p class = sa >एङि पररूपम्‌ (६.१.९४) तथा वा सुपि की अनुवृत्ति :</p>\n";
display(0);
}
elseif (sub($akarantaupasarga,prat('eN'),blank(0),0) && in_array($first,$akarantaupasarga))
{
$text = two($akarantaupasarga,prat('eN'),$a_upa_without_a,prat('eN'),0);
echo "<p class = sa >By eGi pararUpam (".link_sutra("6.1.94").") :</p>\n";
echo "<p class = sa >एङि पररूपम्‌ (६.१.९४) :</p>\n";
display(0);
}
/* eve cAniyoge (vA 3631) */
if (sub($aa,array("eva"),blank(0),0))
{
$text = two($aa,array("eva"),blank(2),array("eva"),1);
echo "<p class = sa >By eve cAniyoge (vA 3631) :</p>
    <p class = hn >N.B. that the optionality applies only in case the eva is used for avadhAraNa.</p>\n" ;
echo "<p class = sa >एवे चावधारणे (वा ३६३१) :</p>
    <p class = hn >जब 'एव' अवधारण के अर्थ में प्रयुक्त हुआ हो, तभी यह नियम लागू होता है ।</p>\n" ;
display(0);
}
/* vA supyapizaleH (6.1.92) */ // Not possible to know what is nAmadhAtu and what is not. Therefore added as comments. Not coded.
/* aco'ntyAdi Ti (1.1.64) */ // a saJjJAsUtra. No vidhi mentioned.
/* otvoShThayoH samAse vA (vA 3634) */
if (sub($aa,array("otu","ozW"),blank(0),0))
{
$text = two($aa,array("otu","ozW"),blank(2),array("otu","ozW"),1);
echo "<p class = sa >By otvoShThayoH samAse vA (vA 3634) :</p>
    <p class = hn >If what you entered is a samAsa, it will be optionally converted. Otherwise ignore the pararUpa form.</p>\n";
echo "<p class = sa >ओत्वोष्ठ्योः समासे वा (वा ३६३४) :</p>
    <p class = hn >यदि समास है तभी यह नियम लागू होगा । अन्यथा पररूप वाला रूप नहीं बनेगा ।</p>\n";
display(0);
}
/* nAmreDitasyAntasya tu vA (6.1.99), tasya paramAmreDitam (8.1.2) */
for($i=0;$i<count($text);$i++)
{
    $tttt = explode("at",$text[$i]); // exploding the word to check whether it is AmreDita. e.g. paTatpaTat will be split - paT. at, paT, at.
    if (count($tttt) > 1 )
    {
    if ($tttt[0] === $tttt[1]) // e.g. paT - paT.
    {
        $amredita = 1; // 0 - no AmreDita. 1 - AmreDita.
        break;
    }
    else
    {
        $amredita = 0;
    }
    }
    else
    {
        $amredita = 0;
    }
}
if ( $amredita === 1 && $start === 1)
{
$text = two(array("at"),array("iti"),array("a"),array("iti"),1); // e.g. paTatpaTiti, paTatpaTatiti.
echo "<p class = sa >nAmreDitasyAntasya tu vA (".link_sutra("6.1.99")."), tasya paramAmreDitam (".link_sutra("8.1.2").") :</p>
    <p class = hn >When the 'at' happens to be at the end of an onaematopic word and it is followed by 'iti', its 'Ti' is elided. This rule doesn't apply on single vowel words like 'zrat'.</p>\n";
echo "<p class = sa >नाम्रेडितस्यान्तस्य तु वा (६.१.९९), तस्य परमाम्रेडितम्‌ (८.१.२) :</p>
    <p class = hn >आम्रेडित में यह नियम लागू वैभाषिक तौर से होता है, और अन्तिम तकार का होता है । एकाच्‌ शब्दों में, जैसे कि श्रत्‌, यह लागू नहीं होता ।</p>\n";
display(0);   
}
/* avyaktAnukaraNasyAta itau (6.1.98) */
$ff = preg_split('/[aAiiuUfFxXeEoO]/',$first); // checking whether the first word has more than two vowels.
if (sub(array("at"),array("iti"),blank(0),0) && $amredita === 0 && $start ===1 && count($ff)>2)
{
$text = two($array("at"),array("iti"),blank(1),array("iti"),0);
echo "<p class = sa >By avyaktAnukaraNasyAta itau (".link_sutra("6.1.98").") :</p>
    <p class = hn > When the 'at' happens to be at the end of an onaematopic word and it is followed by 'iti', its 'Ti' is elided. This rule doesn't apply on single vowel words like 'zrat'.</p>\n";
echo "<p class = sa >अव्यक्तानुकरणस्यात इतौ (६.१.९८) :</p>
    <p class = hn >अव्यक्तानुकरण में प्रयुक्त हुए शब्द के अत्‌ के बाद में यदि इति हो तो यह नियम लागू होता है । एकाच्‌ शब्दों में, जैसे कि श्रत्‌, यह लागू नहीं होता ।</p>\n";
display(0);
}
/* vRddhireci (6.1.88) */
$aa = array("a","A"); // a and A
$vrrdhi = array("E","O","E","O","E","O","E","O"); // vRddhi of 'ec'.
if (sub($aa,prat("ec"),blank(0),0) )
{
$text = one(array("a+e","a+E","a+o","a+O","A+e","A+E","A+o","A+O"),array("E+","E+","O+","O+","E+","E+","O+","O+"),0);
$text = two($aa,prat('ec'),blank(2),$vrrdhi,0);
echo "<p class = sa >By vRddhireci (".link_sutra("6.1.88").") :</p>\n";
echo "<p class = sa >वृद्धिरेचि (६.१.८८) :</p>\n";
display(0); 
$vriddhireci=1; // 0 - this sUtra has not applied. 1 - this sUtra has appied.
} else {$vriddhireci=0; }
/* udaH sthAstambhvoH pUrvasya (8.1.61) */
if(sub(array("ud","ut"),array("sTA","stam"),blank(0),0))
{
$text = two(array("ud","ut"),array('sTA','stam'),array("ud","ut"),array('TTA','Ttam'),0);
echo "<p class = sa >By udaH sthAstambhvoH pUrvasya (".link_sutra("8.1.61").") :</p>\n";
echo "<p class = sa >उदः स्थास्तम्भ्वोः पूर्वस्य (८.१.६१) :</p>\n";
display(0);
}
/* saMhitAyAm (6.1.72) */ 
// This is adhikArasUtra. Nothing to code here.
/* Che ca (6.1.73) */
if (sub($hrasva,array("C"),blank(0),0))
{
$text = two(array("a","i","u","f","x"),array("C"),array("at","it","ut","ft","xt"),array("C"),0);
echo "<p class = sa >By Che ca (".link_sutra("6.1.73").") :</p>\n";
echo "<p class = sa >छे च (६.१.७३) :</p>\n";
display(0);

}
/* AGmAGozca (6.1.74) */
if (($first === "A" || $first === "mA") && $start===1)
{
$text = two(array("A"),array("C"),array("At"),array("C"),0);
echo "<p class = sa >By AGmAGozca (".link_sutra("6.1.74").") :</p>\n";
echo "<p class = sa >आङ्माङोश्च (६.१.७४) :</p>\n";
display(0);
}
/* dIrghAt (6.1.75) and padAntAdvA (6.1.76) */
if (sub($dirgha,array("C"),blank(0),0) && $pada === "pratyaya" && $start === 1) // for $dirgha see function.php
{
$text = two($dirgha,array("C"),array("At","It","Ut","Ft","Xt","et","Et","ot","Ot"),array("C"),0);
echo "<p class = sa >By dIrghAt (".link_sutra("6.1.75").") padAntAdvA (".link_sutra("6.1.76").") :</p>
    <p class = hn >N.B.: The 'tuk' Agama is optional in case the preceding dIrgha vowel is at the padAnta. Otherwise, it is mandatory to add.</p>\n";
echo "<p class = sa >दीर्घात्‌ (६.१.७५) तथा पदान्ताद्वा (६.१.७६) :</p>
    <p class = hn >यदी दीर्घ स्वर पदान्त में हो तब तुक्‌ आगम लगाना पाक्षिक है । अन्यथा यह आवश्यक है ।</p>\n";
display(0);
}
if (sub($dirgha,array("C"),blank(0),0) && $pada === "pada" && $start ===1)
{
$text = two($dirgha,array("C"),$dirghata,array("C"),1);
echo "<p class = sa >By dIrghAt (".link_sutra("6.1.75").") padAntAdvA (".link_sutra("6.1.76").") :</p>
    <p class = hn >N.B.: The 'tuk' Agama is optional in case the preceding dIrgha vowel is at the padAnta. Otherwise, it is mandatory to add.</p>\n";
echo "<p class = sa >दीर्घात्‌ (६.१.७५) तथा पदान्ताद्वा (६.१.७६) :</p>
    <p class = hn >यदी दीर्घ स्वर पदान्त में हो तब तुक्‌ आगम लगाना पाक्षिक है । अन्यथा यह आवश्यक है ।</p>\n";
display(0);
}
/* yasmAtpratyayavidhistadAdi pratyaye'Ggam (2.4.13) */
// Pending to code.
/* creating a + elision for two ++ simultaneously. */
$text = one(array("++"),array("+"),0);

// adding 1 to $start. All sUtras where $start===1 has been mentioned in the condition won't apply in the second loop.
$start++;
}
while ($text !== $original); // looping till all the applicable sUtras of sapAdasaptAdhyAyI are exhausted. i.e. the original ($original) and the output ($text) are the same.



/* tripAdI functions */
// tripAdI functions are always applied in serial manner because of 'pUrvatrAsiddham'. Therefor there is no possibility of a sUtra being applied out of turn (Except explicitly mentioned in grammar books.)
// Therefore we have kept them in their order as per aSTAdhyAyI. Where there is violation, we have placed a patch.

/* na NisambuddhyoH (8.2.8) */ 
if (arr($text,'/[n][+]$/')  && ( in_array($so,array("Ni")) || (in_array($so,array("su!")) && $sambuddhi===1)) && $bham===0 && $shi===0 && $ikoci===0 )
{
    echo "<p class = sa >By na NisambuddhyoH (".link_sutra("8.2.8").") :</p>\n";
    echo "<p class = sa >न ङिसम्बुद्ध्योः (८.२.८) :</p>\n";
    display(0); 
    $Gisambu=1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else {$Gisambu=0; }
/* patches for maghavA bahulam */
if (sub(array("maGavAn"),blank(0),blank(0),0) && arr($text,'/[v][a][n][+]$/') && in_array($so,$sup) && $bham===0 && $shi===0 && $ikoci===0 && $Gisambu===0 && $sambuddhi===0)
{
    $text = two(array("maGavAn"),array("+"),array("maGavA"),array("+"),0);
    echo "<p class = sa >By na lopaH prAtipadikAntasya (".link_sutra("8.2.7").") :</p>\n";
    echo "<p class = sa >न लोपः प्रातिपदिकान्तस्य (८.२.७) :</p>\n";
    display(0);        
}
/*if (sub(array("maGavan","Ahar@","Ahan"),blank(0),blank(0),0) && in_array($so,$sup) && $bham===0 && $shi===0 && $ikoci===0 && $Gisambu===0 && $sambuddhi===0)
{
    $text = two(array("maGavan","Ahar@","Ahan"),array("+"),array("maGavAn","AhAr@","AhAn"),array("+"),0);
    echo "<p class = sa >By sarvanAmasthAne cAsambuddhau (".link_sutra("6.4.8").") :</p>\n";
    echo "<p class = hn >Because of bahulagrahaNa in maghavan, saMyogAntasya lopaH is not asiddha here. :</p>\n";
    echo "<p class = sa >सर्वनामस्थाने चासम्बुद्धौ (६.४.८)  :</p>\n";
    echo "<p class = hn >मघवा बहुलं के बहुलग्रहण के कारण, संयोगान्तलोप असिद्ध नहीं है । </p>\n";
    display(0);        
}*/
/* NAvuttarapade pratiSedho vaktavyaH (vA 4785) */
// Pending because involves samAsa. Out of purview right now.

/* sambuddhau napuMsakAnAM nalopo vA vAcyaH (vA 4786) */
$napumsakanalopa=array(); // when there is nalopa in napuMsakaliGga, 1 is added in the array. useful for na lopaH prAtipadikAntasya.
if (arr($text,'/[n][+]$/') && in_array($so,$sup) && $bham===0 && $shi===0 && $ikoci===0 && $so==="su!" && $sambuddhi===1 && $gender==="n")
{
    $text = two(array("n"),array("+"),array(""),array("+"),1);
    echo "<p class = sa >By sambuddhau napuMsakAnAM nalopo vA vAcyaH (vA 4786) :</p>\n";
    echo "<p class = sa >सम्बुद्धौ नपुंसकानां नलोपो वा वाच्यः (वा ४७८६) :</p>\n";
    display(0); 
    $napumsakanalopa=array_merge($napumsakanalopa,array(1));
}
/* na lopaH prAtipadikAntasya (8.2.7) */
// parame vyoman is pending. Vedic in nature. 
$nalopa=0; // 0 - no nalopa, is default. 1 - there is nalopa. Useful to display nalopaH supsvarasaJjJAtugvidhiSu kRti (8.2.2).
if (arr($text,'/[n][+]$/') && $nopadha===1 && !in_array(1,$napumsakanalopa) )
{
    $text = two(array("n"),array("+"),array(""),array("+"),0);
    echo "<p class = sa >By na lopaH prAtipadikAntasya (".link_sutra("8.2.7").") :</p>\n";
    echo "<p class = sa >न लोपः प्रातिपदिकान्तस्य (८.२.७) :</p>\n";
    display(0);        
    $nalopa=1;
}
elseif (arr($text,'/[n][+]$/') && in_array($so,$sup) && $pada="pada" && $shi===0 && $ikoci===0 && $Gisambu===0  && !in_array(1,$napumsakanalopa))
{
    $text = two(array("n"),array("+"),array(""),array("+"),0);
    echo "<p class = sa >By na lopaH prAtipadikAntasya (".link_sutra("8.2.7").") :</p>\n";
    echo "<p class = sa >न लोपः प्रातिपदिकान्तस्य (८.२.७) :</p>\n";
    display(0);        
    $nalopa=1;
}
if (arr($text,'/[n][+]['.pc('hl').']/') && ( $astana===1 || $Satcatur===1)  && !in_array(1,$napumsakanalopa))
{
    $text = two(array("n"),array("+"),array(""),array("+"),0);
    echo "<p class = sa >By na lopaH prAtipadikAntasya (".link_sutra("8.2.7").") :</p>\n";
    echo "<p class = sa >न लोपः प्रातिपदिकान्तस्य (८.२.७) :</p>\n";
    display(0);        
    $nalopa=1;
}
elseif (arr($text,'/[n][+]['.pc('hl').']/')  && !arr($text,'/[n][+][e]/') && ($pada==="pada"|| $so==="Am" ) && in_array($so,$sup) && $bham===0 && $shi===0 && $ikoci===0 && $Gisambu===0  && !in_array(1,$napumsakanalopa))
{
    $text = two(array("n"),array("+"),array(""),array("+"),0);
    echo "<p class = sa >By na lopaH prAtipadikAntasya (".link_sutra("8.2.7").") :</p>\n";
    echo "<p class = sa >न लोपः प्रातिपदिकान्तस्य (८.२.७) :</p>\n";
    display(0);        
    $nalopa=1;
}
/* nalopaH supsvarasaJjJAtugvidhiSu kRti (8.2.2) */
if ($nalopa===1)
{
    echo "<p class = pa >By nalopaH supsvarasaJjJAtugvidhiSu kRti (".link_sutra("8.2.2").") :</p>\n";
    echo "<p class = pa >नलोपः सुप्स्वरसञ्ज्ञातुग्विधिषु कृति (८.२.२) :</p>\n";
    display(0);
}
/* patch for udan Adeza of udaka */
if (sub(array("udan","Asan"),array("+"),array("ByAm","Bir","Byar","su"),0))
{
    $text = two(array("udan","Asan"),array("+"),array("uda","Asa"),array("+"),0);
    echo "<p class = sa >By na lopaH prAtipadikAntasya (".link_sutra("8.2.7").") :</p>\n";
    echo "<p class = sa >न लोपः प्रातिपदिकान्तस्य (८.२.७) :</p>\n";
    display(0);        
}
/* nazervA (8.2.63) */
if (arr($text,'/[n][a][S][+]/') && $pada==="pada")
{
    $text = one(array("naS+",),array("nak+",),1);
    echo " <p class = sa >By nazervA (".link_sutra("8.2.63").") :</p>\n";
    echo " <p class = sa >नशेर्वा (८.२.६३) :</p>\n";
    display(0);
}
/* kvinpratyayasya kuH (8.2.62) defining */
if (sub(array("S","z","s"),array("+"),blank(0),0) && ( $kvin===1 || ($kvip===1 && $fo==="dfS") ) && $pada==="pada" && $Asarva===1 && $goanc===0)
{
    $kvinku=1; // 0 - not eligible for kvinpratyasya kuH. 1 - eligible for kvinpratyayasya kuH.
}
elseif(sub($hl,array("+"),blank(0),0) && ( $kvin===1 || ($kvip===1 && $fo==="dfS") ) && $pada==="pada" && !sub(array("S","z","s"),array("+"),blank(0),0) && $goanc===0)
{
    $kvinku=1;
}
else
{
    $kvinku=0;
}
/*if (sub(array("S","z","s"),array("+"),blank(0),0) && ( $kvin===1 || ($kvip===1 && $fo==="diS") ) && $pada==="pada" && $Asarva===1)
{
    $text1 = $text;        
    $text2 = two(array("S","z","s"),array("+"),array("K","k","k"),array("+"),0); // check for z and s.
    $text = $text2;
    echo "<p class = pa >There is difference of opinion here. According to one school, vrazca.. applies and z->S->g->g,k is done. According to the other school, kvinpratyayasya kuH is apavAda of Satva. Therefore z->K->K,k happens.</p>\n";
    echo "<p class = pa >कुत्वस्यासिद्धत्वाद्‌ 'व्रश्च..' इति षः, तस्य जश्त्वे डः । तस्य कुत्वेन गः । तस्य चर्त्वेन पक्षे कः । तादृग्‌, तादृश्‌ ॥ 'षत्वापवादत्वात्‌ कुत्वेन खकारः' इति कैयटहरदत्तादिमते तु चर्त्वाभावपक्षे ख एव श्रूयते, न तु गः, जश्त्वं प्रति कुत्वस्य असिद्धत्वात्‌ ॥</p>\n";
    display(0); 
    $text= $text1;
    $kvinku=1;    
} 
elseif (sub($hl,array("+"),blank(0),0) && ( $kvin===1 || ($kvip===1 && $fo==="diS") ) && $pada==="pada" && !sub(array("S","z","s"),array("+"),blank(0),0))
{   
    $text = two($cu,array("+"),$ku,array("+"),0);
    $text = two($Tu,array("+"),$ku,array("+"),0);
    $text = two($tu,array("+"),$ku,array("+"),0);
    $text = two($pu,array("+"),$ku,array("+"),0);
    $text = two(array("h"),array("+"),array("g"),array("+"),0);
    echo "<p class = sa >By kvinpratyayasya kuH (".link_sutra("8.2.62").") :</p>\n";
    echo "<p class = sa >क्विन्प्रत्ययस्य कुः (८.२.६२) :</p>\n";
    display(0); $kvinku=1;
}
else { $kvinku=0; } 
*/ // bracketed. Output seems fine after bracketing. Kept in case we need it in future.

/* vrazcabhrasjamRjayajarAjabhrAjacChazAM ca (8.2.35) */
// TubhrAjR dIptau and ejR bhejR bhrAjR dIptau are different. This is pending to code.
// parau vrajeH SaH padAnte (u 217) pending. 
$vrasca = array("vfSc","sfj","mfj","yaj","rAj","BrAj","devej","parivrAj","Bfj","ftvij");
$vrashca = array("vfSz","sfz","mfz","yaz","rAz","BrAz","devez","parivrAz","Bfz","ftviz");
if ( (sub($vrasca,array("+"),prat("Jl"),0) ||  ( sub($vrasca,array("+"),blank(0),0) && $pada==="pada")) && $_GET['cond1_9_3']!=="2" && ($kvinku===0 || ($fo==="asfj" && in_array($so,array("su!","am")))) )
{
    if (sub($vrasca,prat('Jl'),blank(0),0))
    {
    $text = two($vrasca,prat('Jl'),$vrashca,prat("Jl"),0);
    }
    else 
    {
    $text = one($vrasca,$vrashca,0);    
    }
    echo "<p class = sa >By vrazcabhrasjasRjamRjayajarAjabhrAjacChazAM SaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >व्रश्चभ्रस्जसृजमृजयजराजभ्राजच्छशां षः (८.२.३५) :</p>\n";
    display(0); 
    $vras1 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $vras1 = 0; }
if (arr($text,'/[CS]$/'))
{ 
    if ($dhatu===1)
    {
        if ($kvinku===1 && $Asarva===1 && arr(array($fo),'/[S]$/'))
        {
            $text = last(array("C","S"),array("z","z"),0);
        }
        else
        {
            $text = last(array("C","S"),array("z","z"),0);                    
        }
    echo "<p class = sa >By vrazcabhrasjasRjamRjayajarAjabhrAjacChazAM SaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >व्रश्चभ्रस्जसृजमृजयजराजभ्राजच्छशां षः (८.२.३५) :</p>\n";
    }
    else
    {
        if ($kvinku===1 && $Asarva===1 &&  arr(array($fo),'/[S]$/'))
        {
//            $text = last(array("C","S"),array("z","z"),1);                                
            $text = last(array("C","S"),array("z","z"),0);                                
        }
        else
        {
//            $text = last(array("C","S"),array("z","z"),1);                    
                        $text = last(array("C","S"),array("z","z"),0);                    
        }
    echo "<p class = sa >By vrazcabhrasjasRjamRjayajarAjabhrAjacChazAM SaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = hn >Some people hold that there is anuvRtti of 'dhAtoH' here. In that case Satva won't happen. It is optional.</p>\n";
    echo "<p class = sa >व्रश्चभ्रस्जसृजमृजयजराजभ्राजच्छशां षः (८.२.३५) :</p>\n";
    echo "<p class = hn >केचित्तु व्रश्चादिसूत्रे 'दादेर्धातोः इति सूत्रात्‌ 'धातोः' इत्यनुवर्तयन्ति । तन्मते षत्वं न भवति ।</p>\n";
    }
    display(0); 
    $vras3 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $vras3 =0; }
if (arr($text,'/[CS][+]/') && $pada === "pada")
{
    if ($dhatu===1)
    {
        if ($kvinku===1 && $Asarva===1 && arr(array($fo),'/[S]$/'))
        {
            $text = two(array("C","S"),array("+"),array("z","z"),array("+"),0);
        }
        else
        {
            $text = two(array("C","S"),array("+"),array("z","z"),array("+"),0);
        }
    echo "<p class = sa >By vrazcabhrasjasRjamRjayajarAjabhrAjacChazAM SaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >व्रश्चभ्रस्जसृजमृजयजराजभ्राजच्छशां षः (८.२.३५) :</p>\n";
    }
    else
    {
        if ($kvinku===1 && $Asarva===1 && arr(array($fo),'/[S]$/'))
        {
            $text2=$text;
            $text=$text1;
//           $text = two(array("C","S"),array("+"),array("z","z"),array("+"),1);
             $text = two(array("C","S"),array("+"),array("z","z"),array("+"),0);
            $text = array_merge($text,$text2);
            $text = array_unique($text);
            $text1=array(); $text2=array();                        
        }
        else
        {
//           $text = two(array("C","S"),array("+"),array("z","z"),array("+"),1);
             $text = two(array("C","S"),array("+"),array("z","z"),array("+"),0);
        }
    echo "<p class = sa >By vrazcabhrasjasRjamRjayajarAjabhrAjacChazAM SaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = hn >Some people hold that there is anuvRtti of 'dhAtoH' here. In that case Satva won't happen. It is optional.</p>\n";
    echo "<p class = sa >व्रश्चभ्रस्जसृजमृजयजराजभ्राजच्छशां षः (८.२.३५) :</p>\n";
    echo "<p class = hn >केचित्तु व्रश्चादिसूत्रे 'दादेर्धातोः इति सूत्रात्‌ 'धातोः' इत्यनुवर्तयन्ति । तन्मते षत्वं न भवति ।</p>\n";
    }
    display(0); 
    $vras4 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $vras4 = 0; } 
/* nimittApAye naimittikasyApyapAyaH (paribhASA) */ 
if (($vras1===1 && sub(array("vfSz"),blank(0),blank(0),0)) || (($vras3 ===1 || $vras4 ===1) && sub(array("cz"),blank(0),blank(0),0)))
{
    $text = one(array("vfSz"),array("vfsz"),0);
    $text = one(array("cz"),array("z"),0);
    echo "<p class = sa >By nimittApAye naimittikasyApyapAyaH (paribhASA) :</p>\n";
    echo "<p class = sa >निमित्तापाये नैमित्तिकस्याप्यपायः (परिभाषा) :</p>\n";
    display(0);
}
/* vivikz patch for overcoming skoH saMyogAdyorante ca */
if (sub(array("vivikz"),array("+"),blank(0),0) && $pada==='pada')
{
    $text = two(array("vivikz"),array("+"),array("viviS"),array("+"),0);
    echo "<p class = sa >As katva is asiddha to skoH saMyogAdyorante ca, saMyogAntalopa happens. 'S' is changed to 's' by nimittApAye naimittikasyApyapAyaH. :</p>\n";
    echo "<p class = sa >स्कोः संयोगाद्योरन्ते च इति कलोपे प्राप्ते कत्वस्य असिद्धत्वात्‌ संयोगान्तलोपः । सकारस्य लोपे 'निमित्ताभावे नैमित्तिकस्याप्यपायः' इति षत्वमपि निवर्तते । :</p>\n";
    display(0);    
    $text = two(array("viviS"),array("+"),array("viviz"),array("+"),0);
    echo "<p class = sa >By vrazcabhrasjasRjamRjayajarAjabhrAjacChazAM SaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >व्रश्चभ्रस्जसृजमृजयजराजभ्राजच्छशां षः (८.२.३५) :</p>\n";
    display(0);    
}
/* rakS, takS patch to bar application of skoH saMyogAdyorante ca */
if (sub(array("takz","rakz"),array("+"),blank(0),0) && $pada === "pada" && $Nyanta===1)
{
    echo "<p class = pa >skoH saMyogAdyorante ca doesn't apply here because of sthAnivadbhAva of Nilopa.</p>\n";
    echo "<p class = hn >'pUrvatrAsiddhe na sthAnivat' (vA 433) doesn't apply here, because it is overruled by 'tasya doSaH saMyogAdilopalatvaNatveSu (vA 440).</p>\n";
    echo "<p class = pa >तक्षिरक्षिभ्यां ण्यन्ताभ्यां क्विपि तु 'स्कोः..' इति न प्रवर्तते । णिलोपस्य स्थानिवद्भावात्‌ ।</p>\n";
    echo "<p class = hn >'पूर्वत्रासिद्धे न स्थानिवत्‌' (वा ४४३) इह नास्ति । 'तस्य दोषः संयोगादिलोपलत्वणत्वेषु (वा ४४०) इति निषेधात्‌ ।</p>\n";
    display(0); 
    $rakS=1; // 0 - doesn't prevent skoH saMyogAdyorante ca. 1 - prevents skoH saMyogAdyorante ca.
} else { $rakS=0; }
/* pipak, vivak, didhak patch to bar application of skoH saMyogAdyorante ca */
//if (sub(array("vivakz","diDakz","pipakz"),array("+"),blank(0),0) && $pada === "pada" && $san===1) // removed for checking whether $san makes any difference.
if (sub(array("vivakz","diDakz","pipakz"),array("+"),blank(0),0) && $pada === "pada")
{
    echo "<p class = pa >skoH saMyogAdyorante ca doesn't apply here because kutva is asiddha to it.</p>\n";
    echo "<p class = pa >'स्कोः...' इति कलोपं प्रति कुत्वस्य असिद्धत्वात्‌ संयोगान्तलोपः ।</p>\n";
    display(0); 
    $pipakS=1; // 0 - doesn't prevent skoH saMyogAdyorante ca. 1 - prevents skoH saMyogAdyorante ca.
} else { $pipakS=0; }

/* skoH saMyogAdyorante ca (8.2.29) */
if ((sub(array("s","k"),$hl,prat("Jl"),0) || arr($text,'/[sk]['.flat($hl).'][+]$/'))  && $pada === "pada" && $rakS===0 && $pipakS===0)
{
    $text = three(array("s","k"),$hl,prat("Jl"),array("",""),$hl,prat("Jl"),0);
    $text = three($ac,array("s","k"),$hl,$ac,array("",""),$hl,0);
    echo "<p class = sa >By skoH saMyogAdyorante ca (".link_sutra("8.2.29").") :</p>\n";
    echo "<p class = sa >स्कोः संयोगाद्योरन्ते च (८.२.२९) :</p>\n";
    display(0);
}
/* patch for cikIrz */
if (sub(array("cikIrz"),array("+"),blank(0),0) && ($pada==="pada" || arr($text,'/[r][z][+]$/')) )
{
    $text = two(array("cikIrz"),array("+"),array("cikIr"),array("+"),0);
    echo "<p class = sa >By rAtsasya (".link_sutra("8.2.24").") :</p>\n"; 
    echo "<p class = sa >रात्सस्य (८.२.२४) :</p>\n";
    display(0); 
}
/* rAtsasya (8.2.24) */
if ((arr($text,('/[r][+][s]$/')) && $pada === "pratyaya") || (arr($text,('/[r][s][+]/')) && $pada === "pada") )
{
    $text = one(array("r+s"),array("r"),0);
    $text = two(array("rs"),array("+"),array("r"),array("+"),0);
    echo "<p class = sa >By rAtsasya (".link_sutra("8.2.24").") :</p>\n"; 
    echo "<p class = sa >रात्सस्य (८.२.२४) :</p>\n";
    display(0); 
}
if ((arr($text,('/[r][+][hyvrlYmGRnJBGQDjbgqdKPCWTcwtkpzS]$/')) && $pada === "pratyaya") || (arr($text,('/[r][hyvrlYmGRnJBGQDjbgqdKPCWTcwtkpzS][+]/')) && $pada === "pada") )
{
    echo "<p class = pa >rAtsasya (".link_sutra("8.2.24").") prevents application of saMyogAntasya lopaH.</p>\n"; 
    echo "<p class = pa >रात्सस्य (८.२.२४) से संयोगान्तस्य लोपः का प्रतिषेध होता है ।</p>\n";
    display(0); 
    $ratsasya=1; // 0 - doesn't prevent saMyogAntasya lopaH. 1 - prevents saMyogAntasya lopaH.
} else { $ratsasya=0; }
/* saMyogAntasya lopaH (8.2.23) */
// coding pending because not clear. And also 'yaNaH pratiSedho vAcyaH' prohibits its application.
if ( ( sub(array("N"),$ku,array("+"),0) || sub(array("Y"),$cu,array("+"),0) || sub(array("R"),$Tu,array("+"),0) ||sub(array("m"),$pu,array("+"),0) ) && $ratsasya===0 && $pada==="pada" && $vriddhireci===0 && !sub(array("+"),array("A"),blank(0),0) ) // patch for nimittApAye naimittikasyApAyaH.
{
    $text = three(array("N"),$ku,array("+"),array("n"),blank(count($ku)),array("+"),0); 
    $text = three(array("Y"),$cu,array("+"),array("n"),blank(count($cu)),array("+"),0); 
    $text = three(array("R"),$Tu,array("+"),array("n"),blank(count($Tu)),array("+"),0); 
    $text = three(array("m"),$pu,array("+"),array("n"),blank(count($pu)),array("+"),0); 
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") and nimittApAye naimittikasyApyapAyaH (pa) :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) तथा निमित्तापाये नैमित्तिकस्याप्यपायः (प) :</p>\n";
    display(0);            
}
elseif ( sub($hl,$hl,array("+"),0) && $bham===0 && $pada==="pada" && $ratsasya===0 && $vriddhireci===0 && !arr($text,'/['.pc('hl').']['.pc('hl').'][+]['.pc('ac').']/'))
{
    $text = three($hl,$hl,array("+"),$hl,blank(count($hl)),array("+"),0);
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) :</p>\n";
    display(0);
} 
elseif ( sub($hl,array("+"),$hl,0) && arr($text,'/['.pc('hl').'][+]['.pc('hl').']$/') && $ratsasya===0 && $vriddhireci===0 )
{
    $text = three($hl,array("+"),$hl,$hl,array("+"),blank(count($hl)),0);
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) :</p>\n";
    display(0);
} 
elseif ( (sub(array("M"),array("s"),array("+"),0) && $pada==="pada" && $vriddhireci===0) ) // patch for mAMsa. mAMspacanyA UkhAyAH is pending. ayasmayAdIni etc pending.
{
    $text = three(array("M"),array("s+"),$ku,array("N+"),blank(count($hl)),$ku,0); 
    $text = three(array("M"),array("s+"),$cu,array("Y+"),blank(count($hl)),$cu,0); 
    $text = three(array("M"),array("s+"),$Tu,array("R+"),blank(count($hl)),$Tu,0); 
    $text = three(array("M"),array("s+"),$tu,array("n+"),blank(count($hl)),$tu,0); 
    $text = three(array("M"),array("s+"),$pu,array("m+"),blank(count($hl)),$pu,0); 
    $text = three(array("M"),array("s"),array("+"),array("m"),blank(count($hl)),array("+"),0); 
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") and nimittApAye naimittikasyApyapAyaH (pa) :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) तथा निमित्तापाये नैमित्तिकस्याप्यपायः (प) :</p>\n";
    display(0);            
}
elseif ( (sub(array("M"),$hl,array("+"),0) && $pada==="pada" && $vriddhireci===0) )
{
    $text = three(array("M"),$hl,array("+"),array("M"),blank(count($hl)),array("+"),0);
    echo "<p class = sa >By saMyogAntasya lopaH (".link_sutra("8.2.23").") :</p>\n";
    echo "<p class = sa >संयोगान्तस्य लोपः (८.२.२३) :</p>\n";
    display(0);            
    if (sub(array("M"),array("+"),blank(0),0))
    {
    $text = three(array("M"),array("+"),$ku,array("N"),array("+"),$ku,0);
    $text = three(array("M"),array("+"),$cu,array("Y"),array("+"),$cu,0);
    $text = three(array("M"),array("+"),$Tu,array("R"),array("+"),$Tu,0);
    $text = three(array("M"),array("+"),$tu,array("n"),array("+"),$tu,0);
    $text = three(array("M"),array("+"),$pu,array("m"),array("+"),$pu,0);
    $text = two(array("M"),array("+"),array("m"),array("+"),0);
    echo "<p class = sa >By nimittApAye naimittikasyApyapAyaH (pa) :</p>\n";
    echo "<p class = sa >निमित्तापाये नैमित्तिकस्याप्यपायः (प) :</p>\n";
    display(0);
    }
}
/* jhalo jhali (8.2.26) */
if ( sub(prat("Jl"),array("s"),prat("Jl"),0))
{
$text = three(prat("Jl"),array("s"),prat("Jl"),prat("Jl"),array(""),prat("Jl"),0); 
echo "<p class = sa >By jhalo jhali (".link_sutra("8.2.26").") :</p>\n";
echo "<p class = sa >झलो झलि (८.२.२६) :</p>\n";
display(0);
}
/* coH kuH (8.2.30) */
if ((arr($text,'/['.flat($cu).'][+]['.pc('Jl').']/')) && !in_array($fo,$noco)&& ( $kvinku===0 || $ancu===1) )
{
$text = three($cu,array("+"),prat('Jl'),$ku,array("+"),prat('Jl'),0); 
echo "<p class = sa >By coH kuH (".link_sutra("8.2.30").") :</p>\n";
echo "<p class = sa >चोः कुः (८.२.३०) :</p>\n";
display(0); $coku=1; // 0 - doesn't prevent kvinpratyayasya kuH. 1 - prevents kvinpratyayasya kuH.
}
elseif (!in_array($so,$noco) && arr($text,'/['.flat($cu).'][+]$/') && $kvinku===0)
{
    $text = two($cu,array("+"),$ku,array("+"),0);
    echo "<p class = sa >By coH kuH (".link_sutra("8.2.30").") :</p>\n";
    echo "<p class = sa >चोः कुः (८.२.३०) :</p>\n";
    display(0);   $coku=1;
}
else
{
    $coku=0;
}
/* vA druhamuhaSNuhaSNihAm (8.2.34) */
$druh = array("druh","muh","snuh","snih","droh","moh","snoh","sneh");
if (sub($druh,blank(0),blank(0),0) && (arr($text,'/[+]$/') || arr($text,'/[+]['.pc("Jl").']/')) )
{ 
    $text = one(array("druh","muh","snuh","snih","droh","moh","snoh","sneh"),array("druG","muG","snuG","sniG","droG","moG","snoG","sneG"),1);
    echo "<p class = sa >By vA druhamuhaSNuhaSNihAm (".link_sutra("8.2.34").") :</p>\n";
    echo "<p class = sa >वा द्रुहमुहष्णुहष्णिहाम्‌ (८.२.३४) :</p>\n"; 
    display(0);
}
/* dAderdhAtorghaH (8.2.33) */
if (sub($dade,blank(0),blank(0),0) && (arr($text,'/[+]$/') || arr($text,'/[+]['.pc("Jl").']/')) )
{
    $text = one(array("dah","dAh","dih","duh","dfh","drAh","druh"),array("daG","dAG","diG","duG","dfG","drAG","druG"),0);
    echo "<p class = sa >By dAderdhAtorghaH (".link_sutra("8.2.33").") :</p>\n";
    echo "<p class = sa >दादेर्धातोर्घः (८.२.३३) :</p>\n";
    display(0); 
    $hodha1 = 1; // 0 - doesn't prevent ho DhaH. 1 - prevents ho DhaH.
}
/* naho dhaH (8.2.35) */
if (sub(array("nah"),blank(0),blank(0),2) && (arr($text,'/[+]$/') || arr($text,'/[+]['.pc("Jl").']/')) )
{
    $text = one(array("nah",),array("naD"),0);
    echo "<p class = sa >By naho dhaH (".link_sutra("8.2.35").") :</p>\n";
    echo "<p class = sa >नहो धः (८.२.३५) :</p>\n";
    display(0); 
    $hodha2 = 1; // 0 - doesn't prevent ho DhaH. 1 - prevents ho DhaH.  
} else { $hodha2 = 0; } 
/* AhasthaH (8.2.36) */
if (in_array($first,array("Ah")) && (arr($text,'/[+]['.pc("Jl").']/')) )
{
    $text = one(array("Ah",),array("AT"),0);
    echo "<p class = sa >By AhasthaH (".link_sutra("8.2.36").") :</p>\n";
    echo "<p class = sa >आहस्थः (८.२.३६) :</p>\n";
    display(0); 
    $hodha3=1; // 0 - doesn't prevent ho DhaH. 1 - prevents ho DhaH.
} else { $hodha3 = 0; } 
/* ho DhaH (8.2.32) */ 
if (arr($text,'/[h][+]/') && sub(array("h"),prat("Jl"),blank(0),0) && $hodha1===0 && $hodha2 === 0 && $hodha3 === 0 && $kvinku===0)
{
    $text = two(array("h"),prat('Jl'),array("Q"),prat('Jl'),0);
    echo "<p class = sa >ho DhaH (".link_sutra("8.2.32").")  :</p>\n";
    echo "<p class = sa >हो ढः (८.२.३२)  :</p>\n";
    display(0);    
} 
if (arr($text,'/[h][+]$/') && $pada ==="pada" && $hodha1===0 && $hodha2 === 0 && $hodha3 === 0 && $kvinku===0)
{ 
    $text = two(array("h"),array("+"),array("Q"),array("+"),0);
    echo "<p class = sa >ho DhaH (".link_sutra("8.2.32").") :</p>\n";
    echo "<p class = sa >हो ढः (८.२.३२) :</p>\n";
    display(0);    
}
if (arr($text,'/[h]$/')  && $hodha1===0 && $hodha2 === 0 && $hodha3 === 0 && $kvinku===0)
{
    $text = last(array("h"),array("Q"),0);
    echo "<p class = sa >ho DhaH (".link_sutra("8.2.32").")  :</p>\n";
    echo "<p class = sa >हो ढः (८.२.३२)  :</p>\n";
    display(0);    
}
/* ekAco bazo bhaS jhaSantasya sdhvoH (8.2.37) */  // Not good code. Think hard.
if (sub($ekaco,array("+"),blank(0),0) && ( arr($text,'/[+][sd]/') || arr($text,'/[+]$/') || $pada==="pada"))
{
 $text = one($ekaco,$ekaco1,0);
 echo "<p class = sa >By ekAco bazo bhaS jhaSantasya sdhvoH (".link_sutra("8.2.37").") :</p>\n";
    echo "<p class = sa >एकाचो बशो भष्‌ झषन्तस्य स्ध्वोः (८.२.३७):</p>\n";
    display(0);  
}
/* jhalAM jazo'nte (8.2.39) */
if (arr($text,'/['.pc('Jl').']$/') )
{
    if ($r2 ===1) 
         {
            echo "<p class = sa >jhalAM jazo'nte is barred by sasajuSo ruH for second word. <hr>\n"; echo "<p class = sa >द्वितीय पद के लिए ससजुषो रुः से झलां जशोऽन्ते बाधित हुआ है । <hr>\n";          
         }    
    else 
        {
            $text = last(prat('Jl'),savarna(prat('Jl'),prat('jS')),0);            
        }
            echo "<p class = sa >By jhalAM jazo'nte (".link_sutra("8.2.39")."), The padAnta is 'jhal' is replaced by 'jaz' :</p>\n";
            echo "<p class = sa >झलां जशोऽन्ते (८.२.३९) :</p>\n";
            display(0);
}
if (arr($text,'/['.pc('Jl').'][+]/') && ( $pada === "pada" && !arr(array($fo),'/[s]$/'))  )
{     
    if ($r1 === 1 ) 
        {
            echo "<p class = sa >jhalAM jazo'nte is barred by sasajuSo ruH for first word. <hr>\n"; echo "<p class = sa >प्रथम पद के लिए ससजुषो रुः से झलां जशोऽन्ते बाधित हुआ है । <hr>\n";
        }
    else 
        {
            $text = two(prat('Jl'),array("+"),savarna(prat('Jl'),prat('jS')),array("+"),0);                
            echo "<p class = sa >By jhalAM jazo'nte (".link_sutra("8.2.39")."), The padAnta is 'jhal' is replaced by 'jaz' :</p>\n";
            echo "<p class = sa >झलां जशोऽन्ते (८.२.३९) :</p>\n";
            display(0);    
        }
} 
/* kvinpratyaya patch for asRj */
if ($fo==="asfj" && in_array($so,array("su!","am")) && $gender==="n")
{
    $text=two(array("z"),array("+"),array("K"),array("+"),0);
    echo "<p class = sa >By kvinpratyayasya kuH (".link_sutra("8.2.62").") :</p>\n";
    echo "<p class = sa >क्विन्प्रत्ययस्य कुः (८.२.६२) :</p>\n";
    display(0);
    $text=two(array("K"),array("+"),array("g"),array("+"),0);
    echo "<p class = sa >By jhalAM jazo'nte (".link_sutra("8.2.39").") :</p>\n";
    echo "<p class = sa >झलां जशोऽन्ते (८.२.३९) :</p>\n";
    display(0); 
    $asRj=1; // 0 - doesn't prevent reapplication of kvinpratyayasya kuH. 1 - prevents reapplication of kvinpratyayasya kuH.
} else {$asRj=0; }
/* kvinpratyayasya kuH (8.2.62) */
if (sub($hl,array("+"),blank(0),0) && ( $kvin===1 || ($kvip===1 && $fo==="diS") ) && $pada==="pada" && !sub(array("S","z","s"),array("+"),blank(0),0)  && ( $kvinku===1 || $Asarva===0 ) && ($coku!==1||$goanc===1) && $asRj===0)
{   
    $text = two($cu,array("+"),$ku,array("+"),0);
    $text = two($Tu,array("+"),$ku,array("+"),0);
    $text = two($tu,array("+"),$ku,array("+"),0);
    $text = two($pu,array("+"),$ku,array("+"),0);
    $text = two(array("h","z"),array("+"),array("G","K"),array("+"),0);
  //$text = two(array("S","s","y","r","v","l"),array("+"),array("S","s","y","r","v","l"),array("+"),0);  // Pending to find kutva of S,z,s.

    echo "<p class = sa >By kvinpratyayasya kuH (".link_sutra("8.2.62").") :</p>\n";
    if ($kvip===1)
    {
    echo "<p class = hn >As 'tyadAdiSu dRSo'nAlocane kaYca (".link_sutra("3.2.60").") mandates kvin for dRz, others also take kutvam. </p>\n";       // special message fror kvip.
    }
    echo "<p class = sa >क्विन्प्रत्ययस्य कुः (८.२.६२) :</p>\n";
    if ($kvip===1)
    {
    echo "<p class = hn >त्यदादिषु दृशोऽनालोचने कञ्च (३.२.६०) इति दृशेः क्विन्विधानादन्यत्रापि कुत्वम्‌ ।</p>\n";        
    }
    display(0);
    if(sub(array("G"),array("+"),blank(0),0))
    {
        $text = two(array("G"),array("+"),array("g"),array("+"),0);
        echo "<p class = sa >By jhalAM jazo'nte (".link_sutra("8.2.39")."), The padAnta is 'jhal' is replaced by 'jaz' :</p>\n";
        echo "<p class = sa >झलां जशोऽन्ते (८.२.३९) :</p>\n";
        display(0);            
    }
}
/* vizvasya vasurAToH (6.3.128) */
if (sub(array("viSva"),array("vasu","rAq"),blank(0),0)) 
{
    $text = two(array("viSva"),array("vasu","rAq"),array("viSvA"),array("vasu","rAq"),0);
    echo "<p class = sa >By vizvasya vasurAToH (".link_sutra("6.3.128").") :</p>\n";
     echo "<p class = sa >विश्वस्य वसुराटोः (६.३.१२८) :</p>\n";
    display (0);
}
/* bhobhagoaghoapUrvasya yo'zi (8.3.17) : */
$ash = array("a","A","i","I","u","U","f","F","x","X","e","o","E","O","h","y","v","r","l","Y","m","N","R","n","J","B","G","Q","D","j","b","g","q","d");
if (sub(array("Bo","Bago","aGo","a","A"),array("r@"),$ash,0)) 
{
    $text = three(array("Bo","Bago","aGo","a","A"),array("r@"),$ash,array("Bo","Bago","aGo","a","A"),array("y"),$ash,0);
    echo "<p class = sa >By bhobhagoaghoapUrvasya yo'zi (".link_sutra("8.3.17")."):</p>\n";
     echo "<p class = sa >भोभगोअघोअपूर्वस्य योऽशि (८.३.१७) :</p>\n";
    $bho = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
    display (0);
} else { $bho =0; }
// Patch to convert the rutva before vowels and hash to repha.
if (arr($text,'/[r][@]/'))
{ 
    echo "<p class = pa >By upadeze'janunAsika it (".link_sutra("1.3.2").") :</p>\n";
    echo "<p class = pa >उपदेशेऽजनुनासिक इत्‌ (१.३.२) :</p>\n";
    display(0);
    $text = two(array("r@"),$ac,array("r"),$ac,0);
    $text = two(array("r@"),prat('hS'),array("r"),prat('hS'),0);
    $text = two(array("r@"),array("+"),array("r"),array("+"),0);
    if (arr($text,'/[r][@]$/'))
    {
    $text = last(array("@"),array(""),0); 
    }    
    echo "<p class = sa >By tasya lopaH (".link_sutra("1.3.9").") :</p>\n";
    echo "<p class = sa >तस्य लोपः (१.३.९) :</p>\n";
    display(0);    
}
/* SaDhoH kassi (8.2.41) */
if (sub(array("z","Q"),array("s"),blank(0),0) && !in_array("Sapluk",$vik))
{
    $text = two(array("z","Q"),array("s"),array("k","k"),array("s"),0);
    echo "<p class = sa >By SaDhoH kassi (".link_sutra("8.2.41").") :</p>\n";
    echo "<p class = sa >षढोः कस्सि (८.२.४१) :</p>\n";
    display(0);    
}
/* mo no dhAtoH (8.2.64) */
if (arr($text,'/[m][+]/') && $dhatu===1 && $pada==="pada")
{
    $text = one(array("m+",),array("n+",),0);
    $text = one(array("san+",),array("sam+",),0); // upasarga sam wrongly converted to san. So bringing it back. Pending to make it specific to start.
    echo " <p class = sa >By mo no dhAtoH (".link_sutra("8.2.64").") :</p>\n";
    echo " <p class = sa >मो नो धातोः (८.२.६४) :</p>\n";
    display(0);
}
/* rvorupadhAyA dIrgha ikaH (8.2.76) */
//if ($dhatu===1 && ((sub(array("i","I","u","U","f","F","x","X",),array("r+","v+"),$hl,0) && $pada==="pada" ) || arr($text,'/[iIuUfFxX][rv]$/') || sub(array("i","I","u","U","f","F","x","X",),array("r","v"),array("+"),0)) && $pada==="pada") // This gives wrong application for Bis pratyaya. 
if ($dhatu===1 && ((sub(array("i","I","u","U","f","F","x","X",),array("r+","v+"),$hl,0) && $pada==="pada" ) || sub(array("i","I","u","U","f","F","x","X",),array("r","v"),array("+"),0)) && $pada==="pada" && !($us==="nis" && in_array($so,$tiG)) )
{
    $text = three(array("i","I","u","U","f","F","x","X",),array("r","v"),array("+"),array("I","I","U","U","F","F","F","F",),array("r","v"),array("+"),0);
    echo "<p class = sa >By rvorupadhAyA dIrgha ikaH (".link_sutra("8.2.76").") :</p>\n";
    echo "<p class = sa >र्वोरुपधाया दीर्घ इकः (८.२.७६) :</p>\n";
    display(0); 
}
/* hali ca (8.2.77) */
$rvverb=array("ir","Ir","Ir","ur","kIv","kur","kfv","klIv","kziv","kzIv","kzIv","kzur","kzur","Kur","Kur","gir","gur","gUr","gUr","Gur","GUr","cir","cIv","cIv","cur","cUr","Cur","jIv","jur","jUr","tir","1tIv","tIv","3tIv","tur","tUr","div","div","div","dIv","dIv","DIv","Dur","Dur","Druv","niv","nIv","pIv","pur","pUr","Bur","mIv","mIv","mur","mur","mUr","mUr","rIv","SUr","Sriv","SrIv","zWiv","zWIv","siv","siv","sIv","sur","sUr","sTiv","sPur","sriv","srIv","hur",);
// pending to code for how to apply this to whole $rvverb. Right now just coded for pratidivan
if (sub(array("divn"),blank(0),blank(0),1))
{
    $dhatu=1;
}

if ($dhatu===1 && sub($ik,array("r","v"),$hl,0) && (arr($text,'/[rv][+]/')|| arr($text,'/[v][n][+]/') ) && $karoti!==1  && !($us==="nis" && in_array($so,$tiG)))
{
    $text = three(array("i","I","u","U","f","F","x","X",),array("r","v"),$hl,array("I","I","U","U","F","F","F","F",),array("r","v"),$hl,0);
    echo "<p class = sa >By hali ca (".link_sutra("8.2.77").") 3:</p>\n";
    echo "<p class = sa >हलि च (८.२.७७) :</p>\n"; 
    if ($allopo===1)
    {
        echo "<p class = hn >allopa doesn't have sthAnivadbhAva, because sthAnivadbhAva is barred in dIrghavidhi.</p>\n";
        echo "<p class = hn >स्थानिवद्भाव का दीर्घविधि में निषेध होने से यहाँ अल्लोप का स्थानिवद्भाव नहीं है ।</p>\n";        
    }
    display(0); 
}
/* eta Idbahuvacane (8.2.81) */
if (sub(array("ad+e","ade+Bir","ade+Byar","ade+sAm","ade+su"),blank(0),blank(0),0) && in_array($so,$bahusup) && $fo==="adas")
{
     $text=one(array("ad+e","ade+Bir","ade+Byar","ade+sAm","ade+su"),array("amI","amI+Bir","amI+Byar","amI+sAm","amI+su"),0);
     echo "<p class = sa >eta Idbahuvacane (".link_sutra("8.2.81").") :</p>\n";
     echo "<p class = sa >एत ईद्बहुवचने (८.२.८१) :</p>\n";
     display(0);
}
/* adaso'serdAdu do maH (8.2.80) */
// For proper adas forms. 
$acmu = array("u","U","u","U","u","U","u","U","u","U","U","U","U","U",);
if (sub(array("ad"),blank(0),blank(0),0) && in_array($fo,array("adas","adakas")) && !arr($text,'/[a][r][+]/') )
{
     $text=two(array("ad"),$ac,array("am"),$acmu,0);
     echo "<p class = sa >adaso'serdAdu do maH (".link_sutra("8.2.80").") :</p>\n";
     echo "<p class = sa >अदसोऽसेर्दादु दो मः (८.२.८०) :</p>\n";
     display(0);
}
// Not coded properly. coded only for adas-ancu combination.
if (sub(array("adadr"),blank(0),blank(0),0) )
{
     $text=one(array("adadr"),array("amumu"),1);
     $text=one(array("amumu"),array("adamu"),1);
     echo "<p class = sa >adaso'serdAdu do maH (".link_sutra("8.2.80").") :</p>\n";
     echo "<p class = hn >adaso'dreH pRthaGmutvaM kecidicCanti latvavat | kecidantyasadezasya netyeke'serhi dRSyate || </p>\n";
     echo "<p class = sa >अदसोऽसेर्दादु दो मः (८.२.८०) :</p>\n";
     echo "<p class = hn >अदसोऽद्रेः पृथङ्मुत्वं केचिदिच्छन्ति लत्ववत्‌ । केचिदन्त्यसदेशस्य नेत्येकेऽसेर्हि दृश्यते ॥</p>\n";
     display(0);
}
/* vyorlaghuprayatnataraH zAkaTAyanasya (8.3.18) */
// This is regarding pronounciation. So no difference in word declention.
/* roH supi (8.3.16) */
if (arr($text,'/[r][+][s][u]$/') && $so==="sup" && !in_array(1,$R))
{
 echo "<p class = pa >roH supi (".link_sutra("8.3.16").") prevents application of kharavasAnayorvisarjanIyaH. </p>\n";
 echo "<p class = pa >रोः सुपि (८.३.१६) से खरवसानयोर्विसर्जनीयः का प्रतिषेध होता है ।</p>\n";
 display(0); 
 $roHsupi=1; // 0 - doesn't prevent kharavasAnayorvisarjanIyaH. 1 - prevents kharavasAnayorvisarjanIyaH.
} else { $roHsupi=0; }
/* kharavasAnayorvisarjanIyaH (8.3.15) */
if (arr($text,'/[+]['.pc('Kr').']/') && $roHsupi===0 && sub(array("r","r@"),array("+"),prat('Kr'),0) && $pada === "pada")
{
 $text = two(array("r@","r"),prat("Kr"),array("H","H"),prat("Kr"),0);
 echo "<p class = sa >By kharavasAnayorvisarjanIyaH (".link_sutra("8.3.15").") :</p>\n";
 echo "<p class = sa >खरवसानयोर्विसर्जनीयः (८.३.१५) :</p>\n";
 display(0);
}
if ( arr($text,'/[@r]$/')||arr($text,'/[r][+]$/') && $roHsupi===0)
{
 $text = last(array("r@","r"),array("H","H"),0);
 if (arr($text,'/[r][+]$/'))
 {
     $text = one(array("+"),array(""),0);
 }
 $text = last(array("r"),array("H"),0);
 echo "<p class = sa >By kharavasAnayorvisarjanIyaH (".link_sutra("8.3.15").") :</p>\n";
 echo "<p class = sa >खरवसानयोर्विसर्जनीयः (८.३.१५) :</p>\n";
 display(0);
}
/* Dho Dhe lopaH (8.3.13) */
if (sub(array("Q"),array("Q"),blank(0),0))
{
    $text = three(array("e","o","E","O","M","H"),array("Q"),array("Q"),array("e","o","E","O","M","H"),array(""),array("Q"),0);
    $text = two(array('Q'),array('Q'),array(''),array('#Q'),0); 
    echo "<p class = sa >By Dho Dhe lopaH (".link_sutra("8.3.13").") :</p>\n";
    echo "<p class = sa >ढो ढे लोपः (८.३.१३) 1:</p>\n";
    display(0); 
    $dho = 1;  // 0 - This sUtra has not applied. 1 - This sUtra has applied.
	/* sahivahorodavarNasya (6.3.111) */
	if (sub(array("va","sa","vA","sA"),array("#Q"),blank(0),0) && ends(array($fo),array("vaha!","zaha!"),4))
	{
		$text = two(array("va","sa","vA","sA"),array("#Q"),array("vo","so","vo","so",),array("Q"),0);
		echo "<p class = sa >By sahivahorodavarNasya (".link_sutra("6.3.111").") :</p>\n";
		echo "<p class = sa >सहिवहोरोदवर्णस्य (६.३.१११) :</p>\n";
		display(0); 
	}
} else { $dho = 0; }
/* ro ri (8.3.14) */
if (sub(array("r"),array("r"),blank(0),0))
{
    $text = three(array("e","o","E","O","M","H"),array("r"),array("r"),array("e","o","E","O","M","H"),array(""),array("r"),0);
    $text = two(array('r'),array('r'),array(''),array('#r'),0); 
    echo "<p class = sa >By ro ri (".link_sutra("8.3.14").") :</p>\n";
    echo "<p class = sa >रो रि (८.३.१४) :</p>\n";

    display(0);
    $ro = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $ro = 0; }
/* Dhralope pUrvasya dIrgho'NaH (6.3.111) */
$ana = array("a","A","i","I","u","U","f","F","x","X");
$anna = array("A","A","I","I","U","U","F","F","X","X");
if (($ro ===1 || $dho===1) && sub($ana,array('#r',"#Q"),blank(0),0))
{
$text = two($ana,array('#r','#Q'),$anna,array(' r',' Q'),0);
echo "<p class = sa >By Dhralope pUrvasya dIrgho'NaH (".link_sutra("6.3.111").") :</p>\n";
echo "<p class = sa >ढ्रलोपे पूर्वस्य दीर्घोऽणः (६.३.१११) :</p>\n";
display(0);
	/* sahivahorodavarNasya (6.3.111) */
	if (sub(array("va","sa","vA","sA"),array("Q"),blank(0),0) && ends(array($fo),array("vaha!","zaha!"),4))
	{
		$text = two(array("va","sa","vA","sA"),array("Q"),array("vo","so","vo","so",),array("Q"),0);
		echo "<p class = sa >By sahivahorodavarNasya (".link_sutra("6.3.111").") :</p>\n";
		echo "<p class = sa >सहिवहोरोदवर्णस्य (६.३.१११) :</p>\n";
		display(0); 
	}
}
/* lopaH zAkalyasya (8.3.19) and vyorlaghuprayatnataraH zAkaTAyanasya (8.3.18) */ 
$aa = array("a","A");$yv = array("y+","v+"); $space=array(" "," "); // creating some arrays. 
if (sub($aa,$yv,$ac,0) && (arr($text,'/['.pc('ec').'][+]['.flat($ac).']/') || $bho === 1) && $pada === "pada")
{
echo "<p class = sa >By lopaH zAkalyasya (".link_sutra("8.3.19").") and vyorlaghuprayatnataraH zAkaTAyanasya (".link_sutra("8.3.18").") :</p>\n";
echo "<p class = sa >लोपः शाकल्यस्य (८.३.१९) तथा व्योर्लघुप्रयत्नः शाकटायनस्य (८.३.१८) :</p>\n";
$text = three($aa,$yv,$ac,$aa,array(" +"," +"),$ac,1); 
display(0);
}
/* hali sarveSAm (8.3.22) */
if ($bho === 1 && sub(array("y+"),$hl,blank(0),0) && $pada==="pratyaya")
{
    $text = three(array("Bo","Bago","aGo","A"),array("y+"),$hl,array("Bo","Bago","aGo","A"),array("+"),$hl,0);
    echo "<p class = sa >By hali sarveSAm (".link_sutra("8.3.22").") :</p>\n";
    echo "<p class = sa >हलि सर्वेषाम्‌ (८.३.२२) :</p>\n";
    display(0);
}
if ($bho === 1 && sub(array("y+"),$hl,blank(0),0) && (in_array($so,$sup) && $pada==="pada"))
{
    $text = three(array("Bo","Bago","aGo","A"),array("y+"),$hl,array("Bo","Bago","aGo","A"),array("+"),$hl,0);
    echo "<p class = sa >By hali sarveSAm (".link_sutra("8.3.22").") :</p>\n";
    echo "<p class = sa >हलि सर्वेषाम्‌ (८.३.२२) :</p>\n";
    display(0);
}
/* oto gArgyasya (8.3.20) */
if (arr($text,'/[o][y][+]/') && $bho ===1 && $pada === "pada")
{
    $text = one(array("oy+"),array("o +"),0);
    echo "<p class = sa >By oto gArgyasya (".link_sutra("8.3.20").") :</p>
        <p class = hn >N.B. This rule applies only to the padAnta alaghuprayatna yakAra following 'o' only.</p>\n";
    echo "<p class = sa >ओतो गार्ग्यस्य (८.३.२०) :</p>
        <p class = hn >यह ओकार के परे आए हुए अलघुप्रयत्न पदान्त यकार को ही लागू होता है ।</p>\n";
    display(0);
}
/* uJi ca pade (8.3.21) */
if ((sub(array("ay","av"),array("u "),blank(0),0)|| (sub(array("ay","av"),blank(0),blank(0),0) && $second === "u")) && $bho ===1 && $pada === "pada")
{
    $text = two(array("ay","av"),array("u"),array("a","a"),array("u"),0);
    echo "<p class = sa >By uJi ca pade (".link_sutra("8.3.21").") :</p>\n";
    echo "<p class = sa >उञि च पदे (८.३.२१) :</p>\n";
    display(0);
}
/* mo rAji samaH kvau (8.3.25) */
if (sub(array("sam"),array("rA"),blank(0),0))
{
    $text = two(array("sam"),array("rA"),array("sam"),array("rA"),0);
    echo "<p class = sa >By mo rAji samaH kvau (".link_sutra("8.3.25").") :</p>\n";
  echo "<p class = sa >मो राजि समः क्वौ (८.३.२५) :</p>\n";
  display(0); 
  $mo = 1; // 0 - doesn't prevent application of mo'nusvAraH. 1 - prevents application of mo'nusvAraH.
} else { $mo = 0; }
/* mo'nusvAraH (8.3.23) */
if (arr($text,'/[m][+]['.pc('hl').']/') && $pada ==="pada" && $mo === 0)
{
$text = two(array('m'),prat('hl'),array('M'),prat('hl'),0);
echo "<p class = sa >By mo'nusvAraH (".link_sutra("8.3.23").") :</p>
    <p class = hn >N.B.: The conversion to anusvAra occurs only if the m is at the end of a pada. Otherwise this conversion doesn't apply. Ignore all consequentiality in that case.</p>\n";
echo "<p class = sa >मोऽनुस्वारः (८.३.२३) :</p>
    <p class = hn >यदि मकार पदान्त में है तभी अनुस्वार में बदलता है । अन्यथा नहीं ।</p>\n";
display(0);
}
/* nazcApadAntasya jhali (8.3.24) */
if (arr($text,'/[mn][+]['.pc('Jl').']/') && $pada === "pratyaya" )
{
$text = two(array('m','n'),prat('Jl'),array('M','M'),prat('Jl'),0);
echo "<p class = sa >By nazcApadAntasya jhali (".link_sutra("8.3.24").") :</p>
    <p class = hn >If n/m is inside a pada, it should be converted to anusvAra. So ignore the case which doesn't apply here.</p>\n";
echo "<p class = sa >नश्चापदान्तस्य झलि (८.३.२४) :</p>
    <p class = hn >यदि नकार या मकार पदान्त में नहीं है तब भी यह नियम से अनुस्वार होता है ।</p>\n";
display(0);
}
if(arr($text,'/[mn]['.pc('Jl').']/') )
{
$text = two(array('m','n'),prat('Jl'),array('M','M'),prat('Jl'),2);
echo "<p class = sa >By nazcApadAntasya jhali (".link_sutra("8.3.24").") :</p>
    <p class = hn >If n/m is inside a pada, it should be converted to anusvAra. So ignore the case which doesn't apply here.</p>\n";
echo "<p class = sa >नश्चापदान्तस्य झलि (८.३.२४) :</p>
    <p class = hn >यदि नकार या मकार पदान्त में नहीं है तब भी यह नियम से अनुस्वार होता है ।</p>\n";
display(0);
}
/* he mapare vA (8.3.26) and yavalapare yavalA veti vaktavyam (vA 4902) */
if (sub(array("M"),array("hm","hy","hv","hl"),blank(0),0))
{
$text = two(array("M"),array("hy",),array("!y",),array("hy",),1);
$text = two(array("M"),array("hm",),array("m",),array("hy",),1);
$text = two(array("M"),array("hv",),array("!v",),array("hv",),1);
$text = two(array("M"),array("hl",),array("!l",),array("hl",),1);
echo "<p class = sa >By he mapare vA (".link_sutra("8.3.26").") and yavalapare yavalA veti vaktavyam (vA 4902) :</p>\n";
echo "<p class = sa >हे मपरे वा (८.३.२६) तथा यवलपरे यवला वेति वक्तव्यम्‌ (वा ४९०२) :</p>\n";
display(0);
}
/* napare naH (8.3.27) */
if (sub(array("M"),array("hn"),blank(0),0))
{
$text = two(array("M"),array("hn"),array("n"),array("hn",),1);
echo "<p class = sa >By napare naH (".link_sutra("8.3.27").") :</p>\n";
echo "<p class = sa >नपरे नः (८.३.२७) :</p>\n";
display(0);
}
/* GNoH kukTukzari (8.3.28) */
if (sub(array("N","R"),prat('Sr'),blank(0),0))
{
$text = two(array("N","R"),prat('Sr'),array("Nk","Rw"),prat('Sr'),1);
echo "<p class = sa >By GNoH kukTukzari (".link_sutra("8.3.28").") :</p>\n";
echo "<p class = sa >ङ्णोः कुक्टुक्शरि (८.३.२८) :</p>\n";
display(0);
}
/* DaH si dhuT (8.3.29) */
$dhut = 0;  // 0 - no dhuT Agama. 1 - dhuT Agama.
if (sub(array("q"),array("s"),blank(0),0))
{
$text = two(array("q"),array("s"),array("q"),array("Ds"),1);
echo "<p class = sa >By DaH si dhuT (".link_sutra("8.3.29").") :</p>\n";
echo "<p class = sa >डः सि धुट्‌ (८.३.२९) :</p>\n";
display(0); 
}
/* nazca (8.3.30) */
if (sub(array("n"),array("s"),blank(0),0))
{
$text = two(array("n"),array("s"),array("n"),array("Ds"),1);
echo "<p class = sa >By nazca (".link_sutra("8.3.30").") :</p>\n";
echo "<p class = sa >नश्च (८.३.३०) :</p>\n";
display(0);
}
/* zi tuk (8.3.31) */
if (arr($text,'/[n][+][S]/') && $pada === "pada")
{
$text = one(array("n+S"),array("nt+S"),1);    
echo "<p class = sa >By zi tuk (".link_sutra("8.3.31").") :</p>\n";
echo "<p class = sa >शि तुक्‌ (८.३.३१) :</p>\n";
display(0);
}
/* Gamo hrasvAdaci GamuNnityam (8.3.32) */ // Here the Agama has to be affiliated to $ac. Patch is bad.
$nogamo = array("aR","ak","ik","uk","ac","ic","ec","aw","aR","iR","am","aS","al",); // array where this rule won't apply.
if (arr($text,'/['.flat($hrasva).'][NRn][+]['.flat($ac).']/') && $pada === "pada" && !in_array($second,$nogamo) && !sub(array("pataYjal","sImant"),blank(0),blank(0),0))
{
$text = three($hrasva,array("N","R","n"),$ac,$hrasva,array("NN","RR","nn"),$ac,0);
echo "<p class = sa >By Gamo hrasvAdaci GamuNnityam (".link_sutra("8.3.32").") :</p>\n";
echo "<p class = sa >ङमो ह्रस्वादचि ङमुण्नित्यम्‌ (८.३.३२) :</p>\n";
display(0);
}
/* saheH sADaH saH (8.3.56) */
if (sub(array("sAq"),array("+"),blank(0),0))
{
$text = two(array("sAq"),array("+"),array("zAq"),array("+"),0);
echo "<p class = sa >By saheH sADaH saH (".link_sutra("8.3.56").") :";
echo "<p class = sa >सहेः साडः सः (८.३.५६) :";
display(0);
}
/* sampuGkAnAM so vaktavyaH (vA 4892) */
if (sub(array("saM","sa!","puM","pu!","kAM","kA!"),array("H"),blank(0),0))
{
$text = two(array("saM","sa!","puM","pu!","kAM","kA!"),array("H"),array("saM","sa!","puM","pu!","kAM","kA!"),array("s"),0);
echo "<p class = sa >By sampuGkAnAM so vaktavyaH (vA 4892) :";
echo "<p class = sa >सम्पुङ्कानां सो वक्तव्यः (वा ४८९२) :";
display(0);
}
/* samo vA lopameke (bhASya) */
if (sub(array("saMs","sa!s"),array("s"),$hl,0))
{
$text = two(array("saMs","sa!s"),array("s"),array("saM","sa!"),array("s"),1);
echo "<p class = sa >By samo vA lopameke (bhASya) :</p>\n";
echo "<p class = sa >समो वा लोपमेके (भाष्य) :</p>\n";
display(0);
}
/* dvistrizcaturiti kRtvo'rthe (8.3.43) */
if (sub(array("dviH","triH","catuH"),$ku,blank(0),0))
{
    $text = two (array("dviH","triH","catuH"),$ku,array("dviz","triz","catuz"),$ku,1);
    echo "<p class = sa >By dvistrizcaturiti kRtvo'rthe (".link_sutra("8.3.43").") :</p>
        <p class = hn >N.B. This applies only in case of kRtvo'rthe.</p>\n";
    echo "<p class = sa >द्विस्त्रिश्चतुरिति कृत्वोऽर्थे (८.३.४३):</p>
        <p class = hn >यह नियम सिर्फ कृत्वोऽर्थ में ही लागू होता है ।</p>\n";
    display(0); 
    $dvi1 = 1;  // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $dvi1 = 0; }
if (sub(array("dviH","triH","catuH"),$pu,blank(0),0))
{
 $text = two (array("dviH","triH","catuH"),$pu,array("dviz","triz","catuz"),$pu,1);
 echo "<p class = sa >By dvistrizcaturiti kRtvo'rthe (".link_sutra("8.3.43").") :</p>
        <p class = hn >N.B. This applies only in case of kRtvo'rthe.</p>\n";
 echo "<p class = sa >द्विस्त्रिश्चतुरिति कृत्वोऽर्थे (८.३.४३):</p>
        <p class = hn >यह नियम सिर्फ कृत्वोऽर्थ में ही लागू होता है ।</p>\n";
    display(0); 
    $dvi2 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $dvi2 = 0; }
/* muhusaH pratiSedhaH (vA 4911) */
if (sub(array("muhu"),array("H"),$pu,0)||sub(array("muhu"),array("H"),$ku,0))
{
    $text = three(array("muhu"),array("H"),$pu,array("muhu"),array("H"),$pu,0);
    $text = three(array("muhu"),array("H"),$ku,array("muhu"),array("H"),$ku,0);
    echo "<p class = sa >By muhusaH pratiSedhaH (vA 4911) :</p>\n";
    echo "<p class = sa >मुहुसः प्रतिषेधः (वा ४९११) :</p>\n";
    display(0); 
    $muhu1 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $muhu1 = 0; }
/* kaskAdiSu ca (8.3.48) */
$kaska = array("kaHkaH","kOtaHkut","sarpiHkuRqik","BrAtuHputr","SunaHkarR","sadyaHkAl","sadyaHkI","sAdyaHk","kAMHkAn","DanuHkapAl","bahiHpal","barhiHpal","yajuHpAtr","ayaHkAnt","tamaHkARq","ayaHkARq","medaHpiRq","BAHkar","ahaHkar","kaH+kaH","kOtaH+kut","sarpiH+kuRqik","BrAtuH+putr","SunaH+karR","sadyaH+kAl","sadyaH+kI","sAdyaH+k","kAMH+kAn","DanuH+kapAl","bahiH+pal","barhiH+pal","yajuH+pAtr","ayaH+kAnt","tamaH+kARq","ayaH+kARq","medaH+piRq","BAH+kar","ahaH+kar"); // kaskAdi gaNa, before joining.
$kaskareplace = array("kaskaH","kOtaskut","sarpizkuRqik","BrAtuzputr","SunaskarR","sadyaskAl","sadyaskI","sAdyask","kAMskAn","DanuzkapAl","bahizpal","barhizpal","yajuzpAtr","ayaskAnt","tamaskARq","ayaskARq","medaspiRq","BAskar","ahaskar","kas+kaH","kOtas+kut","sarpiz+kuRqik","BrAtuz+putr","Sunas+karR","sadyas+kAl","sadyas+kI","sAdyas+k","kAMs+kAn","Danuz+kapAl","bahiz+pal","barhiz+pal","yajuz+pAtr","ayas+kAnt","tamas+kARq","ayas+kARq","medas+piRq","BAs+kar","ahas+kar"); // kaskAdi gaNa, after joining.
if(sub($kaska,blank(0),blank(0),0))
{
$text = one ($kaska,$kaskareplace,0);
echo "<p class = sa >By kaskAdiSu ca (".link_sutra("8.3.48").") ";
echo "<p class = sa >कस्कादिषु च (८.३.४८) ";
    display(0); 
    $kaska = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $kaska = 0; }
/* isusoH sAmarthye (8.3.44) and nityaM samAse'nuttarapadasthasya (8.3.45) */ 
if (sub(array("iH","uH",),$ku,blank(0),0) && $dvi1===0 && $dvi2===0 && $muhu1 ===0 && $us!=="nis")
{
    $text = two (array("iH","uH"),$ku,array("iz","uz"),$ku,1);
    echo "<p class = sa >By isusoH sAmarthye (".link_sutra("8.3.44").") and nityaM samAse'nuttarapadasthasya (".link_sutra("8.3.45").") :</p>
        <p class = hn >N.B. This applies only in case of sAmarthya. If 'is' or 'us' pratyayas are at the end of first component of a compound, they are mandatorily converted to 'S'.</p>\n";
    echo "<p class = sa >इसुसोः सामर्थ्ये (८.३.४४) तथा नित्यं समासेऽनुत्तरपदस्थस्य (८.३.४५) :</p>
        <p class = hn >यह तभी लागू होता है जब सामर्थ्य में प्रयोग हुआ हो । यदि 'इस्‌' और 'उस्‌' प्रत्यय उत्तरपद में न हों तब आवश्यक रूप से शकार में परिवर्तन होता है ।</p>\n";
    display(0); 
    $isu1 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $isu1 = 0; }
if (sub(array("iH","uH"),$pu,blank(0),0)  && $us!=="nis")
{
 $text = two (array("iH","uH"),$pu,array("iz","uz"),$pu,1);
 echo "<p class = sa >By isusoH sAmarthye (".link_sutra("8.3.44").") and nityaM samAse'nuttarapadasthasya (".link_sutra("8.3.45").") :</p>
        <p class = hn >N.B. This applies only in case of sAmarthya. If 'is' or 'us' pratyayas are at the end of first component of a compound, they are mandatorily converted to 'S'.</p>\n";
    echo "<p class = sa >इसुसोः सामर्थ्ये (८.३.४४) तथा नित्यं समासेऽनुत्तरपदस्थस्य (८.३.४५) :</p>
        <p class = hn >यह तभी लागू होता है जब सामर्थ्य में प्रयोग हुआ हो । यदि 'इस्‌' और 'उस्‌' प्रत्यय उत्तरपद में न हों तब आवश्यक रूप से षकार में परिवर्तन होता है ।</p>\n";
    display(0); 
    $isu2 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $isu2= 0; }
/* idudupadhasya cApratyayasya (8.3.41) */
if (sub($iN,array("H"),$ku,0) && $dvi1===0 && $dvi2===0 && $isu1 ===0 && $isu2 ===0&& $muhu1 ===0 && ($pada !== "pratyaya" || $us==="nis"))
{
    $text = three(array("i","u",),array("H"),$ku,array("i","u",),array("z"),$ku,1);
    echo "<p class = sa >By idudupadhasya cApratyayasya (".link_sutra("8.3.41").") :</p>
        <p class = hn >N.B. : the visarga will be converted to 'S' only if it is not followed by pratyaya.</p>\n";
    echo "<p class = sa >इदुदुपधस्य चाप्रत्ययस्य (८.३.४१) :</p>
        <p class = hn >यदि परे प्रत्यय न हो तभी षकार में परिवर्तन होता है ।</p>\n";
    display(0);
}
if (sub($iN,array("H"),$pu,0) && $pada !== "pratyaya")
{
    $text = three(array("i","u",),array("H"),$pu,array("i","u",),array("z"),$pu,1);
    echo "<p class = sa >By idudupadhasya cApratyayasya (".link_sutra("8.3.41").") :</p>
        <p class = hn >N.B. : the visarga will be converted to 'S' only if it is not followed by pratyaya.</p>\n";
    echo "<p class = sa >इदुदुपधस्य चाप्रत्ययस्य (८.३.४१) :</p>
        <p class = hn >यदि परे प्रत्यय न हो तभी षकार में परिवर्तन होता है ।</p>\n";
    display(0);
}
/* ekAdezazAstranimittikasya na Satvam | kaskAdiSu bhrAtuSputrazabdasya pAThAt (vA 4915) */ 
// Pending to code.
/* iNaH SaH (8.3.39) */
if (sub($iN,array("H"),array("pAS","kalp","kAmy","ka","kAMy"),blank(0),0) && $dvi1===0 && $dvi2===0 && $isu1 ===0 && $isu2 ===0 && $muhu1 ===0) 
{
    $text = three($iN,array("H"),array("pAS","kalp","kAmy","ka","kAMy"),$iN,array("z"),array("pAS","kalp","kAmy","ka","kAmy"),0);
    echo "<p class = sa >By iNaH SaH (".link_sutra("8.3.39").") :</p>\n";
    echo "<p class = sa >इणः षः (८.३.३९) :</p>\n";
    display(0); $inah = 1;
} else { $inah = 0; }
/* namaspurasorgatyoH (8.3.40) */
if (sub(array("namaH","puraH"),$ku,blank(0),0))
{
    $text = two(array("namaH","puraH"),$ku,array("namas","puras"),$ku,1);
    echo "<p class = sa >By namaspurasorgatyoH (".link_sutra("8.3.40").") :</p>
          <p class = hn >N.B. : The conversion to namas / puras is done only in case it has gati saJjJA.</p>\n";
    echo "<p class = sa >नमस्पुरसोर्गत्योः (८.३.४०) :</p>
          <p class = hn >यदि गति संज्ञा हो तभी नमस्‌ / पुरस्‌ में परिवर्तन होता है ।</p>\n";
    display(0); 
    $nama1 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $nama1 = 0; }
if (sub(array("namaH","puraH"),$pu,blank(0),0) && $nama1 !==1)
{
    $text = two(array("namaH","puraH"),$pu,array("namas","puras"),$pu,1);
    echo "<p class = sa >By namaspurasorgatyoH (".link_sutra("8.3.40").") :</p>
        <p class = hn >N.B. : The conversion to namas / puras is done only in case it has gati saJjJA.</p>\n";
    echo "<p class = sa >नमस्पुरसोर्गत्योः (८.३.४०) :</p>
          <p class = hn >यदि गति संज्ञा हो तभी नमस्‌ / पुरस्‌ में परिवर्तन होता है ।</p>\n";
    display(0); 
    $nama2 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $nama2 = 0; }
/* tiraso'nyatarsyAm (8.3.42) */
if (sub(array("tiraH"),$ku,blank(0),0))
{
    $text = two (array('tiraH'),$ku,array('tiras'),$ku,1);
    echo "<p class = sa >By tiraso'nyatarasyAm  (".link_sutra("8.3.42").") :</p>\n";
    echo "<p class = sa >तिरसोऽन्यतरस्याम्‌  (८.३.४२) :</p>\n";
    display(0); 
    $tir1 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $tir1 = 0; }
if (sub(array("tiraH"),$pu,blank(0),0))
{
 $text = two (array('tiraH'),$pu,array('tiras'),$pu,1);
 echo "<p class = sa >By tiraso'nyatarasyAm  (".link_sutra("8.3.42").") :</p>\n";
 echo "<p class = sa >तिरसोऽन्यतरस्याम्‌  (८.३.४२) :</p>\n";
    display(0); 
    $tir2 = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $tir2 = 0; }
/* ataH kRkamikaMsakumbhapAtrakuzAkarNISvanavyayasya (8.3.46) */
$atah = 0; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
if (sub(array("aH"),array("kAr","kAm","kAMs","kumBa","pAtra","kuSA","karRI"),blank(0),0) && $nama1 !== 1 && $nama2 !== 1   && $tir1===0 && $tir2===0 )
{
    $text = two(array("aH"),array("kAr","kAm","kAMs","kumBa","pAtra","kuSA","karRI"),array('as'),array("kAr","kAm","kAMs","kumBa","pAtra","kuSA","karRI"),1);
    echo "<p class = sa >By ataH kRkamikaMsakumbhapAtrakuzAkarNISvanavyayasya (".link_sutra("8.3.46").") :</p>
       <p class = hn > This applies olny when there is compound and the word with 'as' is neither uttarapadastha nor avyaya.</p>\n";
    echo "<p class = sa >अतः कृकमिकंसकुम्भपात्रकुशाकर्णीष्वनव्ययस्य (८.३.४६) :</p>
       <p class = hn >यह तब लागू होता है, जब समास में 'अस्‌' से अन्त होनेवाला शब्द न तो उत्तरपदस्त हो न ही अव्यय हो ।</p>\n";
    display(0); 
    $atah = 1;
}
/* adhazzirasI pade */
if (sub(array("aDaH","SiraH"),array("pada"),blank(0),0)  )
{
    $text = two(array("aDaH","SiraH"),array("pada"),array("aDas","Siras"),array("pada"),1);
    echo "<p class = sa >By aDazzirasI pade (".link_sutra("8.3.47").") :</p>
       <p class = hn > This applies olny when there is compound and the word 'adhas' or 'ziras' is not uttarapadastha.</p>\n";
    echo "<p class = sa >अधश्शिरसी पदे (८.३.४७) :</p>
       <p class = hn >यह नियम तभी लागू होता है जब अधस्‌ / शिरस्‌ उत्तरपदस्थ नहीं होते ।</p>\n";
    display(0); $atah = 1;
}
/* so'padAdau (8.3.38), pAzakalpakakAmyeSviti vAcyam (vA 5033), anavyayasyeti vAcyam (vA 4902) and kAmye roreveti vAcyam (vA 4902) */ 
// anavyayasyeti vAcyam (vA 4901) is pending to code.
if (sub(array("H"),array("pAS","kalp","kAmy","ka"),blank(0),0) && $inah !== 1 && $nama1 !== 1 && $nama2 !== 1 && $dvi1===0 && $dvi2===0 && $isu1 ===0 && $isu2 ===0 && $tir1===0 && $tir2===0 && $muhu1 ===0  && $atah ===0)
{
    $text = two(array("H"),array("kalp","kAmy","ka","kAMy"),array('s'),array("kalp","kAmy","ka","kAMy"),0);
    $text = two(array("H"),array("pAS"),array('s'),array("pAS"),0);
    if (preg_match('/[sr]$/',$first))
    {
        $text = one(array('skAmy','skAMy'),array('HkAmy','HkAMy'),1);      
    }
    echo "<p class = sa >By so'padAdau (".link_sutra("8.3.38")."), pAzakalpakakAmyeSviti vAcyam (vA 5033), anavyayasyeti vAcyam (vA 4902) and kAmye roreveti vAcyam (vA 4902) :</p>\n";
    echo "<p class = sa >सोऽपदादौ (८.३.३८), पाशकल्पककाम्येष्विति वाच्यम्‌ (वा ५०३३), अनव्ययस्येति वाच्यम्‌ (वा ४९०२) तथा काम्ये रोरेवेति वाच्यम् (वा ४९०२) :</p>\n";
    display(0);
}
/* zarpare visarjanIyaH (8.3.35) */
if (sub(array("H"),prat('Kr'),prat('Sr'),0) )
{
echo "<p class = sa >By zarpare visarjanIyaH (".link_sutra("8.3.35").") :</p>\n";
echo "<p class = sa >शर्परे विसर्जनीयः (८.३.३५) :</p>\n";
display(0); 
$zarpare = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied. 2 - visarjanIyasya saH. 3 - vA zari.
} else { $zarpare = 0; }
/* kupvoH &k&pau ca (8.3.37) */ // <p class = hn >Note that we have used & as jihvAmUlIya and * as upadhmAnIya.
if((sub(array("H"),$ku,blank(0),0)||sub(array("H"),$pu,blank(0),0)) && $kaska !== 1 && $zarpare ===0)
{
$text = two(array("H"),$ku,array("&"),$ku,1);
$text = two(array("H"),$pu,array("&"),$pu,1);
echo "<p class = sa >By kupvoH &k&pau ca (".link_sutra("8.3.37")."). :</p>\n";
echo "<p class = sa >कुप्वोः ᳲकᳲपौ च (८.३.३७). :</p>\n";
display(0); 
$kupvo = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else {$kupvo = 0; }
/* visarjanIyasya saH (8.3.34) */ 
if(sub(array("H"),prat('Kr'),blank(0),0) && $zarpare !==1 && $kupvo ===0)
{
$text = two(array("H"),prat('Kr'),array("s"),prat('Kr'),0);
echo "<p class = sa >By visarjanIyasya saH (".link_sutra("8.3.34").") :</p>\n";
echo "<p class = sa >विसर्जनीयस्य सः (८.३.३४) :</p>\n";
display(0);
$zarpare = 2;
}
/* vA zari (8.3.36) */
if(sub(array("s"),prat('Sr'),blank(0),0) && $zarpare === 2)
{
$text = two(array("s"),array("S","z","s"),array("H"),array("S","z","s"),1);
echo "<p class = sa >By vA zari (".link_sutra("8.3.36").") :</p>\n";
display(0); 
$zarpare = 3;
}
/* kharpare zari vA visargalopo vaktavyaH (vA 4906) */
if(sub(array("H"),prat('Sr'),prat('Kr'),0) && $zarpare === 3)
{
$text = three(array("H"),prat('Sr'),prat('Kr'),array(""),prat('Sr'),prat('Kr'),1);
echo "<p class = sa >By kharpare zari vA visargalopo vaktavyaH (vA 4906) :</p>\n";
echo "<p class = sa >खर्परे शरि वा विसर्गलोपो वक्तव्यः (वा ४९०६) :</p>\n";
display(0);
}
/* apadAntasya mUrdhanyaH (8.3.55), iNkoH (8.3.57) and AdezapratyayayoH (8.3.59) */
// Not coded perfectly. This is only according to the need of vibhaktis. 
if((sub($iN1,array("+s"),blank(0),0)) &&  (in_array($so,array("Am","sup"))|| in_array(1,$samp)) || ($fo==="adas" && in_array($so,array("Ne","Nasi!","Nas","Am","Ni"))) )
{
$text = two($iN1,array("+s"),$iN1,array("+z"),0);
echo "<p class = sa >By apadAntasya mUrdhanyaH (".link_sutra("8.3.55")."), iNkoH (".link_sutra("8.3.57").") and AdezapratyayayoH (".link_sutra("8.3.59").") :</p>\n";
echo "<p class = sa >अपदान्तस्य मूर्धन्यः (८.३.५५), इण्कोः (८.३.५७) तथा आदेशप्रत्यययोः (८.३.५९) :</p>\n";
display(0);
}
// this is the pending work, which I have started. is and us ending words usually take the 's' -> 'S' conversion. 
$isusend = array("arcis","Arcis","avis","Avis","ASis","kravis","krivis","Cadis","Cardis","jyotis","tuvis","tris","dvis","pATis","barhis","Buvis","rocis","varhis","Socis","saDis","sarpis","havis","aprAyus","arus","Arus","asaScus","AsaScus","Ayus","Danus","cakzus","jayus","tanus","tapus","tarus","tasTus","parus","yajus","vapus","Arus",); // Here we will enumerate those which end with is, us, or any eligible cangdidate.
if((sub($iN1,array("s+","Ms+"),blank(0),0)) && ends(array($fo),$isusend,1) && $pada==="pratyaya" )
{
$text = two($iN1,array("s+",),$iN1,array("z+"),0);
if ($gender==="n")
{
    $text = two($iN1,array("Ms+",),$iN1,array("Mz+"),0);
}
echo "<p class = sa >By apadAntasya mUrdhanyaH (".link_sutra("8.3.55")."), iNkoH (".link_sutra("8.3.57").") and AdezapratyayayoH (".link_sutra("8.3.59").") :</p>\n";
echo "<p class = sa >अपदान्तस्य मूर्धन्यः (८.३.५५), इण्कोः (८.३.५७) तथा आदेशप्रत्यययोः (८.३.५९) :</p>\n";
display(0);
}
/* numvisarjanIyazarvyavAye'pi (8.3.58) */
// Not coded perfectly, only according to the need of vibhaktis.
$iN1 = array("i","I","u","U","f","F","x","X","e","o","E","O","h","y","v","r","l","k","K","g","G","N");
$pr1= '/(['.flat($iN1).'])([M+]*)([s])([uA])/'; // num
$pr2= '/(['.flat($iN1).'])([H+]*)([s])([uA])/'; // visarga
$pr3= '/(['.flat($iN1).'])([S+]*)([s])([uA])/'; // z
$pr4= '/(['.flat($iN1).'])([z+]*)([s])([uA])/'; // S
$pr5= '/(['.flat($iN1).'])([s+]*)([s])([uA])/'; // s
$prr = '$1$2z$4'; // replacement
if ( (arr($text,'/['.flat($iN1).']([HSzs+]*)[s][uA]/')|| (arr($text,'/['.flat($iN1).']([HSzs+]*)[s][uA]/') && $num===1 )) && sub(array("+su","+sAm"),blank(0),blank(0),0) )
{
    foreach ($text as $value)
    {
        if(in_array(1,$num))
        {
        $val[] = preg_replace($pr1,$prr,$value);            
        }
        $val[] = preg_replace($pr2,$prr,$value);
        $val[] = preg_replace($pr3,$prr,$value);
        $val[] = preg_replace($pr4,$prr,$value);
        $val[] = preg_replace($pr5,$prr,$value);
    }
    $text = array_diff($val,$text);; 
    $text = array_unique($text);
    $text = array_values($text);
    $val=array();
echo "<p class = sa >By numvisarjanIyazarvyavAye'pi (".link_sutra("8.3.58").") :</p>\n";
echo "<p class = sa >नुम्विसर्जनीयशर्व्यवायेऽपि (८.३.५८) :</p>\n";
display(0);
}

/* hanteratpUrvasya (8.4.22) */
if( sub($upasarga,array("han","Gn"),blank(0),0))
{
$text = two($upasarga,array("han","Gn"),$upasarga,array("haR","Gn"),0);
echo "<p class = sa >By hanteratpUrvasya (".link_sutra("8.4.22").") :</p>\n";
echo "<p class = sa >हन्तेरत्पूर्वस्य (८.४.२२) :</p>\n";
display(0);   
} 
if( arr($text,'/[G][n]/') && arr(array($fo),'/[h][a][n]/') && !sub($upasarga,array("han","Gn"),blank(0),0))
{
$text = one(array("han","Gn"),array("haR","Gn"),0);
echo "<p class = sa >By hanteratpUrvasya (".link_sutra("8.4.22").") :</p>\n";
echo "<p class = sa >हन्तेरत्पूर्वस्य (८.४.२२) :</p>\n";
display(0);   
}
/* raSAbhyAM no NaH samAnapade (8.4.1) */
// pUrvasmAdapi vidhau sthAnivadbhAvaH , pUrvatrAsiddhe na sthAnivat (vA 433) and tasya doSaH saMyogAdilopalatvaNatveSu (vA 440) are pending to code.
if($pada === "pratyaya" && sub(array("r","z"),array("n"),blank(0),0))
{
$text = two(array("r","z"),array("n"),array("r","z"),array("R"),0);
echo "<p class = sa >By raSAbhyAM no NaH samAnapade (".link_sutra("8.4.1").") :</p>\n";
echo "<p class = sa >रषाभ्यां नो णः समानपदे (८.४.१) :</p>\n";
display(0);   
}
/* ekAjuttarapade NaH (8.4.12) */
// very bad coding for this. Difficult to teach this samAsa, before machine understands samAsa. Therefore enumeration method is used here.
if (sub(array("punarBU+nAm",),blank(0),blank(0),0))
{
    $ekajuttarapada=1; // Because there is ekAc uttarapada in this samAsa.
}
$rasek = '/([rzfF])([aAiIuUfFxXeoEOhyvrkKgGNpPbBmM+]*)([n])/'; // pattern which qualifies for this sUtra.
$ras1ek = '$1$2R'; 
if( $ekajuttarapada===1 && arr($text,$rasek) && $hohante===0 )
{ 
    foreach ($text as $value)
    {
        $value1[] = preg_replace($rasek,$ras1ek,$value);
    }
    $text = $value1;
echo "<p class = sa >By ekAjuttarapade NaH (".link_sutra("8.4.12").") :</p>\n";
echo "<p class = sa >एकाजुत्तरपदे णः (८.४.१२) :</p>\n";
display(0);   
}
if (sub(array("SrIpAn+i","vftrahan+"),blank(0),blank(0),0) && !arr($text,'/[+]$/')) // Patch for zrIpANi
{
    $text=one(array("SrIpAn+i","vftrahan+"),array("SrIpAR+i","vftrahaR+"),0);
    echo "<p class = sa >By ekAjuttarapade NaH (".link_sutra("8.4.12").") :</p>\n";
    echo "<p class = sa >एकाजुत्तरपदे णः (८.४.१२) :</p>\n";
    display(0);   
}
/* Patch to remove the + sign */
// For dvitva, + sign pauses many problems. Now we don't have to remember what was prakRti and what was pratyaya. Therefore we can afford to remove + sign now.
if (arr($text,'/['.pc('hl').' ][+]['.pc('hl').']/') || arr($text,'/[HM!][+]['.pc('hl').']/'))
{
$text = three($hl,array("+"," +"),$hl,$hl,array(""," "),$hl,0);    
$text = three(array("H","M","!"),array("+"),$hl,array("H","M","!"),array(""),$hl,0);    
}
if ( arr($text,'/['.pc('ac').'HM! ][+]['.pc('ac').']/') || arr($text,'/[HM!][+]['.pc('ac').']/') )
{  
$text = one(array("+"),array(" "),0);
}
if ( arr($text,'/['.pc('ac').' ][+]['.pc('hl').'MH]/') || arr($text,'/[+]$/') )
{  
$text = one(array("+"),array(""),0);
}
if ( arr($text,'/['.pc('hl').'][+]['.pc('ac').']/') )
{  
$text = one(array("+"),array(""),0);
}


/* AcAryAdaNatvaM ca (vA 2477) */
if (sub(array("AcAryAnI"),blank(0),blank(0),0))
{
    echo "<p class = st >By AcAryAdaNatvaM ca (vA 2477) :</p>\n";
    echo "<p class = st >आचार्यादणत्वं च (अा २४७७) :</p>\n";     
    display(0);
    $AcArya=1;
} else {$AcArya=0; }
/* aTkupvAGnumvyavAye'pi (8.4.2) and na padAntasya (8.4.37) */
/* RvarNAnnasya NatvaM vAcyam (vA 4969) */
/* na padAntasya 8.4.37) */
// The issue is identifying samAnapada. Can't be coded properly as of now.
$ras = '/([rzfF])([aAiIuUfFxXeoEOhyvrkKgGNpPbBmM+]*)([n])/';
$rasend = '/([rzfF])([aAiIuUfFxXeoEOhyvrkKgGNpPbBmM+]*)([n])$/';
$rasgrep= '/([rzfF][aAiIuUfFxXeoEOhyvrkKgGNpPbBmM+]*[n])/';
$ras1 = '$1$2R'; 
if (arr($text,$rasend) && $hohante===0 && $AcArya===0 )
{
echo "<p class = pa >By na padAntasya 8.4.37), application of aTkupvAGnumvyavAye'pi (".link_sutra("8.4.2").") is barred. </p>\n";
echo "<p class = pa >न पदान्तस्य (८.४.३७) से अट्कुप्वाङ्नुम्व्यवायेऽपि का निषेध हुआ है । </p>\n";     
display(0);    
}
if (arr($text,$ras) && (!arr($text,$rasend) || count(preg_split($rasgrep,$text[0]))>2 ) && $hohante===0 && $AcArya===0 && $_GET['cond2_16_2_1']!=="2" && !sub(array("UruBinn"),blank(0),blank(0),0)) 
{ 
    foreach ($text as $value)
    {
        if (preg_match('/([rzfF])([aAiIuUfFxXeoEOhyvrkKgGNpPbBmM+]*)([n])/',$value) )
        {
        $value = preg_replace($ras,$ras1,$value);
        $value = preg_replace('/[R]$/','n',$value);
        $value1[] = $value;
        }
        else
        {
        $value1[] = $value;    
        }
    }
$text = $value1;
$value1 = array();
if(sub(array("trihAyaR","caturhAyaR",),blank(0),blank(0),0) && $hohante===0 && $_GET['cond2_16_2_1']==="1")
{
echo "<p class = sa >By tricaturbhyAM hAyanasya NatvaM vAcyam (vA 5038) :</p>\n";
echo "<p class = sa >त्रिचतुर्भ्यां हायनस्य णत्वं वाच्यम्‌ (वा ५०३८) :</p>\n";     
}
elseif(arr($text,'/([fF])([R])/') && $hohante===0 && $AcArya===0)
{
echo "<p class = sa >By RvarNAnnasya NatvaM vAcyam (vA 4969) :</p>\n";
echo "<p class = sa >ऋवर्णान्नस्य णत्वं वाच्यम्‌ (वा ४९६९) :</p>\n";     
}
elseif(arr($text,'/([rzf])([aAiIuUfFxXeoEOhyvrkKgGNpPbBmM+]*)([R])/') && $hohante===0 && $AcArya===0 )
{
echo "<p class = sa >By aTkupvAGnumvyavAye'pi (".link_sutra("8.4.2").") :</p>\n";
echo "<p class = sa >अट्कुप्वाङ्नुम्व्यवायेऽपि (८.४.२) :</p>\n";     
}
display(0);
}
/* stoH zcunA zcuH (8.4.40) */
$stu = array("s","t","T","d","D","n"); // sakAra and tavarga.
$zcu = array("S","c","C","j","J","Y"); // zakAra and cavarga.
if(sub($stu,$zcu,blank(0),0))
{
$text = two(array("s"),$zcu,array("S"),$zcu,0); // sakAra followed by cavarga.
$text = two(array("t"),$zcu,array("c"),$zcu,0); // tavarga followed by cavarga.
$text = two(array("T"),$zcu,array("C"),$zcu,0);
$text = two(array("d"),$zcu,array("j"),$zcu,0);
$text = two(array("D"),$zcu,array("J"),$zcu,0);
$text = two(array("n"),$zcu,array("Y"),$zcu,0);
echo "<p class = sa >By stoH zcunA zcuH (".link_sutra("8.4.40").") :</p>\n";
echo "<p class = sa >स्तोः श्चुना श्चुः (८.४.४०) :</p>\n";
display(0);
}
/* stoH zcunA zcuH (8.4.40) and zAt (8.4.44) */
$zcu1= array("c","C","j","J","Y"); // zAt prevents application in case of zakAra being first letter. Therefore created a new array without zakAra.
if(sub($zcu1,$stu,blank(0),0))
{
$text = two($zcu1,array("s"),$zcu1,array("S"),0); 
$text = two($zcu1,array("t"),$zcu1,array("c"),0); 
$text = two($zcu1,array("T"),$zcu1,array("C"),0); 
$text = two($zcu1,array("d"),$zcu1,array("j"),0);
$text = two($zcu1,array("D"),$zcu1,array("J"),0); 
$text = two($zcu1,array("n"),$zcu1,array("Y"),0); 
$text = two(array("S"),array("s"),array("S"),array("S"),0); // z+s=z+z
    echo "<p class = sa >By stoH zcunA zcuH (".link_sutra("8.4.40").") and zAt (".link_sutra("8.4.44").") :</p>\n";
    echo "<p class = sa >स्तोः श्चुना श्चुः (८.४.४०) तथा शात्‌ (८.४.४४) :</p>\n";
display(0);
}
/* anAmnavatinagarINAmiti vAcyam (vA 5016) */
$shtu = array("z","w","W","q","Q","R",); // SakAra and Tavarga.
if (sub($shtu,array("nAm","navat","nagar"),blank(0),0) && $allopo!==1)
{
$text = two($shtu,array("nAm","navat","nagar"),$shtu,array("RAm","Ravat","Ragar"),0);
echo "<p class = sa >By na padAntATToranAm (".link_sutra("8.4.42").") and anAmnavatinagarINAmiti vAcyam (vA 5016) :</p>\n";
echo "<p class = sa >न पदान्ताट्टोरनाम्‌ (८.४.४२) तथा अनाम्नवतिनगरीणामिति वाच्यम्‌ (वा ५०१६) :</p>\n";
display(0);
if (sub($shtu,array("Ravat","Ragar"),blank(0),0) && $allopo!==1)
{
$text = two($shtu,array("Ravat","Ragar"),array("R","R","R","R","R","R"),array("Ravat","Ragar"),0);
    echo "<p class = sa >By STunA STuH (".link_sutra("8.4.41").") :</p>\n";
    echo "<p class = sa >ष्टुना ष्टुः (८.४.४१) :</p>\n";
    display(0);
}
if (sub($shtu,array("RAm"),blank(0),0) && $allopo!==1) // yaro'nunAsike'nunAsiko vA is mandatory in pratyayas. nAm being a pratyaya, we are displaying this message.
{
$text = two($shtu,array("RAm"),array("R","R","R","R","R","R"),array("RAm"),0);
    echo "<p class = sa >By yaro'nunAsike'nunAsiko vA (".link_sutra("8.4.45").") and pratyaye bhASAyAm nityam (vA) :</p>\n";
    echo "<p class = sa >यरोऽनुनासिकेऽनुनासिको वा (८.४.४५) तथा प्रत्यये भाषायां नित्यम्‌ (वार्तिक) :</p>\n";
    display(0);
}
}
/* stoH STunA STuH (8.4.41) and na padAntATToranAm (8.4.41) and toH Si (8.4.43) */
$Tu = array("w","W","q","Q","R",); $tu = array("t","T","d","D","n");

if(((sub($shtu,$stu,blank(0),0)|| sub($stu,$shtu,blank(0),0))) && $allopo===1 )
{
    echo "<p class = pa >STunA STuH (".link_sutra("8.4.41").") is prevented by sthAnivadbhAva of allopa. The same result can be obtained by asiddhatva of bahiraGga allopa in kAryakAlapakSa.</p>\n";
    echo "<p class = pa >पूर्वस्मादपि विधावल्लोपस्य स्थानिवद्भावान्न ष्टुत्वम्‌ । कार्यकालपक्षे बहिरङ्गस्य अल्लोपस्य असिद्धत्वाद्वा ।</p>\n";
    display(0);    
}        
if(((sub($shtu,$stu,blank(0),0)|| sub($stu,$shtu,blank(0),0))) && $allopo===0 )
{
$text = two(array("z"),$stu,array("z"),$shtu,0);
$text = two(array("s"),$shtu,array("z"),$shtu,0);
$text = two(array("t"),$Tu,array("w"),$Tu,0);
$text = two(array("T"),$Tu,array("W"),$Tu,0);
$text = two(array("d"),$Tu,array("q"),$Tu,0);
$text = two(array("D"),$Tu,array("Q"),$Tu,0);
$text = two(array("n"),$Tu,array("R"),$Tu,0);
    if ($pada === "pratyaya" && (sub($Tu,$tu,blank(0),0)))
    {
        $text = two(array("w"),$tu,array("w"),$Tu,0);
        $text = two(array("W"),$tu,array("W"),$Tu,0);
        $text = two(array("q"),$tu,array("q"),$Tu,0);
        $text = two(array("Q"),$tu,array("Q"),$Tu,0);
        $text = two(array("R"),$tu,array("R"),$Tu,0);
    }       
    echo "<p class = sa >By STunA STuH (".link_sutra("8.4.41").") and na padAntATToraNam (".link_sutra("8.4.42").") and toH Si (".link_sutra("8.4.43").") :</p>\n";
    echo "<p class = sa >ष्टुना ष्टुः (८.४.४१), न पदान्ताट्टोरणाम्‌ (८.४.४२) तथा तोः षि (८.४.४३) :</p>\n";
    display(0);
}
/* Dho Dhe lopaH (8.3.13) */
if (sub(array("Q"),array("Q"),blank(0),0))
{
    $text = three(array("e","o","E","O","M","H"),array("Q"),array("Q"),array("e","o","E","O","M","H"),array(""),array("Q"),0);
    $text = two(array('Q'),array('Q'),array(''),array('#Q'),0); 
    echo "<p class = sa >By Dho Dhe lopaH (".link_sutra("8.3.13").") :</p>\n";
    echo "<p class = sa >ढो ढे लोपः (८.३.१३) 2:</p>\n";
    display(0); 
    $dho = 1;  // 0 - This sUtra has not applied. 1 - This sUtra has applied.
	/* sahivahorodavarNasya (6.3.111) */
	if (sub(array("va","sa","vA","sA"),array("#Q"),blank(0),0) && ends(array($fo),array("vaha!","zaha!"),4))
	{
		$text = two(array("va","sa","vA","sA"),array("#Q"),array("vo","so","vo","so",),array("Q"),0);
		echo "<p class = sa >By sahivahorodavarNasya (".link_sutra("6.3.111").") :</p>\n";
		echo "<p class = sa >सहिवहोरोदवर्णस्य (६.३.१११) :</p>\n";
		display(0); 
	}
} else { $dho = 0; }
/* ro ri (8.3.14) */
if (sub(array("r"),array("r"),blank(0),0))
{
    $text = three(array("e","o","E","O","M","H"),array("r"),array("r"),array("e","o","E","O","M","H"),array(""),array("r"),0);
    $text = two(array('r'),array('r'),array(''),array('#r'),0); 
    echo "<p class = sa >By ro ri (".link_sutra("8.3.14").") :</p>\n";
    echo "<p class = sa >रो रि (८.३.१४) :</p>\n";
    display(0);
    $ro = 1; // 0 - This sUtra has not applied. 1 - This sUtra has applied.
} else { $ro = 0; }
/* Dhralope pUrvasya dIrgho'NaH (6.3.111) */
$ana = array("a","A","i","I","u","U","f","F","x","X");
$anna = array("A","A","I","I","U","U","F","F","X","X");
if (($ro ===1 || $dho===1) && sub($ana,array('#r',"#Q"),blank(0),0))
{
$text = two($ana,array('#r','#Q'),$anna,array('r','Q'),0);
echo "<p class = sa >By Dhralope pUrvasya dIrgho'NaH (".link_sutra("6.3.111").") :</p>\n";
echo "<p class = sa >ढ्रलोपे पूर्वस्य दीर्घोऽणः (६.३.१११) :</p>\n";
display(0);
}
/* yaro'nunAsike'nunAsiko vA (8.4.45) */ // this is applicable to only sparzas.
$yara = array("J","B","G","Q","D","j","b","g","q","d","K","P","C","W","T","c","w","t","k","p"); // array of yar varNas.
$anunasikarep = array("Y","m","N","R","n","Y","m","N","R","n","N","m","Y","R","n","Y","R","n","N","m"); // Their corresponding replacement.
$anunasika = array("N","Y","R","n","m"); // array of anunAsika.
if (sub($yara,array("+"),$anunasika,0) && $pada === "pada")
{
$text = three($yara,array("+"),$anunasika,$anunasikarep,array("+"),$anunasika,1);
echo "<p class = sa >By yaro'nunAsike'nunAsiko vA (".link_sutra("8.4.45").") :</p>\n";
echo "<p class = sa >यरोऽनुनासिकेऽनुनासिको वा (८.४.४५) :</p>\n";
display(0);
}
if (sub($yara,array("+"),$anunasika,0) && $pada === "pratyaya")
{
$text = two($yara,$anunasika,$anunasikarep,$anunasika,0);
echo "<p class = sa >By yaro'nunAsike'nunAsiko vA (".link_sutra("8.4.45").") :</p>\n";
echo "<p class = sa >यरोऽनुनासिकेऽनुनासिको वा (८.४.४५) :</p>\n";
display(0);
}
/* nAdinyAkroze putrasya (8.4.48) */
if (sub(array('putrAdin'),blank(0),blank(0),0))
{
    echo "<p class = sa >By nAdinyAkroze putrasya (".link_sutra("8.4.48").") - If Akroza is meant : The dvitva doesn't happen. Otherwise dvitva will happen.</p>\n";
    echo "<p class = sa >नादिन्याक्रोशे पुत्रस्य (८.४.४८) - यदि आक्रोश के अर्थ में प्रयुक्त हुआ है, तब द्वित्व नहीं होगा । अन्यथा द्वित्व होगा ।</p>\n";
}
/* vA hatajagdhayoH (vA 5022) */
if (sub(array("putrahatI"),blank(0),blank(0),0))
{
echo "<p class = sa >By vA hatajagdhayoH (vA 5022) :</p>\n";
echo "<p class = sa >वा हतजग्धयोः (वा ५०२२) :</p>\n";
display(0);
}
if (sub(array('putrajagDI'),blank(0),blank(0),0))
{
echo "<p class = sa >By vA hatajagdhayoH (vA 5022) :</p>\n";
echo "<p class = sa >वा हतजग्धयोः (वा ५०२२) :</p>\n";
display(0);
}
/* cayo dvitIyAH zari pauSkarasAderiti vAcyam (vA 5023) */
/*if (sub(array("N","R"),prat('Sr'),blank(0),0))
{
$text = two(array("Nk","Rw"),prat('Sr'),array("NK","RW"),prat('Sr'),1);
echo "<p class = sa >By cayo dvitIyAH zari pauSkarasAderiti vAcyam (vA 5023) :</p>\n";
echo "<p class = sa >चयोः द्वितीयाः शरि पौष्करसादेरिति वाच्यम्‌ (वा ५०२३) :</p>\n";
display(0); $cayo=1; 
} else {$cayo = 0; }*/
/*anaci ca (8.4.47)*/ // Here the sudhI + upAsya - what about the Asy - Assy is possbile ? Code gives it. But there are 4 options. Code gives two only.
// The cause for using $hrasva instead of $ac is that the dIrgha vowels are debarred by dIrghAdAcAyANAm.
// Here instead of using pratyAhAra hl, we shall do manual enumeration of all the members. Because of "anusvAravisargajihvAmUlIyopadhmAnIyayamAnAmakAropari zarSu ca pAThasyopasaGkhyAtatvenAnusvArasyApyactvAt (in derivation of samskAra) 
/*$hrasvaplus = array("M","!","'"); // additionalities mentioned in saMskAra derivation.
$hala1 = array("y","v","l","Y","m","N","R","n","J","B","G","Q","D","j","b","g","q","d","K","P","C","W","T","c","w","t","k","p","S","z","s","M",);
$hala2 = array("h","y","v","r","l","Y","m","N","R","n","J","B","G","Q","D","j","b","g","q","d","K","P","C","W","T","c","w","t","k","p","S","z","s","M",); // added h,y
if(sub($hrasva,$hala1,$hala2,0))
{
    $text = dvitva($hrasva,$hala1,$hala2,array(""),2,1);
    if (sub($dirgha,$hala1,$hala2,0))
    {
    echo "<p class = sa >By anaci ca (".link_sutra("8.4.47").") and dIrghAdAcAryANAm (".link_sutra("8.4.52").") :</p>\n"; 
    echo "<p class = sa >अनचि च (८.४.४७): तथा दीर्घादाचार्याणाम्‌ (८.४.५२) :</p>\n";     
    }
    else
    {
    echo "<p class = sa >By anaci ca (".link_sutra("8.4.47")."):</p>\n"; 
    echo "<p class = sa >अनचि च (८.४.४७):</p>\n";     
    }
display(1);
}
if(sub($hrasvaplus,$hl,$hala2,0))
{
    $text = dvitva($hrasvaplus,$hl,$hala2,array(""),2,1);
    if (sub($dirgha,$hl,$hala2,0))
    {
    echo "<p class = sa >By anaci ca (".link_sutra("8.4.47").") and dIrghAdAcAryANAm (".link_sutra("8.4.52").") :</p>\n"; 
    echo "<p class = sa >अनचि च (८.४.४७): तथा दीर्घादाचार्याणाम्‌ (८.४.५२) :</p>\n";     
    }
    else
    {
    echo "<p class = sa >By anaci ca (".link_sutra("8.4.47")."):</p>\n"; 
    echo "<p class = sa >अनचि च (८.४.४७):</p>\n";     
    }
    display(1);
}
if(checkarray($dirgha,$hl,array('r','l'),blank(0))!==0 && $sthanivadbhav===1) // for function checkarray, see function.php
{
$text = dvitva($dirgha,$hala1,$hala2,array(""),2,1);
    if (sub($dirgha,$hl,array('r','l'),0))
    {
    echo "<p class = sa >By anaci ca (".link_sutra("8.4.47").") and dIrghAdAcAryANAm (".link_sutra("8.4.52").") :</p>\n"; 
    echo "<p class = sa >अनचि च (८.४.४७): तथा दीर्घादाचार्याणाम्‌ (८.४.५२) :</p>\n";     
    }
    else
    {
    echo "<p class = sa >By anaci ca (".link_sutra("8.4.47")."):</p>\n"; 
    echo "<p class = sa >अनचि च (८.४.४७):</p>\n";     
    }
display(1);
}*/
/* By anaci ca (according to mahAbhASya example of vAkk) */ 
/*if (arr($text,'/['.flat($ac).']['.flat($hl).']$/') || (preg_match('/['.flat($ac).']['.flat($hl).']$/',$first) && $input === $first ))
{
    foreach ($text as $value)
    {
        $split = str_split($value);
        $post = $split[count($split)-1];
        if (in_array($post,$hl))
        {
        $pre = chop($value,$post); 
        $value1[] = str_replace($value,$pre.$post.$post,$value);
        }
        else
        {
            $value1[] = $value;
        }
    }
    $text = array_merge($text,$value1);
    $text = array_unique($text);
    $text = array_values($text);
    $value1 = array();
    if (sub($dirgha,$hala1,$hala2,0))
    {
    echo "<p class = sa >By anaci ca (".link_sutra("8.4.47").") and dIrghAdAcAryANAm (".link_sutra("8.4.52").") :</p>\n"; 
    echo "<p class = sa >अनचि च (८.४.४७): तथा दीर्घादाचार्याणाम्‌ (८.४.५२) :</p>\n";     
    }
    else
    {
    echo "<p class = sa >By anaci ca (".link_sutra("8.4.47")."):</p>\n"; 
    echo "<p class = sa >अनचि च (८.४.४७):</p>\n";     
    }
    display(1);
}*/
/* zaraH khayaH (vA 5019) */
/*$shara = array("S","z","s",); // zar varNas.
if (sub($shara,prat('Ky'),blank(0),0))
{
$text = dvitva($shara,prat('Ky'),array(""),array(""),2,1);
echo "<p class = sa >zaraH khayaH (vA 5019) :</p>\n";
echo "<p class = sa >शरः खयः (वा ५०१९) :</p>\n";
display(1);
}*/
/* aco rahAbhyAM dve (8.4.46) */ 
/*$rh = array("r","h"); // r,h
if (sub($ac,$rh,prat('yr'),0))
{
    if (sub($rh,array("S","z","s"),$ac,0)) // patch to show prohibition by zaro'ci.
    {
        echo "<p class = sa >By zaro'ci (".link_sutra("8.4.48").") :</p>\n";
        echo "<p class = hn >N.B.: zaro'ci bars application of aco rahAbhyAm dve</p>\n";
        echo "<p class = sa >शरोऽचि (८.४.४८) :</p>\n";
        echo "<p class = hn >शरोऽचि अचो रहाभ्यां द्वे के प्रयोग का निषेध करता है ।</p>\n";
        display(0);
    }
    else
    {
        $text = dvitva($ac,$rh,prat('yr'),array(""),3,1);
        echo "<p class = sa >By aco rahAbhyAM dve (".link_sutra("8.4.46").") :</p>\n";
        echo "<p class = sa >अचो रहाभ्यां द्वे (८.४.४६) :</p>\n";
        display(1);
    }
}*/
/* triprabhRtiSu zAkaTAyanasya (8.4.50)*/
/*if (checkarray($ac,$hl,$hl,$hl) === 1)
{
echo "<p class = hn >N.B.: By triprabhRtiSu zAkaTAyanasya (".link_sutra("8.4.50")."), the dvitva is optionally not done in cases where there are more than three hals appearing consecutively. e.g. indra - inndra.  </p>\n";
echo "<p class = hn >त्रिप्रभृतिषु शाकटायनस्य (८.४.५०) - तीन या उससे ज्यादा हल्‌ अगर हो तब शाकटायन के मत में द्वित्व नहीं होता है ।</p>\n";
}*/
/* sarvatra zAkalyasya (8.4.51) */
// It is not coded separately. It is sent as a message in all display function when 1 is selected as option. 
/* dIrghAdAcAryANAm (8-4-52) */
// Not coded separately, because we did dvitva only for $hrasva, and not for 'ac'. So this is already taken care of.
/* jhalAM jaz jhaSi (8.4.53) */
while(sub(array_diff(prat('Jl'),prat('jS')),prat('JS'),blank(0),0) !== false) // check whether we should remove jaz from jhal?
{
    if(sub(prat('Jl'),prat('JS'),blank(0),0))
    {
    $text = two(prat('Jl'),prat('JS'),savarna(prat('Jl'),prat('jS')),prat('JS'),0);
    echo "<p class = sa >By jhalAM jaz jhazi (".link_sutra("8.4.53")."):</p>\n";
    echo "<p class = sa >झलां जश्‌ झशि (८.४.५३):</p>\n";
    display(0);
    }
}
/* yaNo mayo dve vAcye (vA 5018) yaN in paJcamI and may in SaSThI)*/
/*if (sub($hrasva,prat('yR'),prat('my'),0))
{
$text = dvitva(prat('yR'),prat('my'),array(""),array(""),2,1);
echo "<p class = sa >By yaNo mayo dve vAcye (yaN in paJcamI and may in SaSThI) (vA 5018) :</p>\n";
echo "<p class = sa >यणो मयो द्वे वाच्ये (यण्‌ पञ्चमी तथा मय्‌ षष्ठी) (वा ५०१८) :</p>\n";
display(1); 
}
if (sub($dirgha,prat('yR'),prat('my'),0) && $sthanivadbhav ===1)
{
$text = dvitva(prat('yR'),prat('my'),array(""),array(""),2,1);
echo "<p class = sa >By yaNo mayo dve vAcye (yaN in paJcamI and may in SaSThI) (vA 5018) :</p>\n";
echo "<p class = sa >यणो मयो द्वे वाच्ये (यण्‌ पञ्चमी तथा मय्‌ षष्ठी) (वा ५०१८) :</p>\n";
display(1); 
}*/
/* yaNo mayo dve vAcye (vA 5018) may in paJcamI and yaN in SaSThI)*/
/*if (sub($hrasva,prat('my'),prat('yR'),0))
{
$text = dvitva(prat('my'),prat('yR'),array(""),array(""),2,1);
echo "<p class = sa >By yaNo mayo dve vAcye (may in paJcamI and yaN in SaSThI) (vA 5018):</p>\n";
echo "<p class = sa >यणो मयो द्वे वाच्ये (मय्‌ पञ्चमी तथा यण्‌ षष्ठी) (वा ५०१८) :</p>\n";
display(1);
}
if (sub($dirgha,prat('my'),prat('yR'),0) && $sthanivadbhav ===1)
{
$text = dvitva(prat('my'),prat('yR'),array(""),array(""),2,1);
echo "<p class = sa >By yaNo mayo dve vAcye (may in paJcamI and yaN in SaSThI) (vA 5018):</p>\n";
echo "<p class = sa >यणो मयो द्वे वाच्ये (मय्‌ पञ्चमी तथा यण्‌ षष्ठी) (वा ५०१८) :</p>\n";
display(1);
}*/
/* vA'vasAne (8.4.54) */
if (arr($text,'/['.pc('Jl').']$/'))
{
$text = last(prat('Jl'),savarna(prat('Jl'),prat('cr')),1);
echo "<p class = sa >By vA'vasAne (".link_sutra("8.4.54").") :</p>\n";
echo "<p class = sa >वाऽवसाने (८.४.५४) :</p>\n";
    display(0);
}
/* khari ca (8.4.55) */ 
$Jl1 = array("J","B","G","Q","D","j","b","g","q","d","K","P","C","W","T","c","w","t","k","p","S","z","s","h"); // complete jhal.
$Jl2 = array("J","B","G","Q","D","j","b","g","q","d","K","P","C","W","T","h"); // jhal without car.
if ($cayo!==1)
{
    while(sub($Jl2,prat('Kr'),blank(0),0) !== false) // this rule can apply add infinitum.
    {
        if ( (sub($Jl1,prat('Kr'),blank(0),0) || $dhut === 1))
        {
        $text = two($Jl1,prat('Kr'),savarna(prat('Jl'),prat('cr')),prat('Kr'),0);
        echo "<p class = sa >By khari ca (".link_sutra("8.4.55").") :</p>\n";
        echo "<p class = sa >खरि च (८.४.५५) :</p>\n";
        display(0);
        }
    }
}
if (sub(prat('cr'),prat('Kr'),blank(0),0) || $dhut === 1) // parjanyavallakSaNapravRttiH.
    {
    echo "<p class = sa >By khari ca (".link_sutra("8.4.55").") :</p>\n";
    echo "<p class = hn >N.B. By khari ca (".link_sutra("8.4.55")."), 'car' varNas give 'car' varNas only as their savarNa :</p>\n";
    echo "<p class = sa >खरि च (८.४.५५) :</p>\n";
    echo "<p class = hn >खरि च (८.४.५५) से चर्‌ वर्णों का सवर्ण चर्‌ ही रहता है ।</p>\n";
    display(0);
    }
/* aNo'pragRhyasyAnunAsikaH (8.4.57) */
/*if (arr($text,'/[aAiIuUfFxX]$/'))
{
    $text = last(array("a","A","i","I","u","U","f","F","x","X"),array("a!","A!","i!","I!","u!","U!","f!","F!","x!","X!"),1);
    echo "<p class = sa >By aNo'pragRhyasyAnunAsikaH (".link_sutra("8.4.57").") :</p>\n";
    echo "<p class = sa >अणोऽप्रगृह्यस्यानुनासिकः (८.३.५७) :</p>\n";
display(0);
}*/
/* anusvArasya yayi parasavarNaH (8.4.58) */
$mm = array("My","Mv","Mr","Ml","MY","Mm","MN","MR","Mn","MJ","MB","MG","MQ","MD","Mj","Mb","Mg","Mq","Md","MK","MP","MC","MW","MT","Mc","Mw","Mt","Mk","Mp"); // anusvAra+yay.
$pa = array("!yy","!vv","!rr","!ll","YY","mm","NN","RR","nn","YJ","mB","NG","RQ","nD","Yj","mb","Ng","Rq","nd","NK","mP","YC","RW","nT","Yc","Rw","nt","Nk","mp"); // its replacement.
if (sub(array("M"),prat('yy'),blank(0),0) && (in_array(1,$num) || in_array($so,$tiG) ))
{
$text = one($mm,$pa,0);
echo "<p class = sa >By anusvArasya yayi parasavarNaH (".link_sutra("8.4.58").") :</p>\n";
echo "<p class = sa >अनुस्वारस्य ययि परसवर्णः (८.४.५८) :</p>\n";
display(0);
}
/* anusvArasya yayi parasavarNaH (8.4.58) and vA padAntasya (8.4.59) */
elseif (sub(array("M"),prat('yy'),blank(0),0))
{
$text = one($mm,$pa,1);
echo "<p class = sa >By anusvArasya yayi parasavarNaH (".link_sutra("8.4.58").") and vA padAntasya (".link_sutra("8.4.59").") :</p>
    <p class = hn >N.B.: The change of anusvARa to parasavarNa is mandatory for non padAnta conjoints. For padAnta conjoints, it is optional.</p>\n";
echo "<p class = sa >अनुस्वारस्य ययि परसवर्णः (८.४.५८) तथा वा पदान्तस्य (८.४.५९) :</p>
    <p class = hn >पदान्त में पाक्षिक है । अपदान्त के लिए अनिवार्य है ।</p>\n";
display(0);
}
/* torli (8.4.60) */
$to = array("tl","Tl","dl","Dl","nl"); // combinations satisfying rule conditions.
$lirep = array("ll","ll","ll","ll","l!l",); // its replacement.
while(sub($to,blank(0),blank(0),0) !== false)
{
if (sub($to,blank(0),blank(0),0))
{
$text = one($to,$lirep,0);
echo "<p class = sa >By torli (".link_sutra("8.4.60").") :</p>\n";
echo "<p class = sa >तोर्लि (८.४.६०) :</p>\n";
display(0);
}
}
/* jhayo ho'nyatarasyAm (8.4.62) */ 
$Jy = array("Jh","Bh","Gh","Qh","Dh","jh","bh","gh","qh","dh","Kh","Ph","Ch","Wh","Th","ch","wh","th","kh","ph",); // combination satisfying condition.
$h1 = array("JJ","BB","GG","QQ","DD","jJ","bB","gG","qQ","dD","KG","PB","CJ","WQ","TD","cJ","wQ","tD","kG","pB",); // its replacement.
if (sub($Jy,blank(0),blank(0),0)) 
{
$text = one($Jy,$h1,1);
echo "<p class = sa >By jhayo ho'nyatarasyAm (".link_sutra("8.4.62").") :</p>\n";
echo "<p class = sa >झयो होऽन्यतरस्याम्‌ (८.४.६२) :</p>\n";
display(0);
}
/* zazCho'Ti (8.4.63) and ChatvamamIti vAcyam (vA 5025) */
$Jy = array("JS","BS","GS","QS","DS","jS","bS","gS","qS","dS","KS","PS","CS","WS","TS","cS","wS","tS","kS","pS",); // combination satisfying condition.
$h1 = array("JC","BC","GC","QC","DC","jC","bC","gC","qC","dC","KC","PC","CC","WC","TC","cC","wC","tC","kC","pC",); // its replacement.
$aT = array("a","A","i","I","u","U","f","F","x","X","e","o","E","O","h","y","v","r","l","Y","m","G","R","n"); // am varNas.
if(sub($Jy,$aT,blank(0),0) && $pada === "pada")
{
$text = two($Jy,$aT,$h1,$aT,1);
echo "<p class = sa >By zazCho'Ti (".link_sutra("8.4.63").") and ChatvamamIti vAcyam (vA 5025) :</p>\n";
echo "<p class = sa >शश्छोऽटि (८.४.६३) तथा छत्वममीति वाच्यम्‌ (वा ५०२५) :</p>\n";
display(0);
}
/* halo yamAM yami lopaH (8.4.64) */ 
$duplicate = array("NN","YY","RR","nn","mm","yy","rr","ll","vv",); // combination satisfying condition.
$dup = array("N","Y","R","n","m","y","r","l","v",); // its replacement.
$hl = array("k","K","g","G","N","c","C","j","J","Y","w","W","q","Q","R","t","T","d","D","n","p","P","b","B","m","y","r","l","v","S","z","s","h"); // hal varNas.
if (sub($hl,prat('ym'),prat('ym'),0))
{
$text = two($hl,$duplicate,$hl,$dup,1);
echo "<p class = sa >By halo yamAM yami lopaH (".link_sutra("8.4.64").") :</p>\n";
echo "<p class = sa >हलो यमां यमि लोपः (८.४.६४) :</p>\n";
display(0);
}
/* jharo jhari savarNe (8.4.65) */ 
if(sub(prat('hl'),prat('Jr'),prat('Jr'),0))
{
for ($i=0;$i<count(prat('Jr'));$i++)
{$kkk = array("k","K","g","G"); 
$ccc = array("c","C","j","J",);
$www = array("w","W","q","Q",); 
$ttt = array("t","T","d","D",);
$ppp = array("p","P","b","B"); // savarNa groups.
$text = three(prat('hl'),$kkk,$kkk,prat('hl'),blank(4),$kkk,1);
$text = three(prat('hl'),$ccc,$ccc,prat('hl'),blank(4),$ccc,1);
$text = three(prat('hl'),$www,$www,prat('hl'),blank(4),$www,1);
$text = three(prat('hl'),$ttt,$ttt,prat('hl'),blank(4),$ttt,1);
$text = three(prat('hl'),$ppp,$ppp,prat('hl'),blank(4),$ppp,1);
}
echo "<p class = sa >By jharo jhari savarNe (".link_sutra("8.4.65").") :</p>\n";
echo "<p class = sa >झरो झरि सवर्णे (८.४.६५) :</p>\n";
display(0);
}
/* nipAta forms */ 
/* apaspRdhethAmAnRcurAnRhuzcicyuSetityAhAzrAtAHzritamAzIrAzIrtAH (6.1.35) */ 
if(sub(array("apasparDeTAm","AnarcuH","AnarhuH","cucyuvize","tatyAja"),blank(0),blank(0),0) && $veda===1)
// pending zrAtAH.... onwards. Original words to be found out.
{
	$text = one(array("apasparDeTAm","AnarcuH","AnarhuH","cucyuvize","tatyAja"),array("apaspfDeTAm","AnfcuH","AnfhuH","cicyuze","tityAja"),0);
	echo "<p class = sa >By apaspRdhethAmAnRcurAnRhuzcicyuSetityAhAzrAtAHzritamAzIrAzIrtAH (".link_sutra("6.1.35").") :</p>\n";
	echo "<p class = sa >अपस्पृधेथामानृचुरानृहुश्चिच्युषेतित्याजश्राताःश्रितमाशीराशीर्तः (६.१.३५) :</p>\n";
	display(0);
}
/* Final Display */
echo "<p class = sa >Final forms are :</p>\n";
echo "<p class = sa >आखिरी रूप हैं :</p>\n";
display(0);
echo "<hr>\n";
/* setting the $pada back to pratyaya for next use */
$pada="pratyaya";
$id_dhAtu=$id_original;
$id_pratyaya=$id_original_pratyaya;
$it = array();
$itprakriti = array();
$itpratyaya = array();
$Agama=array();
$TAp=0; $DAp=0; $cAp=0; $GIp=0; $GIn=0; $GIS=0; $kGiti=0; $abhyasta=0; $ajAdyataSTAp=0; $tusma=0; $upasarga_joined=0;
}
echo "</body>
</html>";
//ob_end_flush();
/*if (isset($argv[0]))
{
	ob_end_clean();
}
else
{
	ob_end_flush();
}*/
//fputs($outfile,ob_get_contents());
//ob_end_flush();
//fclose($outfile);
$logfile = fopen('D:\\!sorting\\verboutput\\log.txt','a+');
fputs($logfile,"Request completed on :".date('D, d M Y H:i:s')."\n");
fputs($logfile,"------------------------------\n");
fclose($logfile);

function printtofile($buffer)
{
global $outfile;
fputs($outfile,$buffer);
return $buffer;
}

?>