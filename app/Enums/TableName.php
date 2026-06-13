<?php

namespace App\Enums;

enum TableName: string
{
    case USERS = 'users';
    case ORGANISATIONS = 'organisations';
    case TICKETS = 'tickets';
    case COMMENTS = 'comments';
}
