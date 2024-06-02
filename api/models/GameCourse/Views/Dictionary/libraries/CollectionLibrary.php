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
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>Collections are, as the name suggests, groups of multiple items. All of those items should, however, be of the same type.
        An example of a collection could be:</p>
        <div class="bg-base-100 rounded-box px-4 py-2 my-2">
          <pre><code>
            {
              "id": 1,
              "name": "Mary",
              "major": "MEIC"
            },
            {
              "id": 2,
              "name": "John",
              "major": "MEIC"
            },
            {
              "id": 3,
              "name": "Jane",
              "major": null
            }
          </code></pre>
        </div><br>
        <p>Other namespaces, such as <span class="text text-primary">users</span>, have functions that return a collection, such as
        <span class="text-secondary">getStudents</span>. Thus, while this namespace can be used alone, it is mostly used to
        manipulate and retrieve information from existing collections.</p><br>
        <p>Going back to the previous example, assuming that the collection is saved in the variable 
        <span class="text-secondary">%collection</span>, the expression:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{%collection.item(0)}</code></pre>
        </div>
        would return
        <div class="bg-base-100 rounded-box px-4 py-2 my-2">
          <pre><code>
            {
              "id": 1,
              "name": "Mary",
              "major": "MEIC"
            }
          </code></pre>
        </div><br>
        <p>Another useful utility is to sort a collection, for example:</p>
        <div class="bg-base-100 rounded-box p-4 my-2">
          <pre><code>{%collection.sort("DESC: name")}</code></pre>
        </div>
        HTML;
    }



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
                $this,
                "%students.item(0)"
            ),
            new DFunction("index",
                [[ "name" => "collection", "optional" => false, "type" => "array"],
                    ["name" => "item", "optional" => false, "type" => "ValueNode"],
                    ["name" => "key", "optional" => true, "type" => "string"]],
                "Gets the index of an item on a given collection. For items that are not basic types like text, numbers, etc., a search key should be given.",
                ReturnType::NUMBER,
                $this,
                "%students.index(%viewer, \"id\")"
            ),
            new DFunction("count",
                [[ "name" => "collection", "optional" => false, "type" => "array"]],
                "Counts the number of elements in a given collection.",
                ReturnType::NUMBER,
                $this,
                "%students.count"
            ),
            new DFunction("sort",
                [[ "name" => "collection", "optional" => false, "type" => "array"],
                 ["name" => "orderKeyPairs", "optional" => true, "type" => "string"]],
                "Sorts a given collection. Order key pairs format: 'order: param'. Order options: 'ASC', 'DESC', 'asc', 'desc', 'ascending', 'descending'.",
            ReturnType::COLLECTION,
                $this,
                "%userAwards.sort(\"DESC: date\")"
            ),
            new DFunction("filter",
                [[ "name" => "collection", "optional" => false, "type" => "array"],
                 ["name" => "key", "optional" => false, "type" => "string"],
                 ["name" => "value", "optional" => false, "type" => "string"],
                 ["name" => "operation", "optional" => false, "type" => "= | < | >"]],
                "Returns the collection only with objects that have the variable key that satisfy the operation with a specific value.",
            ReturnType::COLLECTION,
                $this,
                "%userAwards.filter(\"reward\", \"100\", \">\")\nReturns the items of the collection %userAwards that have a reward higher than 100."
            ),
            new DFunction("crop",
                [[ "name" => "collection", "optional" => false, "type" => "array"],
                 ["name" => "start", "optional" => false, "type" => "int"],
                 ["name" => "end", "optional" => false, "type" => "int"]],
                "Crops a given collection by only returning items between start and end indexes.",
                ReturnType::COLLECTION,
                $this,
                "%userAwards.crop(0, 10)"
            ),
            new DFunction("getKNeighbors",
                [["name" => "collection", "optional" => false, "type" => "array"],
                 ["name" => "index", "optional" => false, "type" => "int"],
                 ["name" => "k", "optional" => false, "type" => "int"]],
                "Crops a given collection by only returning an item and its K neighbors.",
                ReturnType::COLLECTION,
                $this,
                "%students.getKNeighbors(%students.index(%viewer, \"id\"), 3)"
            ),
            new DFunction("generate",
                [["name" => "size", "optional" => false, "type" => "int"]],
                "Generates a collection of given size. This is useful when you want to repeat a component in a page 
                a given number of times, with no interest in the actual content of each item.",
                ReturnType::COLLECTION,
                $this,
                "collection.generate(5)"
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
     * Returns the collection only with objects that have the variable key that satisfy the operation with a specific value.
     *
     * @param array $collection
     * @param string $orderKeyPairs
     * @return ValueNode
     * @throws Exception
     */
    public function filter(array $collection, string $key, string $value, string $operation = "=" | "<" | ">"): ValueNode
    {
        $filter =
            function($var) use ($key, $value, $operation) {
                if (!isset($var[$key])) $this->throwError("filter", "key '" . $key . "' doesn't exist in the collection items");

                if ($operation == '=') return $var[$key] == $value;
                else if ($operation == '<') return $var[$key] < $value;
                else if ($operation == '>') return $var[$key] > $value;
                else $this->throwError("filter", "operation '" . $operation . "' invalid: must be '=', '<' or '>'");
            };

        $collection = array_filter($collection, $filter);
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
