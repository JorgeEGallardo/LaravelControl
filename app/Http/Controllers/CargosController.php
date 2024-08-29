<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CargosController extends Controller
{
    //eliminarCargoPrimario
    public function eliminarCargoPrimario(Request $request)
    {
        $id_alumno = $request->input('id_alumno');
        $id_programa = $request->input('id_programa');
        $periodo = $request->input('periodo');
        $concepto = $request->input('concepto');
        $monto = $request->input('monto');

        // Eliminar el adeudo del programa
        DB::table('adeudos_programas')
            ->where('id_alumno', $id_alumno)
            ->where('id_programa', $id_programa)
            ->where('periodo', $periodo)
            ->where('concepto', $concepto)
            ->where('monto', $monto)
            ->delete();

        // Eliminar los adeudos fragmentados asociados al alumno y programa en ese periodo
        DB::table('adeudos_fragmentados')
            ->where('id_alumno', $id_alumno)
            ->where('id_programa', $id_programa)
            ->where('periodo', $periodo)
            ->delete();

        return response()->json(['message' => 'Adeudo eliminado exitosamente']);
    }


    public function eliminarCargoSecundario(Request $request)
    {
        $id_alumno = $request->input('id_alumno');
        $concepto = $request->input('concepto');
        $periodo = $request->input('periodo');

        // Eliminar el adeudo secundario
        DB::table('adeudos_secundarios')
            ->where('id_alumno', $id_alumno)
            ->where('concepto', $concepto)
            ->where('periodo', $periodo)
            ->delete();

        return response()->json(['message' => 'Adeudo secundario eliminado exitosamente']);
    }
    
//removerPrograma
    public function removerPrograma(Request $request)
    {
        $id_alumno = $request->input('id_alumno');
        $id_programa = $request->input('programa');
        
        // Obtener la fecha actual y el periodo correspondiente
        $fecha = date("Y-m-d");
        $mes = date("m");
        $anio = date("Y");

        $periodos = [
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
            '12' => 'DICIEMBRE',
        ];

        $periodo = $periodos[$mes] . '/' . $anio;

        // Verificar si existen adeudos para el periodo actual
        $adeudos = DB::table('adeudos_programas')
            ->where('id_alumno', $id_alumno)
            ->where('id_programa', $id_programa)
            ->where('periodo', $periodo)
            ->get();

        if ($adeudos->isNotEmpty()) {
            // Eliminar adeudos si existen
            foreach ($adeudos as $adeudo) {
                DB::table('registro_predefinido')
                    ->where('id_alumno', $id_alumno)
                    ->where('id_programa', $id_programa)
                    ->delete();

                DB::table('adeudos_programas')
                    ->where('id_alumno', $id_alumno)
                    ->where('id_programa', $id_programa)
                    ->where('periodo', $periodo)
                    ->where('concepto', $adeudo->concepto)
                    ->where('monto', $adeudo->monto)
                    ->where('beca', $adeudo->beca)
                    ->where('descuento', $adeudo->descuento)
                    ->where('fecha_limite', $adeudo->fecha_limite)
                    ->delete();

                DB::table('adeudos_fragmentados')
                    ->where('id_alumno', $id_alumno)
                    ->where('id_programa', $id_programa)
                    ->where('periodo', $periodo)
                    ->delete();
            }

            return response()->json(['message' => 'Adeudos eliminados exitosamente']);
        } else {
            // Verificar si existen pagos para el periodo actual
            $pago_reciente = DB::table('pagos_programas')
                ->where('id_alumno', $id_alumno)
                ->where('id_programa', $id_programa)
                ->where('periodo', $periodo)
                ->exists();

            if ($pago_reciente) {
                return response()->json(['error' => 'No puedes dar de baja este programa hasta que finalice el periodo pagado ('.$periodo.').'], 403);
            } else {
                // Eliminar solo el programa si no hay adeudos ni pagos
                DB::table('registro_predefinido')
                    ->where('id_alumno', $id_alumno)
                    ->where('id_programa', $id_programa)
                    ->delete();

                return response()->json(['message' => 'Programa eliminado exitosamente']);
            }
        }
    }
//se supone que elimna el registro en de un alumno en Corte 
    public function eliminarPagoClase(Request $request)
    {
        $id_alumno = $request->query('id_alumno');
        $id_clase = $request->query('id_clase');
        $periodo = $request->query('periodo');
        $paquete = $request->query('paquete');

        if (empty($id_alumno) || empty($id_clase) || empty($periodo) || empty($paquete)) {
            return response()->json(['error' => 'Faltan parámetros'], 400);
        }

        $deleted = DB::table('pagos_clases')
            ->where('id_clase', $id_clase)
            ->where('id_alumno', $id_alumno)
            ->where('periodo', $periodo)
            ->where('paquete', $paquete)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Pago clase eliminado correctamente'], 200);
        } else {
            return response()->json(['error' => 'No se encontró el pago clase a eliminar'], 404);
        }
    }
}
