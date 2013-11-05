<?php
/**
 * plentymarkets shopware connector
 * Copyright © 2013 plentymarkets GmbH
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License, supplemented by an additional
 * permission, and of our proprietary license can be found
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "plentymarkets" is a registered trademark of plentymarkets GmbH.
 * "shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, titles and interests in the
 * above trademarks remain entirely with the trademark owners.
 *
 * @copyright  Copyright (c) 2013, plentymarkets GmbH (http://www.plentymarkets.com)
 * @author     Daniel Bächtle <daniel.baechtle@plentymarkets.com>
 */


/**
 * PlentymarketsImportEntityItemStock provides the actual item stock import funcionality. Like the other import
 * entities this class is called in PlentymarketsImportController.
 * The data import takes place based on plentymarkets SOAP-calls.
 *
 * @author Daniel Bächtle <daniel.baechtle@plentymarkets.com>
 *
 */
class PlentymarketsImportEntityItemStock
{

	/**
	 * Updates the stock for the given item detail id
	 *
	 * @param integer $itemDetailId
	 * @param float $stock
	 */
	public static function update($itemDetailId, $stock)
	{
		// Get the detail
		$Detail = Shopware()->Models()->find('Shopware\Models\Article\Detail', $itemDetailId);

		if (!$Detail instanceof Shopware\Models\Article\Detail)
		{
			PlentymarketsLogger::getInstance()->error('Sync:Item:Stock', 'The stock of the item detail with the id »'. $itemDetailId .'« could not be updated (detail corrupt)', 3511);
		}
		else
		{
			self::updateByDetail($Detail, $stock);
		}
	}

	/**
	 * Updates the stock for the given item detail
	 *
	 * @param Shopware\Models\Article\Detail $Detail
	 * @param float $stock
	 */
	public static function updateByDetail(Shopware\Models\Article\Detail $Detail, $stock)
	{
		$itemWarehousePercentage = PlentymarketsConfig::getInstance()->getItemWarehousePercentage(100);

		if ($itemWarehousePercentage > 100 || $itemWarehousePercentage <= 0)
		{
			$itemWarehousePercentage = 100;
		}

		if ($stock > 0)
		{
			// At least one
			$stock = max(1, ceil($stock / 100 * $itemWarehousePercentage));
		}

		// Remember the last stock (for the log message)
		$previousStock = $Detail->getInStock();

		// Nothing to to
		if ($previousStock == $stock)
		{
			return;
		}

		// Set the stock
		$Detail->setInStock($stock);

		// And save it
		Shopware()->Models()->persist($Detail);
		Shopware()->Models()->flush();

		// Log
		$diff = $stock - $previousStock;
		if ($diff > 0)
		{
			$diff = '+'. $diff;
		}
		PlentymarketsLogger::getInstance()->message('Sync:Item:Stock', 'The stock of the item »'. $Detail->getArticle()->getName() .'« with the number »'. $Detail->getNumber() .'« has been rebooked to '. $stock .' ('. $diff .')');
	}
}
