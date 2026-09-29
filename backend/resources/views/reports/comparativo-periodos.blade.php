@extends('reports.layout', ['title' => 'Reporte Comparativo por Periodos'])
@section('content')
<h2>Periodos</h2><p>Periodo A: {{ $data['periodo_a']['desde'] }} a {{ $data['periodo_a']['hasta'] }} ({{ $data['periodo_a']['duracion_dias'] }} días)</p><p>Periodo B: {{ $data['periodo_b']['desde'] }} a {{ $data['periodo_b']['hasta'] }} ({{ $data['periodo_b']['duracion_dias'] }} días)</p>
<h2>Indicadores</h2><table><tr><th>Indicador</th><th>A</th><th>B</th><th>Diferencia</th><th>%</th><th>Tendencia</th></tr>@foreach($data['indicadores'] as $row)<tr><td>{{ $row['indicador'] }}</td><td>{{ $row['periodo_a'] }}</td><td>{{ $row['periodo_b'] }}</td><td>{{ $row['diferencia_absoluta'] }}</td><td>{{ $row['no_calculable'] ? 'No calculable (periodo base = 0)' : $row['diferencia_porcentual'].'%' }}</td><td>{{ $row['tendencia'] }}</td></tr>@endforeach</table>
@endsection
