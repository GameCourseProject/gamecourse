<?php
namespace GameCourse\Views\Dictionary;

use Exception;
use GameCourse\Core\Core;
use GameCourse\Views\ExpressionLanguage\ValueNode;
use GameCourse\Views\Page\Page;

class PagesLibrary extends Library
{
    public function __construct()
    {
        parent::__construct(self::ID, self::NAME, self::DESCRIPTION);
    }


    /*** ----------------------------------------------- ***/
    /*** ------------------ Metadata ------------------- ***/
    /*** ----------------------------------------------- ***/

    const ID = "pages";    // NOTE: must match the name of the class
    const NAME = "Pages";
    const DESCRIPTION = "Provides access to information regarding course pages.";


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

    /*** --------- Getters ---------- ***/

    /**
     * Gets a given page's ID in the system.
     *
     * @param $page
     * @return ValueNode
     * @throws Exception
     */
    public function id($page): ValueNode
    {
        // NOTE: on mock data, user will be mocked
        if (is_array($page)) $pageId = $page["id"];
        else $pageId = $page->getId();
        return new ValueNode($pageId, Core::dictionary()->getLibraryById(MathLibrary::ID));
    }


    /*** --------- General ---------- ***/

    /**
     * Gets a page by its name.
     *
     * @param string $name
     * @return ValueNode
     * @throws Exception
     */
    public function getPageByName(string $name): ValueNode
    {
        if (Core::dictionary()->mockData()) {
            // TODO: mock page
            $page = [];

        } else {
            $courseId = Core::dictionary()->getCourse()->getId();
            $page = Page::getPageByName($courseId, $name);
            if (!$page) throw new Exception("Page '$name' doesn't exist in course with ID = $courseId");
        }
        return new ValueNode($page, $this);
    }
}
