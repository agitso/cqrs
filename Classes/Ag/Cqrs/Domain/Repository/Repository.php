<?php

namespace Ag\Cqrs\Domain\Repository;

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
abstract class Repository {

	/**
	 * @var \Ag\Cqrs\Service\EventSerializerService
	 * @Flow\Inject
	 */
	protected $eventSerializerService;


	/**
	 * @param \Ag\Cqrs\Domain\Model\Aggregate $aggregate
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 * @return void
	 */
	public function add(\Ag\Cqrs\Domain\Model\Aggregate $aggregate) {
		$aggregateId = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($aggregate, 'aggregateId', TRUE);
		$events = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($aggregate, 'uncommittedEvents', TRUE);
		$version = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($aggregate, 'version', TRUE);

		$stream = $this->getStreamName($aggregateId);

		if (count($events) === 0) {
			throw new \InvalidArgumentException('The aggregate is not created correctly (no events exists.)');
		}

		if ($version - count($events) !== 0) {
			throw new \InvalidArgumentException('The aggregate is already added to the repository.');
		}

		$data = array();
		$data['CorrelationId'] = \TYPO3\Flow\Utility\Algorithms::generateUUID();
		$data['ExpectedVersion'] = -1;
		$data['Events'] = array();

		foreach ($events as $event) {
			$event = $this->eventSerializerService->serialize($event);
			$event['EventId'] = \TYPO3\Flow\Utility\Algorithms::generateUUID();
			$event['Metadata'] = array(
				'aggregateId' => $aggregateId,
				'timestamp' => time(),
			);

			$data['Events'][] = $event;
		}

		$data_string = json_encode($data);

		$ch = curl_init('http://172.16.28.128:2113/streams/' . $stream);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string))
		);

		$result = curl_exec($ch);


		if (intval(curl_getinfo($ch, CURLINFO_HTTP_CODE)) !== 201) {
			curl_close($ch);
			throw new \Exception('Something went wrong: ' . $result);
		}
		;

		curl_close($ch);

		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($aggregate, 'uncommittedEvents', array(), TRUE);
	}


	/**
	 * @param \Ag\Cqrs\Domain\Model\Aggregate $aggregate
	 * @throws \InvalidArgumentException
	 * @throws \Exception
	 */
	public function update(\Ag\Cqrs\Domain\Model\Aggregate $aggregate) {
		$aggregateId = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($aggregate, 'aggregateId', TRUE);
		$events = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($aggregate, 'uncommittedEvents', TRUE);
		$version = \TYPO3\Flow\Reflection\ObjectAccess::getProperty($aggregate, 'version', TRUE);

		$stream = $this->getStreamName($aggregateId);

		if (count($events) === 0) {
			throw new \InvalidArgumentException('The aggregate is not created correctly (no events exists.)');
		}

		$data = array();
		$data['CorrelationId'] = \TYPO3\Flow\Utility\Algorithms::generateUUID();
		$data['ExpectedVersion'] = -2;
		$data['Events'] = array();

		foreach ($events as $event) {
			$event = $this->eventSerializerService->serialize($event);
			$event['EventId'] = \TYPO3\Flow\Utility\Algorithms::generateUUID();
			$event['Metadata'] = array(
				'aggregateId' => $aggregateId,
				'timestamp' => time(),
			);

			$data['Events'][] = $event;
		}

		$data_string = json_encode($data);

		$ch = curl_init('http://172.16.28.128:2113/streams/' . $stream);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string))
		);

		$result = curl_exec($ch);


		if (intval(curl_getinfo($ch, CURLINFO_HTTP_CODE)) !== 201) {
			curl_close($ch);
			throw new \Exception('Something went wrong: ' . $result);
		}
		;

		curl_close($ch);

		\TYPO3\Flow\Reflection\ObjectAccess::setProperty($aggregate, 'uncommittedEvents', array(), TRUE);
	}

	/**
	 * @param string $aggregateId
	 * @return mixed
	 */
	public function getById($aggregateId) {
		$stream = $this->getStreamName($aggregateId);

		$events = array();

		$start = microtime(TRUE);

		$last = $this->getLastUrl($stream);

		$this->loadEvents($events, $last, $aggregateId);

		$className = get_called_class();
		$className = explode('\\', $className);
		$aggregateClass = array_pop($className);
		$aggregateClass = str_replace('Repository', '', $aggregateClass);

		array_pop($className);

		array_push($className, 'Model');
		array_push($className, $aggregateClass);
		$className = implode('\\', $className);

		$aggregateString = sprintf('O:%d:"%s":0:{}', strlen($className), $className);
		$aggregate = unserialize($aggregateString);

		foreach ($events as $event) {
			$aggregate->apply($event);
		}

		$end = microtime(TRUE);

		$time = ($end - $start) * 1000;

		return $aggregate;
	}

	/**
	 * @return null|string
	 */
	protected function getLastUrl($stream) {
		return 'http://172.16.28.128:2113/streams/' . $stream . '/range/99/100?format=json&embed=body';
	}

	/**
	 * @param array $events
	 * @param string $uri
	 * @param string $aggregateId
	 */
	protected function loadEvents(&$events, $uri, $aggregateId) {
		$result = json_decode(file_get_contents($uri), TRUE);
		$noItems = count($result['entries']);

		for ($i = $noItems - 1; $i >= 0; $i--) {
			if (substr($result['entries'][$i]['eventType'], 0, 1) !== '$') {
				$events[] = $this->eventSerializerService->deserialize(
					$result['entries'][$i]['eventType'],
					$aggregateId,
					$result['entries'][$i]['eventNumber'],
					json_decode($result['entries'][$i]['data'], TRUE));
			}
		}

		if ($noItems > 0) {
			$this->loadEvents($events, $result['links'][3]['uri'] . '?format=json&embed=body', $aggregateId);
		}
	}

	/**
	 * @param string $aggregateId
	 * @return string
	 */
	protected function getStreamName($aggregateId) {
		$className = get_called_class();
		$className = explode('\\', $className);
		$aggregateClass = array_pop($className);
		$aggregateClass = str_replace('Repository', '', $aggregateClass);

		return $aggregateClass . '-' . $aggregateId;
	}


}