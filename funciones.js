var rta; // Array con todos los registros traido del PHP, a ser procesados por 'generarTexto()'
var puntos = []; // Array con todos los registros, ya procesados que se va a enviar al HTML antes de ejecutar 'generarTexto()'

// Funciones de conexion de AJAX y PHP

function nuevoAjax(){ 
	/* Crea el objeto AJAX. Esta funcion es generica para cualquier utilidad de este tipo, por
	lo que se puede copiar tal como esta aqui */
	var xmlhttp=false; 
	try 
	{ 
		// Creacion del objeto AJAX para navegadores no IE
		xmlhttp=new ActiveXObject("Msxml2.XMLHTTP"); 
	}
	catch(e)
	{ 
		try
		{ 
			// Creacion del objet AJAX para IE 
			xmlhttp=new ActiveXObject("Microsoft.XMLHTTP"); 
		} 
		catch(E) { xmlhttp=false; }
	}
	if (!xmlhttp && typeof XMLHttpRequest!='Su navegador no soporta AJAX') { xmlhttp=new XMLHttpRequest(); } 

	return xmlhttp; 
}

function actualizarDatos(r,t){
	// Ejemplo de lo que devolveria la consulta php
	rta = "4.6492+-74.0628+-34.6284+-58.4859+190.25.71.212+Colombia+190.189.114.173+Argentina+17(UDP)?1100?1200?12300?13000@98(NCAP)?1500	*"+
	"51.2993+9.491+-34.6284+-58.4859+47.73.0.129+Alemania+190.189.114.173+Argentina+17(UDP)?1700?2200?30?121@SMP(400)?500?600*"+
	"48.8582+2.3387+-34.6284+-58.4859+91.121.99.93+Francia+190.189.114.173+Argentina+17(UDP)?1100?1130*"+
	"-34.6284+-58.4859+51.5092+-0.0955+190.189.114.173+Argentina+77.234.43.34+Inglaterra+17(UDP)?900?930?1000@121(SMP)?1700?1800?1900*"+
	"-34.6284+-58.4859+39.9289+116.3883+190.189.114.173+Argentina+220.181.108.159+China+17(UDP)?2100?2130?2200?2300?2330*"+
	"-34.6284+-58.4859+35.6427+139.7677+190.189.114.173+Argentina+1.75.214.198+Japon+17(UDP)?300@131(PIPE)?210?220?230?240@56(TLSP)?2200*"+
	"-34.6284+-58.4859+57.6198+39.8554+190.189.114.173+Argentina+78.107.156.105+Rusia+17(UDP)?1500?1600";
	/* Creo el objeto AJAX
	var ajax=nuevoAjax();
	var vars = 'texto='+r+'-'+t;
	// Abro la conexión, envío cabeceras correspondientes al uso de POST y envío los datos con el método send del objeto AJAX
	ajax.open("POST", "consultas.php", true);
	ajax.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	ajax.onreadystatechange=function()
	{
		if (ajax.readyState==4)// && ajax.status == 200)
		{
			// Respuesta recibida. Coloco el texto plano en la variable Respuesta
			rta = ajax.responseText;
		}
	}
	ajax.send(vars);*/
}

// Funciones logicas HTML

