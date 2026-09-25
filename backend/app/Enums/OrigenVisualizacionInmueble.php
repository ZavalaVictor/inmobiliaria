<?php

namespace App\Enums;

enum OrigenVisualizacionInmueble: string
{
    case LandingPublica = 'landing_publica';
    case PortalCliente = 'portal_cliente';
    case Interno = 'interno';
}
