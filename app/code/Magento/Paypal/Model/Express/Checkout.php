<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Paypal\Model\Express;

use Magento\Customer\Service\V1\CustomerAccountServiceInterface;
use Magento\Sales\Model\Quote\Address;
use Magento\Customer\Service\V1\Data\Customer as CustomerDataObject;
use Magento\Paypal\Model\Config as PaypalConfig;

/**
 * Wrapper that performs Paypal Express and Checkout communication
 * Use current Paypal Express method instance
 */
class Checkout
{
    /**
     * Cache ID prefix for "pal" lookup
     * @var string
     */
    const PAL_CACHE_ID = 'paypal_express_checkout_pal';

    /**
     * Keys for passthrough variables in sales/quote_payment and sales/order_payment
     * Uses additional_information as storage
     */
    const PAYMENT_INFO_TRANSPORT_TOKEN    = 'paypal_express_checkout_token';
    const PAYMENT_INFO_TRANSPORT_SHIPPING_OVERRIDDEN = 'paypal_express_checkout_shipping_overridden';
    const PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD = 'paypal_express_checkout_shipping_method';
    const PAYMENT_INFO_TRANSPORT_PAYER_ID = 'paypal_express_checkout_payer_id';
    const PAYMENT_INFO_TRANSPORT_REDIRECT = 'paypal_express_checkout_redirect_required';
    const PAYMENT_INFO_TRANSPORT_BILLING_AGREEMENT = 'paypal_ec_create_ba';

    /**
     * @var \Magento\Sales\Model\Quote
     */
    protected $_quote;

    /**
     * Config instance
     *
     * @var PaypalConfig
     */
    protected $_config;

    /**
     * API instance
     *
     * @var \Magento\Paypal\Model\Api\Nvp
     */
    protected $_api;

    /**
     * Api Model Type
     *
     * @var string
     */
    protected $_apiType = 'Magento\Paypal\Model\Api\Nvp';

    /**
     * Payment method type
     *
     * @var string
     */
    protected $_methodType = PaypalConfig::METHOD_WPP_EXPRESS;

    /**
     * State helper variable
     *
     * @var string
     */
    protected $_redirectUrl = '';

    /**
     * State helper variable
     *
     * @var string
     */
    protected $_pendingPaymentMessage = '';

    /**
     * State helper variable
     *
     * @var string
     */
    protected $_checkoutRedirectUrl = '';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Redirect urls supposed to be set to support giropay
     *
     * @var array
     */
    protected $_giropayUrls = array();

    /**
     * Create Billing Agreement flag
     *
     * @var bool
     */
    protected $_isBARequested = false;

    /**
     * Customer ID
     *
     * @var int
     */
    protected $_customerId;

    /**
     * Billing agreement that might be created during order placing
     *
     * @var \Magento\Paypal\Model\Billing\Agreement
     */
    protected $_billingAgreement;

    /**
     * Order
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Magento\App\Cache\Type\Config
     */
    protected $_configCacheType;

    /**
     * Checkout data
     *
     * @var \Magento\Checkout\Helper\Data
     */
    protected $_checkoutData;

    /**
     * Tax data
     *
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxData;

    /**
     * Customer data
     *
     * @var \Magento\Customer\Helper\Data
     */
    protected $_customerData;

    /**
     * @var \Magento\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Magento\Paypal\Model\Info
     */
    protected $_paypalInfo;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\UrlInterface
     */
    protected $_coreUrl;

    /**
     * @var \Magento\Paypal\Model\CartFactory
     */
    protected $_cartFactory;

    /**
     * @var \Magento\Logger\AdapterFactory
     */
    protected $_logFactory;

    /**
     * @var \Magento\Checkout\Model\Type\OnepageFactory
     */
    protected $_checkoutOnepageFactory;

    /**
     * @var \Magento\Sales\Model\Service\QuoteFactory
     */
    protected $_serviceQuoteFactory;

    /**
     * @var \Magento\Paypal\Model\Billing\AgreementFactory
     */
    protected $_agreementFactory;

    /**
     * @var \Magento\Paypal\Model\Api\Type\Factory
     */
    protected $_apiTypeFactory;

    /**
     * @var \Magento\Object\Copy
     */
    protected $_objectCopyService;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Service\V1\CustomerAccountServiceInterface
     */
    protected $_customerAccountService;

    /**
     * @var \Magento\Customer\Service\V1\Data\AddressBuilderFactory
     */
    protected $_addressBuilderFactory;

    /**
     * @var \Magento\Customer\Service\V1\Data\CustomerBuilder
     */
    protected $_customerBuilder;

