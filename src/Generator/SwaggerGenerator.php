<?php
namespace Absolute\SilexApi\Generator;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;

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
                        '$ref' => '#/definitions/_single_' . $_body,
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
                if (substr($_resourceData['response'], -2) == '[]') {
                    $_responseDefinition = '_multiple_' . substr($_resourceData['response'], 0, strlen($_resourceData['response']) - 2);
                } else {
                    $_responseDefinition = '_single_' . $_resourceData['response'];
                }
                
                $_response = [
                    '$ref' => '#/definitions/' . $_responseDefinition,
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
        
        // add definitions
        foreach ($this->config->getModels() as $_modelType => $_modelData) {
            $swagger['definitions']['_single_' . $_modelType] = [
                'type' => 'object',
                'properties' => [],
            ];
            foreach ($_modelData['properties'] as $_field => $_fieldData) {
                switch ($_fieldData['type']) {
                    case 'array':
                        $swagger['definitions']['_single_' . $_modelType]['properties'][$_field] = [
                            'type' => $_fieldData['type'],
                            'items' => [
                                'type' => 'string',
                            ],
                            'example' => $_fieldData['example'],
                        ];
                        break;
                    
                    default:
                        $swagger['definitions']['_single_' . $_modelType]['properties'][$_field] = [
                            'type' => $_fieldData['type'],
                            'format' => $this->_mapFormat($_fieldData['type']),
                            'example' => $_fieldData['example'],
                        ];
                        break;
                }
            }

            $swagger['definitions']['_multiple_' . $_modelType] = [
                'type' => 'array',
                'items' => [
                    '$ref' => '#/definitions/_single_' . $_modelType,
                ],
            ];
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
