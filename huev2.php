<?php
/*
  (w)(c) 2023 Nima Ghassemi Nejad (ngn928@web.de)

  1.0  - initial kickoff
  1.01 - 21.09.2023 bugfix für setrgb, setxy+getxy
                    hue_light class debug output bugfix for xy,rgb,hsv
  1.02 - 25.09.2023 added contact.
  1.03 - 02.10.2023 setAlert property, setSignaling property
  1.04 - 02.10.2023 cacheFile
       - 04.10.2023 bugfix in setRGB, bugfix in setAction, stringToIntegerArray
       - 05.10.2023 array() replaced by []
  1.05 - 14.10.2023 rgb->xy und xy->rgb copied functions from
                    https://github.com/neoteknic/Phue/blob/master/library/Phue/Helper/ColorConversion.php 
  1.06 - 14.10.2023 bugfix in setCT
  1.07 - 15.10.2023 bugfix in rgbToXyBrightness
  1.08 - 17.11.2023 setBrightness =0 turns off the lamp. >0 also turns on the light by default
                    Bugfix in setAlert, setSignalling State=on is ignored
*/
trait NGDebug {
    function outputDebugArray ($customTxt, $myArr = null) {
        $content = '';
        if ($myArr != null) {
            ob_start();
            var_dump($myArr);
            $content = ob_get_contents();
         
            ob_end_clean();
        }
        return $customTxt . ":" . $content;
    }

    public function external_dbg ($logLevel,$txt) {
        if (function_exists('exec_debug')) {
            exec_debug ($logLevel,$txt);
        }
    }
}

interface hueV2Interface {
    public function __construct ($parent, $resourceName, $device);
    public function __localConstruct ($device);
    public function update ($device);

    public function getResourceName();
    public function getName();
    public function getID();
    public function getExportedFunctions();
}


trait hueGeneralFunctions {
    use NGDebug;
    
    private $resourceName; // Resourcename for V2 URL e.g. "light"
    private $deviceID;     // string, hue V2 Light ID
    private $deviceName;   // string human readable name of light

    private $parent;

    private $externalFunctionCall; // Call this function if externalNotification is defined
    private $notifyList;

    //private $mappedFunctions = [];

    public function getResourceName() {
        return $this->resourceName;
    }
    
    public function getName() {
        return $this->deviceName;
    }

    public function getID() {
        return $this->deviceID;
    }

    /* these functions are not available for all device resources. Therefore check exportedFunctions before calling */
    public function getEnabled () {
        return (($this->isEnabled) ? 1 : 0);
    }

    public function getLastChanged() {
        return ($this->lastChanged);        
    }
    
    public function __construct ($parent, $resourceName, $device) {
        $this->parent = $parent;
        $this->resourceName = $resourceName;
                
        $this->deviceID = $device['id'];

        $this->externalNotificationMap = '';
        $this->externalFunctionCall = null;

        $this->notifyList = [];
        
        $name = $this->parent->getDeviceNameFromDevices($this->deviceID);

        if ($name === false)
            $name = $this->deviceID."-Unknown";
        
        $this->deviceName = $name;

        // auto generate mappedFunctions for new class
        if ( (!isset ($this->mappedFunctions)) && (isset ($this->exportedFunctions['read'])))  {
            foreach ($this->exportedFunctions['read'] as $var => $func) {
                $this->mappedFunctions[$func] = array ('variable' => "$var", 'externalFlush' => []);
                $this->$var = ''; // important. generate required variables dynamically
            }
            //printf ("mappedFunctions for $resourceName not available. Generated:\n");
        }
        $this->__localConstruct ($device);
    }

    // this function register external Function Call. $this->externalFunctionCall is used to notify function outside of this class for updates
    public function registerExternalNotifyFunction ($function) {
        $this->externalFunctionCall = $function;
    }
    
    public function registerExternalFlush ($function) {
        if (isset ($this->mappedFunctions[$function]['externalFlush'])) {

            // review if this is really required.
            
            array_push ($this->mappedFunctions[$function]['externalFlush'], 1);
            //printf ("register $function for $id\n");

            // trigger notify
            array_push ($this->notifyList, $function);
        }
    }

    // this function will be called if hue-gateway sends in updates. flushNotification will forward changes to 'externalFunctionCall'
    public function flushNotification() {
        if (!empty ($this->notifyList)) {

            if (is_callable($this->externalFunctionCall)) {
                call_user_func($this->externalFunctionCall, $this);
            } else {
                $this->external_dbg (1, sprintf ("flushNotification::No external callback defined!!"));
            }

            $this->notifyList = [];
        }
    }

    // Function currently not used in code. returns notifyList
    public function getNotifyList() {
        return ($this->notifyList);
    }
    
    public function getExportedFunctions () {
        if (isset ($this->exportedFunctions)) 
            return $this->exportedFunctions;
        return ([]);
    }

    public function updateEntity ($function, $value) {
        // store $value into defined variable and check if notification is required.
        
        if (isset ($this->mappedFunctions[$function]['variable'])) {
            $var = $this->mappedFunctions[$function]['variable'];

            $this->$var = $value;

            if (!empty ($this->mappedFunctions[$function]['externalFlush'])) {
                // printf ("add to notify\n");
                array_push ($this->notifyList, $function);
            }
        } else {
            $this->external_dbg (1, "$function does not exist");
            //print_r ($this->mappedFunctions);
        }
    }
    
    private function putResourceRequest ($data) {
        $ret = false;
        list ($code,$result) = $this->parent->sendRequest("PUT", $this->resourceName."/".$this->deviceID, json_encode($data));
        if ($code == 200) {
            $ret = true;
            //list ($code,$result) = $this->parent->sendRequest("GET", $this->resourceName."/".$this->deviceID);
            //print_r ($result);
            //$this->update ($result);
        } else {
            $this->external_dbg (1, sprintf ("Error $code %s", $result));
            $this->external_dbg (1, sprintf ("Request sent to $this->resourceName / $this->deviceID :", $data));
        }
        return ($ret);
    }

}


class HUE_temperature implements hueV2Interface {
    use hueGeneralFunctions;

    private $exportedFunctions = array ("hidden" => array
                                        (
                                            "name"        => 'getName',
                                            "huev2id"     => 'getID'
                                        ),
                                        "read" => array (
                                            "isEnabled"   => 'getEnabled',      // bool
                                            "temperature" => 'getTemperature',  // float
                                            "lastChanged" => 'getLastChanged'   // date
                                        ));
    
