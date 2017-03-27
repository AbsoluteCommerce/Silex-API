<?php
namespace Absolute\SilexApi\Generator;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;

class ModelGenerator extends GeneratorAbstract
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
        foreach ($this->config->getModels() as $_modelId => $_modelData) {
            // generate the class
            $class = new ClassGenerator;
            $class->setNamespaceName('Absolute\\SilexApi\\Generation\\Model');
            $class->setName(ucfirst($_modelId));
            $class->addUse('Absolute\SilexApi\Model\ModelAbstract');
            $class->setExtendedClass('Absolute\SilexApi\Model\ModelAbstract');
            
            // generate protected property with getters and setters
            $_getters = [];
            $_setters = [];
            $properties = array_key_exists('properties', $_modelData)
                ? $_modelData['properties']
                : [];
            foreach ($properties as $_propertyId => $_propertyData) {
                $_getters[$_propertyId] = 'get' . ucfirst($_propertyId);
                $_setters[$_propertyId] = 'set' . ucfirst($_propertyId);
            }
            $_propertyGenerator = new PropertyGenerator;
            $_propertyGenerator
                ->setName('getters')
                ->setDefaultValue($_getters)
                ->setDocBlock(new DocBlockGenerator(null, null, [
                    new ParamTag('getters', ['array']),
                ]))
                ->addFlag(PropertyGenerator::FLAG_PROTECTED);
            $class->addPropertyFromGenerator($_propertyGenerator);
            $_propertyGenerator = new PropertyGenerator;
            $_propertyGenerator
                ->setName('setters')
                ->setDefaultValue($_setters)
                ->setDocBlock(new DocBlockGenerator(null, null, [
                    new ParamTag('setters', ['array']),
                ]))
                ->addFlag(PropertyGenerator::FLAG_PROTECTED);
            $class->addPropertyFromGenerator($_propertyGenerator);
            
            // generate property methods
            $properties = array_key_exists('properties', $_modelData)
                ? $_modelData['properties']
                : [];
            foreach ($properties as $_propertyId => $_propertyData) {
                $_propertyGenerator = new PropertyGenerator;
                $_propertyGenerator
                    ->setName($_propertyId)
                    ->setDocBlock(new DocBlockGenerator(null, null, [
                        new ParamTag($_propertyId, [$_propertyData['type']]),
                    ]))
                    ->addFlag(PropertyGenerator::FLAG_PRIVATE);
                $class->addPropertyFromGenerator($_propertyGenerator);
                
                // setter
                $_methodBody = <<<EOT
\$this->{$_propertyId} = \${$_propertyId};

return \$this;
EOT;
                $class->addMethod(
                    'set' . ucfirst($_propertyId),
                    [
                        new ParameterGenerator($_propertyId, $this->config->isPhp7Hints() ? $_propertyData['type'] : null),
                    ],
                    MethodGenerator::FLAG_PUBLIC,
                    $_methodBody,
                    new DocBlockGenerator(null, null, [
                        new ParamTag($_propertyId, [$_propertyData['type']]),
                        new ReturnTag([$class->getName()]),
                    ])
                );
                if ($this->config->isPhp7Hints()) {
                    $class->getMethod('set' . ucfirst($_propertyId))->setReturnType($class->getNamespaceName() . '\\' . $class->getName());
                }
                
                // getter
                $class->addMethod(
                    'get' . ucfirst($_propertyId),
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    "return \$this->{$_propertyId};",
                    new DocBlockGenerator(null, null, [
                        new ReturnTag([$_propertyData['type']]),
                    ])
                );
                if ($this->config->isPhp7Hints()) {
                    $class->getMethod('get' . ucfirst($_propertyId))->setReturnType($_propertyData['type']);
                }
            }

            // write the file
            $file = new FileGenerator;
            $generationDir = $this->config->getGenerationDir('Model');
            $file->setFilename($generationDir . $class->getName() . '.php');
            $file->setBody($class->generate());
            $file->write();
        }
    }
}
