<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alumno;
use App\Models\ProgramaPredefinido;
use App\Models\RegistroPredefinido;
use App\Models\AdeudoPrograma;
use App\Models\Clase;
use DB;
use PDF;
use Illuminate\Support\Facades\Storage;


class ExpedienteAlumnoController extends Controller
{
//PAGOS PENDIENTES
    public function getAdeudosPorAlumno($id)
    {
        $total = 0;
        $data = [];

        // Consultar adeudos de programas
        $adeudosProgramas = DB::table('adeudos_programas')
            ->where('id_alumno', $id)
            ->orderBy('fecha_limite')
            ->get();

        $adeudosSecundarios = DB::table('adeudos_secundarios')
            ->where('id_alumno', $id)
            ->orderBy('concepto')
            ->get();

        if ($adeudosProgramas->isEmpty() && $adeudosSecundarios->isEmpty()) {
            return response()->json([
                'message' => 'EL ALUMNO NO PRESENTA ADEUDOS AL DÍA DE HOY'
            ]);
        } else {
            foreach ($adeudosProgramas as $adeudo) {
                $fecha_limite = date('d/M/Y', strtotime($adeudo->fecha_limite));
                $meses = ['Jan' => 'ENE', 'Apr' => 'ABR', 'Aug' => 'AGO', 'Dec' => 'DIC'];
                $fecha_limite = strtr($fecha_limite, $meses);
                $fecha_limite = strtoupper($fecha_limite);

                $deduccion = '';
                if ($adeudo->beca != 0) {

                    $deduccion = " (-{$adeudo->beca}% BECA.)";
                } elseif ($adeudo->descuento != 0) {

                    $deduccion = " (-{$adeudo->descuento}% DESC.)";
                } else {

                }

                if ($adeudo->id_programa != 000) {
                    $nombre_programa = DB::table('programas_predefinidos')
                        ->where('id_programa', $adeudo->id_programa)
                        ->value('nombre');
                } else {
                    $nombre_programa = $this->getNombreProgramaVisita($id, $adeudo->id_programa, $adeudo->periodo);
                }

                $data[] = [
                    'nombre_programa' => $nombre_programa,
                    'periodo' => $adeudo->periodo,
                    'concepto' => $adeudo->concepto,
                    'deduccion' => $deduccion,
                    'importe' => $adeudo->monto,
                    'fecha_limite' => $fecha_limite,
                    'id_programa' => $adeudo->id_programa

                ];

                $total += $adeudo->monto;

            }

            foreach ($adeudosSecundarios as $adeudo) {
                $deduccion = '';
                $img = '';
                if ($adeudo->descuento != 0) {

                    $deduccion = " (-{$adeudo->descuento}% DESC.)";
                }

                $data[] = [
                    'nombre_programa' => $adeudo->concepto,
                    'periodo' => $adeudo->periodo,
                    'concepto' => $adeudo->concepto,
                    'deduccion' => $deduccion,
                    'importe' => $adeudo->monto,
                    'fecha_limite' => 'PAGO INMEDIATO',
                    'id_programa' => 'SEC'
                ];

                $total += $adeudo->monto;

            }

            $total_format = number_format($total, 2);

            return response()->json([
                'data' => $data,
                'total' => $total_format
            ]);
        }
    }

