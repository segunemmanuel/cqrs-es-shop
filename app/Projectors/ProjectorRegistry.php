<?php

namespace App\Projectors;

class ProjectorRegistry
{
    public function __construct(
        protected ProductProjector $products,
        protected InventoryProjector $inventory,
        protected OrderProjector $orders,
    ) {}

    public function dispatch(string $eventName, array $payload): void
    {
        // map event names (strings) to concrete projector methods
        $map = [
            'ProductCreated'   => fn() => $this->products->onProductCreated($payload),
            'InventoryAdjusted'=> fn() => $this->inventory->onInventoryAdjusted($payload),
            'OrderCreated'     => fn() => $this->orders->onOrderCreated($payload),
            'OrderItemAdded'   => fn() => $this->orders->onOrderItemAdded($payload),
            'OrderPlaced'      => fn() => $this->orders->onOrderPlaced($payload),
            'OrderCancelled'   => fn() => $this->orders->onOrderCancelled($payload),
            'OrderItemRemoved' => fn() => $this->orders->onOrderItemRemoved($payload),
            'PaymentAuthorized'=> fn() => $this->orders->onPaymentAuthorized($payload),

        ];

        if (isset($map[$eventName])) {
            $map[$eventName]();
        }
        // silently ignore unknown events (or throw if you prefer)
    }
}
