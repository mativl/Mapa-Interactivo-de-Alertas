#!/usr/bin/python

# python-mysqldb

import MySQLdb, socket, struct, os, time, mysql.connector, datetime
from datetime import timedelta
from datetime import date

# Revision de Sintaxis de MySQL
def revisar_syntax(texto):
	texto = texto.replace("'",'')
	texto = texto.replace('"','')
	var = "\ "
	var = var.strip()
	texto = texto.replace(var,'')
	return texto

# Funcion para convertir IP a decimal
def ip2int(addr):                                                               
    if addr != 'None':
    	return struct.unpack("!I", socket.inet_aton(addr))[0]

# Funcion para convertir decimal a IP
def int2ip(addr):
    return socket.inet_ntoa(struct.pack("!I", addr))

# Funcion para filtrar por integer
def traducirIps(datos,resultado):
	for elem in datos:
		if 167772160 < elem < 184549375:# 10.0.0.0
			elem = 3363530122 # Integer de la IP 200.123.101.138 de Argentina
			resultado.append(elem)
		elif 2886729728 < elem < 2887778303:# 172.16.0.0   
			elem = 3363530122
			resultado.append(elem)
		elif  3232235520 < elem < 3232301055: # 192.168.0.0
			elem = 3363530122
			resultado.append(elem)
		elif 2851995648 < elem < 2852061183:# 169.254.0.0/16
			elem = 3363530122
			resultado.append(elem)
		elif elem == 0: # 0.0.0.0 
			elem = 3363530122
			resultado.append(elem)
		elif elem == 1681915904: # 100.64.0.0
			elem = 3363530122
			resultado.append(elem)
		elif elem == 2130706432: # 127.0.0.0
			elem = 3363530122
			resultado.append(elem)
		elif elem == 3221225472: # 192.0.0.0
			elem = 3363530122
			resultado.append(elem)
		elif elem == 3221225984: # 192.0.2.0
			elem = 3363530122
			resultado.append(elem)
		elif elem == 3227017984: # 192.88.99.0
			elem = 3363530122
			resultado.append(elem)
		elif elem == 3323068416: # 198.18.0.0
			elem = 3363530122
			resultado.append(elem)
		elif elem == 3325256704: # 198.51.100.0
			elem = 3363530122
			resultado.append(elem)
		elif elem == 3405803776: # 203.0.113.0
			elem = 3363530122
			resultado.append(elem)
		elif elem == 4026531840: # 240.0.0.0
			elem = 3363530122
			resultado.append(elem)
		elif elem == 4294967295: # 255.255.255.255
			elem = 3363530122
			resultado.append(elem)
		else:
			resultado.append(elem)

def obtenerLatLon(datos,lat,lon):
	for elem in datos:
		if elem is not None:
			geoIp = os.popen("geoiplookup -f /usr/share/GeoIP/GeoLiteCity.dat " + elem).read()
			geoIpSplit = geoIp.split(",")
			string1 = "Rev 1: IP Address not found" #String que devuelve cuando no haya la direccion 
			string2 = geoIpSplit[1].strip() #Posicion del Array GeoIpSplit + strip() para sacarle los espacios en blanco y comparar el len
			if len(string1) == len(string2): # Si NO encuentra la Ip a traves de GeoIP
				try:
					print 'A traves de ipinfo.io'
					geoIp2 = os.popen("curl ipinfo.io/"+elem+"/loc").read()
					if geoIp2[0] == "P" or geoIp2[0] == "u": # Si la primera letra es "P" o "u" del resultado "Please provide a valid IP address" o "undefined" que imprime ipinfo cuando no lo encuentra, temporalmente se lo resuelve reemplazando la IP por una de Arg con su Lat y Lon, no se va a guardar ya que en el filtrado se la saca debido a que su IP asociada es de Arg y la Alerta iria de Arg a Arg independientemente si es de Alerta Saliente o Entrante
						lat.append("-34.603298") # Lat de Argentina
						lon.append("-58.381599") # Lon de Argentina
						print 'Cambio por ip Arg'
					else: # Si encuentra la ip a traves de ipinfo
						geoIpSplit2 = geoIp2.split(",") 
						lat.append(geoIpSplit2[0].strip())
						lon.append(geoIpSplit2[1].strip())
				except IndexError as exception:
					lat.append("-34.603298") # Lat de Argentina
					lon.append("-58.381599") # Lon de Argentina
					print 'Cambio por ip Arg'
			else: # Si encuentra la IP a traves de GeoIP
				lat.append(geoIpSplit[6].strip())
				lon.append(geoIpSplit[7].strip())

