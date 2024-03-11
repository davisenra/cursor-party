<?php

declare(strict_types=1);

namespace CursorParty;

final class Cursor
{
    public function __construct(
        public string $username,
        public int $x,
        public int $y,
    ) {
    }
}
