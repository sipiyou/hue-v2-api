<?php
define('MAIN_PATH','__INSERT_EDOMI_PATH__'); // /usr/local/edomi
//require(dirname(__FILE__)."/../main/include/php/incl_lbsexec.php");
$HUEV2_LIB = MAIN_PATH . "/main/include/php/SmartThings/huev2.php";

$bridgeIP = '__INSERT_BRIDGE_ID__';
$apiKey = '__INSERT_API_KEY__';
$cacheDir = MAIN_PATH . "/www/data/tmp/";

include_once $HUEV2_LIB;

/*
  (w)(c) 2023 Nima Ghassemi Nejad (ngn928@web.de)
  v 1.00  09.09.2023 - initial release
    1.01  18.09.2023 - minor improvements
    1.02  02.10.2023 - cache enabled
    1.03  03.10.2023 - multi gateway support
    1.04  05.10.2023 - replaced array() by []
*/

/*
  get /resource/room:


Array
(
    [id] => d4917cd8-ad87-49fa-92a5-b2120ed5dbab
    [id_v1] => /groups/2
    [children] => Array
        (
            [0] => Array
                (
                    [rid] => aeba68c7-94a0-48fe-a841-8817646f4499
                    [rtype] => device
                )

        )

    [services] => Array
        (
            [0] => Array
                (
                    [rid] => 7a20a391-4da2-4011-b524-039a48d7566c
                    [rtype] => grouped_light
                )

        )

    [metadata] => Array
        (
            [name] => Höhle
            [archetype] => recreation
        )

    [type] => room
)

  Array
(
    [id] => 57b73b19-1245-4167-9ad8-45aed1cd1c34
    [id_v1] => /groups/3
    [children] => Array
        (
            [0] => Array
                (
                    [rid] => 015a33e3-fd1e-47b5-a32f-6d0317bf2f16
                    [rtype] => device
                )

            [1] => Array
                (
                    [rid] => 081a120b-4814-4628-aa10-e29e1c8d23d9
                    [rtype] => device
                )

            [2] => Array
                (
                    [rid] => 831aa535-165f-44af-be49-ef3b458743cf
                    [rtype] => device
                )

            [3] => Array
                (
                    [rid] => ec239acf-8e56-4fad-a28e-66eda0165659
                    [rtype] => device
                )

            [4] => Array
                (
                    [rid] => f26ae206-928d-4888-8627-5c288ac50ef9
                    [rtype] => device
                )

        )

    [services] => Array
        (
            [0] => Array
                (
                    [rid] => 37d70086-1ca9-421e-8eef-83e189038e93
                    [rtype] => grouped_light
                )

        )

    [metadata] => Array
        (
            [name] => Esszimmer
            [archetype] => living_room
        )

    [type] => room
)

 */



class hueAdmin {
    use NGDebug;
    
    private $adminVersion = "1.02";
    private $mysqli;
    private $allDevices;
    private $REQUEST;
    
    public $adminFileName;
    private $clearCacheHTML;
    
    private $dbMain;
    private $dbSub;

    private $cbodyHTML;
    private $versionInfoHTML;

    private $footerHTML = <<<HTML
</div>
</div>
</div>

</body>
</html>
HTML;
    
    private $headerHTML = <<<HTML
<!DOCTYPE html>
<head>
<meta charset="UTF-8">
<title>Edomi HUE KO-Setup</title>
<link rel="icon" href="shared/img/favicon-admin.png" type="image/png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs5/jq-3.6.0/jszip-2.5.0/dt-1.11.3/b-2.1.1/b-colvis-2.1.1/b-html5-2.1.1/b-print-2.1.1/fh-3.2.0/r-2.2.9/sr-1.0.1/datatables.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://cdn.datatables.net/v/bs5/jq-3.6.0/jszip-2.5.0/dt-1.11.3/b-2.1.1/b-colvis-2.1.1/b-html5-2.1.1/b-print-2.1.1/fh-3.2.0/r-2.2.9/sr-1.0.1/datatables.min.js"></script>
<style>

	.input:-internal-autofill-selected{
		color:red !important;
	}
	.dataTables_length select {
	   background-color: #212529;
	   color:797970;
	   border-color:#797970;
	}
	.dataTables_filter input {
	   background-color: #212529;
	   color:#797970;
	   border-color:#797970;
	}
	
