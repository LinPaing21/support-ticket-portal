<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case ORGANISATION_OWNER = 'organisation_owner';
    case CLIENT = 'client';
    case AGENT = 'agent';
}