def obtenerPaises(datos,resultado):
	for elem in datos:
		if elem is not None:
			geoIp = os.popen("geoiplookup -f /usr/share/GeoIP/GeoLiteCountry.dat " + elem).read() 
			geoIpSplit = geoIp.split(",") # Formato de salida si lo encuentra --> AR, Argentina (Cod de 2 digitos universal del Pais, mas el nombre del Pais)
			if len(geoIpSplit[0]) > 30: # Formato de salida si NO lo encuentra --> "GeoIP Country Edition: IP Address not found" con un length de 43 digitos
				resultado.append('IP Address not found')
			else:
				resultado.append(geoIpSplit[1].strip())

def revisionIPs(datos,resultado):
	ip_int = [] 
	ip_revisada = []
	for elem in datos:
		elem = elem.strip() # Saco los espacios en blanco
		ipformatoint = ip2int(elem) # Convierto las IP en formato int para poder traducir las IPs privadas a publicas
		ip_int.append(ipformatoint)

	traducirIps(ip_int,ip_revisada) # Traduzco las ips privadas y reservadas a publicas para geolocalizarlas
			
	for elem in ip_revisada:
		intformatoip = int2ip(elem) # Convierto las IPs, que estan en formato int, devuelta a formato ip
		resultado.append(intformatoip)

def syntax_registros_dia_anterior(desde,hasta):
	desde = desde - timedelta(hours=3) # Los cambio a UTC(-3), hora local, para comparar con registros mapa
	hasta = hasta - timedelta(hours=3) # Los cambio a UTC(-3), hora local, para comparar con registros mapa
	desde = desde.strftime("%H:%M:%S") # Formateo en HH:MM:SS para poder compararlos
	hasta = hasta.strftime("%H:%M:%S") # Formateo en HH:MM:SS para poder compararlos
	delete_viejos = "DELETE FROM registros_diarios WHERE hora>'%s' AND hora<'%s'" % (desde,hasta)
	return delete_viejos  

ts = time.time()
hora_ultimo_registro = datetime.datetime.fromtimestamp(ts) # Obtengo la hora actual segun el formato timestamp
hora_ultimo_registro = hora_ultimo_registro + timedelta(hours=3) # Le sumo 3 horas para comparar con los timestamp de los registros, los cuales estan configurados con UTC (0) y nosotros somos -3
hora_ultimo_registro = hora_ultimo_registro - timedelta(minutes=30) # Le resto 30 minutos, para que la consulta inicial, cuando se ejecute por primera vez el python, me traiga

mysql_conn_ossim = MySQLdb.connect(host="ip", user="user", passwd="pass", db="alienvault")

mysql_conn_registro = mysql.connector.connect(host="ip", user="user-map", passwd="pass-map", db="ciber_mapa")

