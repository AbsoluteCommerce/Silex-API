<?php
namespace Absolute\SilexApi\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Code\Generator\FileGenerator;

class RouteCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('absolute:silexapi:generation:routes')
            ->setDescription('Generate Silex Routes file.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apiData = require(DATA_DIR . 'api.php');

        $openingTag = <<<EOT
<?php
/** @var Application \$app */
EOT;
        
        $useClasses = [
            'Silex\Application',
            'Symfony\Component\HttpFoundation\Request as HttpRequest',
            'Absolute\SilexApi\Request\RequestFactory',
            'Absolute\SilexApi\Response\ResponseFactory',
            'Absolute\SilexApi\Exception\NotImplementedException',
        ];
        
        $body = '';
        foreach ($apiData['operations'] as $_operationId => $_operationData) {
            $_paramString = $this->_buildParamString($_operationData['params'] ?? []);
            
            $body .= <<<EOT
// {$_operationData['name']} :: {$_operationData['description']}
\$app->patch('{$_operationData['path']}', function (HttpRequest \$httpRequest{$_paramString})
{
    \$resource = new \\Acme\\WebService\\Resource\\{$_operationId};
    if (!\$resource instanceof \\Acme\\WebService\\Generation\\Resource\\{$_operationId}Interface) {
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

    return ResponseFactory::prepareResponse(\$httpRequest, \$resource->execute());
});


EOT;
        }

        $generator = new FileGenerator;
        $generator->setUses($useClasses);
        $generator->setBody($body);
        $generator->setSourceDirty(true);
        $content = $generator->generate();
        
        $content = str_replace("<?php", $openingTag, $content);
        file_put_contents(GENERATION_DIR . 'routes.php', $content);
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
            $buildData[] = "    \$resource->set{$_ucFirst}(RequestFactory::getQuery(\$httpRequest, '{$_paramData['field']}'));";
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
    /** @var \\Acme\\WebService\\Generation\\Model\\{$ucFirst}Model \${$modelClass} */
    \${$modelClass} = new \\Acme\\WebService\\Generation\\Model\\{$ucFirst}Model;
    RequestFactory::hydrateModel(\$httpRequest, \${$modelClass});
    \$resource->set{$ucFirst}(\${$modelClass});
EOT;
        return $body;
    }
}