function superposicion_puntos(){ // Dentro de la funcion voy a llamar "repetidos" a aquellos registros que tengas misma Lat y Long de Origen y Destino
	/* Cada fila con formato: Lat_Ori + Lon_Ori + Lat_Dest + Lon_Dest + Ip_Ori + Pais_Ori + Ip_Dest + Pais_Dest + Puerto(Protocolo)?Hora?Hora?...@Puerto(Protocolo)?Hora?Hora?...@...
   		Notar como esta separada cada variable "+", los puertos estan separados por "@" y cada puerto tiene la cantidad de veces (Por hora) que se repiten separados por "?"
   		El texto es uno solo y todas las filas estan unidas por el caracter "*"
	*/
	var lineas = rta.split("*");
	var indices = [];
	var coords = [];
	
	for(var j=0;j<lineas.length;j++){
		var columnas = lineas[j].split("+");
		coords.push(columnas[0]+columnas[1]+columnas[2]+columnas[3]) // LatOri + LonOri + LatDest + LonDest
	}
	// Una vez creada la lista coords, recorro cada elemento y lo comparo a ver si existen duplicados en el mismo
	for(var j=0;j<lineas.length;j++){ // Mismo indice q coords, con delete no pierdo el indice 
		elem_actual = coords[j];
		delete coords[j]; // Lo saco de la lista asi no se busca a si mismo como repetido. BORRO el objeto pero no lo saco de la lista, queda como undefined, para manterer el indice, a diferencia de splice(j,1);
		if(coords.indexOf(elem_actual) == -1 && elem_actual != undefined) indices.push(j); // Guardo el indice de la lista lineas, el cual agrego sin modificar ya que no se encontraba repetido el punto
 		else if(coords.indexOf(elem_actual) > -1){
 			var repetido = [];
 			repetido.push(j); // Primer elemento de la lista, añado el indice de la lista lineas, que se va a volver a repetir n veces en adelante, y las posiciones donde se repite se agregaran a esta lista.
 			var posicion_repetido = coords.indexOf(elem_actual); // Me tiene que dar si o si la posicion del primer repetido sino no entraria al else
 			repetido.push(posicion_repetido);
 			delete coords[posicion_repetido]; // Una vez agregado a la lista repetido lo saco. Saco el objeto pero mantengo el indice
 			while(coords.indexOf(elem_actual) != -1){ // Vuelvo a realizar el proceso hasta que no haya mas repetidos
 				posicion_repetido = coords.indexOf(elem_actual);
 				repetido.push(posicion_repetido); // Lo agrego a la lista
 				delete coords[posicion_repetido]; // Lo saco de la lista
 			}
 			var indices_repetidos = repetido.join('-');
 			indices.push(indices_repetidos);
 		}
	}
	return indices;
}	

