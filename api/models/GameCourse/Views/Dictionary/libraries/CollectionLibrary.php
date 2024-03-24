<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use GameCourse\Views\ViewHandler;
use Utils\Utils;

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
            new DFunction("item",
                [[ "name" => "collection", "optional" => false, "type" => "array"],
                 ["name" => "index", "optional" => false, "type" => "int"]],
                "Gets a given collection's item on a specific index.",
                ReturnType::MIXED,
                $this
            ),
            new DFunction("index",
                [[ "name" => "collection", "optional" => false, "type" => "array"],
                    ["name" => "item", "optional" => false, "type" => "ValueNode"],
                    ["name" => "key", "optional" => true, "type" => "string"]],
                "Gets the index of an item on a given collection. For items that are not basic types like text, numbers, etc., a search key should be given.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("count",
                [[ "name" => "collection", "optional" => false, "type" => "array"]],
                "Counts the number of elements in a given collection.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("sort",
                [[ "name" => "collection", "optional" => false, "type" => "array"],
                 ["name" => "orderKeyPairs", "optional" => true, "type" => "string"]],
                "Sorts a given collection. Order key pairs format: 'order: param'. Order options: 'ASC', 'DESC', 'asc', 'desc', 'ascending', 'descending'.",
            ReturnType::COLLECTION,
                $this
            ),
            new DFunction("crop",
                [[ "name" => "collection", "optional" => false, "type" => "array"],
                 ["name" => "start", "optional" => false, "type" => "int"],
                 ["name" => "end", "optional" => false, "type" => "int"]],
                "Crops a given collection by only returning items between start and end indexes.",
                ReturnType::COLLECTION,
                $this
            ),
            new DFunction("getKNeighbors",
                [["name" => "collection", "optional" => false, "type" => "array"],
                 ["name" => "index", "optional" => false, "type" => "int"],
                 ["name" => "k", "optional" => false, "type" => "int"]],
                "Crops a given collection by only returning an item and its K neighbors.",
                ReturnType::COLLECTION,
                $this
            ),
            new DFunction("generate",
                [["name" => "size", "optional" => false, "type" => "int"]],
                "Generates a collection of given size.",
                ReturnType::COLLECTION,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /**
     * Gets a given collection's item on a specific index.
     *
     * @param array $collection
     * @param int $index
     * @return ValueNode
     * @throws Exception
     */
    public function item(array $collection, int $index): ValueNode
    {
        if ($index < 0) $this->throwError("item", "index can't be smaller than 0");

        $size = count($collection);
        if ($index > $size - 1) $this->throwError("item", "index can't be bigger than " . ($size - 1));

        if ($size === 0) return new ValueNode(null);
        return new ValueNode($collection[$index], $collection[0]["libraryOfItem"]);
    }

    /**
     * Gets the index of an item on a given collection.
     * For items that are not basic types like text, numbers,
     * etc., a search key should be given.
     *
     * @param array $collection
     * @param $item
     * @param string|null $key
     * @return ValueNode
     * @throws Exception
     */
    public function index(array $collection, $item, string $key = null): ValueNode
    {
        $size = count($collection);
        if ($size > 0 && is_array(array_values($collection)[0])) { // Collection of non-basic types
            if (!$key) $this->throwError("index", "non-basic items require a key to search on collection");
            $index = 0;
            foreach ($collection as $value) {
                if ($value[$key] == $item) break;
                $index++;
            }
            if ($index > $size - 1) $index = false;

        } else { // Collection of basic types
            if (!Utils::isSequentialArray($collection))
                $index = array_search(array_search($item, $collection), array_keys($collection));
            else $index = array_search($item, $collection);
        }

        if ($index === false) $this->throwError("index", "couldn't find item in collection");
        return new ValueNode($index, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

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

            // Sort collection
            $itemIndex = -1;
            usort($collection, function ($a, $b) use ($orderKeyPairs, &$itemIndex) {
                $itemIndex++;
                $keyIndex = -1;

                foreach ($orderKeyPairs as $key => $order) {
                    $keyIndex++;

                    // Compile & evaluate key, if key is an expression
                    $itemsToProcess = [];
                    if (!array_key_exists($key, $a) && !array_key_exists("sort$keyIndex", $a)) $itemsToProcess[0] = &$a;
                    if (!array_key_exists($key, $b) && !array_key_exists("sort$keyIndex", $b)) $itemsToProcess[1] = &$b;
                    if (!empty($itemsToProcess)) {
                        $k = $key;

                        // Get library of collection items
                        $library = $itemsToProcess[0]["libraryOfItem"];

                        // Compile expression
                        if (strpos($k, "{") !== 0) $k = "{" . $k . "}";
                        ViewHandler::compileExpression($k);

                        // Evaluate expression
                        $visitor = Core::dictionary()->getVisitor();
                        foreach ($itemsToProcess as $j => &$item) {
                            $ky = $k;
                            $itemVisitor = $visitor->copy();
                            $itemVisitor->addParam("item", new ValueNode($item, $library));
                            $itemVisitor->addParam("index", $itemIndex + $j);
                            Core::dictionary()->setVisitor($itemVisitor);
                            ViewHandler::evaluateNode($ky, $itemVisitor);
                            $item["sort$keyIndex"] = $ky;
                        }
                    }

                    // Sort items
                    $key = !array_key_exists($key, $a) ? "sort$keyIndex" : $key;
                    $order = strtolower($order);
                    if ($order === "asc" || $order === "ascending") {
                        if (is_string($a[$key]) && is_string($b[$key])) { // string
                            if (strcmp($a[$key], $b[$key]) == 0) continue;
                            return strcmp($a[$key], $b[$key]);

                        } else if (is_numeric($a[$key]) && is_numeric($b[$key])) { // int
                            if ($a[$key] == $b[$key]) continue;
                            return $a[$key] - $b[$key];

                        } else $this->throwError("sort", "sorting value is neither string nor integer");

                    } else if ($order === "desc" || $order === "descending") {
                        if (is_string($a[$key]) && is_string($b[$key])) { // string
                            if (strcmp($a[$key], $b[$key]) == 0) continue;
                            return -strcmp($a[$key], $b[$key]);

                        } else if (is_numeric($a[$key]) && is_numeric($b[$key])) { // int
                            if ($a[$key] == $b[$key]) continue;
                            return $b[$key] - $a[$key];

                        } else $this->throwError("sort", "sorting value is neither string nor integer");

                    } else $this->throwError("sort", "order '$order' not available");
                }

                return 0;
            });
        }

        return new ValueNode($collection, $this);
    }

    /**
     * Crops a given collection by only returning items
     * between start and end indexes.
     *
     * @param array $collection
     * @param int $start
     * @param int $end
     * @return ValueNode
     * @throws Exception
     */
    public function crop(array $collection, int $start, int $end): ValueNode
    {
        if ($start < 0) $this->throwError("crop", "start index can't be smaller than 0");

        $size = count($collection);
        if ($size < $end + 1) $end = $size - 1;
        else if ($end > $size - 1)
            $this->throwError("crop", "end index can't be bigger than " . ($size - 1));

        $collection = array_slice($collection, $start, $end - $start + 1);
        return new ValueNode($collection, $this);
    }

    /**
     * Crops a given collection by only returning an item
     * and its K neighbors.
     *
     * @param array $collection
     * @param int $index
     * @param int $k
     * @return ValueNode
     * @throws Exception
     */
    public function getKNeighbors(array $collection, int $index, int $k): ValueNode
    {
        return $this->crop($collection, max($index - $k, 0), min($index + $k, count($collection) - 1));
    }

    /**
     * Generates a collection of a given size.
     * Useful to use in loopData, when we don't have
     * exactly a collection, but instead a number of times to repeat.
     *
     * @param int $size
     * @return ValueNode
     * @throws Exception
     */
    public function generate(int $size): ValueNode
    {
        $collection = array_fill(0, $size, []);
        return new ValueNode($collection, $this);
    }
}
