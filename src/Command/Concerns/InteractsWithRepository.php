<?php

namespace PHPacker\PHPacker\Command\Concerns;

use PHPacker\PHPacker\Services\GitHub;

trait InteractsWithRepository
{
    protected function repository(): GitHub
    {
        return once(function () {
            return new GitHub($this->repository, $this->repositoryDir);
        });
    }
}
