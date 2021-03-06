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
 * @category    Magento
 * @package     Magento_Sales
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Magento\Sales\Model\Resource\Quote;

/**
 * Quote payment resource model
 */
class Payment extends \Magento\Sales\Model\Resource\AbstractResource
{
    /**
     * Serializeable field: additional_information
     *
     * @var array
     */
    protected $_serializableFields = array('additional_information' => array(null, array()));

    /**
     * @var \Magento\Sales\Model\Payment\Method\Converter
     */
    protected $_paymentConverter;

    /**
     * @param \Magento\App\Resource $resource
     * @param \Magento\Stdlib\DateTime $dateTime
     * @param \Magento\Sales\Model\Payment\Method\Converter $paymentConverter
     */
    public function __construct(
        \Magento\App\Resource $resource,
        \Magento\Stdlib\DateTime $dateTime,
        \Magento\Sales\Model\Payment\Method\Converter $paymentConverter
    ) {
        $this->_paymentConverter = $paymentConverter;
        parent::__construct($resource, $dateTime);
    }

    /**
     * Main table and field initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_converter = $this->_paymentConverter;
        $this->_init('sales_flat_quote_payment', 'payment_id');
    }
}
