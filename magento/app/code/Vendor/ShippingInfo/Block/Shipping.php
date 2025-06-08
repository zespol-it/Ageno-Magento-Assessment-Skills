<?php
namespace Vendor\ShippingInfo\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Shipping extends Template
{
    /** @var ScopeConfigInterface */
    protected $scopeConfig;

    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    public function getShippingPrice()
    {
        // Pobierz cenę z konfiguracji flat rate
        $price = $this->scopeConfig->getValue(
            'carriers/flatrate/price',
            ScopeInterface::SCOPE_STORE
        );
        return $price !== null ? (float)$price : null;
    }
} 