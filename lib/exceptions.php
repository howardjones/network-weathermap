<?php

class WeathermapRuntimeWarning extends Exception
{
    // These should become warnings in the log for a map
    // (e.g. fonts couldn't be loaded, targets not recognised)
}

class WeathermapInternalFail extends Exception
{
    // this is an assertion failure, to make testing easier
    // (e.g. something was called with invalid arguments)
}

class WeathermapDeprecatedException extends Exception
{
    // this is to fence off old code when refactoring
}
