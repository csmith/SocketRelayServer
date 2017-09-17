#!/usr/bin/php
<?php
	require_once(__DIR__ . '/functions.php');

	use shanemcc\socketrelayserver\SocketRelayServer;
	use shanemcc\socketrelayserver\iface\ReportHandler;
	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelayserver\impl\SocketRelay\SocketHandler as SocketRelaySocketHandler;

	// TODO: Do this better.
	class RelayReportHandler implements ReportHandler {
		/** @var Array Array of config. */
		private $config;

		/**
		 * Create the ReportHandler.
		 *
		 * @param $config Array Array of config.
		 */
		public function __construct($config) {
			$this->config = $config;
		}

		/** @inheritDoc */
		public function handle(BaseSocketHandler $handler, String $messageType, String $number, String $key, String $messageParams) {
			$reportHandler = $this->config['reporthandler'];
			$config = isset($this->config['reporter'][$reportHandler]) ? $this->config['reporter'][$reportHandler] : [];

			if ($reportHandler == 'socketrelay') {
				$fp = fsockopen($config['host'], $config['port'], $errno, $errstr, 30);
				if ($fp) {
					$out = '-- ' . $config['key'] . ' ' . $messageType . ' ' . $messageParams . "\n";
					fwrite($fp, $out);
					fclose($fp);
				}

				if ($handler instanceof SocketRelaySocketHandler) {
					$handler->sendResponse($number, $messageType, 'Message relayed.');
				}
			}
		}
	}


	$server = new SocketRelayServer($config['listen']['host'], (int)$config['listen']['port'], (int)$config['listen']['timeout']);

	$server->setValidKeys($config['validKeys']);
	$server->setReportHandler(new RelayReportHandler($config));
	$server->run();