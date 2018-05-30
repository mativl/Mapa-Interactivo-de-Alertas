<?php
$dato = $_POST['texto']; // Con formato 1100-1200
$horas = [];
$horas = explode("-",$dato); // horas[0] = hora_inicial, horas[1] = hora_final

$link = mysqli_connect("ip", "user-map", "pass-map");

/* Comprobar la Conexión */
if(mysqli_connect_errno()) {
    printf("Falló la conexión: %s\n", mysqli_connect_error());
    exit();
}

$texto_para_js = []; // Texto que voy a pasar a JavaScript
$ips_sin_repetir = []; // Guardo en un array todas las filas, en cada posicion hay un array con los datos de la BBDD (Array Doble)
$relacion_ips_repetidas = []; // Guardo la Relacion de Ips --> Ip_Origen - Ip_Destino, comienzo el arbol

$consulta_inicial = "SELECT *, count(*) FROM registros_diarios WHERE hora > '$horas[0]' AND hora < '$horas[1]' GROUP BY ip_origen, ip_destino having count(*) = 1"; 

/* If we have to retrieve large amount of data we use MYSQLI_USE_RESULT */
if($resultado = mysqli_query($link, $consulta_inicial, MYSQLI_USE_RESULT)){
	while ($fila = mysqli_fetch_row($resultado)){
        array_push($ips_sin_repetir, $fila);
    }
    mysqli_free_result($resultado);
    $consulta_repetidos = "SELECT *, count(*) FROM registros_diarios WHERE hora > '$horas[0]' AND hora < '$horas[1]' GROUP BY ip_origen, ip_destino having count(*) > 1"; 
    if($resultado = mysqli_query($link, $consulta_repetidos, MYSQLI_USE_RESULT)){
        while($fila = mysqli_fetch_row($resultado)){
            $texto = $fila[1]."-". $fila[5];
            array_push($relacion_ips_repetidas, $texto);
        }
    }
}
//printf("Cantidad de filas NO repetidas: %s", count($ips_sin_repetir)."<br/>");
//printf("Cantidad de filas repetidas: %s", count($relacion_ips_repetidas)."<br/>");

/* Cada fila con formato: Lat_Ori + Lon_Ori + Lat_Dest + Lon_Dest + Ip_Ori + Pais_Ori + Ip_Dest + Pais_Dest + Puerto(Protocolo)?Hora?Hora?...@Puerto(Protocolo)?Hora?Hora?...@...
   Notar como esta separada cada variable "+", los puertos estan separados por "@" y cada puerto tiene la cantidad de veces (Por hora) que se repiten separados por "?"
   El texto es uno solo y todas las filas estan unidas por el caracter "*"
*/
for ($i=0; $i < count($ips_sin_repetir); $i++){ 
    $fila = $ips_sin_repetir[$i];
    $time = time();
    $hora_actual = date("H:i:s", $time);
    if($fila[0]>$hora_actual){
        $fila[0] = $fila[0].'(Ayer)';
    }
    $var_aux = $fila[3].'+'.$fila[4].'+'.$fila[7].'+'.$fila[8].'+'.$fila[1].'+'.$fila[2].'+'.$fila[5].'+'.$fila[6].'+'.$fila[9].'('.$fila[10].')?'.$fila[0];
    array_push($texto_para_js, $var_aux);
}

for ($i=0; $i < count($relacion_ips_repetidas); $i++){ 
    $ips = explode("-",$relacion_ips_repetidas[$i]);
    $puertos_de_ips_repetidas = [];
    $pais_origen = 0;
    $lat_origen = 0;
    $lon_origen = 0;
    $pais_destino = 0;
    $lat_destino = 0;
    $lon_destino = 0;

    $consulta_puertos = "SELECT * FROM registros_diarios WHERE ip_origen= '$ips[0]' AND ip_destino= '$ips[1]' AND hora > '$horas[0]' AND hora < '$horas[1]' GROUP BY puerto_destino";

    if($resultado = mysqli_query($link, $consulta_puertos, MYSQLI_USE_RESULT)){
        while($fila = mysqli_fetch_row($resultado)){
            $pais_origen = $fila[2];
            $lat_origen = $fila[3];
            $lon_origen = $fila[4];
            $pais_destino = $fila[6];
            $lat_destino = $fila[7];
            $lon_destino = $fila[8];
            $protocolo = $fila[10];
            array_push($puertos_de_ips_repetidas, $fila[9]);
        }
        mysqli_free_result($resultado);
    }
    
    $var_aux = []; // Utilizado para seguir usando el implode a un nivel mayor 
    for ($j=0; $j < count($puertos_de_ips_repetidas); $j++){ 
        $horas_de_puertos_repetidos = [];
        $consulta_hora = "SELECT * FROM registros_diarios WHERE ip_origen= '$ips[0]' AND ip_destino= '$ips[1]' AND puerto_destino= '$puertos_de_ips_repetidas[$j]' AND hora > '$horas[0]' AND hora < '$horas[1]'GROUP BY hora";
        if ($resultado = mysqli_query($link, $consulta_hora, MYSQLI_USE_RESULT)){
            while($fila = mysqli_fetch_row($resultado)){
                array_push($horas_de_puertos_repetidos, $fila[0]);
            }
            mysqli_free_result($resultado);
        }
        $horas_de_puertos_repetidos = ordenar_hora($horas_de_puertos_repetidos);
        $horas_de_puertos_repetidos = implode("?", $horas_de_puertos_repetidos);
        $texto_aux = $puertos_de_ips_repetidas[$j].'('.$protocolo.')?'. $horas_de_puertos_repetidos;
        array_push($var_aux, $texto_aux);
    }
    $var_aux = implode("@", $var_aux);
    $datos_unidos = $lat_origen.'+'.$lon_origen.'+'.$lat_destino.'+'.$lon_destino.'+'.$ips[0].'+'.$pais_origen.'+'.$ips[1].'+'.$pais_destino.'+'.$var_aux; // Utilizado para seguir unir todos los datos de esta "Relacion Ip" a $texto_para_js
    array_push($texto_para_js, $datos_unidos);
}

$texto_para_js = implode("*", $texto_para_js);
//imprimir($texto_para_js);
echo $texto_para_js;

function imprimir($js){
    $lista = explode("*",$js);
    for($i=0; $i < count($lista); $i++){ 
        $fila = explode("+",$lista[$i]);
        printf("Ip Origen: %s hacia Ip Destino: %s",$fila[4],$fila[6]."<br/>");
        $puertos_y_protocolo = explode("@",$fila[8]);
        for($j=0; $j < count($puertos_y_protocolo); $j++){ 
            $hora = explode("?",$puertos_y_protocolo[$j]);
            printf("Puerto(Protocolo): %s ",$hora[0]."<br/>");
            for ($x=1; $x < count($hora); $x++){ 
                printf("Hora: %s",$hora[$x]."<br/>");
            }
        }
        printf("-------------------------------------------------- <br/>");
    }
}

function ordenar_hora($lista){
    $time = time();
    $hora_actual = date("H:i:s", $time);
    $lista_ordenada = [];
    $ayer = [];
    $hoy = [];
    for ($i=0; $i < count($lista); $i++) { 
        $hora_reg = $lista[$i];
        if($hora_reg>$hora_actual){
            $formato_ayer = $hora_reg.'(Ayer)';
            array_push($ayer, $formato_ayer);
        }
        else{
            array_push($hoy, $hora_reg);
        }
    }
    $lista_ordenada = array_merge($ayer,$hoy);
    return $lista_ordenada;
}
mysqli_close($link);
?>