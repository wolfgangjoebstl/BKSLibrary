$(document).ready(function(){  
"use strict";

	let pendingUpdate = false;
	let pic=true, pic0=true, pic1=true, pic2=true;
	let item=0;
	var $write = $('#write');					// keine globale Variable ???
	//var shift = false, capslock = false;

	window.visualViewport.addEventListener("scroll", viewportHandler);
	window.visualViewport.addEventListener("resize", viewportHandler);
		 
	// height ist immer Höhe iFrame, responsive muss ausserhalb sein

	function viewportHandler(event) {
		const viewport = event.target;
		document.getElementById('sp-inf-txt').innerHTML = "infonow+" + Date() + "Viewport Height x Width : "+viewport.height+" x "+viewport.width+"Screen: "+screen.width+" x "+screen.height  ;
		if (viewport.width<1100) {
			$(".image-pic9").css("display", "none");
			$(".image-pic4").css("display", "none");	
			$(".image-pic0").css("display", "inline");			
    		 }
		else {
		if (viewport.width<1400) {
			$(".image-pic9").css("display", "none");
			$(".image-pic4").css("display", "inline");
			$(".image-pic0").css("display", "none");			
			}
		else {			
			$(".image-pic0").css("display", "none");			
			$(".image-pic4").css ("display", "none"); 
			$(".image-pic9").css("display", "inline");
			} }
		if (pendingUpdate) return;
		pendingUpdate = true;

		requestAnimationFrame(() => {								// das ist eine window animation
			pendingUpdate = false;
			const layoutViewport = document.getElementById("sp");					// id ganze frame, default layoutViewport

			// Since the bar is position: fixed we need to offset it by the
			// visual viewport's offset from the layout viewport origin.
			const viewport = event.target;
			const offsetLeft = viewport.offsetLeft;
			const offsetTop =
			  viewport.height -
			  layoutViewport.getBoundingClientRect().height +
			  viewport.offsetTop;

			// You could also do this by setting style.left and style.top if you
			// use width: 100% instead.
			//bottomBar.style.transform = `translate(${offsetLeft}px, ${offsetTop}px) scale(${1 / viewport.scale })`;
			document.getElementById('sp-inf-txt').innerHTML = "requestAnimationFrame+" + Date();
			});
	}		 
		 
  $("#phide").click(function(){ 
	 $(this).hide('slow'); 
	 //$("ajax_result").hide();	
	 //$(this).text('empty');
	 document.getElementById('ajax_result').innerHTML = "empty";

		});
	
	$("#sp-cmd-item0").click(function(){ 
		document.getElementById('sp-inf-txt').innerHTML = "empty image picture 0";
		if (pic) { 
			$("#sp-pic-img-p0").css ("display", "none");
			$("#sp-pic-img-p1").css ("display", "none");
			$("#sp-pic-img-p2").css ("display", "none");
			$("#sp-pic-img-p3").css ("display", "none");
			$("#sp-pic-img-full").css ("display", "inline"); 
			pic=false; }
		else {
		$("#sp-pic-img-full").css ("display", "none");
		$("#sp-pic-img-p0").css ("display", "inline"); 
		$("#sp-pic-img-p1").css ("display", "inline");
		$("#sp-pic-img-p2").css ("display", "inline");
		$("#sp-pic-img-p3").css ("display", "inline"); 
		pic=true; }
		
		});
	
	// Button fuenf	
	$("#sp-cmd-item4").click(function(){ 
		document.getElementById('sp-inf-txt').innerHTML = "empty image picture 0";
		if (item==0) $(".image-item-p0").css ("display", "none");
		if (item==1) $(".image-item-p1").css ("display", "none");
		if (item==2) $(".image-item-p2").css ("display", "none");
		if (item==3) $(".image-item-p3").css ("display", "none");
		if (item==4) $(".image-item-p0").css ("display", "inline");
		if (item==5) $(".image-item-p1").css ("display", "inline");
		if (item==6) $(".image-item-p2").css ("display", "inline");
		if (item==7) $(".image-item-p3").css ("display", "inline");
		
		item++;
		if (item==8) item=0;
		});

	$("#sp-cmd-item1").click(function(){ 
		document.getElementById('sp-inf-txt').innerHTML = "resize image picture single to bigger";
		$(".image-pic0").css ("max-height", "800px");
		});

	$("#sp-cmd-item2").click(function(){ 
		document.getElementById('sp-inf-txt').innerHTML = "change picture size";
		$("#sp-pic-img-full").css ("max-height", "800px");
		});
	
	// Button vier, orf on/off
	$("#sp-cmd-item3").click(function(){ 			// change class
		document.getElementById('sp-inf-txt').innerHTML = "empty image picture frame to one";
		if (pic1==false) { 
			$("#sp-pic-grid").addClass("container-picture").removeClass("container-picture2"); 
			$("#sp-pic-grid-right").css ("display", "none");
			$(".image-pic9").css("display", "inline");
			$(".image-pic4").css("display", "none");	
			$(".image-pic0").css("display", "none");
			pic1=true; }
		else { 			// Orf ein
			$("#sp-pic-grid").addClass("container-picture2").removeClass("container-picture"); 
			$("#sp-pic-grid-right").css ("display", "inline");
			$("#sp-pic-img-p4-4").css ("display", "inline"); 
			$("#sp-pic-img-p5-4").css ("display", "inline"); 
			$(".image-pic9").css("display", "none");
			$(".image-pic4").css("display", "inline");	
			$(".image-pic0").css("display", "none");
			pic1=false; }
		});
		
	$("#sp-pic-img-p0").click(function(){ 
		document.getElementById('sp-inf-txt').innerHTML = "empty image picture 0";
		if (pic0) { $("#sp-pic-img-p0").css ("display", "none"); pic0=false; }
		else { $("#sp-pic-img-p0").css ("display", "inline"); pic0=true; }
		});

	$("#sp-pic-img-p3").click(function(){ 
		document.getElementById('sp-inf-txt').innerHTML = "empty image picture 3";

		});

	$("#sp-pic-img-p2").click(function(){ 
		document.getElementById('sp-inf-txt').innerHTML = "empty image picture";
		if (pic0===false) { $("#sp-pic-img-p2").css ("display", "inline"); pic0=true; }
		});

	$("#sp-inf-txt").click(function(){ 
		//$(this).hide('slow'); 
		var screenWidth = screen.width;
		var windowHeight = window.innerHeight;
		let zoom = (( window.outerWidth - 10 ) / window.innerWidth) * 100;
		$(this).text("Viewport: " + $(window).width() + " x " + $(window).height() + "Pixel   " + 
		"Screen: " + screen.width + " x " + screen.height + "   Window : " + window.innerWidth + " x " + window.innerHeight + "  Zoom : " + zoom ); 
		//	enterFullscreen(document.documentElement);				// nur der Frame geht Fullpage

		});

	// iframe auf fullscreen

	function enterFullscreen(element) {
	  if(element.requestFullscreen) {
		element.requestFullscreen();
	  } else if(element.msRequestFullscreen) {      // for IE11 (remove June 15, 2022)
		element.msRequestFullscreen();
	  } else if(element.webkitRequestFullscreen) {  // iOS Safari
		element.webkitRequestFullscreen();
	  }
	}

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
     document.getElementById('demotext').innerHTML = "demotext : "+$write.text();		// demotext wird mit dem Wert von Write überschrieben
	 //$("#write").text("jetzt aber");
	 $write.text("jetzt aber");				//write zeigt auf das feld mit ID write, siehe def oben
	 //document.write("Hallo");   Wuerde das ganze fenster überschreiben
	 //$(this).hide(); 
	 });

		
  $("#amis_send_simple_ajax").click(function(){   // Ajax Request
	 document.getElementById('ajax_result').innerHTML = "empty";
	 //$.post('/user/Amis/AmisReceiver.php',  {    name: "Donald Duck",    city: "Duckburg"  },
	 $.get('/user/Amis/AmisReceiver.php',
		function(data, status){
			//alert("Data: " + data + "\nStatus: " + status);
			//$("ajax_result").show();
			var obj = JSON.parse(data);					// immer json formatiert, immer ein Objekt mit Id
			document.getElementById('ajax_result').innerHTML = data;
			}).fail(function(errorobj, textstatus, error) {
				// Bei Fehler
				document.getElementById('ajax_fail').innerHTML = "Fehler Ajax : "+error+" "+textstatus;		
			});
	     });

  $("#amis_send_full_ajax").click(function(){   // Ajax Request
	document.getElementById('ajax_result').innerHTML = "empty";
	$.ajax({
		url : '/user/Amis/AmisReceiver.php',
		type : 'post',			// get oder post gemeinsam mit data
		dataType: "json",
		data: "command=sort&id="+this.id,			
		}).done(function (data) {
				ajaxresponse(data);
				// Auswerteprozess und Darstellung für this
		}).fail(function(errorobj, textstatus, error) {
				document.getElementById('ajax_fail').innerHTML = "Fehler Ajax : "+error+" "+textstatus;
		}).always(function(data) {
				return data;
		});
  });
		 
  $("#guthbnFieldAjaxFirst").click(function(){ 					// Ajax Request
  	 var $write = $('#write');
     var data = ajaxrequest('/user/Amis/AmisReceiver.php', 'get', 'command:ARD');			// die ersten beiden Parameter sind exakt festgelegt		
	 $("ajax_result").hide();
	 $write.text("jetzt aber Ajaxrequest abgesetzt."+data+".");
	 });
		

	const tableSort = function(tab) {
	
		// Kopfzeile vorbereiten
		const initTableHead = function(sp) { 
			const sortbutton = document.createElement("button");
			sortbutton.type = "button";
			sortbutton.className = "sortbutton unsorted";
			sortbutton.addEventListener("click", function(e) { if(e.detail <= 1) tsort(sp); }, false);
			sortbutton.innerHTML = "<span class='visually-hidden'>" + sort_hint.asc + "</span>" 
													 + "<span class='visually-hidden'>" + sort_hint.desc + "</span>" 
													 + tabletitel[sp].innerHTML + sortsymbol;
			tabletitel[sp].innerHTML = "<span class='visually-hidden'>" + tabletitel[sp].innerHTML + "</span>";
			tabletitel[sp].appendChild(sortbutton);
			sortbuttons[sp] = sortbutton;
			tabletitel[sp].abbr = "";
		} // initTableHead
		
		// Tabellenfelder auslesen und auf Zahl oder String prüfen
		const getData = function (ele, col) { 
			const val = ele.textContent;
			// Tausendertrenner entfernen, und Komma durch Punkt ersetzen
			const tval = val.replace(/\s/g,"").replace(",", ".");
			if (!isNaN(tval) && tval.search(/[0-9]/) != -1) return tval; // Zahl
			sorttype[col] = "s"; // String
			return val;
		} // getData	

		// Vergleichsfunktion für Strings
		const vglFkt_s = function(a, b) { 
			return a[sorted].localeCompare(b[sorted],"de");
		} // vglFkt_s

		// Vergleichsfunktion für Zahlen
		const vglFkt_n = function(a, b) { 
			return a[sorted] - b[sorted];
		} // vglFkt_n

		// Der Sortierer
		const tsort = function(sp) { 
			if (sp == sorted) { // Tabelle ist schon nach dieser Spalte sortiert, also nur Reihenfolge umdrehen
				arr.reverse();
				sortbuttons[sp].classList.toggle("sortedasc"); 
				sortbuttons[sp].classList.toggle("sorteddesc"); 
				tabletitel[sp].abbr = (tabletitel[sp].abbr==sort_info.asc)?sort_info.desc:sort_info.asc;
			}
			else { // Sortieren 
				if (sorted > -1) {
					sortbuttons[sorted].classList.remove("sortedasc");
					sortbuttons[sorted].classList.remove("sorteddesc");
					sortbuttons[sorted].classList.add("unsorted");
					tabletitel[sorted].abbr = "";
				}
				sortbuttons[sp].classList.remove("unsorted");
				sortbuttons[sp].classList.add("sortedasc");
				sorted = sp;
				tabletitel[sp].abbr = sort_info.asc;
				if(sorttype[sp] == "n") arr.sort(vglFkt_n);
				else arr.sort(vglFkt_s);
			}	
			for (let r = 0; r < nrows; r++) tbdy.appendChild(arr[r][ncols]); // Sortierte Daten zurückschreiben
		} // tsort

		// Tabellenelemente ermitteln
		const thead = tab.tHead;
		let tr_in_thead, tabletitel;
		if (thead) tr_in_thead = thead.rows;
		if (tr_in_thead) tabletitel = tr_in_thead[0].cells;
		if ( !(tabletitel && tabletitel.length > 0) ) { 
			console.error("Tabelle hat keinen Kopf und/oder keine Kopfzellen."); 
			return; 
		}
		let tbdy = tab.tBodies;
		if ( !(tbdy) ) { 
			console.error("Tabelle hat keinen tbody.");
			return; 
		}
		tbdy = tbdy[0];
		const tr = tbdy.rows;
		if ( !(tr && tr.length > 0) ) { 
			console.error("Tabelle hat keine Zeilen im tbody."); 
			return; 
		}
		const nrows = tr.length,
				ncols = tr[0].cells.length;

		// Einige Variablen
		let arr = [],
				sorted = -1,
				sortbuttons = [],
				sorttype = [];

		// Hinweistexte
		const sort_info = {
			asc: "Tabelle ist aufsteigend nach dieser Spalte sortiert",
			desc: "Tabelle ist absteigend nach dieser Spalte sortiert",
		};
		const sort_hint = {
			asc: "Sortiere aufsteigend nach ",
			desc: "Sortiere absteigend nach ",
		};
		
		// Sortiersymbol
		const sortsymbol = '<svg role="img" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="-5 -5 190 110"><path  d="M0 0 L50 100 L100 0 Z" style="stroke:currentColor;fill:transparent;stroke-width:10;"/><path d="M80 100 L180 100 L130 0 Z" style="stroke:currentColor;fill:transparent;stroke-width:10;"/></svg>';

		// Stylesheets für Button im TH
		if(!document.getElementById("Stylesheet_tableSort")) {
			const sortbuttonStyle = document.createElement('style'); 
			const stylestring = '.sortbutton { width: 100%; height: 100%; border: none; background-color: transparent; font: inherit; color: inherit; text-align: inherit; padding: 0; cursor: pointer; } '	
			 + '.sortierbar thead th span.visually-hidden { position: absolute !important; clip: rect(1px, 1px, 1px, 1px) !important; padding: 0 !important; border: 0 !important; height: 1px !important; width: 1px !important; overflow: hidden !important; white-space: nowrap !important; } '
			 + '.sortierbar caption span { font-weight: normal; font-size: .8em; } '
			 + '.sortbutton svg { margin-left: .2em; height: .7em; } '
			 + '.sortbutton.sortedasc svg path:last-of-type { fill: currentColor !important; } '
			 + '.sortbutton.sorteddesc svg path:first-of-type { fill: currentColor!important; } '
			 + '.sortbutton.sortedasc > span.visually-hidden:first-of-type { display: none; } '
			 + '.sortbutton.sorteddesc > span.visually-hidden:last-of-type { display: none; } '
			 + '.sortbutton.unsorted > span.visually-hidden:last-of-type { display: none; } ';
			sortbuttonStyle.innerText = stylestring;
			sortbuttonStyle.id = "Stylesheet_tableSort";
			document.head.appendChild(sortbuttonStyle);
		}

		// Kopfzeile vorbereiten
		for (let i = 0; i < tabletitel.length; i++) initTableHead(i);

		// Array mit Info, wie Spalte zu sortieren ist, vorbelegen
		for (let c = 0; c < ncols; c++) sorttype[c] = "n";

		// Tabelleninhalt in ein Array kopieren
		for (let r = 0; r < nrows; r++) {
			arr[r] = [];
			for (let c = 0, cc; c < ncols; c++) {
				cc = getData(tr[r].cells[c],c);
				arr[r][c] = cc;
				// tr[r].cells[c].innerHTML += "<br>"+cc+"<br>"+sorttype[c]; // zum Debuggen
			}
			arr[r][ncols] = tr[r];
		}

		// Tabelle die Klasse "is_sortable" geben
		tab.classList.add("is_sortable");

		// An caption Hinweis anhängen
		const caption = tab.caption;
		if(caption) caption.innerHTML += "<br><span>Ein Klick auf die Spaltenüberschrift sortiert die Tabelle.</span>";

	} // tableSort

	// Alle Tabellen suchen, die sortiert werden sollen, und den Tabellensortierer starten.
	const initTableSort = function() { 
		//alert ("Hello");
		const sort_Table = document.querySelectorAll("table.sortierbar");
		for (let i = 0; i < sort_Table.length; i++) new tableSort(sort_Table[i]);
	} // initTable

	if (window.addEventListener) window.addEventListener("DOMContentLoaded", initTableSort, false);

