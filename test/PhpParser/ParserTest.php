<?php

namespace PhpParser;

use PhpParser\Comment;
use PhpParser\Node\Scalar\String;

require_once __DIR__ . '/CodeTestAbstract.php';

class ParserTest extends CodeTestAbstract
{
    /**
     * @dataProvider provideTestParse
     */
    public function testParse($name, $code, $dump) {
        $parser = new Parser(new Lexer\Emulative);
        $dumper = new NodeDumper;

        $stmts = $parser->parse($code);
        $this->assertSame(
            $this->canonicalize($dump),
            $this->canonicalize($dumper->dump($stmts)),
            $name
        );
    }

    public function provideTestParse() {
        return $this->getTests(__DIR__ . '/../code/parser', 'test');
    }

    /**
     * @dataProvider provideTestParseFail
     */
    public function testParseFail($name, $code, $msg) {
        $errors = array();
        $parser = new Parser(new Lexer\Emulative, array(
            'errorCallback' => function($msg, $line) use(&$errors) {
                $errors[] = $msg . ' on line ' . $line;
            }
        ));
        $dumper = new NodeDumper;

        try {
            $stmts = $parser->parse($code);
            if ($stmts != array()) {
                $errors[] = $dumper->dump($stmts);
            }
        } catch (Error $e) {
            $errors[] = $e->getMessage();
        }

        $this->assertSame($this->canonicalize($msg), implode("\n", $errors), $name);
    }

    public function provideTestParseFail() {
        return $this->getTests(__DIR__ . '/../code/parser', 'test-fail');
    }

    public function testAttributeAssignment() {
        $lexer = new Lexer(array(
            'usedAttributes' => array(
                'comments', 'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            )
        ));

        $code = <<<'EOC'
<?php
/** Doc comment */
function test($a) {
    // Line
    // Comments
    echo $a;
}
EOC;
        $code = $this->canonicalize($code);

        $parser = new Parser($lexer);
        $stmts = $parser->parse($code);

        /** @var \PhpParser\Node\Stmt\Function_ $fn */
        $fn = $stmts[0];
        $this->assertInstanceOf('PhpParser\Node\Stmt\Function_', $fn);
        $this->assertEquals(array(
            'comments' => array(
                new Comment\Doc('/** Doc comment */', 2),
            ),
            'startLine' => 3,
            'endLine' => 7,
            'startTokenPos' => 3,
            'endTokenPos' => 21,
        ), $fn->getAttributes());

        $param = $fn->params[0];
        $this->assertInstanceOf('PhpParser\Node\Param', $param);
        $this->assertEquals(array(
            'startLine' => 3,
            'endLine' => 3,
            'startTokenPos' => 7,
            'endTokenPos' => 7,
        ), $param->getAttributes());

        /** @var \PhpParser\Node\Stmt\Echo_ $echo */
        $echo = $fn->stmts[0];
        $this->assertInstanceOf('PhpParser\Node\Stmt\Echo_', $echo);
        $this->assertEquals(array(
            'comments' => array(
                new Comment("// Line\n", 4),
                new Comment("// Comments\n", 5),
            ),
            'startLine' => 6,
            'endLine' => 6,
            'startTokenPos' => 16,
            'endTokenPos' => 19,
        ), $echo->getAttributes());

        /** @var \PhpParser\Node\Expr\Variable $var */
        $var = $echo->exprs[0];
        $this->assertInstanceOf('PhpParser\Node\Expr\Variable', $var);
        $this->assertEquals(array(
            'startLine' => 6,
            'endLine' => 6,
            'startTokenPos' => 18,
            'endTokenPos' => 18,
        ), $var->getAttributes());
    }

    /**
     * @expectedException \RangeException
     * @expectedExceptionMessage The lexer returned an invalid token (id=999, value=foobar)
     */
    public function testInvalidToken() {
        $lexer = new InvalidTokenLexer;
        $parser = new Parser($lexer);
        $parser->parse('dummy');
    }
}

class InvalidTokenLexer extends Lexer {
    public function getNextToken(&$value = null, &$startAttributes = null, &$endAttributes = null) {
        $value = 'foobar';
        return 999;
    }
}
