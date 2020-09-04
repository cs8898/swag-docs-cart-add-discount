<?php declare(strict_types=1);

namespace Swag\CartAddDiscountForProductTests;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Swag\CartAddDiscountForProduct\Core\Checkout\AwesomeProductsCollector;

class AwesomeProductsCollectorTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function test_process(): void
    {
        $productId = Uuid::randomHex();
        $cartService = $this->getContainer()->get(CartService::class);

        $cart = $cartService->createNew('discount-test-cart');
        $toCalculate = $cartService->createNew('calculation-test-cart');
        $salesChannelContext = $this->getContainer()->get(SalesChannelContextFactory::class)->create('test-sales-channel-token', Defaults::SALES_CHANNEL);
        $awesomeCartProcessor = $this->getProcessor();

        $productLineItem = new LineItem(
            $productId,
            LineItem::PRODUCT_LINE_ITEM_TYPE
        );

        $productLineItem->setLabel('awesome');

        $price = new CalculatedPrice(
            10,
            10,
            new CalculatedTaxCollection(
                [new CalculatedTax(1, 10, 10)]
            ),
            new TaxRuleCollection(
                [new TaxRule(10)]
            )
        );

        $priceDefinition = new QuantityPriceDefinition(10.0, new TaxRuleCollection(), 2, 1, true);
        $productLineItem->setPriceDefinition($priceDefinition);
        $productLineItem->setPrice($price);

        $cart->add($productLineItem);
        $toCalculate->add($productLineItem);

        $awesomeCartProcessor->process(
            new CartDataCollection(),
            $cart,
            $toCalculate,
            $salesChannelContext,
            new CartBehavior()
        );

        $result = $toCalculate->getLineItems()->filter(static function ($item) {
            if ($item->getId() === 'AWESOME_DISCOUNT') {
                return $item;
            }
        });

        static::assertCount(1, $result);
        static::assertSame('awesome_discount', $result->first()->getType());
        static::assertSame(-1.0, $result->first()->getPrice()->getUnitPrice());
    }

    private function getProcessor(): AwesomeProductsCollector
    {
        return $this->getContainer()->get(AwesomeProductsCollector::class);
    }
}
