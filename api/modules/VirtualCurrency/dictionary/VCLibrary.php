<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use InvalidArgumentException;

class VCLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }

    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "vc";    // NOTE: must match the name of the class
    const NAME = "Virtual Currency";
    const DESCRIPTION = "Provides access to information regarding virtual currency.";


    /*** ----------------------------------------------- ***/
    /*** --------------- Documentation ----------------- ***/
    /*** ----------------------------------------------- ***/

    public function getNamespaceDocumentation(): ?string
    {
        return <<<HTML
        <p>This namespace allows you to obtain data regarding the users' spendings, and the tokens
        configured for the course. <b>VC</b> stands for <b>Virtual Currency</b>.</p><br>
        <p>A spending is characterized by:</p>
        <div class="bg-base-100 rounded-box px-4 py-2 my-2">
          <pre><code>
            {
              "id": 3,
              "user": 3,
              "course": 1,
              "description": "Token(s) exchange",
              "amount": 160,
              "date": "2024-04-08 09:29:21"
            }
          </code></pre>
        </div>
        HTML;
    }

    /*** ----------------------------------------------- ***/
    /*** ------------------ Mock data ------------------ ***/
    /*** ----------------------------------------------- ***/

    private function mockSpending($userId) : array
    {
        return [
            "id" => Core::dictionary()->faker()->numberBetween(0, 100),
            "course" => 0,
            "user" => $userId,
            "description" => Core::dictionary()->faker()->text(20),
            "amount" => Core::dictionary()->faker()->numberBetween(50, 500),
            "date" => Core::dictionary()->faker()->dateTimeThisYear()->format("Y-m-d H:m:s")
        ];
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("description",
                [["name" => "spending", "optional" => false, "type" => "any"]],
                "Gets a given spending's description.",
                ReturnType::TEXT,
                $this,
                "vc.description(%spending)\nor (shorthand notation):\n%spending.description"
            ),
            new DFunction("amount",
                [["name" => "spending", "optional" => false, "type" => "any"]],
                "Gets a given spending's amount.",
                ReturnType::NUMBER,
                $this,
                "vc.amount(%spending)\nor (shorthand notation):\n%spending.amount"
            ),
            new DFunction("date",
                [["name" => "spending", "optional" => false, "type" => "any"]],
                "Gets a given spending's date.",
                ReturnType::TIME,
                $this,
                "vc.date(%spending)\nor (shorthand notation):\n%spending.date"
            ),
            new DFunction("getVCName",
                [],
                "Gets Virtual Currency name.",
                ReturnType::TEXT,
                $this,
                "vc.getVCName()"
            ),
            new DFunction("getImage",
                [],
                "Gets Virtual Currency image URL.",
                ReturnType::TEXT,
                $this,
                "vc.getImage()"
            ),
            new DFunction("getUserTokens",
                [["name" => "userId", "optional" => false, "type" => "int"]],
                "Gets total tokens for a given user.",
                ReturnType::NUMBER,
                $this,
                "vc.getUserTokens(%user)"
            ),
            new DFunction("getUserSpending",
                [["name" => "userId", "optional" => false, "type" => "int"]],
                "Gets spending for a given user.",
                ReturnType::SPENDING_COLLECTION,
                $this,
                "vc.getUserSpending(%user)"
            ),
            new DFunction("getUserTotalSpending",
                [["name" => "userId", "optional" => false, "type" => "int"]],
                "Gets total spending for a given user.",
                ReturnType::NUMBER,
                $this,
                "vc.getUserTotalSpending(%user)"
            ),
            new DFunction("getUserExchanged",
                [["name" => "userId", "optional" => false, "type" => "int"]],
                "Gets a boolean indicating if the student already exchanged their tokens for XP or not.",
                ReturnType::BOOLEAN,
                $this,
                "vc.getUserExchanged(%user)"
            ),
            new DFunction("exchangeTokensForXP",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "ratio", "optional" => false, "type" => "string"],
                    ["name" => "threshold", "optional" => false, "type" => "int"],
                    ["name" => "extra", "optional" => false, "type" => "bool"]],
                "Exchanges a given user's tokens for XP according to a specific ratio and threshold. Option to give XP as extra credit.",
                ReturnType::VOID,
                $this,
                "vc.exchangeTokensForXP(%user, '3:1', 2000, false)"
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given spending's description.
     *
     * @param $spending
     * @return ValueNode
     * @throws Exception
     */
    public function description($spending): ValueNode
    {
        // NOTE: on mock data, spending will be mocked
        if (!is_array($spending)) throw new InvalidArgumentException("Invalid type for first argument: expected a spending.");
        $description = $spending["description"];
        return new ValueNode($description, Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets a given spending's amount.
     *
     * @param $spending
     * @return ValueNode
     * @throws Exception
     */
    public function amount($spending): ValueNode
    {
        // NOTE: on mock data, spending will be mocked
        if (!is_array($spending)) throw new InvalidArgumentException("Invalid type for first argument: expected a spending.");
        $amount = $spending["amount"];
        return new ValueNode($amount, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }

    /**
     * Gets a given spending's date.
     *
     * @param $spending
     * @return ValueNode
     * @throws Exception
     */
    public function date($spending): ValueNode
    {
        // NOTE: on mock data, spending will be mocked
        if (!is_array($spending)) throw new InvalidArgumentException("Invalid type for first argument: expected a spending.");
        $date = $spending["date"];
        return new ValueNode($date, Core::dictionary()->getLibraryById(TimeLibrary::ID));
    }


    /*** ---------- Config ---------- ***/

    /**
     * Gets Virtual Currency name.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getVCName(): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        $VCModule = new VirtualCurrency($course);
        return new ValueNode($VCModule->getVCName(), Core::dictionary()->getLibraryById(TextLibrary::ID));
    }

    /**
     * Gets Virtual Currency image URL.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getImage(): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        $VCModule = new VirtualCurrency($course);
        return new ValueNode($VCModule->getImage(), Core::dictionary()->getLibraryById(TextLibrary::ID));
    }


    /*** ---------- Wallet ---------- ***/

    /**
     * Gets total tokens for a given user.
     *
     * @param int $userId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserTokens(int $userId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $userTokens = Core::dictionary()->faker()->numberBetween(0, 500);

        } else {
            $VCModule = new VirtualCurrency($course);
            $userTokens = $VCModule->getUserTokens($userId);
        }
        return new ValueNode($userTokens, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** --------- Spending --------- ***/

    /**
     * Gets spending for a given user.
     *
     * @param int $userId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserSpending(int $userId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $spending = array_map(function () use ($userId) {
                return $this->mockSpending($userId);
            }, range(1, Core::dictionary()->faker()->numberBetween(3, 10)));

        } else {
            $VCModule = new VirtualCurrency($course);
            $spending = $VCModule->getUserSpending($userId);
        }
        return new ValueNode($spending, $this);
    }

    /**
     * Gets total spending for a given user.
     *
     * @param int $userId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserTotalSpending(int $userId): ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $userSpending = Core::dictionary()->faker()->numberBetween(0, 500);

        } else {
            $VCModule = new VirtualCurrency($course);
            $userSpending = $VCModule->getUserTotalSpending($userId);
        }
        return new ValueNode($userSpending, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** ---- Exchanging Tokens ----- ***/

    /**
     * Checks if the student already exchanged Tokens for XP.
     *
     * @param int $userId
     * @return ValueNode
     * @throws Exception
     */
    public function getUserExchanged(int $userId): ?ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            $exchanged = Core::dictionary()->faker()->boolean();

        } else {
            $VCModule = new VirtualCurrency($course);
            $exchanged = $VCModule->hasExchanged($userId);
        }
        return new ValueNode($exchanged, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Exchanges a given user's tokens for XP according to
     * a specific ratio and threshold. Option to give XP as
     * extra credit.
     *
     * @param int $userId
     * @param string $ratio
     * @param int|null $threshold
     * @param bool|null $extra
     * @return ValueNode
     * @throws Exception
     */
    public function exchangeTokensForXP(int $userId, string $ratio, ?int $threshold = null, ?bool $extra = true): ?ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        $args = [$userId, $ratio, $threshold, $extra];
        return new ValueNode("exchangeTokensForXP(" . implode(", ", $args) . ")");
    }
}