function generarTexto(){ // Desgloza el array traido del PHP, procesa los datos y genera un nueva array unicamente con las coordenadas y el texto (HTML) respectivo, para ser mandado al HTML
    puntos.splice(0,puntos.length); // Limpio el Array que va a ser mandado al HTML
    indices = superposicion_puntos(); // Recordar que dentro de este array guardo LAS POSICIONES 0,1,2, es decir funciona como un i=0,i<tal,i++, por lo que no tengo que usar for
    								  // Ademas de que si existe alguna superposicion de puntos me dice que en la posicion (ej) 11 se superpone el registro 16,17,etc... con formato 11,16,17
    var lineas = rta.split("*");
    for(var z = 0; z < indices.length; z++){
    	if(typeof indices[z] == "number"){ // Cuando no existen repetidos en un mismo punto
	        var columnas = lineas[indices[z]].split("+");
	        var resultado = [];
	        var texto;
	        if(columnas[7] == "Argentina"){ // Cuando es punto rojo, osea Pais Destino Argentina
				var texto_rojo = 	'<h3 class="popover-title">'+columnas[5]+'</h3>'+
									 	'<dl>'+
									 	'<dt class="accordion"> Ip Origen: '+columnas[4]+' ('+columnas[5]+')'+'</dt>'+
									 	'<dd class="contenido"> Ip Destino: '+columnas[6]+' ('+columnas[7]+')'+
									 	'<br> Puerto de Destino (Protocolo):';
				var aux_horas = [];
				var aux2_rojo = columnas[8].split("@");
				for (var p = 0; p < aux2_rojo.length; p++){
					var split_rojo = aux2_rojo[p].split("?"); // split_rojo[0] = Nro Puerto (Prot), split_rojo[1][2][...] las horas
					cantidad_horas = split_rojo.length - 1;
					var texto_aux_rojo =  '<br>'+split_rojo[0]+', '+cantidad_horas+' vez/veces: ';
					aux_horas.push(texto_aux_rojo);
					for(var t=1; t<split_rojo.length; t++){
						var texto_aux_rojo_2 = '<li class = "li_popup" style="margin-left:2em;"> Hora: '+split_rojo[t]+'</li>';
						aux_horas.push(texto_aux_rojo_2);
					}
				}
				var join_de_lista_rojo = aux_horas.join(''); 
				texto = texto_rojo+join_de_lista_rojo+'</dd></dl>';
			}
			else if(columnas[5] == "Argentina"){ // Cuando es punto verde, osea Pais Origen Argentina
				var texto_rojo = 	'<h3 class="popover-title">'+columnas[7]+'</h3>'+
									 	'<dl>'+
									 	'<dt class="accordion"> Ip Destino: '+columnas[6]+' ('+columnas[7]+')'+'</dt>'+
									 	'<dd class="contenido"> Ip Origen: '+columnas[4]+' ('+columnas[5]+')'+
									 	'<br> Puerto de Destino (Protocolo):';
				var aux_horas = [];
				var aux2_rojo = columnas[8].split("@");
				for (var p = 0; p < aux2_rojo.length; p++){
					var split_rojo = aux2_rojo[p].split("?"); // split_rojo[0] = Nro Puerto (Prot), split_rojo[1][2][...] las horas
					cantidad_horas = split_rojo.length - 1;
					var texto_aux_rojo =  '<br>'+split_rojo[0]+', '+cantidad_horas+' vez/veces: ';
					aux_horas.push(texto_aux_rojo);
					for(var t=1; t<split_rojo.length; t++){
						var texto_aux_rojo_2 = '<li class = "li_popup" style="margin-left:2em;"> Hora: '+split_rojo[t]+'</li>';
						aux_horas.push(texto_aux_rojo_2);
					}
				}
				var join_de_lista_rojo = aux_horas.join(''); 
				texto = texto_rojo+join_de_lista_rojo+'</dd></dl>';
			}
			resultado.push(columnas[0]); // LatOri
			resultado.push(columnas[1]); // LonOri
			resultado.push(columnas[2]); // LatDes
			resultado.push(columnas[3]); // LonDes
			resultado.push(texto);
			var fila_lista = resultado.join('+');
			puntos.push(fila_lista);
    	}
    	else{ // Cuando existen repetidos en un mismo punto
    		var posiciones_repetidos = indices[z].split("-");
    		var columnas = lineas[parseInt(posiciones_repetidos[0])].split("+");
    		var resultado = [];
	        var texto;
    		if(columnas[7] == "Argentina"){ // Cuando es punto rojo, osea Pais Destino Argentina
    			var titulo = '<h3 class="popover-title">'+columnas[5]+'</h3><dl>';
    			var texto_aux = [];
    			for(var i = 0; i<posiciones_repetidos.length;i++){
    				columnas = lineas[posiciones_repetidos[i]].split("+");
    				var texto_rojo = 	 	'<dt class="accordion"> Ip Origen: '+columnas[4]+' ('+columnas[5]+')'+'</dt>'+
										 	'<dd class="contenido"> Ip Destino: '+columnas[6]+' ('+columnas[7]+')'+
										 	'<br> Puerto de Destino (Protocolo):';
					var aux_horas = [];
					var aux2_rojo = columnas[8].split("@");
					for (var p = 0; p < aux2_rojo.length; p++){
						var split_rojo = aux2_rojo[p].split("?"); // split_rojo[0] = Nro Puerto (Prot), split_rojo[1][2][...] las horas
						cantidad_horas = split_rojo.length - 1;
						var texto_aux_rojo =  '<br>'+split_rojo[0]+', '+cantidad_horas+' vez/veces: ';
						aux_horas.push(texto_aux_rojo);
						for(var t=1; t<split_rojo.length; t++){
							var texto_aux_rojo_2 = '<li class = "li_popup" style="margin-left:2em;"> Hora: '+split_rojo[t]+'</li>';
							aux_horas.push(texto_aux_rojo_2);
						}
					}
					var join_de_lista_rojo = aux_horas.join(''); 
					var temp = texto_rojo+join_de_lista_rojo+'</dd>';
					texto_aux.push(temp);
    			}
    			texto_aux = texto_aux.join('');
    			texto = titulo+texto_aux+'</dl>';
    		}
    		else if(columnas[5] == "Argentina"){ // Cuando es punto verde, osea Pais Origen Argentina
    			var titulo = '<h3 class="popover-title">'+columnas[7]+'</h3><dl>';
    			var texto_aux = [];
    			for(var i = 0; i<posiciones_repetidos.length;i++){ 
    				columnas = lineas[posiciones_repetidos[i]].split("+");
    				var texto_rojo = 	 	'<dt class="accordion"> Ip Destino: '+columnas[6]+' ('+columnas[7]+')'+'</dt>'+
										 	'<dd class="contenido"> Ip Origen: '+columnas[4]+' ('+columnas[5]+')'+
										 	'<br> Puerto de Destino (Protocolo):';
					var aux_horas = [];
					var aux2_rojo = columnas[8].split("@");
					for (var p = 0; p < aux2_rojo.length; p++){
						var split_rojo = aux2_rojo[p].split("?"); // split_rojo[0] = Nro Puerto (Prot), split_rojo[1][2][...] las horas
						cantidad_horas = split_rojo.length - 1;
						var texto_aux_rojo =  '<br>'+split_rojo[0]+', '+cantidad_horas+' vez/veces:';
						aux_horas.push(texto_aux_rojo);
						for(var t=1; t<split_rojo.length; t++){
							var texto_aux_rojo_2 = '<li class = "li_popup" style="margin-left:2em;"> Hora: '+split_rojo[t]+'</li>';
							aux_horas.push(texto_aux_rojo_2);
						}
					}
					var join_de_lista_rojo = aux_horas.join(''); 
					var temp = texto_rojo+join_de_lista_rojo+'</dd>';
					texto_aux.push(temp);
    			}
    			texto_aux = texto_aux.join('');
    			texto = titulo+texto_aux+'</dl>';
    		}
    		resultado.push(columnas[0]); // LatOri
			resultado.push(columnas[1]); // LonOri
			resultado.push(columnas[2]); // LatDes
			resultado.push(columnas[3]); // LonDes
			resultado.push(texto);
			var fila_lista = resultado.join('+');
			puntos.push(fila_lista);
    	}
    }
    return puntos;
}

