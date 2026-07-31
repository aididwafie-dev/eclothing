<?php

namespace App\Http\Controllers\Concerns;

use App\Services\OrderStatusService;

/**
 * Shared accessor for the OrderStatusService, previously duplicated verbatim
 * in AdminController and DashboardController.
 */
trait ResolvesOrderStatus
{
    private function orderStatus(): OrderStatusService
    {
        return app(OrderStatusService::class);
    }
}
