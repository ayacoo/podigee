<?php

declare(strict_types=1);

namespace Ayacoo\Podigee\Tca\DisplayCond;

class IsPodigee
{
    /**
     * @param array<string,mixed> $parameters
     */
    public function match(array $parameters): bool
    {
        $record = $parameters['record'] ?? [];
        if (!is_array($record)) {
            return false;
        }

        return (!empty($record['podigee_thumbnail'] ?? ''));
    }
}
