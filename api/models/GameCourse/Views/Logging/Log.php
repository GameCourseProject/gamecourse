<?php
namespace GameCourse\Views\Logging;

abstract class Log
{
    protected $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }


    abstract function process(array $views, int $courseId);
}
