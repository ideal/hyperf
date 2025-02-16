<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Tracer\Listener;

use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SwitchManager;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use OpenTracing\Tracer;

/**
 * @Listener
 */
class DbQueryExecutedListener implements ListenerInterface
{
    use SpanStarter;

    /**
     * @var Tracer
     */
    private $tracer;

    /**
     * @var SwitchManager
     */
    private $switchManager;

    public function __construct(Tracer $tracer, SwitchManager $switchManager)
    {
        $this->tracer = $tracer;
        $this->switchManager = $switchManager;
    }

    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event)
    {
        if ($this->switchManager->isEnable('db') === false) {
            return;
        }
        $sql = $event->sql;
        if (! Arr::isAssoc($event->bindings)) {
            foreach ($event->bindings as $key => $value) {
                $sql = Str::replaceFirst('?', "'{$value}'", $sql);
            }
        }

        $endTime = microtime(true);
        $span = $this->startSpan('db.query', [
            'start_time' => (int) (($endTime - $event->time / 1000) * 1000 * 1000),
        ]);
        $span->setTag('db.sql', $sql);
        $span->setTag('db.query_time', $event->time . ' ms');
        $span->finish((int) ($endTime * 1000 * 1000));
    }
}
