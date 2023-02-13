<?php
namespace GameCourse\Views\Dictionary;

use GameCourse\Core\Core;
use GameCourse\User\User;
use GameCourse\Views\ExpressionLanguage\ValueNode;

class UsersLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "users";    // NOTE: must match the name of the class
    const NAME = "Users";
    const DESCRIPTION = "Provides access to information regarding users.";


    /*** ----------------------------------------------- ***/
    /*** ------------------ Functions ------------------ ***/
    /*** ----------------------------------------------- ***/

    public function getFunctions(): ?array
    {
        return [
            new DFunction("getUsers",
                "Gets users in the system. Option to retrieve only active users.",
                ReturnType::COLLECTION,
                $this
            )
        ];
    }

    // NOTE: add new library functions bellow & update its
    //       metadata in 'getFunctions' above

    public function getUsers(?bool $active = null): ValueNode
    {
        // TODO: mock data
        return new ValueNode(User::getUsers($active), Core::dictionary()->getLibraryById(CollectionLibrary::ID));
    }

    public function getUser(int $userId): ValueNode
    {
        // TODO: mock data
        $user = User::getUserById($userId);
        return new ValueNode($user, $this);
    }

    public function name($user): ValueNode
    {
        // TODO: mock data
        if (is_array($user)) $user = User::getUserById($user["id"]);
        return new ValueNode($user->getName(), Core::dictionary()->getLibraryById(TextLibrary::ID));
    }
}
