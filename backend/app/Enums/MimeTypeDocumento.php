<?php

namespace App\Enums;

enum MimeTypeDocumento: string
{
    case ApplicationPdf = 'application/pdf';
    case ImagePng = 'image/png';
    case ImageJpeg = 'image/jpeg';
}
