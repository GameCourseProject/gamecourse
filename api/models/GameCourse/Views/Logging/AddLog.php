<?php
namespace GameCourse\Views\Logging;

use Exception;
use GameCourse\Views\Aspect\Aspect;
use GameCourse\Views\CreationMode;
use GameCourse\Views\ViewHandler;

class AddLog extends Log
{
    protected $viewId;
    protected $mode;

    public function __construct(int $viewId, string $mode)
    {
        parent::__construct(LogType::ADD_VIEW);
        $this->viewId = $viewId;
        $this->mode = $mode;
    }


    /**
     * @throws Exception
     */
    function process(array $views, int $courseId)
    {
        if ($this->mode == CreationMode::BY_REFERENCE) return;

        $view = $views[$this->viewId];
        $aspect = Aspect::getAspectInView($view, $courseId);
        ViewHandler::insertView($view, $aspect);
    }
}
