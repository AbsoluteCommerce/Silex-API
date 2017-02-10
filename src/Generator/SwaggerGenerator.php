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
        
        // add paths
        foreach ($this->config->getResources() as $_resourceId => $_resourceData) {
            $_parameters = [];
            foreach ($_resourceData['params'] ?? [] as $_paramId => $_paramData) { #todo remove this for < PHP7 support
                $_parameters[] = [
                    'name' => $_paramId,
                    'in' => 'path',
                    'description' => $_paramData['description'],
                    'required' => $_paramData['required'],
                    'type' => $_paramData['type'],
                ];
            }
            foreach ($_resourceData['queries'] ?? [] as $_paramId => $_paramData) { #todo remove this for < PHP7 support
                $_parameters[] = [
                    'name' => $_paramId,
                    'in' => 'query',
                    'description' => $_paramData['description'],
                    'required' => $_paramData['required'],
                    'type' => $_paramData['type'],
                ];
            }
            $_body = $_resourceData['body'];
            if ($_body ?? false) { #todo remove this for < PHP7 support
                $_parameters[] = [
                    'name' => 'body',
                    'in' => 'body',
                    'description' => $_body,
                    'required' => true,
                    'schema' => '#/definitions/' . $_body,
                ];
            }
            
            $swagger['paths'][$_resourceData['path']][$_resourceData['method']] = [
                'tags' => $_resourceData['tags'],
                'summary' => $_resourceData['name'],
                'description' => $_resourceData['description'],
                'operationId' => ucfirst($_resourceId),
                'produces' => [
                    'application/json'
                ],
                'parameters' => $_parameters,
                'responses' => [
                    'default' => [
                        'schema' => [
                            '$ref' => '#/definitions/' . $_resourceData['response']
                        ],
                    ],
                ]
            ];
        }
        
        // add definitions
        foreach ($this->config->getModels() as $_modelType => $_modelData) {
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

        // write the file
        $file = new FileGenerator;
        $generationDir = $this->config->getGenerationDir('data');
        $file->setFilename($generationDir . 'swagger.json');
        $file->setSourceContent(json_encode($swagger, JSON_PRETTY_PRINT));
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
