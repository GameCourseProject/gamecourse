<?php
namespace GameCourse\Module\Config;

/**
 * This class holds all action scopes available in the module configuration
 * and emulates an enumerator (not available in PHP 7.3).
 */
class ActionScope
{
    /*** ----------------------------------------------- ***/
    /*** ------------------- Scopes -------------------- ***/
    /*** ----------------------------------------------- ***/

    const ALL = "all";                                              // applies to all items
    const FIRST = "first";                                          // applies to the first item only
    const LAST = "last";                                            // applies to the last item only
    const EVEN = "even";                                            // applies to items with even indexes
    const ODD = "odd";                                              // applies to items with odd indexes
    const ALL_BUT_FIRST = "all-but-first";                          // applies to all items except the first one
    const ALL_BUT_LAST = "all-but-last";                            // applies to all items except the last one
    const ALL_BUT_FIRST_AND_LAST = "all-but-first-and-last";        // applies to all items except the first and last ones
    const ALL_BUT_TWO_LAST = "all-but-two-last";                    // applies to all items except the the two last ones
    // NOTE: insert here new scopes of actions
}