    /**
     * @var \Magento\Customer\Service\V1\Data\CustomerDetailsBuilder
     */
    protected $_customerDetailsBuilder;

    /**
     * @var \Magento\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * Set config, session and quote instances
     *
     * @param \Magento\Logger $logger
     * @param \Magento\Customer\Helper\Data $customerData
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Checkout\Helper\Data $checkoutData
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\App\Cache\Type\Config $configCacheType
     * @param \Magento\Locale\ResolverInterface $localeResolver
     * @param \Magento\Paypal\Model\Info $paypalInfo
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\UrlInterface $coreUrl
     * @param \Magento\Paypal\Model\CartFactory $cartFactory
     * @param \Magento\Logger\AdapterFactory $logFactory
     * @param \Magento\Checkout\Model\Type\OnepageFactory $onepageFactory
     * @param \Magento\Sales\Model\Service\QuoteFactory $serviceQuoteFactory
     * @param \Magento\Paypal\Model\Billing\AgreementFactory $agreementFactory
     * @param \Magento\Paypal\Model\Api\Type\Factory $apiTypeFactory
     * @param \Magento\Object\Copy $objectCopyService
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Service\V1\CustomerAccountServiceInterface $customerAccountService
     * @param \Magento\Customer\Service\V1\Data\AddressBuilderFactory $addressBuilder
     * @param \Magento\Customer\Service\V1\Data\CustomerBuilder $customerBuilder
     * @param \Magento\Customer\Service\V1\Data\CustomerDetailsBuilder $customerDetailsBuilder
     * @param \Magento\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Message\ManagerInterface $messageManager
     * @param array $params
     * @throws \Exception
     */
    public function __construct(
        \Magento\Logger $logger,
        \Magento\Customer\Helper\Data $customerData,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Checkout\Helper\Data $checkoutData,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\App\Cache\Type\Config $configCacheType,
        \Magento\Locale\ResolverInterface $localeResolver,
        \Magento\Paypal\Model\Info $paypalInfo,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\UrlInterface $coreUrl,
        \Magento\Paypal\Model\CartFactory $cartFactory,
        \Magento\Logger\AdapterFactory $logFactory,
        \Magento\Checkout\Model\Type\OnepageFactory $onepageFactory,
        \Magento\Sales\Model\Service\QuoteFactory $serviceQuoteFactory,
        \Magento\Paypal\Model\Billing\AgreementFactory $agreementFactory,
        \Magento\Paypal\Model\Api\Type\Factory $apiTypeFactory,
        \Magento\Object\Copy $objectCopyService,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Service\V1\CustomerAccountServiceInterface $customerAccountService,
        \Magento\Customer\Service\V1\Data\AddressBuilderFactory $addressBuilder,
        \Magento\Customer\Service\V1\Data\CustomerBuilder $customerBuilder,
        \Magento\Customer\Service\V1\Data\CustomerDetailsBuilder $customerDetailsBuilder,
        \Magento\Encryption\EncryptorInterface $encryptor,
        \Magento\Message\ManagerInterface $messageManager,
        $params = array()
    ) {
        $this->_customerData = $customerData;
        $this->_taxData = $taxData;
        $this->_checkoutData = $checkoutData;
        $this->_customerSession = $customerSession;
        $this->_configCacheType = $configCacheType;
        $this->_logger = $logger;
        $this->_localeResolver = $localeResolver;
        $this->_paypalInfo = $paypalInfo;
        $this->_storeManager = $storeManager;
        $this->_coreUrl = $coreUrl;
        $this->_cartFactory = $cartFactory;
        $this->_logFactory = $logFactory;
        $this->_checkoutOnepageFactory = $onepageFactory;
        $this->_serviceQuoteFactory = $serviceQuoteFactory;
        $this->_agreementFactory = $agreementFactory;
        $this->_apiTypeFactory = $apiTypeFactory;
        $this->_objectCopyService = $objectCopyService;
        $this->_checkoutSession = $checkoutSession;
        $this->_customerAccountService = $customerAccountService;
        $this->_addressBuilderFactory = $addressBuilder;
        $this->_customerBuilder = $customerBuilder;
        $this->_customerDetailsBuilder = $customerDetailsBuilder;
        $this->_encryptor = $encryptor;
        $this->_messageManager = $messageManager;

        if (isset($params['config']) && $params['config'] instanceof PaypalConfig) {
            $this->_config = $params['config'];
        } else {
            throw new \Exception('Config instance is required.');
        }

        if (isset($params['quote']) && $params['quote'] instanceof \Magento\Sales\Model\Quote) {
            $this->_quote = $params['quote'];
        } else {
            throw new \Exception('Quote instance is required.');
        }
    }