	div.dataTables_wrapper div.dataTables_length select{
		color:#797970;
	}
	
	.pagination a {
		background-color: #212529;
		color:#797970;
		border-color:#797970;

	}
		
	.pagination a:hover:not(.active) {
		background-color: black;
		color:white;
		border-color:#797970;
	} 
	
	.page-link {
		background-color: #212529!important;
		color:black;
		border-color:#797970;
	}
	
	.odd{
		background-color:black;
	}
	.even{
		background-color:rgba(0,0,0,.3);
	}
	
	.page-item.active .page-link {
        color: #fff !important;
        background-color:#797970 !important;
        border-color: #797970!important; 
    }

    .page-link {
        color: #000 !important;
        background-color: #212529 !important;
        border: 1px solid  !important; 
    }
	
	.page-link:not(.active)  {
        color: #797970 !important;
        background-color: #212529 !important;
		border-color: #797970!important;
    }
	
    .page-link:hover {
        color: #fff !important;
        background-color: #212529 !important;
        border-color: #797970!important; 
    }
		
	.page-item.disabled .page-link{
		//visibility: hidden;
		color: black !important;
        background-color: #212529 !important;
		border-color: #797970!important;		
	}
</style>

<script>
	$.fn.dataTable.ext.order['dom-checkbox'] = function  ( settings, col )
	{
		return this.api().column( col, {order:'index'} ).nodes().map( function ( td, i ) {
			return $('input', td).prop('checked') ? '1' : '0';
		} );
	}

	$(document).ready(function() {
		$('#device').DataTable({
			"pageLength": 15,
			'info':true,
			'statesave':true,
			'autowidth': true,
			"columns":[
				{ "orderDataType": "dom-checkbox" },
				null,
				null,
				null,
				null,
				null,			
			],
			"columnDefs": [
				{
					"targets": [2],
					"searchable": false,
					'orderable': true
				},{
					"targets": [0,1,3],
					"searchable": true,
					'orderable': true
				},{
					"targets": [4,5],
					"searchable": false,
					"orderable": false
				}  
			],
			
			"pagingType": "full_numbers",
			"lengthMenu": [
				[5,15,20,25,50, -1],
				[5,15,20,25,50, "Alle "]
			],
			"language": {
				"decimal": ",",
				"thousands": ".",
				"search": "_INPUT_",
				"searchPlaceholder": "Suche",
				"sEmptyTable": "Keine Daten in der Tabelle vorhanden",
				"sInfo": "_START_ bis _END_ von _TOTAL_ Einträgen",
				"sInfoEmpty": "0 bis 0 von 0 Einträgen",
				"sInfoFiltered": "(gefiltert von _MAX_ Einträgen)",
				"sInfoPostFix": "",
				"sInfoThousands": ".",
				"sLengthMenu": "_MENU_  Datensätze anzeigen",
				"sLoadingRecords": "Wird geladen...",
				"sProcessing": "Bitte warten...",
				"sSearch": "Suchen",
				"sZeroRecords": "Keine Einträge vorhanden.",
				"oPaginate": {
					"sFirst": "Erste",
					"sPrevious": "Zurück",
					"sNext": "Nächste",
					"sLast": "Letzte"
				}
			}
		});
	});

</script>
<script>

         function showResult(myField, str) {
             //window.alert("myfield:"+myField+" str:"+str+"::end");
             if (str.length==0) {
                 document.getElementById(myField).innerHTML="";
                 document.getElementById(myField).style.border="0px";
                 return;
             }
             var xmlhttp=new XMLHttpRequest();
             xmlhttp.onreadystatechange=function() {
                 if (this.readyState==4 && this.status==200) {
                     var outputTxt = this.responseText;
                     /*
                     var s = this.responseText.split(/\n/);
                     var outputTxt = '';
                     for (i=0;i<s.length;i++) {
                         var dataFields = s[i].split(/\t/);
                         outputTxt += dataFields[0]+" - "+dataFields[1]+" - "+((dataFields[2] === 1) ? "knx": "lokal")+"<br/>";
                     }
                     */
                     //document.getElementById(myField).innerHTML=s[0]+" - "+s[1]+" - "+(s[2] === 2) ? 'lokal' : 'knx'+"<br/>"; //this.responseText;
                     document.getElementById(myField).innerHTML=outputTxt; //this.responseText;
                     document.getElementById(myField).style.border="1px solid #A5ACB2";
                 }
             }
             xmlhttp.open("GET","?term="+str,true);
             xmlhttp.send();
         }
</script>

</head>
<body  style="background: rgba(0,0,0,0.7);font-family:EDOMIfont,Lucida Grande,Arial;">
	<div class="container">
		<div class="jumbotron">
			<div class="card mt-5" id="main"style="top-margin: 30px;box-shadow: rgb(128 128 128) 0px 0px 10px 3px;background: rgba(0,0,0,0.7); color: #797970;border-radius: 10px;">
                <div class="card-header">
HTML;
    
