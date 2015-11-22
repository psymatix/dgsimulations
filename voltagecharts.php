<html>
<head>
<style type="text/css">

th,td {
	border: 1px solid #000;
}

</style>
</head>
<body>

<?php 
$dg = $_GET['dg']; $case = $_GET['case'];
$title = $dg;

//testCases
//14
$InterestBuses14 = array(6,7,8,9,10,11,12,13,14); 

//33
$InterestBuses33 = array(6,18,33); 


//folders
//pv
$foldersPV = array("0.5MW PV", "1MW PV", "2MW PV", "4MW PV");

//wind
$foldersWind = array("1.65MW WIND", "2MW WIND", "3MW WIND");



$InterestBuses = ($case == 14) ? $InterestBuses14 : $InterestBuses33; 
$folders = ($dg == "PV") ?  $foldersPV : $foldersWind;
$output = array();

foreach ($folders as $k=>$folder){
    $output[$folder] = array();
}

//ARRAY KEYS ARE FOLDER NAMES
//FOLDER NAMES ARE COLUMN HEADERS
//INTEREST BUSES ARE ROW LEADERS

//file names
//each file contains all the bus voltages of all buses when the DG is connected to that bus

function fillData($f, $startRow, $endRow){

    $row = 1; global $InterestBuses; global $case; $dgref = array();
    //$datafile =  
  
if (($handle = fopen($f, "r")) !== FALSE) { 
    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE ) {
        
        //only row 2-17 for 14 bus
      
        $row++;
        
        if(($row > $startRow && $row < $endRow) && $data[9] != ""){
          
	$nodename = substr($data[1], 0, strpos($data[1]," "));
        if($case == 33 && (substr($data[1], 0, strpos($data[1]," ")) == "BUS") ){
            $nodename = "B" . substr($data[1], 4, 1); // convert BUS 2 .. to B2
                       
        }
        
        $nodeNum = ($case == 14) ? (substr($nodename, 4)) : (substr($nodename, 1)); //BUS_X for 14, BX FOR 33
        $nodeNum = $nodeNum + 0; 
        $nodeVoltagePU = $data[10];
        $nodeVoltageKV = $data[11];
        
             if(in_array($nodeNum, $InterestBuses) && $case == 14){
                 //only store data for buses we are interested in
                $dgref[ $nodeNum ] = array($nodeVoltagePU + 0, $nodeVoltageKV + 0); // +0 to make float
             }else{
                 //read all for 33 bus
                 $dgref[ $nodeNum ] = array($nodeVoltagePU + 0, $nodeVoltageKV + 0); // +0 to make float
             }
       
        }// if row > start row
    }
    fclose($handle);
}
ksort($dgref);
return $dgref;
    
} //fill data



?>

<?php 

foreach($output as $folder=>$fdata){
    //$fdata is an array to hold
    
    //scan through each file and read its data using buses
    foreach($InterestBuses as $k=>$bus){
        $path = "data/". $case . "/" . $folder . "/" . $bus . ".rlf";
      
     if($case == 33){
          $upperlimit = $case + 4;
     }else{
          $upperlimit = $case + (($dg == "PV") ? 3 : 4); //wind 14 has a small glitch: an element in the middle of the list  
     }   
        
        $fdata[$bus] = fillData($path, 2, $upperlimit); // replace 6.rlf with file full ref
            
    }//scan through files
   
   $output[$folder] = $fdata;
}// scan through folders


//var_dump($output);
?>

<?php 

//create tables
function display(){
    
    global $InterestBuses;
    global $folders;
    global $output;
    global $case;
    
 foreach($InterestBuses as $k=>$bus){
   echo "<h2>DG at Bus " . $bus . "</h2>";
    
    //build table
    $html = '';
    $html .= '<table>'
            . '<thead>'
            . '<tr>'
            . '<th></th>';//spacer
    
    //headers
    
    //PU headers
    foreach($folders as $fk => $dg){
        $html .=  '<th>' . $dg . '(u%)</th>';
    }
    
    // KV headers
    foreach($folders as $fk => $dg){
        $html .=  '<th>' . $dg . '(kV)</th>';
    }
    
    $html .= '</tr>'
            . '</thead>'
            . '<tbody>';
    //
    
    if($case == 14){ 
        $loopBuses = $InterestBuses;
    }elseif($case == 33){
        //
         $loopBuses = array();
        for($i = 1; $i<34; $i++){
            array_push($loopBuses, $i);
        }
    }
    
    
    foreach($loopBuses as $kk => $bb){
        $html .= '<tr>';
        $html .= '<td>' . $bb . '</td>'; //bus number as row leader
        
         foreach($folders as $fk => $dg){
                $html .=  '<td>' . $output[$dg][$bus][$bb][0] . '</td>'; // key 0 is the voltagePU
            }
    
            //repeat for voltage KV
            
             foreach($folders as $fk => $dg){
                $html .=  '<td>' . $output[$dg][$bus][$bb][1] . '</td>'; // key 1 is the voltageKV
            }  
            
        $html .= '</tr>';
    }
 
    $html .= '</tbody>'
            . '</table>';
    
    echo $html;
 }

}


?>    

    <h1><?php echo $title;?></h1>
    
<?php display(); ?>
</body>
</html>