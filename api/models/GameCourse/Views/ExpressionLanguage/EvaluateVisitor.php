<?php
namespace GameCourse\Views\ExpressionLanguage;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Course\Course;
use GameCourse\Views\Dictionary\CollectionLibrary;
use Utils\Cache;
use Utils\Utils;

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

    /**
     * @throws Exception
     */
    public function getParam(string $name)
    {
        if (!$this->hasParam($name))
            throw new Exception("Param '$name' doesn't exist on visitor.");
        return $this->params[$name];
    }

    public function addParam(string $name, $value)
    {
        $this->params[$name] = $value;
    }

    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->params);
    }

    public function mockData(): bool
    {
        return $this->mockData;
    }

    public function copy(): EvaluateVisitor
    {
        return new EvaluateVisitor($this->params, $this->mockData);
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

        // Check if function result is in cache before calling it
        $cacheId = $this->getNodeCacheId($node);
        $cacheValue = Cache::getFromViewsCache($cacheId);
        if ($cacheValue) return unserialize($cacheValue);

        if ($context) {
            $context = $context->accept($this);
            $contextVal = $context->getValue();

            // If no library is set, gets the library of the context
            if (!$library) {
                if (is_array($contextVal) && (empty($contextVal) || Utils::isSequentialArray($contextVal)))
                    $library = Core::dictionary()->getLibraryById(CollectionLibrary::ID);
                else {
                    $library = $context->getLibrary();
                    if (!$library) {
                        throw new Exception('Calling function \'' . $funcName . '\' on incorrect argument type.');
                    }
                }
                $node->setLibrary($library);
            }
        } else $contextVal = null;

        // Call function
        $course = $this->params["course"] ? Course::getCourseById($this->params["course"]) : null;
        $result = Core::dictionary()->callFunction($course, $library->getId(), $funcName, $args, $contextVal, $this->mockData);

        // If result is a collection, set library on each item
        // NOTE: important for functions in the collection library
        if (is_array($result->getValue()) && Utils::isSequentialArray($result->getValue())) {
            $collection = array_map(function ($item) use ($result) {
                if (!isset($item["libraryOfItem"])) $item["libraryOfItem"] = $result->getLibrary();
                return $item;
            }, $result->getValue());
            $result = new ValueNode($collection, $result->getLibrary());
        }

        Cache::storeInViewsCache($cacheId, serialize($result));
        return $result;
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


    /*** ---------------------------------------------------- ***/
    /*** ---------------------- Helpers --------------------- ***/
    /*** ---------------------------------------------------- ***/

    /**
     * Gets cache ID for a given node.
     *
     * Some identifiers for a node are the node itself and all
     * visitor parameters used by it. These parameters can be
     * general variables like 'course', 'viewer', 'user', etc, or
     * any variables defined by the user.
     *
     * We can then serialize both the node and its parameters
     * used and after hashing all of it we get a unique identifier
     * for the node which will be its cache ID.
     *
     * @param Node $node
     * @return string
     */
    private function getNodeCacheId(Node $node): string
    {
        // Get all visitor params the node uses
        $nodeParams = [];
        $this->findNodeParams($node, $nodeParams);

        // Create a unique identifier for the node
        return hash("sha256", serialize($node) . serialize($nodeParams));
    }

    /**
     * Finds all visitor parameters used by a given node.
     *
     * @param Node $node
     * @param array $nodeParams
     * @return void
     */
    private function findNodeParams(Node $node, array &$nodeParams)
    {
        // Do a REGEX search on a string representation of the node
        $pattern = "/[\"']param[\"'] => [\"'](.*)[\"']|%(\w+)/";
        preg_match_all($pattern, var_export($node, true), $matches);

        // Add all params found
        foreach (array_merge($matches[1], $matches[2]) as $match) {
            if (!empty($match) && !isset($nodeParams[$match]) && isset($this->params[$match])) {
                $nodeParams[$match] = $this->params[$match];

                // Continue searching if param found is itself a Node
                if ($this->params[$match] instanceof Node)
                    $this->findNodeParams($this->params[$match], $nodeParams);
            }
        }
    }
}