    public function __construct ($allDevices, $mysqli, $request) {
        $this->mysqli = $mysqli;
        $this->allDevices = $allDevices;
        $this->REQUEST = $request;

        $this->checkIfDBExists();
    
        $this->adminFileName = "hue_admin.php";
        $this->dbMain = [];
        $this->dbSub = [];
        $this->configuredEcus = [];

        //$this->showAllAvailableDevices();
            
        //$this->mysqli = mysqli_connect("localhost", "root", "", ""); // , "demo"
        $this->cbodyHTML = <<<HTML
        <div class="card-body">
         <table cclass="table  table-striped" style="color:#797970;width:100%" id="device">
		 <thead>
			<tr>
				<td>Name</td>
				<td>Typ</td>
				<td>ID V2</td>
				<td>Editieren</td>
				<td>Löschen</td>
			</tr>
		 </thead>
HTML;
        $this->versionInfoHTML = <<<HTML
	</tr>
</table>
<div align="right" class="mt-2" style="font-size:small">HUE-Admin v $this->adminVersion</div> 
</div>
HTML;
        $this->clearCacheHTML = <<<HTML
<div align='right' class='fs-5'><a href='?clearCache=1' class='bi bi-trash' style='color:red'>Cache erneuern</a></div>
HTML;
    }

    private function sadd_rowX() {
        $numargs = func_num_args();
        $arg_list = func_get_args();
        $ret = "<tr>";
        for ($i = 0; $i < $numargs; $i++) {
            $ret .= "<td>".$arg_list[$i]."</td>";
        }
        $ret .= "</tr>";
        return ($ret);
    }

    private function checkIfDBExists() {
        $this->mysqli->query ("create table if not exists edomiProject.hueMainEntry (id bigint unsigned not null auto_increment,deviceName text not null,huev2id varchar(60) not null, primary key (id,huev2id)) ENGINE=MyISAM");
        $this->mysqli->query ("create table if not exists edomiProject.hueSub (id bigint unsigned not null auto_increment,mainID bigint unsigned not null,hueType text,koID bigint unsigned,primary key (id)) ENGINE=MyISAM");
    }
    
    private function getAllConfiguredECUs() {
        $sql = "select huev2id from edomiProject.hueMainEntry"; //  m left join edomiProject.hueSub s on m.id=s.mainID
        $html = $this->cbodyHTML;
        if($stmt = $this->mysqli->prepare($sql)){
            if($stmt->execute()) {
                $result = $stmt->get_result();
                while($row = $result->fetch_array(MYSQLI_ASSOC)){
                    $this->configuredEcus[$row['huev2id']] = 1;
                }
            }
        }
    }
    private function getAllAvailableDevices() {
        $subHTML = "<form action='".$this->adminFileName."' method='post'>";
                
        $selectBox = "<select class='form-select bg-dark' name='huev2id' size=1 style='color:#797970;border-color: rgba(0,0,0,0.7)'>";

        // Sort output by DeviceName.Resource
        $sortedArray = [];
        foreach ($this->allDevices as $aID=>$aDevices) {
            foreach ($aDevices as $dId => $device) {
                if (!isset ($this->configuredEcus[$dId])) {
                    $expFunctions = $device->getExportedFunctions();
                    // filter empty deviceNames. These are devices which are not implemented.
                    if ( !empty ($device->getName()) && (isset ($expFunctions['read']) || isset ($expFunctions['write'])))
                        $sortedArray[$aID.$device->getResourceName().$device->getName()] = array ($aID,$device);
                }
            }
        }
        ksort($sortedArray);

        // Iterate over the sorted array and access each object
        foreach ($sortedArray as $d) {
            list ($aId,$device) = $d; // .$aId."."
            $selectBox .= "<option value='".$device->getID()."'>$aId.[".$device->getResourceName()."].".$device->getName()."</option>";
        }
        
        $selectBox .= "</select>";

        $subHTML .= "<div class='input-group mt-2'>".$selectBox."<input type='submit' class='btn btn-outline-success btn-sm ms-2' name='addNewDevice' value='weiter' style='width:100px'></div>";
        $subHTML .= "</form>";

        return $subHTML;
    }

