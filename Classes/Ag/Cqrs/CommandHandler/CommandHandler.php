<?php
namespace Ag\Cqrs\CommandHandler;

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class CommandHandler {

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @param \Ag\Cqrs\Command\Command $command
	 * @throws \Exception
	 * @return void
	 */
	public function handle(\Ag\Cqrs\Command\Command $command) {

		$namespaceparts = explode('\\', get_class($command));

		$commandName = array_pop($namespaceparts);
		array_pop($namespaceparts);
		$namespaceparts[] = 'CommandHandler';
		$namespaceparts[] = $commandName.'Handler';

		$className = implode('\\', $namespaceparts);

		if(!class_exists($className)) {
			throw new \Exception('Could not find command handler.');
		}

		$commandHandler = $this->objectManager->get($className);
		if(!method_exists($commandHandler, 'handle')) {
			throw new \Exception('Could not find handle method.');
		}

		$commandHandler->handle($command);
	}

}
?>