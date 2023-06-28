
			function trigger_button(action, module, info) {
				var id         = $(this).attr("id");
				var WFC10Path             = $("#WFC10Path").val();
				
				document.getElementById('demo').innerHTML = Date()+" action="+action+" module="+module+" id="+id+" Path="+WFC10Path;
				$.ajax({type: "POST",
						url: "/user/Guthabensteuerung/GuthabensteuerungReceiver.php",
						data: "id="+id+"&action="+action+"&module="+module+"&info="+info});
			}

			function trigger_button2(action, module, info) {
				var id                    = $(this).attr("id");
				var WFC10Enabled          = $("#WFC10Enabled").is(':checked');
				var WFC10TabPaneExclusive = $("#WFC10TabPaneExclusive").is(':checked');
				var WFC10Path             = $("#WFC10Path").val();
				var WFC10ID               = $("#WFC10ID").val();
				var WFC10TabPaneParent    = $("#WFC10TabPaneParent").val();
				var WFC10TabPaneItem      = $("#WFC10TabPaneItem").val();
				var WFC10TabPaneIcon      = $("#WFC10TabPaneIcon").val();
				var WFC10TabPaneName      = $("#WFC10TabPaneName").val();
				var WFC10TabPaneOrder     = $("#WFC10TabPaneOrder").val();
				var WFC10TabItem          = $("#WFC10TabItem").val();
				var WFC10TabIcon          = $("#WFC10TabIcon").val();
				var WFC10TabName          = $("#WFC10TabName").val();
				var WFC10TabOrder         = $("#WFC10TabOrder").val();
	
				var MobileEnabled         = $("#MobileEnabled").is(':checked');
				var MobilePath            = $("#MobilePath").val();
				var MobilePathIcon        = $("#MobilePathIcon").val();
				var MobilePathOrder       = $("#MobilePathOrder").val();
				var MobileName            = $("#MobileName").val();
				var MobileIcon            = $("#MobileIcon").val();
				var MobileOrder           = $("#MobileOrder").val();

				$.ajax({type: "POST",
						url: "/user/Guthabensteuerung/GuthabensteuerungReceiver.php",
						contentType:"application/x-www-form-urlencoded; charset=ISO-8859-1",
						data: "id="+encodeURIComponent(id)
						       +"&action="+encodeURIComponent(action)
						       +"&module="+encodeURIComponent(module)
						       +"&info="+encodeURIComponent(info)+
						       +"&WFC10Enabled="+encodeURIComponent(WFC10Enabled)
						       +"&WFC10TabPaneExclusive="+encodeURIComponent(WFC10TabPaneExclusive)
						       +"&WFC10Path="+encodeURIComponent(WFC10Path)
						       +"&WFC10ID="+encodeURIComponent(WFC10ID)
						       +"&WFC10TabPaneParent="+encodeURIComponent(WFC10TabPaneParent)
						       +"&WFC10TabPaneItem="+encodeURIComponent(WFC10TabPaneItem)
						       +"&WFC10TabPaneIcon="+encodeURIComponent(WFC10TabPaneIcon)
						       +"&WFC10TabPaneName="+encodeURIComponent(WFC10TabPaneName)
						       +"&WFC10TabPaneOrder="+encodeURIComponent(WFC10TabPaneOrder)
						       +"&WFC10TabItem="+encodeURIComponent(WFC10TabItem)
						       +"&WFC10TabIcon="+encodeURIComponent(WFC10TabIcon)
						       +"&WFC10TabName="+encodeURIComponent(WFC10TabName)
						       +"&WFC10TabOrder="+encodeURIComponent(WFC10TabOrder)
						       +"&MobileEnabled="+encodeURIComponent(MobileEnabled)
						       +"&MobilePath="+encodeURIComponent(MobilePath)
						       +"&MobilePathIcon="+encodeURIComponent(MobilePathIcon)
						       +"&MobilePathOrder="+encodeURIComponent(MobilePathOrder)
						       +"&MobileName="+encodeURIComponent(MobileName)
						       +"&MobileIcon="+encodeURIComponent(MobileIcon)
						       +"&MobileOrder="+encodeURIComponent(MobileOrder)
						});
						
			}