    private function generateInputBox ($obj, $dbMain, $dbSub) {

        function getVal ($table, $dbSub, $retString) {
            $val =  (isset ($dbSub[$table])) ? $dbSub[$table] : '';

            if ($retString) {
                $val = ($val != '') ? "value='$val'" : "";
            }
            return ($val);
        }

        $exportedFunctions = $obj->getExportedFunctions();
            
        $html = sprintf ("<div>[%s].%s (%s) verkn&uuml;pfen</div><br>", $obj->getResourceName(), $obj->getName(), $dbMain['huev2id']);
        $html .= "<form action='".$this->adminFileName."' method='post'>";

        $keyStyleBegin = '<div class=\'input-group input-group-sm mb-1\'><label style=\'width:180px\'>';
        $keyStyleEnd   = '</label>';

        if (isset ($dbMain['id'])) 
            $html .= "<input type='hidden' name='hueMainEntry_id' value='".$dbMain['id']."'>";
        
        foreach ($exportedFunctions['hidden'] as $key=>$cmd) {
            $value = $obj->$cmd();
            $html .= "<input type='hidden' name='hueMainEntry_$key' value='$value'>";
        }

        foreach ($exportedFunctions['read'] as $key=>$dbfield) {
            $value = getVal ($dbfield,$dbSub, 1);
            
            $cname = "class_rd_$dbfield";
            $html .= $this->sadd_rowX ($keyStyleBegin."Status::".$key.$keyStyleEnd,"<input type='text' class='form-control ms-2 bg-dark' style='color:#797970;border-color:black;' placeholder='search' autocomplete='off' name='hueSub_$dbfield' $value onkeyup='showResult(\"$cname\", this.value)' oninput='showResult(\"$cname\", this.value)' >","<select class='form-select bg-dark ms-1' style='color:#797970;border-color:black' name='hueSub_$dbfield' id='$cname'></select></div>" );
        }

        $html.= $this->sadd_rowX("&nbsp;");
        
        if (isset ($exportedFunctions['write'])) {
            foreach ($exportedFunctions['write'] as $key=>$dbfield) {
                $value = getVal ($dbfield,$dbSub,1);
                $cname = "class_wr_$dbfield";
                $html .= $this->sadd_rowX ($keyStyleBegin."Set::".$key.$keyStyleEnd,"<input type='text' class='form-control ms-2 bg-dark' style='color:#797970;border-color:black;' placeholder='search' autocomplete='off' name='hueSub_$dbfield' $value onkeyup='showResult(\"$cname\", this.value)' oninput='showResult(\"$cname\", this.value)' >","<select class='form-select bg-dark ms-1' style='color:#797970;border-color:black' name='hueSub_$dbfield' id='$cname'></select></div>" );
            }
        }
        
        $html .= $this->sadd_rowX ("<div class='input-group me-2 mt-3' style='text-align:right; display: block;'><input type='submit' class='btn btn-outline-success pull-right' name='saveDeviceSettings' value='abspeichern'></div>");
    
        $html .= "</form>";
        print $html;
    }

