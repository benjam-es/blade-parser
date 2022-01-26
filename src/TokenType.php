<?php

namespace RyanChandler\BladeParser;

enum TokenType
{
    case Directive;
    case Echo;
    case Comment;
    case Literal;
    case Php;
    case Verbatim;
}
