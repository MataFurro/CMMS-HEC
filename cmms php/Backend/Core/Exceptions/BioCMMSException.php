<?php

namespace Backend\Core\Exceptions;

use Exception;

class BioCMMSException extends Exception
{
}

class DatabaseException extends BioCMMSException
{
}

class ValidationException extends BioCMMSException
{
}

class NotFoundException extends BioCMMSException
{
}