/* 
function pause(milliseconds) {
	var dt = new Date();
	while ((new Date()) - dt <= milliseconds) { /* Do nothing  }
}

Funcion vieja no la borro por las dudas de q necesite algo despues 
function generarTexto_traerDatos(){ 
    puntos.splice(0,puntos.length); // Limpio el Array que va a ser mandado al HTML
    var lista = rta.split("*");
    for(i=0; i < lista.length; i++){ 
        var fila = lista[i].split("+");
        var resultado = [];
        var texto;
        if(fila[7] == "Argentina"){ // Cuando es punto rojo, osea Pais Destino Argentina
			var texto_rojo = 	'<h3 class="popover-title">'+fila[5]+'</h3>'+
								 	'<dl>'+
								 	'<dt class="accordion"> Ip Origen: '+fila[4]+' ('+fila[5]+')'+'</dt>'+
								 	'<dd class="contenido"> Ip Destino: '+fila[6]+' ('+fila[7]+')'+
								 	'<br> Puerto de Destino (Protocolo):';
			var aux_horas = [];
			var aux2_rojo = fila[8].split("@");
			for (var z = 0; z < aux2_rojo.length; z++){
				var split_rojo = aux2_rojo[z].split("?"); // split_rojo[0] = Nro Puerto (Prot), split_rojo[1][2][...] las horas
				cantidad_horas = split_rojo.length - 1;
				var texto_aux_rojo =  '<li class = "accordion">'+split_rojo[0]+', '+cantidad_horas+' vez/veces hoy </li>';
				aux_horas.push(texto_aux_rojo);
				for(var t=1; t<split_rojo.length; t++){
					var texto_aux_rojo_2 = '<li class = "li_popup"> Hora: '+split_rojo[t]+'</li>';
					aux_horas.push(texto_aux_rojo_2);
				}
			}
			var join_de_lista_rojo = aux_horas.join(''); 
			texto = texto_rojo+join_de_lista_rojo+'</dd></dl>';			
		}
		else if(fila[5] == "Argentina"){ // Cuando es punto verde, osea Pais Origen Argentina){
			var texto_rojo = 	'<h3 class="popover-title">'+fila[7]+'</h3>'+
								 	'<dl>'+
								 	'<dt class="accordion"> Ip Destino: '+fila[6]+' ('+fila[7]+')'+'</dt>'+
								 	'<dd class="contenido"> Ip Origen: '+fila[4]+' ('+fila[5]+')'+
								 	'<br> Puerto de Destino (Protocolo):';
			var aux_horas = [];
			var aux2_rojo = fila[8].split("@");
			for (var z = 0; z < aux2_rojo.length; z++){
				var split_rojo = aux2_rojo[z].split("?"); // split_rojo[0] = Nro Puerto (Prot), split_rojo[1][2][...] las horas
				cantidad_horas = split_rojo.length - 1;
				var texto_aux_rojo =  '<li class = "accordion">'+split_rojo[0]+', '+cantidad_horas+' vez/veces hoy </li>';
				aux_horas.push(texto_aux_rojo);
				for(var t=1; t<split_rojo.length; t++){
					var texto_aux_rojo_2 = '<li class = "li_popup"> Hora: '+split_rojo[t]+'</li>';
					aux_horas.push(texto_aux_rojo_2);
				}
			}
			var join_de_lista_rojo = aux_horas.join(''); 
			texto = texto_rojo+join_de_lista_rojo+'</dd></dl>';	
		}
		resultado.push(fila[0]);
		resultado.push(fila[1]);
		resultado.push(fila[2]);
		resultado.push(fila[3]);
		resultado.push(texto);
		lista[i] = resultado.join('+');
		puntos.push(lista[i]);
    }
    return puntos;
}
*/