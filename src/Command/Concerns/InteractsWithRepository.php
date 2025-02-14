<?php

namespace PHPacker\PHPacker\Command\Concerns;

use PHPacker\PHPacker\Services\GitHub;
use PHPacker\PHPacker\Contracts\RemoteRepositoryService;

trait InteractsWithRepository
{
    /*
    * We're not using a DI container yet.
    * Use this if we need to swap the implementation
    */
    protected function repository(): RemoteRepositoryService
    {
        return once(function () {
            return new GitHub($this->repository);
        });
    }
}
