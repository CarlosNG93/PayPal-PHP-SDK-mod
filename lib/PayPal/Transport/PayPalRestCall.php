<?php

namespace PayPal\Transport;

use PayPal\Core\PayPalHttpConfig;
use PayPal\Core\PayPalHttpConnection;
use PayPal\Core\PayPalLoggingManager;
use PayPal\Rest\ApiContext;

/**
 * Class PayPalRestCall
 *
 * @package PayPal\Transport
 */
class PayPalRestCall
{


    /**
     * Paypal Logger
     *
     * @var PayPalLoggingManager logger interface
     */
    private $logger;

    /**
     * API Context
     *
     * @var ApiContext
     */
    private $apiContext;


    /**
     * Default Constructor
     *
     * @param ApiContext $apiContext
     */
    public function __construct(ApiContext $apiContext)
    {
        $this->apiContext = $apiContext;
        $this->logger = PayPalLoggingManager::getInstance(__CLASS__);
    }

    /**
     * @param array  $handlers Array of handlers
     * @param string $path     Resource path relative to base service endpoint
     * @param string $method   HTTP method - one of GET, POST, PUT, DELETE, PATCH etc
     * @param string $data     Request payload
     * @param array  $headers  HTTP headers
     * @return mixed
     * @throws \PayPal\Exception\PayPalConnectionException
     */
    public function execute($path, $method, $data = '', $headers = array(), $handlers = array())
    {
        $config = $this->apiContext->getConfig();
        $httpConfig = new PayPalHttpConfig(null, $method, $config);

        // Asegura que $headers sea un arreglo
        $headers = is_array($headers) ? $headers : [];

        // Agrega el encabezado 'Content-Type' si no está ya presente
        if (!array_key_exists('Content-Type', $headers)) {
            $headers['Content-Type'] = 'application/json';
        }

        $httpConfig->setHeaders($headers);

        // Si se configura un proxy a través de la configuración, agrégalo
        if (!empty($config['http.Proxy'])) {
            $httpConfig->setHttpProxy($config['http.Proxy']);
        }

        // Maneja los controladores específicos si se proporcionan
        foreach ($handlers as $handler) {
            if (!is_object($handler)) {
                $fullHandler = "\\" . (string)$handler;
                $handler = new $fullHandler($this->apiContext);
            }
            $handler->handle($httpConfig, $data, array('path' => $path, 'apiContext' => $this->apiContext));
        }

        $connection = new PayPalHttpConnection($httpConfig, $config);
        $response = $connection->execute($data);

        return $response;
    }
}
