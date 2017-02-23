<?php
namespace Absolute\SilexApi\Generator;

use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;

class RouteGenerator extends GeneratorAbstract
{
    /**
     * @inheritdoc
     */
    public function generate()
    {
        $this->_generateRouteClass();
        $this->_generateRoutesFile();
    }

    /**
     * 
     */
    private function _generateRouteClass()
    {
        // generate the class
        $class = new ClassGenerator;
        $class->setNamespaceName('Absolute\\SilexApi\\Generation\\Route');
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

require \$dataPath . 'routes.php';
EOT;
        $class->addMethod(
            'register',
            $params,
            MethodGenerator::FLAG_PUBLIC,
            $methodBody,
            $docBlock
        );

        // write the file
        $file = new FileGenerator;
        $generationDir = $this->config->getGenerationDir('Route');
        $file->setFilename($generationDir . $class->getName() . '.php');
        $file->setBody($class->generate());
        $file->write();
    }

    /**
     * 
     */
    private function _generateRoutesFile()
    {
        $openingTag = <<<EOT
<?php
/** @var Application \$app */
EOT;
        
        $useClasses = [
            'Silex\Application',
            'Symfony\Component\HttpFoundation\Request as HttpRequest',
            'Symfony\Component\HttpFoundation\Response as HttpResponse',
            'Absolute\SilexApi\SilexApi',
            'Absolute\SilexApi\Request\RequestInterface',
            'Absolute\SilexApi\Response\ResponseInterface',
            'Absolute\SilexApi\Exception\NotImplementedException',
            'Absolute\SilexApi\Generation\Resource as ResourceInterface',
            'Absolute\SilexApi\Generation\Model',
            $this->config->getNamespace(GeneratorConfig::NAMESPACE_RESOURCE),
        ];
        
        $body = '';
        
        // append the default HTTP Response
        $body .= <<<EOT
// prepare the HTTP Response as 500, the application must prove the request was successful
\$httpResponse = new HttpResponse(
    HttpResponse::\$statusTexts[HttpResponse::HTTP_INTERNAL_SERVER_ERROR],
    HttpResponse::HTTP_INTERNAL_SERVER_ERROR
);


EOT;
        
        // append the routes
        foreach ($this->config->getResources() as $_resourceId => $_resourceData) {
            $_className = ucfirst($_resourceId);
            if (!empty($_resourceData['namespace'])) {
                $_className = ucfirst($_resourceData['namespace']) . '\\' . $_className;
            }
            
            $_paramString = $this->_buildParamString($_resourceData['params'] ?? []); #todo remove this for < PHP7 support
            
            $body .= <<<EOT
// {$_resourceData['name']} :: {$_resourceData['description']}
\$app->{$_resourceData['method']}('{$_resourceData['path']}', function (HttpRequest \$httpRequest{$_paramString}) use (\$app, \$httpResponse)
{
    /** @var Resource\\{$_className} \$resource */
    \$resource = \$app[SilexApi::DI_RESOURCE_FACTORY]->get(Resource\\{$_className}::class, \$app, \$httpResponse);
    if (!\$resource instanceof ResourceInterface\\{$_className}Interface) {
        throw new NotImplementedException;
    }
    
EOT;

            if ($_resourceParams = $this->_buildResourceParams($_resourceData['params'] ?? [])) { #todo remove this for < PHP7 support
                $body .= PHP_EOL . $_resourceParams . PHP_EOL;
            }
            
            if ($_resourceQueries = $this->_buildResourceQueries($_resourceData['queries'] ?? [])) { #todo remove this for < PHP7 support
                $body .= PHP_EOL . $_resourceQueries . PHP_EOL;
            }
            
            if ($_resourceBody = $this->_buildResourceBody($_resourceData['body'] ?? null)) { #todo remove this for < PHP7 support
                $body .= PHP_EOL . $_resourceBody . PHP_EOL;
            }

            $body .= <<<EOT

    /** @var ResponseInterface \$response */
    \$response = \$app[SilexApi::DI_RESPONSE_FACTORY]->get(\$httpRequest, \$httpResponse);
    return \$response->prepareResponse(\$httpRequest, \$resource->execute());
});


EOT;
        }

        // write the file
        $file = new FileGenerator;
        $generationDir = $this->config->getGenerationDir('data');
        $file->setFilename($generationDir . 'routes.php');
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
        
        $buildData = [
            "    /** @var RequestInterface \$request */",
            "    \$request = \$app[SilexApi::DI_REQUEST_FACTORY]->get(\$httpRequest);",
        ];
        foreach ($params as $_paramId => $_paramData) {
            $_ucFirst = ucfirst($_paramId);
            $buildData[] = "    \$resource->set{$_ucFirst}(\$request->getQuery(\$httpRequest, '{$_paramData['field']}'));";
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
    \${$modelClass} = \$app[SilexApi::DI_MODEL_FACTORY]->get(Model\\{$ucFirst}::class);
    \$app[SilexApi::DI_REQUEST_FACTORY]->get(\$httpRequest)->hydrateModel(\$httpRequest, \${$modelClass});
    \$resource->set{$ucFirst}(\${$modelClass});
EOT;
        return $body;
    }
}
