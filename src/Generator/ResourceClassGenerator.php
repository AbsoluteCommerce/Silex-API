<?php
namespace Absolute\SilexApi\Generator;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;

class ResourceClassGenerator extends GeneratorAbstract
{
    /**
     * @inheritdoc
     */
    public function generate()
    {
        $this->_generateClasses();
    }

    /**
     *
     */
    private function _generateClasses()
    {
        foreach ($this->config->getResources() as $_resourceId => $_resourceData) {
            $_className = ucfirst($_resourceId);
            
            // skip any implementations that already exist
            $resourceDir = $this->config->getResourceDir();
            if (@file_exists($resourceDir . $_className . '.php')) {
                // continue; #todo remove comment
            }
            
            // generate the class
            $class = new ClassGenerator;
            $class->setNamespaceName($this->config->getNamespace(GeneratorConfig::NAMESPACE_RESOURCE));
            $class->setName($_className);
            $class->addUse('Absolute\\SilexApi\\Generation\\Resources\\' . $_className . 'Interface');
            $class->setImplementedInterfaces(['Absolute\\SilexApi\\Generation\\Resources\\' . $_className . 'Interface']);
            
            // generate param methods
            $params = array_key_exists('params', $_resourceData)
                ? $_resourceData['params']
                : [];
            foreach ($params as $_paramId => $_paramData) {
                $_propertyGenerator = new PropertyGenerator;
                $_propertyGenerator
                    ->setName($_paramId)
                    ->setDocBlock(new DocBlockGenerator(null, null, [
                        new ParamTag($_paramId, [$_paramData['type']]),
                    ]))
                    ->addFlag(PropertyGenerator::FLAG_PRIVATE);
                $class->addPropertyFromGenerator($_propertyGenerator);
                
                $class->addMethod(
                    'set' . ucfirst($_paramId),
                    [
                        new ParameterGenerator($_paramId, $_paramData['type']), #todo configurable PHP7 scalar type hint
                    ],
                    MethodGenerator::FLAG_PUBLIC,
                    "\$this->{$_paramId} = \${$_paramId};",
                    '@inheritdoc'
                );
            }
            
            // generate query methods
            $queries = array_key_exists('queries', $_resourceData)
                ? $_resourceData['queries']
                : [];
            foreach ($queries as $_queryId => $_queryData) {
                $_propertyGenerator = new PropertyGenerator;
                $_propertyGenerator
                    ->setName($_queryId)
                    ->setDocBlock(new DocBlockGenerator(null, null, [
                        new ParamTag($_queryId, [$_queryData['type']]),
                    ]))
                    ->addFlag(PropertyGenerator::FLAG_PRIVATE);
                $class->addPropertyFromGenerator($_propertyGenerator);

                $class->addMethod(
                    'set' . ucfirst($_queryId),
                    [
                        new ParameterGenerator($_queryId, $_queryData['type']), #todo configurable PHP7 scalar type hint
                    ],
                    MethodGenerator::FLAG_PUBLIC,
                    "\$this->{$_queryId} = \${$_queryId};",
                    '@inheritdoc'
                );
            }
            
            // generate body method
            $bodyModel = array_key_exists('body', $_resourceData)
                ? $_resourceData['body']
                : false;
            if ($bodyModel) {
                $_modelName = ucfirst($bodyModel) . 'Model';
                $class->addUse("Absolute\\SilexApi\\Generation\\Models\\{$_modelName}");

                $_propertyGenerator = new PropertyGenerator;
                $_propertyGenerator
                    ->setName($bodyModel)
                    ->setDocBlock(new DocBlockGenerator(null, null, [
                        new ParamTag($bodyModel, [$_modelName]),
                    ]))
                    ->addFlag(PropertyGenerator::FLAG_PRIVATE);
                $class->addPropertyFromGenerator($_propertyGenerator);
                
                $class->addMethod(
                    'set' . ucfirst($bodyModel),
                    [
                        new ParameterGenerator($bodyModel, 'Absolute\\SilexApi\\Generation\\Models\\' . $_modelName),
                    ],
                    MethodGenerator::FLAG_PUBLIC,
                    "\$this->{$bodyModel} = \${$bodyModel};",
                    '@inheritdoc'
                );
            }

            // generate execute() method
            $_response = $_resourceData['response'];
            if ($_response === null) {
                $class->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    "return null;",
                    '@inheritdoc'
                );
            } else {
                $_responseModel = ucfirst($_resourceData['response']) . 'Model';
                $class->addUse("Absolute\\SilexApi\\Generation\\Models\\{$_responseModel}");
                $class->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    "return new {$_responseModel};",
                    '@inheritdoc'
                );
                #todo configurable PHP7 return type hint
            }

            // write the file
            $file = new FileGenerator;
            $resourceDir = $this->config->getResourceDir();
            $file->setFilename($resourceDir . $class->getName() . '.php');
            $file->setBody($class->generate());
            $file->write();
        }
    }
}
