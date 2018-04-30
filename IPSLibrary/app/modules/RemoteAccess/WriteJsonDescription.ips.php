<?

	/*
	 * This file is part of the IPSLibrary.
	 *
	 * The IPSLibrary is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published
	 * by the Free Software Foundation, either version 3 of the License, or
	 * (at your option) any later version.
	 *
	 * The IPSLibrary is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with the IPSLibrary. If not, see http://www.gnu.org/licenses/gpl.txt.
	 */    


/*
 * Aus den aktuellen Filenamen eine Description erfinden und Objekte Kategorisieren
 * Alles als Json String in der Instanz Beschreibung abspeichern
 *
 */

/* Struktur erfassen */

$alleKategorien = IPS_GetCategoryList();
//print_r($alleKategorien); 
foreach ($alleKategorien as $Kategorie)
	{
	//echo $Kategorie."     ".IPS_GetName($Kategorie)."\n";
	}
$HardwareID=IPS_GetCategoryIDByName ("Hardware",0);
echo "Kategorie Hardware hat ID: ".$HardwareID."\n";
$FS20ID=IPS_GetCategoryIDByName ("FS20",$HardwareID);
echo "Kategorie FS20 hat ID: ".$FS20ID."\n";

$rooms=array("Wohnzimmer","Kellerzimmer","Arbeitszimmer");

$info=array();
$info["Ort"]="BKS";
$info["Typ"]="FS20";


$devices=IPS_GetChildrenIDs($FS20ID);
//print_r($devices);
foreach ($devices as $device)
	{
	$deviceName=IPS_GetName($device);
	$evalName=explode("-",$deviceName);
	echo $device."     ".$deviceName."    ".sizeof($evalName);

	if (in_array($evalName[0],$rooms)==true)
		{
		$info["Raum"]=$evalName[0];
		$infostring=json_encode($info);
		echo "     ".$infostring."\n";	
		IPS_SetInfo($device,$infostring);
		}
	else echo "\n";
			
	}




?>