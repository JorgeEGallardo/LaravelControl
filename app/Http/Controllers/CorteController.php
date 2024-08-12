<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Corte;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class CorteController extends Controller
{
    public function index()
    {
        $cortes = Corte::all();
        return response()->json($cortes);
    }

    public function corteCaja()
    {
        $pagos = DB::table('pagos_programas')
            ->leftJoin('programas_predefinidos', 'pagos_programas.id_programa', '=', 'programas_predefinidos.id_programa')
            ->leftJoin('alumnos', 'pagos_programas.id_alumno', '=', 'alumnos.id_alumno')
            ->where('pagos_programas.corte', 0)
            ->select(
                'pagos_programas.id_programa',
                'pagos_programas.id_alumno',
                'pagos_programas.fecha_pago',
                'pagos_programas.periodo',
                'pagos_programas.monto',
                'pagos_programas.recibo',
                'programas_predefinidos.nombre as nombre_programa',
                'alumnos.nombre as nombre_alumno'
            )
            ->orderBy('pagos_programas.id_programa', 'desc')
            ->orderBy('pagos_programas.periodo', 'desc')
            ->get();

        return response()->json($pagos);
    }

    public function getCortesPorAnio($anio)
    {
        $totalfinal = 0;
        $num = 1;

        $cortes = DB::table('cortes')
            ->select(DB::raw('DISTINCT MONTH(fecha) as mesnum'))
            ->whereYear('fecha', $anio)
            ->orderBy('fecha', 'desc')
            ->get();

        $data = [];

        foreach ($cortes as $corte) {
            $mesnum = $corte->mesnum;
            $total = 0.00;

            $cortesMes = DB::table('cortes')
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mesnum)
                ->orderBy('fecha', 'desc')
                ->get();

            foreach ($cortesMes as $corteMes) {
                $total += $corteMes->total;
            }

            $meses = [
                1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
                5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
                9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
            ];

            $mes = $meses[$mesnum];
            $total_format = number_format($total, 2);

            $data[] = [
                'num' => $num,
                'mes' => $mes . " DEL " . $anio,
                'total' => $total_format,
                'mesnum' => $mesnum
            ];

            $num++;
            $totalfinal += $total;
        }

        $totalfinal_format = number_format($totalfinal, 2);

        $anios = DB::table('cortes')
            ->select(DB::raw('DISTINCT YEAR(fecha) as anio'))
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json([
            'data' => $data,
            'totalfinal' => $totalfinal_format,
            'anios' => $anios,
            'anio' => $anio
        ]);
    }

    public function getCortesPorMes($anio, $mes)
    {
        $meses = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
        ];
        
        $mesnombre = $meses[$mes];
        
        $totalfinal = 0;
        $num = 1;

        $cortes = DB::table('cortes')
            ->whereMonth('fecha', $mes)
            ->whereYear('fecha', $anio)
            ->orderBy('fecha', 'desc')
            ->get();

        if ($cortes->isEmpty()) {
            return response()->json(['message' => 'NO HAY CORTES DE CAJA REGISTRADOS ESTE MES'], 404);
        }

        $data = [];
        
        foreach ($cortes as $corte) {
            $id_corte = $corte->id_corte;
            $fecha = $corte->fecha;
            $id_autor = $corte->id_autor;
            $total = $corte->total;

            $autor = DB::table('usuarios')
                ->where('id', $id_autor)
                ->value('nombre');

            $dias = ["DOMINGO","LUNES","MARTES","MIÉRCOLES" ,"JUEVES","VIERNES","SÁBADO"];
            $dia = substr($fecha, 8, 2);
            $mes = substr($fecha, 5, 2);
            $anio = substr($fecha, 0, 4);
            $nombredia = strtoupper($dias[intval((date("w", mktime(0, 0, 0, $mes, $dia, $anio))))]);

            $ano = date("Y", strtotime($fecha));
            $mes = date("m", strtotime($fecha));
            $dia = date("d", strtotime($fecha));
            $mesnombre = strtoupper($meses[intval($mes)]);
            $fecha_format = strtoupper($dia . " DE " . $mesnombre . " DEL " . $ano);

            $total_format = number_format($total, 2);

            $data[] = [
                'num' => $num,
                'fecha' => $nombredia . ", " . $fecha_format,
                'autor' => $autor,
                'total' => $total_format,
                'id_corte' => $id_corte
            ];

            $num++;
            $totalfinal += $total;
        }

        $totalfinal_format = number_format($totalfinal, 2);

        return response()->json([
            'data' => $data,
            'totalfinal' => $totalfinal_format,
            'mes' => $mesnombre,
            'anio' => $anio
        ]);
    }

    public function getPagosPorCorte($id_corte)
    {
        $total = 0;
        $num = 1;

        // Consultar pagos de programas
        $pagosProgramas = DB::table('pagos_programas')
            ->join('programas_predefinidos', 'pagos_programas.id_programa', '=', 'programas_predefinidos.id_programa')
            ->select('pagos_programas.*', 'programas_predefinidos.nombre as nombre_programa')
            ->where('pagos_programas.corte', $id_corte)
            ->orderBy('pagos_programas.id_alumno')
            ->get();

        $dataProgramas = [];

        foreach ($pagosProgramas as $pago) {
            $fecha_pago = date('d/M/Y', strtotime($pago->fecha_pago));
            $meses = ['Jan' => 'ENE', 'Apr' => 'ABR', 'Aug' => 'AGO', 'Dec' => 'DIC'];
            $fecha_pago = strtr($fecha_pago, $meses);
            $fecha_pago = strtoupper($fecha_pago);

            $monto_format = number_format($pago->monto, 2);

            $dataProgramas[] = [
                'num' => $num,
                'recibo' => $pago->recibo,
                'id_alumno' => $pago->id_alumno,
                'nombre_programa' => $pago->nombre_programa,
                'periodo' => $pago->periodo,
                'concepto' => $pago->concepto,
                'fecha_pago' => $fecha_pago,
                'monto' => $monto_format
            ];

            $total += $pago->monto;
            $num++;
        }

        // Consultar pagos secundarios
        $pagosSecundarios = DB::table('pagos_secundarios')
            ->where('corte', $id_corte)
            ->orderBy('id_alumno')
            ->get();

        $dataSecundarios = [];

        foreach ($pagosSecundarios as $pago) {
            $fecha_pago = date('d/M/Y', strtotime($pago->fecha_pago));
            $fecha_pago = strtr($fecha_pago, $meses);
            $fecha_pago = strtoupper($fecha_pago);

            $monto_format = number_format($pago->monto, 2);

            $dataSecundarios[] = [
                'num' => $num,
                'recibo' => $pago->recibo,
                'id_alumno' => $pago->id_alumno,
                'concepto' => $pago->concepto,
                'periodo' => $pago->periodo,
                'fecha_pago' => $fecha_pago,
                'monto' => $monto_format
            ];

            $total += $pago->monto;
            $num++;
        }

        $total_format = number_format($total, 2);

        return response()->json([
            'pagos_programas' => $dataProgramas,
            'pagos_secundarios' => $dataSecundarios,
            'total' => $total_format
        ]);
    }

    public function realizarCorte(Request $request)
    {
       /* // Obtener el usuario de la sesión
        $usuario = Session::get('usuario');

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        // Obtener el id del autor basado en el usuario de la sesión
        $result = DB::table('usuarios')->where('usuario', $usuario)->first();
        if (!$result) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }
        $id_autor = $result->id;*/

        $fecha = now()->format('Y-m-d');
        $total = $request->input('total');

        if ($total != 0) {
            // Insertar en la tabla cortes
            $id_corte = DB::table('cortes')->insertGetId([
                'fecha' => $fecha,
                'id_autor' => "MK09P7",
                'total' => $total
            ]);

            // Actualizar pagos_programas y pagos_secundarios
            DB::table('pagos_programas')->where('corte', '0')->update(['corte' => $id_corte]);
            DB::table('pagos_secundarios')->where('corte', '0')->update(['corte' => $id_corte]);

            // Retornar respuesta JSON con el id del corte
            return response()->json(['id_corte' => $id_corte, 'message' => 'Corte realizado con éxito'], 200);
        } else {
            // Retornar error si no hay ingresos registrados
            return response()->json(['error' => 'No se realizó el corte porque no hay ingresos registrados'], 400);
        }
    
    }
}
