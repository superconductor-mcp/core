<?php

namespace Fabricate\Contracts\Schema\Messaging\Sampling;

use Fabricate\Contracts\Schema\Capabilities\SamplingMessage;
use Fabricate\Contracts\Schema\Messaging\Result;

interface CreateMessageResult extends Result, SamplingMessage
{

}
