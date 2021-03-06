<?php
	namespace shanemcc\socketrelay\impl\messagehandler;

	use shanemcc\socketrelay\impl\ServerSocketHandler;

	abstract class MessageHandler {
		/**
		 * Create a new Message Handler.
		 */
		public function __construct() { }

		/**
		 * Get the MessageType of this handler.
		 *
		 * @return string MessageType of handler
		 */
		public abstract function getMessageType(): String;

		/**
		 * Get the description of this handler.
		 *
		 * @return string description of handler
		 */
		public abstract function getDescription(): String;

		/**
		 * Handle this message.
		 *
		 * @param ServerSocketHandler $handler SocketHandler that we are handling for
		 * @param string $number 'Number' from client
		 * @param string $key Key that was given
		 * @param string $messageParams Params that were given
		 * @return bool True if message was handled or false if we should fire
		 *              the invalid message handler
		 */
		public abstract function handleMessage(ServerSocketHandler $handler, String $number, String $key, String $messageParams): bool;

	}
