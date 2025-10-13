<?php


/*********************************************************************************************/

class EmailControlCenter
	{

	var $CategoryIdData       	= 0;
	var $SendEmailID			= 0;
	
	var $installedModules     	= array();
	var $log_OperationCenter  	= array();
	
	var $FilenameActual        	= "";
	var $FilenameHistory        = "";
	var $DIR_copystatusdropbox	= "";	
	var $device					= "";	

	/**
	 * @public
	 *
	 * Initialisierung des EmailControlCenter Objektes
	 *
	 */
	public function __construct()
		{
		$repository = 'https://raw.githubusercontent.com//wolfgangjoebstl/BKSLibrary/master/';
		if (!isset($moduleManager)) 
			{
			IPSUtils_Include ('IPSModuleManager.class.php', 'IPSLibrary::install::IPSModuleManager');
			$moduleManager = new IPSModuleManager('Emailsteuerung',$repository);
			}
		$this->installedModules = $moduleManager->GetInstalledModules();
		$this->CategoryIdData     = $moduleManager->GetModuleCategoryID('data');
		$this->SendEmailID = IPS_GetInstanceIDByName("SendEmail", $this->CategoryIdData);		
		
		echo "construct EmailControlCenter:\n";
		if (isset($this->installedModules['OperationCenter']))
			{
			echo "   OperationCenter installiert, check ob es auf Dropbox Verzeichnis eine Status Datei gibt.\n ";
			IPSUtils_Include ("OperationCenter_Configuration.inc.php","IPSLibrary::config::modules::OperationCenter");
			IPSUtils_Include ("OperationCenter_Library.class.php","IPSLibrary::app::modules::OperationCenter");

			$moduleManagerOC = new IPSModuleManager('OperationCenter',$repository);
			$CategoryIdData  = $moduleManagerOC->GetModuleCategoryID('data');

			$categoryId_Nachrichten    = CreateCategory('Nachrichtenverlauf',   $CategoryIdData, 20);
			$input = CreateVariable("Nachricht_Input",3,$categoryId_Nachrichten, 0, "",null,null,""  );
			$this->log_OperationCenter=new Logging("C:\Scripts\Log_OperationCenter.csv",$input);
				
			$OperationCenter=new OperationCenter();
			$this->DIR_copystatusdropbox = $OperationCenter->oc_Setup['DropboxStatusDirectory'].IPS_GetName(0).'/';
			echo "  Gefunden, Status Dateien findet man auf ".$this->DIR_copystatusdropbox.".\n";
			$this->FilenameActual=$this->DIR_copystatusdropbox.date("Ymd").'StatusAktuell.txt';
			$this->FilenameHistory=$this->DIR_copystatusdropbox.date("Ymd").'StatusHistorie.txt';
			}
		$this->device=IPS_GetName(0);
		echo "   Du arbeitest auf Gerät : ".$this->device." und sendest zwei Statusemails über den Email Client : ".$this->SendEmailID."\n";					
		}

	public function SendMailStatusasAttachment($delay=0)
		{
		$emailstatus=true;
		if (isset($this->installedModules['OperationCenter']))
			{		
			$emailText="\nLogspeicher ausgedruckt:\n".$this->log_OperationCenter->PrintNachrichten();
			if ($delay != 0)
				{
				$filenameActual=$this->DIR_copystatusdropbox.date("Ymd",time()-$delay).'StatusAktuell.txt';
				$filenameActualwoDir=date("Ymd",time()-$delay).'StatusAktuell.txt';
				$filenameHistory=$DIR_copystatusdropbox.date("Ymd",time()-$delay).'StatusHistorie.txt';	
				$filenameHistorywoDir=date("Ymd",time()-$delay).'StatusHistorie.txt';
				}
			else
				{
				$filenameActual=$this->FilenameActual;
				$filenameActualwoDir=date("Ymd").'StatusAktuell.txt';
				$filenameHistory=$this->FilenameHistory;
				$filenameHistorywoDir=date("Ymd").'StatusHistorie.txt';						
				}
			$error=false;
			if ( ($status=@file_get_contents($this->FilenameActual)) === false)
				{
				echo "EmailControlCenter: Filename ".$filenameActual." wurde noch nicht erzeugt.\n";
				$emailStatus2=@SMTP_SendMail($this->SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle Werte ".$this->device, "File wurde noch nicht erzeugt !".$emailText);
				if ($emailStatus2==false) 
					{
					echo "EmailControlCenter: Fehler bei der email Uebertragung der Aktuellen Werte.\n";
					}
				$error=true;				
				}
			if ( ($status=@file_get_contents($this->FilenameHistory)) === false)
				{
				echo "EmailControlCenter: Filename ".$filenameHistory." wurde noch nicht erzeugt.\n";
				$emailStatus2=@SMTP_SendMail($this->SendEmailID,date("Y.m.d D")." Nachgefragter Status, historische Werte ".$this->device, "File wurde noch nicht erzeugt !".$emailText);
				if ($emailStatus2==false) 
					{
					echo "EmailControlCenter: Fehler bei der email Uebertragung der Historischen Werte.\n";
					}				
				$error=true;				
				}
			if ($error==false)	/* die Statusdateien sind vorhanden, es geht weiter, bei beiden Dateien immer Übertraqgung als Dropbox Link ! */
				{
				$LinkDropbox=	'https://www.dropbox.com/home/PrivatIPS/IP-Symcon/Status/'.$this->device.'?preview='.$filenameActualwoDir."\n".
									'https://www.dropbox.com/home/PrivatIPS/IP-Symcon/Status/'.$this->device.'?preview='.$filenameHistorywoDir;
				$emailStatus=@SMTP_SendMail($this->SendEmailID,date("Y.m.d D")." Nachgefragter Status ".$this->device, "Übertragung als Dropbox Link:\n".$LinkDropbox."\n\n".$emailText);
				if ($emailStatus==false) 
					{
					echo "EmailControlCenter: Fehler bei der email Uebertragung der Aktuellen Werte.\n";
					}			
				}
			}	
		return ($emailstatus);	
		}
		
	public function SendMailStatusActualasAttachment($delay=0)
		{
		$emailstatus=true;
		if (isset($this->installedModules['OperationCenter']))
			{		
			$emailText="\nLogspeicher ausgedruckt:\n".$this->log_OperationCenter->PrintNachrichten();
			if ($delay != 0)
				{
				$filenameActual=$this->DIR_copystatusdropbox.date("Ymd",time()-$delay).'StatusAktuell.txt';
				$filenamewoDir=date("Ymd",time()-$delay).'StatusAktuell.txt';							
				}
			else
				{
				$filenameActual=$this->FilenameActual;
				$filenamewoDir=date("Ymd").'StatusAktuell.txt';					
				}
		
			if ( ($status=@file_get_contents($this->FilenameActual)) === false)
				{
				echo "EmailControlCenter: Filename ".$filenameActual." wurde noch nicht erzeugt.\n";
				$emailStatus2=@SMTP_SendMail($this->SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle Werte ".$this->device, "File wurde noch nicht erzeugt !".$emailText);
				if ($emailStatus2==false) 
					{
					echo "EmailControlCenter: Fehler bei der email Uebertragung der Aktuellen Werte.\n";
					}			
				}
			else
		   		{
				$emailStatus=@SMTP_SendMailAttachment($this->SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle Werte ".$this->device, "Daten und Auswertungen siehe Anhang\n".$emailText,$filenameActual);
				if ($emailStatus==false) 
					{
					echo "EmailControlCenter: Fehler bei der email Uebertragung der Aktuellen Werte als Anhang. Uebertragung als Dropbox Link.\n";
					$LinkDropbox='https://www.dropbox.com/home/PrivatIPS/IP-Symcon/Status/'.$this->device.'?preview='.$filenamewoDir;
					$emailStatus=@SMTP_SendMail($this->SendEmailID,date("Y.m.d D")." Nachgefragter Status, aktuelle Werte ".$this->device, "Übertragung File als Anhang nicht erfolgreich. Übertragung als Dropbox Link:\n".$LinkDropbox."\n\n".$emailText);
					if ($emailStatus==false) 
						{
						echo "EmailControlCenter: Fehler bei der email Uebertragung der Aktuellen Werte.\n";
						}			
					}
				}
			}	
		return ($emailstatus);	
		}	 	

	public function SendMailStatusHistoryasAttachment($delay=0)
		{
		$emailstatus=true;
		if (isset($this->installedModules['OperationCenter']))
			{		
			$emailText="\nLogspeicher ausgedruckt:\n".$this->log_OperationCenter->PrintNachrichten();
			if ($delay != 0)
				{
				$filenameHistory=$DIR_copystatusdropbox.date("Ymd",time()-$delay).'StatusHistorie.txt';	
				$filenamewoDir=date("Ymd",time()-$delay).'StatusHistorie.txt';		
				}
			else
				{
				$filenameHistory=$this->FilenameHistory;
				$filenamewoDir=date("Ymd").'StatusHistorie.txt';	
				}
							
			if ( ($status=@file_get_contents($this->FilenameHistory)) === false)
				{
				echo "EmailControlCenter: Filename ".$filenameHistory." wurde noch nicht erzeugt.\n";
				$emailStatus2=@SMTP_SendMail($this->SendEmailID,date("Y.m.d D")." Nachgefragter Status, historische Werte ".$this->device, "File wurde noch nicht erzeugt !".$emailText);
				if ($emailStatus2==false) 
					{
					echo "EmailControlCenter: Fehler bei der email Uebertragung der Historischen Werte.\n";
					}				
				}
			else
			   {
				$emailStatus=@SMTP_SendMailAttachment($this->SendEmailID,date("Y.m.d D")." Nachgefragter Status, historische Werte ".$this->device, "Daten und Auswertungen siehe Anhang\n".$emailText,$filenameHistory);
				if ($emailStatus==false) 
					{
					echo "EmailControlCenter: Fehler bei der email Uebertragung der Aktuellen Werte als Anhang. Uebertragung als Dropbox Link.\n";
					$LinkDropbox='https://www.dropbox.com/home/PrivatIPS/IP-Symcon/Status/'.$this->device.'?preview='.$filenamewoDir;
					$emailStatus=@SMTP_SendMail($this->SendEmailID,date("Y.m.d D")." Nachgefragter Status, historische Werte ".$this->device, "Übertragung File als Anhang nicht erfolgreich. Übertragung als Dropbox Link:\n".$LinkDropbox."\n\n".$emailText);
					if ($emailStatus==false) 
						{
						echo "EmailControlCenter: Fehler bei der email Uebertragung der Historischen Werte als Anhang.\n";
						}
					}	
				}		
			}	 	
		return ($emailstatus);	
		}

	public function GetDirStatusActual()
		{
		echo "EmailControlCenter: Inhalt des Verzeichnisses der Status Dateien : ".$this->DIR_copystatusdropbox." \n";
		$dir=dirtoArray($this->DIR_copystatusdropbox);
		print_r($dir);	
		}
		
	private function dirToArray($dir)
		{
	   	$result = array();

		$cdir = scandir($dir);
		foreach ($cdir as $key => $value)
			{
			if (!in_array($value,array(".","..")))
				{
				if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
         			{
					$result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value);
	         		}
    	     	else
        	 		{
            		$result[] = $value;
         			}
	      		}
   			}
		return $result;
		}						

	}

/*********************************************************************************************/




?>