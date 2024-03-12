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
<b>__INSERT_DISCLAIMER__</b>
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
            $adminPage= "__hue_admin.txt__";
            
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

            $hueLib = "__huev2.txt__";

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
