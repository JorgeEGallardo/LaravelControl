<div class='main'>
    <table>
        <tr>
            <td rowspan='2'>
        </td>
             <img src='imagenes/mcdclogorecibo.png' class='resimg'>
            <td width=600 align='center' colspan='2'>
                <h2>REGIMEN SIMPLIFICADO DE CONFIANZA | CARLOS ISAAC NEVAREZ CAMPILLO | RFC: NECC910406K24</h2>
            </td>
        </tr>
        <tr>
            <td><h1>RECIBO DE COBRO</h1></td>
            <td align='right'><b>No. </b>{{ $recibo }}</td>
        </tr>
    </table>
    <table width=650>
        <tr>
            <td width=30>
                <b>&nbsp;&nbsp;&nbsp;&nbsp; ID: </b> {{ $result['id_alumno'] }}
            </td>
            <td width=60>
                <b>Nombre: </b> {{ $result['nombre'] }}
            </td>
            <td width=80>
                <b>Fecha: </b> {{ $fechaDia }}
            </td>
        </tr>
    </table>

    <div class='innerBox'>
        <table>
            <tr>
                <td width=120><b>PROGRAMA DE CLASES</b></td>
                <td width=100><b>CONCEPTO</b></td>
                <td width=60><b>PERIODO</b></td>
                <td width=90><b>FECHA LIM.</b></td>
                <td width=90 ><b>IMPORTE</b></td>
            </tr>

            @foreach ($datos as $dato)
                <tr>
                    <td>{{ $dato['nombre_programa'] }}</td>
                    <td>{{ $dato['concepto'] }}</td>
                    <td>{{ $dato['periodo'] }}</td>
                    <td>{{ $dato['fecha_limite'] }}</td>
                    <td>$ {{ $dato['importe_programa'] }}</td>
                </tr>
            @endforeach

            <tr>
                <td ></td>
                <td ></td>
                <td ></td>
                <td ></td>
                <td width=605><b>TOTAL</b>: ${{ $total }}</td>
            </tr>
        </table>

        <table>
            
        </table>
    </div>

    <div class='centrar'>
        <h2>MAKING CHEER AND DANCE CENTER, PROLONGACION FELIPE PESCADOR #1410 ENTRE COSTA Y AYUNTAMIENTO DURANGO, DGO. CEL. 6181093009<br>
            NOTA: CARECE DE VALIDEZ COMO COMPROBANTE DE PAGO SI NO TIENE SELLO DE LA ACADEMIA.<br></h2>
    </div>

</div>


<style>

    
.centrar
{
  	margin: auto;
}




body
{
	position: static;
	background-color: #FFF;
	font-family:Arial;
	overflow-x: hidden;
	color: #000;
	font-size:12px;
}

.innerBox
{
	width:609px;
	height: 188px;
	border-radius:15px;
	padding:10px;
	padding-bottom:5px;     
	border-style: solid;
   	border-width: 3px;

}

.innerBox2
{
	width:609px;
	border-radius:15px;
	padding:10px;
	padding-bottom:5px;
	height: 198px;     
	border-style: solid;
   	border-width: 3px;

}

.main
{
	width:635px;
	padding:8px; 
	border-style: solid;
   	border-width: 3px;
	background-image:url(imagenes/marcaagua.png);
	background-repeat:no-repeat;
	background-size: 635px;
	background-attachment: fixed;

}





.resimg
{
	height:50px;
	margin: auto;

}


.resimg2
{
	height:100px;
	margin: auto;
}

.resimg3
{	
	height:15px;

}

.centrar
{
	text-align: center;
}

h1
{	
	color: #000000;
	font-size:18px;
	margin-bottom:0px;
	margin-top:0px;
}

h2
{	
	color: #000000;
	font-size:6px;
	margin-bottom:0px;
	margin-top:1px;
}

h3
{	
	color: #000000;
	font-size:12px;
	margin-bottom:0px;
	margin-top:0px;
}

h4
{	
	color: #000000;
	font-size:16px;
	margin-bottom:0px;
	margin-top:0px;
}



#lista
{
	font-size:11px;
}




@media print
{
  .oculto-impresion, .oculto-impresion *{display: none !important;}
}

</style>
