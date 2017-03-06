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
        
        // add replace property
        $_propertyGenerator = new PropertyGenerator;
        $_propertyGenerator
            ->setName('replace')
            ->setDefaultValue([
                '\/' => '/',
            ])
            ->setDocBlock(new DocBlockGenerator(null, null, [
                new ParamTag('replace', ['array']),
            ]))
            ->addFlag(PropertyGenerator::FLAG_PRIVATE);
        $class->addPropertyFromGenerator($_propertyGenerator);
        
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

\$replace = array_merge(
    \$this->replace,
    \$replace
);

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
                $swagger['definitions']['_single_' . $_modelType]['properties'][$_field] = [
                    'type' => $_fieldData['type'],
                    'format' => $this->_mapFormat($_fieldData['type']),
                    'example' => $_fieldData['example'],
                ];
            }

            $swagger['definitions']['_multiple_' . $_modelType] = [
                'type' => 'array',
                'items' => [
                    '$ref' => '#/definitions/_single_' . $_modelType,
                ],
            ];
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
