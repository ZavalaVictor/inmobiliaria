<?php

namespace App\Services\Citas;

use App\Mail\Citas\CitaMail;
use Illuminate\Support\Facades\Mail;

class CitaCorreoSender
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(string $email, array $data): void
    {
        Mail::to($email)->send(new CitaMail($data));
    }
}
