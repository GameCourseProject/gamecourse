<?php
namespace GameCourse\Views\ExpressionLanguage;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Views\Dictionary\CollectionLibrary;

class EvaluateVisitor extends Visitor
{
    private $params;        // variables available
    private $mockData;      // whether to generate mocks

    public function __construct(array $params, bool $mockData = false) {
        $this->params = $params;
        $this->mockData = $mockData;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function mockData(): bool
    {
        return $this->mockData;
    }

    public function addParam(string $name, $value)
    {
        if (!isset($this->params[$name])) $this->params[$name] = $value;
    }


    /*** ---------------------------------------------------- ***/
    /*** ------------------ Visiting nodes ------------------ ***/
    /*** ---------------------------------------------------- ***/

    /**
     * @param ArgumentSequence $node
     * @return ValueNode
     */
    public function visitArgumentSequence(ArgumentSequence $node): ValueNode
    {
        $args = [$node->getNode()->accept($this)->getValue()];
        $next = $node->getNext();
        if ($next != null) $args = array_merge($args, $next->accept($this)->getValue());
        return new ValueNode($args);
    }

    /**
     * @param FunctionOp $node
     * @return ValueNode
     * @throws Exception
     */
    public function visitFunctionOp(FunctionOp $node): ValueNode {
        $funcName = $node->getName();
        if ($node->getArgs() == null) $args = [];
        else $args = $node->getArgs()->accept($this)->getValue();

        $context = $node->getContext();
        $library = $node->getLibrary();

        if ($context) {
            $context = $context->accept($this);
            $contextVal = $context->getValue();

            // If no library is set, gets the library of the context
            if (!$library) {
                $library = $context->getLibrary();
                $node->setLibrary($library);
            }

            // If context is a collection, set libraries
            if (is_array($contextVal) && array_key_exists(0, $contextVal)) {
                // Set library of collection items
                $contextVal = array_map(function ($item) use ($library) {
                    $item["libraryOfItem"] = $library;
                    return $item;
                }, $contextVal);

                // Set library of function
                $library = Core::dictionary()->getLibraryById(CollectionLibrary::ID);
                $node->setLibrary($library);
            }
        } else $contextVal = null;

        $course = $this->params["course"] ? Course::getCourseById($this->params["course"]) : null;
        return Core::dictionary()->callFunction($course, $library->getId(), $funcName, $args, $contextVal, $this->mockData);
    }

    /**
     * @param GenericBinaryOp $node
     * @return ValueNode
     * @throws Exception
     */
    public function visitGenericBinaryOp(GenericBinaryOp $node): ValueNode
    {
        $lhs = $node->getLhs()->accept($this)->getValue();
        $rhs = $node->getRhs()->accept($this)->getValue();
        $op = $node->getOp();

        switch ($op) {
            case '+':
                if (is_string($lhs) || is_string($rhs)) return new ValueNode($lhs . $rhs);
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
            default:
                throw new Exception('Unknown binary operation: ' . $op);
        }
    }

    /**
     * @param GenericUnaryOp $node
     * @return ValueNode
     * @throws Exception
     */
    public function visitGenericUnaryOp(GenericUnaryOp $node): ValueNode
    {
        $rhs = $node->getRhs()->accept($this)->getValue();
        $op = $node->getOp();
        switch ($op) {
            case '-': return new ValueNode(-$rhs);
            case '!': return new ValueNode(!$rhs);
            case '~': return new ValueNode(~$rhs);
            default:
                throw new Exception('Unknown unary operation: ' . $op);
        }
    }

    /**
     * @param ParameterNode $node
     * @return ValueNode
     * @throws Exception
     */
    public function visitParameterNode(ParameterNode $node): ValueNode
    {
        $variableName = $node->getParameter();
        if (!array_key_exists($variableName, $this->params))
            throw new Exception('Unknown variable: ' . $variableName);

        $param = $this->params[$variableName];
        return $param instanceof Node ? $param->accept($this) : new ValueNode($param);
    }

    /**
     * @param StatementSequence $node
     * @return ValueNode
     * @throws Exception
     */
    public function visitStatementSequence(StatementSequence $node): ValueNode
    {
        $text = $node->getNode()->accept($this)->getValue();
        if (is_array($text))
            throw new Exception("Tried to write an object as a string.");

        $next = $node->getNext();
        if ($next != null) {
            $nextStr = $next->accept($this)->getValue();
            if ($nextStr === null) $nextStr = "";
            if (is_string($nextStr) || is_int($nextStr)) $text .= $nextStr;
            else throw new Exception("Can't process statement '" . $text . "{?}'. Found an object where a string should be.");
        }
        return new ValueNode($text);
    }

    /**
     * @param ValueNode $node
     * @return ValueNode
     */
    public function visitValueNode(ValueNode $node): ValueNode
    {
        return $node;
    }
}
