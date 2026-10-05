<?php

namespace App\Services\NapoliInfo;

interface SourceProvider
{
    public function id(): string;

    public function name(): string;

    /** @return list<array{external_id:string,source_url:string,title:string,raw_hash:string}> */
    public function fetch(): array;
}