    public function __localConstruct ($device) {
        $this->update ($device);
    }

    public function getTemperature () {
        return ($this->temperature);
    }
    
    public function update ($device) {
        if (isset ($device['enabled']))
            $this->updateEntity ('getEnabled', $device['enabled']);
        
        if (isset ($device['temperature']['temperature_report']['temperature']))
            $this->updateEntity ('getTemperature', $device['temperature']['temperature_report']['temperature']);

        if (isset ($device['temperature']['temperature_report']['changed']))
            $this->updateEntity ('getLastChanged', $device['temperature']['temperature_report']['changed']);

        $this->external_dbg (2, sprintf ("TempSensor ($this->deviceName)::active: $this->isEnabled, temp: $this->temperature, changed: $this->lastChanged\n"));
        $this->flushNotification();
    }
}

class HUE_device_power implements hueV2Interface {
    use hueGeneralFunctions;

    private $exportedFunctions = array ("hidden" => array
                                        (
                                            "name"         => 'getName',
                                            "huev2id"      => 'getID'
                                        ),
                                        "read" => array (
                                            "batteryState" => 'getBatteryState',  // string normal, low, critical
                                            "batteryLevel" => 'getBatteryLevel' // int 0..100
                                        ));
    
    public function __localConstruct ($device) {
        $this->update ($device);
    }

    public function getBatteryState() {
        return ($this->batteryState);
    }

    public function getBatteryLevel() {
        return ($this->batteryLevel);
        
    }
    
    public function update ($device) {
        if (isset ($device['power_state']['battery_level']))
            $this->updateEntity ('getBatteryLevel', $device['power_state']['battery_level']);

        if (isset ($device['power_state']['battery_state']))
            $this->updateEntity ('getBatteryState', $device['power_state']['battery_state']);

        $this->external_dbg (2, sprintf ("devicePower ($this->deviceName):: batteryLevel: $this->batteryLevel, state: $this->batteryState\n"));
        $this->flushNotification();
    }
}

class HUE_motion implements hueV2Interface {
    use hueGeneralFunctions;

    private $exportedFunctions = array ("hidden" => array
                                        (
                                            "name"              => 'getName',
                                            "huev2id"           => 'getID'
                                        ),
                                        "read" => array (
                                            "isEnabled"         => 'getEnabled',  // bool
                                            "motionState"       => 'getMotionState', // bool if motion is detected
                                            "sensitivityStatus" => 'getSensitivityStatus', // string "set" / "changeinc"
                                            "sensitivityLevel"  => 'getSensitivityLevel', // int
                                            "lastChanged"       => 'getLastChanged' // date
                                        ));

    public function __localConstruct ($device) {
        $this->update ($device);
    }

    public function getMotionState() {
        return (($this->motionState) ? 1 : 0);
    }

    public function getSensitivityStatus() {
        return ($this->sensitivityStatus);
    }

    public function getSensitivityLevel() {
        return ($this->sensitivityLevel);
    }

    public function update ($device) {
        if (isset ($device['enabled']))
            $this->updateEntity ('getEnabled', $device['enabled']);
        
        if (isset ($device['motion']['motion_report']['motion']))
            $this->updateEntity ('getMotionState', $device['motion']['motion_report']['motion']);

        if (isset ($device['motion']['motion_report']['changed']))
            $this->updateEntity ('getLastChanged', $device['motion']['motion_report']['changed']);

        if (isset ($device['sensitivity']['status']))
            $this->updateEntity ('getSensitivityStatus', $device['sensitivity']['status']);

        if (isset ($device['sensitivity']['sensitivity']))
            $this->updateEntity ('getSensitivityLevel', $device['sensitivity']['sensitivity']);

        $this->external_dbg (2, sprintf ("MotionSensor ($this->deviceName)::active: $this->isEnabled, motion: ".$this->getMotionState().", changed: $this->lastChanged, sensitivityStatus: $this->sensitivityStatus sensitivityLevel:$this->sensitivityLevel\n"));
        $this->flushNotification();
    }
}

class HUE_light_level implements hueV2Interface {
    use hueGeneralFunctions;
    
    private $exportedFunctions = array ("hidden" => array
                                        (
                                            "name"        => 'getName',
                                            "huev2id"     => 'getID'
                                        ),
                                        "read" => array (
                                            "isEnabled"   => 'getEnabled',
                                            "lightLevel"  => 'getLightLevel',
                                            "lastChanged" => 'getLastChanged'
                                        ));
    
    public function __localConstruct ($device) {
        $this->update ($device);
    }

    public function getLightLevel() {
        return ($this->lightLevel);
    }
    
    public function update ($device) {
        if (isset ($device['enabled']))
            $this->updateEntity ('getEnabled', $device['enabled']);
        
        if (isset ($device['light']['light_level_report']['light_level']))
            $this->updateEntity ('getLightLevel', $device['light']['light_level_report']['light_level']);

        if (isset ($device['light']['light_level_report']['changed']))
            $this->updateEntity ('getLastChanged', $device['light']['light_level_report']['changed']);
        
        $this->external_dbg (2, sprintf ("LightlevelSensor $this->deviceName::active: $this->isEnabled, lightLevel: $this->lightLevel, changed: $this->lastChanged\n"));
        $this->flushNotification();
    }
}


trait hueLightResources {
    public $hasCT;       // bool
    public $hasColor;    // bool
    public $hasDimming;  // bool

    public function getState () {
        return (($this->lightState) ? 1 : 0);
    }

    public function setState ($state) {
        $state = ($state) ? true : false;
        $data = array ("on" => array ("on"=>$state));
        return ($this->putResourceRequest ($data));
    }

    public function setCT (int $colorTemp) {
        $data = array ("color_temperature" => array ("mirek"=>$colorTemp));
        if ((!$this->lightState) && ($this->parent->autoTurnOn))
            $data["on"]["on"] = true;

        return ($this->putResourceRequest ($data));
    }

    public function setBrightness (int $value) {
        // input value = 0..255, hue brightness = 0..100
        $data = array ("dimming" => array ("brightness"=> ceil($value * 100 / 255)));

        $data["on"]["on"] = ($value == 0) ? false : true;
        
        /*
          if ((!$this->lightState) && ($this->parent->autoTurnOn))
          $data["on"]["on"] = true;
        */
        //$this->external_dbg (3, outputDebugArray ("DPT3: %d", $dpt3));
        
        return ($this->putResourceRequest ($data));
    }

