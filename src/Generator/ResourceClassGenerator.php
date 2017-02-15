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
            
            // generate the class
            $class = new ClassGenerator;
            $class->setName($_className);

            // prepare the file
            $file = new FileGenerator;
            if (!empty($_resourceData['namespace'])) {
                $_subDir = ucfirst($_resourceData['namespace']);
                $resourceDir = $this->config->getResourceDir($_subDir);
                $class->addUse('Absolute\\SilexApi\\Generation\\Resource\\' . $_subDir . '\\' . $_className . 'Interface');
                $class->setNamespaceName($this->config->getNamespace(GeneratorConfig::NAMESPACE_RESOURCE) . '\\' . $_subDir);
                $class->setImplementedInterfaces(['Absolute\\SilexApi\\Generation\\Resource\\' . $_subDir . '\\' . $_className . 'Interface']);
            } else {
                $resourceDir = $this->config->getResourceDir();
                $class->addUse('Absolute\\SilexApi\\Generation\\Resource\\' . $_className . 'Interface');
                $class->setNamespaceName($this->config->getNamespace(GeneratorConfig::NAMESPACE_RESOURCE));
                $class->setImplementedInterfaces(['Absolute\\SilexApi\\Generation\\Resource\\' . $_className . 'Interface']);
            }
            $class->addUse('Absolute\\SilexApi\\Resource\\ResourceAbstract');
            $class->setExtendedClass('Absolute\\SilexApi\\Resource\\ResourceAbstract');
            $file->setFilename($resourceDir . $class->getName() . '.php');

            // skip any implementations that already exist
            if (@file_exists($file->getFilename())) {
                continue;
            }
            
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
                $_modelName = ucfirst($bodyModel);
                $class->addUse("Absolute\\SilexApi\\Generation\\Model\\{$_modelName}");

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
                        new ParameterGenerator($bodyModel, 'Absolute\\SilexApi\\Generation\\Model\\' . $_modelName),
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
                $_responseModel = ucfirst($_resourceData['response']);
                $_hasArray = strpos($_responseModel, '[]');
                if ($_hasArray !== false) {
                    $_responseModel = substr($_responseModel, 0, $_hasArray);
                    $_body = "return [\$this->modelFactory->get({$_responseModel}::class)];";
                } else {
                    $_body = "return \$this->modelFactory->get({$_responseModel}::class);";
                }
                $class->addUse("Absolute\\SilexApi\\Generation\\Model\\{$_responseModel}");
                $class->addMethod(
                    'execute',
                    [],
                    MethodGenerator::FLAG_PUBLIC,
                    $_body,
                    '@inheritdoc'
                );
                #todo configurable PHP7 return type hint
            }

            // write the file
            $file->setBody($class->generate());
            $file->write();
        }
    }
}
