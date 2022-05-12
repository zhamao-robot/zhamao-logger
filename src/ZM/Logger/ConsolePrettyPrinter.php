<?php

declare(strict_types=1);

namespace ZM\Logger;

/**
 * 输出漂亮的表格参数形式，支持自适应终端大小
 *
 * Class ConsolePrettyPrinter
 */
class ConsolePrettyPrinter
{
    protected $params;

    protected $head;

    protected $foot;

    public function __construct(array $params, $head = '', $foot = '')
    {
        $this->params = $params;
        $this->head = $head === '' ? str_pad('', 65, '=') : $head;
        $this->foot = $foot === '' ? str_pad('', 65, '=') : $foot;
    }

    public static function createFromArray(array $params): ConsolePrettyPrinter
    {
        return new static($params);
    }

    public function printAll(): void
    {
        $this->printHead();
        $this->printBody();
        $this->printFoot();
    }

    public function printHead()
    {
        // TODO: 写头
    }

    public function printBody()
    {
        // TODO: 写主体
    }

    public function printFoot()
    {
        // TODO: 写尾
    }
}
