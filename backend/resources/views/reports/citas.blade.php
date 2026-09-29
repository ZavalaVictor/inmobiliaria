@extends('reports.layout', ['title' => 'Reporte de Citas y Visitas'])
@section('content')
<h2>Resumen</h2><table><tr><th>Programadas</th><th>Realizadas</th><th>Canceladas</th><th>Reprogramadas</th><th>Cumplimiento</th></tr><tr><td>{{ $data['resumen']['citas_programadas'] }}</td><td>{{ $data['resumen']['citas_realizadas'] }}</td><td>{{ $data['resumen']['citas_canceladas'] }}</td><td>{{ $data['resumen']['citas_reprogramadas'] }}</td><td>{{ $data['resumen']['porcentaje_cumplimiento'] }}%</td></tr></table>
<h2>Horarios con mayor demanda</h2><table><tr><th>Hora</th><th>Total</th></tr>@foreach($data['graficas']['horarios_con_mayor_demanda'] as $row)<tr><td>{{ $row['hora'] }}</td><td>{{ $row['total'] }}</td></tr>@endforeach</table>
<h2>Inmuebles con más visitas realizadas</h2><table><tr><th>Inmueble</th><th>Total</th></tr>@foreach($data['tablas']['inmuebles_con_mas_visitas'] as $row)<tr><td>{{ $row['codigo'] }} — {{ $row['titulo'] }}</td><td>{{ $row['total_visitas'] }}</td></tr>@endforeach</table>
<h2>Actividad por agente</h2><table><tr><th>Agente</th><th>Total</th><th>Realizadas</th><th>Canceladas</th><th>Reprogramadas</th></tr>@foreach($data['graficas']['actividad_por_agente'] as $row)<tr><td>{{ $row['agente'] }}</td><td>{{ $row['total_citas'] }}</td><td>{{ $row['realizadas'] }}</td><td>{{ $row['canceladas'] }}</td><td>{{ $row['reprogramadas'] }}</td></tr>@endforeach</table>
@if($data['resumen']['citas_programadas'] + $data['resumen']['citas_realizadas'] + $data['resumen']['citas_canceladas'] === 0)<p class="muted">Sin datos para los filtros seleccionados.</p>@endif
@endsection
