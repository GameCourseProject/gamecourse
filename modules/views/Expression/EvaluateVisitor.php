<?php
namespace Modules\Views\Expression;

use SmartBoards\Core;

class EvaluateVisitor extends Visitor {
    private $params;
    private $viewHandler;

    public function __construct($params, $viewHandler) {
        $this->params = $params;
        $this->viewHandler = $viewHandler;
    }

    public function visitStatementSequence($node) {
        $text = $node->getNode()->accept($this)->getValue();
        $next = $node->getNext();
        if ($next != null) {
            $text .= $next->accept($this)->getValue();
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
        
        if ($context==null){
            return $this->viewHandler->callFunction($node->getLib(),$funcName, $args);
        }else{
            $contextVal=$context->accept($this)->getValue();
            $lib=$node->getLib();
            if($node->getLib()==null){
                //gets the lib name of the previous function 
                //ex: users.getUser().name in the function 'name' gets users lib
                if (is_a($context, "Modules\Views\Expression\FunctionOp")) {
                    $lib = $context->getLib();
                    $node->setLib($lib);
                } 
                if ($lib==null){
                    if ($contextVal["type"] == "object")
                        $lib = $contextVal["value"]["libraryOfVariable"];
                    else {//type == collection
                        $lib = $contextVal["value"][0]["libraryOfVariable"];
                    }
                }
            }
            return $this->viewHandler->callFunction($lib,$funcName, $args, $contextVal);
        }
    }
    
    //for now we're keeping the new functionality in the if and the old in the else
    //if the context is in a db path, add parameter
    public function visitContextSequence($node, $valueContinuation, $dbPath=false) {
        $contextKey = $node->getNode()->accept($this)->getValue();
        $next = $node->getNext();
        if ($dbPath){
            $valueContinuation[$node->getAttribute()]=$contextKey;
            if ($next == null){
                
                return $valueContinuation;
            } else {
                return $next->accept($this, $valueContinuation,true);
            }
        }else{
            $cont = $valueContinuation->followKey($contextKey);
            
            if ($next == null)
                return $cont;
            else
                return $next->accept($this, $cont);
        }
    }
    
    public function visitDatabasePath($node, $parent, $returnContinuation) {
        $t = $node->getPath();
        $context = $node->getContext();

        $contextArray=null;
        if ($parent != null) {//ToDO
            print_r("visitDatabasePath received a parent");
            print_r($parent);
            $valueCont = $parent->execute($t);
        }
        
        if ($context != null) {
            $contextArray = $context->accept($this, [],true);
        }
       
        // add [course => %course] to context array
        if ($t!="game_course_user" && $t!="course"){
            $contextArray['course']=$this->params['course'];
        }
        
        
        $subPath = $node->getSubPath();
        if ($subPath!=null){
            // select subpath from t where context
            return new ValueNode(Core::$systemDB->select($t,$contextArray,$subPath->getPath()));
        }else if ($returnContinuation){//not using continuations, just returning an array
            return Core::$systemDB->selectMultiple($t,$contextArray);
        }else{
            return new ValueNode(Core::$systemDB->selectMultiple($t,$contextArray));
        }
        
    }

    public function visitDatabasePathFromParameter($node, $returnContinuation) {
        $variableName = $node->getParameter();
        if (!array_key_exists($variableName, $this->params))
            throw new \Exception('Unknown variable: ' . $variableName);
        $param = $this->params[$variableName];
       // if (is_null($param) || !is_object($param) || !is_a($param, 'SmartBoards\DataRetrieverContinuation'))
        //    throw new \Exception('Variable '  . $variableName . ' in path should be a continuation');

        $context = $node->getContext();
        if ($context != null) {
            //ToDo: change jison for DatabasePathFromParameter to just be param w key
            throw new \Exception('Unable to use this context ' . $context. " with parameter " . $variableName);
            $param = $context->accept($this, $param);
        }   

        $path = $node->getPath();
        if ($path == null) {
            if ($returnContinuation)
                return $param;
            else
                return new ValueNode($param->getValue());
        } else {//assuming that there is no path, just a key of array
            //ToDo instead of this, change jison for DatabasePathFromParameter to just be param w key
            return new ValueNode($param[$path->getPath()]);
            //return $path->accept($this, $param, $returnContinuation);
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
