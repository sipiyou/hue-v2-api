###[DEF]###
[name           = HueV2-API::Connector v0.989 ]

[e#1 trigger    = (Re)Start/Stopp ]
[e#2 important  = HUE Gateway IP]
[e#3 important  = HUE Token ]
[e#4 important  = Lampen automatisch einschalten#init=0 ]

[e#8 important  = Admin-Interface#init=1 ]
[e#9            = DEBUG#init=0]

###[/DEF]###

###[HELP]###
<a class="cmdButton" href="../hue_admin.php" target="_hueAdmin" >Administration</a>

Dieser LBS stellt die Verbindung zum HUE-Gateway über die HUE V2 API, die Callbacks unterstützt.
Somit ist kein Polling mehr notwendig. Hierdurch wird die Netzwerklast verringert und die Reaktionsgeschwindigkeit erhöht.
 
Der Baustein darf nur einmal im Projekt verwendet werden!

E1  : Betriebsmodus
      0=LBS stoppen
      1=LBS starten.

E2  : IP-Adresse vom HUE Gateway
E3  : HUE Token. Es kann der Token aus der V1-API verwendet werden.
E4  : Lampen automatisch einschalten.
      Eine Lampe/Gruppe wird automatisch eingeschaltet, wenn die Parameter gesetzt werden.
      Falls das nicht erwünscht ist, werden die Parameter vom HUE-Gateway verworfen.
      Default = Aus.

E8  : Admin-Interface aktivieren (=1) oder 0 = deaktivieren
E9  : Debug: [0..3], 0= Kritisch, 1=Info, 2= Debug, 3 = LowLevel
 
Der LBS installiert zusätzlich folgende Dateien:
 EDOMI_ROOT/main/include/php/SmartThings/huev2.php
 EDOMI_ROOT/hue_admin.php

Achtung: LBS benötigt PHP 7.x und setzt HUE-Gateway mit V2-API vorraus

<h1><center>Unterstützte Objekt-Typen</center></h2>
<h2>temperature</h2>
<pre>
    getEnabled             : bool     Funktion ist aktiv / passiv
    getTemperature         : float    Temperatur xx.yy
    getLastChanged         : date     Zeitstempel
</pre>
<h2>device_power</h2>
<pre>
    getBatteryState        : string   normal, low, critical
    getBatteryLevel        : int      Batteriestatus: 0..100%   
</pre>
<h2>motion</h2>
<pre>
    getEnabled             : bool
    getMotionState         : bool     Bewegung ja / nein
    getSensitivityStatus   : string   "set" / "changeinc"
 
    getSensitivityLevel    : int
    getLastChanged         : date     Zeitstempel
</pre>
<h2>light_level</h2>
<pre>
    getEnabled             : bool
    getLightLevel          : int      Lux
    getLastChanged         : date     Zeitstempel 
</pre>
<h2>grouped_light</h2>
<pre>
    getState               : bool     an/aus
    getBrightness          : int      Helligkeit 0..255
 
    setGState              : bool     Lampen ein/ausschalten
    setRGB                 : string   RRGGBB oder #RRGGBB
    setXY                  : string   x;y. Beispiel 0.1234;0.2385
    setHSV                 : string   HHSSVV oder #HHSSVV
    setDPT3                : KNX-DPT3 KO (dimmen)
    setCT                  : int      Farbtemperatur 153..500
    setGBrightness         : int      Dimmwert absolut 0..100%
    setAlert               : int      0 = breathe
    setSignaling           : string   signaling, duration      Beispiel 1;10000
                                      signaling 0 = no_signal
                                                1 = on_off
                                                2 = on_off_color
                                                3 = alternating
                                      nicht alle signaling-Optionen sind für alle Lampen verfügbar.
 </pre>
<h2>light</h2>
<pre>
    getState               : bool     an/aus
    getBrightness          : int      Helligkeit 0..255
    getRGB                 : string   RRGGBB
    getXY                  : string   x;y
    getHSV                 : string   HHSSVV
    getCT                  : int      Farbtemperatur
 
    setState               : bool     Lampen ein/ausschalten
    setRGB                 : string   RRGGBB oder #RRGGBB
    setXY                  : string   x;y    Beispiel 0.1234;0.2385
    setHSV                 : string   HHSSVV oder #HHSSVV
    setDPT3                : KNX-DPT3 KO (dimmen)
    setCT                  : int      Farbtemperatur 153..500
    setBrightness          : int      Dimmwert absolut 0..100%
    setAlert               : int      0 = breathe
    setSignaling           : string   signaling, duration      Beispiel 1;10000
                                      signaling 0 = no_signal
                                                1 = on_off
                                                2 = on_off_color
                                                3 = alternating
                                      nicht alle signaling-Optionen sind für alle Lampen verfügbar.
 </pre>
<h2>zigbee_connectivity</h2>
<pre>
    getStatus              : int      0 = unbekannt, 1=connected, 2=disconnected, 3=connectivity_issue, 4=unidirectional_incoming
    getMACAddress          : string   MAC-Adresse
</pre>
<h2>scene</h2>
<pre>
    setScene               : bool     0/1 = szene abrufen

    getStatus              : int      0 = unbekannt, 1= inaktiv, 2 = statisch, 3 = dynamische palette
    getSpeed               : float    0..1
    getAutoDynamic         : bool     szene automatisch starten oder über setScene
</pre>
<h2>contact</h2>
<pre>
    getEnabled             : bool
    getStatus              : int      0 = unbekannt, 1= contact, 2 = no contact
    getLastChanged         : date     Zeitstempel
</pre>
 
<h2><center>Disclaimer</center></h2>
<b>(c),(w) 2023 by Nima Ghassemi Nejad (ngn928@web.de)

The code provided in this release is the intellectual property of Nima Ghassemi Nejad, and is protected by copyright laws. By accessing and using this code, you agree to the following terms and conditions:

Permission: This code is made available for personal, educational, and non-commercial use only. No part of this code may be used, copied, reproduced, modified, distributed, or transmitted in any form or by any means without the prior written permission from the author.

Commercial Projects: The use of this code in commercial projects or for commercial purposes is strictly prohibited without obtaining explicit written permission from the author. This includes, but is not limited to, using the code in products, services, or applications that are intended for sale, licensing, or any form of commercial distribution.

Modifications: You are not allowed to modify or alter this code without obtaining explicit written permission from the author. Any modifications made to the code without proper authorization may infringe upon the intellectual property rights of the autor.

No Warranty: This code is provided "as is" without any warranty, express or implied. The author makes no representations or warranties regarding the accuracy, functionality, or suitability of this code for any particular purpose. You acknowledge that the use of this code is at your own risk.

Limitation of Liability: In no event shall the author be liable for any direct, indirect, incidental, special, exemplary, or consequential damages arising out of the use or inability to use this code, even if the author has been advised of the possibility of such damages.

Legal Compliance: You agree to comply with all applicable laws, regulations, and third-party rights when using this code. You are solely responsible for ensuring that your use of this code is in compliance with all relevant legal obligations.

If you have any questions or require further information regarding the use of this code, please contact Nima at ngn928@web.de.

By using this code, you acknowledge that you have read, understood, and agreed to the terms and conditions outlined in this disclaimer.    
</b>
###[/HELP]###
###[LBS]###
<?

 /*
Changelog:
==========
v0.00  09.09.2023 NG initial Kickoff
 0.97  20.09.2023 NG first beta release
 0.971 21.09.2023 NG huev2.php v1.1, Hilfe-Bugfix
 0.98  25.09.2023 NG support for contact
 0.981 30.09.2023 NG check if correct curl version is installed.
 0.982 02.10.2023 NG setAlert + setSignaling
 0.983 02.10.2023 NG cache for admin
 0.984 04.10.2023 NG bugfix in setRGB, bugfix in setAction, stringToIntegerArray, preparation for multi-gateway support
 0.985 05.10.2023 NG multi GW support in LBS
 0.986 14.10.2023 NG rgb->xy bugfix, bugfix in startup
 0.987 14.10.2023 NG bugfix in setCT
 0.988 15.10.2023 NG bugfix in rgbToXyBrightness
 0.989 17.11.2023 NG Admin-Page can be disabled. setBrightness=0 turns off the lamp, >0 also turn on the lamp. 
                     Bugfix in setAlert, setSignaling
 */
 
function LB_LBSID_debug($debugLevel,$thisTxtDbgLevel, $str) {
    if ($thisTxtDbgLevel <= $debugLevel) {
        $dbgTxts = array("Kritisch","Info","Debug","LowLevel");
        writeToCustomLog("LBS_ST_LBSID", $dbgTxts[$thisTxtDbgLevel], $str);
    }
}

function LB_LBSID($id) {
    $ADMIN_FILE = MAIN_PATH . "/www/hue_admin.php";
    $HUE_LIB_DIR = MAIN_PATH . "/main/include/php/SmartThings";
    $HUE_LIB = $HUE_LIB_DIR . "/huev2.php";
    
    if ($E = logic_getInputs($id)) {
        
        logic_setInputsQueued($id, $E); // Send all inputs to EXEC daemon

        if ($E[8]['refresh']) {
            $adminPage= "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";
            
            switch ($E[8]['value']) {
            case 0:
                if (file_exists($ADMIN_FILE)) {
                    unlink($ADMIN_FILE);
                }
                LB_LBSID_debug ($E[9]['value'], 1, "Admin ($ADMIN_FILE) deaktiviert!");
                break;
            case 1:
                $aPage = gzuncompress(base64_decode($adminPage));
                $aPage = str_replace("__INSERT_BRIDGE_ID__", $E[2]['value'],$aPage);
                $aPage = str_replace("__INSERT_API_KEY__", $E[3]['value'],$aPage);
                $aPage = str_replace("__INSERT_EDOMI_PATH__", MAIN_PATH,$aPage);
            
                if (!file_put_contents($ADMIN_FILE, $aPage)) {
                    LB_LBSID_debug ($E[9]['value'], 0, "Datei $ADMIN_FILE konnte nicht erstellt werden!");
                } else {
                    LB_LBSID_debug ($E[9]['value'], 1, "Admin ($ADMIN_FILE) aktiviert!");
                }
            }
        }
            
        if (($E[1]['refresh'] == 1) && ($E[1]['value'] == 1)) {
            if(!is_dir($HUE_LIB_DIR)) {
                LB_LBSID_debug ($E[9]['value'], 1, "Verzeichnis anlegen ".$HUE_LIB_DIR);
                if (!mkdir($HUE_LIB_DIR)) {
                    LB_LBSID_debug ($E[9]['value'], 0, "Verzeichnis konnte nicht angelegt werden!: ".$HUE_LIB_DIR);
                }
            }

            $hueLib = "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";

            if (!file_put_contents($HUE_LIB, gzuncompress(base64_decode($hueLib)))) {
                LB_LBSID_debug ($E[9]['value'], 0, "Datei $HUE_LIB konnte nicht erstellt werden!");
            }

            
            logic_callExec(LBSID, $id, false); // Start Daemon only once
        }
       
    }
}
 ?>

###[/LBS]###

###[EXEC]###
<?php
 //require('wrapper.php');
 require(dirname(__FILE__)."/../../../../main/include/php/incl_lbsexec.php");
 $HUE_LIB = MAIN_PATH . "/main/include/php/SmartThings/huev2.php";

 include_once $HUE_LIB;

 sql_connect();

 set_time_limit(0);
 set_error_handler("customErrorHandler");
 
 $E = logic_getInputs($id);

 $lbsID = LBSID;
 
 if (isset ($hasWrapper))
     $E=W_logic_getInputs($id);
 

 $bIP = explode (";",$E[2]['value']); // $bridgeIP
 $bApiKey = explode (";",$E[3]['value']); // $apiKey
 $autoOn   = $E[4]['value'];
 
 $dbgTxts = array("Kritisch","Info","Debug","LowLevel");
 $debugLevel = $E[9]['value'];

 if(!file_exists($HUE_LIB)) {
     exec_debug(0,"huev2 lib nicht vorhanden. Bitte manuell installieren.");
     sql_disconnect();
     die();
 }

 $myHue = [];
 
 for ($i=0;$i<count ($bIP);$i++) {
     exec_debug (2, "init hue-gateway # $i");
     $mH = new myHueV2API ($bIP[$i], $bApiKey[$i], $autoOn, false, '');

     if ($mH && $mH->start()) {
         array_push ($myHue, $mH);
         exec_debug (2, "hue-gateway #$i ok");
     }
 }

 if (count ($myHue) > 0) {
     $hueDB = new myHUEEdomiConnector ($id, $lbsID, $myHue);

     $running = 0;

     do {
         $running = 0;
         
         foreach ($myHue as $mH) {
             $running += $mH->runLoop();
         }
         
         if ($E = logic_getInputsQueued($id)) {
             if (isset($E[1]['refresh']) && ($E[1]['refresh']) && ($E[1]['value'] == 0)) {
                 exec_debug (1, "E1=0 gesetzt. LBS beenden");
                 //$running = 0;
                 break;
             }
             $hueDB->checkEdomiRequests($E);
         }

         usleep (1000);
     } while ( ($running > 0) || (getSysInfo(1) >=1) );
 } else {
     exec_debug (0, "Kein HUE-Gateway aktiv. LBS beenden");
 }

 exec_debug (1, "LBS beendet");
 sql_disconnect();
 

 function customErrorHandler($errCode, $errText, $errFile, $errRow) {
     global $dbgTxts;
     if (0 == error_reporting()) {
         // Error reporting is currently turned off or suppressed with @
         return;
     }
     writeToCustomLog("LBS_ST_LBSID", $dbgTxts[0], 'Datei: ' . $errFile . ' | Fehlercode: ' . $errCode . ' | Zeile: ' . $errRow . ' | ' . $errText);
 }

 function exec_debug ($thisTxtDbgLevel, $str) {
     global $debugLevel;
     global $dbgTxts;
     global $hasWrapper;
     
     if ($thisTxtDbgLevel <= $debugLevel) {
         if (isset ($hasWrapper)) {
             W_writeToCustomLog("LBS_LBSID", $dbgTxts[$thisTxtDbgLevel], $str);
         } else {
             writeToCustomLog("LBS_ST_LBSID", $dbgTxts[$thisTxtDbgLevel], $str);
         }
     }
 }

class myHUEEdomiConnector {
    private $dbResult;
    private $myHue; // array of objects

    private $myDB;

    private $fromHUE;   // map data received from HUE to Edomi [huev2ID.function] => KO
    private $toHUE; // map data received from Edomi and forward to hue [KO-Id] => [huev2ID.function]
    
    private $lbsID;
    private $id;

    private $allDevices;

    public function __construct($id, $lbsID, $hueV2) {
        $this->id = $id;
        $this->lbsID = $lbsID;
        
        $this->myHue = $hueV2;
        
        $this->dbResult = '';
        $this->allDevices = [];
        $this->loadDB();
    }

    // function will be called by HUE. External function !
    public function flushEdomi($device) {
        $functionList = $device->getNotifyList();
        $v2ID = $device->getID();
        $devName = $device->getName();
        
        foreach ($functionList as $func) {

            $koID = $this->fromHUE[$v2ID][$func];

            $val = call_user_func([$device, $func]);
            
            exec_debug (3, sprintf ("EdomiConnector::flushEdomi: $v2ID::$func ($devName) = Set KO (%s)= %s", $koID, $val));
            
            $this->myWriteGA ($koID, $val);
        }
    }

    private function findDeviceByID ($dId) {
        foreach ($this->allDevices as $aId => $dev) {
            if (isset ($dev[$dId])) {
                return ($aId);
            }
        }
        return (false);
    }
    
    public function loadDB() {
        $sql = sql_call ("select a.id, a.huev2id, hueType, koID from edomiProject.hueMainEntry a left join edomiProject.hueSub b on a.id=b.mainID");

        foreach ($this->myHue as $aID=>$aDevices) {
            $this->allDevices[$aID] = $aDevices->getAllAvailableHUEDevices();
        }

        // Register all KO's to the hue devices

        $dynamicParameterCount = 10; // first id which is available as parameter for this LBS

        $this->edomiDynamicParameters = [];
        
        while ($result = sql_result($sql)) {
            $v2ID = $result['huev2id'];
            $hueType = $result['hueType'];

            $aId = $this->findDeviceByID ($v2ID);

            if ($aId === false) {
                exec_debug (1, sprintf ("EdomiConnector::Device $v2ID nicht gefunden. Typ=$hueType"));
            } else {
                if (strstr ($hueType ,"get")) {
                    $this->fromHUE[$v2ID][$hueType] = $result['koID'];
                    
                    exec_debug (3, sprintf ("EdomiConnector::get Device $v2ID gefunden. Typ=$hueType"));
                    $this->allDevices[$aId][$v2ID]->registerExternalFlush ($hueType);
                } else if (strstr ($hueType, "set")) {
                    $this->toHUE[$dynamicParameterCount++] = array ('koID' => $result['koID'],
                                                                    'function' => [$this->allDevices[$aId][$v2ID], $hueType]);
                    
                    exec_debug (3, sprintf ("EdomiConnector::set Device $v2ID gefunden. Typ=$hueType"));
                }
            }
        }

        // Flush all notifications
        foreach ($this->allDevices as $aID=>$aDevices) {
            foreach ($aDevices as $device) {
                $device->registerExternalNotifyFunction ([$this, 'flushEdomi']);
                $device->flushNotification();
            }
        }

        $sql="delete from edomiLive.RAMlogicLink where eingang >=10 and elementid=".$this->id;
        exec_debug (3, sprintf ($sql));
        sql_call ($sql);

        if (isset ($this->toHUE)) {
            foreach ($this->toHUE as $key => $val) {
                $koID = $val['koID'];
            
                $mysql = "insert into edomiLive.RAMlogicLink (elementid,functionid, eingang, linktyp, linkid, ausgang, init, refresh, value) values ($this->id,$this->lbsID, $key, 0, $koID, NULL, 2, 0, '0')";
                sql_call ($mysql);
                exec_debug (3, sprintf ("param=%d, koID = %d - $mysql",$key, $koID));
            }
        } else {
            exec_debug (1, sprintf ("Keine Ausgangs-KO's definiert"));
        }
    }

    public function checkEdomiRequests ($E) {
        foreach ($E as $key => $val) {
            if ($key >= 10) {
                if ($E[$key]['refresh'] == 1) {

                    if (isset ($this->toHUE[$key]['koID'])) {
                        $set = $this->getKoFromEdomi ($this->toHUE[$key]['koID']);

                        call_user_func($this->toHUE[$key]['function'], $set);
                        exec_debug (3, sprintf ("refresh auf Eingang $key [%s], ko=$set",$this->toHUE[$key]['koID']));
                    } else {
                        exec_debug (1, sprintf ("ko existiert nicht. Kein refresh auf Eingang $key [%s], ko=$set",$this->toHUE[$key]['koID']));
                    }
                }
            }
        }
    }
    
    public function getKoFromEdomi($id) {
        $val = getGADataFromID ($id,0,"value");
        if (!isset($val['value'])) {
            exec_debug (1, sprintf ("KO id= $id liefert keinen Wert!",$val));
            return (0);
        }
        return ($val['value']);
    }

    public function myWriteGA ($ga, $val) {
        if ($ga != 0) {
            writeGA ($ga, $val);
        }
    }
}
 
?> 
###[/EXEC]###
