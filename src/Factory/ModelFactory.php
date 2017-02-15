<?php
namespace Absolute\SilexApi\Factory;

use Absolute\SilexApi\Model\ModelInterface;

class ModelFactory
{
    /**
     * @param string $className
     * @param array $data
     * @return ModelInterface
     */
    public static function get($className, array $data = [])
    {
        $className = 'Absolute\\SilexApi\\Generation\\Model\\' . $className;
        return new $className($data);
    }
}