    public function setHSV ($hsv) {
        list ($h,$s,$v) = ColorConversion::stringToArray ($hsv);
        
        list ($x,$y,$bri) = ColorConversion::hsvToXYBrightness ($h,$s,$v);

        $data = array ("dimming" => array ("brightness"=> ceil($bri)),
                       "color"   => array ("xy" => array ("x" => $x, "y" => $y))
        );
        if ((!$this->lightState) && ($this->parent->autoTurnOn))
            $data["on"]["on"] = true;

        return ($this->putResourceRequest ($data));
    }

        
    public function setRGB ($rgb) {
        list ($r,$g,$b) = ColorConversion::stringToArray ($rgb);
        list ($x,$y,$bri) = ColorConversion::rgbToXyBrightness($r, $g, $b);
        $this->xy = array ($x,$y);
        $data = array ("color" => array ("xy" => array
                                         (
                                             "x" => $x,
                                             "y" => $y
                                         )),
                       "dimming" => array ("brightness"=> ceil($bri * 100 / 255))
        );
        if ((!$this->lightState) && ($this->parent->autoTurnOn))
            $data["on"]["on"] = true;

        //print_r ($data);
        return ($this->putResourceRequest ($data));
    }
        
    public function getCT () {
        return ($this->colorTemperature);
    }
    public function getBrightness () {
        return ($this->brightness);
    }
    public function getXY () {
        if (!is_array ($this->xy))
            $this->xy = [0,0];
        
        return (sprintf ("%f;%f",$this->xy[0],$this->xy[1]));
        //return ($this->xy);
    }

    public function getRGB () {
        if (!is_array ($this->rgb))
            $this->rgb = [0,0,0];
        return (sprintf ("%02X%02X%02X",$this->rgb[0],$this->rgb[1],$this->rgb[2]));
        //return ($this->rgb);
    }

    public function getHSV () {
        if (!is_array ($this->hsv))
            $this->hsv = [0,0,0];

        return (sprintf ("%02X%02X%02X",$this->hsv[0],$this->hsv[1],$this->hsv[2]));
        //return ($this->hsv);
    }
    
    public function initXY ($x,$y) {
        $xy = $this->xy = array ($x,$y);

        $rgb = $this->rgb = ColorConversion::xyToRgb ($x, $y, $this->brightness); // xyBriToRgb

        $hsv = ColorConversion::convertRGBtoHSV ($rgb[0],$rgb[1],$rgb[2]); // convertRGBtoHSV

        $this->updateEntity ('getXY', $xy);
        $this->updateEntity ('getRGB', $rgb);
        
        if ($this->hsv !== false) {
            $this->updateEntity ('getHSV', $hsv);
            //printf ("hsv ok\n");
        } else {
            $this->external_dbg (1, sprintf ("hsv fail\n"));
        }
    }

    public function setXY ($xy) {
        list ($x,$y) = ColorConversion::stringToFloatArray ($xy);
        $this->initXY ($x,$y);
    }
    
    public function setDPT3 ($dpt3) {
        $direction = $dpt3 >> 3;
        $dpt3 = $dpt3 & 0x07;

        $this->external_dbg (3, sprintf ("DPT3: $dpt3, direction=$direction"));
        /*
          Dimmstufen:
          $dpt3 = 
          7 = 64 Stufen
          6 = 32 Stufen
          5 = 16 Stufen
          4 =  8 Stufen
          3 =  4 Stufen
          2 =  2 Stufen
          1 =  1 Stufe
        */
        if ($dpt3 != 0) {
            $steps = 1 << ($dpt3-1);
            $modValue = round(255/ $steps);

            $this->external_dbg (3, sprintf ("steps: $steps, modVal= $modValue"));
            
            $value = $oldValue = $this->brightness;

            switch ($direction) {
            case 1:
                // heller
                $value = (($value + $modValue) < 255) ? $value+$modValue : 255;
                break;
            case 0:
                // dunkler
                $value = (($value - $modValue) > 0) ? $value-$modValue : 0;
                break;
            }

            if ($oldValue != $value) { // SBC
                $this->external_dbg (2, sprintf("set new brightness = %d (old=%d)",$value, $oldValue));
                $this->setBrightness ($value);
                $this->brightness = $value;
            }
        } else {
            $this->updateEntity ('getBrightness', $this->brightness);
            $this->flushNotification();
            // Taster losgelassen
        }
    }

    private $actionProperties = array ("breathe");
    
    public function setAlert (int $action) {
        // check if action is defined. If not, set default to = 0 which is breathe
        if (isset ($this->actionProperties[$action])) {
            $action = $this->actionProperties[$action];
        } else {
            $action = $this->actionProperties[0];
            $this->external_dbg (1, sprintf ("setAlert, action=$action undefined. Using default=breathe\n"));
        }

        $this->external_dbg (3, sprintf ("setAlert, action=$action\n"));
        
        $data = array ("alert" => array ("action"=>$action));

        //if ((!$this->lightState) && ($this->parent->autoTurnOn))
        //$data["on"]["on"] = true;

        return ($this->putResourceRequest ($data));
    }

    private $signalProperties = array ("no_signal", "on_off", "on_off_color", "alternating");
    
    public function setSignaling ($argument) {
        list ($action, $duration) = ColorConversion::stringToIntegerArray ($argument);

        if (isset ($this->signalProperties[$action])) {
            $action = $this->signalProperties[$action];
        } else {
            $action = $this->signalProperties[0];
            $this->external_dbg (1, sprintf ("setSignaling: action = undefined. Using default=no_signal\n"));
        }

        $this->external_dbg (3, sprintf ("setSignaling, action=$action, $duration\n"));
        $data = array (
            "signaling" => array (
                "signal" => $action,
                "duration" => $duration
            )
        );
        
        //if ((!$this->lightState) && ($this->parent->autoTurnOn))
        //$data["on"]["on"] = true;

        return ($this->putResourceRequest ($data));
    }
    
}

class HUE_grouped_light implements hueV2Interface {
    use hueGeneralFunctions;
    use hueLightResources;
    
    private $rid;
    
