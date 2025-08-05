<?php

namespace App\Enums;

enum UserRole:string
{
  
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case LOAN_CLERK = 'loan_clerk';
    

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::MEMBER => 'Member',
            self::LOAN_CLERK => 'Loan Clerk'
        };
    }
}
