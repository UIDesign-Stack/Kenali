<?php

namespace App\Enums;

enum TestSessionStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';
}