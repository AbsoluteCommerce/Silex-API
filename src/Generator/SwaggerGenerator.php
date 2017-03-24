<?php
namespace Absolute\SilexApi\Generator;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;

#todo these generators (this SwaggerGenerator in particular) need unit tests and then refactoring, they are very ugly

class SwaggerGenerator extends GeneratorAbstract
{
    /**
     * @inheritdoc
     */
    public function generate()
    {
        $this->_generateSwaggerClass();
        $this->_generateSwaggerJson();
    }

    /**
     * @inheritdoc
     */
    private function _generateSwaggerClass()
    {
        // generate the class
        $class = new ClassGenerator;
        $class->setNamespaceName('Absolute\\SilexApi\\Generation\\Docs');
        $class->setName('Swagger');
        
        // generate parse() method
        $docBlock = new DocBlockGenerator;
        $docBlock->setTags([
            new ParamTag('replace', ['array']),
            new ReturnTag(['string']),
        ]);
        $params = [
            new ParameterGenerator('replace', 'array', []),
        ];
        $methodBody = <<<EOT
\$dataPath = __DIR__ 
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'data'
    . DIRECTORY_SEPARATOR;

\$swaggerJson = file_get_contents(\$dataPath . 'swagger.json');
\$swaggerJson = str_replace(
    array_keys(\$replace),
    array_values(\$replace),
    \$swaggerJson
);

return \$swaggerJson;
EOT;
        $class->addMethod(
            'parse',
            $params,
            MethodGenerator::FLAG_PUBLIC,
            $methodBody,
            $docBlock
        );

        // write the file
        $file = new FileGenerator;
        $generationDir = $this->config->getGenerationDir('Docs');
        $file->setFilename($generationDir . $class->getName() . '.php');
        $file->setBody($class->generate());
        $file->write();
    }

