<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    // Mostrar todos los usuarios
    public function index()
    {
        return Usuario::all();
    }

    // Mostrar un usuario específico
    public function show($id)
    {
        return Usuario::find($id);
    }

    // Crear un nuevo usuario
    public function store(Request $request)
    {
        $request['password'] = bcrypt($request->password);
        $usuario = Usuario::create($request->all());
        return response()->json($usuario, 201);
    }

    // Actualizar un usuario existente
    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->update($request->all());
        return response()->json($usuario, 200);
    }

    // Eliminar un usuario
    public function destroy($id)
    {
        Usuario::destroy($id);
        return response()->json(null, 204);
    }
//RECARGOS
    public function agregarRecargos()
    {
        $informacion = DB::table('informacion')->first();
        $titulo = $informacion->nombre_corto;
        $nombre_completo = $informacion->nombre;
        $version = $informacion->version;

        // Definir periodo
        $mes = date("m");
        $anio = date("Y");

        switch ($mes) {
            case '01': $periodo = 'ENERO/'.$anio; break;
            case '02': $periodo = 'FEBRERO/'.$anio; break;
            case '03': $periodo = 'MARZO/'.$anio; break;
            case '04': $periodo = 'ABRIL/'.$anio; break;
            case '05': $periodo = 'MAYO/'.$anio; break;
            case '06': $periodo = 'JUNIO/'.$anio; break;
            case '07': $periodo = 'JULIO/'.$anio; break;
            case '08': $periodo = 'AGOSTO/'.$anio; break;
            case '09': $periodo = 'SEPTIEMBRE/'.$anio; break;
            case '10': $periodo = 'OCTUBRE/'.$anio; break;
            case '11': $periodo = 'NOVIEMBRE/'.$anio; break;
            case '12': $periodo = 'DICIEMBRE/'.$anio; break;
        }

        // Verificar si ya existen recargos para el periodo actual
        $flag = DB::table('periodos_recargos')->where('mes', $mes)->where('anio', $anio)->count();

        if ($flag == 0) {
            $descuento = '0';
            $corte = '0';
            $concepto = 'RECARGO';
            $monto = $informacion->precio_recargo;

            // Obtener fecha del último día del mes
            $dia = date("d", mktime(0, 0, 0, $mes + 1, 0, $anio));

            // Agregar recargos a los alumnos con adeudos
            $alumnos = DB::table('alumnos')->get();
            foreach ($alumnos as $alumno) {
                $id_alumno = $alumno->id_alumno;
                $adeudo = DB::table('adeudos_programas')->where('id_alumno', $id_alumno)->count();

                if ($adeudo != 0) {
                    DB::table('adeudos_secundarios')->insert([
                        'id_alumno' => $id_alumno,
                        'concepto' => $concepto,
                        'periodo' => $periodo,
                        'monto' => $monto,
                        'descuento' => $descuento,
                        'corte' => $corte
                    ]);
                }
            }

            // Insertar el periodo de recargos
            DB::table('periodos_recargos')->insert([
                'mes' => $mes,
                'anio' => $anio
            ]);

            return response()->json(['message' => 'Recargos agregados con éxito'], 200);
        }

        return response()->json(['message' => 'Ya existen recargos para el periodo actual'], 400);
    }
}
