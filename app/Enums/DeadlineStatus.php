<?php

namespace App\Enums;

enum DeadlineStatus: string
{
    case ON_TRACK = 'on-track';
    case DUE_SOON = 'due-soon';
    case OVERDUE = 'overdue';
    case COMPLETED = 'completed';
}
