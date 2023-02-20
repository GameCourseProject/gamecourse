<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\EvaluateVisitor;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use GameCourse\Views\ViewHandler;

class CollectionLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "collection";    // NOTE: must match the name of the class
    const NAME = "Collection";
    const DESCRIPTION = "Provides utility functions for collections of items.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("count",
                "Counts the number of elements in a given collection.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("sort",
                "Sorts a given collection. Order key pairs format: 'order: param'. Order options: 'ASC', 'DESC', 'asc', 'desc', 'ascending', 'descending'.",
            ReturnType::COLLECTION,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Counts the number of elements in a given collection.
     *
     * @param array $collection
     * @return ValueNode
     * @throws Exception
     */
    public function count(array $collection): ValueNode
    {
        return new ValueNode(count($collection), Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Sorts a given collection. Order key pairs format: 'order: param'. Order options: 'ASC', 'DESC', 'asc', 'desc', 'ascending', 'descending'.
     *
     * @param array $collection
     * @param string $orderKeyPairs
     * @return ValueNode
     * @throws Exception
     */
    public function sort(array $collection, string $orderKeyPairs = ""): ValueNode
    {
        if (!empty($collection) && !empty($orderKeyPairs)) {
            // Parse order key pairs
            $orderKeyPairsParsed = array_map("trim", explode(",", $orderKeyPairs));
            $orderKeyPairs = [];
            foreach ($orderKeyPairsParsed as $pair) {
                $parts = array_map("trim", explode(":", $pair));
                $order = $parts[0];
                $key = $parts[1];
                $orderKeyPairs[$key] = $order;
            }

            // Compile & evaluate keys that are expressions
            $i = 0;
            foreach ($orderKeyPairs as $key => $order) {
                $k = $key;

                if (!array_key_exists($k, $collection[0])) {
                    // Get library of collection items
                    $library = $collection[0]["libraryOfItem"];

                    // Compile expression
                    if (strpos($k, "{") !== 0) $k = "{" . $k . "}";
                    ViewHandler::compileExpression($k);

                    // Evaluate expression
                    $visitor = Core::dictionary()->getVisitor();
                    foreach ($collection as &$item) {
                        $ky = $k;
                        $itemVisitor = new EvaluateVisitor($visitor->getParams(), $visitor->mockData());
                        $itemVisitor->addParam("item", new ValueNode($item, $library));
                        $itemVisitor->addParam("index", $i);
                        ViewHandler::evaluateNode($ky, $itemVisitor);
                        $item["sort$i"] = $ky;
                    }

                    $orderKeyPairs["sort$i"] = $order;
                    unset($orderKeyPairs[$key]);
                }
                $i++;
            }

            // Sort collection
            usort($collection, function ($a, $b) use ($orderKeyPairs) {
                foreach ($orderKeyPairs as $key => &$order) {
                    $order = strtolower($order);
                    if ($order === "asc" || $order === "ascending") {
                        if (is_string($a[$key]) && is_string($b[$key])) { // string
                            if (strcmp($a[$key], $b[$key]) == 0) continue;
                            return strcmp($a[$key], $b[$key]);

                        } else if (is_numeric($a[$key]) && is_numeric($b[$key])) { // int
                            if ($a[$key] == $b[$key]) continue;
                            return $a[$key] - $b[$key];

                        } else throw new Exception("Can't sort collection: value is neither string nor integer.");

                    } else if ($order === "desc" || $order === "descending") {
                        if (is_string($a[$key]) && is_string($b[$key])) { // string
                            if (strcmp($a[$key], $b[$key]) == 0) continue;
                            return -strcmp($a[$key], $b[$key]);

                        } else if (is_numeric($a[$key]) && is_numeric($b[$key])) { // int
                            if ($a[$key] == $b[$key]) continue;
                            return $b[$key] - $a[$key];

                        } else throw new Exception("Can't sort collection: value is neither string nor integer.");

                    } else throw new Exception("Can't sort collection: order '$order' not available.");
                }
                return 0;
            });
        }

        return new ValueNode($collection, $this);
    }
}
