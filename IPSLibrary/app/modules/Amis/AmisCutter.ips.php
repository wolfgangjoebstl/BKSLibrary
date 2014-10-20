<?

/*
	 * @defgroup 
	 * @ingroup
	 * @{
	 *
	 * Script zur 
	 *
	 *
	 * @file      
	 * @author        Wolfgang Joebstl
	 * @version
	 *  Version 2.50.52, 07.08.2014<br/>
*/

Include_once(IPS_GetKernelDir()."scripts\IPSLibrary\AllgemeineDefinitionen.inc.php");

/******************************************************

				INIT

*************************************************************/


/* macht das selbe wie der Cutter */




//Hier die COM-Port Instanz
$serialPortID = IPS_GetInstanceListByModuleID('{6DC3D946-0D31-450F-A8C6-C42DB8D7D4F1}');
$com_Port = $serialPortID[0];

$parentid1  = IPSUtil_ObjectIDByPath('Program.IPSLibrary.data.modules.Amis');
// Wert in der die aktuell gerade empfangenen Einzelzeichen hineingeschrieben werden
$AMISReceiveCharID = CreateVariableByName($parentid1, "AMIS ReceiveChar", 3);
$AMISReceiveChar1ID = CreateVariableByName($parentid1, "AMIS ReceiveChar1", 3);

if (!file_exists("C:\Scripts\Log_Cutter.csv"))
		{
      $handle=fopen("C:\Scripts\Log_Cutter.csv", "a");
	   fwrite($handle, date("d.m.y H:i:s").";Zählerdatensatz\r\n");
      fclose($handle);
	   }

if ($_IPS['SENDER'] == "RegisterVariable")
	 {
    $content = $_IPS['VALUE'];

  	 $handle=fopen("C:\Scripts\Log_Cutter.csv","a");
	 $ausgabewert=date("d.m.y H:i:s").";".strlen($content).";";
	 for($i=0;$i<strlen($content);$i++)
	 	{
		//$ausgabewert.=ord($content[$i]).";".$content[$i].";";
		if (ord($content[$i])==2)
		   {
		   //$ausgabewert.="Anfang**********************;";
			SetValue($AMISReceiveChar1ID,GetValue($AMISReceiveCharID));
			SetValue($AMISReceiveCharID,"");
		   }
		else
		   {
			if (ord($content[$i])==3)
			   {
			   //$ausgabewert.="Ende**********************;";
			   $ausgabewert.=GetValue($AMISReceiveCharID).";";
			   $trans = array(chr(13) => "", chr(10) => "");
			   fwrite($handle, strtr($ausgabewert,$trans)."\r\n");
			   
			   /* verarbeitung der eingelesenen Telegramme  */
			   /*                                           */
			   /*                                           */
			   /*                                           */
			   /*********************************************/

		   	}
		   else
				{
				SetValue($AMISReceiveCharID,GetValue($AMISReceiveCharID).$content[$i]);
				}
			}
		}

	 fclose($handle);
	 }
	 
	 
	   
?>