    private function getNombreProgramaVisita($id_alumno, $id_programa, $periodo)
    {
        $fragmentado = DB::table('adeudos_fragmentados')
            ->where('id_alumno', $id_alumno)
            ->where('id_programa', $id_programa)
            ->where('periodo', $periodo)
            ->first();

        $clase = DB::table('clases')
            ->where('id_clase', $fragmentado->id_clase)
            ->first();

        $programa = DB::table('programas_predefinidos')
            ->where('id_programa', $clase->id_programa)
            ->value('nombre');

        return "{$clase->nombre} ({$programa})";
    }
//HISTORIAL DE PAGOS
    public function getPagosPorAlumno($id)
    {
        // Consultar pagos de programas
        $pagosProgramas = DB::table('pagos_programas')
            ->where('id_alumno', $id)
            ->where('id_programa', '!=', 000)
            ->orderBy('fecha_pago', 'DESC')
            ->orderBy('monto', 'DESC')
            ->get();

        // Consultar pagos fragmentados
        $pagosFragmentados = DB::table('pagos_fragmentados')
            ->where('id_alumno', $id)
            ->where('id_programa', 000)
            ->orderBy('id_clase', 'DESC')
            ->get();

        // Consultar pagos secundarios
        $pagosSecundarios = DB::table('pagos_secundarios')
            ->where('id_alumno', $id)
            ->orderBy('fecha_pago', 'DESC')
            ->orderBy('monto', 'DESC')
            ->get();

        if ($pagosProgramas->isEmpty() && $pagosFragmentados->isEmpty() && $pagosSecundarios->isEmpty()) {
            return response()->json([
                'message' => 'NO HAY PAGOS REGISTRADOS'
            ]);
        } else {
            $data = [];
            foreach ($pagosProgramas as $pago) {
                $programa = DB::table('programas_predefinidos')
                    ->where('id_programa', $pago->id_programa)
                    ->value('nombre');

                $fecha_pago = date('d/M/Y', strtotime($pago->fecha_pago));
                $meses = ['Jan' => 'ENE', 'Apr' => 'ABR', 'Aug' => 'AGO', 'Dec' => 'DIC'];
                $fecha_pago = strtr($fecha_pago, $meses);
                $fecha_pago = strtoupper($fecha_pago);

                $data[] = [
                    'recibo' => $pago->recibo,
                    'fecha_pago' => $fecha_pago,
                    'programa' => $programa,
                    'periodo' => $pago->periodo,
                    'concepto' => $pago->concepto,
                    'importe' => $pago->monto
                ];
            }

            foreach ($pagosFragmentados as $pago) {
                $clase = DB::table('clases')
                    ->where('id_clase', $pago->id_clase)
                    ->first();

                $programa = DB::table('programas_predefinidos')
                    ->where('id_programa', $clase->id_programa)
                    ->value('nombre');



                $data[] = [
                    'recibo' => 'VISITA',
                    'fecha_pago' => $pago->periodo,
                    'programa' => "{$clase->nombre} ({$programa})",
                    'periodo' => $pago->periodo,
                    'concepto' => 'VISITA',
                    'importe' => $pago->monto
                ];
            }

            foreach ($pagosSecundarios as $pago) {
                $fecha_pago = date('d/M/Y', strtotime($pago->fecha_pago));
                $fecha_pago = strtr($fecha_pago, $meses);
                $fecha_pago = strtoupper($fecha_pago);

                $data[] = [
                    'recibo' => $pago->recibo,
                    'fecha_pago' => $fecha_pago,
                    'programa' => 'SECUNDARIO',
                    'periodo' => $pago->periodo,
                    'concepto' => $pago->concepto,
                    'importe' => $pago->monto
                ];
            }

            return response()->json([
                'data' => $data
            ]);
        }
    }
//CLASES DEL ALUMNO
    public function getProgramasPorAlumno($id)
    {
        // Verificar si el alumno tiene programas cargados
        $programasCount = DB::table('registro_predefinido')
            ->where('id_alumno', $id)
            ->count();

        if ($programasCount == 0) {
            return response()->json([
                'message' => 'EL ALUMNO NO CUENTA CON NINGÚN PROGRAMA CARGADO ACTUALMENTE'
            ]);
        } else {
            $programas = DB::table('registro_predefinido')
                ->where('id_alumno', $id)
                ->get();

            $data = [];

            foreach ($programas as $programa) {
                $id_programa = $programa->id_programa;
                $beca = $programa->beca;

                $programaInfo = DB::table('programas_predefinidos')
                    ->where('id_programa', $id_programa)
                    ->orderBy('nombre')
                    ->first();

                $nombre = $programaInfo->nombre;
                $mensualidad = $programaInfo->mensualidad;

                $descuentoCount = DB::table('adeudos_programas')
                    ->where('id_alumno', $id)
                    ->where('id_programa', $id_programa)
                    ->where('descuento', '!=', 0)
                    ->count();

                $clases = DB::table('clases')
                    ->where('id_programa', $id_programa)
                    ->orderBy('porcentaje', 'DESC')
                    ->get();

                $clasesData = [];

                foreach ($clases as $clase) {
                    $maestro = DB::table('maestros')
                        ->where('id_maestro', $clase->id_maestro)
                        ->orderBy('nombre')
                        ->first();

                    $clasesData[] = [
                        'nombre' => $clase->nombre,
                        'informacion' => $clase->informacion,
                        'maestro' => $maestro->nombre_titular,
                    ];
                }

                $data[] = [
                    'nombre' => $nombre,
                    'mensualidad' => $mensualidad,
                    'beca' => $beca,
                    'descuentoCount' => $descuentoCount,
                    'clases' => $clasesData,
                ];
            }

            return response()->json($data);
        }
    }
//INFORMACION AGREGAR VISITAS
public function obtenerInformacionVisitas()
    {
        // Obtener el precio de visita
        $informacion = DB::table('informacion')->first();
        $precio = $informacion->precio_visita;

        // Obtener los programas predefinidos que no están ocultos y están activos
        $programas = DB::table('programas_predefinidos')
            ->where('ocultar', 0)
            ->where('status', 1)
            ->orderBy('id_programa')
            ->get();

        $data = [];

        foreach ($programas as $programa) {
            $id_programa = $programa->id_programa;
            $nombre_programa = $programa->nombre;

            $clases = DB::table('clases')
                ->where('id_programa', $id_programa)
                ->orderBy('id_programa')
                ->get();

            foreach ($clases as $clase) {
                $id_clase = $clase->id_clase;
                $id_maestro = $clase->id_maestro;
                $nombre_clase = $clase->nombre;
                $informacion_clase = $clase->informacion;

                $maestro = DB::table('maestros')
                    ->where('id_maestro', $id_maestro)
                    ->first();
                $nombre_maestro = $maestro->nombre_titular;

                $data[] = [
                    'programa' => $nombre_programa,
                    'clase' => $nombre_clase,
                    'informacion' => $informacion_clase,
                    'maestro' => $nombre_maestro,
                    'precio' => $precio,
                ];
            }
        }

        return response()->json($data);
    }

//PROGRAMAS
    public function obtenerProgramas($id)
    {
        // Obtener programas predefinidos que no están ocultos
        $programas = DB::table('programas_predefinidos')
            ->where('ocultar', 0)
            ->orderBy('nombre')
            ->get();

        $data = [];

        foreach ($programas as $programa) {
            $id_programa = $programa->id_programa;
            $nombre = $programa->nombre;
            $mensualidad = $programa->mensualidad;

            $carga = DB::table('registro_predefinido')
                ->where('id_alumno', $id)
                ->where('id_programa', $id_programa)
                ->count();

            if ($carga == 0) {
                $clases = DB::table('clases')
                    ->where('id_programa', $id_programa)
                    ->orderBy('porcentaje', 'DESC')
                    ->get();

                $clase_data = [];

                foreach ($clases as $clase) {
                    $maestro = DB::table('maestros')
                        ->where('id_maestro', $clase->id_maestro)
                        ->first();

                    $clase_data[] = [
                        'nombre_clase' => $clase->nombre,
                        'informacion' => $clase->informacion,
                        'nombre_maestro' => $maestro->nombre_titular,
                    ];
                }

                $data[] = [
                    'id_programa' => $id_programa,
                    'nombre' => $nombre,
                    'mensualidad' => $mensualidad,
                    'clases' => $clase_data,
                ];
            }
        }

        return response()->json($data);
    }

//COBRAR INSCRIPCION
    public function registrarInscripcion(Request $request, $id_alumno)
    {
        $concepto = 'INSCRIPCION';

        // Obtener información de la tabla 'informacion'
        $informacion = DB::table('informacion')->first();
        $periodo = $informacion->temporada;
        $monto = $informacion->precio_inscripcion;

        $descuento = '0';
        $corte = '0';

        // Verificar si ya existe un registro de inscripción
        $filas = DB::table('adeudos_secundarios')
            ->where('id_alumno', $id_alumno)
            ->where('concepto', $concepto)
            ->where('periodo', $periodo)
            ->count();

        if ($filas == 0) {
            // Insertar nuevo registro de inscripción
           $data = DB::table('adeudos_secundarios')->insert([
                'id_alumno' => $id_alumno,
                'concepto' => $concepto,
                'periodo' => $periodo,
                'monto' => $monto,
                'descuento' => $descuento,
                'corte' => $corte

            ]);

            return response()->json([$data], 200);

        } else {
            // Mostrar alerta si ya existe un registro de inscripción
            return response()->json(['message' => 'Ya se encuentra un cobro pendiente de inscripción registrado.']);
        }
    }
//COBRAR RECARGO
    public function registrarRecargo(Request $request, $id_alumno)
    {
        $concepto = 'RECARGO';

        // Obtener información de la tabla 'informacion'
        $informacion = DB::table('informacion')->first();
        $monto = $informacion->precio_recargo;

        $descuento = '0';
        $corte = '0';

        $mes = date('m');
        $anio = date('Y');
        $dia = date('t'); // Obtener el último día del mes

        $fecha = $anio . "-" . $mes . "-" . $dia;

        $meses = [
            '01' => 'ENERO',
            '02' => 'FEBRERO',
            '03' => 'MARZO',
            '04' => 'ABRIL',
            '05' => 'MAYO',
            '06' => 'JUNIO',
            '07' => 'JULIO',
            '08' => 'AGOSTO',
            '09' => 'SEPTIEMBRE',
            '10' => 'OCTUBRE',
            '11' => 'NOVIEMBRE',
            '12' => 'DICIEMBRE'
        ];

        $periodo = $meses[$mes] . "/" . $anio;

        // Insertar nuevo registro de adeudo
        $data = DB::table('adeudos_secundarios')->insert([
            'id_alumno' => $id_alumno,
            'concepto' => $concepto,
            'periodo' => $periodo,
            'monto' => $monto,
            'descuento' => $descuento,
            'corte' => $corte

        ]);

        return response()->json([$data], 200);

}
//REGISTRAR VISITA
public function registrarVisita(Request $request, $id_alumno, $id_clase)
    {
        $alumno = DB::table('alumnos')->where('id_alumno', $id_alumno)->first();

        if ($alumno->status == 1) {
            $ano = date("Y");
            $mes = date("M");
            $dia = date("d");

            $meses = [
                'Jan' => 'ENE', 'Feb' => 'FEB', 'Mar' => 'MAR', 'Apr' => 'ABR',
                'May' => 'MAY', 'Jun' => 'JUN', 'Jul' => 'JUL', 'Aug' => 'AGO',
                'Sep' => 'SEPT', 'Oct' => 'OCT', 'Nov' => 'NOV', 'Dec' => 'DIC'
            ];

            $mes = strtoupper($meses[$mes]);
            $fecha = strtoupper("$dia/$mes/$ano");
            $periodo = $fecha;
            $fecha = date("Y-m-d");

            $informacion = DB::table('informacion')->first();
            $precio = $informacion->precio_visita;
            $concepto = 'VISITA';
            $beca = 0;
            $descuento = 0;

            $clase = DB::table('clases')->where('id_clase', $id_clase)->first();
            $id_maestro = $clase->id_maestro;
            $id_programa = 0;

            $adeudo_programa = DB::table('adeudos_programas')
                ->where('id_programa', $id_programa)
                ->where('id_alumno', $id_alumno)
                ->exists();

            if (!$adeudo_programa) {
                DB::table('adeudos_programas')->insert([
                    'id_alumno' => $id_alumno,
                    'id_programa' => $id_programa,
                    'periodo' => $periodo,
                    'concepto' => $concepto,
                    'monto' => $precio,
                    'beca' => $beca,
                    'descuento' => $descuento,
                    'fecha_limite' => $fecha

                ]);

                DB::table('adeudos_fragmentados')->insert([
                    'id_alumno' => $id_alumno,
                    'id_programa' => $id_programa,
                    'id_clase' => $id_clase,
                    'periodo' => $periodo,
                    'id_maestro' => $id_maestro,
                    'monto' => $precio
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Visita registrada exitosamente.'

                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'El alumno cuenta con una visita cargada. Las visitas no se pueden acumular.'
                ], 400);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'El alumno se encuentra dado de baja. No se pueden agregar visitas.'
            ], 400);
        }
    }
