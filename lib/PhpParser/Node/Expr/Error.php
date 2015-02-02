<?php

namespace PhpParser\Node\Expr;

use PhpParser\Node\Expr;

/**
 * Node representing an invalid expression.
 *
 * This node may be inserted in the AST if the parser is run
 * with error recovery enabled.
 */
class Error extends Expr {
    /**
     * Constructs an error expression node.
     *
     * @param array $attributes Additional attributes
     */
    public function __construct($attributes = array()) {
        parent::__construct(array(), $attributes);
    }
}
