<?php
namespace Absolute\SilexApi\Command;

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

class SwaggerCommand extends AbstractCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();
        
        $this
            ->setName('absolute:silexapi:generation:swagger')
            ->setDescription('Generate Swagger JSON file.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_generateSwaggerClass($input, $output);
        $this->_generateSwaggerJson($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function _generateSwaggerClass(InputInterface $input, OutputInterface $output)
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
        $generationDir = $this->_getGenerationDir($input);
        $destination = $generationDir
            . DIRECTORY_SEPARATOR . 'Docs'
            . DIRECTORY_SEPARATOR;
        @mkdir($destination, 0777, true);
        $file = new FileGenerator;
        $file->setFilename($destination . 'Swagger.php');
        $file->setBody($class->generate());
        $file->write();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function _generateSwaggerJson(InputInterface $input, OutputInterface $output)
    {
        $apiData = $this->_getClientData($input);
        
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

        // write the file
        $generationDir = $this->_getGenerationDir($input);
        $destination = $generationDir
            . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR;
        @mkdir($destination, 0777, true);
        $file = new FileGenerator;
        $file->setFilename($destination . 'swagger.json');
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
