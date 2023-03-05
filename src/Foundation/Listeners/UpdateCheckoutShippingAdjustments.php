<?php

declare(strict_types=1);

/**
 * Contains the UpdateCheckoutShippingAdjustments class.
 *
 * @copyright   Copyright (c) 2023 Vanilo UG
 * @author      Attila Fulop
 * @license     MIT
 * @since       2023-03-05
 *
 */

namespace Vanilo\Foundation\Listeners;

use Vanilo\Adjustments\Contracts\Adjustable;
use Vanilo\Checkout\Events\ShippingMethodSelected;
use Vanilo\Shipment\Contracts\ShippingFeeCalculator;
use Vanilo\Shipment\Contracts\ShippingMethod;
use Vanilo\Shipment\Models\ShippingMethodProxy;

class UpdateCheckoutShippingAdjustments
{
    public function handle(ShippingMethodSelected $event): void
    {
        $checkout = $event->getCheckout();

        $cart = $checkout->getCart();

        // @todo we're getting a CartManager here
//        if (!($cart instanceof Adjustable)) {
//            return;
//        }

        /** @var ShippingMethod $shippingMethod */
        if (null === $shippingMethod = ShippingMethodProxy::find($event->selectedShippingMethodId())) {
            return;
        }

        /** @var ShippingFeeCalculator $calculator */
        $calculator = $shippingMethod->getCalculator();
        $fee = $calculator->calculate($checkout, $shippingMethod->configuration());
        if ($adjuster = $fee->getAdjuster()) {
            $cart->adjustments()->create($adjuster);
        }
    }
}