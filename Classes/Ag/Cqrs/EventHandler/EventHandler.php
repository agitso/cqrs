<?php
namespace Ag\Cqrs\EventHandler;

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class EventHandler {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @param \Ag\Cqrs\Domain\Event\Event $event
	 * @throws \Exception
	 * @return void
	 */
	public function handle(\Ag\Cqrs\Domain\Event\Event $event) {

		$namespaceparts = explode('\\', get_class($event));

		$eventName = array_pop($namespaceparts);
		array_pop($namespaceparts);
		$namespaceparts[] = 'EventHandler';
		$namespaceparts[] = $eventName.'Handler';

		$className = implode('\\', $namespaceparts);

		if(!class_exists($className)) {
			throw new \Exception('Could not find event handler.');
		}

		$eventHandler = $this->objectManager->get($className);
		if(!method_exists($eventHandler, 'handle')) {
			throw new \Exception('Could not find handle method.');
		}

		$eventHandler->handle($event);
	}
}
?>