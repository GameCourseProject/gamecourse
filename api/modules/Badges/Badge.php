<?php
namespace GameCourse\Badges;

/**
 * This is the Badge model, which implements the necessary methods
 * to interact with badges in the MySQL database.
 */
class Badge
{
    const TABLE_BADGE = 'badge';

    const HEADERS = [   // headers for import/export functionality
        "name", "description", "image", "maxLevel", "isExtra", "isBragging", "isCount", "isPost", "isPoint", "isActive",
        "desc1", "xp1", "count1", "desc2", "xp2", "count2", "desc3", "xp3", "count3"
    ];
}
