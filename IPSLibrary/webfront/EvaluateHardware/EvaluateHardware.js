$(document).ready(function(){  
"use strict";



	



	$("#hrow-0").click(function(){
		initTableSort();
		const firstobj = document.getElementById('evhw-go');
		const firstcol = document.getElementById('hrow-0');
		firstobj.innerHTML = "sort " + firstcol.innerHTML;
		
		});

	$("#hrow-1").click(function(){
		const firstobj = document.getElementById('evhw-go');
		const firstcol = document.getElementById('hrow-1');
		firstobj.innerHTML = "sort " + firstcol.innerHTML;	
		});

	$("#hrow-2").click(function(){
		const firstobj = document.getElementById('evhw-go');
		const firstcol = document.getElementById('hrow-2');
		const sort_Table = document.querySelectorAll("table.sortierbar");
		firstobj.innerHTML = "sort " + firstcol.innerHTML+ sort_Table+ sort_Table.length;
		});
		
	$("#hrow-3").click(function(){
		checkTableSort();
		const firstobj = document.getElementById('evhw-go');
		const firstcol = document.getElementById('hrow-0');
		firstobj.innerHTML = "check " + firstcol.innerHTML;
		
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
			alert ("tsort der Sortierer ");
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
		const sort_Table = document.querySelectorAll("table.sortierbar");			// ein Array Modul List
		//alert ("Init Table Sort for in total "+sort_Table.length+" Tables");
		for (let i = 0; i < sort_Table.length; i++) new tableSort(sort_Table[i]);
	} // initTable

	const checkTableSort = function() { 
		const sort_Table = document.querySelectorAll("table.sortierbar");			// ein Array Modul List
		//alert ("Init Table Sort for in total "+sort_Table.length+" Tables");
		for (let i = 0; i < sort_Table.length; i++) 
			{
			}
	} // initTable

window.addEventListener("DOMContentLoaded", initTableSort, false);

});