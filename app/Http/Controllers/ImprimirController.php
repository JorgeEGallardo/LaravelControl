<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 

class ImprimirController extends Controller
{
    public function buscarAlumnos(Request $request)
    {
        $fix = $request->input('fix');
        $id = $request->input('id');
        $nombre = $request->input('nombre');
        $clase = $request->input('clase');
        $status = $request->input('status');

        $fecha = Carbon::now();
        $ano = $fecha->year;
        $mes = strtoupper($fecha->format('M'));
        $dia = $fecha->day;

        $meses = ['Jan' => 'ENE', 'Apr' => 'ABR', 'Aug' => 'AGO', 'Dec' => 'DIC'];
        $mes = $meses[$mes] ?? $mes;

        $fechaDia = sprintf("%02d/%s/%04d", $dia, $mes, $ano);

        $query = DB::table('alumnos');

        if ($fix == 1) {
            $query->where('id_alumno', 'LIKE', '%' . $id . '%')
                  ->where('nombre', 'LIKE', '%' . $nombre . '%')
                  ->where('status', 'LIKE', '%' . $status . '%');
        } elseif ($fix == 0) {
            $query->where('nombre', 'LIKE', '%' . $nombre . '%');
            if ($query->count() == 0) {
                $query->where('id_alumno', 'LIKE', '%' . $nombre . '%');
                $id = $nombre;
                $nombre = "";
            }
        } elseif ($fix == 2) {
            $query->where('status', $status);
        }

        $query->orderBy('nombre');
        $alumnos = $query->get();

        $response = [];
        foreach ($alumnos as $alumno) {
            $materias = DB::table('registro_clases')
                ->join('clases', 'registro_clases.id_clase', '=', 'clases.id_clase')
                ->where('registro_clases.id_alumno', $alumno->id_alumno)
                ->pluck('clases.nombre')
                ->implode(', ');

            if ($clase) {
                $registro = DB::table('registro_clases')
                    ->where('id_clase', $clase)
                    ->where('id_alumno', $alumno->id_alumno)
                    ->first();

                if ($registro) {
                    $response[] = [
                        'id' => $alumno->id_alumno,
                        'nombre' => $alumno->nombre,
                        'materias' => $materias,
                        'celular' => $alumno->celular,
                        'status' => $alumno->status == 1 ? 'ACTIVO' : 'INACTIVO',
                    ];
                }
            } else {
                $response[] = [
                    'id' => $alumno->id_alumno,
                    'nombre' => $alumno->nombre,
                    'materias' => $materias,
                    'celular' => $alumno->celular,
                    'status' => $alumno->status == 1 ? 'ACTIVO' : 'INACTIVO',
                ];
            }
        }

        if (empty($response)) {
            return response()->json(['message' => 'LA BÚSQUEDA NO ARROJÓ RESULTADOS'], 404);
        }

        return response()->json($response);
    }

//Consultar deudores
    public function consultarDeudores(Request $request)
    {
        $id_alumno = $request->input('id');
        $nombre = $request->input('nombre');
        $id_programa = $request->input('programa');
        $mes = $request->input('mes');
        $anio = $request->input('anio');

        if ($mes != '' && $anio != '') {
            $periodo = $mes . "/" . $anio;
        } else {
            $periodo = '';
        }

        if (empty($nombre)) {
            $query = DB::select("SELECT * FROM adeudos_programas WHERE id_alumno LIKE '%$id_alumno%' AND id_programa LIKE '%$id_programa%' AND periodo LIKE '%$periodo%' ORDER BY id_programa DESC, periodo, concepto, id_alumno, fecha_limite DESC");
            $filas = count($query);
            $flag = 0;
        } else {
            $query2 = DB::table('alumnos')->where('nombre', 'like', '%' . $nombre . '%')->orderBy('nombre')->get();
            
            $filas = count($query2);
            $flag = 1;
        }

        if ($filas != 0) {
            $query = DB::select("SELECT * FROM adeudos_programas WHERE id_alumno LIKE '%$id_alumno%' AND id_programa LIKE '%$id_programa%' AND periodo LIKE '%$periodo%' ORDER BY id_programa DESC, id_alumno, fecha_limite");

            $num = 0;
            $data = [];

            foreach ($query as $result) {
                $id_alumno = $result->id_alumno;
                $id_programa = $result->id_programa;
                $periodo = $result->periodo;
                $monto = $result->monto;
                $fecha_limite = $result->fecha_limite;

                $query2 = DB::table('alumnos')->where('id_alumno', $id_alumno)->first();
                $nombre = $query2->nombre;
                $celular = $query2->celular;
                $telefono = $query2->telefono;
                $telefono_2 = $query2->telefono_2;

                $query3 = DB::table('programas_predefinidos')->where('id_programa', $id_programa)->first();
                $nombre_programa = $query3->nombre;

                // Transformar la fecha_limite si es necesario
                // ...

                $data[] = [
                    'num' => ++$num,
                    'id_alumno' => $id_alumno,
                    'nombre' => $nombre,
                    'nombre_programa' => $nombre_programa,
                    'periodo' => $periodo,
                    'monto' => $monto,
                    'fecha_limite' => $fecha_limite,
                    'celular' => $celular,
                    'telefono' => $telefono,
                    'telefono_2' => $telefono_2,
                ];
            }

            return response()->json(['data' => $data]);
        } else {
            return response()->json(['message' => 'No hay resultados']);
        }
    }
}
