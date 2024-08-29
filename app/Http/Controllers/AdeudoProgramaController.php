<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdeudoPrograma;
use App\Models\Alumno;
use App\Models\RegistroPredefinido;
use App\Models\ProgramaPredefinido;
use App\Models\Clase;
use App\Models\Maestro;

class AdeudoProgramaController extends Controller
{
    public function index()
    {
        // Retorna todos los adeudos de programas
        return AdeudoPrograma::all();
    }

    public function filterByAlumno($id_alumno)
    {
        // Filtra los adeudos de programas por id_alumno e incluye el nombre del alumno y del programa
        $adeudos = AdeudoPrograma::with(['alumno', 'programa'])
                    ->where('id_alumno', $id_alumno)
                    ->get()
                    ->map(function($adeudo) {
                        return [
                            'id_alumno' => $adeudo->id_alumno,
                            'nombre_alumno' => $adeudo->alumno->nombre,
                            'nombre_programa' => $adeudo->programa->nombre,
                            'periodo' => $adeudo->periodo,
                            'concepto' => $adeudo->concepto,
                            'monto' => $adeudo->monto,
                            'beca' => $adeudo->beca,
                            'descuento' => $adeudo->descuento,
                            'fecha_limite' => $adeudo->fecha_limite
                        ];
                    });

        return response()->json($adeudos);
    }

    public function store(Request $request)
    {
        // Valida y guarda el nuevo adeudo de programa
        $request->validate([
            'id_alumno' => 'required',
            'id_programa' => 'required',
            'periodo' => 'required',
            'concepto' => 'required',
            'monto' => 'required',
            'beca' => 'required',
            'descuento' => 'required',
            'fecha_limite' => 'required',
        ]);

        return AdeudoPrograma::create($request->all());
    }

    public function show($id)
    {
        // Retorna un adeudo de programa específico por su ID
        return AdeudoPrograma::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        // Valida y actualiza el adeudo de programa
        $adeudoPrograma = AdeudoPrograma::findOrFail($id);

        $request->validate([
            'id_alumno' => 'required',
            'id_programa' => 'required',
            'periodo' => 'required',
            'concepto' => 'required',
            'monto' => 'required',
            'beca' => 'required',
            'descuento' => 'required',
            'fecha_limite' => 'required',
        ]);

        $adeudoPrograma->update($request->all());

        return $adeudoPrograma;
    }

    public function destroy($id)
    {
        // Elimina un adeudo de programa
        $adeudoPrograma = AdeudoPrograma::findOrFail($id);
        $adeudoPrograma->delete();

        return response()->json(['message' => 'Adeudo de programa eliminado correctamente']);
    }

   
}