    private function saveEntry(&$showCardEntries) {
        $main = [];
        $sub  = [];

        foreach ($this->REQUEST as $key=>$val) {
            if (strncmp ("hue", $key,3 ) == 0) {
                $r = explode("_",$key);
                switch ($r[0]) {
                case "hueMainEntry":
                    $main[$r[1]] = $val;
                    break;
                case "hueSub":
                    $sub[$r[1]] = $val;
                    break;
                }
            }
        }

        if (!isset($main['id']) || ($main['id'] == '')) {
            // insert new data into db
            $mainSQL = "insert into edomiProject.hueMainEntry (deviceName,huev2id) values(?,?)";
            if($entry = $this->mysqli->prepare($mainSQL)){
                $entry->bind_param('ss',$main['name'],$main['huev2id']);
                if ($entry->execute()) {
                    $main['id'] = $mainID = $entry->insert_id;
                    foreach ($sub as $key=>$val) {
                        if (empty($val)) continue;
                        $subSQL = "insert into edomiProject.hueSub (mainID,hueType, koID) values (?,?,?)";

                        if($subEntry = $this->mysqli->prepare($subSQL)){
                            $subEntry->bind_param('iss',$mainID,$key, $val);
                            if (!$subEntry->execute()) {
                                echo "<br>SQL-ERROR:" . $subEntry->error();
                            }
                        }
                    }
                } 
            }
        } else {
            $existingSubEntries = [];

            // this section is required to check if updates or inserts are required. Inserts are required if fields were left empty upon first time run
            $getSubs = "select id, koID, hueType from edomiProject.hueSub where mainID=".$main['id'];
            if($subEntry = $this->mysqli->prepare($getSubs)){
                if($subEntry->execute()) {
                    $result = $subEntry->get_result();
                    while ($row = $result->fetch_array(MYSQLI_ASSOC)){
                        $existingSubEntries{$row['hueType']} = $row['id'];
                    }
                }
            }

            //print_r ($existingSubEntries);
            //print "<br><br>$getSubs<br>\n";

            $mainSQL = "update edomiProject.hueMainEntry set deviceName=? where id=?";
            if($entry = $this->mysqli->prepare($mainSQL)){
                $mainID = $main['id'];

                //$this->external_dbg (2, sprintf ("enabled: %d - devname: %s, devType: %s, desc: %s, id: %s<br>\n",$enabled, $main['deviceName'],$main['huev2id'],$main['description'],$main['id']));

                $entry->bind_param('si',$main['name'],$main['id']);
                if ($entry->execute()) {
                    foreach ($sub as $key=>$val) {
                        if (empty($val)) continue;

                        if (isset($existingSubEntries{$key})) {
                            unset ($existingSubEntries{$key});
                            $subSQL = "update edomiProject.hueSub set koID=? where mainID=? and hueType=?";
                            if($subEntry = $this->mysqli->prepare($subSQL)){
                                $subEntry->bind_param('sis', $val,$mainID,$key);
                                if (!$subEntry->execute()) {
                                    echo "<br>SQL-ERROR:" . $subEntry->error();
                                }
                            }
                        } else {
                            unset ($existingSubEntries{$key});
                            $subSQL = "insert into edomiProject.hueSub (mainID,hueType, koID) values (?,?,?)";
                            if($subEntry = $this->mysqli->prepare($subSQL)){
                                $subEntry->bind_param('iss',$mainID,$key, $val);
                                if (!$subEntry->execute()) {
                                    echo "<br>SQL-ERROR:" . $subEntry->error();
                                }
                            }
                        }
                    }

                    $idList = '';
                    foreach ($existingSubEntries as $key => $val) {
                        $idList .= empty($idList) ? $val : ",$val";
                    }

                    if (!empty($idList)) {
                        $sql = "delete from edomiProject.hueSub where id in ($idList)";
                        if ($this->mysqli->query($sql)) {
                            // echo "clean up ok!";
                        } else {
                            echo "clean up failed $sql<br>!";
                        }
                    }
                }
            }
        }
    }

    private function addNewEntryDialog(&$showCardEntries) {
        $dId = $this->REQUEST["huev2id"];

        $aId = $this->findDeviceByID ($dId);
        if ($aId !== false) {
            $exportedFunctions = $this->allDevices[$aId][$dId]->getExportedFunctions();

            if ($exportedFunctions != '') {
                $dbMain['huev2id'] = $dId;

                foreach ($exportedFunctions as $key => $val) {
                    if ($key == "hidden") {
                        $dbMain[$key] = $val;
                    } else {
                        $dbSub[$key] = $val;
                    }
                    //print_r ($exportedFunctions);
                }
                    
                $this->generateInputBox ($this->allDevices[$aId][$dId], $dbMain, []);
                $showCardEntries = false;
            }
        }
    }

