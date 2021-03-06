<?php declare(strict_types = 1);

namespace Sbludufunk\Randown\Evaluator;

use Error;
use Exception;
use Sbludufunk\Randown\Evaluator\Classes\Objecto;
use Sbludufunk\Randown\Evaluator\Classes\PrivateConstructors\ConcatClass;
use Sbludufunk\Randown\Evaluator\Classes\PrivateConstructors\RandoClass;
use Sbludufunk\Randown\Evaluator\Classes\PrivateConstructors\TextClass;
use Sbludufunk\Randown\Evaluator\Classes\PublicConstructors\BagClass;
use Sbludufunk\Randown\Parser\Nodes\ArgumentNode;
use Sbludufunk\Randown\Parser\Nodes\ArgumentsNode;
use Sbludufunk\Randown\Parser\Nodes\FunctionCallNode;
use Sbludufunk\Randown\Parser\Nodes\MethodCallNode;
use Sbludufunk\Randown\Parser\Nodes\RandoNode;
use Sbludufunk\Randown\Parser\Nodes\ReferenceNode;
use Sbludufunk\Randown\Parser\Nodes\TextNode;
use Sbludufunk\Randown\Tokenizer\Tokens\ReferenceToken;

class Engine
{
    private $_variables;

    public function __construct(){
        $this->_variables = [];
    }

    public function registerReference(
        Bool $constant,
        ReferenceToken $name,
        Objecto $value
    ){
        $strName = (String)$name->normalize();
        if($this->_variables[$strName]["constant"] ?? FALSE){
            throw new Error("Cannot override constant reference \"$strName\"");
        }
        $this->_variables[$strName] = ["value" => $value, "constant" => $constant];
    }

    public function evaluate(Array $nodes): String{
        return (String)$this->evaluateConcatenation($nodes);
    }

    /** @throws Exception */
    private function evaluateConcatenation(Array $nodes): ?Objecto{
        $buffer = [];
        foreach($nodes as $node){
            $item =
                $this->evaluateText($node) ??
                $this->evaluateVariable($node) ??
                $this->evaluateRandoCall($node) ??
                $this->evaluateFunctionCall($node) ??
                NULL;
            assert($item !== NULL);
            $buffer[] = $item;
        }
        return count($buffer) === 1 ? $buffer[0] : new ConcatClass($buffer);
    }

    /** @throws Exception */
    private function evaluateText($node): ?Objecto{
        if(!$node instanceof TextNode){ return NULL; }
        $text = new TextClass($node->unescape());
        return $this->evaluateMethodCalls($text, $node->calls());
    }

    /** @throws Exception */
    private function evaluateVariable($node): ?Objecto{
        if(!$node instanceof ReferenceNode){ return NULL; }
        $thisValue = $this->_variables[$node->token()->name()] ?? NULL;
        if($thisValue === NULL){ throw new UndefinedVariable([], $node); }
        return $this->evaluateMethodCalls($thisValue, $node->calls());
    }

    /** @throws Exception */
    private function evaluateFunctionCall($node): ?Objecto{
        if(!$node instanceof FunctionCallNode){ return NULL; }
        $functionName = $node->token()->name();
        if($function === NULL){ throw new UndefinedFunction([], $node); }
        $arguments = $this->evaluateArguments($node->arguments());
        $result = $function->invoke(...$arguments);
        return $this->evaluateMethodCalls($result, $node->methodCalls());
    }

    /** @throws Exception */
    private function evaluateRandoCall($node): ?Objecto{
        if(!$node instanceof RandoNode){ return NULL; }
        $arguments = $this->evaluateArguments($node->arguments());
        $opt = new RandoClass(new BagClass($arguments));
        return $this->evaluateMethodCalls($opt, $node->calls());
    }

    /** @throws Exception */
    private function evaluateMethodCalls(Objecto $thisValue, array $methodCalls): Objecto{
        /** @var MethodCallNode[] $methodCalls */
        if($methodCalls === []){ return $thisValue; }
        $methodCallNode = array_shift($methodCalls);
        /** @var MethodCallNode $methodCallNode */
        $methodName = $methodCallNode->token()->name();
        $arguments = $this->evaluateArguments($methodCallNode->arguments());
        $newThisValue = $thisValue->invoke($methodName, $arguments);
        return $this->evaluateMethodCalls($newThisValue, $methodCalls);
    }

    /** @throws Exception */
    private function evaluateArguments(ArgumentsNode $arguments): array{
        $actualArguments = [];
        foreach($arguments->toArray() as $argument){
            /** @var ArgumentNode $argument */
            $actualArguments[] = $this->evaluateConcatenation($argument->contents());
        }
        return $actualArguments;
    }
}
