<?php
/*
  v 1.0 - (w) 2023 by Nima Ghassemi Nejad
        
  generate final edomi lbs

  run

  php compile.php

*/

function insertDisclaimer (&$txt) {
    global $disclaimer;
    $txt = str_replace('__INSERT_DISCLAIMER__', $disclaimer, $txt);    
}

// php -d display_errors=on compress.php
$compressMe = array ("../huev2.php"     => array ("disclaimer" => 1,
                                                  "fname"      => "huev2.txt"),
                     
                     "../hue_admin.php" => array ("disclaimer" => 0,
                                                  "fname"      => "hue_admin.txt"),
);

$disclaimer = file_get_contents("../disclaimer");

foreach ($compressMe as $k =>$v) {
    //$data = file_get_contents($k);

    $data = php_strip_whitespace ($k);
    if ($v['disclaimer'] == 1)
        $data = "<?php /* $disclaimer */ ?>". $data;

    $gzdata = gzcompress($data, 9,FORCE_DEFLATE);

    $ec = $encoded[$v['fname']] = base64_encode($gzdata);

    // only for debugging
    //file_put_contents($v['fname'], $ec);
}

$lbsName = "19002629_lbs.php";

$lbs = file_get_contents ("../template_".$lbsName);

foreach ($encoded as $key=>$val) {
    $lbs = str_replace('__'.$key.'__', $val, $lbs);
}

insertDisclaimer ($lbs);

    
file_put_contents($lbsName, $lbs);

?>
