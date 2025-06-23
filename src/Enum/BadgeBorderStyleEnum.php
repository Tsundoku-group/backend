<?php

namespace App\Enum;

enum BadgeBorderStyleEnum: string
{
    case SOLID = 'solid';
    case DOTTED = 'dotted';
    case DASHED = 'dashed';
    case TRIANGULAR = 'triangular';
    case OCTAGONAL = 'octagonal';
    case HEXAGONAL = 'hexagonal';
    case SERRATED = 'serrated';
}