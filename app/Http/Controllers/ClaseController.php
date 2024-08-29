<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ProgramaPredefinido;
use App\Models\Maestro;
use App\Models\RegistroPredefinido;
use App\Models\Alumno;
use Carbon\Carbon;

class ClaseController extends Controller
{
    // Mostrar todas las clases
    public function index()
    {
        $programas = ProgramaPredefinido::where('ocultar', 0)
        ->orderBy('nombre')
        ->get();

        $resultado = [];

        foreach ($programas as $programa) {
            $clases = Clase::leftJoin('maestros', 'clases.id_maestro', '=', 'maestros.id_maestro')
                            ->select('clases.*', 'maestros.nombre_titular', 'maestros.nombre as nombre_maestro', 'maestros.direccion', 'maestros.fecha_nac', 'maestros.rfc', 'maestros.celular', 'maestros.status as status_maestro')
                            ->where('clases.id_programa', $programa->id_programa)
                            ->orderBy('clases.porcentaje', 'DESC')
                            ->get();

            $clasesArreglo = $clases->toArray();

            $resultado[] = [
                'id_programa' => $programa->id_programa,
                'nombre_programa' => $programa->nombre,
                'mensualidad' => $programa->mensualidad,
                'nivel' => $programa->nivel,
                'complex' => $programa->complex,
                'status' => $programa->status,
                'ocultar' => $programa->ocultar,
                'clases' => $clasesArreglo
            ];
        }

        return response()->json($resultado);

    }

    // Mostrar una clase específica
    public function show($id)
    {
        return Clase::find($id);
    }

