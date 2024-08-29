<?php

namespace App\Http\Controllers;

use App\Models\Nomina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NominaController extends Controller
{
    // Mostrar todas las nóminas
    public function index()
    {
        return Nomina::all();
    }

    // Mostrar una nómina específica
    public function show($id)
    {
        return Nomina::find($id);
    }

    // Crear una nueva nómina
    public function store(Request $request)
    {
        $nomina = Nomina::create($request->all());
        return response()->json($nomina, 201);
    }

    // Actualizar una nómina existente
    public function update(Request $request, $id)
    {
        $nomina = Nomina::findOrFail($id);
        $nomina->update($request->all());
        return response()->json($nomina, 200);
    }

    // Eliminar una nómina
    public function destroy($id)
    {
        Nomina::destroy($id);
        return response()->json(null, 204);
    }
//NOMINAS
    public function mostrarNominas(Request $request)
    {
        $anio = $request->query('anio');
        $totalfinal = 0;
        $num = 1;

        $nominas = DB::table('nominas')
            ->whereYear('fecha', $anio)
            ->orderBy('fecha', 'DESC')
            ->get();

        $filas = $nominas->count();

        if ($filas != 0) {
            $response = [];
            foreach ($nominas as $result) {
                $id_nomina = $result->id_nomina;
                $fecha = $result->fecha;
                $id_autor = $result->id_autor;
                $clases = number_format($result->clases, 2);
                $inscripciones = number_format($result->inscripciones, 2);
                $recargos = number_format($result->recargos, 2);
                $total = number_format($result->total, 2);
                $total_neto = number_format($result->total_neto, 2);

                $ano = date("Y", strtotime($fecha));
                $mes = date("M", strtotime($fecha));
                $dia = date("d", strtotime($fecha));

                $meses = [
                    'Jan' => 'ENERO', 'Feb' => 'FEBRERO', 'Mar' => 'MARZO',
                    'Apr' => 'ABRIL', 'May' => 'MAYO', 'Jun' => 'JUNIO',
                    'Jul' => 'JULIO', 'Aug' => 'AGOSTO', 'Sep' => 'SEPTIEMBRE',
                    'Oct' => 'OCTUBRE', 'Nov' => 'NOVIEMBRE', 'Dec' => 'DICIEMBRE'
                ];

                $mes = $meses[$mes] ?? $mes;
                $fecha = strtoupper("$dia DE $mes DEL $ano");

                $autor = DB::table('usuarios')->where('id', $id_autor)->value('nombre');

                $response[] = [
                    'num' => $num,
                    'fecha' => $fecha,
                    'autor' => $autor,
                    'clases' => $clases,
                    'inscripciones' => $inscripciones,
                    'recargos' => $recargos,
                    'total' => $total,
                    'total_neto' => $total_neto,
                    'id_nomina' => $id_nomina
                ];

                $num++;
                $totalfinal += $result->total;
            }

            return response()->json([
                'data' => $response,
                'totalfinal' => number_format($totalfinal, 2)
            ], 200);
        } else {
            return response()->json([
                'message' => 'NO HAY NÓMINAS REALIZADAS'
            ], 404);
        }
    }

    public function mostrarAnios()
    {
        $anios = DB::table('nominas')
            ->selectRaw('DISTINCT YEAR(fecha) as anio')
            ->orderBy('fecha', 'DESC')
            ->get();

        return response()->json($anios, 200);
    }
//GENERAR NOMINA
    public function generarNomina(Request $request)
    {
        $anio = date("Y");
        $fecha = date("Y-m-d");
        $gran_total_clases = 0;
        $gran_total_comisiones = 0;
        $gran_total_inscripciones = 0;
        $gran_total_recargos = 0;
        $porcentaje_comision = 0.1; // Asumiendo un valor de comisión

        $filas = DB::table('pagos_fragmentados')->where('nomina', '0')->count();
        $filas_2 = DB::table('pagos_secundarios')->where('nomina', '0')->count();

        if ($filas != 0 || $filas_2 != 0) {
            if ($filas != 0) {
                $programas = DB::table('programas_predefinidos')->orderBy('id_programa')->get();

                foreach ($programas as $programa) {
                    $id_programa = $programa->id_programa;

                    $clases = DB::table('clases')->where('id_programa', $id_programa)->get();
                    foreach ($clases as $clase) {
                        $id_maestro = $clase->id_maestro;
                        $id_clase = $clase->id_clase;
                        $total_clase = 0;

                        $pagos_fragmentados = DB::table('pagos_fragmentados')
                            ->where('id_clase', $id_clase)
                            ->where('nomina', 0)
                            ->get();

                        foreach ($pagos_fragmentados as $pago) {
                            $monto = $pago->monto;
                            $total_clase += $monto;
                        }

                        $total_comision = $total_clase * $porcentaje_comision;
                        $total_neto = $total_clase - $total_comision;

                        $gran_total_clases += $total_clase;
                        $gran_total_comisiones += $total_comision;

                        DB::table('registro_nominas')->insert([
                            'id_nomina' => null,
                            'id_maestro' => $id_maestro,
                            'id_clase' => $id_clase,
                            'total' => $total_clase,
                            'comision' => $total_comision,
                            'total_neto' => $total_neto
                        ]);
                    }
                }
            }

            if ($filas_2 != 0) {
                $pagos_inscripciones = DB::table('pagos_secundarios')
                    ->where('concepto', 'INSCRIPCION')
                    ->where('nomina', 0)
                    ->get();

                foreach ($pagos_inscripciones as $pago) {
                    $monto = $pago->monto;
                    $gran_total_inscripciones += $monto;
                }

                $pagos_recargos = DB::table('pagos_secundarios')
                    ->where('concepto', 'RECARGO')
                    ->where('nomina', 0)
                    ->get();

                foreach ($pagos_recargos as $pago) {
                    $monto = $pago->monto;
                    $gran_total_recargos += $monto;
                }
            }

            $ingresos_totales = $gran_total_clases + $gran_total_inscripciones + $gran_total_recargos;
            $gran_total_neto = $ingresos_totales - $gran_total_comisiones;

            DB::table('nominas')->insert([
                'id_nomina' => null,
                'fecha' => $fecha,
                'id_autor' => $request->user()->id,
                'clases' => $gran_total_clases,
                'inscripciones' => $gran_total_inscripciones,
                'recargos' => $gran_total_recargos,
                'total' => $ingresos_totales,
                'comisiones' => $gran_total_comisiones,
                'total_neto' => $gran_total_neto,
                'porcentaje_comision' => $porcentaje_comision
            ]);

            $id_nomina = DB::getPdo()->lastInsertId();

            DB::table('pagos_fragmentados')->where('nomina', 0)->update(['nomina' => $id_nomina]);
            DB::table('pagos_secundarios')->where('nomina', 0)->update(['nomina' => $id_nomina]);

            return response()->json([
                'message' => 'Nómina generada exitosamente.',
                'id_nomina' => $id_nomina,
                'fecha' => $fecha,
                'ingresos_totales' => $ingresos_totales,
                'gran_total_neto' => $gran_total_neto,
                'gran_total_comisiones' => $gran_total_comisiones
            ], 200);
        } else {
            return response()->json([
                'message' => 'No se puede realizar el corte porque no hay ingresos aplicables registrados.'
            ], 400);
        }
    }
//INFORME DE NÓMINA GENERAL
    public function informeNomina($id_nomina)
        {
            $nominaInfo = DB::table('nominas')
            ->where('id_nomina', $id_nomina)
            ->select('fecha', 'id_autor')
            ->first();

            $fechanomina = $nominaInfo->fecha;
            $id_autor = $nominaInfo->id_autor;

            $autor = DB::table('usuarios')
                ->where('id', $id_autor)
                ->value('nombre');
        // Format Spanish Date
        $fechanomina = DB::table('nominas')->where('id_nomina', $id_nomina)->value('fecha');
        $ano = date("Y", strtotime($fechanomina));
        $mes = date("M", strtotime($fechanomina));
        $dia = date("d", strtotime($fechanomina));

        $meses = [
            'Jan' => 'ENERO',
            'Feb' => 'FEBRERO',
            'Mar' => 'MARZO',
            'Apr' => 'ABRIL',
            'May' => 'MAYO',
            'Jun' => 'JUNIO',
            'Jul' => 'JULIO',
            'Aug' => 'AGOSTO',
            'Sep' => 'SEPTIEMBRE',
            'Oct' => 'OCTUBRE',
            'Nov' => 'NOVIEMBRE',
            'Dec' => 'DICIEMBRE'
        ];

        $mes = $meses[$mes] ?? $mes;
        $fechanomina = strtoupper($dia . " DE " . $mes . " DEL " . $ano);

        // Get data for the report
        $maestros = DB::table('registro_nominas')
                        ->where('id_nomina', $id_nomina)
                        ->select('id_maestro')
                        ->distinct()
                        ->orderBy('id_clase')
                        ->get();

        $data = [];

        foreach ($maestros as $maestro) {
            $id_maestro = $maestro->id_maestro;
            $totalmaestro = 0;
            $totalgenerado = 0;

            $nombre_maestro = DB::table('maestros')->where('id_maestro', $id_maestro)->value('nombre');

            $registros = DB::table('registro_nominas')
                            ->where('id_maestro', $id_maestro)
                            ->where('id_nomina', $id_nomina)
                            ->get();

            $clases = [];
            foreach ($registros as $registro) {
                $id_clase = $registro->id_clase;
                $total = $registro->total;
                $comision = $registro->comision;    

                $totalgenerado += $total;
                $totalmaestro += $comision;

                $nombre_clase = DB::table('clases')->where('id_clase', $id_clase)->value('nombre');
                $id_programa = DB::table('clases')->where('id_clase', $id_clase)->value('id_programa');
                $nombre_programa = DB::table('programas_predefinidos')->where('id_programa', $id_programa)->value('nombre');
                $transacciones = DB::table('pagos_fragmentados')
                                    ->where('nomina', $id_nomina)
                                    ->where('id_programa', $id_programa)
                                    ->where('id_clase', $id_clase)
                                    ->count();

                $clases[] = [
                    'nombre_clase' => $nombre_clase,
                    'nombre_programa' => $nombre_programa,
                    'transacciones' => $transacciones,
                    'total' => number_format($total, 2),
                    'comision' => number_format($comision, 2)
                ];
            }

            $data[] = [
                'nombre_maestro' => $nombre_maestro,
                'clases' => $clases,
                'totalgenerado' => number_format($totalgenerado, 2),
                'totalmaestro' => number_format($totalmaestro, 2)
            ];
        }

        // Get totals
        $nomina = DB::table('nominas')->where('id_nomina', $id_nomina)->first();
        $totals = [
            'mensualidades' => number_format($nomina->clases, 2),
            'inscripciones' => number_format($nomina->inscripciones, 2),
            'recargos' => number_format($nomina->recargos, 2),
            'total' => number_format($nomina->total, 2),
            'comisiones' => number_format($nomina->comisiones, 2),
            'total_neto' => number_format($nomina->total_neto, 2)
        ];

        return response()->json([
            'fechanomina' => $fechanomina,
            'nombre'=> $autor,
            'folio'=> $id_nomina,
            'data' => $data,
            'totals' => $totals
        ]);
    }
}