    /**
     * Checkout with PayPal image URL getter
     * Spares API calls of getting "pal" variable, by putting it into cache per store view
     *
     * @return string
     */
    public function getCheckoutShortcutImageUrl()
    {
        // get "pal" thing from cache or lookup it via API
        $pal = null;
        if ($this->_config->areButtonsDynamic()) {
            $cacheId = self::PAL_CACHE_ID . $this->_storeManager->getStore()->getId();
            $pal = $this->_configCacheType->load($cacheId);
            if (self::PAL_CACHE_ID == $pal) {
                $pal = null;
            } elseif (!$pal) {
                $pal = null;
                $this->_getApi();
                try {
                    $this->_api->callGetPalDetails();
                    $pal = $this->_api->getPal();
                    $this->_configCacheType->save($pal, $cacheId);
                } catch (\Exception $e) {
                    $this->_configCacheType->save(self::PAL_CACHE_ID, $cacheId);
                    $this->_logger->logException($e);
                }
            }
        }

        return $this->_config->getExpressCheckoutShortcutImageUrl(
            $this->_localeResolver->getLocaleCode(),
            $this->_quote->getBaseGrandTotal(),
            $pal
        );
    }

    /**
     * Setter that enables giropay redirects flow
     *
     * @param string $successUrl - payment success result
     * @param string $cancelUrl  - payment cancellation result
     * @param string $pendingUrl - pending payment result
     * @return $this
     */
    public function prepareGiropayUrls($successUrl, $cancelUrl, $pendingUrl)
    {
        $this->_giropayUrls = array($successUrl, $cancelUrl, $pendingUrl);
        return $this;
    }

    /**
     * Set create billing agreement flag
     *
     * @param bool $flag
     * @return $this
     */
    public function setIsBillingAgreementRequested($flag)
    {
        $this->_isBARequested = $flag;
        return $this;
    }

    /**
     * Setter for customer
     *
     * @param CustomerDataObject $customerData
     * @return $this
     */
    public function setCustomerData(CustomerDataObject $customerData)
    {
        $this->_quote->assignCustomer($customerData);
        $this->_customerId = $customerData->getId();
        return $this;
    }

    /**
     * Setter for customer with billing and shipping address changing ability
     *
     * @param CustomerDataObject $customerData
     * @param Address|null $billingAddress
     * @param Address|null $shippingAddress
     * @return $this
     */
    public function setCustomerWithAddressChange(
        CustomerDataObject $customerData,
        $billingAddress = null,
        $shippingAddress = null
    ) {
        $this->_quote->assignCustomerWithAddressChange($customerData, $billingAddress, $shippingAddress);
        $this->_customerId = $customerData->getId();
        return $this;
    }

