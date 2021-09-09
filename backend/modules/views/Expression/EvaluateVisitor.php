<?php
namespace Modules\Views\Expression;

use GameCourse\Core;

class EvaluateVisitor extends Visitor {
    private $params;
    private $viewHandler;

    public function __construct($params, $viewHandler) {
        $this->params = $params;
        $this->viewHandler = $viewHandler;
    }

    public function visitStatementSequence($node) {
        $text = $node->getNode()->accept($this)->getValue();
        if (is_array(($text)))
            throw new \Exception("Tried to write an object as a string");
        $next = $node->getNext();
        if ($next != null) {
            $nextStr = $next->accept($this)->getValue();
            if ($nextStr===null) $nextStr="";
            if (is_string($nextStr) || is_int($nextStr) )  {
                $text .= $nextStr;
            } else {
                throw new \Exception("Can't process statement '".$text."{?}'. Found an object where a string should be");
            }
        }
        return new ValueNode($text);
    }

    public function visitArgumentSequence($node) {
        $args = array($node->getNode()->accept($this)->getValue());
        $next = $node->getNext();
        if ($next != null)
            $args = array_merge($args, $next->accept($this)->getValue());
        return new ValueNode($args);
    }

    public function visitValueNode($node) {
        return $node;
    }

    public function visitGenericUnaryOp($node) {
        $rhs = $node->getRhs()->accept($this)->getValue();
        $op = $node->getOp();
        switch ($op) {
            case '-': return new ValueNode(-$rhs);
            case '!': return new ValueNode(!$rhs);
            case '~': return new ValueNode(~$rhs);
            default:
                throw new \Exception('Unknown unary operation: ' . $op);
        }
    }

    public function visitGenericBinaryOp($node) {
        $lhs = $node->getLhs()->accept($this)->getValue();
        $rhs = $node->getRhs()->accept($this)->getValue();
        $op = $node->getOp();
        switch ($op) {
            case '+':
                if (is_string($lhs) || is_string($rhs))
                    return new ValueNode($lhs . $rhs);
                return new ValueNode($lhs + $rhs);
            case '-': return new ValueNode($lhs - $rhs);
            case '*': return new ValueNode($lhs * $rhs);
            case '/': return new ValueNode($lhs / $rhs);
            case '%': return new ValueNode($lhs % $rhs);
            case '==': return new ValueNode($lhs == $rhs);
            case '<': return new ValueNode($lhs < $rhs);
            case '>': return new ValueNode($lhs > $rhs);
            case '<=': return new ValueNode($lhs <= $rhs);
            case '>=': return new ValueNode($lhs >= $rhs);
            case '&&': return new ValueNode($lhs && $rhs);
            case '||': return new ValueNode($lhs || $rhs);
            case '&': return new ValueNode($lhs & $rhs);
            case '|': return new ValueNode($lhs | $rhs);
            case '^': return new ValueNode($lhs ^ $rhs);
            case 'in_array': return new ValueNode(!is_null($rhs) && in_array($lhs, $rhs));
        }
    }

    public function visitFunctionOp($node) {

        $funcName = $node->getName();
        if ($node->getArgs() == null) {
            $args = array();
        } else {
            $args = $node->getArgs()->accept($this)->getValue();
        }
        $context = $node->getContext();
        $lib=$node->getLib();
        if ($lib=="actions") {
            if ($funcName==="showToolTip" || $funcName==="showPopUp"){
                if (sizeof($args)==1){//does not have user argument
                    $args[]=null;
                }
                $args[]=$this->params;
            }
            else if ($funcName==="hideView" || $funcName==="showView" || $funcName==="toggleView"){
                $args[]=$this;
            }
        }
        
        if ($context==null){
            return $this->viewHandler->callFunction($lib,$funcName, $args);
        }else{
            $contextVal=$context->accept($this)->getValue();
        
            
            if($node->getLib()==null){
                //gets the lib name of the previous function 
                //ex: %user.name in the function 'name' gets users lib
                if (is_array($contextVal)){
                    if (empty($contextVal)){
                        throw new \Exception('Tried to call function "'.$funcName.' on an empty array.');
                    }
                    if ($contextVal["type"] == "object")
                        $lib = $contextVal["value"]["libraryOfVariable"];
                    else {//type == collection
                        if (!empty($contextVal["value"]))
                            $lib = $contextVal["value"][0]["libraryOfVariable"];
                    }
                    $node->setLib($lib);
                }
            }
            return $this->viewHandler->callFunction($lib,$funcName, $args, $contextVal);
        }
    }

    public function visitParameterNode($node) {
        $variableName = $node->getParameter();
        if (!array_key_exists($variableName, $this->params))
            throw new \Exception('Unknown variable: ' . $variableName);
        $key = $node->getKey();
        if ($key == null) {
            return new ValueNode($this->params[$variableName]);
        }else{
            if (!is_string($key))
                $key= $key->getValue();  
            return new ValueNode($this->params[$variableName][$key]);
        }
    }
}
