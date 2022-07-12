<?php
namespace GameCourse\Views\Logging;

use GameCourse\Views\ViewHandler;

class DeleteLog extends Log
{
    protected $viewId;

    public function __construct(int $viewId)
    {
        parent::__construct(LogType::DELETE_VIEW);
        $this->viewId = $viewId;
    }


    function process(array $views, int $courseId)
    {
        ViewHandler::deleteView($this->viewId);
    }
}
