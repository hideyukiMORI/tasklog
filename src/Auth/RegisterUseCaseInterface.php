<?php

declare(strict_types=1);

namespace Tasklog\Auth;

interface RegisterUseCaseInterface
{
    /** @throws EmailAlreadyTakenException */
    public function execute(RegisterInput $input): RegisterOutput;
}
