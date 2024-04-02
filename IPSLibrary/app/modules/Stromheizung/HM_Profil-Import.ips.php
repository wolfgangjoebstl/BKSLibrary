<?php
/********************************
 Profil Import Script
 v 0.1 - 15.02.2014
 (c) Swifty
 Vorraussetzung IPS v3.1
********************************/

//*********************************************************
// Hier bitte die vom Profil Export Script erstellen
// Profile einfügen (Copy & Paste)
//*********************************************************
$Profile[0]="a:1:{s:18:^^HM_Heizung_Auswahl^^;a:11:{s:12:^^Associations^^;a:9:{i:0;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:1:^^ ^^;s:5:^^Value^^;d:0;}i:1;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:5:^^Küche^^;s:5:^^Value^^;d:1;}i:2;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:13:^^Arbeitszimmer^^;s:5:^^Value^^;d:2;}i:3;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:3:^^Bad^^;s:5:^^Value^^;d:3;}i:4;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:9:^^KiZ Janik^^;s:5:^^Value^^;d:4;}i:5;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:9:^^KiZ Anika^^;s:5:^^Value^^;d:5;}i:6;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:4:^^WZ01^^;s:5:^^Value^^;d:6;}i:7;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:4:^^WZ02^^;s:5:^^Value^^;d:7;}i:8;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:4:^^WZ03^^;s:5:^^Value^^;d:8;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:1;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:18:^^HM_Heizung_Auswahl^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[1]="a:1:{s:8:^^Praesenz^^;a:11:{s:12:^^Associations^^;a:3:{i:0;a:4:{s:5:^^Color^^;i:10079487;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:6:^^Normal^^;s:5:^^Value^^;d:0;}i:1;a:4:{s:5:^^Color^^;i:13434828;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:4:^^Frei^^;s:5:^^Value^^;d:1;}i:2;a:4:{s:5:^^Color^^;i:16764057;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:8:^^Abwesend^^;s:5:^^Value^^;d:2;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:3;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:8:^^Praesenz^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[2]="a:1:{s:20:^^HM_Heizung_Wochentag^^;a:11:{s:12:^^Associations^^;a:7:{i:0;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:2:^^Mo^^;s:5:^^Value^^;d:0;}i:1;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:2:^^Di^^;s:5:^^Value^^;d:1;}i:2;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:2:^^Mi^^;s:5:^^Value^^;d:2;}i:3;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:2:^^Do^^;s:5:^^Value^^;d:3;}i:4;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:2:^^Fr^^;s:5:^^Value^^;d:4;}i:5;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:2:^^Sa^^;s:5:^^Value^^;d:5;}i:6;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:2:^^So^^;s:5:^^Value^^;d:6;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:0;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:20:^^HM_Heizung_Wochentag^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[3]="a:1:{s:15:^^HM_Heizung_Slot^^;a:11:{s:12:^^Associations^^;a:0:{}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:3;s:8:^^MinValue^^;d:1;s:6:^^Prefix^^;s:4:^^Slot^^;s:11:^^ProfileName^^;s:15:^^HM_Heizung_Slot^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:1;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[4]="a:1:{s:26:^^HM_Heizung_Temperatur_Edit^^;a:11:{s:12:^^Associations^^;a:4:{i:0;a:4:{s:5:^^Color^^;i:3368703;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:5:^^-1 °C^^;s:5:^^Value^^;d:1;}i:1;a:4:{s:5:^^Color^^;i:3368703;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:7:^^-0,1 °C^^;s:5:^^Value^^;d:2;}i:2;a:4:{s:5:^^Color^^;i:16711680;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:7:^^+0,1 °C^^;s:5:^^Value^^;d:3;}i:3;a:4:{s:5:^^Color^^;i:16711680;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:5:^^+1 °C^^;s:5:^^Value^^;d:4;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:0;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:26:^^HM_Heizung_Temperatur_Edit^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[5]="a:1:{s:20:^^HM_Heizung_Zeit_Edit^^;a:11:{s:12:^^Associations^^;a:6:{i:0;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:3:^^-1h^^;s:5:^^Value^^;d:1;}i:1;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:6:^^-10min^^;s:5:^^Value^^;d:2;}i:2;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:5:^^-5min^^;s:5:^^Value^^;d:3;}i:3;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:5:^^+5min^^;s:5:^^Value^^;d:4;}i:4;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:6:^^+10min^^;s:5:^^Value^^;d:5;}i:5;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:3:^^+1h^^;s:5:^^Value^^;d:6;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:0;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:20:^^HM_Heizung_Zeit_Edit^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[6]="a:1:{s:23:^^HM_Heizung_Slot_add_del^^;a:11:{s:12:^^Associations^^;a:2:{i:0;a:4:{s:5:^^Color^^;i:16764057;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:16:^^ZeitSlot Löschen^^;s:5:^^Value^^;d:1;}i:1;a:4:{s:5:^^Color^^;i:13434828;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:16:^^ZeitSlot anfügen^^;s:5:^^Value^^;d:2;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:0;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:23:^^HM_Heizung_Slot_add_del^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[7]="a:1:{s:27:^^HM_Heizung_Profil_speichern^^;a:11:{s:12:^^Associations^^;a:1:{i:0;a:4:{s:5:^^Color^^;i:16711680;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:16:^^Profil speichern^^;s:5:^^Value^^;d:1;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:0;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:27:^^HM_Heizung_Profil_speichern^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[8]="a:1:{s:29:^^HM_heizung_Profil_uebertragen^^;a:11:{s:12:^^Associations^^;a:2:{i:0;a:4:{s:5:^^Color^^;i:-1;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:1:^^ ^^;s:5:^^Value^^;d:0;}i:1;a:4:{s:5:^^Color^^;i:3368703;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:17:^^Profil übertragen^^;s:5:^^Value^^;d:1;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:0;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:29:^^HM_heizung_Profil_uebertragen^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[9]="a:1:{s:20:^^HM_Heizung_Steuerung^^;a:11:{s:12:^^Associations^^;a:5:{i:0;a:4:{s:5:^^Color^^;i:3368703;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:6:^^Sommer^^;s:5:^^Value^^;d:-1;}i:1;a:4:{s:5:^^Color^^;i:16711680;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:7:^^Manuell^^;s:5:^^Value^^;d:0;}i:2;a:4:{s:5:^^Color^^;i:32768;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:9:^^Automatik^^;s:5:^^Value^^;d:1;}i:3;a:4:{s:5:^^Color^^;i:16750848;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:8:^^Zentrale^^;s:5:^^Value^^;d:2;}i:4;a:4:{s:5:^^Color^^;i:8388608;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:5:^^Party^^;s:5:^^Value^^;d:3;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:0;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:20:^^HM_Heizung_Steuerung^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[10]="a:1:{s:29:^^HM_Wochenprofil_aktualisieren^^;a:11:{s:12:^^Associations^^;a:1:{i:0;a:4:{s:5:^^Color^^;i:3368703;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:13:^^Aktualisieren^^;s:5:^^Value^^;d:0;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:0;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:29:^^HM_Wochenprofil_aktualisieren^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";
$Profile[11]="a:1:{s:26:^^HM_Heizung_Steuerung_RT-DN^^;a:11:{s:12:^^Associations^^;a:4:{i:0;a:4:{s:5:^^Color^^;i:3368703;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:6:^^Sommer^^;s:5:^^Value^^;d:-1;}i:1;a:4:{s:5:^^Color^^;i:3381606;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:9:^^Automatik^^;s:5:^^Value^^;d:0;}i:2;a:4:{s:5:^^Color^^;i:16711680;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:7:^^Manuell^^;s:5:^^Value^^;d:1;}i:3;a:4:{s:5:^^Color^^;i:16777113;s:4:^^Icon^^;s:0:^^^^;s:4:^^Name^^;s:5:^^Boost^^;s:5:^^Value^^;d:3;}}s:6:^^Digits^^;i:0;s:4:^^Icon^^;s:0:^^^^;s:10:^^IsReadOnly^^;b:0;s:8:^^MaxValue^^;d:0;s:8:^^MinValue^^;d:0;s:6:^^Prefix^^;s:0:^^^^;s:11:^^ProfileName^^;s:26:^^HM_Heizung_Steuerung_RT-DN^^;s:11:^^ProfileType^^;i:1;s:8:^^StepSize^^;d:0;s:6:^^Suffix^^;s:0:^^^^;}}";

//***********************************************************************

$overwrite=true;


Foreach ($Profile as $Profil)
	{
   $temp=str_replace(':^^' , ':"' , $Profil);
	$temp=str_replace('^^;' , '";' , $temp);

	$Profil=unserialize($temp);
   profil_erstellen($Profil);
 	}


function Profil_erstellen($Profil)

	{

	//print_r($Profil);
	$ProfilName=array_keys($Profil)[0];

	$Profil=$Profil[$ProfilName];

   $Digits=$Profil['Digits'];
   $Icon=$Profil['Icon'];
   $IsReadOnly=$Profil['IsReadOnly'];
	$Minimalwert=$Profil['MaxValue'];
	$Maximalwert=$Profil['MinValue'];
	$Praefix=$Profil['Prefix'];
	$Suffix=$Profil['Suffix'];
   $Typ=$Profil['ProfileType'];      //0=Boolean, 1=Integer, 2=Float, 3=String
	$Schrittweite=$Profil['StepSize'];



  	Foreach($Profil['Associations'] as $key=> $Assos)
		{
		$Asso[$key]=array("Wert" => $Assos['Value'],
			 				   "Name" => $Assos['Name'],
								"Icon" => $Assos['Icon'],
								"Farbe"=> $Assos['Color']);

		}

		//echo $ProfilName."\n";
		//print_r($Profil['Associations']);
		//	print_r($Asso);


	If (IPS_VariableProfileExists($ProfilName))
		{
		if ($overwrite=true)
		   {
	   	IPS_SetVariableProfileText ( $ProfilName , $Praefix , $Suffix );
		   IPS_SetVariableProfileValues ( $ProfilName, $Minimalwert , $Maximalwert , $Schrittweite );
			IPS_SetVariableProfileDigits ($ProfilName,$Digits);
			IPS_SetVariableProfileIcon ($ProfilName,$Icon);
			If (Count($Profil['Associations'])>0)
			   {
				Foreach($Asso as $Association)
					{
	   			IPS_SetVariableProfileAssociation ( $ProfilName , $Association['Wert'] , $Association['Name'] , $Association['Icon'] , $Association['Farbe'] );
					}
				}
			}
  		}
		else
		{
	   IPS_CreateVariableProfile ( $ProfilName , $Typ );
   	IPS_SetVariableProfileText ( $ProfilName , $Praefix , $Suffix );
	   IPS_SetVariableProfileValues ( $ProfilName, $Minimalwert , $Maximalwert , $Schrittweite );
		IPS_SetVariableProfileDigits ($ProfilName,$Digits);
		IPS_SetVariableProfileIcon ($ProfilName,$Icon);
		If (Count($Profil['Associations'])>0)
			   {
				Foreach($Asso as $Association)
					{
	   			IPS_SetVariableProfileAssociation ( $ProfilName , $Association['Wert'] , $Association['Name'] , $Association['Icon'] , $Association['Farbe'] );
					}
				}
		}
//	print_r(IPS_GetVariableProfileList ( ));
//	print_r(IPS_GetVariableProfile($ProfilName));
	}

?>