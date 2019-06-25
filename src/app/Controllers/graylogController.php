<?php
/**
 * Created by PhpStorm.
 * User: alpha
 * Date: 2018/9/20
 * Time: 18:04
 */

namespace app\Controllers;

use Gelf\Publisher;
use Gelf\Message;
use Gelf\PublisherInterface;
use Gelf\Transport\UdpTransport;
use Psr\Log\LogLevel;
use Exception;

class graylogController extends BaseController
{
    /**
     * @var string|null
     */
    protected $facility;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * Creates a PSR-3 Logger for GELF/Graylog2
     *
     * @param PublisherInterface|null $publisher
     * @param string|null $facility
     */
    public function initialization($controllerName, $methodName)
    {
        // if no publisher is provided build a "default" publisher
        // which is logging via Gelf over UDP to localhost on the default port
        $this->publisher = new Publisher(
            new UdpTransport($this->config['log']['graylog']['ip'], $this->config['log']['graylog']['port'])
        );
        $facility = 'strack_log';
        $this->setFacility($facility);
    }

    /**
     * Publishes a given message and context with given level
     * @param $level
     * @param int $rawMessage
     * @param array $context
     */
    public function log($level, $rawMessage, array $context = array())
    {
        $message = $this->initMessage($level, $rawMessage, $context);

        // add exception data if present
        if (isset($context['exception'])
            && $context['exception'] instanceof Exception
        ) {
            $this->initExceptionData($message, $context['exception']);
        }

        $this->publisher->publish($message);
    }

    /**
     * Returns the currently used publisher
     *
     * @return PublisherInterface
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * Sets a new publisher
     *
     * @param PublisherInterface $publisher
     */
    public function setPublisher(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * Returns the faciilty-name used in GELF
     *
     * @return string|null
     */
    public function getFacility()
    {
        return $this->facility;
    }

    /**
     * Sets the facility for GELF messages
     *
     * @param string|null $facility
     */
    public function setFacility($facility = null)
    {
        $this->facility = $facility;
    }

    /**
     * Initializes message-object
     *
     * @param  mixed $level
     * @param  mixed $message
     * @param  array $context
     * @return Message
     */
    protected function initMessage($level, $message, array $context)
    {
        // assert that message is a string, and interpolate placeholders
        $message = (string)$message;
        $context = $this->initContext($context);
        $message = self::interpolate($message, $context);

        // create message object
        $messageObj = new Message();
        $messageObj->setLevel($level);
        $messageObj->setShortMessage($message);
        $messageObj->setFacility($this->facility);

        foreach ($context as $key => $value) {
            $messageObj->setAdditional($key, $value);
        }

        return $messageObj;
    }

    /**
     * Initializes context array, ensuring all values are string-safe
     *
     * @param array $context
     * @return array
     */
    protected function initContext($context)
    {
        foreach ($context as $key => &$value) {
            switch (gettype($value)) {
                case 'string':
                case 'integer':
                case 'double':
                    // These types require no conversion
                    break;
                case 'array':
                case 'boolean':
                    $value = json_encode($value);
                    break;
                case 'object':
                    if (method_exists($value, '__toString')) {
                        $value = (string)$value;
                    } else {
                        $value = '[object (' . get_class($value) . ')]';
                    }
                    break;
                case 'NULL':
                    $value = 'NULL';
                    break;
                default:
                    $value = '[' . gettype($value) . ']';
                    break;
            }
        }

        return $context;
    }

    /**
     * Initializes Exceptiondata with given message
     *
     * @param Message $message
     * @param Exception $exception
     */
    protected function initExceptionData(Message $message, Exception $exception)
    {
        $message->setLine($exception->getLine());
        $message->setFile($exception->getFile());

        $longText = "";

        do {
            $longText .= sprintf(
                "%s: %s (%d)\n\n%s\n",
                get_class($exception),
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getTraceAsString()
            );

            $exception = $exception->getPrevious();
        } while ($exception && $longText .= "\n--\n\n");

        $message->setFullMessage($longText);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * Reference implementation
     * @param mixed $message
     * @param array $context
     * @return string
     */
    private static function interpolate($message, array $context)
    {
        // build a replacement array with braces around the context keys
        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = $val;
        }

        // interpolate replacement values into the message and return
        return strtr($message, $replace);
    }

    /**
     * 通用返回数据
     * @param $data
     * @param int $code
     * @param string $msg
     */
    protected function response($data, $code = 200, $msg = '')
    {
        $resData = ["status" => $code, "message" => $msg, "data" => $data];
        $this->http_output->end(json_encode($resData));
    }

    /**
     * 记录自定义log
     */
    public function http_write()
    {
        $this->log(LogLevel::INFO, "this is a test");
        $this->response([], 404, "Wrong token.");
    }

}
