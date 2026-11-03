<?php

namespace Fabricate\Schema\Common\Messaging;

use Fabricate\Contracts\Schema\Messaging\ClientResult;
use Fabricate\Contracts\Schema\Messaging\EmptyResult as EmptyContract;
use Fabricate\Contracts\Schema\Messaging\ServerResult;

readonly class EmptyResult extends Result implements EmptyContract, ClientResult, ServerResult
{

}