    private $exportedFunctions = array ("hidden" => array
                                        (
                                            "name"             => 'getName',
                                            "huev2id"          => 'getID'
                                        ),
                                        "read" => array (
                                            "lightState"       => 'getState', // bool
                                            "brightness"       => 'getBrightness', // int
                                        ),
                                        "write" => array (
                                            "lightState"       => 'setGState',
                                            "rgb"              => 'setRGB',
                                            "xy"               => 'setXY',
                                            "hsv"              => 'setHSV',
                                            "DPT3Dimming"      => 'setDPT3',
                                            "colorTemperature" => 'setCT',
                                            "brightness"       => 'setGBrightness',
                                            "alert"            => 'setAlert',
                                            "signal"           => 'setSignaling',
                                        )
    );

    public function __localConstruct ($device) {
        $this->xy  = [];
        $this->rgb = [];

        $this->update ($device);
    }

    public function setGState ($state) {
        if ($this->setState ($state))
            $this->updateEntity ("getState", $state);
    }

    public function setGBrightness ($val) {
        if ($this->setBrightness ($val))
            $this->updateEntity ("getBrightness", $val);
    }
            
    public function update ($device) {
        $this->rid = $device['owner']['rid'];

        $this->external_dbg (3, "got rid = $this->rid ".$device['owner']['rtype']."\n");

        if (isset ($this->parent->allDevices[$this->rid])) {
            $this->deviceName = $this->parent->allDevices[$this->rid]->getName();
            $this->external_dbg (3, sprintf ("notify owner %s [type=%s]\n", $this->parent->allDevices[$this->rid]->getName(), $device['owner']['rtype']) );
        }

        if (isset ($device['on']['on'])) {
            $this->updateEntity ('getState', ($device['on']['on']) ? 1 : 0);
        }

        if (isset ($device['dimming']['brightness'])) {
            $br = ceil ($device['dimming']['brightness'] * 255/100);
            $this->updateEntity ('getBrightness', $br);
        }

        $this->external_dbg (2, sprintf ("GroupedLight $this->deviceName $this->deviceID, brightness=%f, state=%d\n", $this->getBrightness(), $this->getState()));

        $this->flushNotification();
        //$this->external_dbg (2, $this->outputDebugArray ("GroupedLight",$device));
        //        print_r ($device);
    }
}

class HUE_room implements hueV2Interface {
    use hueGeneralFunctions;
    private $services;   // Array
    private $children;   // array
    
    public function __localConstruct ($device) {
    }

    public function __construct ($parent, $resourceName, $device) {
        $this->parent = $parent;
        $this->resourceName = $resourceName;
        $this->deviceID = $device['id'];
        $this->deviceName = $device['metadata']['name'];
        $this->services   = $device['services'];
        $this->children   = $device['children'];

        // Children refer to the devices in a room.
        
        //print_r ($device);
        $this->update ($device);
    }    
    
    public function update ($device) {
        /*
        if (isset ($device['on']['on']))
            $this->isOn = $device['on']['on'];
        
        if (isset ($device['dimming']['brightness']))
            $this->lightLevel = $device['dimming']['brightness'];
        */
        $this->external_dbg (2, sprintf ("Room: %s, $this->deviceID\n", $this->getName()));
        //$this->external_dbg (2, $this->outputDebugArray ("Room",$device));
        //printf ("Room $this->deviceName - $this->deviceID\n");
        //print_r ($device);
        //print_r ($this->children);
    }
}

class HUE_zone implements hueV2Interface {
    use hueGeneralFunctions;
    private $services;   // Array
    private $children;   // array
    
    public function __localConstruct ($device) {
    }

    public function __construct ($parent, $resourceName, $device) {
        $this->parent = $parent;
        $this->resourceName = $resourceName;
        //public function __construct ($allDevices, $device) {
        $this->deviceID = $device['id'];
        $this->deviceName = $device['metadata']['name'];
        $this->services   = $device['services'];
        $this->children   = $device['children'];

        //print_r ($device);
        $this->update ($device);
    }    
    
    public function update ($device) {
        $this->external_dbg (2, sprintf ("Zone $this->deviceName - $this->deviceID\n"));
        //$this->external_dbg (2, $this->outputDebugArray ("Zone",$device));
    }
}

class HUE_light implements hueV2Interface {
    use hueGeneralFunctions;
    use hueLightResources;
    
    private $exportedFunctions = array ("hidden" => array
                                        (
                                            "name"             => 'getName',
                                            "huev2id"          => 'getID'
                                        ),                                       
                                        
                                        "read" => array (
                                            "lightState"       => 'getState', // bool
                                            "rgb"              => 'getRGB',  // array (r,g,b)
                                            "xy"               => 'getXY',   // array (x,y)
                                            "hsv"              => 'getHSV',  // array (h,s,v)
                                            "colorTemperature" => 'getCT', // int
                                            "brightness"       => 'getBrightness', // int
                                        ),
                                        "write" => array (
                                            "lightState"       => 'setState',
                                            "rgb"              => 'setRGB',
                                            "xy"               => 'setXY',
                                            "hsv"              => 'setHSV',
                                            "DPT3Dimming"      => 'setDPT3',
                                            "colorTemperature" => 'setCT',
                                            "brightness"       => 'setBrightness',
                                            "alert"            => 'setAlert',
                                            "signal"           => 'setSignaling',
                                        )
    );
    
    public function __localConstruct ($device) {
        $this->hasCT = isset ($device['color_temperature']) ? 1 : 0;
        $this->hasColor = isset ($device['color']['xy']) ? 1 :0 ;
        $this->hasDimming = isset ($device['dimming']['brightness']) ? 1 : 0;

        $this->xy  = [];
        $this->rgb = [];

        if (!$this->hasCT) {
            unset($this->exportedFunctions['read']['ct']);
            unset($this->exportedFunctions['write']['ct']);
            unset($this->mappedFunctions['getCT']);
        }

        if (!$this->hasColor) {
            unset($this->exportedFunctions['read']['rgb']);
            unset($this->exportedFunctions['read']['xy']);
            unset($this->exportedFunctions['read']['hsv']);
            
            unset($this->exportedFunctions['write']['rgb']);
            unset($this->exportedFunctions['write']['xy']);
            unset($this->exportedFunctions['write']['hsv']);

            unset($this->mappedFunctions['getRGB']);
            unset($this->mappedFunctions['getXY']);
            unset($this->mappedFunctions['getHSV']);
        }

        if (!$this->hasDimming) {
            unset($this->exportedFunctions['read']['brightness']);
            unset($this->exportedFunctions['write']['brightness']);

            unset($this->mappedFunctions['getBrightness']);
        }
        $this->update ($device);
    }       

