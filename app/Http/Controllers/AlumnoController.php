<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BajaAlumno;
use PDF;
use Illuminate\Support\Facades\Storage;

class AlumnoController extends Controller
{
    // Mostrar todos los alumnos
    public function index()
    {
        return Alumno::all();
    }
//lista de alumnos
public function mostrarDatosCombinados(Request $request)
{
    $status = $request->input('status');

    $alumnos = Alumno::leftJoin('registro_predefinido', 'alumnos.id_alumno', '=', 'registro_predefinido.id_alumno')
    ->leftJoin('programas_predefinidos', 'registro_predefinido.id_programa', '=', 'programas_predefinidos.id_programa')
    ->select('alumnos.id_alumno', 'alumnos.nombre', 'alumnos.celular')
    ->selectRaw('GROUP_CONCAT(programas_predefinidos.nombre ORDER BY programas_predefinidos.nombre ASC SEPARATOR ", ") as nombre_programa')
    ->when($status !== null, function ($query) use ($status) {
        return $query->where('alumnos.status', $status);
    })
    ->groupBy('alumnos.id_alumno', 'alumnos.nombre', 'alumnos.celular')
    ->orderBy('alumnos.nombre', 'asc')
    ->get();
    return response()->json($alumnos, 200);

}
//PDF
public function PDFmostrarDatosCombinados(Request $request)
{
    $status = $request->input('status');

    $alumnos = Alumno::leftJoin('registro_predefinido', 'alumnos.id_alumno', '=', 'registro_predefinido.id_alumno')
        ->leftJoin('programas_predefinidos', 'registro_predefinido.id_programa', '=', 'programas_predefinidos.id_programa')
        ->select('alumnos.id_alumno', 'alumnos.nombre', 'programas_predefinidos.nombre as nombre_programa', 'alumnos.celular')
        ->when($status !== null, function ($query) use ($status) {
            return $query->where('alumnos.status', $status);
        })
        ->orderBy('alumnos.nombre', 'asc')
        ->get();

    $pdf = PDF::loadView('alumnos-pdf', compact('alumnos'));

    $pdfPath = 'pdf/alumnos_combinados.pdf';
    Storage::put('public/' . $pdfPath, $pdf->output());

    $downloadLink = Storage::url($pdfPath);

    return response()->json([
        'message' => 'PDF generado exitosamente',
        'download_link' => $downloadLink,
    ], 200);
}
    // Mostrar un alumno específico
    public function show($id)
    {
        return Alumno::find($id);
    }

    // Crear un nuevo alumno
    public function store(Request $request)
{
    // Generar el ID del alumno
    $nombre = $request->input('nombre');
    $random1 = rand(1, 99);
    $random2 = rand(1, 99);
    $random1 = str_pad($random1, 2, "0", STR_PAD_LEFT);
    $random2 = str_pad($random2, 2, "0", STR_PAD_LEFT);
    $letra = substr($nombre, 0, 1);
    $id = 'M'.$random1.$letra.$random2;

    try {
        // Validar los datos de la solicitud
        $validatedData = $request;

          // Procesar la imagen si existe
          if ($request->hasFile('imagen')) {
            $image = $request->file('imagen');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('imagenes', $imageName, 'public');
            $validatedData['url_imagen'] = $imagePath;
        }

        // Añadir el ID del alumno a los datos validados
        $validatedData['id_alumno'] = $id;

        // Crear un nuevo alumno
        $alumno = Alumno::create($request->all());

        return response()->json([
            'message' => 'Alumno creado exitosamente',
            'alumno' => $alumno
        ], 201);

    } catch (\Exception $e) {
        // Registrar el error
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // Actualizar un alumno existente
    public function update(Request $request, $id)
    {
        try{
        $alumno = Alumno::findOrFail($id);

         // Validar los datos de la solicitud
        $validatedData = $request;


        // Procesar la imagen si existe
        if ($request->hasFile('imagen')) {
            $image = $request->file('imagen');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('imagenes', $imageName, 'public');
            $validatedData['url_imagen'] = $imagePath;

            // Si deseas eliminar la imagen anterior
            if ($alumno->url_imagen) {
                Storage::disk('public')->delete($alumno->url_imagen);
            }
        }

        // Actualizar los datos del alumno
        $alumno->update($request->all());

        return response()->json([
            'message' => 'Alumno actualizado2 exitosamente',
            'alumno' => $alumno,
            'rq' => $validatedData
        ], 200);
    }catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }

    }

    // Eliminar un alumno
    public function destroy($id)
    {
        Alumno::destroy($id);
        return response()->json(null, 204);
    }

    public function getAlumnosConDetalles($id_alumno)
    {
        $alumnos = DB::table('alumnos')
            ->leftJoin('registro_predefinido', 'alumnos.id_alumno', '=', 'registro_predefinido.id_alumno')
            ->leftJoin('programas_predefinidos', 'registro_predefinido.id_programa', '=', 'programas_predefinidos.id_programa')
            ->leftJoin('clases', 'programas_predefinidos.id_programa', '=', 'clases.id_programa')
            ->leftJoin('maestros', 'clases.id_maestro', '=', 'maestros.id_maestro')
            ->select(
                'alumnos.id_alumno',
                'alumnos.nombre as nombre_alumno',
                'programas_predefinidos.nombre as nombre_programa',
                'clases.nombre as nombre_clase',
                'clases.informacion as informacion_clase',
                'maestros.nombre as nombre_maestro',
                'registro_predefinido.precio'
            )
            ->where('alumnos.id_alumno', $id_alumno)
            ->get();

        return response()->json($alumnos);
    }

    public function bajaAlumno($id)
    {
        // Obtener la fecha actual
        $fecha = date('Y-m-d');

        // Actualizar el estado del alumno
        Alumno::where('id_alumno', $id)->update(['status' => 0]);

        // Insertar en la tabla bajas_alumnos
        BajaAlumno::create([
            'id_alumno' => $id,
            'fecha' => $fecha
        ]);

         // Retornar una respuesta exitosa
         return response()->json(['message' => 'Operaciones realizadas correctamente']);
    }

    public function altaAlumno($id)
    {
        $fechaActual = date("Y-m-d");

        // Buscar la fecha de baja del alumno
        $bajaAlumno = BajaAlumno::where('id_alumno', $id)->first();
        if ($bajaAlumno !== null) {
            $fechabaja = $bajaAlumno->fecha;
        $fechabaja = $bajaAlumno->fecha;

        // Calcular la fecha límite (2 meses después de la fecha de baja)
        $fechaLimite = date("Y-m-d", strtotime("$fechabaja +2 month"));

        if ($fechaActual <= $fechaLimite) {
            // Actualizar el estado del alumno a '1' (activo)
            Alumno::where('id_alumno', $id)->update(['status' => 1]);

            // Eliminar el registro de baja del alumno
            BajaAlumno::where('id_alumno', $id)->delete();

        } else {
            // Si la fecha actual supera la fecha límite, solo actualizar el estado del alumno y eliminar el registro de baja
            Alumno::where('id_alumno', $id)->update(['status' => 1]);
            BajaAlumno::where('id_alumno', $id)->delete();
        }
        }else {
            return response()->json(['message' => 'Este alumno no esta dado de baja']);
        }

        // Retornar una respuesta exitosa
        return response()->json(['message' => 'Operaciones realizadas correctamente']);
    }
}