//REGISTRAR PROGRAMA
public function agregarPrograma(Request $request)
{
    $id_alumno = $request->input('id_alumno');
    $id_programa = $request->input('id_programa');

    // Verificar si el alumno existe y está activo
    $alumno = Alumno::find($id_alumno);

    if ($alumno && $alumno->status == 1) {
        // Verificar si el programa existe y está activo
        $programa = ProgramaPredefinido::where('id_programa', $id_programa)->first();

        if ($programa && $programa->status == 1) {
            // Calcular periodo y fecha limite
            $mes = date("m");
            $anio = date("Y");
            $dia = date("d");

            $periodo = strtoupper(date("F", mktime(0, 0, 0, $mes, 10))).'/'.$anio;

            $dia_limite = DB::table('informacion')->value('dia_limite');

            if ($dia <= $dia_limite) {
                $dia = $dia_limite;
                $fecha = $anio."-".$mes."-".$dia;
            } else {
                $dia = date("t");
                $fecha = $anio."-".$mes."-".$dia;
            }

            // Verificar si el registro ya existe sin usar 'periodo'
            $exists = DB::table('registro_predefinido')
                ->where('id_alumno', $id_alumno)
                ->where('id_programa', $id_programa)
                ->exists();

            if (!$exists) {
                DB::table('registro_predefinido')->insert([
                    'id_alumno' => $id_alumno,
                    'id_programa' => $id_programa,
                    'precio' => $programa->mensualidad,
                    'beca' => 0
                ]);

                DB::table('adeudos_programas')->insert([
                    'id_alumno' => $id_alumno,
                    'id_programa' => $id_programa,
                    'periodo' => $periodo,
                    'concepto' => 'MENSUALIDAD',
                    'monto' => $programa->mensualidad,
                    'beca' => 0,
                    'descuento' => 0,
                    'fecha_limite' => $fecha
                ]);

                $clases = DB::table('clases')->where('id_programa', $id_programa)->orderBy('porcentaje', 'desc')->get();
                foreach ($clases as $clase) {
                    $monto_frag = $programa->mensualidad * ($clase->porcentaje / 100.0);

                    DB::table('adeudos_fragmentados')->insert([
                        'id_alumno' => $id_alumno,
                        'id_programa' => $id_programa,
                        'id_clase' => $clase->id_clase,
                        'periodo' => $periodo,
                        'id_maestro' => $clase->id_maestro,
                        'monto' => $monto_frag
                    ]);
                }

                return response()->json(['message' => 'Programa añadido correctamente'], 200);
            } else {
                return response()->json(['error' => 'El alumno ya está inscrito en este programa.'], 400);
            }
        } else {
            return response()->json(['error' => 'El grupo se encuentra cerrado o no existe.'], 400);
        }
    } else {
        return response()->json(['error' => 'El alumno se encuentra dado de baja o no existe. No se pueden agregar programas.'], 400);
    }
}
//PAGO
public function accionPago(Request $request)
{
    $fecha = date("Y-m-d");
        $corte = 0;
        $nomina = 0;
        $cant = $request->input('cant');
        $total = $request->input('total');

        $ano = date("Y", strtotime($fecha));
        $mes = date("M", strtotime($fecha));
        $dia = date("d", strtotime($fecha));
        $meses = ['Jan' => 'ENE', 'Apr' => 'ABR', 'Aug' => 'AGO', 'Dec' => 'DIC'];
        $mes = strtoupper($meses[$mes] ?? $mes);

        $fechaDia = "$dia/$mes/$ano";

        $recibo_programa = DB::table('pagos_programas')->max('recibo') + 1;
        $recibo_programa = str_pad($recibo_programa, 5, "0", STR_PAD_LEFT);

        $recibo_secundario = DB::table('pagos_secundarios')->max('recibo') + 1;
        $recibo_secundario = str_pad($recibo_secundario, 5, "0", STR_PAD_LEFT);

        $recibo = max($recibo_programa, $recibo_secundario);

        $id_alumno = $request->input('id_alumno');
        $alumno = DB::table('alumnos')->where('id_alumno', $id_alumno)->first();

        if (!$alumno) {
            return response()->json(['error' => 'Alumno no encontrado'], 404);
        }

    $pagos_realizados = [];

    for ($i = 0; $i < $cant; $i++) {
        if ($request->has('add'.$i)) {
            $id_programa = $request->input('id_programa_'.$i);
            $nombre_programa = $request->input('nombre_programa_'.$i);
            $concepto = $request->input('concepto_'.$i);
            $periodo = $request->input('periodo_'.$i);
            $fecha_limite = $request->input('fecha_limite_'.$i);
            $importe_programa = $request->input('importe_'.$i);

            $pago_info = [
                'nombre_programa' => $nombre_programa,
                'concepto' => $concepto,
                'periodo' => $periodo,
                'fecha_limite' => $fecha_limite,
                'importe_programa' => $importe_programa,
            ];

            if ($id_programa !== 'SEC') {
                $adeudosFragmentados = DB::table('adeudos_fragmentados')
                    ->where('id_alumno', $id_alumno)
                    ->where('id_programa', $id_programa)
                    ->where('periodo', $periodo)
                    ->get();

                foreach ($adeudosFragmentados as $adeudo) {
                    DB::table('pagos_fragmentados')->insert([
                        'id_alumno' => $id_alumno,
                        'id_programa' => $id_programa,
                        'id_clase' => $adeudo->id_clase,
                        'periodo' => $periodo,
                        'id_maestro' => $adeudo->id_maestro,
                        'monto' => $adeudo->monto,
                        'nomina' => $nomina,
                    ]);

                    DB::table('adeudos_fragmentados')
                        ->where('id_alumno', $id_alumno)
                        ->where('id_programa', $id_programa)
                        ->where('id_clase', $adeudo->id_clase)
                        ->where('periodo', $periodo)
                        ->delete();
                }

                $adeudoPrograma = DB::table('adeudos_programas')
                    ->where('id_alumno', $id_alumno)
                    ->where('id_programa', $id_programa)
                    ->where('periodo', $periodo)
                    ->first();

                if ($adeudoPrograma) {
                    $fecha_limite = $adeudoPrograma->fecha_limite;

                    DB::table('adeudos_programas')
                        ->where('id_alumno', $id_alumno)
                        ->where('id_programa', $id_programa)
                        ->where('periodo', $periodo)
                        ->delete();

                    DB::table('pagos_programas')->insert([
                        'id_alumno' => $id_alumno,
                        'id_programa' => $id_programa,
                        'periodo' => $periodo,
                        'concepto' => $concepto,
                        'monto' => $importe_programa,
                        'descuento' => '000',
                        'beca' => '000',
                        'fecha_limite' => $fecha_limite,
                        'fecha_pago' => $fecha,
                        'recibo' => $recibo,
                        'corte' => $corte,
                    ]);
                }
            } else {
                DB::table('pagos_secundarios')->insert([
                    'id_alumno' => $id_alumno,
                    'concepto' => $concepto,
                    'periodo' => $periodo,
                    'monto' => $importe_programa,
                    'descuento' => '000',
                    'fecha_pago' => $fecha,
                    'nomina' => $nomina,
                    'recibo' => $recibo,
                    'corte' => $corte,
                ]);

                DB::table('adeudos_secundarios')
                    ->where('id_alumno', $id_alumno)
                    ->where('concepto', $concepto)
                    ->where('periodo', $periodo)
                    ->delete();
            }

            $total += $importe_programa;
            $pagos_realizados[] = $pago_info;
        }
    }
  // Obtenemos los datos necesarios para el recibo
  $recibo = $recibo; // Ejemplo de número de recibo
  $result = [
      'id_alumno' => $id_alumno,
      'nombre' => $alumno->nombre
  ]; // Ejemplo de datos de alumno
  $datos = $pagos_realizados;

    $pdf = PDF::loadView('recibo', compact('recibo', 'result', 'fechaDia', 'datos', 'total'));

    $pdfPath = 'pdf/recibo_' . time() . '.pdf';
    Storage::put('public/' . $pdfPath, $pdf->output());

    $downloadLink = Storage::url($pdfPath);

    return response()->json([
        'message' => 'PDF generado exitosamente',
        'download_link' => $downloadLink,
        'total' => $total,
        'pagos_realizados' => $pagos_realizados
    ], 200);
}
}
