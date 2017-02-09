<?php
namespace Absolute\SilexApi\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SwaggerCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('absolute:silexapi:generation:swagger')
            ->setDescription('Generate Swagger JSON file.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $swagger = [
            'swagger' => '2.0',
            'info' => [
                'description' => '',
                'version' => '1.0.0',
                'title' => 'Absolute SilexApi',
                'termsOfService' => '',
                'contact' => [
                    'email' => '',
                ],
                'license' => [
                    'name' => '',
                    'url'  => '',
                ],
            ],
            'host' => '{HOSTNAME}',
            'basePath' => '',
            'tags' => [
                [
                    'name' => 'test',
                    'description' => 'Test Endpoints',
                ],
                [
                    'name' => 'home',
                    'description' => 'API Home',
                ],
                [
                    'name' => 'user',
                    'description' => 'Users / Customers',
                ],
            ],
            'schemes' => [
                '{SCHEME}',
            ],
            'paths' => [],
            'securityDefinitions' => [
                'api_key' => [
                    'type' => 'apiKey',
                    'name' => 'api_key',
                    'in'   => 'header',
                ],
            ],
            'definitions' => [],
            'externalDocs' => [
                'description' => '',
                'url'         => '',
            ],
        ];

        // retrieve the data to be injected
        $apiData = require(DATA_DIR . 'api.php');
        
        // add paths
        foreach ($apiData['operations'] as $_operationId => $_operationData) {
            $_parameters = [];
            foreach ($_operationData['params'] ?? [] as $_paramId => $_paramData) {
                $_parameters[] = [
                    'name' => $_paramId,
                    'in' => 'path',
                    'description' => $_paramData['description'],
                    'required' => $_paramData['required'],
                    'type' => $_paramData['type'],
                ];
            }
            foreach ($_operationData['queries'] ?? [] as $_paramId => $_paramData) {
                $_parameters[] = [
                    'name' => $_paramId,
                    'in' => 'query',
                    'description' => $_paramData['description'],
                    'required' => $_paramData['required'],
                    'type' => $_paramData['type'],
                ];
            }
            $_body = $_operationData['body'];
            if ($_body ?? false) {
                $_parameters[] = [
                    'name' => 'body',
                    'in' => 'body',
                    'description' => $_body,
                    'required' => true,
                    'schema' => '#/definitions/' . $_body,
                ];
            }
            
            $swagger['paths'][$_operationData['path']][$_operationData['verb']] = [
                'tags' => $_operationData['tags'],
                'summary' => $_operationData['name'],
                'description' => $_operationData['description'],
                'operationId' => $_operationId,
                'produces' => [
                    'application/json'
                ],
                'parameters' => $_parameters,
                'responses' => [
                    'default' => [
                        'schema' => [
                            '$ref' => '#/definitions/' . $_operationData['response']
                        ],
                    ],
                ]
            ];
        }
        
        // add definitions
        foreach ($apiData['models'] as $_modelType => $_modelData) {
            $swagger['definitions'][$_modelType] = [
                'type' => 'object',
                'properties' => [],
            ];
            
            foreach ($_modelData['properties'] as $_field => $_type) {
                $swagger['definitions'][$_modelType]['properties'][$_field] = [
                    'type' => $_type,
                    'format' => $this->_mapFormat($_type),
                ];
            }
        }

        $swaggerJson = json_encode($swagger, JSON_PRETTY_PRINT);
        file_put_contents(GENERATION_DIR . 'swagger.json', $swaggerJson);
    }

    /**
     * @param string $type
     * @return string
     */
    private function _mapFormat($type)
    {
        switch ($type) {
            case 'int': $type = 'integer'; break;
            
            default:
                break;
        }
        
        return $type;
    }
}
