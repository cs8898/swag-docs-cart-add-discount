<?php declare(strict_types=1);

namespace Swag\CartAddDiscountForProduct\Core\Checkout;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\PercentagePriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\PercentagePriceDefinition;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AwesomeProductsCollector implements CartProcessorInterface
{
    /**
     * @var PercentagePriceCalculator
     */
    private $calculator;

    public function __construct(PercentagePriceCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $products = $this->findAwesomeProducts($toCalculate);

        // no awesome products found? early return
        if ($products->count() === 0) {
            return;
        }

        $discountLineItem = $this->createDiscount('AWESOME_DISCOUNT');

        // declare price definition to define how this price is calculated
        $definition = new PercentagePriceDefinition(
            -10,
            $context->getContext()->getCurrencyPrecision(),
            new LineItemRule(LineItemRule::OPERATOR_EQ, $products->getKeys())
        );

        $discountLineItem->setPriceDefinition($definition);

        // calculate price
        $discountLineItem->setPrice(
            $this->calculator->calculate($definition->getPercentage(), $products->getPrices(), $context)
        );

        // add discount to new cart
        $toCalculate->add($discountLineItem);
    }

    private function findAwesomeProducts(Cart $cart): LineItemCollection
    {
        return $cart->getLineItems()->filter(function (LineItem $item) {
            if ($item->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                return false;
            }

            $awesomeInLabel = stripos($item->getLabel(), 'awesome') !== false;

            if (!$awesomeInLabel) {
                return false;
            }

            return $item;
        });
    }

    private function createDiscount(string $name): LineItem
    {
        $discountLineItem = new LineItem($name, 'awesome_discount', null, 1);

        $discountLineItem->setLabel('\'You are awesome!\' discount');
        $discountLineItem->setGood(false);
        $discountLineItem->setStackable(false);
        $discountLineItem->setRemovable(false);

        return $discountLineItem;
    }
}
