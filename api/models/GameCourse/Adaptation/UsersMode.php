<?php

namespace GameCourse\Adaptation;

/**
 * This class holds all types of usersMode in the EditableGameElement
 * and emulates an enumerator (not available in PHP 7.3).
 * It allows different users to edit.
 */
class UsersMode
{
    const ALL_USERS = "all-users";                  // All users in the system can edit
    const ALL_EXCEPT_USERS = "all-except-users";    // All users except some in the system can edit
    const ONLY_SOME_USERS = "only-some-users";      // Only some users can edit
}