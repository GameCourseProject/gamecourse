<?php
namespace GameCourse\Views\Logging;

use Exception;
use GameCourse\Views\ViewHandler;

class EditLog extends Log
{
    protected $viewId;

    public function __construct(int $viewId)
    {
        parent::__construct(LogType::EDIT_VIEW);
        $this->viewId = $viewId;
    }


    /**
     * @throws Exception
     */
    function process(array $views, int $courseId)
    {
        $view = $views[$this->viewId];
        $aspect = ViewHandler::getViewAspect($view, $courseId);
        ViewHandler::updateView($view, $aspect);
    }
}
