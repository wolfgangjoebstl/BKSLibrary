$(document).ready(function(){  
"use strict";

	var $write = $('#write');					// keine globale Variable ???
	//var shift = false, capslock = false;
	
  $("#phide").click(function(){ $(this).hide(); });
	
	//$('#guthbnField').click(function(){ alert("hi");  });
    //$("#guthbnField").click(function(){ $(this).hide(); });
	
  $("#guthbnField").click(function(){ 				    // Function um Parameter aus der URi zu holen
	var ipsValue = get_url_param('action');
	$(this).html("<p>action="+ipsValue+"</p>"); 
	});	
	
    function get_url_param( name ) {
        name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
        var regexS = "[\\?&]"+name+"=([^&#]*)";
        var regex = new RegExp( regexS );
        var results = regex.exec( window.location.href );
		//return ( window.location.href );
        if ( results == null )
            return "";
        else
            return results[1];
    }


  $("#guthbnField-More").click(function(){ 			// Objekt Manipulation
	 var $write = $('#write');
     document.getElementById('demotext').innerHTML = "demotext : "+$write.text();
	 //$("#write").text("jetzt aber");
	 $write.text("jetzt aber");
	 //document.write("Hallo");   Wuerde das ganze fenster überschreiben
	 //$(this).hide(); 
	 });

  $("#guthbnFieldAjaxFirst").click(function(){ 					// Ajax Request
  	 var $write = $('#write');
     ajaxrequest('/user/Guthabensteuerung/GuthabensteuerungReceiver.php', 'get', 'command=ARD');			// die ersten beiden Parameter sind exakt festgelegt		
	 $write.text("jetzt aber Ajaxrequest abgesetzt.");
	 });
		

//Ajax aufrufen
	function ajaxrequest(action, method, data)
		{
			// Der eigentliche AJAX Aufruf
			$.ajax({
				url : action,
				type : method,
				//contentType: "application/x-www-form-urlencoded; charset=UTF-8";
				data : data,
				//dataType: "xml",
			}).done(function (data) {
				// Bei Erfolg
					//alert("Erfolgreich:\n XML Response:\n" + data);
					
					//Daten auswerten
					// Antwort des Servers -> als XML-Dokument
					e2response(data);	
					return data;
			}).fail(function() {
				// Bei Fehler
				alert("Fehler Ajax!");
				
			}).always(function() {
				// Immer
				 //alert("Beendet!");
				// Funktionen bei Beenden
				//cue the page loader
				//$.mobile.loading( 'hide' );
			});
		}
		
		



//Formular für Ajax 
	$("form").submit(function(event) {
		// Das eigentliche Absenden verhindern
		event.preventDefault();
		//cue the page loader
		//$.mobile.loading( 'show' );
		// Das sendende Formular und die Metadaten bestimmen
		var form = $(this); // Dieser Zeiger $(this) oder $("form"), falls die ID form im HTML exisitiert, klappt übrigens auch ohne jQuery ;)
		var action = form.attr("action"), // attr() kann enweder den aktuellen Inhalt des gennanten Attributs auslesen, oder setzt ein neuen Wert, falls ein zweiter Parameter gegeben ist
			method = form.attr("method"),
			data   = form.serialize(); // baut die Daten zu einem String nach dem Muster vorname=max&nachname=Müller&alter=42 ... zusammen
			//alert (data);
			ajaxrequest(action, method, data);
	});
	
//XML Response auswerten
//*
//Dreambox
// Auf command prüfen und die passende function aufrufen 
	function e2response(data)
		{
			// Dreambox Funktionsanfrage prüfen und passende Auswertung aufrufen
			// XML Antworten Child names sind je nach Funktion unterschiedlich daher muss der Name vom ersten Child geprüft werden
			//alert ("e2response: Ok");
			
			// Prüft auf den ersten Childname des XML
			var firstnode = data.documentElement.nodeName;
			//alert ("Name der ersten Node:\n" + firstnode);
						
			if (firstnode === "statuslist")
			{
				
				$(data).find('status').each(function()
				{
						var status = $(this).find('neostatus').text();
						alert (status);
						if (status === "Alles Prima")
							{
								
								$("#responseips").html(status);
								alert ("Status "+status+" !");
								
							}
						if (status === "Alles Bunt")
							{
								
								$("#responseips").html(status);
								alert ("Status "+status+" !");
								
							}
				
				});
		
			}
	
		}
	
	

			
			
/*
    var anon = function()    {   $write.val("Werner");  }
    


    
    $('#keyboard li').click(function(){
        var $this = $(this),
            character = $this.val(); // If it's a lowercase letter, nothing happens to this variable
        
        // Shift keys
        if ($this.hasClass('left-shift') || $this.hasClass('right-shift')) {
            $('.letter').toggleClass('uppercase');
            $('.symbol span').toggle();
            
            shift = (shift === true) ? false : true;
            capslock = false;
            return false;
        }
        
        // Caps lock
        if ($this.hasClass('capslock')) {
            $('.letter').toggleClass('uppercase');
            capslock = true;
            return false;
        }
        
        // Delete
        if ($this.hasClass('delete')) {
            var html = $write.val();
            
            $write.val(html.substr(0, html.length - 1));
            return false;
        }
        
        // Special characters
        if ($this.hasClass('symbol')) character = $('span:visible', $this).html();
        if ($this.hasClass('space')) character = ' ';
        if ($this.hasClass('tab')) character = "	";
        if ($this.hasClass('return')) character = "
";
        
        // Uppercase letter
        if ($this.hasClass('uppercase')) character = character.toUpperCase();
        
        // Remove shift once a key is clicked.
        if (shift === true) {
            $('.symbol span').toggle();
            if (capslock === false) $('.letter').toggleClass('uppercase');
            
            shift = false;
        }
    
        // Senden
        if ($this.hasClass('senden')) {
            var html = $write.val();
            
                        xhReq = new XMLHttpRequest;
                        xhReq.open("POST","keyboard-receive.php",true);
                        xhReq.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        action = "value=" + $write.val();
                        action += "&ipsValue=" + ipsValue;
                        xhReq.send(action);
                        // Eingabefeld der Tastatur löschen
                        $write.val(html.substr(0, html.length - html.length));


                        skipThis = false;
                        
            return false;
        }


    
        // Add the character
        $write.val($write.val() + character);
    
        var timerid;    
        $write.change(function(e){
            var value = $(this).val();
            if($(this).data("lastval")!= value){
                $(this).data("lastval",value);
                


                clearTimeout(timerid);
                timerid = setTimeout(function() {


                    //change action
                    $write.val("");   


                },10000);


            };
        }).change();
            
    });  */
});

// warum das ausserhalb von jquery sein muss, noch nicht verstanden

	function trigger_button_id(id, action, module, info) {
		//var WFC10Path             = $("#WFC10Path").val();
		
		//id="unKnown";
		document.getElementById('demoText').innerHTML = Date()+" id="+id+" action="+action+" module="+module;		// ist nach dem refresh wieder weg
		$.ajax({type: "POST",
				url: "/user/Guthabensteuerung/GuthabensteuerungReceiver.php",
				data: "id="+id+"&action="+action+"&module="+module+"&info="+info});
			};

	function trigger_button(action, module, info) {
		id = "trigger_button";
		document.getElementById('demoText').innerHTML = Date()+" action="+action+" module="+module+" info="+info;		// ist nach dem refresh wieder weg
		$.ajax({type: "POST",
				url: "/user/Guthabensteuerung/GuthabensteuerungReceiver.php",
				data: "id="+id+"&action="+action+"&module="+module+"&info="+info});
			};			
			



