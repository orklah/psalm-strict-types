<?php
declare(strict_types=1);

namespace Orklah\StrictTypes\Utils;

class StrictCheckResult
{
    /**
     * @var bool
     */
    public $is_correct;
    /**
     * @var bool
     */
    public $is_partial;
    /**
     * @var bool
     */
    public $is_mixed;

    public function __construct(bool $is_correct, bool $is_partial, bool $is_mixed){

        $this->is_correct = $is_correct;
        $this->is_partial = $is_partial;
        $this->is_mixed = $is_mixed;
    }
}
