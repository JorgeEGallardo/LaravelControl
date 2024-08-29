<?php

namespace App\Http\Controllers;

use App\Models\Maestro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 

class MaestroController extends Controller
{
    // Mostrar todos los maestros
    public function index()
    {
        return Maestro::all();
    }
    public function obtenerMaestros()
    {
        // Obtener los maestros y sus clases asociadas
        $maestros = DB::table('maestros')
            ->leftJoin('clases', 'maestros.id_maestro', '=', 'clases.id_maestro')
            ->select('maestros.id_maestro', 'maestros.nombre', 'clases.nombre as nombre_clase')
            ->where('maestros.status', '=', '1')
            ->orderBy('maestros.nombre')
            ->get();

        // Organizar la información para que cada maestro tenga sus clases en un array
        $resultado = [];
        foreach ($maestros as $maestro) {
            if (!isset($resultado[$maestro->id_maestro])) {
                $resultado[$maestro->id_maestro] = [
                    'id_maestro' => $maestro->id_maestro,
                    'nombre_maestro' => $maestro->nombre,
                    'clases' => []
                ];
            }
            $resultado[$maestro->id_maestro]['clases'][] = $maestro->nombre_clase;
        }

        // Convertir el resultado en un array de objetos
        $resultado = array_values($resultado);

        return response()->json($resultado);
    }
    // Mostrar un maestro específico
    public function show($id)
    {
        return Maestro::find($id);
    }

    // Crear un nuevo maestro
    public function store(Request $request)
    {
        //generar el ID de Maestro
        $nombre = $request->input('nombre');
        $random1=rand(0,99);
		$random2=rand(0,9);
		$random1=str_pad($random1, 2, "0", STR_PAD_LEFT);
		$letra = substr ($nombre, 0, 1);
		$id='MK'.$random1.$letra.$random2;

        try {
        $validatedData = $request;

        $validatedData['id_maestro'] = $id;

        $maestro = Maestro::create($request->all());
        return response()->json($maestro, 201);
        }catch (\Exception $e) {
            // Registrar el error
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Actualizar un maestro existente
    public function update(Request $request, $id)
    {
        $maestro = Maestro::findOrFail($id);
        $maestro->update($request->all());
        return response()->json($maestro, 200);
    }

    // Eliminar un maestro
    public function destroy($id)
    {
        Maestro::destroy($id);
        return response()->json(null, 204);
    }
    
    public function actualizarStatus(Request $request, $id)
    {
        // Verificar si el maestro tiene clases asignadas
        $filas = DB::table('clases')
            ->where('id_maestro', $id)
            ->count();

        if ($filas == 0) {
            // Actualizar el estado del maestro a '0'
            DB::table('maestros')
                ->where('id_maestro', $id)
                ->update(['status' => '0']);
            
            return response()->json(['message' => 'Estado del maestro actualizado exitosamente'], 200);
        } else {
            return response()->json(['error' => 'No puedes eliminar el maestro porque tiene clases cargadas'], 400);
        }
    }
}

