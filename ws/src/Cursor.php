<?php

namespace CursorParty;

final class Cursor
{
    public function __construct(
        public int $x,
        public int $y
    ) {
    }
}