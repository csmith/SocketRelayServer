<?php
	namespace shanemcc\socketrelayserver\impl\ReactSocket;

	use React\EventLoop\Factory as EventLoopFactory;
	use React\EventLoop\LoopInterface;

	use React\Socket\TcpServer;
	use React\Socket\ConnectionInterface;

	use shanemcc\socketrelayserver\iface\SocketServer as BaseSocketServer;
	use shanemcc\socketrelayserver\impl\ReactSocket\ClientConnection;

	/**
	 * SocketServer Implemenation using ReactPHP library.
	 */
	class SocketServer extends BaseSocketServer {
		/** @var ConcertoSocketServer Underlying SocketServer */
		private $server;

		/** @var LoopInterface Event Loop handler. */
		private $loop;

		/** @var Array of open handlers. */
		private $handlers;

		/** @inheritDoc */
		public function listen() {
			if ($this->loop !== null) { throw new Exception('Already Listening.'); }

 			$this->handlers = new \SplObjectStorage();

			$this->loop = EventLoopFactory::create();
			$this->server = new TcpServer($this->getHost() . ':' . $this->getPort(), $this->loop);

			$this->server->on('connection', function(ConnectionInterface $conn) {
				$clientConnection = new ClientConnection($conn);
				$handler = $this->getSocketHandlerFactory()->get($clientConnection);
				$this->handlers[$handler] = ['time' => time(), 'conn' => $clientConnection];

				try { $handler->onConnect(); } catch (Throwable $ex) { $this->onError('connect', $ex); }

				$conn->on('data', function (String $data) use ($handler) {
					try { $handler->onData(trim($data)); } catch (Throwable $ex) { $this->onError('data', $ex); }
					$this->handlers[$handler]['time'] = time();
				});

				$conn->on('close', function () use ($handler) {
					try { $handler->onClose(); } catch (Throwable $ex) { $this->onError('close', $ex); }
					unset($this->handlers[$handler]);
				});
			});

			$this->loop->addPeriodicTimer($this->getTimeout(), function() {
				$timeout = time() - $this->getTimeout();

				$killed = [];

				foreach ($this->handlers as $handler) {
					$time = $this->handlers[$handler]['time'];

					if ($time < $timeout) {
						$close = true;
						try { $close = $handler->onTimeout(); } catch (Throwable $ex) { $this->onError('timeout', $ex); }

						if ($close) {
							$killed[] = $handler;
						} else {
							$this->handlers[$handler] = time();
						}
					}
				}

				foreach ($killed as $handler) {
					$this->handlers[$handler]['conn']->close();
					unset($this->handlers[$handler]);
				}
			});

			$this->loop->run();
		}

		/**
		 * Display exception information.
		 *
		 * @param String $handlerName Handler name.
		 * @param Throwable $throwable The exception.
		 */
		public function onError(String $handlerName, Throwable $throwable) {
			echo 'Throwable in ', $handlerName, ' handler.', "\n";
			echo "\t", $throwable->getMessage(), "\n";
			foreach ($throwable->getTrace() as $t) {
				echo "\t\t", $t, "\n";
			}
		}
	}
