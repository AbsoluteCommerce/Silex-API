<?php
namespace Absolute\SilexApi\Model;

abstract class ModelAbstract implements ModelInterface
{
    /** @var array */
    protected $setters = [];
    
    /** @var array */
    protected $getters = [];

    /**
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->setData($data);
    }

    /**
     * @inheritdoc
     */
    public function setData(array $data)
    {
        foreach ($data as $_field => $_value) {
            $_method = array_key_exists($_field, $this->setters)
                ? $this->setters[$_field]
                : false;
            
            if (!$_method) {
                continue;
            }
            
            $this->{$_method}($_value);
        }
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $data = [];
        foreach ($this->getters as $_field => $_method) {
            $data[$_field] = $this->{$_method}();
        }
        
        return $data;
    }
}
