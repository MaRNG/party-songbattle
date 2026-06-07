<?php

namespace App\Model\Enum;

enum GameStatusEnum: string
{
    case WAITING = 'waiting';
    case PLAYING = 'playing';
    case FINISHED = 'finished';
}
