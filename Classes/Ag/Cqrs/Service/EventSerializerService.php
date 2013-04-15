<?php

namespace Ag\Cqrs\Service;

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class EventSerializerService {

	/**
	 * @param \Ag\Cqrs\Domain\Event\Event $event
	 * @return array
	 */
	public function serialize(\Ag\Cqrs\Domain\Event\Event $event) {
		$className = get_class($event);
		$className = explode('\\', $className);

		$data = array();
		$data['EventType'] = array_pop($className);

		$data['Data'] = \TYPO3\Flow\Reflection\ObjectAccess::getGettableProperties($event);
		unset($data['Data']['aggregateId']);
		unset($data['Data']['eventId']);

		return $data;
	}

	/**
	 * @param string $eventType
	 * @param string $aggregateId
	 * @param string $eventId
	 * @param array $data
	 * @return \Ag\Cqrs\Domain\Event\Event
	 */
	public function deserialize($eventType, $aggregateId, $eventId, $data) {
		$class = '\Ag\Test\Domain\Event\\' . $eventType;

		$event = new $class($aggregateId);

		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($event, 'eventId', $eventId, TRUE);

		foreach($data as $key=>$value) {
			\TYPO3\Flow\Reflection\ObjectAccess::setProperty($event, $key, $value, TRUE);
		}

		return $event;
	}
}