    public function update ($device) {
        //$this->external_dbg (3, $this->outputDebugArray ("light",$device));
                                         
        if (isset ($device['on']['on'])) {
            $this->updateEntity ('getState', ($device['on']['on']) ? 1 : 0);
        }

        if (isset ($device['dimming']['brightness'])) {
            $br = ceil ($device['dimming']['brightness'] * 255/100);
            $this->updateEntity ('getBrightness', $br);
            if ($this->hasColor) {
                if (isset ($this->xy[0]) && isset($this->xy[1]))
                    $this->initXY ($this->xy[0], $this->xy[1]);
            }
        }

        if (isset ($device['color_temperature']['mirek'])) {
            $this->updateEntity ('getCT', $device['color_temperature']['mirek']);
        }

        if (isset ($device['color']['xy'])) {
            $this->initXY ($device['color']['xy']['x'], $device['color']['xy']['y']);
        }

        $rgbString = '';
        if ($this->hasColor) {
            $rgbString = "xy=[".$this->getXY()."],hsv=[".$this->getHSV()."],rgb=[".$this->getRGB()."]";
        }

        $this->external_dbg (2, sprintf ("Light <%s>: name:'%s', on/off: %s, ct: $this->colorTemperature, dimming: $this->brightness, hasColor: $this->hasColor, hasCT: $this->hasCT, hasDimm: $this->hasDimming, $rgbString\n",
                $this->deviceID,
                $this->deviceName,
                $this->lightState
        ));
        
        $this->flushNotification();
    }
}

class HUEDevice implements hueV2Interface {
    use hueGeneralFunctions;
    
    private $services;   // Array
    
    public function __localConstruct($device) {
    }
   
    public function __construct ($parent, $resourceName, $device) {
        //public function __construct ($allDevices, $device) {
        $this->parent = $parent;
        $this->resourceName = $resourceName;
        $this->deviceID = $device['id'];
        $this->deviceName = $device['metadata']['name'];
        $this->services   = $device['services'];

        //foreach ($this->services as 
        $this->update ($device);
        /*
    [services] => Array
        (
            [0] => Array
                (
                    [rid] => 36db82ae-4cd6-4385-8a6d-2b781f188541
                    [rtype] => zigbee_connectivity
                )

            [1] => Array
                (
                    [rid] => bb71fd7b-6dc7-4fb4-b75c-0c430bcc783f
                    [rtype] => light
                )
        )
        */
        //$this->update ($device);
    }
   
    public function update ($device) {
        $this->external_dbg (2, sprintf ("device[$this->deviceID]: $this->deviceName\n"));
    }

    public function findDeviceNamebyID ($id) {
        foreach ($this->services as $service) {
            if ($service['rid'] == $id)
                return ($this->deviceName);
        }
        return (false);
    }

    public function getAllServices () {
        return $this->services;
    }
}

class HUE_zigbee_connectivity implements hueV2Interface {
    use hueGeneralFunctions;

    private $exportedFunctions = array ("hidden" => array
                                        (
                                            "name"             => 'getName',
                                            "huev2id"          => 'getID'
                                        ),                                       
                                        
                                        "read" => array (
                                            "connectionState"  => 'getStatus', // int
                                            "macAddress"       => 'getMACAddress',  // string
                                        )
    );

    private $statusTxt = array ('connected' => 1,
                                'disconnected' => 2,
                                'connectivity_issue' => 3,
                                'unidirectional_incoming' => 4,
    );
    
    public function __localConstruct ($device) {
        $this->update ($device);
    }

    public function getStatus() {
        return ($this->connectionState);
    }

    public function getMACAddress() {
        return ($this->macAddress);
    }
    public function update ($device) {
        if (isset ($device['status'])) {
            //one of connected, disconnected, connectivity_issue, unidirectional_incoming

            $status = $this->statusTxt[$device['status']] ?? 0;
            $this->updateEntity ('getStatus', $status);
        }

        if (isset ($device['mac_address'])) {
            $this->updateEntity ('getMACAddress', $device['mac_address']);
        }
        
        $this->external_dbg (2, sprintf ("zigbee-connectivity $this->deviceName - $this->deviceID, status: %d [%s], mac: %s\n", $this->getStatus(), $device['status'], $this->getMACAddress()));
        //$this->external_dbg (2, $this->outputDebugArray ("Zone",$device));
        $this->flushNotification();
    }
}

class HUE_scene implements hueV2Interface {
    use hueGeneralFunctions;

    private $palette;
    
    private $exportedFunctions = array ("hidden" => array
                                        (
                                            "name"             => 'getName',
                                            "huev2id"          => 'getID'
                                        ),                                       
                                        "write" => array (
                                            "recall"           => 'setScene',  // bool
                                        ),
                                        "read" => array (
                                            "status"           => 'getStatus', // int [1..3]
                                            "speed"            => 'getSpeed',  // float 0..1
                                            "autoDynamic"      => 'getAutoDynamic' // bool Indicates whether to automatically start the scene dynamically on active recall
                                        )
    );

    private $statusTxt = array ('inactive' => 1,
                                'static' => 2,
                                'dynamic_palette' => 3,
    );
    
    public function __localConstruct ($device) {
        $this->deviceName = $device['metadata']['name'];
        $this->update ($device);
    }

    /*
    public function __construct ($parent, $resourceName, $device) {
        $this->parent = $parent;
        $this->resourceName = $resourceName;
        $this->deviceID = $device['id'];

    }    
    */

    public function setScene($scene) {
        $setAction = "active";
        $data = array ("recall" => array ("action"=> "active"));
        return ($this->putResourceRequest ($data));
    }
    
    public function getStatus() {
        return ($this->status);
    }

    public function getSpeed() {
        return ($this->speed);
    }

    public function getAutoDynamic() {
        return ($this->autoDynamic);
    }
    
    public function update ($device) {
        if (isset ($device['actions'])) {
            $this->actions    = $device['actions'];
        }

        if (isset ($device['group'])) {
            $this->group      = $device['group'];
        }

        if (isset ($device['palette'])) {
            $this->palette    = $device['palette'];
        }
        
        if (isset ($device['status']['active'])) {
            // one of inactive, static, dynamic_palette
            $status = $this->statusTxt[$device['status']['active']] ?? 0;
            $this->updateEntity ('getStatus', $status);
        }

        if (isset ($device['speed'])) {
            $this->updateEntity ('getSpeed', $device['speed']);
        }

        if (isset ($device['auto_dynamic'])) {
            $this->updateEntity ('getSpeed', ($device['auto_dynamic']) ? 1 : 0);
        }
        $this->external_dbg (2, sprintf ("scene $this->deviceName - $this->deviceID, status: %d [%s], speed=%f, autoDyn=%d\n", $this->getStatus(), $this->getSpeed(), $this->getAutoDynamic(), $device['status']['active']));
        //$this->external_dbg (2, $this->outputDebugArray ("Scene",$device));
        $this->flushNotification();
    }
}

