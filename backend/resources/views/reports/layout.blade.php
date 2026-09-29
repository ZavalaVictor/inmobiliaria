<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172b4d; font-size: 9px; }
        .brand { color: #087f8c; font-size: 20px; font-weight: bold; }
        h1 { color: #172b4d; font-size: 17px; margin: 8px 0; }
        h2 { color: #087f8c; font-size: 12px; margin: 14px 0 5px; }
        .meta { color: #526581; margin-bottom: 8px; }
        .filters { background: #eef5f6; padding: 7px; }
        .grid { width: 100%; }
        .card { border: 1px solid #d8e1e8; padding: 7px; margin: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 5px 0 10px; }
        th { background: #172b4d; color: #fff; text-align: left; }
        th, td { border: 1px solid #d8e1e8; padding: 4px; }
        .muted { color: #64748b; }
        .footer { position: fixed; bottom: -15px; left: 0; right: 0; text-align: center; color: #64748b; }
    </style>
</head>
<body>
    <div class="brand">SotyTech</div>
    <h1>{{ $title }}</h1>
    <div class="meta">Generado en: {{ $data['generado_en'] }} | Generado por: {{ $user->nombres }} {{ $user->apellido_paterno }}</div>
    <div class="filters"><strong>Filtros aplicados:</strong> {{ json_encode($data['filtros'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</div>
    @yield('content')
    <div class="footer">SotyTech · Reporte generado bajo demanda</div>
</body>
</html>