    private function getDBEntry ($mainID) {
        $result = false;

        $dbMain = [];
        $dbSub = [];
        
        if ($stmt = $this->mysqli->prepare("select * from edomiProject.hueMainEntry where id=$mainID")) {
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $dbMain = $result->fetch_array(MYSQLI_ASSOC);

                $stmt = $this->mysqli->prepare("select hueType, koID from edomiProject.hueSub where mainID=$mainID");
            
                if ($stmt->execute()) {
                    $result = $stmt->get_result();

                    while ($subResult = $result->fetch_array(MYSQLI_ASSOC)) {
                        $dbSub[$subResult['hueType']] = $subResult['koID'];
                    }
                }

                $result = true;
            }
        }
        return (array ($result, $dbMain, $dbSub));
    }

    private function findDeviceByID ($dId) {
        foreach ($this->allDevices as $aId => $dev) {
            if (isset ($dev[$dId])) {
                return ($aId);
            }
        }
        return (false);
    }
    
    private function editEntry(&$showCardEntries) {
        $id = $this->REQUEST["edit"];
            
        list ($result, $dbMain, $dbSub)  = $this->getDBEntry ($id);
        
        if ($result) {
            $dId = $dbMain["huev2id"];

            $aId = $this->findDeviceByID ($dId);
            if ($aId !== false) {
                $exportedFunctions = $this->allDevices[$aId][$dId]->getExportedFunctions();
                
                if ($exportedFunctions != '') {
                    $this->generateInputBox ($this->allDevices[$aId][$dId], $dbMain, $dbSub);
                    $showCardEntries = false;
                }
            }
        }
    }

    private function deleteEntry(&$showCardEntries) {
        $id=$this->REQUEST["delete"];

        $sql = "delete edomiProject.hueMainEntry,edomiProject.hueSub from edomiProject.hueMainEntry left join edomiProject.hueSub on hueMainEntry.id=mainID where hueMainEntry.id=$id";
        if ($this->mysqli->query($sql)) {
            // echo "delete ok!";
        } else  {
            echo "delete failed<br> ($sql)!";
            echo "<br>SQL-ERROR:" . mysqli_error($mysqli);
        }
    }

    public function ShowAllConfiguredDevices(){
        $sql = "select * from edomiProject.hueMainEntry"; //  m left join edomiProject.hueSub s on m.id=s.mainID
        $html = $this->cbodyHTML;
        if($stmt = $this->mysqli->prepare($sql)){
            if($stmt->execute()) {
                $result = $stmt->get_result();
                while($row = $result->fetch_array(MYSQLI_ASSOC)){

                    $type = 'Not in GW!!!';
                    $aId = $this->findDeviceByID ($row['huev2id']);
                    if ($aId !== false) {
                        $type = $this->allDevices[$aId][$row['huev2id']]->getResourceName();                        
                    }
                    
                    $html .= $this->sadd_rowX ($row['deviceName'],$type,$row['huev2id'],
                                               "<div class='fs-5'><a href='?edit=".$row['id']."'class='bi bi-wrench' style='color:white'></a></div>",
                                               "<div class='fs-5'><a href='?delete=".$row['id']."' class='bi bi-x-square-fill' style='color:red'></a></div>"); 
                }
            }
        }
        return ($html);
    }

    public function run () {
        echo $this->headerHTML;

        //$this->REQUEST["huev2id"] = "f25acf80-31a2-4737-bc1a-acd6d36c125e";  $this->REQUEST["addNewDevice"] = 1;

        $showCardEntries = true;

        // handle interactions with admin-page
        
        if (isset($this->REQUEST["saveDeviceSettings"])) {
            $this->saveEntry($showCardEntries);
        } else if (isset($this->REQUEST["addNewDevice"]) && isset($this->REQUEST["huev2id"]) ) {
            $this->addNewEntryDialog($showCardEntries);
        } else if (isset($this->REQUEST["edit"])) {
            $this->editEntry($showCardEntries);
        } else if (isset($this->REQUEST["delete"])) {
            $this->deleteEntry($showCardEntries);
        }

        if ($showCardEntries) {
            $this->getAllConfiguredECUs();
            echo $this->getAllAvailableDevices();
            echo $this->clearCacheHTML;
            echo $this->ShowAllConfiguredDevices();
        }

        echo $this->versionInfoHTML;
        echo $this->footerHTML;
    }
}

function exec_debug ($lvl,$str) {
    if ($lvl == 0)
        echo $str;
}
// //curl --insecure -N -H 'hue-application-key: u38mny5qtQXfp3ajDkRrbmGaXxx92deiz2ALYsbp' -H 'Accept: text/event-stream' https://192.168.1.64/eventstream/clip/v2

