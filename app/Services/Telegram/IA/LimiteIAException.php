<?php

namespace App\Services\Telegram\IA;

use RuntimeException;

/** El proveedor de IA rechazó la petición por límite de uso (HTTP 429). */
class LimiteIAException extends RuntimeException {}
