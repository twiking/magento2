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
namespace Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer;

/**
 * Adminhtml sales create order product search grid product name column renderer
 *
 * @category   Magento
 * @package    Magento_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Product extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text
{
    /**
     * Render product name to add Configure link
     *
     * @param   \Magento\Object $row
     * @return  string
     */
    public function render(\Magento\Object $row)
    {
        $rendered = parent::render($row);
        $isConfigurable = $row->canConfigure();
        $style = $isConfigurable ? '' : 'disabled';
        $prodAttributes = $isConfigurable ? sprintf(
            'list_type = "product_to_add" product_id = %s',
            $row->getId()
        ) : 'disabled="disabled"';
        return sprintf(
            '<a href="javascript:void(0)" class="action-configure %s" %s>%s</a>',
            $style,
            $prodAttributes,
            __('Configure')
        ) . $rendered;
    }
}
