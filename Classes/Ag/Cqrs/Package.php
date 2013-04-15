<?php
namespace Ag\Cqrs;
use TYPO3\Flow\Package\Package as BasePackage;

class Package extends BasePackage {

	/**
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
			// This is not nice, but Simplepie is not PSR-0 compatible and would break if we don't do it.
		require \TYPO3\Flow\Utility\Files::concatenatePaths(array(FLOW_PATH_PACKAGES, 'Libraries/simplepie/simplepie/autoloader.php'));
	}

}

?>