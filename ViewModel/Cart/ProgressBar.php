<?php
/**
 * Copyright © Chris Mallory All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ChrisMallory\FreeShippingProgressBar\ViewModel\Cart;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

class ProgressBar extends DataObject implements ArgumentInterface
{
    /**
     * System XML config path for ChrisMallory_FreeShippingBanner - Uses default checkout cart section
     */
    protected const CHECKOUT_CART_XML_CONFIG_PATH = 'checkout/cart/';

    /**
     * System XML config path for core Free Shipping method
     */
    protected const CARRIERS_FREE_SHIPPING_XML_CONFIG_PATH = 'carriers/freeshipping/';

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var Session $session
     */
    protected $session;

    /**
     * @var PriceCurrencyInterface $priceCurrency
     */
    protected $priceCurrency;

    /**
     * Countdown constructor.
     *
     * @param ScopeConfigInterface      $scopeConfig
     * @param Session                   $session
     * @param PriceCurrencyInterface    $priceCurrency
     * @param array                     $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session $session,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($data);
    }

    /**
     * Check if free shipping countdown is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::CHECKOUT_CART_XML_CONFIG_PATH
            . 'freeshipping_progress_enable');
    }

    /**
     * Get Cart/Quote
     *
     * @return CartInterface|Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getQuote()
    {
        return $this->session->getQuote();
    }

    /**
     * Get minimum order total required to be eligible for free shipping
     *
     * @return float
     */
    public function getFreeShippingMinValue(): float
    {
        if ($this->scopeConfig->getValue(self::CHECKOUT_CART_XML_CONFIG_PATH
                . 'use_freeshipping_method_config')
            && $this->scopeConfig->getValue(self::CARRIERS_FREE_SHIPPING_XML_CONFIG_PATH
                . 'active')
        ) {
            return $this->getFreeShippingMethodMinValue();
        }

        return (float)$this->scopeConfig->getValue(self::CHECKOUT_CART_XML_CONFIG_PATH
            . 'freeshipping_progress_min_total');
    }

    public function getFreeShippingMethodMinValue(): float
    {
        return (float)$this->scopeConfig->getValue(self::CARRIERS_FREE_SHIPPING_XML_CONFIG_PATH
            . 'free_shipping_subtotal');
    }

    /**
     * Get current quote/cart total
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCurrentTotal(): float
    {
        $quote = $this->session->getQuote();

        return (float)$quote->getSubtotalWithDiscount();
    }

    /**
     * Validate if current order total is free shipping eligible
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isFreeShippingEligible(): bool
    {
        $currentTotal = $this->getCurrentTotal();
        if ($this->scopeConfig->getValue(self::CHECKOUT_CART_XML_CONFIG_PATH
            . 'use_freeshipping_method_config')) {
            if ($this->scopeConfig->getValue(self::CARRIERS_FREE_SHIPPING_XML_CONFIG_PATH . 'active')) {
                return ($currentTotal >= $this->getFreeShippingMethodMinValue());
            }
            return false;
        }

        return ($currentTotal >= $this->getFreeShippingMinValue());
    }

    /**
     * Get difference between minimum order total for free shipping current order total
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getFreeShippingDifference(): float
    {
        $currentTotal = $this->getCurrentTotal();

        return $this->getFreeShippingMinValue() - $currentTotal;
    }

    /**
     * Get percentage completed towards free shipping
     *
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getFreeShippingCompletionPercent(): float
    {
        return ($this->getCurrentTotal() / $this->getFreeShippingMinValue()) * 100;
    }

    /**
     * @param float $price
     * @param int   $precision
     *
     * @return string
     */
    public function getFormattedPrice(float $price, int $precision = 2): string
    {
        return $this->priceCurrency->format($price, false, $precision);
    }
}
