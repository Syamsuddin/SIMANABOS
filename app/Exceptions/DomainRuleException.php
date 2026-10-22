<?php

namespace App\Exceptions;

use RuntimeException;

/** Pelanggaran aturan domain; membawa daftar pesan untuk ditampilkan ke pengguna (docs/14). */
class DomainRuleException extends RuntimeException
{
    /** @param  list<string>  $messages */
    public function __construct(public readonly array $messages)
    {
        parent::__construct(implode(' ', $messages));
    }
}