//Ajax aufrufen
	function ajaxrequest(action, method, data)
		{
			// Der eigentliche AJAX Aufruf
			$.ajax({
				url : action,
				type : method,			// get oder post
				//contentType: "application/json; charset=utf-8",
				//contentType: "application/x-www-form-urlencoded; charset=UTF-8",
				dataType: "json",
				//data : data,
				error: function(xhr){
				document.getElementById('ajax_fail').innerHTML = "An error occured: " + xhr.status + " " + xhr.statusText;
				//alert("An error occured: " + xhr.status + " " + xhr.statusText);			// error 200, wrong content type
				//dataType: "xml",
				},
			}).done(function (data) {
				// Bei Erfolg
					//alert("Erfolgreich:\n XML Response:\n" + data);
						 document.getElementById('ajax_result').innerHTML = "Ergebnis empfangen: "+data;
					//Daten auswerten
					// Antwort des Servers -> als XML-Dokument
					//e2response(data);	
					//return data;
			}).fail(function(errorobj, textstatus, error) {
				// Bei Fehler
				alert("Fehler Ajax : "+error+" "+textstatus);
				
			}).always(function(data) {
				// Immer
				 //alert("Beendet!");
				// Funktionen bei Beenden
				//cue the page loader
				//$.mobile.loading( 'hide' );
					//e2response(data);	
					return data;
			});
		};
		
	function ajaxresponse(data)
		{
		//const response_obj = JSON.parse(data.response);					// const, static object, immer json formatiert, immer ein Objekt mit Id
		const data_str = JSON.stringify(data.response);			// Übergabe bereits als Object, response ist ein php kodiertes json
		const person = {
			prename: "wolfgang",
		    surname: "joebstl" 
			}
		const header_str = JSON.stringify(data.response.Header);
		const person_str = JSON.stringify(person);
		//alert ("Data ID is "+data.id);
		document.getElementById('ajax_result').innerHTML = "Ergebnis empfangen: "+data_str;
		document.getElementById('ajax_id').innerHTML = data.id;
		
		// response row header
		var row_header="";
		const header_keys = [];
		//data.response.Header.forEach(function(currentValue){row_header += "<th>"+currentValue+"</th>"});			// list of entries
		for (const [key, value] of Object.entries(data.response.Header)) {											// list ov value pairs
			row_header += "<th>"+value.Column+"</th>";
			header_keys.push(value.Key);
			};
		row_header = "<tr><td></td>"+row_header+"</tr>";
		$("#amis_table_csv_head").append(row_header);

		// response table content
		//var row = "<tr><td>"+person.prename+"</td><td>"+header_str+"</td></tr>";
		var row;
		
		for (const [key, value] of Object.entries(data.response.Table)) {			// Zeilen, key ist die Zeilennummer
			//console.log(`${key}: ${value}`);
			row="";
			// eine Zeile schreiben
			//for (const [subkey, subvalue] of Object.entries(value)) {
			for (let result of header_keys) {										// iterable array
				for (const [subkey, subvalue] of Object.entries(value)) {
					//row += "<td>"+subkey+subvalue+"</td>";
					if (result == subkey) row += "<td>"+subvalue+"</td>";									// subkeys nicht eingeordnet
					}
				//row += "<td>"+result+"</td>";	
			}
			/*header_keys.foreach(function(header_key){								// object ??
				/*for (const [subkey, subvalue] of Object.entries(value)) {
					//row += "<td>"+subkey+subvalue+"</td>";
					if (header_key == subkey) row += "<td>"+subvalue+"</td>";									// subkeys nicht eingeordnet
					}
				//row += "<td>"+header_key+"</td>"; 
				});
			//row += "<td>"+"Ergebnis "+header_keys+"</td>";	*/
			row = "<tr><td>"+key+"</td>"+row+"</tr>";
			$("#amis_table_csv_body").append(row);					// Zeile anfügen
			};
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
	
			 initTableSort();					// sobald DOM geladen ist wird die Tablelle sortierbar aufgebaut
			 
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
			



