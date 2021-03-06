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
 * @package     Magento_Catalog
 * @subpackage  integration_tests
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Extends valid Url rewrites
 */
require __DIR__ . '/url_rewrites.php';

/**
 * Invalid rewrite for product assigned to different category
 */
/** @var $rewrite \Magento\UrlRewrite\Model\UrlRewrite */
$rewrite = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('Magento\UrlRewrite\Model\UrlRewrite');
$rewrite->setStoreId(
    1
)->setIdPath(
    'product/1/4'
)->setRequestPath(
    'category-2/simple-product.html'
)->setTargetPath(
    'catalog/product/view/id/1'
)->setIsSystem(
    1
)->setCategoryId(
    4
)->setProductId(
    1
)->save();

/**
 * Invalid rewrite for product assigned to category that doesn't belong to store
 */
$rewrite = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('Magento\UrlRewrite\Model\UrlRewrite');
$rewrite->setStoreId(
    1
)->setIdPath(
    'product/1/5'
)->setRequestPath(
    'category-5/simple-product.html'
)->setTargetPath(
    'catalog/product/view/id/1'
)->setIsSystem(
    1
)->setCategoryId(
    5
)->setProductId(
    1
)->save();