    /**
     * Reserve order ID for specified quote and start checkout on PayPal
     *
     * @param string $returnUrl
     * @param string $cancelUrl
     * @return string
     * @throws \Magento\Model\Exception
     */
    public function start($returnUrl, $cancelUrl)
    {
        $this->_quote->collectTotals();

        if (!$this->_quote->getGrandTotal() && !$this->_quote->hasNominalItems()) {
            throw new \Magento\Model\Exception(
                __(
                    'PayPal can\'t process orders with a zero balance due. '
                    . 'To finish your purchase, please go through the standard checkout process.'
                )
            );
        }

        $this->_quote->reserveOrderId()->save();
        // prepare API
        $this->_getApi();
        $this->_api->setAmount($this->_quote->getBaseGrandTotal())
            ->setCurrencyCode($this->_quote->getBaseCurrencyCode())
            ->setInvNum($this->_quote->getReservedOrderId())
            ->setReturnUrl($returnUrl)
            ->setCancelUrl($cancelUrl)
            ->setSolutionType($this->_config->solutionType)
            ->setPaymentAction($this->_config->paymentAction)
        ;
        if ($this->_giropayUrls) {
            list($successUrl, $cancelUrl, $pendingUrl) = $this->_giropayUrls;
            $this->_api->addData(
                [
                    'giropay_cancel_url' => $cancelUrl,
                    'giropay_success_url' => $successUrl,
                    'giropay_bank_txn_pending_url' => $pendingUrl,
                ]
            );
        }

        $this->_setBillingAgreementRequest();

        if ($this->_config->requireBillingAddress == PaypalConfig::REQUIRE_BILLING_ADDRESS_ALL) {
            $this->_api->setRequireBillingAddress(1);
        }

        // suppress or export shipping address
        if ($this->_quote->getIsVirtual()) {
            if ($this->_config->requireBillingAddress == PaypalConfig::REQUIRE_BILLING_ADDRESS_VIRTUAL) {
                $this->_api->setRequireBillingAddress(1);
            }
            $this->_api->setSuppressShipping(true);
        } else {
            $address = $this->_quote->getShippingAddress();
            $isOverridden = 0;
            if (true === $address->validate()) {
                $isOverridden = 1;
                $this->_api->setAddress($address);
            }
            $this->_quote->getPayment()->setAdditionalInformation(
                self::PAYMENT_INFO_TRANSPORT_SHIPPING_OVERRIDDEN,
                $isOverridden
            );
            $this->_quote->getPayment()->save();
        }

        // add line items
        /** @var $cart \Magento\Payment\Model\Cart */
        $cart = $this->_cartFactory->create(array('salesModel' => $this->_quote));
        $this->_api->setPaypalCart($cart)
            ->setIsLineItemsEnabled($this->_config->lineItemsEnabled);

        // add shipping options if needed and line items are available
        $cartItems = $cart->getAllItems();
        if ($this->_config->lineItemsEnabled && $this->_config->transferShippingOptions && !empty($cartItems)) {
            if (!$this->_quote->getIsVirtual() && !$this->_quote->hasNominalItems()) {
                $options = $this->_prepareShippingOptions($address, true);
                if ($options) {
                    $this->_api->setShippingOptionsCallbackUrl(
                        $this->_coreUrl->getUrl(
                            '*/*/shippingOptionsCallback',
                            ['quote_id' => $this->_quote->getId()]
                        )
                    )->setShippingOptions($options);
                }
            }
        }

        $this->_config->exportExpressCheckoutStyleSettings($this->_api);

        /* Temporary solution. @TODO: do not pass quote into Nvp model */
        $this->_api->setQuote($this->_quote);
        $this->_api->callSetExpressCheckout();

        $token = $this->_api->getToken();
        $this->_redirectUrl = $this->_config->getExpressCheckoutStartUrl($token);

        $this->_quote->getPayment()->unsAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_BILLING_AGREEMENT)->save();
        return $token;
    }

    /**
     * Update quote when returned from PayPal
     * rewrite billing address by paypal
     * save old billing address for new customer
     * export shipping address in case address absence
     *
     * @param string $token
     * @return void
     */
    public function returnFromPaypal($token)
    {
        $this->_getApi();
        $this->_api->setToken($token)
            ->callGetExpressCheckoutDetails();
        $quote = $this->_quote;

        $this->_ignoreAddressValidation();

        // import billing address
        $billingAddress = $quote->getBillingAddress();
        $exportedBillingAddress = $this->_api->getExportedBillingAddress();
        $quote->setCustomerEmail($billingAddress->getEmail());
        $quote->setCustomerPrefix($billingAddress->getPrefix());
        $quote->setCustomerFirstname($billingAddress->getFirstname());
        $quote->setCustomerMiddlename($billingAddress->getMiddlename());
        $quote->setCustomerLastname($billingAddress->getLastname());
        $quote->setCustomerSuffix($billingAddress->getSuffix());
        $quote->setCustomerNote($exportedBillingAddress->getData('note'));
        $this->_setExportedAddressData($billingAddress, $exportedBillingAddress);

        // import shipping address
        $exportedShippingAddress = $this->_api->getExportedShippingAddress();
        if (!$quote->getIsVirtual()) {
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress) {
                if ($exportedShippingAddress) {
                    $this->_setExportedAddressData($shippingAddress, $exportedShippingAddress);
                    $shippingAddress->setCollectShippingRates(true);
                    $shippingAddress->setSameAsBilling(0);
                }

                // import shipping method
                $code = '';
                if ($this->_api->getShippingRateCode()) {
                    $code = $this->_matchShippingMethodCode($shippingAddress, $this->_api->getShippingRateCode());
                    if ($code) {
                        // possible bug of double collecting rates :-/
                        $shippingAddress->setShippingMethod($code)->setCollectShippingRates(true);
                    }
                }
                $quote->getPayment()->setAdditionalInformation(
                    self::PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD,
                    $code
                );
            }
        }

        // import payment info
        $payment = $quote->getPayment();
        $payment->setMethod($this->_methodType);
        $this->_paypalInfo->importToPayment($this->_api, $payment);
        $payment->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_PAYER_ID, $this->_api->getPayerId())
            ->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_TOKEN, $token);
        $quote->collectTotals()->save();
    }

    /**
     * Check whether order review has enough data to initialize
     *
     * @param string|null $token
     * @return void
     * @throws \Magento\Model\Exception
     */
    public function prepareOrderReview($token = null)
    {
        $payment = $this->_quote->getPayment();
        if (!$payment || !$payment->getAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_PAYER_ID)) {
            throw new \Magento\Model\Exception(__('Payer is not identified.'));
        }
        $this->_quote->setMayEditShippingAddress(
            1 != $this->_quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_SHIPPING_OVERRIDDEN)
        );
        $this->_quote->setMayEditShippingMethod(
            '' == $this->_quote->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD)
        );
        $this->_ignoreAddressValidation();
        $this->_quote->collectTotals()->save();
    }

    /**
     * Return callback response with shipping options
     *
     * @param array $request
     * @return string
     * @throws \Exception
     */
    public function getShippingOptionsCallbackResponse(array $request)
    {
        // prepare debug data
        $logger = $this->_logFactory->create(array('fileName' => 'payment_' . $this->_methodType . '.log'));
        $debugData = array('request' => $request, 'response' => array());

        try {
            // obtain addresses
            $this->_getApi();
            $address = $this->_api->prepareShippingOptionsCallbackAddress($request);
            $quoteAddress = $this->_quote->getShippingAddress();

            // compare addresses, calculate shipping rates and prepare response
            $options = array();
            if ($address && $quoteAddress && !$this->_quote->getIsVirtual()) {
                foreach ($address->getExportedKeys() as $key) {
                    $quoteAddress->setDataUsingMethod($key, $address->getData($key));
                }
                $quoteAddress->setCollectShippingRates(true)->collectTotals();
                $options = $this->_prepareShippingOptions($quoteAddress, false, true);
            }
            $response = $this->_api->setShippingOptions($options)->formatShippingOptionsCallback();

            // log request and response
            $debugData['response'] = $response;
            $logger->log($debugData);
            return $response;
        } catch (\Exception $e) {
            $logger->log($debugData);
            throw $e;
        }
    }

    /**
     * Set shipping method to quote, if needed
     *
     * @param string $methodCode
     * @return void
     */
    public function updateShippingMethod($methodCode)
    {
        $shippingAddress = $this->_quote->getShippingAddress();
        if (!$this->_quote->getIsVirtual() && $shippingAddress) {
            if ($methodCode != $shippingAddress->getShippingMethod()) {
                $this->_ignoreAddressValidation();
                $shippingAddress->setShippingMethod($methodCode)->setCollectShippingRates(true);
                $this->_quote->collectTotals();
            }
        }
    }

    /**
     * Update order data
     *
     * @param array $data
     * @return void
     */
    public function updateOrder($data)
    {
        /** @var $checkout \Magento\Checkout\Model\Type\Onepage */
        $checkout = $this->_checkoutOnepageFactory->create();

        $this->_quote->setTotalsCollectedFlag(true);
        $checkout->setQuote($this->_quote);
        if (isset($data['billing'])) {
            if (isset($data['customer-email'])) {
                $data['billing']['email'] = $data['customer-email'];
            }
            $checkout->saveBilling($data['billing'], 0);
        }
        if (!$this->_quote->getIsVirtual() && isset($data['shipping'])) {
            $checkout->saveShipping($data['shipping'], 0);
        }

        if (isset($data['shipping_method'])) {
            $this->updateShippingMethod($data['shipping_method']);
        }
        $this->_quote->setTotalsCollectedFlag(false);
        $this->_quote->collectTotals();
        $this->_quote->setDataChanges(true);
        $this->_quote->save();
    }

    /**
     * Place the order when customer returned from PayPal until this moment all quote data must be valid.
     *
     * @param string $token
     * @param string|null $shippingMethodCode
     * @return void
     */
    public function place($token, $shippingMethodCode = null)
    {
        if ($shippingMethodCode) {
            $this->updateShippingMethod($shippingMethodCode);
        }

        $isNewCustomer = false;
        switch ($this->getCheckoutMethod()) {
            case \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST:
                $this->_prepareGuestQuote();
                break;
            case \Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER:
                $this->_prepareNewCustomerQuote();
                $isNewCustomer = true;
                break;
            default:
                $this->_prepareCustomerQuote();
                break;
        }

        $this->_ignoreAddressValidation();
        $this->_quote->collectTotals();
        $parameters = array('quote' => $this->_quote);
        $service = $this->_serviceQuoteFactory->create($parameters);
        $service->submitAllWithDataObject();
        $this->_quote->save();

        if ($isNewCustomer) {
            try {
                $this->_involveNewCustomer();
            } catch (\Exception $e) {
                $this->_logger->logException($e);
            }
        }

        $order = $service->getOrder();
        if (!$order) {
            return;
        }

        // commence redirecting to finish payment, if paypal requires it
        if ($order->getPayment()->getAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_REDIRECT)) {
            $this->_redirectUrl = $this->_config->getExpressCheckoutCompleteUrl($token);
        }

        switch ($order->getState()) {
            // even after placement paypal can disallow to authorize/capture, but will wait until bank transfers money
            case \Magento\Sales\Model\Order::STATE_PENDING_PAYMENT:
                // TODO
                break;
                // regular placement, when everything is ok
            case \Magento\Sales\Model\Order::STATE_PROCESSING:
            case \Magento\Sales\Model\Order::STATE_COMPLETE:
            case \Magento\Sales\Model\Order::STATE_PAYMENT_REVIEW:
                $order->sendNewOrderEmail();
                break;
            default:
                break;
        }
        $this->_order = $order;
    }

    /**
     * Make sure addresses will be saved without validation errors
     *
     * @return void
     */
    private function _ignoreAddressValidation()
    {
        $this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$this->_quote->getIsVirtual()) {
            $this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);
            if (!$this->_config->requireBillingAddress && !$this->_quote->getBillingAddress()->getEmail()) {
                $this->_quote->getBillingAddress()->setSameAsBilling(1);
            }
        }
    }

    /**
     * Determine whether redirect somewhere specifically is required
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->_redirectUrl;
    }

    /**
     * Get created billing agreement
     *
     * @return \Magento\Paypal\Model\Billing\Agreement|null
     */
    public function getBillingAgreement()
    {
        return $this->_billingAgreement;
    }

    /**
     * Return order
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Get checkout method
     *
     * @return string
     */
    public function getCheckoutMethod()
    {
        if ($this->getCustomerSession()->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }
        if (!$this->_quote->getCheckoutMethod()) {
            if ($this->_checkoutData->isAllowedGuestCheckout($this->_quote)) {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $this->_quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }
        return $this->_quote->getCheckoutMethod();
    }

    /**
     * Sets address data from exported address
     *
     * @param Address $address
     * @param array $exportedAddress
     * @return void
     */
    protected function _setExportedAddressData($address, $exportedAddress)
    {
        foreach ($exportedAddress->getExportedKeys() as $key) {
            $oldData = $address->getDataUsingMethod($key);
            $isEmpty = null;
            if (is_array($oldData)) {
                foreach ($oldData as $val) {
                    if (!empty($val)) {
                        $isEmpty = false;
                        break;
                    }
                    $isEmpty = true;
                }
            }
            if (empty($oldData) || $isEmpty === true) {
                $address->setDataUsingMethod($key, $exportedAddress->getData($key));
            }
        }
    }

    /**
     * Set create billing agreement flag to api call
     *
     * @return $this
     */
    protected function _setBillingAgreementRequest()
    {
        if (!$this->_customerId || $this->_quote->hasNominalItems()) {
            return $this;
        }

        $isRequested = $this->_isBARequested || $this->_quote->getPayment()
            ->getAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_BILLING_AGREEMENT);

        if (!($this->_config->allow_ba_signup == PaypalConfig::EC_BA_SIGNUP_AUTO
            || $isRequested && $this->_config->shouldAskToCreateBillingAgreement())
        ) {
            return $this;
        }

        if (!$this->_agreementFactory->create()->needToCreateForCustomer($this->_customerId)) {
            return $this;
        }
        $this->_api->setBillingType($this->_api->getBillingAgreementType());
        return $this;
    }

    /**
     * @return \Magento\Paypal\Model\Api\Nvp
     */
    protected function _getApi()
    {
        if (null === $this->_api) {
            $this->_api = $this->_apiTypeFactory->create($this->_apiType)->setConfigObject($this->_config);
        }
        return $this->_api;
    }

    /**
     * Attempt to collect address shipping rates and return them for further usage in instant update API
     * Returns empty array if it was impossible to obtain any shipping rate
     * If there are shipping rates obtained, the method must return one of them as default.
     *
     * @param Address $address
     * @param bool $mayReturnEmpty
     * @param bool $calculateTax
     * @return array|false
     */
    protected function _prepareShippingOptions(Address $address, $mayReturnEmpty = false, $calculateTax = false)
    {
        $options = array();
        $i = 0;
        $iMin = false;
        $min = false;
        $userSelectedOption = null;

        foreach ($address->getGroupedAllShippingRates() as $group) {
            foreach ($group as $rate) {
                $amount = (double)$rate->getPrice();
                if ($rate->getErrorMessage()) {
                    continue;
                }
                $isDefault = $address->getShippingMethod() === $rate->getCode();
                $amountExclTax = $this->_taxData->getShippingPrice($amount, false, $address);
                $amountInclTax = $this->_taxData->getShippingPrice($amount, true, $address);

                $options[$i] = new \Magento\Object(
                    [
                        'is_default' => $isDefault,
                        'name' => trim("{$rate->getCarrierTitle()} - {$rate->getMethodTitle()}", ' -'),
                        'code' => $rate->getCode(),
                        'amount' => $amountExclTax,
                    ]
                );
                if ($calculateTax) {
                    $options[$i]->setTaxAmount(
                        $amountInclTax - $amountExclTax + $address->getTaxAmount() - $address->getShippingTaxAmount()
                    );
                }
                if ($isDefault) {
                    $userSelectedOption = $options[$i];
                }
                if (false === $min || $amountInclTax < $min) {
                    $min = $amountInclTax;
                    $iMin = $i;
                }
                $i++;
            }
        }

        if ($mayReturnEmpty && is_null($userSelectedOption)) {
            $options[] = new \Magento\Object(
                [
                    'is_default' => true,
                    'name'       => __('N/A'),
                    'code'       => 'no_rate',
                    'amount'     => 0.00,
                ]
            );
            if ($calculateTax) {
                $options[$i]->setTaxAmount($address->getTaxAmount());
            }
        } elseif (is_null($userSelectedOption) && isset($options[$iMin])) {
            $options[$iMin]->setIsDefault(true);
        }

        // Magento will transfer only first 10 cheapest shipping options if there are more than 10 available.
        if (count($options) > 10) {
            usort($options, array(get_class($this), 'cmpShippingOptions'));
            array_splice($options, 10);
            // User selected option will be always included in options list
            if (!is_null($userSelectedOption) && !in_array($userSelectedOption, $options)) {
                $options[9] = $userSelectedOption;
            }
        }

        return $options;
    }

    /**
     * Compare two shipping options based on their amounts
     *
     * This function is used as a callback comparison function in shipping options sorting process
     * @see self::_prepareShippingOptions()
     *
     * @param \Magento\Object $option1
     * @param \Magento\Object $option2
     * @return int
     */
    protected static function cmpShippingOptions(\Magento\Object $option1, \Magento\Object $option2)
    {
        if ($option1->getAmount() == $option2->getAmount()) {
            return 0;
        }
        return ($option1->getAmount() < $option2->getAmount()) ? -1 : 1;
    }

    /**
     * Try to find whether the code provided by PayPal corresponds to any of possible shipping rates
     * This method was created only because PayPal has issues with returning the selected code.
     * If in future the issue is fixed, we don't need to attempt to match it. It would be enough to set the method code
     * before collecting shipping rates
     *
     * @param Address $address
     * @param string $selectedCode
     * @return string
     */
    protected function _matchShippingMethodCode(Address $address, $selectedCode)
    {
        $options = $this->_prepareShippingOptions($address, false);
        foreach ($options as $option) {
            if ($selectedCode === $option['code'] // the proper case as outlined in documentation
                || $selectedCode === $option['name'] // workaround: PayPal may return name instead of the code
                // workaround: PayPal may concatenate code and name, and return it instead of the code:
                || $selectedCode === "{$option['code']} {$option['name']}"
            ) {
                return $option['code'];
            }
        }
        return '';
    }

    /**
     * Prepare quote for guest checkout order submit
     *
     * @return $this
     */
    protected function _prepareGuestQuote()
    {
        $quote = $this->_quote;
        $quote->setCustomerId(null)
            ->setCustomerEmail($quote->getBillingAddress()->getEmail())
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
        return $this;
    }

    /**
     * Prepare quote for customer registration and customer order submit
     * and restore magento customer data from quote
     *
     * @return void
     */
    protected function _prepareNewCustomerQuote()
    {
        $quote      = $this->_quote;
        $billing    = $quote->getBillingAddress();
        $shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();

        /** @var \Magento\Customer\Service\V1\Data\AddressBuilder $billingAddressBuilder */
        $billingAddressBuilder = $this->_addressBuilderFactory->create();
        $customerBilling = $billingAddressBuilder
            ->populate($billing->exportCustomerAddressData())
            ->setDefaultBilling(true);
        if ($shipping && !$shipping->getSameAsBilling()) {
            /** @var \Magento\Customer\Service\V1\Data\AddressBuilder $shippingAddressBuilder */
            $shippingAddressBuilder = $this->_addressBuilderFactory->create();
            $customerShipping = $shippingAddressBuilder
                ->populate($shipping->exportCustomerAddressData())
                ->setDefaultShipping(true)
                ->create();
            $shipping->setCustomerAddressData($customerShipping);
        } elseif ($shipping) {
            $customerBilling->setDefaultShipping(true);
        }
        $customerBilling = $customerBilling->create();
        $billing->setCustomerAddressData($customerBilling);
        /**
         * @todo integration with dynamic attributes customer_dob, customer_taxvat, customer_gender
         */
        if ($quote->getCustomerDob() && !$billing->getCustomerDob()) {
            $billing->setCustomerDob($quote->getCustomerDob());
        }

        if ($quote->getCustomerTaxvat() && !$billing->getCustomerTaxvat()) {
            $billing->setCustomerTaxvat($quote->getCustomerTaxvat());
        }

        if ($quote->getCustomerGender() && !$billing->getCustomerGender()) {
            $billing->setCustomerGender($quote->getCustomerGender());
        }

        $customerData = $this->_objectCopyService->getDataFromFieldset(
            'checkout_onepage_billing',
            'to_customer',
            $billing
        );

        $customer = $this->_customerBuilder->populateWithArray($customerData);

        $customer->setEmail($quote->getCustomerEmail());
        $customer->setPrefix($quote->getCustomerPrefix());
        $customer->setFirstname($quote->getCustomerFirstname());
        $customer->setMiddlename($quote->getCustomerMiddlename());
        $customer->setLastname($quote->getCustomerLastname());
        $customer->setSuffix($quote->getCustomerSuffix());

        $quote->setCustomerData($customer->create())->addCustomerAddressData($customerBilling);

        if (isset($customerShipping)) {
            $quote->addCustomerAddressData($customerShipping);
        }
    }

    /**
     * Prepare quote for customer order submit
     *
     * @return void
     */
    protected function _prepareCustomerQuote()
    {
        $quote      = $this->_quote;
        $billing    = $quote->getBillingAddress();
        $shipping   = $quote->isVirtual() ? null : $quote->getShippingAddress();

        $customer = $this->_customerAccountService->getCustomer($this->getCustomerSession()->getCustomerId());
        if (!$billing->getCustomerId() || $billing->getSaveInAddressBook()) {
            $billingAddress = $billing->exportCustomerAddressData();
            $billing->setCustomerAddressData($billingAddress);
        }
        if ($shipping
            && !$shipping->getSameAsBilling()
            && (!$shipping->getCustomerId() || $shipping->getSaveInAddressBook())
        ) {
            $shippingAddress = $shipping->exportCustomerAddressData();
            $shipping->setCustomerAddressData($shippingAddress);
        }

        $isBillingAddressDefaultBilling = false;
        $isBillingAddressDefaultShipping = false;
        if (!$customer->getDefaultBilling()) {
            $isBillingAddressDefaultBilling = true;
        }

        if ($shipping && isset($shippingAddress) && !$customer->getDefaultShipping()) {
            /** @var \Magento\Customer\Service\V1\Data\AddressBuilder $shippingAddressBuilder */
            $shippingAddressBuilder = $this->_addressBuilderFactory->create();
            $shippingAddress = $shippingAddressBuilder->populate($shippingAddress)
                ->setDefaultBilling(false)
                ->setDefaultShipping(true)
                ->create();
            $quote->addCustomerAddressData($shippingAddress);
        } else if (!$customer->getDefaultShipping()) {
            $isBillingAddressDefaultShipping = true;
        }

        if (isset($billingAddress)) {
            /** @var \Magento\Customer\Service\V1\Data\AddressBuilder $billingAddressBuilder */
            $billingAddressBuilder = $this->_addressBuilderFactory->create();
            $billingAddress = $billingAddressBuilder
                ->populate($billingAddress)
                ->setDefaultBilling($isBillingAddressDefaultBilling)
                ->setDefaultShipping($isBillingAddressDefaultShipping)
                ->create();
            $quote->addCustomerAddressData($billingAddress);
        }
        $quote->setCustomerData($customer);
    }

    /**
     * Involve new customer to system
     *
     * @return $this
     */
    protected function _involveNewCustomer()
    {
        $customer = $this->_quote->getCustomerData();
        $confirmationStatus = $this->_customerAccountService->getConfirmationStatus($customer->getId());
        if ($confirmationStatus === CustomerAccountServiceInterface::ACCOUNT_CONFIRMATION_REQUIRED) {
            $url = $this->_customerData->getEmailConfirmationUrl($customer->getEmail());
            $this->_messageManager->addSuccess(
            // @codingStandardsIgnoreStart
                __('Account confirmation is required. Please, check your e-mail for confirmation link. To resend confirmation email please <a href="%1">click here</a>.', $url)
            // @codingStandardsIgnoreEnd
            );
        } else {
            $this->getCustomerSession()->loginById($customer->getId());
        }
        return $this;
    }

    /**
     * Get customer session object
     *
     * @return \Magento\Customer\Model\Session
     */
    public function getCustomerSession()
    {
        return $this->_customerSession;
    }
}
