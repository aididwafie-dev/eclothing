<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a checkout would overwrite an order the member is no longer
 * allowed to change.
 *
 * Checkout upserts into the member's existing order for a uniform (see
 * OrderCheckoutService::resolveOrderId), resetting it to Pending and
 * clearing remarks + collection_date. That is the intended way to edit an
 * order while it is still Pending, but doing it to an order the store has
 * already moved on -- Processing above all -- silently discards work that is
 * under way, so it is refused here instead.
 */
class OrderNotEditableException extends RuntimeException
{
    /**
     * @param array<int, array{uniform: string, status: string}> $blocked
     */
    public function __construct(private array $blocked)
    {
        parent::__construct($this->buildMessage());
    }

    /**
     * @return array<int, array{uniform: string, status: string}>
     */
    public function blocked(): array
    {
        return $this->blocked;
    }

    private function buildMessage(): string
    {
        $parts = array_map(
            fn ($entry) => $entry['uniform'] . ' (' . $entry['status'] . ')',
            $this->blocked
        );

        return 'Your existing order for ' . implode(', ', $parts)
            . ' can no longer be changed. Only orders that are still Pending can be edited.';
    }
}
