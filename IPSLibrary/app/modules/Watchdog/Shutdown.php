<?

			echo "Selber Ausschalten und PC herunterfahren.\n";
			$handle2=fopen("c:/scripts/process_self_shutdown.bat","w");
			fwrite($handle2,'net stop IPSServer');
			fwrite($handle2,"\r\n");
			fwrite($handle2,'shutdown');
			fwrite($handle2,"\r\n");
			fclose($handle2);
			//IPS_ExecuteEx("c:/scripts/process_self_shutdown.bat","", true, false,1);



?>