<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Alumno;
use App\Models\ProgramaPredefinido;
use App\Models\AdeudoFragmentado;
use App\Models\Maestro;
use App\Models\Clase;
use App\Models\AdeudoPrograma;

class DescuentosController extends Controller
{
//Info de configurar descuento
    public function getconfigurarDescuento(Request $request)
    {
        $id_alumno = $request->input('id_alumno');
        $id_programa = $request->input('id_programa');
        $periodo = $request->input('periodo');

        // Obtener la información del alumno
        $alumno = Alumno::find($id_alumno);
        if (!$alumno) {
            return response()->json(['error' => 'Alumno no encontrado'], 404);
        }
        $nombre_alumno = $alumno->nombre;

        // Obtener la información del programa
        $programa = ProgramaPredefinido::where('id_programa', $id_programa)->first();
        if (!$programa) {
            return response()->json(['error' => 'Programa no encontrado'], 404);
        }
        $nombre_programa = $programa->nombre;

        $response = [
            'nombre_alumno' => $nombre_alumno,
            'nombre_programa' => $nombre_programa,
        ];

        if ($id_programa != '000') {
            $response['clases'] = [];
            $adeudos_fragmentados = AdeudoFragmentado::where('id_alumno', $id_alumno)
                ->where('id_programa', $id_programa)
                ->where('periodo', $periodo)
                ->get();

            foreach ($adeudos_fragmentados as $adeudo) {
                $id_clase = $adeudo->id_clase;
                $id_maestro = $adeudo->id_maestro;

                $maestro = Maestro::find($id_maestro);
                $nombre_maestro = $maestro ? $maestro->nombre_titular : 'Desconocido';

                $clase = Clase::find($id_clase);
                $nombre_clase = $clase ? $clase->nombre : 'Desconocido';

                $response['clases'][] = [
                    'nombre_clase' => $nombre_clase,
                    'nombre_maestro' => $nombre_maestro,
                ];
            }

            $adeudo_programa = AdeudoPrograma::where('id_alumno', $id_alumno)
                ->where('id_programa', $id_programa)
                ->where('periodo', $periodo)
                ->first();

            if ($adeudo_programa) {
                $response['precio_actual'] = $adeudo_programa->monto;
                $response['descuento'] = $adeudo_programa->descuento;
            }
        }

        return response()->json($response);
    
    }

    public function updateDescuento(Request $request)
{
    $id_alumno = $request->input('id_alumno');
    $id_programa = $request->input('id_programa');
    $periodo = $request->input('periodo');
    $precio_actual = $request->input('precio_actual');
    $precio_original = $request->input('precio_original');
    $descuento = $request->input('descuento');
    $precio_descuento = $request->input('precio_descuento');
    $tipo = $request->input('tipo');
    $observaciones = strtoupper($request->input('observaciones'));
    $fecha = date("Y-m-d");
    $hora = date("H:i:s");

    // Actualizar el monto y el descuento en la tabla 'adeudos_programas'
    DB::table('adeudos_programas')
        ->where('id_alumno', $id_alumno)
        ->where('id_programa', $id_programa)
        ->where('periodo', $periodo)
        ->where('monto', $precio_actual)
        ->update(['monto' => $precio_descuento, 'descuento' => $descuento]);

    // Obtener y actualizar los montos fragmentados en la tabla 'adeudos_fragmentados'
    $adeudosFragmentados = DB::table('adeudos_fragmentados')
        ->where('id_alumno', $id_alumno)
        ->where('id_programa', $id_programa)
        ->where('periodo', $periodo)
        ->get();

    foreach ($adeudosFragmentados as $adeudo) {
        $id_clase = $adeudo->id_clase;
        $id_maestro = $adeudo->id_maestro;
        $porcentaje = Clase::find($id_clase)->porcentaje;
        $monto = ($precio_descuento * $porcentaje) / 100;

        DB::table('adeudos_fragmentados')
            ->where('id_alumno', $id_alumno)
            ->where('id_programa', $id_programa)
            ->where('id_clase', $id_clase)
            ->where('periodo', $periodo)
            ->where('id_maestro', $id_maestro)
            ->update(['monto' => $monto]);
    }

    // Insertar el registro del descuento en la tabla 'registro_descuentos'
    DB::table('registro_descuentos')->insert([
        'id_alumno' => $id_alumno,
        'id_programa' => $id_programa,
        'periodo' => $periodo,
        'precio_orig' => $precio_original,
        'descuento' => $descuento,
        'precio_final' => $precio_descuento,
        'tipo' => $tipo,
        'observaciones' => $observaciones,
        'fecha' => $fecha
    ]);

    return response()->json(['message' => 'Datos actualizados correctamente'], 200);
}
}