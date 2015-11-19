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
$dg = $_GET['dg']; $case = $_GET['case']; $loss = $_GET['loss'];
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
        
        if(($row > $startRow && $row < $endRow)){
            if(substr($data[3], 0, 6) == "BRANCH"){ 
            //only store data for lines, buses have that field blank
                
                    $branchKey = $data[3]; 
                    $mwLoss = $data[17];
                    $mvarLoss = $data[18];

           
                
                $dgref[ $branchKey ] = array($mwLoss + 0, $mvarLoss + 0); // +0 to make float
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
      
    $upperlimit = 140; // go through all the lines
    
        $fdata[$bus] = fillData($path, 2, $upperlimit); // replace 6.rlf with file full ref
            
    }//scan through files
   
   $output[$folder] = $fdata;
}// scan through folders

//print_r($output);
 //  print_r( $output["0.5MW PV"][18]);

?>

<?php 

//create tables
function display(){
    
    global $InterestBuses;
    global $folders;
    global $output;
    global $loss;
    
 //build table
    $html = '';
    $html .= '<table>'
            . '<thead>'
            . '<tr>'
            . '<th></th>';//spacer
    
    //headers
    
    //PU headers
    foreach($folders as $fk => $dg){
        $html .=  '<th>' . $dg ;
       $html .= '(' . $loss . ')';
        $html .= '</th>';
       
    }
  
    $html .= '</tr>'
            . '</thead>'
            . '<tbody>';  
        
    foreach($InterestBuses as $kk => $bb){
     
        $html .= '<tr>';
        $html .= '<td>' . $bb . '</td>'; //bus number as row leader
      
         foreach($folders as $fk => $dg){
             
             $allLosses = ""; //reset for each dg group
             
            //bus losses
             $allLosses = $output[$dg][$bb]; //eg. [1MW][6] or [2MW][6]
             $totalMWLoss = 0; $totalMVARLoss = 0;
                foreach($allLosses as $branchName=>$bLosses){
                   $totalMWLoss = $totalMWLoss + $bLosses[0];
                   $totalMVARLoss = $totalMVARLoss + $bLosses[1];
                }
                switch($loss){
                    case "MW":
                        $disp = $totalMWLoss;
                        break;
                    case "MVAR":
                        $disp = $totalMVARLoss;
                        break;
                    case "MVA":
                        $disp = sqrt(pow($totalMWLoss,2) + pow($totalMVARLoss, 2));
                        $disp = round($disp, 4);
                        break;
                }
              $html .=  '<td>' . $disp  . '</td>'; 
           
            }
    
            
        $html .= '</tr>';
    }
     
    $html .= '</tbody>'
            . '</table>';
    
    echo $html;


}


?>    

    <h1><?php echo $title;?></h1>
    
<?php display(); ?>
</body>
</html>