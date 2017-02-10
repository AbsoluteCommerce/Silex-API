<?php
namespace Absolute\SilexApi\Model;

interface ModelInterface
{
    /**
     * @param array $data
     */
    public function setData(array $data);

    /**
     * @return array
     */
    public function getData();
}
