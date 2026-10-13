<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when an admin's order-status change is refused by a business rule
 * that is not basic input validation -- the order is outside their role's
 * queue, or they are trying to approve their own order. Both the web
 * (AdminController) and JSON (Api\Admin\OrderController) callers catch this
 * and translate it their own way: a flash message + redirect for the former,
 * a 403 response for the latter.
 */
class AdminOrderActionRefusedException extends RuntimeException
{
}
