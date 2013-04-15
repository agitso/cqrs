<?php

namespace Ag\Cqrs\Domain\Event;


abstract class Event {

	/**
	 * @var array
	 */
	protected $eventId;

	/**
	 * @var string
	 */
	protected $aggregateId;

	/**
	 * @param string $aggregateId
	 */
	public function __construct($aggregateId) {
		$this->aggregateId = $aggregateId;
	}

	/**
	 * @return array
	 */
	public function getEventId() {
		return $this->eventId;
	}

	/**
	 * @return string
	 */
	public function getAggregateId() {
		return $this->aggregateId;
	}
}