class HUE_contact implements hueV2Interface {
    use hueGeneralFunctions;

    private $exportedFunctions = array ("hidden" => array
                                        (
                                            "name"        => 'getName',
                                            "huev2id"     => 'getID'
                                        ),
                                        "read" => array (
                                            "isEnabled"   => 'getEnabled',      // bool
                                            "state"       => 'getState',        // string 'contact', 'nocontact'
                                            "lastChanged" => 'getLastChanged'   // date
                                        ));

    private $statusTxt = array ('nocontact' => 1,
                                'contact' => 2,
    );
    
    public function __localConstruct ($device) {
        $this->update ($device);
    }

    public function getState () {
        return ($this->state);
    }
    
    public function update ($device) {
        if (isset ($device['enabled']))
            $this->updateEntity ('getEnabled', $device['enabled']);
        
        if (isset ($device['contact_report']['state']))
            $status = $this->statusTxt[$device['contact_report']['active']] ?? 0;
            $this->updateEntity ('getState', $status);

        if (isset ($device['contact_report']['changed']))
            $this->updateEntity ('getLastChanged', $device['contact_report']['changed']);

        $this->external_dbg (2, sprintf ("ContactSensor ($this->deviceName)::active: $this->isEnabled, state: $this->status, changed: $this->lastChanged\n"));
        $this->flushNotification();
    }
}

class myHueV2API {
    use NGDebug;
    
    private $bridgeIP;
    private $token;

    private $url;
    private $mh;
    
    private $processCallback;
    public $gotHelo;

    public $autoTurnOn;              // Turn on lamps automatically if they're off and only values (like ct, color, dimming values) are set
    
    private $myHUEDevices = [];
    public $allDevices = [];

    private $curlHTTPVersion;
    private $cacheEnabled;
    private $cachePath;
    
    public $supportedServices = array ("temperature",
                                                                 
                                       "motion",
                                       "light_level",
                                       "light",
                                       "device_power",

                                       "room",
                                       "zone",
                                       // grouped_lights must be loaded after 'room' and 'zone'
                                       "grouped_light",
                                       "scene",
                                       "contact",
                                       "zigbee_connectivity",
    );
    
    public function __construct ($ip, $token, $autoTurnOn, $enableCache, $cachePath /*, callable $callback*/) {
        $this->bridgeIP = $ip;
        $this->token = $token;
        $this->autoTurnOn = $autoTurnOn;
        $this->cacheEnabled = $enableCache;
        $this->cachePath = $cachePath."huev2_".crc32($ip).".";
        
        $this->mh = curl_multi_init();
        //$this->processCallback = $callback;
        $this->processCallback = [$this, 'processEvents'];
                
        $this->gotHelo = 0;

        if (defined('CURL_HTTP_VERSION_2_0')) {
            $this->curlHTTPVersion = CURL_HTTP_VERSION_2_0;
        } else {
            $this->external_dbg (1, "PHP/CURL Version probably too old. Update PHP to at least PHP 7.4. Trying workaround but this might fail.");
            $this->curlHTTPVersion = 3;
            //return (false);
        }
        return (true);
    }

    function __destruct () {
        curl_multi_close($this->mh);
    }

    public function getDeviceNameFromDevices ($id) {
        foreach ($this->myHUEDevices as $device) {
            $name = $device->findDeviceNamebyID ($id);
            if ($name !== false) {
                return ($name);
                break;
            }
        }
    }
    
    public function myCallbackFromStream ($ch, $content) {
        if (strpos($content, ": hi") === 0) {
            //print "got helo\n";
            $this->gotHelo = 1;
        } else {
            //echo $content;
            
            $jsonString = substr($content, strpos($content, '['));
            
            // Convert the JSON string to an array
            $array = json_decode($jsonString, true);

            //print_r ($array);
            
            //$c = json_encode ($content,true);

            if (is_callable($this->processCallback)) {
                call_user_func($this->processCallback, $array);
            } else {
                print "no callback\n";
            }
        }
        return strlen($content);
    }

    public function sendCachedRequest ($method, $url, $data = null) {
        $cFile = $this->cachePath.crc32($url);
        
        $responseCode = 0;
        $response = '';
        
        if ($this->cacheEnabled) {
            if (file_exists($cFile)) {
                $response = file_get_contents($cFile);
                $responseCode = 200;
            }
        }

        if ($responseCode == 0) {
            list ($responseCode, $response) = $this->sendRequest ($method, $url,$data);
            if ( ($responseCode == 200) && $this->cacheEnabled)
                file_put_contents($cFile, $response);
        }
        
        return (array($responseCode, $response));
    }
    
    public function sendRequest($method, $url, $data = null) {
        $ch = curl_init("https://".$this->bridgeIP."/clip/v2/resource/".$url);

        $requestHeaders = array(
            "accept: */*",
            "Content-Type: application/json",
            "hue-application-key: ".$this->token,
        );

        curl_setopt_array($ch, array(
            CURLOPT_HTTP_VERSION   => $this->curlHTTPVersion,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $requestHeaders,
            //CURLOPT_VERBOSE        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS     => $data
        ));
    
        $response = curl_exec($ch);

        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        return (array($responseCode, $response));
    }

    public function setupEventStream () {
        $ch = curl_init("https://".$this->bridgeIP."/eventstream/clip/v2");

        curl_setopt_array($ch, array(
            CURLOPT_HTTP_VERSION   => $this->curlHTTPVersion,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => array("accept: text/event-stream",
                                            "hue-application-key: ".$this->token
            ),
            //CURLOPT_VERBOSE        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_WRITEFUNCTION  => [$this, 'myCallbackFromStream'],
        ));
    
        curl_multi_add_handle($this->mh, $ch);
    }

