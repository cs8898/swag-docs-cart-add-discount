<?php declare(strict_types=1);

namespace Swag\CartAddDiscountForProduct\Core\Checkout;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\Framework\Struct\StructCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AwesomeProductsCollector implements CollectorInterface
{
    public function prepare(StructCollection $definitions, Cart $cart, SalesChannelContext $context, CartBehavior $behavior): void
    {
    }

    public function collect(StructCollection $fetchDefinitions, StructCollection $data, Cart $cart, SalesChannelContext $context, CartBehavior $behavior): void
    {

    }

    public function enrich(StructCollection $data, Cart $cart, SalesChannelContext $context, CartBehavior $behavior): void
    {
        // Figure out all products containing 'awesome' in its name
        $products = $this->findAwesomeProducts($cart);

        $name = 'AWESOME_DISCOUNT';
        $discountAlreadyInCart = $cart->has($name);

        // No products matched, remove all discounts if any in the cart
        if ($products->count() === 0) {
            if ($discountAlreadyInCart) {
                $cart->getLineItems()->remove($name);
            }

            return;
        }

        // If the discount is already in the cart, fetch it from the cart. Otherwise, create it
        if (!$discountAlreadyInCart) {
            $discountLineItem = $this->createNewDiscountLineItem($name);
        } else {
            $discountLineItem = $cart->get($name);
        }

        // Set a new percentage price definition
        $discountLineItem->setPriceDefinition(
            new PercentagePriceDefinition(
                -10,
                $context->getContext()->getCurrencyPrecision(),
                new LineItemRule(LineItemRule::OPERATOR_EQ, $products->getKeys())
            )
        );

        // If the discount line item was in cart already, do not add it again
        if (!$discountAlreadyInCart) {
            $cart->add($discountLineItem);
        }
    }

    private function findAwesomeProducts(Cart $cart): \Shopware\Core\Checkout\Cart\LineItem\LineItemCollection
    {
        return $cart->getLineItems()->filter(function (LineItem $item) {
            // The discount itself has the name 'awesome' - so check if the type matches to our discount
            if ($item->getType() === 'awesome_discount') {
                return false;
            }

            $awesomeInLabel = stripos($item->getLabel(), 'awesome') !== false;

            if (!$awesomeInLabel) {
                return false;
            }

            return $item;
        });
    }

    private function createNewDiscountLineItem(string $name): LineItem
    {
        $discountLineItem = new LineItem($name, 'awesome_discount', 1);

        $discountLineItem->setLabel('\'You are awesome!\' discount');
        $discountLineItem->setGood(false);
        $discountLineItem->setStackable(false);
        $discountLineItem->setRemovable(false);

        return $discountLineItem;
    }
}
