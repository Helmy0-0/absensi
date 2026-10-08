<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Pelanggaran aturan bisnis. Pesannya aman ditampilkan ke pengguna
 * (di-render sebagai flash "error" di bootstrap/app.php).
 */
class BisnisException extends RuntimeException
{
}