    public function start() {
        // store all huedevices into myHueDevices
        
        list ($code, $result) = $this->sendCachedRequest("GET", "device");

        if ($code == 200) {
            $deviceList = json_decode($result, true);

            // device type device must be handeled first.
        
            if (isset ($deviceList['data'])) {
                foreach ($deviceList['data'] as $device) {
                    //print_r ($device);
                    $this->myHUEDevices[$device['id']] = new HUEDevice (/*NULL, */$this, "device", $device);
                }
            }

            // dynamically create new hue objects depending on their type
            foreach ($this->supportedServices as $name /* =>  $service */) {
                list ($code, $result) = $this->sendCachedRequest("GET", $name);
                $deviceList = json_decode($result, true);

                //print_r ($deviceList);
        
                $class = "HUE_".$name;
            
                if (isset ($deviceList['data'])) {
                    foreach ($deviceList['data'] as $device) {
                        $this->allDevices[$device['id']] = new $class (/*$this->myHUEDevices,*/ $this, $name, $device);
                        /*
                          $mycb = $service['cb'];
                          $this->$mycb[$device['id']] = new $class ($this->myHUEDevices, $this, $name, $device);

                          //$this->myHUEDevices[$device['id'][$Id]] = $this->$mycb[$device['id']];

                          $exported = $this->$mycb[$device['id']]->getExportedFunctions();
                        */
                        //print_r ($exported);
                    }
                }
            }

            $this->setupEventStream();
            return (true);
        } else {
            $this->external_dbg (0, sprintf ("cannot connect to hue gateway IP=%s: Token=%s ",$this->bridgeIP,$this->token));
            return (false);
        }
    }

    function getAllAvailableHUEDevices () {
        return ($this->allDevices);
    }
    
    function getAllServices () {
        // go through all hueDevices and return each device with it's supported Services
        
        foreach ($this->myHUEDevices as $device) {
            $this->external_dbg (2, sprintf ("Device [%s,%s]: %s: ",$device->getID(), $device->getResourceName(), $device->getName() ));
            $services = $device->getAllServices();

            /*
            foreach ($services as $service) {
                if (array_key_exists($service['rtype'], $this->supportedServices)) {
                    printf ("\t%s\n",$service["rtype"]);
                }
            }
            */
        }
    }

    /*
      this function is called for processing any events coming from hue-gateway. If device is known, the stream content is forwarded to object for processing
    */
    function processEvents ($array) {
        if (isset ($array[0]['data'])) {
            foreach ($array[0]['data'] as $stream) {

                if (isset ($this->allDevices[$stream['id']])) {
                    $this->allDevices[$stream['id']]->update ($stream);
                } else {
                    $type = $stream['type'];
                    $this->external_dbg (1, sprintf ("unknown device $type\n"));
                    $this->external_dbg (1, sprintf ($this->outputDebugArray ("stream", $stream)));
                    //print_r ($stream);
                }
            }
        }
    }

    //private $tested = 0;
    
    public function runLoop () {
        /*
        if ($this->gotHelo) {
            if (!$this->tested) {
                $this->external_dbg (1, sprintf ("test light\n"));
                //$this->myLights['599d5fdd-856a-4afc-8629-da86ce945adb']->setState (true);
                //$this->myLights['599d5fdd-856a-4afc-8629-da86ce945adb']->setCT (400);

                //$this->myLights['599d5fdd-856a-4afc-8629-da86ce945adb']->setBrightness (10);
                //$this->myLights['599d5fdd-856a-4afc-8629-da86ce945adb']->setRGB ("FF0000");
                
                
                $this->tested = 1;
            }
        }
        */      
        $running = 0;
        curl_multi_exec($this->mh, $running);
        return ($running);
    }

}    

class ColorConversion {

    /*
      input x,y
      brightness = 0..255 (!)
    */
    static function xyToRgb_old($x, $y, $brightness) {
        /*
        // Adjust xy values if they fall outside the RGB gamut
        if ($x > 0.9505 || $y > 1.0 || $brightness > 1.0) {
            // Apply gamut mapping or other adjustments here
        }
        */
        
        // Convert xy coordinates to XYZ color space
        $z = 1.0 - $x - $y;
        $Y = $brightness / 255.0;
        $X = ($Y / $y) * $x;
        $Z = ($Y / $y) * $z;
    
        // Convert XYZ to linear RGB
        $R = $X * 3.2406 - $Y * 1.5372 - $Z * 0.4986;
        $G = -$X * 0.9689 + $Y * 1.8758 + $Z * 0.0415;
        $B = $X * 0.0557 - $Y * 0.2040 + $Z * 1.0570;
    
        // Apply gamma correction to convert linear RGB to sRGB
        $R = $R <= 0.0031308 ? $R * 12.92 : 1.055 * pow($R, 1 / 2.4) - 0.055;
        $G = $G <= 0.0031308 ? $G * 12.92 : 1.055 * pow($G, 1 / 2.4) - 0.055;
        $B = $B <= 0.0031308 ? $B * 12.92 : 1.055 * pow($B, 1 / 2.4) - 0.055;
    
        // Clamp RGB values within the valid range (0-255)
        $R = max(0, min(255, round($R * 255)));
        $G = max(0, min(255, round($G * 255)));
        $B = max(0, min(255, round($B * 255)));
    
        // Return RGB values as an array
        return [$R,$G,$B];
    }

    static function rgbToHsv($R, $G, $B) {
        // Convert RGB values to the range of 0 to 1
        $r = $R / 255;
        $g = $G / 255;
        $b = $B / 255;
    
        // Find the maximum and minimum values of RGB
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
    
        // Calculate the value (V)
        $v = $max;
    
        // Calculate the saturation (S)
        $delta = $max - $min;
        if ($max != 0) {
            $s = $delta / $max;
        } else {
            $s = 0;
        }
    
        // Calculate the hue (H)
        if ($delta == 0) {
            $h = 0;
        } else {
            if ($max == $r) {
                $h = 60 * fmod((($g - $b) / $delta), 6);
            } elseif ($max == $g) {
                $h = 60 * ((($b - $r) / $delta) + 2);
            } else {
                $h = 60 * ((($r - $g) / $delta) + 4);
            }
        }
    
        // Convert the hue, saturation, and value to the range of 0 to 255
        $h = round($h * 255 / 360);
        $s = round($s * 255);
        $v = round($v * 255);
    
        // Return HSV values as an array
        return [$h, $s, $v];
    }
    
