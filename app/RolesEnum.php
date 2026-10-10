<?php

namespace App;

enum RolesEnum: string
{
    case ADMIN = 'admin';

    case OWNER = 'owner';
    case USER = 'user';
    case CUSTOMER = 'customer';
}
