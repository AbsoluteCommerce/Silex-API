<?php
namespace Absolute\SilexApi\Generator;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\InterfaceGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;

class ResourceInterfaceGenerator extends GeneratorAbstract
{
    /**
     * @inheritdoc
     */
    public function generate()
    {
        $this->_generateInterfaces();
    }

    /**
     *
     */
    private function _generateInterfaces()
    {
        foreach ($this->config->getResources() as $_resourceId => $_resourceData) {
            $_className = ucfirst($_resourceId);
            
            // generate the class
            $class = new InterfaceGenerator;
            $class->setName($_className . 'Interface');
            
            // prepare the file
            $file = new FileGenerator;
            if (!empty($_resourceData['namespace'])) {
                $_subDir = ucfirst($_resourceData['namespace']);
                $generationDir = $this->config->getGenerationDir('Resource/' . $_subDir);
                $class->setNamespaceName('Absolute\\SilexApi\\Generation\\Resource\\' . $_subDir);
            } else {
                $generationDir = $this->config->getGenerationDir('Resource');
                $class->setNamespaceName('Absolute\\SilexApi\\Generation\\Resource');
            }
            $file->setFilename($generationDir . $class->getName() . '.php');
            
            // generate param methods
            $params = array_key_exists('params', $_resourceData)
                ? $_resourceData['params']
                : [];
            foreach ($params as $_paramId => $_paramData) {
                $class->addMethod(
                    'set' . ucfirst($_paramId),
                    [
                        new ParameterGenerator($_paramId), #todo configurable PHP7 scalar type hint
                        # new ParameterGenerator($_paramId, $_paramData['type']),
                    ],
                    MethodGenerator::FLAG_PUBLIC,
                    null,
                    new DocBlockGenerator(null, null, [
                        new ParamTag($_paramId, [$_paramData['type']]),
                    ])
                );
            }
            
            // generate query methods
            $queries = array_key_exists('queries', $_resourceData)
                ? $_resourceData['queries']
                : [];
            foreach ($queries as $_queryId => $_queryData) {
                $class->addMethod(
                    'set' . ucfirst($_queryId),
                    [
                        new ParameterGenerator($_queryId), #todo configurable PHP7 scalar type hint
                        # new ParameterGenerator($_queryId, $_queryData['type']),
                    ],
                    MethodGenerator::FLAG_PUBLIC,
                    null,
                    new DocBlockGenerator(null, null, [
                        new ParamTag($_queryId, [$_queryData['type']]),
                    ])
                );
            }
            
            // generate body method
            $bodyModel = array_key_exists('body', $_resourceData)
                ? $_resourceData['body']
                : false;
            if ($bodyModel) {
                $_modelName = ucfirst($bodyModel);
                $class->addUse("Absolute\\SilexApi\\Generation\\Model\\{$_modelName}");

                $class->addMethod(
                    'set' . ucfirst($bodyModel),
                    [
                        new ParameterGenerator($bodyModel, 'Absolute\\SilexApi\\Generation\\Model\\' . $_modelName),
                    ],
                    MethodGenerator::FLAG_PUBLIC,
                    null,
                    new DocBlockGenerator(null, null, [
                        new ParamTag($bodyModel, [$_modelName]),
                    ])
                );
            }

            // generate execute() method
            $_response = $_resourceData['response'];
            if ($_response === null) {
                $docBlock = new DocBlockGenerator;
                $docBlock->setTags([
                    new ReturnTag('null'),
                ]);
                $class->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    null,
                    $docBlock
                );
            } else {
                $_responseModel = ucfirst($_resourceData['response']);
                $_hasArray = strpos($_responseModel, '[]');
                if ($_hasArray !== false) {
                    $_responseModel = substr($_responseModel, 0, $_hasArray);
                }
                $class->addUse("Absolute\\SilexApi\\Generation\\Model\\{$_responseModel}");
                $docBlock = new DocBlockGenerator;
                $docBlock->setTags([
                    new ReturnTag($_responseModel . (($_hasArray !== false) ? '[]' : null)),
                ]);
                #todo configurable PHP7 return type hint
                $class->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    null,
                    $docBlock
                );
            }

            // write the file
            $file->setBody($class->generate());
            $file->write();
        }
    }
}
