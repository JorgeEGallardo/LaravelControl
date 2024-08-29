<!DOCTYPE html>
<html>
<head>
    <title>Lista de Alumnos</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Lista de Alumnos</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Programa</th>
                <th>Celular</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($alumnos as $alumno)
            <tr>
                <td>{{ $alumno->id_alumno }}</td>
                <td>{{ $alumno->nombre }}</td>
                <td>{{ $alumno->nombre_programa }}</td>
                <td>{{ $alumno->celular }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
