<?php

namespace App;

enum PermissionsEnum: string
{
    case ApproveOwners = 'ApproveOwners';
    case SellServices = 'SellServices';
    case BuyServices = 'BuyServices';
}
