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
            // TODO
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above


    /*** ---------- Config ---------- ***/

    /**
     * TODO: description
     *
     * @return ValueNode
     * @throws Exception
     */
    public function isEnabled(): ValueNode
    {
        $course = Core::dictionary()->getCourse();
        $isEnabled = $course->isModuleEnabled(VirtualCurrency::ID);
        return new ValueNode($isEnabled, Core::dictionary()->getLibraryById(BoolLibrary::ID));
    }

    /**
     * Gets Virtual Currency name.
     *
     * @return ValueNode
     * @throws Exception
     */
    public function getVCName(): ValueNode
    {
        $VCModule = new VirtualCurrency(Core::dictionary()->getCourse());
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
        $VCModule = new VirtualCurrency(Core::dictionary()->getCourse());
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
        if (Core::dictionary()->mockData()) {
            $userTokens = Core::dictionary()->faker()->numberBetween(0, 500);

        } else {
            $VCModule = new VirtualCurrency(Core::dictionary()->getCourse());
            $userTokens = $VCModule->getUserTokens($userId);
        }
        return new ValueNode($userTokens, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** ---- Exchanging Tokens ----- ***/

    /**
     * Exchanges a given user's tokens for XP according to
     * a specific ratio and threshold.
     * Returns the total amount of XP earned.
     *
     * @param int $userId
     * @param float $ratio
     * @param int|null $threshold
     * @return ValueNode
     * @throws Exception
     */
    public function exchangeTokensForXP(int $userId, float $ratio = 1, ?int $threshold = null): ?ValueNode
    {
        if (Core::dictionary()->mockData()) {
           // Do nothing

        } else {
            $VCModule = new VirtualCurrency(Core::dictionary()->getCourse());
            $VCModule->exchangeTokensForXP($userId, $ratio, $threshold);
        }
        return null;
    }
}