    // Actualizar una clase existente
    /*public function update(Request $request, $id)
    {
        $clase = Clase::findOrFail($id);
        $clase->update($request->all());
        return response()->json($clase, 200);
    }

    // Eliminar una clase
    public function destroy($id)
    {
        Clase::destroy($id);
        return response()->json(null, 204);
    }*/
//Crear Clase
public function store(Request $request)
{
    // Obtiene los datos del formulario
    $campos = $request->input('campos'); // Número de clases
    $nombre = strtoupper($request->input('nombre'));
    $mensualidad = $request->input('mensualidad');
    $complex = $request->input('complex') === 'Yes' ? '1' : '0'; // Convertir a '1' o '0'
    $nivel = strtoupper($request->input('nivel'));
    $status = '1';
    $ocultar = '0';

    $nivelString = strtoupper($request->input('nivel'));

    // Convertir el nivel a un valor entero basado en el texto
    $niveles = [
        'INFANTIL' => '001',
        'ADULTOS' => '002',
        'MULTINIVEL' => '003',
        'PRINCIPIANTE' => '004',
        'INTERMEDIO' => '005',
        'INTERMEDIO/AVANZADO' => '006',
        'AVANZADO' => '007'
    ];

    $nivel = $niveles[$nivelString] ?? '000'; // Valor por defecto si el nivel no está en el array


    // Verificar si el nombre del programa ya existe
    $programaExistente = DB::table('programas_predefinidos')->where('nombre', $nombre)->first();

    if (!$programaExistente) {
        // Insertar el nuevo programa
        $id_programa = DB::table('programas_predefinidos')->insertGetId([
            'nombre' => $nombre,
            'mensualidad' => $mensualidad,
            'nivel' => $nivel,
            'complex' => $complex,
            'status' => $status,
            'ocultar' => $ocultar
        ]);

        // Insertar las clases asociadas al programa
        foreach ($request->input('clases') as $index => $clase) {
            $claseNombre = strtoupper($clase['clase']);
            $maestro = $clase['maestro'];
            $informacion = strtoupper($clase['informacion']);
            $porcentaje = $clase['porcentaje'];
            $personal = $clase['personal'] ?? '0'; // Valor por defecto '0'

            DB::table('clases')->insert([
                'id_programa' => $id_programa,
                'nombre' => $claseNombre,
                'id_maestro' => $maestro,
                'informacion' => $informacion,
                'porcentaje' => $porcentaje,
                'personal' => $personal
            ]);
        }

        return response()->json('Programa creado exitosamente', 200);
    } else {
        return response()->json('El programa de clases ' . $nombre . ' ya se encuentra registrado en el sistema', 400);
    }
}
// lista de alumnos por clase
public function obtenerDatos($id_programa, $id_clase = null)
{
    // Obtener los datos del programa
    $programa = ProgramaPredefinido::where('id_programa', $id_programa)->first();

    // Verificar si el programa existe
    if (!$programa) {
        return response()->json(['error' => 'Programa no encontrado'], 404);
    }

    // Inicializar las variables para almacenar las clases y los alumnos
    $clasesList = [];
    $alumnosList = [];

    // Si se proporciona el id_clase, buscar solo esa clase
    if ($id_clase !== null) {
        $clase = Clase::leftJoin('maestros', 'clases.id_maestro', '=', 'maestros.id_maestro')
            ->select('clases.*', 'maestros.nombre_titular', 'maestros.nombre as nombre_maestro')
            ->where('clases.id_programa', $id_programa)
            ->where('clases.id_clase', $id_clase)
            ->orderBy('clases.porcentaje', 'DESC')
            ->first();

        if ($clase) {
            $clasesList[] = $clase;
        }
    } else {
        // Obtener todas las clases del programa
        $clases = Clase::leftJoin('maestros', 'clases.id_maestro', '=', 'maestros.id_maestro')
            ->select('clases.*', 'maestros.nombre_titular', 'maestros.nombre as nombre_maestro')
            ->where('clases.id_programa', $id_programa)
            ->orderBy('clases.porcentaje', 'DESC')
            ->get();

        $clasesList = $clases->toArray();
    }

    // Si se proporciona el id_clase o si no hay clases disponibles, buscar los alumnos
    if ($id_clase !== null || empty($clasesList)) {
        $alumnos = RegistroPredefinido::where('id_programa', $id_programa)
            ->orderBy('id_alumno')
            ->get();

        foreach ($alumnos as $registro) {
            $alumno = Alumno::where('id_alumno', $registro->id_alumno)->first();

            if ($alumno) {
                $alumnosList[] = [
                    'id_alumno' => $alumno->id_alumno,
                    'nombre' => $alumno->nombre,
                    'celular' => $alumno->celular
                ];
            }
        }
    }

    $resultado = [
        'id_programa' => $programa->id_programa,
        'nombre_programa' => $programa->nombre,
        'mensualidad' => $programa->mensualidad,
        'clases' => $clasesList,
        'alumnos' => $alumnosList
    ];

    return response()->json($resultado);
}
//actualizar
public function update(Request $request, $id)
    {
        $nombre = strtoupper($request->input('nombre'));
        $maestro = $request->input('maestro');
        $informacion = $request->input('informacion');


        $existingClase = Clase::where('nombre', $nombre)->where('id_clase', '!=', $id)->first();

        if (!$existingClase) {
            Clase::where('id_clase', $id)->update([
                'nombre' => $nombre,
                'id_maestro' => $maestro,
                'informacion'=> $informacion,

            ]);

            return response()->json('success', 200);
        } else {
            return response()->json(['La clase ' . $nombre . ' ya se encuentra registrada en el sistema'], 400);
        }
    }

//Mostrar informacion del alumno para la beca
    public function mostrarInformacionAlumno(Request $request)
    {
        $id_alumno = $request->input('id_alumno');
        $id_programa = $request->input('id_programa');
        $periodo = $request->input('periodo');

        $alumno = DB::table('alumnos')->where('id_alumno', $id_alumno)->first();
        if (!$alumno) {
            return response()->json(['error' => 'Alumno no encontrado'], 404);
        }
        $nombre_alumno = $alumno->nombre;

        $programa = DB::table('programas_predefinidos')->where('id_programa', $id_programa)->first();
        if (!$programa) {
            return response()->json(['error' => 'Programa no encontrado'], 404);
        }
        $nombre_programa = $programa->nombre;

        $data = [
            'nombre_alumno' => $nombre_alumno,
            'nombre_programa' => $nombre_programa,
            'periodo' => $periodo,
            'beca' => 0,
            'precio_actual' => 0,
            'precio_original' => 0,
        ];

        if ($id_programa != '000') {
            $adeudos_fragmentados = DB::table('adeudos_fragmentados')
                ->where('id_alumno', $id_alumno)
                ->where('id_programa', $id_programa)
                ->where('periodo', $periodo)
                ->get();

            $clases = [];
            foreach ($adeudos_fragmentados as $adeudo) {
                $clase = DB::table('clases')->where('id_clase', $adeudo->id_clase)->first();
                $maestro = DB::table('maestros')->where('id_maestro', $adeudo->id_maestro)->first();
                $clases[] = [
                    'nombre_clase' => $clase->nombre,
                    'nombre_maestro' => $maestro->nombre_titular,
                ];
            }

            $data['clases'] = $clases;

            $adeudo_programa = DB::table('adeudos_programas')
                ->where('id_alumno', $id_alumno)
                ->where('id_programa', $id_programa)
                ->where('periodo', $periodo)
                ->first();

            if ($adeudo_programa) {
                $data['precio_actual'] = $adeudo_programa->monto;
                $data['beca'] = $adeudo_programa->beca;
                $data['precio_original'] = $adeudo_programa->beca == 0
                    ? $adeudo_programa->monto
                    : ($adeudo_programa->monto / (100 - $adeudo_programa->beca)) * 100;
            }
        } else {
            // Aquí iría la lógica de inscripciones y recargos si fuera necesario
        }

        return response()->json($data);
    }
//Crea la Beca
    public function actualizarBeca(Request $request)
    {
        $id_alumno = $request->input('id_alumno');
        $id_programa = $request->input('id_programa');
        $periodo = $request->input('periodo');
        $precio_actual = $request->input('precio_actual');
        $precio_original = $request->input('precio_original');
        $beca = $request->input('beca');
        $precio_beca = $request->input('precio_descuento'); // Nombre del campo según el formulario
        $tipo = $request->input('tipo');
        $observaciones = strtoupper($request->input('observaciones'));
        $fecha = Carbon::now()->format('Y-m-d');

        // Verificar si el alumno tiene adeudos en el programa y periodo especificados
        $adeudo = DB::table('adeudos_programas')
            ->where('id_alumno', $id_alumno)
            ->where('id_programa', $id_programa)
            ->where('periodo', $periodo)
            ->where('monto', $precio_actual)
            ->first();

        if ($adeudo) {
            // Actualizar el monto y beca en adeudos_programas
            DB::table('adeudos_programas')
                ->where('id_alumno', $id_alumno)
                ->where('id_programa', $id_programa)
                ->where('periodo', $periodo)
                ->where('monto', $precio_actual)
                ->update(['monto' => $precio_beca, 'beca' => $beca]);

            // Verificar si hay descuento y actualizarlo
            if ($adeudo->descuento != 0) {
                DB::table('adeudos_programas')
                    ->where('id_alumno', $id_alumno)
                    ->where('id_programa', $id_programa)
                    ->where('periodo', $periodo)
                    ->where('monto', $precio_actual)
                    ->update(['descuento' => 0]);
            }

            // Actualizar los adeudos fragmentados
            $adeudos_fragmentados = DB::table('adeudos_fragmentados')
                ->where('id_alumno', $id_alumno)
                ->where('id_programa', $id_programa)
                ->where('periodo', $periodo)
                ->get();

            foreach ($adeudos_fragmentados as $fragmento) {
                $clase = DB::table('clases')->where('id_clase', $fragmento->id_clase)->first();
                if ($clase) {
                    $monto = ($precio_beca * $clase->porcentaje) / 100;

                    DB::table('adeudos_fragmentados')
                        ->where('id_alumno', $id_alumno)
                        ->where('id_programa', $id_programa)
                        ->where('id_clase', $fragmento->id_clase)
                        ->where('periodo', $periodo)
                        ->where('id_maestro', $fragmento->id_maestro)
                        ->update(['monto' => $monto]);
                }
            }
        }

        // Actualizar el registro predefinido
        DB::table('registro_predefinido')
            ->where('id_alumno', $id_alumno)
            ->where('id_programa', $id_programa)
            ->where('precio', $precio_actual)
            ->update(['precio' => $precio_beca, 'beca' => $beca]);

        // Insertar en registro_becas
        DB::table('registro_becas')->insert([
            'id_alumno' => $id_alumno,
            'id_programa' => $id_programa,
            'periodo' => $periodo,
            'precio_orig' => $precio_original,
            'beca' => $beca,
            'precio_final' => $precio_beca,
            'tipo' => $tipo,
            'observaciones' => $observaciones,
            'fecha' => $fecha
        ]);

        return response()->json(['message' => 'Beca actualizada con éxito'], 200);
    }
}
