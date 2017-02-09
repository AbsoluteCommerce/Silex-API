<?php
namespace Absolute\SilexApi\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;

class RouteCommand extends AbstractCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('absolute:silexapi:generation:routes')
            ->setDescription('Generate Silex Routes file.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_generateRouteClass($input, $output);
        $this->_generateRoutesFile($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function _generateRouteClass(InputInterface $input, OutputInterface $output)
    {
        // generate the class
        $class = new ClassGenerator;
        $class->setNamespaceName('Absolute\\SilexApi\\Generation\\Routes');
        $class->setName('RouteRegistrar');
        $class->addUse('Silex\Application');

        // generate register() method
        $docBlock = new DocBlockGenerator;
        $docBlock->setTags([
            new ParamTag('app', 'Application'),
        ]);
        $params = [
            new ParameterGenerator('app', 'Silex\Application'),
        ];
        $methodBody = <<<EOT
\$dataPath = __DIR__ 
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'data'
    . DIRECTORY_SEPARATOR;

require_once \$dataPath . 'routes.php';
EOT;
        $class->addMethod(
            'register',
            $params,
            MethodGenerator::FLAG_PUBLIC,
            $methodBody,
            $docBlock
        );

        // write the file
        $generationDir = $this->_getGenerationDir($input);
        $destination = $generationDir
            . DIRECTORY_SEPARATOR . 'Routes'
            . DIRECTORY_SEPARATOR;
        @mkdir($destination, 0777, true);
        $file = new FileGenerator;
        $file->setFilename($destination . 'RouteRegistrar.php');
        $file->setBody($class->generate());
        $file->write();
    }

    /**
     * @inheritdoc
     */
    private function _generateRoutesFile(InputInterface $input, OutputInterface $output)
    {
        $apiData = $this->_getClientData($input);

        $openingTag = <<<EOT
<?php
/** @var Application \$app */
EOT;
        
        $useClasses = [
            'Silex\Application',
            'Symfony\Component\HttpFoundation\Request',
            'Absolute\SilexApi\Request\RequestFactory',
            'Absolute\SilexApi\Response\ResponseFactory',
            'Absolute\SilexApi\Exception\NotImplementedException',
            
            'Absolute\SilexApi\Generation\Resource as ResourceInterface',
            'Absolute\SilexApi\Generation\Model',
            $this->_getClientNamespace($input) . 'Resource',
        ];
        
        $body = '';
        foreach ($apiData['operations'] as $_operationId => $_operationData) {
            $_paramString = $this->_buildParamString($_operationData['params'] ?? []);
            
            $body .= <<<EOT
// {$_operationData['name']} :: {$_operationData['description']}
\$app->{$_operationData['method']}('{$_operationData['path']}', function (Request \$request{$_paramString})
{
    \$resource = new Resource\\{$_operationId};
    if (!\$resource instanceof ResourceInterface\\{$_operationId}Interface) {
        throw new NotImplementedException;
    }
    
EOT;

            if ($_resourceParams = $this->_buildResourceParams($_operationData['params'] ?? [])) {
                $body .= PHP_EOL . $_resourceParams . PHP_EOL;
            }
            
            if ($_resourceQueries = $this->_buildResourceQueries($_operationData['queries'] ?? [])) {
                $body .= PHP_EOL . $_resourceQueries . PHP_EOL;
            }
            
            if ($_resourceBody = $this->_buildResourceBody($_operationData['body'] ?? null)) {
                $body .= PHP_EOL . $_resourceBody . PHP_EOL;
            }

            $body .= <<<EOT

    return ResponseFactory::prepareResponse(\$request, \$resource->execute());
});


EOT;
        }

        // write the file
        $generationDir = $this->_getGenerationDir($input);
        $destination = $generationDir
            . DIRECTORY_SEPARATOR . 'data'
            . DIRECTORY_SEPARATOR;
        @mkdir($destination, 0777, true);
        $file = new FileGenerator;
        $file->setFilename($destination . 'routes.php');
        $file->setUses($useClasses);
        $file->setBody($body);
        $file->setSourceContent(str_replace("<?php", $openingTag, $file->generate()));
        $file->setSourceDirty(false);
        $file->write();
    }

    /**
     * @param array $params
     * @return string
     */
    private function _buildParamString(array $params)
    {
        if (!count($params)) {
            return '';
        }
        
        $buildData = [];
        foreach ($params as $_paramId => $_paramData) {
            $buildData[] = '$' . $_paramId;
        }
        
        $result = ', ' . implode(', ', $buildData);
        
        return $result;
    }

    /**
     * @param array $params
     * @return bool|string
     */
    private function _buildResourceParams(array $params)
    {
        if (!count($params)) {
            return false;
        }
        
        $buildData = [];
        foreach ($params as $_paramId => $_paramData) {
            $_ucFirst = ucfirst($_paramId);
            $buildData[] = "    \$resource->set{$_ucFirst}(\${$_paramData['field']});";
        }
        
        $result = implode(PHP_EOL, $buildData);
        
        return $result;
    }

    /**
     * @param array $params
     * @return bool|string
     */
    private function _buildResourceQueries(array $params)
    {
        if (!count($params)) {
            return false;
        }
        
        $buildData = [];
        foreach ($params as $_paramId => $_paramData) {
            $_ucFirst = ucfirst($_paramId);
            $buildData[] = "    \$resource->set{$_ucFirst}(RequestFactory::getQuery(\$request, '{$_paramData['field']}'));";
        }
        
        $result = implode(PHP_EOL, $buildData);
        
        return $result;
    }

    /**
     * @param string $modelClass
     * @return bool|string
     */
    private function _buildResourceBody($modelClass)
    {
        if ($modelClass === null) {
            return false;
        }
        
        $ucFirst = ucfirst($modelClass);

        $body = <<<EOT
    /** @var Model\\{$ucFirst}Model \${$modelClass} */
    \${$modelClass} = new Model\\{$ucFirst}Model;
    RequestFactory::hydrateModel(\$request, \${$modelClass});
    \$resource->set{$ucFirst}(\${$modelClass});
EOT;
        return $body;
    }
}
