<?php

namespace Etu\Module\BuckUTTBundle\Layer;

use Etu\Module\BuckUTTBundle\Layer\History\HistoryItem;
use Etu\Module\BuckUTTBundle\Soap\SoapManager;
use Etu\Module\BuckUTTBundle\Soap\SoapManagerBuilder;

class BuckUTTLayer
{
	/**
	 * @var SoapManagerBuilder
	 */
	protected $builder;

	/**
	 * @param SoapManagerBuilder $builder
	 */
	public function __construct(SoapManagerBuilder $builder)
	{
		$this->builder = $builder;
	}

	/**
	 * @return string
	 */
	public function getUserCredit()
	{
		$client = $this->builder->createManager('SBUY');
		return number_format($client->getCredit() / 100, 2);
	}
	
	/**
	 * @return int
	 */
	public function getSessionStatus()
	{
		$client = $this->builder->createManager('SBUY');
		return $client->getSessionStatus();
	}

	/**
	 * @return float
	 */
	public function getLastYearHistory()
	{
		return $this->getHistoryBetween(new \DateTime('1 year ago'), new \DateTime());
	}

	/**
	 * @param \DateTime $start
	 * @param \DateTime $end
	 * @return array
	 */
	public function getReloadsBetween(\DateTime $start, \DateTime $end)
	{
		$history = array();
		$dates = array();

		/** @var SoapManager $client */
		$client = $this->builder->createManager('SADMIN');

		$reloads = $client->getHistoriqueRecharge($start->format('U'), $end->format('U'));

		if (! is_array($reloads)) {
			return array();
		}

		foreach ($reloads as $reload) {
			$item = new HistoryItem();
			$item->setType(HistoryItem::TYPE_RELOAD)
				->setDate(\DateTime::createFromFormat('U', $reload[0]))
				->setObject($reload[1])
				->setPoint($reload[4])
				->setAmount(number_format($reload[5]/100, 2));

			$history[] = $item;
			$dates[] = $reload[0];
		}

		array_multisort($dates, SORT_DESC, $history);

		return $history;
	}

	/**
	 * @param \DateTime $start
	 * @param \DateTime $end
	 * @return array
	 */
	public function getPurchasesBetween(\DateTime $start, \DateTime $end)
	{
		$history = array();
		$dates = array();

		/** @var SoapManager $client */
		$client = $this->builder->createManager('SADMIN');

		$purchases = $client->getHistoriqueAchats($start->format('U'), $end->format('U'));

		if ((int) $purchases == 400){
			return array();
		}

		foreach ($purchases as $purchase) {
			$item = new HistoryItem();
			$item->setType(HistoryItem::TYPE_BUY)
				->setDate(\DateTime::createFromFormat('U', $purchase[0]))
				->setObject($purchase[1])
				->setUser($purchase[2].' '.$purchase[3])
				->setPoint($purchase[4])
				->setFundation($purchase[5])
				->setAmount(number_format($purchase[6]/100, 2));

			$history[] = $item;
			$dates[] = $purchase[0];
		}

		array_multisort($dates, SORT_DESC, $history);

		return $history;
	}

	/**
	 * @param \DateTime $start
	 * @param \DateTime $end
	 * @return array
	 */
	public function getHistoryBetween(\DateTime $start, \DateTime $end)
	{
		/** @var HistoryItem[] $history */
		$history = array();
		$dates = array();

		/** @var SoapManager $client */
		$client = $this->builder->createManager('SADMIN');

		$purchases = $client->getHistoriqueAchats($start->format('U'), $end->format('U'));

		if (! is_array($purchases)) {
			return array();
		}

		$subObjects = array();

		foreach ($purchases as $purchase) {
			$item = new HistoryItem();
			$item->setType(HistoryItem::TYPE_BUY)
				->setDate(\DateTime::createFromFormat('U', $purchase[0]))
				->setObject($purchase[1])
				->setUser($purchase[2].' '.$purchase[3])
				->setPoint($purchase[4])
				->setFundation($purchase[5])
				->setAmount(number_format($purchase[6]/100, 2));

			if ($item->getAmount() == '0.00') {
				$subObjects[] = $item;
			} else {
				if (! empty($subObjects)) {
					$item->setSubObjects($subObjects);
					$subObjects = array();
				}

				$history[] = $item;
				$dates[] = $purchase[0];
			}
		}

		$reloads = $client->getHistoriqueRecharge($start->format('U'), $end->format('U'));

		if ((int) $reloads == 400){
			array_multisort($dates, SORT_DESC, $history);
			return $history;
		}

		foreach ($reloads as $reload) {
			$item = new HistoryItem();
			$item->setType(HistoryItem::TYPE_RELOAD)
				->setDate(\DateTime::createFromFormat('U', $reload[0]))
				->setObject($reload[1])
				->setPoint($reload[4])
				->setAmount(number_format($reload[5]/100, 2));

			$history[] = $item;
			$dates[] = $reload[0];
		}

		array_multisort($dates, SORT_DESC, $history);

		return $history;
	}
}
