<?php

namespace MonarcCore\Controller;

class ApiAnrController extends AbstractController
{
    protected $name = 'anrs';

    public function getList()
    {
        return $this->methodNotAllowed();
    }

    public function get($id)
    {
        return $this->methodNotAllowed();
    }

    public function create($data)
    {
        return $this->methodNotAllowed();
    }

    public function delete($id)
    {
        return $this->methodNotAllowed();
    }
}

