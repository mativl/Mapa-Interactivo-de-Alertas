
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
    <head>
        <title> Mapa Interactivo</title>

	    <!-- Codigo de Bootstrap -->
		<link href='https://fonts.googleapis.com/css?family=Roboto:700,300' rel='stylesheet' type='text/css'>
	        <!-- Bootstrap Core CSS -->
	    <link href="css/bootstrap.min.css" rel="stylesheet">
	    	<!-- Bootstrap full CSS -->
	    <link href="css/bootstrap.css" rel="stylesheet">
	    	<!-- Custom CSS -->
	    <link href="css/modern-business.css" rel="stylesheet">
	    	<!-- Bootstrap Core CSS -->
	    <link href="bower_components/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
		    <!-- Custom CSS -->
	    <link href="dist/css/sb-admin-2.css" rel="stylesheet">
		    <!-- Custom Fonts -->
	    <link href="bower_components/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
			<!-- Custom CSS -->
    	<link href="css/modern-business.css" rel="stylesheet">
    	<link href="css/sb-admin-2.css" rel="stylesheet">

		<!-- Links propios -->
		<link rel="stylesheet" href="http://openlayers.org/en/v3.14.2/css/ol.css" type="text/css">
		<link href='https://fonts.googleapis.com/css?family=Roboto+Condensed' rel='stylesheet' type='text/css'>

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
        <script type="text/javascript" src="http://openlayers.org/en/v3.14.2/build/ol.js" ></script>
	    <script src="https://api.mapbox.com/mapbox.js/plugins/arc.js/v0.1.0/arc.js"></script>
	    <script src="http://openlayers.org/en/v3.15.1/build/ol.js"></script>
		
	  
        <!-- Codigo Propio -->
        <script type="text/javascript" src="funciones.js"></script>
        <script type="text/javascript" src="ol-popup.js"></script>
        <link rel="stylesheet" href="style.css">

        <script type="text/javascript">
			// <![CDATA[

			var map = {};
			var layerFeatures_rojo;
			var layerFeatures_verde;
			var lineasLayer;
			var intervaloLineas;
			var intervalo_display_Punto;
			var intervalo_conexion_puntos;
			var paro_intervalo_display_puntos = false;
			var timeouts = [];
			var es_estatico = false;
			var conj_desde = [];
			var conj_hasta = [];

			var style_alerta_entrante = new ol.style.Style({
		        stroke: new ol.style.Stroke({
		          color: '#C40000',
		          width: 2
		        })
		     });

			var style_alerta_saliente = new ol.style.Style({
		        stroke: new ol.style.Stroke({
		          color: '#037A1D',
		          width: 2
		        })
		     });

			function init(){
				var dark_matter_layer = new ol.layer.Tile({
					source: new ol.source.XYZ({
	        			url: 'http://{a-z}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}.png',
	        			//attributions: [new ol.Attribution({ html: ['&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, &copy; <a href="http://cartodb.com/attributions">CartoDB</a>'] })]
	        			//wrapX: false
	      			})
	      		});

	      		map = new ol.Map({
		            target: 'map',
		            controls: ol.control.defaults({
		                attributionOptions: ({
		                    collapsible: false
		                })
		            }).extend([
		                new ol.control.ZoomSlider(),
		                /*new ol.control.ZoomToExtent({
		                    extent: [
		                        813079.7791264898, 5929220.284081122,
		                        848966.9639063801, 5936863.986909639
		                    ]
		                }),*/
		                //new ol.control.Rotate(),
		                //new ol.control.OverviewMap(),
		                new ol.control.ScaleLine(),
		                //new ol.control.FullScreen(),
		            ]),
		            // interactions and controls are seperate entities in ol3
		            // we extend the default navigation with a hover select interaction
		            interactions: ol.interaction.defaults().extend([
		                new ol.interaction.Select({
		                    condition: ol.events.condition.mouseMove
		                })
		            ]),
		            layers: [
		                dark_matter_layer
		            ],
		            view: new ol.View({
		                center: [0, 0],
		                zoom: 2,
		                projection: 'EPSG:3857',
		                minZoom: 2,
		            })
		        });

	      		var popup = new ol.Overlay.Popup();
				popup.setOffset([0, -55]);
				map.addOverlay(popup);

			    map.on('click', function(evt) {
				    var f = map.forEachFeatureAtPixel(
				        evt.pixel,
				        function(ft, layer){return ft;}
				    );
				    if (f && f.get('type') == 'click') {
				        var geometry = f.getGeometry();
				        var coord = geometry.getCoordinates();
				        
				        var content = '<p>'+f.get('desc')+'</p>';
				        
				        popup.show(coord, content);
				        var acc = document.getElementsByClassName("accordion");
						var i;

						for (i = 0; i < acc.length; i++){
						    acc[i].onclick = function(){
						        this.classList.toggle("active");
						        this.nextElementSibling.classList.toggle("show");
						  }
						}
				    } 
				    else { popup.hide(); }
				});
				iniciar_lineas();
				iniciar_conexion_puntos(1000); // Minimo 1000 (1 segundo) para que el Ajax actualize

				var radio_linea = document.getElementById('radio_lineas');
	      		radio_linea.checked = true;
			}

			function obtener_hora(cual){
				if(cual == 'Inicial'){ //Hora inicial
					var Digital = new Date();
					var hours = Digital.getHours();
					var minutes = Digital.getMinutes();
					hours = hours.toString();
					// Tengo que tener cuidado de al restarle 15 minutos no se pase 
					if(minutes > 14) minutes = minutes - 15;
					else{
						minutes = minutes - 15 + 60;
						hours = hours - 1;	
					}  
					minutes = minutes.toString();
					if(minutes.length == 1) minutes = '0'+minutes // Como es hora militar, no puedo dejarla con un digito a la variable "minuto" si es menor a 10. Si es 0, tiene que quedar 00.
					Digital = hours +':'+ minutes;
					return Digital;
				}
				if(cual == 'Final'){ // Hora Final
					var Digital = new Date();
					var hours = Digital.getHours();
					var minutes = Digital.getMinutes();
					hours = hours.toString();
					minutes = minutes.toString();
					if(minutes.length == 1) minutes = '0'+minutes
					Digital = hours +':'+ minutes;
					return Digital;
				}
			}

			// Codigo para el dibujo de Puntos
			
			function dibujarPuntos(LatOri,LonOri,LatDest,LonDest,texto){
				var sourceFeatures_rojo = new ol.source.Vector();
	    		layerFeatures_rojo = new ol.layer.Vector({source:sourceFeatures_rojo});

	    		var sourceFeatures_verde = new ol.source.Vector();
	    		layerFeatures_verde = new ol.layer.Vector({source:sourceFeatures_verde});
	    			    		
	    		for(var i=0;i<texto.length;i++){
					if(LatDest[i] == "-34.6284" && LonDest[i] == "-58.4859"){ // Dibuja los puntos rojos
						var style_atacante = [
						    new ol.style.Style({
						        image: new ol.style.Icon(({
						            scale: 0.7,
						            rotateWithView: false,
						            anchor: [0.5, 1],
						            anchorXUnits: 'fraction',
						            anchorYUnits: 'fraction',
						            opacity: 1,
						            src:'img/marker_atacante.png' //rojo
						        })),
						        zIndex: 5
						    }),
						    new ol.style.Style({
						        image: new ol.style.Circle({
						            radius: 5,
						            fill: new ol.style.Fill({
						                color: 'rgba(255,255,255,1)'
						            }),
						            stroke: new ol.style.Stroke({
						                color: 'rgba(0,0,0,1)'
						            })
						        })
						    })
						];

						var texto_rojo = texto[i];

						var lat_rojo = parseFloat(LatOri[i]);
						var lon_rojo = parseFloat(LonOri[i]);

						var punto_rojo = new ol.geom.Point(ol.proj.transform([lon_rojo,lat_rojo],'EPSG:4326', 'EPSG:3857'));
						var feature_rojo = new ol.Feature({
						    type: 'click',
						    desc: texto_rojo,
						    geometry: punto_rojo
						});
						feature_rojo.setStyle(style_atacante);
						sourceFeatures_rojo.addFeature(feature_rojo);
					}
					
					else if(LatOri[i] == "-34.6284" && LonOri[i] == "-58.4859"){ // Dibuja los puntos verdes
						var style_atacado = [
						    new ol.style.Style({
						        image: new ol.style.Icon(({
						            scale: 0.7,
						            rotateWithView: false,
						            anchor: [0.5, 1],
						            anchorXUnits: 'fraction',
						            anchorYUnits: 'fraction',
						            opacity: 1,
						            src:'img/marker_atacado.png' //verde
						        })),
						        zIndex: 5
						    }),
						    new ol.style.Style({
						        image: new ol.style.Circle({
						            radius: 5,
						            fill: new ol.style.Fill({
						                color: 'rgba(255,255,255,1)'
						            }),
						            stroke: new ol.style.Stroke({
						                color: 'rgba(0,0,0,1)'
						            })
						        })
						    })
						];
						
						var texto_verde = texto[i];
									
						var lat_verde = parseFloat(LatDest[i]);
						var lon_verde = parseFloat(LonDest[i]);
						
						var punto_verde = new ol.geom.Point(ol.proj.transform([lon_verde,lat_verde],'EPSG:4326', 'EPSG:3857'));
						var feature_verde = new ol.Feature({
						    type: 'click',
						    desc: texto_verde,
						    geometry: punto_verde
						});
						feature_verde.setStyle(style_atacado);
						sourceFeatures_verde.addFeature(feature_verde);
					}
				}
			}	 		
			
	 		function traer_datos(puntos_o_lineas){
				var lat_origen = [];
				var lon_origen = [];
				var lat_destino = [];
				var lon_destino = [];
				var texto = [];
				var puntos = generarTexto();
				for(var i = 0; i < puntos.length; i++){
					var fila = puntos[i].split("+"); // fila[0]=LatOri, fila[1]=LonOri, fila[2]=LatDest, fila[3]=LonDest, fila[4]=texto
					lat_origen.push(fila[0]);
					lon_origen.push(fila[1]);
					lat_destino.push(fila[2]);
					lon_destino.push(fila[3]);
					texto.push(fila[4]);
				}
				if(puntos_o_lineas == 'puntos') dibujarPuntos(lat_origen,lon_origen,lat_destino,lon_destino,texto);
				if(puntos_o_lineas == 'lineas'){
					conj_desde.splice(0,conj_desde.length);
					conj_hasta.splice(0,conj_hasta.length);
					// Agrego lo necesario para dibujar Lineas
					for (var i = 0; i < lat_origen.length; i++) {
						var txt_desde = lat_origen[i]+'*'+lon_origen[i];
						conj_desde.push(txt_desde);
						var txt_hasta = lat_destino[i]+'*'+lon_destino[i];
						conj_hasta.push(txt_hasta);
					}
				}
			}


			function volver_a_mostrar_ult15min(){
				es_estatico = false;
				iniciar_conexion_puntos(1000); // Minimo 1000 (1 segundo) para que el Ajax actualize
				borrarPuntos();
				setTimeout(function(){
					seleccionarPunto('AMBOS');
					var radio_linea = document.getElementById('ambos_puntos');
	      			radio_linea.checked = true;
				},2000);
			}

			function ver_que_punto_esta_seleccionado(){
				var que_punto;
				var todosPuntos = document.getElementsByName('puntos');
				for(x = 0; x < todosPuntos.length; x++){
					if(todosPuntos[x].checked == true){
						que_punto = todosPuntos[x].value;
					}
			    }
			    return que_punto;
			}

			function iniciar_conexion_puntos(tiempo){
	 			var hi = obtener_hora('Inicial');
	 			var hf = obtener_hora('Final');
	 			actualizarDatos(hi,hf);
	 			setTimeout(function(){
		 			traer_datos('puntos');
					intervalo_conexion_puntos = setInterval(function(){
			 			if(es_estatico == false){
			 				borrarPuntos();
			 				hi = obtener_hora('Inicial');
	 						hf = obtener_hora('Final');
			 				actualizarDatos(hi,hf);
			 				traer_datos('puntos'); // Ver si no hay delay, si existe hacer un setTimeout
			 				var radio_linea = document.getElementById('radio_lineas');
			 				if(radio_linea.checked == false){
			 					var punto = ver_que_punto_esta_seleccionado();
			 					iniciar_display_puntos(punto);
			 				}
			 			}
		 			},180000); // 3 Min (Milisegundos)
	 			},tiempo); // ACA ESTA EL DELAY DE LOS PRIMEROS SEGUNDOS, AUNQUE LO HAGA EN SEGUNDO PLANO COMO QUE LA QUEDA EN UN MOMENTO
	 		}

			function iniciar_display_puntos(que){
				borrarPuntos();
				seleccionarPunto(que);
				clearInterval(intervalo_display_Punto);
				intervalo_display_Punto = setInterval(function(){
					borrarPuntos();
					seleccionarPunto(que);
				},180000); // 3 Min (Milisegundos)
	 		}

			function borrarPuntos(){
	   			map.removeLayer(layerFeatures_rojo);
	   			map.removeLayer(layerFeatures_verde);
			}

			// Codigo para el dibujo de Lineas

	 		function iniciar_lineas(){
	      		var hi = obtener_hora('Inicial');
	 			var hf = obtener_hora('Final');
			 	actualizarDatos(hi,hf);
	      		setTimeout(function(){
		      		dibujarLineas();
		      		intervaloLineas = setInterval(function(){ actualizar_lineas();},30000); // 30 Seg (Milisegundos)
	      		},2000);	
	      	}

	 		function actualizar_lineas(){
		        map.removeLayer(lineasLayer);
		        dibujarLineas();
	      	}

	      	function dibujarLineas(){
				traer_datos('lineas');
				var flightsSource;
				var addLater = function(feature, timeout){
					timeouts.push(setTimeout(function() {
							feature.set('start', new Date().getTime());
							flightsSource.addFeature(feature);
						}, timeout));
				};

				var pointsPerMs = 0.05;
				var animateFlights = function(event){
					var vectorContext = event.vectorContext;
					var frameState = event.frameState;
					
					var features = flightsSource.getFeatures();
					for (var i = 0; i < features.length; i++){
						var feature = features[i];

						var color_linea;
						if(feature.get('alerta') == 'entrante') color_linea = style_alerta_entrante;
						if(feature.get('alerta') == 'saliente') color_linea = style_alerta_saliente;
						vectorContext.setStyle(color_linea);	

						if (!feature.get('finished')){
							// only draw the lines for which the animation has not finished yet
							var coords = feature.getGeometry().getCoordinates();
							var elapsedTime = frameState.time - feature.get('start');
							var elapsedPoints = elapsedTime * pointsPerMs;
							if (elapsedPoints >= coords.length) {
								feature.set('finished', true);
							}

							var maxIndex = Math.min(elapsedPoints, coords.length);
							var currentLine = new ol.geom.LineString(coords.slice(0, maxIndex));
							 
							// directly draw the line with the vector context
							vectorContext.drawGeometry(currentLine);
						}
					}
					// tell OL3 to continue the animation
					map.render();
				};
				      
				flightsSource = new ol.source.Vector({
					wrapX: false,
					loader: function(){
						for (var i = 0; i < conj_desde.length; i++) {
							// Veo que tipo de alerta es, si entrante o saliente asi mas adelante le defino el estilo de la linea (color). Uso la variable alerta: que_tipo_de_ataque_es
							var que_tipo_de_ataque_es;
							var desde = conj_desde[i].split("*");
							var hasta = conj_hasta[i].split("*"); 

							if(desde[0] == -34.6284 && desde[1] == -58.4859) que_tipo_de_ataque_es = 'saliente';
							else que_tipo_de_ataque_es = 'entrante';
							
							// Tengo que analizar si la diferencia entre la entre la Lat de Origen y Lat de Destino, y entre Lon Origen y Lon Destino, no sea mayor a 180 porque sino no la dibuja
							if(hasta[1]-desde[1] < 180 && hasta[0]-desde[0] < 180){
								// create an arc circle between the two locations
								var arcGenerator = new arc.GreatCircle(
								{x: desde[1], y: desde[0]},
								{x: hasta[1], y: hasta[0]});

								var arcLine = arcGenerator.Arc(100, {offset: 10});
								if (arcLine.geometries.length === 1){
									var line = new ol.geom.LineString(arcLine.geometries[0].coords);
									line.transform(ol.proj.get('EPSG:4326'), ol.proj.get('EPSG:3857'));

									var feature = new ol.Feature({
									geometry: line,
									finished: false,
									alerta: que_tipo_de_ataque_es
									});
									// add the feature with a delay so that the animation
									// for all features does not start at the same time
									addLater(feature, i * 500);
								}
							}
							else{
								if(hasta[1]-desde[1] < 180){
									var arcGenerator1 = new arc.GreatCircle(
									{x: desde[1], y: desde[0]},
									{x: hasta[1]-90, y: hasta[0]});

									var arcLine1 = arcGenerator1.Arc(100, {offset: 10});
									if (arcLine1.geometries.length === 1){
										var line1 = new ol.geom.LineString(arcLine1.geometries[0].coords);
										line1.transform(ol.proj.get('EPSG:4326'), ol.proj.get('EPSG:3857'));

										var feature1 = new ol.Feature({
										geometry: line1,
										finished: false,
										alerta: que_tipo_de_ataque_es
										});
										// add the feature with a delay so that the animation
										// for all features does not start at the same time
										//addLater(feature1, i * 1);
									}
									var arcGenerator2 = new arc.GreatCircle(
									{x: hasta[1]-90, y: desde[0]},
									{x: hasta[1], y: hasta[0]});

									var arcLine2 = arcGenerator2.Arc(100, {offset: 10});
									if (arcLine2.geometries.length === 1){
										var line2 = new ol.geom.LineString(arcLine2.geometries[0].coords);
										line2.transform(ol.proj.get('EPSG:4326'), ol.proj.get('EPSG:3857'));

										var feature2 = new ol.Feature({
										geometry: line2,
										finished: false,
										alerta: que_tipo_de_ataque_es
										});
										// add the feature with a delay so that the animation
										// for all features does not start at the same time
										//addLater(feature2, i * 1);
									}
									addLater(feature1, i+1 * 1);
									addLater(feature2, i+1 * 1900);
								}
								else if(hasta[0]-desde[0] < 180){
									var arcGenerator1 = new arc.GreatCircle(
									{x: desde[1], y: desde[0]},
									{x: hasta[1]-90, y: hasta[0]-90});

									var arcLine1 = arcGenerator1.Arc(100, {offset: 10});
									if (arcLine1.geometries.length === 1){
										var line1 = new ol.geom.LineString(arcLine1.geometries[0].coords);
										line1.transform(ol.proj.get('EPSG:4326'), ol.proj.get('EPSG:3857'));

										var feature1 = new ol.Feature({
										geometry: line1,
										finished: false,
										alerta: que_tipo_de_ataque_es
										});
										// add the feature with a delay so that the animation
										// for all features does not start at the same time
										//addLater(feature1, i * 1);
									}
										
									var arcGenerator2 = new arc.GreatCircle(
									{x: hasta[1]-90, y: hasta[0]-90},
									{x: hasta[1], y: hasta[0]});

									var arcLine2 = arcGenerator2.Arc(100, {offset: 10});
									if (arcLine2.geometries.length === 1){
										var line2 = new ol.geom.LineString(arcLine2.geometries[0].coords);
										line2.transform(ol.proj.get('EPSG:4326'), ol.proj.get('EPSG:3857'));

										var feature2 = new ol.Feature({
										geometry: line2,
										finished: false,
										alerta: que_tipo_de_ataque_es
										});
										// add the feature with a delay so that the animation
										// for all features does not start at the same time
										//addLater(feature2, i * 1);
									}
									addLater(feature1, i+1 * 1);
									addLater(feature2, i+1 * 1900);
								}
							}
						}
						map.on('postcompose', animateFlights);
					}
				});

				lineasLayer = new ol.layer.Vector({
					source: flightsSource,
					style: function(feature) {
						// if the animation is still active for a feature, do not
						// render the feature with the layer style
						if (feature.get('finished')){
							if(feature.get('alerta') == 'entrante') return style_alerta_entrante;
							if(feature.get('alerta') == 'saliente')return style_alerta_saliente;
						}
						else {
							return null;
						}
					}
				});
				map.addLayer(lineasLayer);
			}

			// Codigo para seleccionar Modos

	      	function seleccionarModo(modo){
	      		if(modo == 'Linea'){
		   			clearInterval(intervalo_display_Punto);
	      			borrarPuntos();
	      			iniciar_lineas();
	      			var allRadios = document.getElementsByName('puntos');
					for(x = 0; x < allRadios.length; x++){
						allRadios[x].checked = false;
			        }
	      		}
	      	}
	      	
	      	function seleccionarPunto(cual){
	   			for(var i=0; i<timeouts.length; i++) clearTimeout(timeouts[i]);
	   			clearInterval(intervaloLineas);
	   			map.removeLayer(lineasLayer);
	   			var radio_linea = document.getElementById('radio_lineas');
	      		radio_linea.checked = false;

				if(cual == 'ROJO'){
					borrarPuntos();
					map.addLayer(layerFeatures_rojo);
				} 
				if(cual == 'VERDE'){
					borrarPuntos();
					map.addLayer(layerFeatures_verde);
				}
				if(cual == 'AMBOS'){
					borrarPuntos();
					map.addLayer(layerFeatures_rojo);
					map.addLayer(layerFeatures_verde);
				} 
			}

			function rango_horas(){ // ya esta
				$(document).ready(function(){
			        es_estatico = true;
			        var hora_inicial = $('#hora_inicial').val();
			        hora_inicial = hora_inicial.split("");
			        hora_inicial = hora_inicial[0]+hora_inicial[1]+':'+hora_inicial[2]+hora_inicial[3];
			        var hora_final = $('#hora_final').val();
			        hora_final = hora_final.split("");
			        hora_final = hora_final[0]+hora_final[1]+':'+hora_final[2]+hora_final[3];
			        clearInterval(intervalo_conexion_puntos);
			        clearInterval(intervalo_display_Punto);
			        actualizarDatos(hora_inicial,hora_final);
		 			setTimeout(function(){
		 				borrarPuntos();
			        	traer_datos('puntos');
			        	seleccionarPunto('AMBOS');
			        	var radio_linea = document.getElementById('ambos_puntos');
	      				radio_linea.checked = true;
			        },1000);
			    });
			}

			jQuery(function ($) {
			    $('#basic-modal input.basic, #basic-modal a.basic').click(function (e) {
			        e.preventDefault();
			        $('#basic-modal-content').modal();
			    });
			});

			$(document).ready(function(){
        		$('.dropdown-toggle').dropdown()
    		});


			// ]]>
        </script> 

        <style type="text/css">
	    html, body, #map {
	        width:100%; height:100%; margin:0;
	    }
	  	</style>
  	
    </head>
    <body onload="javascript:init();">
		<!--
		<div class='super-container'><meta http-equiv="refresh" content="30" >
		
		<button type="button" onclick="ajax()">ajax</button>
		<button type="button" onclick="dibujar('default','default')">dibujarPuntos</button>
		<button type="button" onclick="lineas()">dibujar Lineas</button>-->


		<div class="mapa" id="map">
		<input class="toggle-box" id="header1" type="checkbox" >

		<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>

                <a class="navbar-brand" href="index.php" style="color: #428BCA; text-shadow: 1px 1px 1px #000, 1px 1px 1px #ccc; "> &nbsp;&nbsp;&nbsp;<i><b>Mapa Intectivo </b></i></a>

            </div>
     
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav navbar-right">
                  
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown">Alertas <b class="caret"></b></a>
                            <ul class="dropdown-menu">
                            <div class="col-lg-12">
                               <li>
                                <input type="radio" name="puntos" onchange="iniciar_display_puntos('ROJO');" value = "ROJO"/> Alertas entrantes
                            </li>
                            <li class="divider"></li>
                             <li>
                                <input type="radio" name="puntos" onchange="iniciar_display_puntos('VERDE');" value = "VERDE"/> Alertas salientes
                            </li>
                            <li class="divider"></li>
                            <li>
                               <input type="radio" id ="ambos_puntos" name="puntos" onchange="iniciar_display_puntos('AMBOS');" value = "AMBOS"/> Todas las Alertas
                            </li>
                            <li class="divider"></li>
                            <li>
                                <input type="radio" id ="radio_lineas" name="modo" onchange="seleccionarModo('Linea');" checked /> Visualizar Trayecto
                            </li>
                          
                           </div> 
      
                </ul>
                </li>   


                 <li class="dropdown">
                   <a href="#" class="dropdown-toggle" data-toggle="dropdown">Busqueda Avanzada <b class="caret"></b></a>
                            <ul class="dropdown-menu">
				        	
					        <div class="col-lg-12">
					        	<h4 class="modal-title" id="myModalLabel">Filtrado por Hora</h4>
								Establecer rango de hora de las alertas:
								<form role="form">
									<div class="form-group">
										<label for="hora_inicial">Inicio (Hora Militar HHMM):</label>
										<input type="number" min="0001" max="2359" class="form-control" id="hora_inicial" placeholder="Hora Inicial">
									</div>
												             
									<div class="form-group">
										<label for="hora_final">Final (Hora Militar HHMM):</label>
										<input type="number" min="0001" max="2359" class="form-control" id="hora_final" placeholder="Hora Final">
									</div>
												 
									<button type="button" class="btn btn-info" id="cambiar_hora" data-dismiss="modal" onclick="rango_horas()">Filtrar</button>
								</form>
								<button type="button" class="btn btn-info" id="iniciar_conexion_puntos" onclick="volver_a_mostrar_ult15min()"> Mostrar Alertas (Ultimos 15 Min)</button>
							</div>
			
				  		</ul>
				  </li>
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </nav>

</div>

	    <script type="text/javascript">
	    jQuery(function ($) {
			$('#basic-modal input.basic, #basic-modal a.basic').click(function (e){
				e.preventDefault();
		   		$('#basic-modal-content').modal();
			});
		});
	    </script>


	    <!-- Codigo de la Barra Superior -->
		<script type='text/javascript' src="js/jquery.js"></script>
	    <script type='text/javascript' src="js/bootstrap.min.js"></script>
	    	<!-- jQuery -->
	    <script src="bower_components/jquery/dist/jquery.min.js"></script>
	   		<!-- Bootstrap Core JavaScript -->
	    <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
	    	<!-- Metis Menu Plugin JavaScript -->
	    <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>
	    	<!-- Custom Theme JavaScript -->
	    <script src="dist/js/sb-admin-2.js"></script>

	</body>

</html>
