<?php
namespace Rxnet\Zmq;


use React\EventLoop\LoopInterface;
use Rxnet\Event\Event;
use Rxnet\Zmq\Serializer\MsgPack;
use Rxnet\Zmq\Serializer\Serializer;

class ZeroMQ extends SocketWithQa
{
    protected $loop;
    protected $context;
    protected $serializer;

    public function __construct(LoopInterface $loop, Serializer $serializer = null, \ZMQContext $context = null)
    {
        $this->loop = $loop;
        $this->serializer = $serializer ?: new MsgPack();
        $this->context = $context ?: new \ZMQContext(10);
    }

    public function push($dsn = null)
    {
        $socket = new SocketWithQa($this->context->getSocket(\ZMQ::SOCKET_PUSH), $this->serializer, $this->loop);
        if ($dsn) {
            $socket->bind($dsn);
        }
        return $socket;
    }

    public function pull($dsn = null)
    {
        $socket = new Socket($this->context->getSocket(\ZMQ::SOCKET_PULL), $this->serializer, $this->loop);
        if ($dsn) {
            $socket->connect($dsn);
        }
        return $socket;
    }

    public function router($dsn = null)
    {
        $socket = new SocketWithReqRep($this->context->getSocket(\ZMQ::SOCKET_ROUTER), $this->serializer, $this->loop);
        if ($dsn) {
            $socket->bind($dsn);
        }
        return $socket;
    }

    public function dealer($dsn = null, $identity = null)
    {
        $socket = new SocketWithReqRep($this->context->getSocket(\ZMQ::SOCKET_DEALER), $this->serializer, $this->loop);
        if ($dsn) {
            $socket->connect($dsn, $identity);
        }
        return $socket;
    }

    public function req($dsn = null)
    {
        $socket = new SocketWithReqRep($this->context->getSocket(\ZMQ::SOCKET_REQ), $this->serializer, $this->loop);
        if ($dsn) {
            $socket->connect($dsn);
        }
        return $socket;
    }

    public function rep($dsn = null)
    {
        $socket = new SocketWithReqRep($this->context->getSocket(\ZMQ::SOCKET_REP), $this->serializer, $this->loop);
        if ($dsn) {
            $socket->bind($dsn);
        }
        return $socket;
    }

    public function ack($dsn = null)
    {
        $socket = new SocketWithReqRep($this->context->getSocket(\ZMQ::SOCKET_REP), $this->serializer, $this->loop);
        if ($dsn) {
            $socket->bind($dsn);
        }
        return $socket->flatMap(function (Event $event) use($socket) {
            $ack = new Event('/zmq/ack', [], ['id' => $event->getLabel('id')]);
            return $socket->send($ack)->map(function() use($event) {
                return $event;
            });
        });
    }
}