    static function rgbToXyBrightness($red, $green, $blue) {
        // Normalize the values to 1
        $normalizedToOne['red'] = $red / 255;
        $normalizedToOne['green'] = $green / 255;
        $normalizedToOne['blue'] = $blue / 255;

        // Make colors more vivid
        foreach ($normalizedToOne as $key => $normalized) {
            if ($normalized > 0.04045) {
                $color[$key] = pow(($normalized + 0.055) / (1.0 + 0.055), 2.4);
            } else {
                $color[$key] = $normalized / 12.92;
            }
        }

        // Convert to XYZ using the Wide RGB D65 formula
        $xyz['x'] = $color['red'] * 0.664511 + $color['green'] * 0.154324 + $color['blue'] * 0.162028;
        $xyz['y'] = $color['red'] * 0.283881 + $color['green'] * 0.668433 + $color['blue'] * 0.047685;
        $xyz['z'] = $color['green'] * 0.072310 + $color['blue'] * 0.986039;
        //$xyz['z'] = $color['red'] * 0.000000 + $color['green'] * 0.072310 + $color['blue'] * 0.986039;

        // Calculate the x/y values
        if (array_sum($xyz) == 0) {
            $x = 0;
            $y = 0;
        } else {
            $x = $xyz['x'] / array_sum($xyz);
            $y = $xyz['y'] / array_sum($xyz);
        }

        return array(
            $x,
            $y,
            round($xyz['y'] * 255)
        );
    }

    /**
     * Converts XY (and brightness) values to RGB
     *
     * @param float $x X value
     * @param float $y Y value
     * @param int $bri Brightness value
     *
     * @return array red, green, blue key/value
     */

    static function xyToRgb($x, $y, $bri) {
        //static function convertXYToRGB(float $x, float $y, ?int $bri = 255): array
        // Calculate XYZ
        $z = 1.0 - $x - $y;
        $xyz['y'] = $bri / 255;
        //Temp fix for division by zero
        if($y==0){
        	$y=0.001;
        }
        $xyz['x'] = ($xyz['y'] / $y) * $x;
        $xyz['z'] = ($xyz['y'] / $y) * $z;
        
        // Convert to RGB using Wide RGB D65 conversion
        $color['red'] = $xyz['x'] * 1.656492 - $xyz['y'] * 0.354851 - $xyz['z'] * 0.255038;
        $color['green'] = -$xyz['x'] * 0.707196 + $xyz['y'] * 1.655397 + $xyz['z'] * 0.036152;
        $color['blue'] = $xyz['x'] * 0.051713 - $xyz['y'] * 0.121364 + $xyz['z'] * 1.011530;
        
        $maxValue = 0;
        foreach ($color as $key => $normalized) {
            // Apply reverse gamma correction
            if ($normalized <= 0.0031308) {
                $color[$key] = 12.92 * $normalized;
            } else {
                $color[$key] = (1.0 + 0.055) * ($normalized ** (1.0 / 2.4)) - 0.055;
            }
            $color[$key] = max(0, $color[$key]);
            if ($maxValue < $color[$key]) {
                $maxValue = $color[$key];
            }
        }
        foreach ($color as $key => $normalized) {
            if ($maxValue > 1) {
                $color[$key] /= $maxValue;
            }
            // Scale back from a maximum of 1 to a maximum of 255
            $color[$key] = round($color[$key] * 255);
        }
        
        return array ($color['red'], $color['green'],$color['blue']);
    }

    static function convertRGBtoHSV($r, $g, $b) {
        $r /= 255;
        $g /= 255;
        $b /= 255;
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $v = $max;
        $d = $max - $min;
        $s = $max == 0 ? 0 : $d / $max;
        if ($max == $min) {
            $h = 0;
        }
        else {
            if ($max == $r) {
                $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
            }
            if ($max == $g) {
                $h = ($b - $r) / $d + 2;
            }
            if ($max == $b) {
                $h = ($r - $g) / $d + 4;
            }
            $h /= 6;
        }
        if ($h >= 0 && $h <= 1 && $s >= 0 && $s <= 1 && $v >= 0 && $v <= 1) {
            return array(
                ($h * 255) ,
                ($s * 255) ,
                ($v * 255)
            );
        }
        return false;
    }

    static function hsvToXYBrightness($h, $s, $v) {
        // input r,g,b = 0..255. output brightness = 0..100
        // Convert HSV to RGB
        $r = $g = $b = 0;
        $h /= 255;
        $s /= 255;
        $v /= 255;
    
        if ($s == 0) {
            $r = $g = $b = $v;
        } else {
            $h *= 6;
            $i = floor($h);
            $f = $h - $i;
            $p = $v * (1 - $s);
            $q = $v * (1 - $s * $f);
            $t = $v * (1 - $s * (1 - $f));
        
            switch ($i) {
            case 0:
                $r = $v; $g = $t; $b = $p;
                break;
            case 1:
                $r = $q; $g = $v; $b = $p;
                break;
            case 2:
                $r = $p; $g = $v; $b = $t;
                break;
            case 3:
                $r = $p; $g = $q; $b = $v;
                break;
            case 4:
                $r = $t; $g = $p; $b = $v;
                break;
            default:
                $r = $v; $g = $p; $b = $q;
                break;
            }
        }
    
        // Convert RGB to XY coordinates
        $x = ($r * 0.649926 + $g * 0.103455 + $b * 0.197109) / ($r + $g + $b);
        $y = ($r * 0.234327 + $g * 0.743075 + $b * 0.022598) / ($r + $g + $b);
    
        // Convert brightness to percentage
        $brightness = $v * 100;
    
        return (array ($x, $y, $brightness));
    }    

    static function stringToArray($str)
    {
        $str = str_replace("#", "", $str);

        if (strlen($str) == 3) {
            $a = hexdec(substr($str, 0, 1) . substr($str, 0, 1));
            $b = hexdec(substr($str, 1, 1) . substr($str, 1, 1));
            $c = hexdec(substr($str, 2, 1) . substr($str, 2, 1));
        } else {
            $a = hexdec(substr($str, 0, 2));
            $b = hexdec(substr($str, 2, 2));
            $c = hexdec(substr($str, 4, 2));
        }
        return (array ($a,$b,$c));
    }

    static function stringToFloatArray($str) {
        $v = explode(";", $str);
    
        if (count($v) != 2) {
            return (array (0,0));
        }
    
        return array(floatval($v[0]), floatval($v[1]));
    }

    static function stringToIntegerArray ($string) {
        $v = explode(";", $string);
        if (count($v) != 2)
            return (array (0,0));
        
        return (array((int)$v[0], (int)$v[1]));
    }
}
?>
