<?php
	namespace shanemcc\socket\iface;

	use \Throwable;

	/**
	 * Base Socket.
	 */
	abstract class Socket {
		/** @var string Host to use. */
		private $host;

		/** @var int Port to use. */
		private $port;

		/** @var int Timeout for inactive connectons. */
		private $timeout;

		/** @var SocketHandlerFactory Factory to create SocketHandlers. */
		private $factory;

		/** @var MessageLoop Our MessageLoop */
		private $loop;

		/** @var callable Error Handler */
		private $errorHandler;

		/**
		 * Create a new Socket.
		 *
		 * @param MessageLoop $loop Our message loop
		 * @param string  $host Host to use
		 * @param int $port Port to use
		 * @param int $timeout How long to allow client sockets to be idle (-1 for infinite)
		 */
		public function __construct(MessageLoop $loop, String $host, int $port, int $timeout) {
			$this->loop = $loop;
			$this->host = $host;
			$this->port = $port;
			$this->timeout = $timeout;
		}

		/**
		 * Get our error Handler.
		 *
		 * @return callable Our error handler
		 */
		public function getErrorHandler(): Callable {
			return $this->errorHandler;
		}

		/**
		 * Set our error Handler.
		 *
		 * @param callable $handler New error handler
		 */
		public function setErrorHandler(Callable $handler) {
			$this->errorHandler = $handler;
		}

		/**
		 * Handle an error.
		 *
		 * @param string $handlerName Handler name
		 * @param Throwable $throwable The exception
		 */
		public function onError(String $handlerName, Throwable $throwable) {
			if ($this->errorHandler !== null) {
				try {
					call_user_func($this->errorHandler, $handlerName, $throwable);
				} catch (Throwable $t) {
					$this->defaultOnError($handlerName, $throwable);
				}
			} else {
				$this->defaultOnError($handlerName, $throwable);
			}
		}

		/**
		 * Display exception information.
		 *
		 * @param string $handlerName Handler name
		 * @param Throwable $throwable The exception
		 */
		public function defaultOnError(String $handlerName, Throwable $throwable) {
			echo 'Throwable in ', $handlerName, ' handler.', "\n";
			echo "\t", $throwable->getMessage(), "\n";
			foreach (explode("\n", $throwable->getTraceAsString()) as $t) {
				echo "\t\t", $t, "\n";
			}
		}

		/**
		 * Get our message loop.
		 *
		 * @return MessageLoop Our message loop
		 */
		public function getMessageLoop(): MessageLoop {
			return $this->loop;
		}

		/**
		 * Get our host.
		 *
		 * @return string host
		 */
		public function getHost(): String {
			return $this->host;
		}

		/**
		 * Get our port.
		 *
		 * @return int port
		 */
		public function getPort(): int {
			return $this->port;
		}

		/**
		 * Get our timeout value.
		 *
		 * @return int timeout value
		 */
		public function getTimeout(): int {
			return $this->timeout;
		}

		/**
		 * Set our SocketHandlerFactory.
		 *
		 * @param SocketHandlerFactory $factory Factory to create SocketHandlers
		 */
		public function setSocketHandlerFactory(SocketHandlerFactory $factory) {
			$this->factory = $factory;
		}

		/**
		 * Get our SocketHandlerFactory.
		 *
		 * @return SocketHandlerFactory Factory that creates SocketHandlers
		 */
		public function getSocketHandlerFactory(): SocketHandlerFactory {
			return $this->factory;
		}

		/**
		 * Called to start the socket listening.
		 */
		public abstract function listen();

		/**
		 * Called to start the socket as a client socket.
		 */
		public abstract function connect();

		/**
		 * Close the server and all open connections.
		 *
		 * @param string $message Reason for closing
		 */
		public abstract function close(String $message = 'Server closing.');
	}
