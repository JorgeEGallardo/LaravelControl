<div class='main'>
    <table>
        <tr>
            <td rowspan='2'><img src='{{ asset('images/mcdclogorecibo.png') }}' class='resimg'></td>
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
            <td width=55>
                <b>&nbsp;&nbsp;&nbsp;&nbsp; ID: </b> {{ $result['id_alumno'] }}
            </td>
            <td width=236>
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
                <td width=240><b>PROGRAMA DE CLASES</b></td>
                <td width=140><b>CONCEPTO</b></td>
                <td width=120><b>PERIODO</b></td>
                <td width=120><b>FECHA LIM.</b></td>
                <td width=90 align='right'><b>IMPORTE</b></td>
            </tr>

            @foreach ($datos as $dato)
                <tr>
                    <td>{{ $dato['nombre_programa'] }}</td>
                    <td>{{ $dato['concepto'] }}</td>
                    <td>{{ $dato['periodo'] }}</td>
                    <td>{{ $dato['fecha_limite'] }}</td>
                    <td align='right'>$ {{ $dato['importe_programa'] }}</td>
                </tr>
            @endforeach
        </table>

        <table>
            <tr>
                <td align='right' width=605><b>TOTAL</b>: ${{ $total }}</td>
            </tr>
        </table>
    </div>

    <div class='centrar'>
        <h2>MAKING CHEER AND DANCE CENTER, PROLONGACION FELIPE PESCADOR #1410 ENTRE COSTA Y AYUNTAMIENTO DURANGO, DGO. CEL. 6181093009<br>
            NOTA: CARECE DE VALIDEZ COMO COMPROBANTE DE PAGO SI NO TIENE SELLO DE LA ACADEMIA.<br></h2>
    </div>

</div>

<div>
    <input class="oculto-impresion" type="submit" id="btnMod" value="Regresar" onclick="regresar()" />
    <input class="oculto-impresion" type="submit" id="btnMod" value="Imprimir" onclick="printpost()" />
</div>
