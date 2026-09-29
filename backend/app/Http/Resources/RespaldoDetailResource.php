<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class RespaldoDetailResource extends RespaldoResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'checksum_sha256' => $this->checksum_sha256,
            'mensaje_error' => $this->mensaje_error,
        ];
    }
}
