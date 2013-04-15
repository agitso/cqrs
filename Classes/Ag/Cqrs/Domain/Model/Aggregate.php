<?php

namespace Ag\Cqrs\Domain\Model;


abstract class Aggregate {

	/**
	 * @var string
	 */
	protected $aggregateId;

	/**
	 * @var array
	 */
	protected $uncommittedEvents = array();

	/**
	 * @var int
	 */
	protected $version = 0;

	/**
	 * @param \Ag\Cqrs\Domain\Event\Event $event
	 */
	public function apply(\Ag\Cqrs\Domain\Event\Event $event) {
		$className = get_class($event);
		$className = explode('\\', $className);
		$eventType = array_pop($className);

		$method = 'apply' . $eventType;

		if (method_exists($this, $method)) {
			$this->$method($event);
		}

		if ($event->getEventId() === NULL) {
			$this->uncommittedEvents[] = $event;
		}

		$this->version++;
	}
}