$dbgTxts = array("Kritisch","Info","Debug","LowLevel");

/*
function writeToCustomLog($lName, $dbgTxt, $output) {
    printf ("%s => %s\n",$lName.$dbgTxt,$output);
}
*/

$debugLevel = 0;

$mysqli = mysqli_connect("localhost", "root", "", "");

if(isset($_REQUEST["term"])){
    // Prepare a select statement
    $where = '';
    $binds = 0;
    $searchParam = $_REQUEST["term"];

    if (is_numeric($searchParam)) {
        $where = "id=$searchParam";
    } else {
        $where = "(name LIKE ? or ga LIKE ?)";
        $binds = 1;
    }
    $sql = "SELECT * FROM edomiProject.editKo WHERE $where and folderid >1000";
    
    if($stmt = $mysqli->prepare($sql)){
        if ($binds) {
            $searchParam .= '%';
            $stmt->bind_param("ss", $searchParam, $searchParam);
        }
        if($stmt->execute()){
            $result = $stmt->get_result();
            $outputTxt = '';            

            while($row = $result->fetch_array(MYSQLI_ASSOC)){
                $outputTxt .= "<option value='".$row["id"]."'>".$row["name"]." [".$row["ga"]."]</option>";
                //$outputTxt .= (empty($outputTxt) ? '' : "\n");
                //$outputTxt .= $row["id"]."\t".$row["name"]."\t".$row["gatyp"];
            }
            echo $outputTxt;
        } else{
            echo "SQL-ERROR: $sql. " . $mysqli->error();
        }
    }
    $stmt->close();
    $mysqli->close();
    exit;
}

if(isset($_REQUEST["clearCache"])){
    $files = glob($cacheDir . '/' . "huev2_*");
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    header ("Location: ".$_SERVER['HTTP_REFERER']);
    exit;
}

/*
function getValue ($row) {
    if ($row['value'] != NULL) {
        return ($row['value']);
    }

    $sql = 
    if($stmt = mysqli->prepare($sql)){
        if($stmt->execute()) {
            $result = $stmt->get_result();
            while($row = $result->fetch_array(MYSQLI_ASSOC)){
                switch ($row['eingang']) {
                }
            }
        }
    }
}
//$ss1 = sql_call("SELECT " . $cols . " FROM edomiLive.RAMko WHERE (id=" . $gaid . " AND gatyp=" . $gatyp . ")");

$sql = "select eingang, linkid, value from edomiLive.logicLink where eingang in(2,3) and functionid=19002629";
if($stmt = mysqli->prepare($sql)){
    if($stmt->execute()) {
        $result = $stmt->get_result();
        while($row = $result->fetch_array(MYSQLI_ASSOC)){
            switch ($row['eingang']) {
            case 2:
                if ($row['value'] != NULL)
                    $bridgeIP = $row['value'];
                    
                break;
            case 3:
                break;
            }
        }
    }
}

exit;
*/

$bIP = explode (";",$bridgeIP);
$bApiKey = explode (";",$apiKey);

$allDevices = [];
$myHue = [];

for ($i=0;$i<count ($bIP);$i++) {
    $mH = new myHueV2API ($bIP[$i], $bApiKey[$i], true, true, $cacheDir); /*, 'process'*/
    
    if ($mH && $mH->start()) {
        array_push ($myHue, $mH);

        //$mH->getAllServices();
        
        array_push ($allDevices, $mH->getAllAvailableHUEDevices());
    }
}

if (count ($allDevices) > 0) {
    /*
      print "--------------------------\n";

    foreach ($allDevices as $dev) {
        foreach ($dev as $device) {
            //print_r ($device);
            printf ("Device [%s,%s]: %s: ",$device->getID(), $device->getResourceName(), $device->getName() );
            print_r ($device->getExportedFunctions());
        }
    }
    */

    $myHueAdmin = new hueAdmin ($allDevices, $mysqli, $_REQUEST);

    $myHueAdmin->run();
}

$mysqli->close();

/*
$running = 0;
do {
    foreach ($myHue as $mH) {
        $running = $mH->runLoop();
    }
    usleep (1000);
} while ($running > 0);

function exec_debug ($lvl, $txt) {
    printf ($txt);
}
*/

?>
