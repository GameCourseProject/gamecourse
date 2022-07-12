<?php
namespace GameCourse\Views\Logging;

use GameCourse\Views\ViewHandler;

class MoveLog extends Log
{
    protected $viewRoot;
    protected $from;
    protected $to;

    public function __construct(int $viewRoot, ?array $from, ?array $to)
    {
        parent::__construct(LogType::MOVE_VIEW);
        $this->viewRoot = $viewRoot;
        $this->from = $from;
        $this->to = $to;
    }


    function process(array $views, int $courseId)
    {
        ViewHandler::moveView($this->viewRoot, $this->from, $this->to);
    }
}