    /**
     * @inheritdoc
     */
    private function _generateSwaggerJson()
    {
        $swagger = [
            'swagger' => '2.0',
            'info' => [
                'version' => $this->config->getApiVersion(),
                'title' => $this->config->getApiName(),
                'description' => $this->config->getApiDescription(),
                'termsOfService' => '',
                'contact' => [
                    'email' => $this->config->getApiEmail(),
                ],
                'license' => [
                    'name' => $this->config->getApiLicenseName(),
                    'url'  => $this->config->getApiLicenseUrl(),
                ],
            ],
            'host' => '{HOSTNAME}',
            'basePath' => '{BASEPATH}',
            'tags' => [],
            'schemes' => [
                '{SCHEME}',
            ],
            'paths' => [],
            'securityDefinitions' => [
                'http_auth' => [
                    'type' => 'basic',
                ],
            ],
            'definitions' => [],
            'externalDocs' => [
                'description' => '',
                'url'         => '',
            ],
        ];
        
        // add paths
        $tags = [];
        foreach ($this->config->getResources() as $_resourceId => $_resourceData) {
            $_parameters = [];
            $_resourceDataParams = isset($_resourceData['params']) ? $_resourceData['params'] : [];
            foreach ($_resourceDataParams as $_paramId => $_paramData) {
                $_parameters[] = [
                    'name' => $_paramId,
                    'in' => 'path',
                    'description' => $_paramData['description'],
                    'required' => $_paramData['required'],
                    'type' => $_paramData['type'],
                ];
            }
            $_resourceDataQueries = isset($_resourceData['queries']) ? $_resourceData['queries'] : [];
            foreach ($_resourceDataQueries as $_paramId => $_paramData) {
                $_parameters[] = [
                    'name' => $_paramId,
                    'in' => 'query',
                    'description' => $_paramData['description'],
                    'required' => $_paramData['required'],
                    'type' => $_paramData['type'],
                ];
            }
            $_body = isset($_resourceData['body']) ? $_resourceData['body'] : null;
            if ($_body !== null) {
                $_parameters[] = [
                    'name' => 'body',
                    'in' => 'body',
                    'description' => $_body,
                    'required' => true,
                    'schema' => [
                        '$ref' => '#/definitions/' . $_resourceId . 'Request',
                    ],
                ];
            }
            
            // append the resource data
            $swagger['paths'][$_resourceData['path']][$_resourceData['method']] = [
                'tags' => $_resourceData['tags'],
                'summary' => $_resourceData['name'],
                'description' => $_resourceData['description'],
                'operationId' => ucfirst($_resourceId),
                'consumes' => [
                    'application/json'
                ],
                'produces' => [
                    'application/json'
                ],
                'parameters' => $_parameters,
            ];

            // append the response data
            if ($_resourceData['response'] === null) {
                $_response = null;
            } else {
                $_response = [
                    '$ref' => '#/definitions/' . $_resourceId . 'Response',
                ];
            }
            $swagger['paths'][$_resourceData['path']][$_resourceData['method']]['responses'] = [
                'default' => [
                    'schema' => $_response,
                ],
            ];
            
            // append to tags
            $tags = array_merge($tags, $_resourceData['tags']);
        }
        
        // add tags
        $tags = array_unique($tags);
        foreach ($tags as $_tag) {
            $swagger['tags'][] = [
                'name' => strtolower(str_replace(' ', '_', $_tag)),
                'description' => ucwords($_tag),
            ];
        }
        
        // add Request definitions as operation request/response so we can have specific examples
        $models = $this->config->getModels();
        foreach ($this->config->getResources() as $_resourceId => $_resourceData) {
            $_requestModel = isset($_resourceData['body']) ? $_resourceData['body'] : null;
            if ($_requestModel == null) {
                continue;
            }
            
            $_requestProperties = [];
            $_requestModelData = array_key_exists($_requestModel, $models) ? $models[$_requestModel] : false;
            if ($_requestModelData != false) {
                foreach ($_requestModelData['properties'] as $_field => $_fieldData) {
                    // skip if not required for this operation
                    if (!isset($_fieldData['in_request']) || $_fieldData['in_request'] == false) {
                        continue;
                    } elseif (is_array($_fieldData['in_request']) && !in_array($_resourceId, $_fieldData['in_request'])) {
                        continue;
                    }

                    $_requestProperties[$_field] = [
                        'type' => $_fieldData['type'],
                        'example' => $_fieldData['example'],
                    ];
                    
                    // check for resource specific example
                    if (isset($_fieldData['exampleByOperation']) && array_key_exists($_resourceId, $_fieldData['exampleByOperation'])) {
                        $_requestProperties[$_field]['example'] = $_fieldData['exampleByOperation'][$_resourceId];
                    }
                    
                    switch ($_fieldData['type']) {
                        case 'array':
                            $_requestProperties[$_field]['items'] = [
                                'type' => 'string',
                            ];
                            break;

                        default:
                            $_requestProperties[$_field]['format'] = $this->_mapFormat($_fieldData['type']);
                            break;
                    }
                }

                $swagger['definitions'][$_resourceId . 'Request'] = [
                    'type' => 'object',
                    'properties' => $_requestProperties,
                ];
            }
        }
        
        // add Response definitions as operation request/response so we can have specific examples
        $models = $this->config->getModels();
        foreach ($this->config->getResources() as $_resourceId => $_resourceData) {
            if (substr($_resourceData['response'], -2) == '[]') {
                $_responseModel = substr($_resourceData['response'], 0, strlen($_resourceData['response']) - 2);
                $_responseModelData = array_key_exists($_responseModel, $models) ? $models[$_responseModel] : false;
            } else {
                $_responseModelData = array_key_exists($_resourceData['response'], $models) ? $models[$_resourceData['response']] : false;
            }

            $_responseProperties = [];
            if ($_responseModelData != false) {
                foreach ($_responseModelData['properties'] as $_field => $_fieldData) {
                    $_responseProperties[$_field] = [
                        'type' => $_fieldData['type'],
                        'example' => $_fieldData['example'],
                    ];

                    // check for resource specific example
                    if (isset($_fieldData['exampleByOperation']) && array_key_exists($_resourceId, $_fieldData['exampleByOperation'])) {
                        $_responseProperties[$_field]['example'] = $_fieldData['exampleByOperation'][$_resourceId];
                    }
                    
                    switch ($_fieldData['type']) {
                        case 'array':
                            $_responseProperties[$_field]['items'] = [
                                'type' => 'string',
                            ];
                            break;

                        default:
                            $_responseProperties[$_field]['format'] = $this->_mapFormat($_fieldData['type']);
                            break;
                    }
                }
            }

            if (substr($_resourceData['response'], -2) == '[]') {
                $swagger['definitions'][$_resourceId . 'Response'] = [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => $_responseProperties,
                    ],
                ];
            } else {
                $swagger['definitions'][$_resourceId . 'Response'] = [
                    'type' => 'object',
                    'properties' => $_responseProperties,
                ];
            }
        }
        
        // prepare the Swagger JSON
        $swaggerJson = json_encode($swagger, JSON_PRETTY_PRINT);
        $swaggerJson = str_replace('\/', '/', $swaggerJson);

        // write the file
        $file = new FileGenerator;
        $generationDir = $this->config->getGenerationDir('data');
        $file->setFilename($generationDir . 'swagger.json');
        $file->setSourceContent($swaggerJson);
        $file->setSourceDirty(false);
        $file->write();
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