while 1:

	print 'A partir de las: ',hora_ultimo_registro

	cursor_registro = mysql_conn_registro.cursor()

	cursor_ossim = mysql_conn_ossim.cursor() 
	consulta = """SELECT distinct
	inet_ntoa(conv(hex(ip_src), 16, 10)) as ip_src,
	inet_ntoa(conv(hex(ip_dst), 16, 10)) as ip_dst,
	ip_proto as ip_protocolo,
	layer4_sport as source_port,
	layer4_dport as destination_port,
	timestamp as fecha_hora

	FROM alienvault_siem.acid_event
	WHERE timestamp > '%s'
	order by timestamp desc limit 50""" % (hora_ultimo_registro)
	cursor_ossim.execute(consulta)

	# Obtencion de IPs Origen y Destino desde la Base de Datos------------

	ip_origen =[]
	ip_destino =[]
	puerto_origen =[]
	puerto_destino =[]
	protocolo =[]
	hora =[]
	
	for row in cursor_ossim.fetchall():
	    ip_origen.append(str(row[0]))
	    ip_destino.append(str(row[1]))
	    protocolo.append(str(row[2]))
	    puerto_origen.append(str(row[3]))
	    puerto_destino.append(str(row[4]))
	    hora.append(row[5])

	# Si hay registros nuevos, imprimo su timestamp y proceso los registros para guardarlos en la base de datos del mapa, sino se refresca en un minuto. Utilizo el timestamp de cada registro como ID

	if len(hora) > 0:
		hora_ultimo_registro = hora[0] # Del registro original de la base de datos
		last_index = len(hora) - 1
		hora_primer_registro = hora[last_index] # Del registro original de la base de datos
		
		# Revision IPs Origen -------------------------------------------------

		ip_origen_final = []
		revisionIPs(ip_origen,ip_origen_final)

		# Revision IPs Destino --------------------------------------------

		ip_destino_final = []
		revisionIPs(ip_destino,ip_destino_final)

		# Obtener latitudes y longitudes de las IPs de Origen ------------------

		lat_origen = []
		lon_origen = []
		obtenerLatLon(ip_origen_final,lat_origen,lon_origen)

		# Obtener latitudes y longitudes de las IPs de Destino ------------------

		lat_destino = []
		lon_destino = []
		obtenerLatLon(ip_destino_final,lat_destino,lon_destino)

		# Obtener nombre de los paises de las IPs de Origen ------------------

		paises_origen = []
		obtenerPaises(ip_origen_final,paises_origen)

		# Obtener nombre de los paises de las IPs de Destino ------------------

		paises_destino = []
		obtenerPaises(ip_destino_final,paises_destino)

		# Convierte los indices de Protocolos IP  por el nombre del protocolo

		protocoloConvert = []

		filer = open("indicesProtocolos.txt","r")
		indicesProtocolos = []
		linea=filer.readline() 
		while linea!="": 
			indicesProtocolos.append(linea) 
			linea=filer.readline()

		for elem in protocolo:
			i = 0
			while i < len(indicesProtocolos):
				listaProt = []
				listaProt = indicesProtocolos[i].split(",")
				if elem == listaProt[0]:
					protocoloConvert.append(listaProt[1].strip())
				i += 1
		filer.close()

		# Voy pisando los registros que estan dentro de la hora de las nuevas alertas. Creo una ventana de 24 horas hacia atras
		
		delete_registros = syntax_registros_dia_anterior(hora_primer_registro,hora_ultimo_registro)
		elimino = 1

		# Agregarlos a la base de datos 

		i = 0
		while i < len(ip_origen_final):
			if lat_origen[i]!=lat_destino[i] and lon_origen[i]!=lon_destino[i]: # Asi filtro las que son alertas de red interna. Arg - Arg
				if elimino == 1:
					cursor_registro.execute(delete_registros)
					mysql_conn_registro.commit()
					elimino = 0
					print 'Elimine los archivos viejos en este rango de hora'
				hora_utc_local = hora[i] - timedelta(hours=3) # Le resto 3 horas para quedar en UTC(-3), hora local Bs As
				hora_HMS = hora_utc_local.strftime("%H:%M:%S")
				paises_origen[i] = revisar_syntax(paises_origen[i])
				paises_destino[i] = revisar_syntax(paises_destino[i])
				print ip_origen_final[i],'-',paises_origen[i],'-',lat_origen[i],'-',lon_origen[i],'-',ip_destino_final[i],'-',paises_destino[i],'-',lat_destino[i],'-',lon_destino[i] 
				add_registro = ("INSERT INTO registros_diarios (hora, ip_origen, pais_origen, lat_origen, lon_origen, ip_destino, pais_destino, lat_destino, lon_destino, puerto_destino, protocolo_destino)"
					+"VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s','%s','%s','%s','%s')" % 
					(hora_HMS,ip_origen_final[i],paises_origen[i],lat_origen[i],lon_origen[i],ip_destino_final[i],paises_destino[i],lat_destino[i],lon_destino[i],puerto_destino[i],protocoloConvert[i]))
				cursor_registro.execute(add_registro)
				mysql_conn_registro.commit()
				
				#print 'Hora: ',hora_HMS,' - Ip origen: ',ip_origen_final[i],paises_origen[i],lat_origen[i],lon_origen[i],' - Ip destino: ',ip_destino_final[i],paises_destino[i],lat_destino[i],lon_destino[i],' - Puerto: ',puerto_destino[i],' - Protocolo: ',protocoloConvert[i]
			i+=1 
		
		print 'Hora del ultimo registro: ', hora_ultimo_registro

	cursor_registro.close()
	
	# Refrezcar cada minuto (60 seg) ---------------------------------------------

	time.sleep(60) # en segundos
	
# Cerrar la Base de Datos  --------------------------------------------------

mysql_conn_registro.close()

mysql_conn_ossim.commit()
cursor_ossim.close()
mysql_conn_ossim.close()
