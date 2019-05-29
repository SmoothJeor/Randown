<?php declare(strict_types = 1);

namespace Sbludufunk\Randown\Nodes;

use Sbludufunk\Randown\Tokens\MethodCallToken;

class MethodCallNode implements Node
{
    private $_token;

    private $_arguments;

    public function __construct(MethodCallToken $token, ArgumentsNode $arguments){
        $this->_token = $token;
        $this->_arguments = $arguments;
    }

    public function __toString(): String{
        return $this->_token . $this->_arguments;
    }

    public function token(): MethodCallToken{
        return $this->_token;
    }

    public function arguments(): ArgumentsNode{
        return $this->_arguments;
    }
}