<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Module\VirtualCurrency\VirtualCurrency;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class VCLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }

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
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "vc";    // NOTE: must match the name of the class
    const NAME = "Virtual Currency";
    const DESCRIPTION = "Provides access to information regarding virtual currency.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("getVCName",
                [],
                "Gets Virtual Currency name.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("getImage",
                [],
                "Gets Virtual Currency image URL.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("getUserTokens",
                [["name" => "userId", "optional" => false, "type" => "int"]],
                "Gets total tokens for a given user.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("description",
                [["name" => "spending", "optional" => false, "type" => "any"]],
                "Gets a given spending's description.",
                ReturnType::TEXT,
                $this
            ),
            new DFunction("amount",
                [["name" => "spending", "optional" => false, "type" => "any"]],
                "Gets a given spending's amount.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("date",
                [["name" => "spending", "optional" => false, "type" => "any"]],
                "Gets a given spending's date.",
                ReturnType::TIME,
                $this
            ),
            new DFunction("getUserSpending",
                [["name" => "userId", "optional" => false, "type" => "int"]],
                "Gets spending for a given user.",
                ReturnType::COLLECTION,
                $this
            ),
            new DFunction("getUserTotalSpending",
                [["name" => "userId", "optional" => false, "type" => "int"]],
                "Gets total spending for a given user.",
                ReturnType::NUMBER,
                $this
            ),
            new DFunction("exchangeTokensForXP",
                [["name" => "userId", "optional" => false, "type" => "int"],
                    ["name" => "ratio", "optional" => true, "type" => "float"],
                    ["name" => "threshold", "optional" => true, "type" => "int"],
                    ["name" => "extra", "optional" => true, "type" => "bool"]],
                "Exchanges a given user's tokens for XP according to a specific ratio and threshold. Option to give XP as extra credit.",
                ReturnType::VOID,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above


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
     * Gets a given spending's description.
     *
     * @param $spending
     * @return ValueNode
     * @throws Exception
     */
    public function description($spending): ValueNode
    {
        // NOTE: on mock data, spending will be mocked
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
        $date = $spending["date"];
        return new ValueNode($date, Core::dictionary()->getLibraryById(TimeLibrary::ID));
    }

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
     * Exchanges a given user's tokens for XP according to
     * a specific ratio and threshold. Option to give XP as
     * extra credit.
     *
     * @param int $userId
     * @param float $ratio
     * @param int|null $threshold
     * @param bool|null $extra
     * @return ValueNode
     * @throws Exception
     */
    public function exchangeTokensForXP(int $userId, float $ratio = 1, ?int $threshold = null, ?bool $extra = true): ?ValueNode
    {
        // Check permissions
        $viewerId = intval(Core::dictionary()->getVisitor()->getParam("viewer"));
        $course = Core::dictionary()->getCourse();
        $this->requireCoursePermission("getCourseById", $course->getId(), $viewerId);

        if (Core::dictionary()->mockData()) {
            // Do nothing
            return null;

        } else {
            $VCModule = new VirtualCurrency($course);
            $VCModule->exchangeTokensForXP($userId, $ratio, $threshold, $extra);
        }
        return null;
    }
}
